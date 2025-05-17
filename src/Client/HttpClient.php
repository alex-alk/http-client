<?php

namespace HttpClient\Client;

use HttpClient\Exception\RequestException;
use HttpClient\Factory\RequestFactory;
use HttpClient\Factory\ResponseFactory;
use HttpClient\Factory\StreamFactory;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * How to create parallel requests:
 * $r1 = $factory->createRequest(...);
 * $r2 = $factory->createRequest(...);
 * $responses = $client->sendRequests[$r1, $2];
 * foreach $responses as $response...
 */
class HttpClient implements ClientInterface
{

    private array $extraOptions = [];

    public function __construct(
        protected ?RequestFactoryInterface $requestFactory = null,
        protected ?StreamFactoryInterface $streamFactory = null,
        protected ?ResponseFactoryInterface $responseFactory = null
    ) {
        $this->requestFactory = $requestFactory ?: new RequestFactory;
        $this->streamFactory = $streamFactory ?: new StreamFactory;
        $this->responseFactory = $responseFactory ?: new ResponseFactory;
    }

    public function setExtraOptions(array $extraOptions): void
    {
        $this->extraOptions = $extraOptions;
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $request = $this->requestFactory->createRequest($method, $url);

        if (isset($options['body'])) {
            $body = $this->streamFactory->createStream($options['body']);
            $request = $request->withBody($body);
        }

        foreach ($options['headers'] ?? [] as $k => $header) {
            $request = $request->withHeader($k, $header);
        }

        return $this->sendRequest($request);
    }

    /**
     * Send a large set of requests in parallel, in batches.
     *
     * @param RequestInterface[] $requests   Array of PSR-7 requests
     * @param int                $batchSize How many to dispatch concurrently per batch
     * @return ResponseInterface[] Responses in same order as $requests
     * @throws ClientExceptionInterface on any failure
     */
    public function sendRequests(array $requests, int $batchSize = 10): array
    {
        $allResponses = [];
        // chunk preserving original keys
        $chunks = array_chunk($requests, $batchSize, true);

        foreach ($chunks as $chunk) {
            $responses = $this->sendBatch($chunk);
            // merge preserving keys
            $allResponses += $responses;
        }

        // sort by original keys and reindex
        ksort($allResponses);
        return array_values($allResponses);
    }

    /**
     * Internal: send one batch (up to batchSize) in parallel.
     *
     * @param RequestInterface[] $requests
     * @return ResponseInterface[] keyed by original request array keys
     * @throws ClientExceptionInterface
     */
    private function sendBatch(array $requests): array
    {
        $multi       = curl_multi_init();
        $handles     = [];
        $headerStore = [];

        // 1) init handles
        foreach ($requests as $key => $request) {
            $ch = curl_init((string)$request->getUri());
            curl_setopt_array($ch, $this->extraOptions + [
                CURLOPT_CUSTOMREQUEST  => $request->getMethod(),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HEADER         => false,
            ]);

            // headers
            $hdrs = [];
            foreach ($request->getHeaders() as $n => $vals) {
                foreach ($vals as $v) {
                    $hdrs[] = "$n: $v";
                }
            }
            if ($hdrs) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $hdrs);
            }
            // body
            $body = (string)$request->getBody();
            if ($body !== '') {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
            // header parser
            $headerStore[(int)$ch] = [];
            curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, string $line) use (&$headerStore) {
                $trim = trim($line);
                if ($trim === '' || strpos($trim, 'HTTP/') === 0) {
                    return strlen($line);
                }
                if (strpos($trim, ':') !== false) {
                    [$n, $v] = explode(':', $trim, 2);
                    $headerStore[(int)$ch][trim($n)][] = trim($v);
                }
                return strlen($line);
            });

            curl_multi_add_handle($multi, $ch);
            $handles[$key] = $ch;
        }

        // 2) execute
        do {
            $status = curl_multi_exec($multi, $active);
            if ($status > CURLM_OK) {
                break;
            }
            curl_multi_select($multi);
        } while ($active);

        // 3) collect
        $responses = [];
        foreach ($handles as $key => $ch) {
            if (($err = curl_error($ch)) !== '') {
                curl_multi_remove_handle($multi, $ch);
                curl_close($ch);
                curl_multi_close($multi);
                throw new class($err) extends \RuntimeException implements ClientExceptionInterface {};
            }

            $body       = curl_multi_getcontent($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $headers    = $headerStore[(int)$ch] ?? [];

            // build PSR-7 response
            $resp = $this->responseFactory->createResponse($statusCode);
            foreach ($headers as $n => $vals) {
                foreach ($vals as $v) {
                    $resp = $resp->withAddedHeader($n, $v);
                }
            }
            $resp = $resp->withBody($this->streamFactory->createStream($body));

            $responses[$key] = $resp;

            curl_multi_remove_handle($multi, $ch);
            curl_close($ch);
        }

        curl_multi_close($multi);
        return $responses;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, (string)$request->getUri());
        curl_setopt_array($ch, $this->extraOptions + [
            CURLOPT_CUSTOMREQUEST  => $request->getMethod(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HEADER         => false,
        ]);

        // if (isset($settings['verify_peer'])) {
        //     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $settings['verify_peer']);
        // } else {
        //     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // }

        // headers
        $hdrs = [];
        foreach ($request->getHeaders() as $name => $vals) {
            foreach ($vals as $v) {
                $hdrs[] = "$name: $v";
            }
        }
        if ($hdrs) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $hdrs);
        }

        // body
        $body = (string)$request->getBody();
        if ($body !== '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        // capture headers
        $responseHeaders = [];
        curl_setopt(
            $ch,
            CURLOPT_HEADERFUNCTION,
            function ($ch, string $line) use (&$responseHeaders) {
                $trim = trim($line);
                if ($trim === '' || strpos($trim, 'HTTP/') === 0) {
                    return strlen($line);
                }
                if (strpos($trim, ':') !== false) {
                    [$n, $v] = explode(':', $trim, 2);
                    $responseHeaders[trim($n)][] = trim($v);
                }
                return strlen($line);
            }
        );

        // execute
        $respBody = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($respBody === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new RequestException($err, $request, $status);
        }

        
        curl_close($ch);

        // build response via factory
        $resp = $this->responseFactory->createResponse($status);
        foreach ($responseHeaders as $n => $vals) {
            foreach ($vals as $v) {
                $resp = $resp->withAddedHeader($n, $v);
            }
        }
        $resp = $resp->withBody(
            $this->streamFactory->createStream($respBody)
        );

        return $resp;
    }
}
