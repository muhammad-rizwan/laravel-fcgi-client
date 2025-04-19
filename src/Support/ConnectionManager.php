<?php

namespace Rizwan\LaravelFcgiClient\Support;

use Rizwan\LaravelFcgiClient\Connections\NetworkConnection;
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

    public function getConnection(string $name): NetworkConnection
    {
        if (!$this->hasConnection($name)) {
            throw new InvalidArgumentException("LaravelFcgiClient connection '$name' not defined");
        }

        return $this->connections[$name];
    }

    public function parseConnectionUrl(string $url, array $options = []): NetworkConnection
    {
        $connectTimeout = $options['connect_timeout'] ?? 5000;
        $readWriteTimeout = $options['read_write_timeout'] ?? 5000;
        $url = substr($url, 6);

        // Parse host and port
        $parts = parse_url('tcp://' . $url);
        $host = $parts['host'] ?? '127.0.0.1';
        $port = $parts['port'] ?? 9000;

        return new NetworkConnection($host, $port, $connectTimeout, $readWriteTimeout);
    }
}
