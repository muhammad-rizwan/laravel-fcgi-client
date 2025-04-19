<?php

namespace Rizwan\LaravelFcgiClient;

use Rizwan\LaravelFcgiClient\Client\Client;
use Rizwan\LaravelFcgiClient\Connections\ConnectionInterface;
use Rizwan\LaravelFcgiClient\Connections\NetworkConnection;
use Rizwan\LaravelFcgiClient\Connections\UnixDomainSocketConnection;
use Rizwan\LaravelFcgiClient\Enums\RequestMethod;
use Rizwan\LaravelFcgiClient\Requests\RequestBuilder;
use Rizwan\LaravelFcgiClient\RequestContents\JsonContent;
use Rizwan\LaravelFcgiClient\RequestContents\UrlEncodedContent;
use Rizwan\LaravelFcgiClient\Responses\Response;
use Rizwan\LaravelFcgiClient\Support\RequestPool;
use Closure;

class FCGIManager
{
    private ?ConnectionInterface $connection = null;
    private int $connectTimeout = 5000;
    private int $readTimeout = 5000;
    private array $serverParams = [];
    private array $customVars = [];
    private string $scriptPath = '';
    private string $uri = '';

    public function __construct(
        private readonly Client $client = new Client(),
    ) {}

    /**
     * Connect to a FastCGI server via network socket
     */
    public function connect(string $host, int $port = 9000): self
    {
        $this->connection = new NetworkConnection(
            $host,
            $port,
            $this->connectTimeout,
            $this->readTimeout
        );
        return $this;
    }

    /**
     * Connect to a FastCGI server via unix domain socket
     */
    public function connectUnix(string $socketPath): self
    {
        $this->connection = new UnixDomainSocketConnection(
            $socketPath,
            $this->connectTimeout,
            $this->readTimeout
        );
        return $this;
    }

    /**
     * Set the actual script file path on the server
     */
    public function scriptPath(string $path): self
    {
        $this->scriptPath = $path;
        return $this;
    }

    /**
     * Set the virtual URI path
     */
    public function uri(string $uri): self
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * Set both connect and read timeouts at once
     */
    public function timeout(int $milliseconds): self
    {
        $this->connectTimeout = $milliseconds;
        $this->readTimeout = $milliseconds;

        // Update current connection if exists
        if ($this->connection !== null) {
            $this->connection = $this->refreshConnection();
        }

        return $this;
    }

    /**
     * Set the connection timeout
     */
    public function connectTimeout(int $milliseconds): self
    {
        $this->connectTimeout = $milliseconds;

        // Update current connection if exists
        if ($this->connection !== null) {
            $this->connection = $this->refreshConnection();
        }

        return $this;
    }

