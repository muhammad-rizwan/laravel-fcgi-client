<?php

namespace Rizwan\LaravelFcgiClient;

use Rizwan\LaravelFcgiClient\Client\Client;
use Rizwan\LaravelFcgiClient\Connections\NetworkConnection;
use Rizwan\LaravelFcgiClient\Enums\RequestMethod;
use Rizwan\LaravelFcgiClient\Requests\RequestBuilder;
use Rizwan\LaravelFcgiClient\Responses\Response;
use Illuminate\Support\Facades\Concurrency;
use Closure;

class FCGIManager
{
    private ?NetworkConnection $connection = null;
    private string $scriptPath = '';
    private string $uri = '';
    private array $serverParams = [];
    private array $customVars = [];
    private int $connectTimeout = 5000;
    private int $readTimeout = 5000;

    public function __construct(
        private readonly Client $client = new Client(),
    ) {}

    /**
     * Send a request using the specified method
     */
    private function sendRequest(
        string $host,
        ?int $port,
        string $scriptPath,
        RequestMethod $method,
        array $options = [],
        bool $isJson = false
    ): Response {
        // Use default port if not provided
        $port = $port ?? 9000;

        // Set up connection
        $this->connection = new NetworkConnection(
            $host,
            $port,
            $options['connect_timeout'] ?? $this->connectTimeout,
            $options['read_timeout'] ?? $this->readTimeout
        );

        $this->scriptPath = $scriptPath;
        $this->uri = $options['uri'] ?? '';

        // Set custom params if provided
        $this->serverParams = $options['server_params'] ?? [];
        $this->customVars = $options['custom_vars'] ?? [];

        // Build request with appropriate content
        $builder = (new RequestBuilder())
            ->method($method)
            ->path($this->scriptPath);

        if ($method === RequestMethod::GET) {
            $builder->query($options['query'] ?? []);
        } elseif ($isJson) {
            $builder->json($options['data'] ?? []);
        } elseif (in_array($method, [RequestMethod::POST, RequestMethod::PUT, RequestMethod::PATCH])) {
            $builder->formData($options['data'] ?? []);
        }

        $request = $builder->build();

        // Apply common parameters
        if (!empty($this->uri)) {
            $request = $request->withServerParam('REQUEST_URI', $this->uri);
            $request = $request->withServerParam('SCRIPT_NAME', $this->uri);
        }

        foreach ($this->serverParams as $key => $value) {
            $request = $request->withServerParam($key, $value);
        }

        foreach ($this->customVars as $key => $value) {
            $request = $request->withCustomVar($key, $value);
        }

        return $this->client->sendRequest($this->connection, $request);
    }

    /**
     * Make a GET request
     */
    public function get(string $host, string $scriptPath, array $options = [], ?int $port = null): Response
    {
        return $this->sendRequest($host, $port, $scriptPath, RequestMethod::GET, $options);
    }

    /**
     * Make a POST request
     */
    public function post(string $host, string $scriptPath, array $options = [], ?int $port = null): Response
    {
        return $this->sendRequest($host, $port, $scriptPath, RequestMethod::POST, $options);
    }

    /**
     * Make a PUT request
     */
    public function put(string $host, string $scriptPath, array $options = [], ?int $port = null): Response
    {
        return $this->sendRequest($host, $port, $scriptPath, RequestMethod::PUT, $options);
    }

    /**
     * Make a PATCH request
     */
    public function patch(string $host, string $scriptPath, array $options = [], ?int $port = null): Response
    {
        return $this->sendRequest($host, $port, $scriptPath, RequestMethod::PATCH, $options);
    }

    /**
     * Make a DELETE request
     */
    public function delete(string $host, string $scriptPath, array $options = [], ?int $port = null): Response
    {
        return $this->sendRequest($host, $port, $scriptPath, RequestMethod::DELETE, $options);
    }

    /**
     * Make a JSON request (POST with JSON content)
     */
    public function json(string $host, string $scriptPath, array $options = [], ?int $port = null): Response
    {
        return $this->sendRequest($host, $port, $scriptPath, RequestMethod::POST, $options, true);
    }

    /**
     * Execute multiple requests in parallel using Laravel's Concurrency facade
     */
    public function pool(Closure $callback): array
    {
        // Execute the callback directly with $this as context
        $requests = $callback($this);

        // Wrap each request in a closure for the Concurrency facade
        $closures = [];
        foreach ($requests as $key => $request) {
            $closures[$key] = fn() => $request;
        }

        // Use Laravel's Concurrency facade
        return Concurrency::concurrent($closures);
    }
}
