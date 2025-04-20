<?php

namespace Rizwan\LaravelFcgiClient\Client;

use Rizwan\LaravelFcgiClient\Connections\NetworkConnection;
use Rizwan\LaravelFcgiClient\Encoders\NameValuePairEncoder;
use Rizwan\LaravelFcgiClient\Encoders\PacketEncoder;
use Rizwan\LaravelFcgiClient\Exceptions\{ConnectionException, ReadException, TimeoutException, WriteException};
use Rizwan\LaravelFcgiClient\Requests\Request;
use Rizwan\LaravelFcgiClient\Responses\Response;
use Throwable;

final class Client
{
    /**
     * @param SocketCollection $sockets
     * @param PacketEncoder $packetEncoder
     * @param NameValuePairEncoder $nameValuePairEncoder
     */
    public function __construct(
        private SocketCollection $sockets = new SocketCollection(),
        private PacketEncoder $packetEncoder = new PacketEncoder(),
        private NameValuePairEncoder $nameValuePairEncoder = new NameValuePairEncoder(),
    ) {}

    /**
     * @throws Throwable
     * @throws TimeoutException
     * @throws ReadException
     * @throws ConnectionException
     * @throws WriteException
     */
    public function sendRequest(NetworkConnection $connection, Request $request): Response
    {
        $socket = $this->sockets->new(
            $connection,
            $this->packetEncoder,
            $this->nameValuePairEncoder
        );

        try {
            $socket->sendRequest($request);
            return $socket->fetchResponse();
        } catch (Throwable $e) {
            $this->sockets->remove($socket->getId());
            throw $e;
        } finally {
            $this->sockets->remove($socket->getId());
        }
    }
}
