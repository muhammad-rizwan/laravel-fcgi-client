<?php

namespace Rizwan\LaravelFcgiClient\Responses;

interface ResponseInterface
{
    public function getHeaders(): array;

    public function getHeader(string $headerKey): array;

    public function getHeaderLine(string $headerKey): string;

    public function hasHeader(string $headerKey): bool;

    public function getBody(): string;

    public function body(): string;

    public function getOutput(): string;

    public function getError(): string;

    public function getDuration(): float;

    public function getConnectDuration(): float;

    public function getWriteDuration(): float;

    public function successful(): bool;

    public function status(): ?int;
    public function statusMessage(): ?string;


    public function ok(): bool;

    public function unauthorized(): bool;

    public function forbidden(): bool;

    public function notFound(): bool;

    public function serverError(): bool;

    public function json(?string $key = null, mixed $default = null): mixed;

    public function toArray(): array;

    public function header(string $key): string;

    public function throw(): static;

    public function throwIf(bool|callable $condition, ?callable $throwCallback = null): static;

    public function throwUnless(bool|callable $condition, ?callable $throwCallback = null): static;
}
