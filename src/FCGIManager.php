<?php

namespace Rizwan\LaravelFcgiClient;

use Closure;
use Illuminate\Support\Facades\Concurrency;
use Rizwan\LaravelFcgiClient\Client\Client;
use Rizwan\LaravelFcgiClient\Connections\NetworkConnection;
use Rizwan\LaravelFcgiClient\Enums\RequestMethod;
use Rizwan\LaravelFcgiClient\Requests\RequestBuilder;
use Rizwan\LaravelFcgiClient\Responses\Response;
use Rizwan\LaravelFcgiClient\Support\HeaderBag;

class FCGIManager
{
    private ?NetworkConnection $connection = null;
    private string $scriptPath = '';
    private string $uri = '';
    private array $serverParams = [];
    private array $customVars = [];
    private array $headers = [];
    private int $connectTimeout = 5000;
    private int $readTimeout = 5000;

    public function __construct(
        private readonly Client $client = new Client(),
    ) {}

    /**
     * Dispatch a FastCGI request.
     */
    private function sendRequest(
        string $host,
        ?int $port,
        string $scriptPath,
        RequestMethod $method,
        array $options = [],
        bool $isJson = false,
        bool $asForm = false
    ): Response {
        $port ??= 9000;

        $this->connection = new NetworkConnection(
            $host,
            $port,
            $options['connect_timeout'] ?? $this->connectTimeout,
            $options['read_timeout'] ?? $this->readTimeout
        );

        $this->scriptPath = $scriptPath;
        $this->uri = $options['uri'] ?? '';
        $this->serverParams = $options['server_params'] ?? [];
        $this->customVars = $options['custom_vars'] ?? [];
        $this->headers = $options['headers'] ?? [];

        $builder = (new RequestBuilder())
            ->method($method)
            ->path($this->scriptPath)
            ->withHeaders($this->headers);

        if ($method === RequestMethod::GET) {
            $builder->query($options['query'] ?? []);
        } elseif ($isJson) {
            $builder->acceptJson()->json($options['data'] ?? []);
        } elseif ($asForm) {
            $builder->asForm()->formData($options['data'] ?? []);
        }

        $request = $builder->build();

        if (!empty($this->uri)) {
            $request = $request->withServerParam('REQUEST_URI', $this->uri)
                ->withServerParam('SCRIPT_NAME', $this->uri);
        }

        foreach ($this->serverParams as $key => $value) {
            $request = $request->withServerParam($key, $value);
        }

        foreach ($this->customVars as $key => $value) {
            $request = $request->withCustomVar($key, $value);
        }

        return $this->client->sendRequest($this->connection, $request);
    }

    public function get(string $host, string $scriptPath, array $options = [], ?int $port = null): Response
    {
        return $this->sendRequest($host, $port, $scriptPath, RequestMethod::GET, $options);
    }

    public function post(string $host, string $scriptPath, array $options = [], ?int $port = null): Response
    {
        return $this->sendRequest($host, $port, $scriptPath, RequestMethod::POST, $options);
    }

    public function put(string $host, string $scriptPath, array $options = [], ?int $port = null): Response
    {
        return $this->sendRequest($host, $port, $scriptPath, RequestMethod::PUT, $options);
    }

    public function patch(string $host, string $scriptPath, array $options = [], ?int $port = null): Response
    {
        return $this->sendRequest($host, $port, $scriptPath, RequestMethod::PATCH, $options);
    }

    public function delete(string $host, string $scriptPath, array $options = [], ?int $port = null): Response
    {
        return $this->sendRequest($host, $port, $scriptPath, RequestMethod::DELETE, $options);
    }

    /**
     * Send a JSON request (Content-Type: application/json)
     */
    public function json(string $host, string $scriptPath, array $options = [], ?int $port = null): Response
    {
        return $this->sendRequest($host, $port, $scriptPath, RequestMethod::POST, $options, isJson: true);
    }

    /**
     * Send a form-encoded request (Content-Type: application/x-www-form-urlencoded)
     */
    public function form(string $host, string $scriptPath, array $options = [], ?int $port = null): Response
    {
        return $this->sendRequest($host, $port, $scriptPath, RequestMethod::POST, $options, asForm: true);
    }

    /**
     * Execute multiple FastCGI requests concurrently.
     */
    public function pool(Closure $callback): array
    {
        $requests = $callback($this);

        $closures = [];
        foreach ($requests as $key => $request) {
            $closures[$key] = fn() => $request;
        }

        return Concurrency::concurrent($closures);
    }
}
