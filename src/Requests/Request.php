<?php

namespace Rizwan\LaravelFcgiClient\Requests;

use Rizwan\LaravelFcgiClient\Enums\RequestMethod;
use Rizwan\LaravelFcgiClient\RequestContents\ContentInterface;

class Request
{
    private array $serverParams = [];

    private array $customVars = [];

    /**
     * @var string
     */
    public string $requestUri;

    /**
     * @var string
     */
    public string $host;

    public function __construct(
        private readonly RequestMethod $method,
        private readonly string $scriptPath,
        private readonly ?ContentInterface $content = null,
        private readonly array $defaults = []
    ) {
        $this->serverParams = [
            'REQUEST_METHOD' => $this->method->value,
            'SCRIPT_FILENAME' => $this->scriptPath,
            'SERVER_PROTOCOL' => $this->defaults['SERVER_PROTOCOL'] ?? 'HTTP/1.1',
            'CONTENT_TYPE' => $this->content?->getContentType() ?? 'application/x-www-form-urlencoded',
            'CONTENT_LENGTH' => $this->content ? strlen($this->content->getContent()) : 0,
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
        $this->serverParams[$key] = $value;
        return $this;
    }

    public function withCustomVar(string $key, mixed $value): self
    {
        $this->customVars[$key] = $value;
        return $this;
    }
}
