<?php

namespace Rizwan\LaravelFcgiClient\Connections;

use Rizwan\LaravelFcgiClient\Exceptions\ConnectionException;

final class NetworkConnection
{
    /**
     * @param  int  $port  Port for FastCGI (default: 9000)
     * @param  int  $connectTimeout  Timeout in milliseconds
     * @param  int  $readWriteTimeout  Timeout in milliseconds
     */
    public function __construct(
        private string $host,
        private int $port = 9000,
        private int $connectTimeout = 5000,
        private int $readWriteTimeout = 5000,
    ) {}

    public function getSocketAddress(): string
    {
        return sprintf('tcp://%s:%d', $this->host, $this->port);
    }

    public function getConnectTimeout(): int
    {
        return $this->connectTimeout;
    }

    public function getReadWriteTimeout(): int
    {
        return $this->readWriteTimeout;
    }

    /**
     * Establishes a stream socket connection.
     *
     * @return resource
     *
     * @throws ConnectionException
     */
    public function connect(): mixed
    {
        $resource = @stream_socket_client(
            $this->getSocketAddress(),
            $errorNumber,
            $errorString,
            $this->connectTimeout / 1000
        );

        if ($resource === false) {
            throw new ConnectionException("Failed to connect: $errorString", $errorNumber);
        }

        stream_set_timeout(
            $resource,
            intdiv($this->readWriteTimeout, 1000),
            ($this->readWriteTimeout % 1000) * 1000
        );

        return $resource;
    }

    /**
     * Gracefully shuts down and closes the socket.
     */
    public function disconnect(mixed $resource): void
    {
        if (is_resource($resource)) {
            @stream_socket_shutdown($resource, STREAM_SHUT_RDWR);
            @fclose($resource);
        }
    }
}
