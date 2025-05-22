<?php

namespace Rizwan\LaravelFcgiClient\Client;

use Illuminate\Support\Facades\Log;
use Rizwan\LaravelFcgiClient\Connections\NetworkConnection;
use Rizwan\LaravelFcgiClient\Encoders\NameValuePairEncoder;
use Rizwan\LaravelFcgiClient\Encoders\PacketEncoder;
use Rizwan\LaravelFcgiClient\Enums\PacketType;
use Rizwan\LaravelFcgiClient\Enums\ProtocolStatus;
use Rizwan\LaravelFcgiClient\Enums\RequestRole;
use Rizwan\LaravelFcgiClient\Enums\SocketStatus;
use Rizwan\LaravelFcgiClient\Exceptions\ConnectionException;
use Rizwan\LaravelFcgiClient\Exceptions\ReadException;
use Rizwan\LaravelFcgiClient\Exceptions\TimeoutException;
use Rizwan\LaravelFcgiClient\Exceptions\WriteException;
use Rizwan\LaravelFcgiClient\Requests\Request;
use Rizwan\LaravelFcgiClient\Responses\Response;

final class Socket
{
    private const HEADER_LEN = 8;

    private const REQ_MAX_CONTENT_SIZE = 65535;

    private mixed $resource = null;

    private SocketStatus $status = SocketStatus::INIT;

    private float $startTime = 0;

    /** @var float Connection duration in milliseconds */
    private float $connectDuration = 0.0;

    /** @var float Write duration in milliseconds */
    private float $writeDuration = 0.0;

    private ?Response $response = null;

    public function __construct(
        private readonly SocketId $id,
        private readonly NetworkConnection $connection,
        private readonly PacketEncoder $packetEncoder,
        private readonly NameValuePairEncoder $nameValuePairEncoder,
    ) {}

    public function getId(): int
    {
        return $this->id->getValue();
    }

    /**
     * @throws ConnectionException
     * @throws WriteException
     * @throws TimeoutException
     */
    public function sendRequest(Request $request): void
    {
        if (! $this->status->isAvailable()) {
            throw new ConnectionException('Trying to use a socket that is not idle');
        }

        $this->response = null;

        $connectStart = microtime(true);
        $this->connect();
        $connectEnd = microtime(true);
        $this->connectDuration = ($connectEnd - $connectStart) * 1000;

        $packets = $this->buildRequestPackets($request);

        $writeStart = microtime(true);
        $this->write($packets);
        $writeEnd = microtime(true);
        $this->writeDuration = ($writeEnd - $writeStart) * 1000;

        $this->status = SocketStatus::BUSY;
        $this->startTime = microtime(true);
    }

    private function connect(): void
    {
        if (! is_resource($this->resource)) {
            $this->resource = $this->connection->connect();
            $this->status = SocketStatus::IDLE;
        }
    }

    private function buildRequestPackets(Request $request): string
    {
        $requestId = $this->id->getValue();

        $packets = $this->packetEncoder->encodePacket(
            PacketType::BEGIN_REQUEST,
            chr(0).chr(RequestRole::RESPONDER->value).chr(1).str_repeat(chr(0), 5),
            $requestId
        );

        $params = $this->nameValuePairEncoder->encodePairs($request->getParams());

        if ($params !== '') {
            $packets .= $this->packetEncoder->encodePacket(PacketType::PARAMS, $params, $requestId);
        }

        $packets .= $this->packetEncoder->encodePacket(PacketType::PARAMS, '', $requestId);

        $content = $request->getContent()?->getContent();
        if ($content) {
            for ($offset = 0, $length = strlen($content); $offset < $length; $offset += self::REQ_MAX_CONTENT_SIZE) {
                $chunk = substr($content, $offset, self::REQ_MAX_CONTENT_SIZE);
                $packets .= $this->packetEncoder->encodePacket(PacketType::STDIN, $chunk, $requestId);
            }
        }

        return $packets.$this->packetEncoder->encodePacket(PacketType::STDIN, '', $requestId);
    }

