<?php

namespace Rizwan\LaravelFcgiClient\Connections;

class UnixDomainSocketConnection extends Connection
{
    public function __construct(
        private readonly string $socketPath,
        int $connectTimeout = 5000,
        int $readWriteTimeout = 5000,
    ) {
        parent::__construct($connectTimeout, $readWriteTimeout);
    }

    public function getSocketAddress(): string
    {
        return 'unix://' . $this->socketPath;
    }

    public function getSocketPath(): string
    {
        return $this->socketPath;
    }
}
