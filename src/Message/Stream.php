<?php

namespace App\Support\HttpClient\Message;

use Psr\Http\Message\StreamInterface;

class Stream implements StreamInterface
{
    private $resource;

    public function __construct(string $content = '')
    {
        $this->resource = fopen('php://temp', 'r+');
        if ($content !== '') {
            fwrite($this->resource, $content);
            rewind($this->resource);
        }
    }

    public function __toString(): string
    {
        try {
            $this->seek(0);
            return $this->getContents();
        } catch (\Throwable $e) {
            return '';
        }
    }

    public function close(): void
    {
        if (is_resource($this->resource)) {
            fclose($this->resource);
        }
    }

    public function detach()
    {
        $res = $this->resource;
        $this->resource = null;
        return $res;
    }

    public function getSize(): ?int
    {
        $stats = fstat($this->resource);
        return $stats['size'] ?? null;
    }

    public function tell(): int
    {
        return ftell($this->resource);
    }

    public function eof(): bool
    {
        return feof($this->resource);
    }

    public function isSeekable(): bool
    {
        $meta = stream_get_meta_data($this->resource);
        return $meta['seekable'];
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        if (!$this->isSeekable()) {
            throw new \RuntimeException('Stream is not seekable');
        }
        fseek($this->resource, $offset, $whence);
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        $meta = stream_get_meta_data($this->resource);
        return strpbrk($meta['mode'], 'waxc+') !== false;
    }

    public function write($string): int
    {
        if (!$this->isWritable()) {
            throw new \RuntimeException('Stream is not writable');
        }
        return fwrite($this->resource, $string);
    }

    public function isReadable(): bool
    {
        $meta = stream_get_meta_data($this->resource);
        return strpbrk($meta['mode'], 'r+') !== false;
    }

    public function read($length): string
    {
        if (!$this->isReadable()) {
            throw new \RuntimeException('Stream is not readable');
        }
        return fread($this->resource, $length);
    }

    public function getContents(): string
    {
        return stream_get_contents($this->resource);
    }

    public function getMetadata($key = null)
    {
        $meta = stream_get_meta_data($this->resource);
        return $key === null ? $meta : ($meta[$key] ?? null);
    }
}
