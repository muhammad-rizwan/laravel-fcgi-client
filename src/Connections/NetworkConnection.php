<?php

namespace Rizwan\LaravelFcgiClient\Connections;

class NetworkConnection
{
    public function __construct(
        private readonly string $host,
        private readonly int $port = 9000,
        private readonly int $connectTimeout = 5000,
        private readonly int $readWriteTimeout = 5000,
    ) {}

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

    public function getConnectTimeout(): int
    {
        return $this->connectTimeout;
    }

    public function getReadWriteTimeout(): int
    {
        return $this->readWriteTimeout;
    }

    public function connect(): mixed
    {
        $resource = @stream_socket_client(
            $this->getSocketAddress(),
            $errorNumber,
            $errorString,
            $this->connectTimeout / 1000
        );

        if ($resource === false) {
            throw new \Rizwan\LaravelFcgiClient\Exceptions\ConnectionException(
                "Failed to connect: $errorString",
                $errorNumber
            );
        }

        // Set timeout
        stream_set_timeout(
            $resource,
            (int)($this->readWriteTimeout / 1000),
            ($this->readWriteTimeout % 1000) * 1000
        );

        return $resource;
    }

    public function disconnect(mixed $resource): void
    {
        if (is_resource($resource)) {
            @stream_socket_shutdown($resource, STREAM_SHUT_RDWR);
            @fclose($resource);
        }
    }
}
