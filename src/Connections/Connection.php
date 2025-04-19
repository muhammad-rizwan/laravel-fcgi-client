<?php

namespace Rizwan\LaravelFcgiClient\Connections;

use Rizwan\LaravelFcgiClient\Exceptions\ConnectionException;

abstract class Connection implements ConnectionInterface
{
    public function __construct(
        protected readonly int $connectTimeout = 5000,
        protected readonly int $readWriteTimeout = 5000,
    ) {}

    abstract public function getSocketAddress(): string;

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
            throw new ConnectionException(
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
