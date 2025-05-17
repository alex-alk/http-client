<?php

namespace App\Support\HttpClient\Factory;

use App\Support\HttpClient\Message\Request;
use App\Support\HttpClient\Message\Uri;
use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;

class RequestFactory implements RequestFactoryInterface, RequestMethodInterface
{
    public function createRequest(string $method, $uri): RequestInterface
    {
        return new Request($method, new Uri($uri));
    }
}
