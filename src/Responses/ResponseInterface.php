<?php

namespace Rizwan\LaravelFcgiClient\Responses;

interface ResponseInterface
{
    public function getHeaders(): array;

    public function getHeader(string $headerKey): array;

    public function getHeaderLine(string $headerKey): string;

    public function getBody(): string;

    public function getOutput(): string;

    public function getError(): string;

    public function getDuration(): float;

    public function successful(): bool;
}