    /**
     * Set the read/write timeout
     */
    public function readTimeout(int $milliseconds): self
    {
        $this->readTimeout = $milliseconds;

        // Update current connection if exists
        if ($this->connection !== null) {
            $this->connection = $this->refreshConnection();
        }

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
    public function get(array $query = []): Response
    {
        if (empty($this->scriptPath)) {
            throw new \RuntimeException('Script path must be set using scriptPath() method');
        }

        $request = (new RequestBuilder())
            ->method(RequestMethod::GET)
            ->path($this->scriptPath)
            ->query($query)
            ->build();

        // If a URI is set, add it as a server parameter
        if (!empty($this->uri)) {
            $request = $request->withServerParam('REQUEST_URI', $this->uri);
            $request = $request->withServerParam('SCRIPT_NAME', $this->uri);
        }

        // Add custom server params and variables
        foreach ($this->serverParams as $key => $value) {
            $request = $request->withServerParam($key, $value);
        }

        foreach ($this->customVars as $key => $value) {
            $request = $request->withCustomVar($key, $value);
        }

        return $this->client->sendRequest($this->getConnection(), $request);
    }

    /**
     * Make a POST request
     */
    public function post(array $data = []): Response
    {
        if (empty($this->scriptPath)) {
            throw new \RuntimeException('Script path must be set using scriptPath() method');
        }

        $request = (new RequestBuilder())
            ->method(RequestMethod::POST)
            ->path($this->scriptPath)
            ->formData($data)
            ->build();

        // If a URI is set, add it as a server parameter
        if (!empty($this->uri)) {
            $request = $request->withServerParam('REQUEST_URI', $this->uri);
            $request = $request->withServerParam('SCRIPT_NAME', $this->uri);
        }

        // Add custom server params and variables
        foreach ($this->serverParams as $key => $value) {
            $request = $request->withServerParam($key, $value);
        }

        foreach ($this->customVars as $key => $value) {
            $request = $request->withCustomVar($key, $value);
        }

        return $this->client->sendRequest($this->getConnection(), $request);
    }

    /**
     * Make a PUT request
     */
    public function put(array $data = []): Response
    {
        if (empty($this->scriptPath)) {
            throw new \RuntimeException('Script path must be set using scriptPath() method');
        }

        $request = (new RequestBuilder())
            ->method(RequestMethod::PUT)
            ->path($this->scriptPath)
            ->formData($data)
            ->build();

        // If a URI is set, add it as a server parameter
        if (!empty($this->uri)) {
            $request = $request->withServerParam('REQUEST_URI', $this->uri);
            $request = $request->withServerParam('SCRIPT_NAME', $this->uri);
        }

        // Add custom server params and variables
        foreach ($this->serverParams as $key => $value) {
            $request = $request->withServerParam($key, $value);
        }

        foreach ($this->customVars as $key => $value) {
            $request = $request->withCustomVar($key, $value);
        }

        return $this->client->sendRequest($this->getConnection(), $request);
    }

    /**
     * Make a PATCH request
     */
    public function patch(array $data = []): Response
    {
        if (empty($this->scriptPath)) {
            throw new \RuntimeException('Script path must be set using scriptPath() method');
        }

        $request = (new RequestBuilder())
            ->method(RequestMethod::PATCH)
            ->path($this->scriptPath)
            ->formData($data)
            ->build();

        // If a URI is set, add it as a server parameter
        if (!empty($this->uri)) {
            $request = $request->withServerParam('REQUEST_URI', $this->uri);
            $request = $request->withServerParam('SCRIPT_NAME', $this->uri);
        }

        // Add custom server params and variables
        foreach ($this->serverParams as $key => $value) {
            $request = $request->withServerParam($key, $value);
        }

        foreach ($this->customVars as $key => $value) {
            $request = $request->withCustomVar($key, $value);
        }

        return $this->client->sendRequest($this->getConnection(), $request);
    }

    /**
     * Make a DELETE request
     */
    public function delete(): Response
    {
        if (empty($this->scriptPath)) {
            throw new \RuntimeException('Script path must be set using scriptPath() method');
        }

        $request = (new RequestBuilder())
            ->method(RequestMethod::DELETE)
            ->path($this->scriptPath)
            ->build();

        // If a URI is set, add it as a server parameter
        if (!empty($this->uri)) {
            $request = $request->withServerParam('REQUEST_URI', $this->uri);
            $request = $request->withServerParam('SCRIPT_NAME', $this->uri);
        }

        // Add custom server params and variables
        foreach ($this->serverParams as $key => $value) {
            $request = $request->withServerParam($key, $value);
        }

        foreach ($this->customVars as $key => $value) {
            $request = $request->withCustomVar($key, $value);
        }

        return $this->client->sendRequest($this->getConnection(), $request);
    }

    /**
     * Make a JSON request
     */
    public function json(array $data): Response
    {
        if (empty($this->scriptPath)) {
            throw new \RuntimeException('Script path must be set using scriptPath() method');
        }

        $request = (new RequestBuilder())
            ->method(RequestMethod::POST)
            ->path($this->scriptPath)
            ->json($data)
            ->build();

        // If a URI is set, add it as a server parameter
        if (!empty($this->uri)) {
            $request = $request->withServerParam('REQUEST_URI', $this->uri);
            $request = $request->withServerParam('SCRIPT_NAME', $this->uri);
        }

        // Add custom server params and variables
        foreach ($this->serverParams as $key => $value) {
            $request = $request->withServerParam($key, $value);
        }

        foreach ($this->customVars as $key => $value) {
            $request = $request->withCustomVar($key, $value);
        }

        return $this->client->sendRequest($this->getConnection(), $request);
    }

    /**
     * Execute multiple requests in parallel
     */
    public function pool(Closure $callback): array
    {
        $pool = new RequestPool($this->client);

        return $callback($pool);
    }

    /**
     * Get the current connection or throw if not set
     */
    private function getConnection(): ConnectionInterface
    {
        if ($this->connection === null) {
            throw new \RuntimeException('No connection specified. Call connect() or connectUnix() first.');
        }

        return $this->connection;
    }

    /**
     * Refresh the connection with current timeout settings
     */
    private function refreshConnection(): ConnectionInterface
    {
        if ($this->connection instanceof NetworkConnection) {
            return new NetworkConnection(
                $this->connection->getHost(),
                $this->connection->getPort(),
                $this->connectTimeout,
                $this->readTimeout
            );
        }

        if ($this->connection instanceof UnixDomainSocketConnection) {
            return new UnixDomainSocketConnection(
                $this->connection->getSocketPath(),
                $this->connectTimeout,
                $this->readTimeout
            );
        }

        throw new \RuntimeException('Unknown connection type');
    }
}
