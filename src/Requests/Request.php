<?php

namespace Rizwan\LaravelFcgiClient\Requests;

use Rizwan\LaravelFcgiClient\Enums\RequestMethod;
use Rizwan\LaravelFcgiClient\RequestContents\ContentInterface;

class Request
{
    private array $serverParams = [];
    private array $customVars = [];
    private array $responseCallbacks = [];
    private array $failureCallbacks = [];

    public function __construct(
        private readonly RequestMethod $method,
        private readonly string $scriptPath,
        private readonly ?ContentInterface $content = null,
    ) {
        $this->serverParams = [
            'GATEWAY_INTERFACE' => 'LaravelFcgiClient/1.0',
            'REQUEST_METHOD' => $method->value,
            'SCRIPT_FILENAME' => $scriptPath,
            'SERVER_SOFTWARE' => 'Laravel-LaravelFcgiClient',
            'REMOTE_ADDR' => '127.0.0.1',
            'REMOTE_PORT' => 9985,
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_PORT' => 80,
            'SERVER_NAME' => 'localhost',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'CONTENT_TYPE' => $content?->getContentType() ?? 'application/x-www-form-urlencoded',
            'CONTENT_LENGTH' => $content ? strlen($content->getContent()) : 0,
        ];
    }

    public function getRequestMethod(): RequestMethod
    {
        return $this->method;
    }

    public function getScriptFilename(): string
    {
        return $this->scriptPath;
    }

    public function getContent(): ?ContentInterface
    {
        return $this->content;
    }

    public function getContentLength(): int
    {
        return $this->content ? strlen($this->content->getContent()) : 0;
    }

    public function getParams(): array
    {
        return array_merge($this->customVars, $this->serverParams);
    }

    public function withServerParam(string $key, string $value): self
    {
        $clone = clone $this;
        $clone->serverParams[$key] = $value;
        return $clone;
    }

    public function withCustomVar(string $key, mixed $value): self
    {
        $clone = clone $this;
        $clone->customVars[$key] = $value;
        return $clone;
    }

    public function addResponseCallback(callable $callback): self
    {
        $this->responseCallbacks[] = $callback;
        return $this;
    }

    public function getResponseCallbacks(): array
    {
        return $this->responseCallbacks;
    }

    public function addFailureCallback(callable $callback): self
    {
        $this->failureCallbacks[] = $callback;
        return $this;
    }

    public function getFailureCallbacks(): array
    {
        return $this->failureCallbacks;
    }
}
