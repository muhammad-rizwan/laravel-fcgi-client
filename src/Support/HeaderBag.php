<?php

namespace Rizwan\LaravelFcgiClient\Support;

/**
 * Manages HTTP-style headers and transforms them into
 * FastCGI-compatible server parameters.
 */
class HeaderBag
{
    /**
     * @var array<string, string>
     */
    private array $headers = [];

    /**
     * Set a single header.
     * This will overwrite any existing header with the same name.
     */
    public function set(string $key, string $value): void
    {
        $normalizedKey = $this->normalizeKey($key);
        $this->headers[$normalizedKey] = $value;
    }

    /**
     * Add multiple headers at once.
     * Values will be cast to string automatically.
     *
     * @param  array<string, string|int|float>  $headers
     */
    public function add(array $headers): void
    {
        foreach ($headers as $key => $value) {
            $this->set($key, (string) $value);
        }
    }

    /**
     * Get all normalized headers as an array.
     *
     * @return array<string, string>
     */
    public function all(): array
    {
        return $this->headers;
    }

    /**
     * Convert all headers into FastCGI-compatible server parameters.
     *
     * @return array<string, string>
     */
    public function toServerParams(): array
    {
        return $this->headers;
    }

    /**
     * Normalize a header key into FastCGI/PHP-style server key.
     *
     * Examples:
     * - "Authorization" becomes "HTTP_AUTHORIZATION"
     * - "Content-Type" stays "CONTENT_TYPE" for PHP compatibility
     */
    private function normalizeKey(string $key): string
    {
        $key = strtoupper(str_replace('-', '_', $key));

        return match ($key) {
            'CONTENT_TYPE', 'CONTENT_LENGTH' => $key,
            default => str_starts_with($key, 'HTTP_') ? $key : 'HTTP_'.$key,
        };
    }
}
