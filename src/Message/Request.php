<?php

namespace App\Support\HttpClient\Message;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class Request implements RequestInterface
{
    private string         $method;
    private UriInterface   $uri;
    private array          $headers = [];
    private StreamInterface $body;
    private string         $protocol = '1.1';
    private ?string        $target   = null;

    public function __construct(
        string $method,
        UriInterface $uri,
        array $headers = [],
        ?StreamInterface $body = null
    ) {
        $this->method  = strtoupper($method);
        $this->uri     = $uri;
        $this->headers = $this->normalizeHeaders($headers);
        $this->body    = $body ?? new Stream();
    }

    private function normalizeHeaders(array $h): array
    {
        $out = [];
        foreach ($h as $name => $vals) {
            $out[strtolower($name)] = array_values((array)$vals);
        }
        return $out;
    }

    public function getRequestTarget(): string
    {
        if ($this->target !== null) {
            return $this->target;
        }
        $path = $this->uri->getPath() ?: '/';
        $q    = $this->uri->getQuery();
        return $path . ($q !== '' ? '?' . $q : '');
    }

    public function withRequestTarget($target): self
    {
        $clone = clone $this;
        $clone->target = $target;
        return $clone;
    }

    public function getMethod(): string
    {
        return $this->method;
    }
    public function withMethod($method): self
    {
        $clone = clone $this;
        $clone->method = strtoupper($method);
        return $clone;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }
    public function withUri(UriInterface $uri, $preserveHost = false): self
    {
        $clone = clone $this;
        $clone->uri = $uri;
        if (!$preserveHost) {
            $host = $uri->getHost();
            if ($host !== '') {
                $clone->headers['host'] = [$host . ($uri->getPort() ? ':' . $uri->getPort() : '')];
            }
        }
        return $clone;
    }

    public function getProtocolVersion(): string
    {
        return $this->protocol;
    }
    public function withProtocolVersion($version): self
    {
        $clone = clone $this;
        $clone->protocol = $version;
        return $clone;
    }

    public function getHeaders(): array
    {
        $out = [];
        foreach ($this->headers as $name => $vals) {
            $out[$name] = $vals;
        }
        return $out;
    }

    public function hasHeader($name): bool
    {
        return isset($this->headers[strtolower($name)]);
    }

    public function getHeader($name): array
    {
        return $this->headers[strtolower($name)] ?? [];
    }

    public function getHeaderLine($name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader($name, $value): self
    {
        $clone = clone $this;
        $clone->headers[strtolower($name)] = array_values((array)$value);
        return $clone;
    }

    public function withAddedHeader($name, $value): self
    {
        $clone = clone $this;
        $lower = strtolower($name);
        $clone->headers[$lower] = array_merge(
            $clone->headers[$lower] ?? [],
            (array)$value
        );
        return $clone;
    }

    public function withoutHeader($name): self
    {
        $clone = clone $this;
        unset($clone->headers[strtolower($name)]);
        return $clone;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }
    public function withBody(StreamInterface $body): self
    {
        $clone = clone $this;
        $clone->body = $body;
        return $clone;
    }
}
