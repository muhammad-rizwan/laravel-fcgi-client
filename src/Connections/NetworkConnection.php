<?php

namespace Rizwan\LaravelFcgiClient\Connections;

class NetworkConnection extends Connection
{
    public function __construct(
        private readonly string $host,
        private readonly int $port = 9000,
        int $connectTimeout = 5000,
        int $readWriteTimeout = 5000,
    ) {
        parent::__construct($connectTimeout, $readWriteTimeout);
    }

    public function getSocketAddress(): string
    {
        return sprintf('tcp://%s:%d', $this->host, $this->port);
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }
}
