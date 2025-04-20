<?php

namespace Rizwan\LaravelFcgiClient\Client;

use Rizwan\LaravelFcgiClient\Connections\NetworkConnection;
use Rizwan\LaravelFcgiClient\Encoders\NameValuePairEncoder;
use Rizwan\LaravelFcgiClient\Encoders\PacketEncoder;
use Rizwan\LaravelFcgiClient\Exceptions\ConnectionException;
use Rizwan\LaravelFcgiClient\Exceptions\ReadException;
use Rizwan\LaravelFcgiClient\Exceptions\WriteException;
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

    /**
     * @param NetworkConnection $connection
     * @param Request $request
     * @return Response
     * @throws Throwable
     * @throws ConnectionException
     * @throws WriteException
     */
    public function sendRequest(NetworkConnection $connection, Request $request): Response
    {
        // Create a socket, send request and directly read the response
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
