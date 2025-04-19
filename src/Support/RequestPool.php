<?php

namespace Rizwan\LaravelFcgiClient\Support;

use Rizwan\LaravelFcgiClient\Client\Client;
use Rizwan\LaravelFcgiClient\Connections\NetworkConnection;
use Rizwan\LaravelFcgiClient\Enums\RequestMethod;
use Rizwan\LaravelFcgiClient\Requests\RequestBuilder;

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

    // [Rest of the methods remain similar, just removing Unix socket references]
}
