<?php

namespace Rizwan\LaravelFcgiClient\Client;

use Rizwan\LaravelFcgiClient\Connections\ConnectionInterface;
use Rizwan\LaravelFcgiClient\Encoders\NameValuePairEncoder;
use Rizwan\LaravelFcgiClient\Encoders\PacketEncoder;
use Rizwan\LaravelFcgiClient\Exceptions\ReadException;
use Rizwan\LaravelFcgiClient\Requests\Request;
use Rizwan\LaravelFcgiClient\Responses\Response;
use Throwable;

class Client
{
    private SocketCollection $sockets;
    private PacketEncoder $packetEncoder;
    private NameValuePairEncoder $nameValuePairEncoder;

    public function __construct()
    {
        $this->sockets = new SocketCollection();
        $this->packetEncoder = new PacketEncoder();
        $this->nameValuePairEncoder = new NameValuePairEncoder();
    }

    public function sendRequest(ConnectionInterface $connection, Request $request): Response
    {
        $socketId = $this->sendAsyncRequest($connection, $request);
        return $this->readResponse($socketId);
    }

    public function sendAsyncRequest(ConnectionInterface $connection, Request $request): int
    {
        $socket = $this->sockets->new(
            $connection,
            $this->packetEncoder,
            $this->nameValuePairEncoder
        );

        try {
            $socket->sendRequest($request);
            return $socket->getId();
        } catch (Throwable $e) {
            $this->sockets->remove($socket->getId());
            throw $e;
        }
    }

    public function readResponse(int $socketId, ?int $timeoutMs = null): Response
    {
        try {
            return $this->sockets->getById($socketId)->fetchResponse($timeoutMs);
        } catch (Throwable $e) {
            $this->sockets->remove($socketId);
            throw $e;
        } finally {
            $this->sockets->remove($socketId);
        }
    }

    public function waitForResponse(int $socketId, ?int $timeoutMs = null): void
    {
        $socket = $this->sockets->getById($socketId);

        try {
            $response = $socket->fetchResponse($timeoutMs);
            $socket->notifyCallbacks($response);
        } catch (Throwable $e) {
            $socket->notifyFailureCallbacks($e);
            throw $e;
        } finally {
            $this->sockets->remove($socketId);
        }
    }

    public function hasUnhandledResponses(): bool
    {
        return $this->sockets->hasBusySockets();
    }
}
