<?php

namespace App\Support\HttpClient\Factory;

use App\Support\HttpClient\Message\Stream;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class StreamFactory implements StreamFactoryInterface
{
    public function createStream(string $content = ''): StreamInterface
    {
        return new Stream($content);
    }

    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        return new Stream();
    }

    public function createStreamFromResource($resource): StreamInterface
    {
        return new Stream;
    }
}
