<?php

namespace Rizwan\LaravelFcgiClient\Responses;

class Response implements ResponseInterface
{
    private const HEADER_PATTERN = '#^([^:]+):(.*)$#';

    private array $normalizedHeaders = [];
    private array $headers = [];
    private string $body = '';
    private ?int $statusCode = null;
    private ?string $statusMessage = null;
    private int $attempts = 0;

    public function __construct(
        private readonly string $output,
        private readonly string $error,
        private readonly float $duration,
        /** @var float connection time in ms */
        private readonly float $connectDuration = 0.0,
        /** @var float write time in ms */
        private readonly float $writeDuration = 0.0,
        int $attempts = 0
    ) {
        $this->parseHeadersAndBody();
    }

    private function parseHeadersAndBody(): void
    {
        $lines  = explode(PHP_EOL, $this->output);
        $offset = 0;

        foreach ($lines as $i => $line) {
            if (! preg_match(self::HEADER_PATTERN, $line, $matches)) {
                break;
            }

            $offset       = $i;
            $headerKey    = trim($matches[1]);
            $headerValue  = trim($matches[2]);

            $this->addRawHeader($headerKey, $headerValue);
            $this->addNormalizedHeader($headerKey, $headerValue);
        }

        // Skip the blank line after headers
        $this->body       = implode(PHP_EOL, array_slice($lines, $offset + 2));
        $this->statusCode = $this->extractStatusCode();
        $this->statusMessage = $this->extractStatusMessage();
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
        return $line ? (int) substr($line, 0, 3) : 200;
    }

    private function extractStatusMessage(): string
    {
        $line = $this->getHeaderLine('Status');

        // FCGI returns empty line for 200 OK response
        if ($line === '') {
            return 'OK';
        }

        $parts = explode(' ', trim($line), 2);

        return $parts[1] ?? 'OK';
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

    public function body(): string
    {
        return $this->getBody();
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

    public function getConnectDuration(): float
    {
        return $this->connectDuration;
    }

    public function getWriteDuration(): float
    {
        return $this->writeDuration;
    }

    public function setAttempts(int $attempts): static
    {
        $this->attempts = $attempts;

        return $this;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }
    public function successful(): bool
    {
        return empty($this->error) && $this->getStatusCode() < 400;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function getStatusMessage(): ?string
    {
        return $this->statusMessage;
    }

    public function ok(): bool
    {
        return $this->getStatusCode() === 200;
    }

    public function unauthorized(): bool
    {
        return $this->getStatusCode() === 401;
    }

    public function forbidden(): bool
    {
        return $this->getStatusCode() === 403;
    }

    public function notFound(): bool
    {
        return $this->getStatusCode() === 404;
    }

    public function serverError(): bool
    {
        return $this->getStatusCode() >= 500;
    }

    public function json(?string $key = null, mixed $default = null): mixed
    {
        $decoded = json_decode($this->body, true);

        if ($key === null) {
            return $decoded;
        }

        return data_get($decoded, $key, $default);
    }

    public function toArray(): array
    {
        return [
            'status'      => $this->getStatusCode(),
            'status_message' => $this->getStatusMessage(),
            'headers'     => $this->getHeaders(),
            'body'        => $this->json() ?? $this->getBody(),
            'error'       => $this->getError(),
            'duration'        => round($this->duration * 1000, 2),
            'connect_duration' => round($this->connectDuration, 2),
            'write_duration'   => round($this->writeDuration, 2),
            'attempts'       => $this->attempts,
        ];
    }

    public function header(string $key): string
    {
        return $this->getHeaderLine($key);
    }

    public function hasHeader(string $headerKey): bool
    {
        return isset($this->normalizedHeaders[strtolower($headerKey)]);
    }

    public function throw(): static
    {
        if (! $this->successful()) {
            throw new \RuntimeException(
                "FastCGI request failed with status {$this->getStatusCode()}: {$this->getError()}"
            );
        }

        return $this;
    }

    public function throwIf(bool|callable $condition, ?callable $throwCallback = null): static
    {
        $shouldThrow = is_callable($condition) ? $condition($this) : $condition;

        if ($shouldThrow) {
            throw ($throwCallback
                ? $throwCallback($this)
                : new \RuntimeException('FastCGI response condition failed.'));
        }

        return $this;
    }

    public function throwUnless(bool|callable $condition, ?callable $throwCallback = null): static
    {
        return $this->throwIf(! $condition, $throwCallback);
    }

}
