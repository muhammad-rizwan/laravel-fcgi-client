<?php

namespace Rizwan\LaravelFcgiClient\Support;

use Rizwan\LaravelFcgiClient\Client\Client;
use Rizwan\LaravelFcgiClient\Connections\NetworkConnection;
use Rizwan\LaravelFcgiClient\Connections\UnixDomainSocketConnection;
use Rizwan\LaravelFcgiClient\Enums\RequestMethod;
use Rizwan\LaravelFcgiClient\Requests\RequestBuilder;
use Rizwan\LaravelFcgiClient\Responses\Response;

class RequestPool
{
    private array $requests = [];
    private array $connections = [];
    private int $connectTimeout = 5000;
    private int $readTimeout = 5000;
    private array $serverParams = [];
    private array $customVars = [];

    public function __construct(
        private readonly Client $client
    ) {}

    /**
     * Connect to a FastCGI server via network socket
     */
    public function connect(string $host, int $port = 9000): self
    {
        $connection = new NetworkConnection(
            $host,
            $port,
            $this->connectTimeout,
            $this->readTimeout
        );

        $id = count($this->connections);
        $this->connections[$id] = $connection;

        return $this;
    }

    /**
     * Connect to a FastCGI server via unix domain socket
     */
    public function connectUnix(string $socketPath): self
    {
        $connection = new UnixDomainSocketConnection(
            $socketPath,
            $this->connectTimeout,
            $this->readTimeout
        );

        $id = count($this->connections);
        $this->connections[$id] = $connection;

        return $this;
    }

    /**
     * Set timeout for the current connection
     */
    public function timeout(int $milliseconds): self
    {
        $this->connectTimeout = $milliseconds;
        $this->readTimeout = $milliseconds;

        return $this;
    }

    /**
     * Add a custom server parameter
     */
    public function withServerParam(string $name, string $value): self
    {
        $this->serverParams[$name] = $value;
        return $this;
    }

    /**
     * Add a custom variable
     */
    public function withCustomVar(string $name, mixed $value): self
    {
        $this->customVars[$name] = $value;
        return $this;
    }

    /**
     * Make a GET request
     */
    public function get(string $path, array $query = []): int
    {
        $connectionId = count($this->connections) - 1;
        if ($connectionId < 0) {
            throw new \RuntimeException('No connection specified. Call connect() or connectUnix() first.');
        }

        $request = (new RequestBuilder())
            ->method(RequestMethod::GET)
            ->path($path)
            ->query($query)
            ->build();

        // Add custom server params and variables
        foreach ($this->serverParams as $key => $value) {
            $request = $request->withServerParam($key, $value);
        }

        foreach ($this->customVars as $key => $value) {
            $request = $request->withCustomVar($key, $value);
        }

        $id = count($this->requests);
        $this->requests[$id] = [
            'connection' => $connectionId,
            'request' => $request
        ];

        // Return the request ID
        return $id;
    }

    /**
     * Make a POST request
     */
    public function post(string $path, array $data = []): int
    {
        $connectionId = count($this->connections) - 1;
        if ($connectionId < 0) {
            throw new \RuntimeException('No connection specified. Call connect() or connectUnix() first.');
        }

        $request = (new RequestBuilder())
            ->method(RequestMethod::POST)
            ->path($path)
            ->formData($data)
            ->build();

        // If a URI is set, add it as a server parameter
        if (!empty($this->uri)) {
            $request = $request->withServerParam('REQUEST_URI', $this->uri);
        }

        // Add custom server params and variables
        foreach ($this->serverParams as $key => $value) {
            $request = $request->withServerParam($key, $value);
        }

        foreach ($this->customVars as $key => $value) {
            $request = $request->withCustomVar($key, $value);
        }

        $id = count($this->requests);
        $this->requests[$id] = [
            'connection' => $connectionId,
            'request' => $request
        ];

        // Return the request ID
        return $id;
    }

