<?php

namespace Rizwan\LaravelFcgiClient\RequestContents;

class UrlEncodedContent implements ContentInterface
{
    public function __construct(
        private readonly array $data
    ) {}

    public function getContentType(): string
    {
        return 'application/x-www-form-urlencoded';
    }

    public function getContent(): string
    {
        return http_build_query($this->data);
    }
}
