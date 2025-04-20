<?php

namespace Rizwan\LaravelFcgiClient;

use Closure;
use Illuminate\Support\Facades\Concurrency;
use Rizwan\LaravelFcgiClient\Client\Client;
use Rizwan\LaravelFcgiClient\Connections\NetworkConnection;
use Rizwan\LaravelFcgiClient\Enums\RequestMethod;
use Rizwan\LaravelFcgiClient\Requests\RequestBuilder;
use Rizwan\LaravelFcgiClient\Responses\Response;
use Throwable;

class FCGIManager
{
    private ?NetworkConnection $connection = null;

    private string $scriptPath = '';

    private string $uri = '';

    private array $headers = [];

    private array $serverParams = [];

    private array $customVars = [];

    private array $query = [];

    private array $payload = [];

    private ?string $rawBody = null;

    private string $rawBodyType = 'text/plain';

    private int $connectTimeout = 5000;

    private int $readTimeout = 5000;

    private int $maxRetries = 0;

    private int $retryDelayMs = 0;

    private ?Closure $retryWhen = null;

    public function __construct(
        private readonly Client $client = new Client,
    ) {}

    public function withHeaders(array $headers): self
    {
        $this->headers = $headers;

        return $this;
    }

    public function withQuery(array $query): self
    {
        $this->query = $query;

        return $this;
    }

    public function withPayload(array $data): self
    {
        $this->payload = $data;

        return $this;
    }

    public function withBody(string $body, string $type = 'text/plain'): self
    {
        $this->rawBody = $body;
        $this->rawBodyType = $type;

        return $this;
    }

    public function withUri(string $uri): self
    {
        $this->uri = $uri;

        return $this;
    }

    public function withServerParams(array $params): self
    {
        $this->serverParams = $params;

        return $this;
    }

    public function withCustomVars(array $vars): self
    {
        $this->customVars = $vars;

        return $this;
    }

    public function timeout(int $ms): self
    {
        $this->readTimeout = $ms;

        return $this;
    }

    public function connectTimeout(int $ms): self
    {
        $this->connectTimeout = $ms;

        return $this;
    }

    public function retry(int $times, int $sleepMs = 0, ?callable $when = null): self
    {
        $this->maxRetries = $times;
        $this->retryDelayMs = $sleepMs;
        $this->retryWhen = $when instanceof Closure ? $when : ($when ? Closure::fromCallable($when) : null);

        return $this;
    }

    private function sendRequest(
        string $host,
        ?int $port,
        string $scriptPath,
        RequestMethod $method,
        bool $isJson = false,
        bool $asForm = false
    ): Response {
        $port ??= 9000;

        $this->connection = new NetworkConnection(
            $host,
            $port,
            $this->connectTimeout,
            $this->readTimeout
        );

        $this->scriptPath = $scriptPath;

        $builder = (new RequestBuilder)
            ->method($method)
            ->path($this->scriptPath)
            ->withHeaders($this->headers);

        if ($method === RequestMethod::GET) {
            $builder->query($this->query);
        } elseif ($isJson) {
            $builder->acceptJson()->json($this->payload);
        } elseif ($asForm) {
            $builder->asForm()->formData($this->payload);
        } elseif (! empty($this->rawBody)) {
            $builder->withBody($this->rawBody, $this->rawBodyType);
        }

        $request = $builder->build();

        if (! empty($this->uri)) {
            $request = $request->withServerParam('REQUEST_URI', $this->uri)
                ->withServerParam('SCRIPT_NAME', $this->uri);
        }

        foreach ($this->serverParams as $key => $value) {
            $request = $request->withServerParam($key, $value);
        }

        foreach ($this->customVars as $key => $value) {
            $request = $request->withCustomVar($key, $value);
        }

        $attempts = 0;

        do {
            try {
                return $this->client->sendRequest($this->connection, $request);
            } catch (Throwable $e) {
                $shouldRetry = $this->retryWhen ? ($this->retryWhen)($e, $request) : true;

                if (++$attempts > $this->maxRetries || ! $shouldRetry) {
                    throw $e;
                }

                if ($this->retryDelayMs > 0) {
                    usleep($this->retryDelayMs * 1000);
                }
            }
        } while (true);
    }

    public function get(string $host, string $scriptPath, ?int $port = null): Response
    {
        return $this->sendRequest($host, $port, $scriptPath, RequestMethod::GET);
    }

    public function post(string $host, string $scriptPath, ?int $port = null): Response
    {
        return $this->sendRequest($host, $port, $scriptPath, RequestMethod::POST);
    }

    public function put(string $host, string $scriptPath, ?int $port = null): Response
    {
        return $this->sendRequest($host, $port, $scriptPath, RequestMethod::PUT);
    }

    public function patch(string $host, string $scriptPath, ?int $port = null): Response
    {
        return $this->sendRequest($host, $port, $scriptPath, RequestMethod::PATCH);
    }

    public function delete(string $host, string $scriptPath, ?int $port = null): Response
    {
        return $this->sendRequest($host, $port, $scriptPath, RequestMethod::DELETE);
    }

    public function json(string $host, string $scriptPath, ?int $port = null): Response
    {
        return $this->sendRequest($host, $port, $scriptPath, RequestMethod::POST, isJson: true);
    }

    public function form(string $host, string $scriptPath, ?int $port = null): Response
    {
        return $this->sendRequest($host, $port, $scriptPath, RequestMethod::POST, asForm: true);
    }

    public function pool(Closure $callback): array
    {
        $requests = $callback($this);

        $closures = [];
        foreach ($requests as $key => $request) {
            $closures[$key] = fn () => $request;
        }

        return Concurrency::run($closures);
    }
}