    /**
     * Make a PUT request
     */
    public function put(string $path, array $data = []): int
    {
        $connectionId = count($this->connections) - 1;
        if ($connectionId < 0) {
            throw new \RuntimeException('No connection specified. Call connect() or connectUnix() first.');
        }

        $request = (new RequestBuilder())
            ->method(RequestMethod::PUT)
            ->path($path)
            ->formData($data)
            ->build();

        // If a URI is set, add it as a server parameter
        if (!empty($this->uri)) {
            $request = $request->withServerParam('REQUEST_URI', $this->uri);
        }

        // Add custom server params and variables
        foreach ($this->serverParams as $key => $value) {
            $request = $request->withServerParam($key, $value);
        }

        foreach ($this->customVars as $key => $value) {
            $request = $request->withCustomVar($key, $value);
        }

        $id = count($this->requests);
        $this->requests[$id] = [
            'connection' => $connectionId,
            'request' => $request
        ];

        // Return the request ID
        return $id;
    }

    /**
     * Make a PATCH request
     */
    public function patch(string $path, array $data = []): int
    {
        $connectionId = count($this->connections) - 1;
        if ($connectionId < 0) {
            throw new \RuntimeException('No connection specified. Call connect() or connectUnix() first.');
        }

        $request = (new RequestBuilder())
            ->method(RequestMethod::PATCH)
            ->path($path)
            ->formData($data)
            ->build();

        // If a URI is set, add it as a server parameter
        if (!empty($this->uri)) {
            $request = $request->withServerParam('REQUEST_URI', $this->uri);
        }

        // Add custom server params and variables
        foreach ($this->serverParams as $key => $value) {
            $request = $request->withServerParam($key, $value);
        }

        foreach ($this->customVars as $key => $value) {
            $request = $request->withCustomVar($key, $value);
        }

        $id = count($this->requests);
        $this->requests[$id] = [
            'connection' => $connectionId,
            'request' => $request
        ];

        // Return the request ID
        return $id;
    }

    /**
     * Make a DELETE request
     */
    public function delete(string $path): int
    {
        $connectionId = count($this->connections) - 1;
        if ($connectionId < 0) {
            throw new \RuntimeException('No connection specified. Call connect() or connectUnix() first.');
        }

        $request = (new RequestBuilder())
            ->method(RequestMethod::DELETE)
            ->path($path)
            ->build();

        // If a URI is set, add it as a server parameter
        if (!empty($this->uri)) {
            $request = $request->withServerParam('REQUEST_URI', $this->uri);
        }
        // Add custom server params and variables
        foreach ($this->serverParams as $key => $value) {
            $request = $request->withServerParam($key, $value);
        }

        foreach ($this->customVars as $key => $value) {
            $request = $request->withCustomVar($key, $value);
        }

        $id = count($this->requests);
        $this->requests[$id] = [
            'connection' => $connectionId,
            'request' => $request
        ];

        // Return the request ID
        return $id;
    }

    /**
     * Make a JSON request
     */
    public function json(string $path, array $data): int
    {
        $connectionId = count($this->connections) - 1;
        if ($connectionId < 0) {
            throw new \RuntimeException('No connection specified. Call connect() or connectUnix() first.');
        }

        $request = (new RequestBuilder())
            ->method(RequestMethod::POST)
            ->path($path)
            ->json($data)
            ->build();

        // If a URI is set, add it as a server parameter
        if (!empty($this->uri)) {
            $request = $request->withServerParam('REQUEST_URI', $this->uri);
        }
        // Add custom server params and variables
        foreach ($this->serverParams as $key => $value) {
            $request = $request->withServerParam($key, $value);
        }

        foreach ($this->customVars as $key => $value) {
            $request = $request->withCustomVar($key, $value);
        }

        $id = count($this->requests);
        $this->requests[$id] = [
            'connection' => $connectionId,
            'request' => $request
        ];

        // Return the request ID
        return $id;
    }

    /**
     * Execute all requests in the pool
     */
    public function execute(): array
    {
        $responses = [];

        foreach ($this->requests as $id => $requestData) {
            $connection = $this->connections[$requestData['connection']];
            $request = $requestData['request'];

            // In a more sophisticated implementation, we'd handle these asynchronously
            // For now, we'll execute them sequentially
            $responses[$id] = $this->client->sendRequest($connection, $request);
        }

        return $responses;
    }
}
