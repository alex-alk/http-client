<?php

namespace HttpClient\Factory;

use Fig\Http\Message\RequestMethodInterface;
use HttpClient\Message\Request;
use HttpClient\Message\Uri;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;

class RequestFactory implements RequestFactoryInterface, RequestMethodInterface
{
    public function createRequest(string $method, $uri): RequestInterface
    {
        return new Request($method, new Uri($uri));
    }
}
