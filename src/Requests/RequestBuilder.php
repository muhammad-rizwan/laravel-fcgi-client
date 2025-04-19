<?php

namespace Rizwan\LaravelFcgiClient\Requests;

use Rizwan\LaravelFcgiClient\Enums\RequestMethod;
use Rizwan\LaravelFcgiClient\RequestContents\ContentInterface;
use Rizwan\LaravelFcgiClient\RequestContents\JsonContent;
use Rizwan\LaravelFcgiClient\RequestContents\UrlEncodedContent;

class RequestBuilder
{
    private RequestMethod $method = RequestMethod::GET;
    private string $scriptPath = '';
    private ?ContentInterface $content = null;
    private array $serverParams = [];
    private array $customVars = [];

    public function method(RequestMethod $method): self
    {
        $this->method = $method;
        return $this;
    }

    public function path(string $path): self
    {
        $this->scriptPath = $path;
        return $this;
    }

    public function content(?ContentInterface $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function query(array $params): self
    {
        $this->serverParams['QUERY_STRING'] = http_build_query($params);
        return $this;
    }

    public function formData(array $data): self
    {
        $this->content = new UrlEncodedContent($data);
        return $this;
    }

    public function json(array $data): self
    {
        $this->content = new JsonContent($data);
        return $this;
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

    public function build(): Request
    {
        $request = new Request($this->method, $this->scriptPath, $this->content);

        foreach ($this->serverParams as $key => $value) {
            $request = $request->withServerParam($key, $value);
        }

        foreach ($this->customVars as $key => $value) {
            $request = $request->withCustomVar($key, $value);
        }

        return $request;
    }
}
