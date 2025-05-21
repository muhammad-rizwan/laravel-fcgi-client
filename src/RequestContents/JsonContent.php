<?php

namespace Rizwan\LaravelFcgiClient\RequestContents;

use RuntimeException;

class JsonContent implements ContentInterface
{
    public function __construct(
        private readonly mixed $data,
        private readonly int $options = 0,
        private readonly int $depth = 512
    ) {}

    public function getContentType(): string
    {
        return 'application/json';
    }

    public function getContent(): string
    {
        $json = json_encode($this->data, $this->options, $this->depth);

        if ($json === false) {
            throw new RuntimeException('Could not encode data to JSON');
        }

        return $json;
    }
}
