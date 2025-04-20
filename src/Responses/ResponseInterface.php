<?php

namespace Rizwan\LaravelFcgiClient\Responses;

/**
 * Represents a generic FastCGI response contract.
 */
interface ResponseInterface
{
    /**
     * Get all response headers (original case).
     *
     * @return array<string, string[]>
     */
    public function getHeaders(): array;

    /**
     * Get the values of a specific header (case-insensitive).
     *
     * @param string $headerKey
     * @return string[]
     */
    public function getHeader(string $headerKey): array;

    /**
     * Get a single concatenated header line (comma-separated).
     *
     * @param string $headerKey
     * @return string
     */
    public function getHeaderLine(string $headerKey): string;

    /**
     * Get the response body (after headers).
     *
     * @return string
     */
    public function getBody(): string;

    /**
     * Get the original full FastCGI output (headers + body).
     *
     * @return string
     */
    public function getOutput(): string;

    /**
     * Get any FastCGI error output (STDERR).
     *
     * @return string
     */
    public function getError(): string;

    /**
     * Get the total duration of the FastCGI request (in seconds).
     *
     * @return float
     */
    public function getDuration(): float;

    /**
     * Get the HTTP status code, if present.
     *
     * @return int|null
     */
    public function getStatusCode(): ?int;

    /**
     * Whether the response is successful.
     *
     * Success = No STDERR output and status code < 400.
     *
     * @return bool
     */
    public function successful(): bool;

    /**
     * Whether the response is a client error (4xx).
     *
     * @return bool
     */
    public function isClientError(): bool;

    /**
     * Whether the response is a server error (5xx).
     *
     * @return bool
     */
    public function isServerError(): bool;
}
