<?php

namespace HttpClient\Message;

use Psr\Http\Message\UriInterface;

class Uri implements UriInterface
{
    private string $scheme    = '';
    private string $userInfo  = '';
    private string $host      = '';
    private ?int   $port      = null;
    private string $path      = '';
    private string $query     = '';
    private string $fragment  = '';

    public function __construct(string $uri = '')
    {
        if ($uri !== '') {
            $parts = parse_url($uri);
            if ($parts === false) {
                throw new \InvalidArgumentException("Malformed URI: $uri");
            }
            $this->scheme   = isset($parts['scheme']) ? strtolower($parts['scheme']) : '';
            $this->userInfo = $parts['user']
                ?? '';
            if (isset($parts['pass'])) {
                $this->userInfo .= ':' . $parts['pass'];
            }
            $this->host    = $parts['host'] ?? '';
            $this->port    = $parts['port'] ?? null;
            $this->path    = $parts['path'] ?? '';
            $this->query   = $parts['query'] ?? '';
            $this->fragment = $parts['fragment'] ?? '';
        }
    }

    public function __toString(): string
    {
        $uri = '';
        if ($this->scheme !== '') {
            $uri .= $this->scheme . '://';
        }
        if ($this->userInfo !== '') {
            $uri .= $this->userInfo . '@';
        }
        $uri .= $this->host;
        if ($this->port !== null) {
            $uri .= ':' . $this->port;
        }
        $uri .= $this->path;
        if ($this->query !== '') {
            $uri .= '?' . $this->query;
        }
        if ($this->fragment !== '') {
            $uri .= '#' . $this->fragment;
        }
        return $uri;
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }
    public function getAuthority(): string
    {
        $auth = '';
        if ($this->userInfo !== '') {
            $auth .= $this->userInfo . '@';
        }
        $auth .= $this->host;
        if ($this->port !== null) {
            $auth .= ':' . $this->port;
        }
        return $auth;
    }
    public function getUserInfo(): string
    {
        return $this->userInfo;
    }
    public function getHost(): string
    {
        return $this->host;
    }
    public function getPort(): ?int
    {
        return $this->port;
    }
    public function getPath(): string
    {
        return $this->path;
    }
    public function getQuery(): string
    {
        return $this->query;
    }
    public function getFragment(): string
    {
        return $this->fragment;
    }

    public function withScheme($scheme): self
    {
        $clone = clone $this;
        $clone->scheme = strtolower($scheme);
        return $clone;
    }
    public function withUserInfo($user, $password = null): self
    {
        $clone = clone $this;
        $clone->userInfo = $user . ($password !== null ? ':' . $password : '');
        return $clone;
    }
    public function withHost($host): self
    {
        $clone = clone $this;
        $clone->host = $host;
        return $clone;
    }
    public function withPort($port): self
    {
        $clone = clone $this;
        $clone->port = $port;
        return $clone;
    }
    public function withPath($path): self
    {
        $clone = clone $this;
        $clone->path = $path;
        return $clone;
    }
    public function withQuery($query): self
    {
        $clone = clone $this;
        $clone->query = ltrim((string)$query, '?');
        return $clone;
    }
    public function withFragment($fragment): self
    {
        $clone = clone $this;
        $clone->fragment = ltrim((string)$fragment, '#');
        return $clone;
    }
}
