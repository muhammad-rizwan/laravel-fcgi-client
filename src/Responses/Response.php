<?php

namespace Rizwan\LaravelFcgiClient\Responses;

class Response implements ResponseInterface
{
    private const HEADER_PATTERN = '#^([^\:]+):(.*)$#';

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
    }

    private function addRawHeader(string $headerKey, string $headerValue): void
    {
        if (!isset($this->headers[$headerKey])) {
            $this->headers[$headerKey] = [$headerValue];
            return;
        }

        $this->headers[$headerKey][] = $headerValue;
    }

    private function addNormalizedHeader(string $headerKey, string $headerValue): void
    {
        $key = strtolower($headerKey);

        if (!isset($this->normalizedHeaders[$key])) {
            $this->normalizedHeaders[$key] = [$headerValue];
            return;
        }

        $this->normalizedHeaders[$key][] = $headerValue;
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
        return empty($this->error) && !$this->isErrorStatus();
    }

    private function isErrorStatus(): bool
    {
        $status = $this->getHeaderLine('Status');

        if (empty($status)) {
            return false;
        }

        $statusCode = (int) substr($status, 0, 3);
        return $statusCode >= 400;
    }
}
