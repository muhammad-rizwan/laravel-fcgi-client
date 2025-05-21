<?php

namespace Rizwan\LaravelFcgiClient\RequestContents;

class RawContent implements ContentInterface
{
    public function __construct(
        private readonly string $content,
        private readonly string $type = 'text/plain'
    ) {}

    public function getContent(): string
    {
        return $this->content;
    }

    public function getContentType(): string
    {
        return $this->type;
    }
}
