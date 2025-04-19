<?php

namespace Rizwan\LaravelFcgiClient\Connections;

interface ConnectionInterface
{
    public function getSocketAddress(): string;

    public function getConnectTimeout(): int;

    public function getReadWriteTimeout(): int;

    public function connect(): mixed;

    public function disconnect(mixed $resource): void;
}
