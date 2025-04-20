<?php

namespace Rizwan\LaravelFcgiClient\Client;

use Rizwan\LaravelFcgiClient\Connections\NetworkConnection;
use Rizwan\LaravelFcgiClient\Encoders\{NameValuePairEncoder, PacketEncoder};
use Rizwan\LaravelFcgiClient\Exceptions\WriteException;

final class SocketCollection
{
    /** @var array<int, Socket> */
    private array $sockets = [];

    /**
     * @throws WriteException
     */
    public function new(
        NetworkConnection $connection,
        PacketEncoder $packetEncoder,
        NameValuePairEncoder $nameValuePairEncoder,
    ): Socket {
        for ($i = 0; $i < 10; $i++) {
            $socketId = SocketId::generate();

            if (!$this->exists($socketId->getValue())) {
                $socket = new Socket($socketId, $connection, $packetEncoder, $nameValuePairEncoder);
                $this->sockets[$socketId->getValue()] = $socket;
                return $socket;
            }
        }

        throw new WriteException('Could not allocate a new socket ID');
    }

    public function exists(int $socketId): bool
    {
        return isset($this->sockets[$socketId]);
    }

    public function remove(int $socketId): void
    {
        unset($this->sockets[$socketId]);
    }
}
