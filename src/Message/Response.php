<?php

namespace App\Support\HttpClient\Message;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class Response implements ResponseInterface
{
    private int            $statusCode;
    private string         $reasonPhrase = '';
    private array          $headers      = [];
    private StreamInterface $body;
    private string         $protocol     = '1.1';

    public function __construct(
        int $statusCode = 200,
        array $headers = [],
        ?StreamInterface $body = null,
        string $reasonPhrase = ''
    ) {
        $this->statusCode   = $statusCode;
        $this->headers      = $this->normalizeHeaders($headers);
        $this->body         = $body ?? new Stream();
        $this->reasonPhrase = $reasonPhrase;
    }

    private function normalizeHeaders(array $h): array
    {
        $out = [];
        foreach ($h as $name => $vals) {
            $out[strtolower($name)] = array_values((array)$vals);
        }
        return $out;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }
    public function withStatus($code, $reasonPhrase = ''): self
    {
        $clone = clone $this;
        $clone->statusCode   = $code;
        $clone->reasonPhrase = $reasonPhrase;
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
