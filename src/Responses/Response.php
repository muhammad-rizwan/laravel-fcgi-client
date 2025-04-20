<?php

namespace Rizwan\LaravelFcgiClient\Responses;

/**
 * Represents a parsed FastCGI response.
 */
class Response implements ResponseInterface
{
    private const HEADER_PATTERN = '#^([^:]+):(.*)$#';

    private array $normalizedHeaders = [];
    private array $headers = [];
    private string $body = '';

    public function __construct(
        private readonly string $output,
        private readonly string $error,
        private readonly float $duration
    ) {
        $this->parseHeadersAndBody();
    }

    /**
     * Parse the headers and body from FastCGI STDOUT output.
     */
    private function parseHeadersAndBody(): void
    {
        $lines = explode(PHP_EOL, $this->output);
        $offset = 0;

        foreach ($lines as $i => $line) {
            if (!preg_match(self::HEADER_PATTERN, $line, $matches)) {
                break;
            }

            $offset = $i;
            $headerKey = trim($matches[1]);
            $headerValue = trim($matches[2]);

            $this->addRawHeader($headerKey, $headerValue);
            $this->addNormalizedHeader($headerKey, $headerValue);
        }

        $this->body = implode(PHP_EOL, array_slice($lines, $offset + 2));
    }

    private function addRawHeader(string $headerKey, string $headerValue): void
    {
        $this->headers[$headerKey][] = $headerValue;
    }

    private function addNormalizedHeader(string $headerKey, string $headerValue): void
    {
        $key = strtolower($headerKey);
        $this->normalizedHeaders[$key][] = $headerValue;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $headerKey): array
    {
        return $this->normalizedHeaders[strtolower($headerKey)] ?? [];
    }

    public function getHeaderLine(string $headerKey): string
    {
        return implode(', ', $this->getHeader($headerKey));
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getOutput(): string
    {
        return $this->output;
    }

    public function getError(): string
    {
        return $this->error;
    }

    public function getDuration(): float
    {
        return $this->duration;
    }

    public function getStatusCode(): ?int
    {
        $status = $this->getHeaderLine('Status');

        if (preg_match('/^\s*(\d{3})/', $status, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    public function successful(): bool
    {
        $code = $this->getStatusCode();
        return empty($this->error) && ($code === null || $code < 400);
    }

    public function isClientError(): bool
    {
        $code = $this->getStatusCode();
        return $code >= 400 && $code < 500;
    }

    public function isServerError(): bool
    {
        $code = $this->getStatusCode();
        return $code >= 500;
    }
}
