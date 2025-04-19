<?php

namespace Rizwan\LaravelFcgiClient\Support;

use Rizwan\LaravelFcgiClient\Connections\ConnectionInterface;
use Rizwan\LaravelFcgiClient\Connections\NetworkConnection;
use Rizwan\LaravelFcgiClient\Connections\UnixDomainSocketConnection;
use InvalidArgumentException;

class ConnectionManager
{
    private array $connections = [];

    public function defineConnection(string $name, string $url, array $options = []): void
    {
        $this->connections[$name] = $this->parseConnectionUrl($url, $options);
    }

    public function hasConnection(string $name): bool
    {
        return isset($this->connections[$name]);
    }

    public function getConnection(string $name): ConnectionInterface
    {
        if (!$this->hasConnection($name)) {
            throw new InvalidArgumentException("LaravelFcgiClient connection '$name' not defined");
        }

        return $this->connections[$name];
    }

    public function parseConnectionUrl(string $url, array $options = []): ConnectionInterface
    {
        $connectTimeout = $options['connect_timeout'] ?? 5000;
        $readWriteTimeout = $options['read_write_timeout'] ?? 5000;

        // Parse URL
        if (str_starts_with($url, 'unix://')) {
            // Unix socket connection
            $socketPath = substr($url, 7);
            return new UnixDomainSocketConnection($socketPath, $connectTimeout, $readWriteTimeout);
        }

        if (str_starts_with($url, 'tcp://')) {
            $url = substr($url, 6);
        }

        // Parse host and port
        $parts = parse_url('tcp://' . $url);
        $host = $parts['host'] ?? '127.0.0.1';
        $port = $parts['port'] ?? 9000;

        return new NetworkConnection($host, $port, $connectTimeout, $readWriteTimeout);
    }
}
