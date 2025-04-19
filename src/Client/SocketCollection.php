<?php

namespace Rizwan\LaravelFcgiClient\Client;

use Rizwan\LaravelFcgiClient\Connections\ConnectionInterface;
use Rizwan\LaravelFcgiClient\Encoders\NameValuePairEncoder;
use Rizwan\LaravelFcgiClient\Encoders\PacketEncoder;
use Rizwan\LaravelFcgiClient\Exceptions\ReadException;
use Rizwan\LaravelFcgiClient\Exceptions\WriteException;

class SocketCollection
{
    /** @var Socket[] */
    private array $sockets = [];

    public function new(
        ConnectionInterface $connection,
        PacketEncoder $packetEncoder,
        NameValuePairEncoder $nameValuePairEncoder
    ): Socket {
        // Try to create a unique socket ID
        for ($i = 0; $i < 10; $i++) {
            $socketId = SocketId::generate();

            if (!$this->exists($socketId->getValue())) {
                $socket = new Socket(
                    $socketId,
                    $connection,
                    $packetEncoder,
                    $nameValuePairEncoder
                );

                $this->sockets[$socketId->getValue()] = $socket;
                return $socket;
            }
        }

        throw new WriteException('Could not allocate a new socket ID');
    }

    public function getById(int $socketId): Socket
    {
        if (!$this->exists($socketId)) {
            throw new ReadException("Socket not found for socket ID: $socketId");
        }

        return $this->sockets[$socketId];
    }

    public function exists(int $socketId): bool
    {
        return isset($this->sockets[$socketId]);
    }

    public function remove(int $socketId): void
    {
        unset($this->sockets[$socketId]);
    }

    public function isEmpty(): bool
    {
        return empty($this->sockets);
    }

    public function hasBusySockets(): bool
    {
        foreach ($this->sockets as $socket) {
            if ($socket->isBusy()) {
                return true;
            }
        }

        return false;
    }
}