    private function write(string $data): void
    {
        if (! is_resource($this->resource)) {
            throw new WriteException('Failed to write request to socket [broken pipe]');
        }

        $writeResult = @fwrite($this->resource, $data);
        $flushResult = @fflush($this->resource);

        if ($writeResult === false || ! $flushResult) {
            $meta = stream_get_meta_data($this->resource);
            throw $meta['timed_out']
                ? new TimeoutException('Write timed out')
                : new WriteException('Failed to write request to socket [broken pipe]');
        }
    }

    /**
     * @throws ReadException
     * @throws TimeoutException|WriteException
     */
    public function fetchResponse(?int $timeoutMs = null): Response
    {
        if ($this->response) {
            return $this->response;
        }

        $this->setStreamTimeout($timeoutMs ?? $this->connection->getReadWriteTimeout());

        $output = '';
        $error = '';

        while ($packet = $this->readPacket()) {
            if ($packet['type'] === PacketType::STDOUT->value) {
                $output .= $packet['content'];
            } elseif ($packet['type'] === PacketType::STDERR->value) {
                $error .= $packet['content'];
            } elseif (
                $packet['type'] === PacketType::END_REQUEST->value &&
                $packet['requestId'] === $this->id->getValue()
            ) {
                break;
            }
        }

        $this->checkResponseErrors($packet, $error);

        $this->response = new Response(
            $output,
            $error,microtime(true) - $this->startTime,
            $this->connectDuration,
            $this->writeDuration

        );

        $this->status = SocketStatus::IDLE;

        return $this->response;
    }

    private function setStreamTimeout(int $timeoutMs): void
    {
        if (is_resource($this->resource)) {
            stream_set_timeout(
                $this->resource,
                intdiv($timeoutMs, 1000),
                ($timeoutMs % 1000) * 1000
            );
        }
    }

    private function readPacket(): ?array
    {
        if (! is_resource($this->resource)) {
            return null;
        }

        $header = fread($this->resource, self::HEADER_LEN);
        if (! $header) {
            return null;
        }

        $packet = $this->packetEncoder->decodeHeader($header);
        $packet['content'] = '';

        if ($packet['contentLength'] > 0) {
            $remaining = $packet['contentLength'];
            while ($remaining > 0 && ($buffer = fread($this->resource, $remaining)) !== false) {
                $packet['content'] .= $buffer;
                $remaining -= strlen($buffer);
            }
        }

        if ($packet['paddingLength'] > 0) {
            @fread($this->resource, $packet['paddingLength']);
        }

        return $packet;
    }

    private function checkResponseErrors(?array $packet, string $error): void
    {
        if ($packet === null && is_resource($this->resource)) {
            $info = stream_get_meta_data($this->resource);

            if ($info['timed_out']) {
                throw new TimeoutException('Read timed out');
            }

            if ($info['unread_bytes'] === 0 && $info['blocked'] && $info['eof']) {
                throw new ReadException('Stream got blocked or terminated');
            }

            throw new ReadException('Read failed');
        }

        if (isset($packet['content']) && strlen($packet['content']) >= 5) {
            $status = ord($packet['content'][4]);

            match ($status) {
                ProtocolStatus::REQUEST_COMPLETE->value => null,
                ProtocolStatus::CANT_MPX_CONN->value => throw new WriteException("This app can't multiplex [CANT_MPX_CONN]"),
                ProtocolStatus::OVERLOADED->value => throw new WriteException('New request rejected; too busy [OVERLOADED]'),
                ProtocolStatus::UNKNOWN_ROLE->value => throw new WriteException('Role value not known [UNKNOWN_ROLE]'),
                default => throw new ReadException("Unknown protocol status: $status"),
            };
        }
    }

    public function disconnect(): void
    {
        if (is_resource($this->resource)) {
            $this->connection->disconnect($this->resource);
            $this->resource = null;
        }
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
