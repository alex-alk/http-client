<?php

namespace HttpClient\Exception;

use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Throwable;

/**
 * Thrown when an HTTP request cannot be sent.
 */
class RequestException extends \RuntimeException implements RequestExceptionInterface
{
    /** @var RequestInterface */
    private RequestInterface $request;

    /**
     * @param string             $message  The Exception message to throw
     * @param RequestInterface   $request  The request which caused the exception
     * @param int                $code     The Exception code
     * @param \Throwable|null    $previous The previous exception for chaining
     */
    public function __construct(
        string $message,
        RequestInterface $request,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->request = $request;
    }

    /**
     * Retrieves the request which caused the exception.
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
