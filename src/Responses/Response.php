<?php

namespace Rizwan\LaravelFcgiClient\Responses;

class Response implements ResponseInterface
{
    private const HEADER_PATTERN = '#^([^:]+):(.*)$#';

    private array $normalizedHeaders = [];
    private array $headers = [];
    private string $body = '';
    private ?int $statusCode = null;

    public function __construct(
        private readonly string $output,
        private readonly string $error,
        private readonly float $duration
    ) {
        $this->parseHeadersAndBody();
    }

    private function parseHeadersAndBody(): void
    {
        $lines = explode(PHP_EOL, $this->output);
        $offset = 0;

        foreach ($lines as $i => $line) {
            $matches = [];
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
        $this->statusCode = $this->extractStatusCode();
    }

    private function addRawHeader(string $headerKey, string $value): void
    {
        $this->headers[$headerKey][] = $value;
    }

    private function addNormalizedHeader(string $headerKey, string $value): void
    {
        $key = strtolower($headerKey);
        $this->normalizedHeaders[$key][] = $value;
    }

    private function extractStatusCode(): ?int
    {
        $line = $this->getHeaderLine('Status');
        return $line ? (int) substr($line, 0, 3) : null;
    }

    public function getHeader(string $headerKey): array
    {
        return $this->normalizedHeaders[strtolower($headerKey)] ?? [];
    }

    public function getHeaderLine(string $headerKey): string
    {
        return implode(', ', $this->getHeader($headerKey));
    }

    public function getHeaders(): array
    {
        return $this->headers;
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

    public function successful(): bool
    {
        return empty($this->error) && $this->status() < 400;
    }

    public function status(): ?int
    {
        return $this->statusCode;
    }

    public function ok(): bool
    {
        return $this->status() === 200;
    }

    public function unauthorized(): bool
    {
        return $this->status() === 401;
    }

    public function forbidden(): bool
    {
        return $this->status() === 403;
    }

    public function notFound(): bool
    {
        return $this->status() === 404;
    }

    public function serverError(): bool
    {
        return $this->status() >= 500;
    }
}
