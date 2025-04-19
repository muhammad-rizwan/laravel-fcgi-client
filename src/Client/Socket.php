<?php

namespace Rizwan\LaravelFcgiClient\Client;

use Rizwan\LaravelFcgiClient\Connections\ConnectionInterface;
use Rizwan\LaravelFcgiClient\Encoders\NameValuePairEncoder;
use Rizwan\LaravelFcgiClient\Encoders\PacketEncoder;
use Rizwan\LaravelFcgiClient\Enums\PacketType;
use Rizwan\LaravelFcgiClient\Enums\ProtocolStatus;
use Rizwan\LaravelFcgiClient\Enums\RequestRole;
use Rizwan\LaravelFcgiClient\Exceptions\ConnectionException;
use Rizwan\LaravelFcgiClient\Exceptions\ReadException;
use Rizwan\LaravelFcgiClient\Exceptions\TimeoutException;
use Rizwan\LaravelFcgiClient\Exceptions\WriteException;
use Rizwan\LaravelFcgiClient\Requests\Request;
use Rizwan\LaravelFcgiClient\Responses\Response;
use Throwable;

class Socket
{
    private const HEADER_LEN = 8;
    private const REQ_MAX_CONTENT_SIZE = 65535;
    private const STREAM_SELECT_USEC = 200000;

    private const SOCK_STATE_INIT = 1;
    private const SOCK_STATE_BUSY = 2;
    private const SOCK_STATE_IDLE = 3;

    private mixed $resource = null;
    private int $status;
    private array $responseCallbacks = [];
    private array $failureCallbacks = [];
    private float $startTime = 0;
    private ?Response $response = null;

    public function __construct(
        private readonly SocketId $id,
        private readonly ConnectionInterface $connection,
        private readonly PacketEncoder $packetEncoder,
        private readonly NameValuePairEncoder $nameValuePairEncoder
    ) {
        $this->status = self::SOCK_STATE_INIT;
    }

    public function getId(): int
    {
        return $this->id->getValue();
    }

    public function isIdle(): bool
    {
        return $this->status === self::SOCK_STATE_INIT || $this->status === self::SOCK_STATE_IDLE;
    }

    public function isBusy(): bool
    {
        return $this->status === self::SOCK_STATE_BUSY;
    }

    public function sendRequest(Request $request): void
    {
        if (!$this->isIdle()) {
            throw new ConnectionException('Trying to use a socket that is not idle');
        }

        $this->response = null;
        $this->responseCallbacks = $request->getResponseCallbacks();
        $this->failureCallbacks = $request->getFailureCallbacks();

        $this->connect();

        $requestPackets = $this->buildRequestPackets($request);
        $this->write($requestPackets);

        $this->status = self::SOCK_STATE_BUSY;
        $this->startTime = microtime(true);
    }

    private function connect(): void
    {
        if (is_resource($this->resource)) {
            return;
        }

        $this->resource = $this->connection->connect();
        $this->status = self::SOCK_STATE_IDLE;
    }

    private function buildRequestPackets(Request $request): string
    {
        // Begin request packet
        $requestPackets = $this->packetEncoder->encodePacket(
            PacketType::BEGIN_REQUEST,
            chr(0) . chr(RequestRole::RESPONDER->value) . chr(1) . str_repeat(chr(0), 5),
            $this->id->getValue()
        );

        // Parameters
        $paramsRequest = $this->nameValuePairEncoder->encodePairs($request->getParams());

        if (!empty($paramsRequest)) {
            $requestPackets .= $this->packetEncoder->encodePacket(
                PacketType::PARAMS,
                $paramsRequest,
                $this->id->getValue()
            );
        }

        // Empty params packet to terminate params
        $requestPackets .= $this->packetEncoder->encodePacket(
            PacketType::PARAMS,
            '',
            $this->id->getValue()
        );

        // Content if available
        $content = $request->getContent();
        if ($content !== null) {
            $contentString = $content->getContent();
            $offset = 0;
            $contentLength = strlen($contentString);

            while ($offset < $contentLength) {
                $chunk = substr($contentString, $offset, self::REQ_MAX_CONTENT_SIZE);
                $requestPackets .= $this->packetEncoder->encodePacket(
                    PacketType::STDIN,
                    $chunk,
                    $this->id->getValue()
                );
                $offset += self::REQ_MAX_CONTENT_SIZE;
            }
        }

        // Empty stdin packet to terminate stdin
        $requestPackets .= $this->packetEncoder->encodePacket(
            PacketType::STDIN,
            '',
            $this->id->getValue()
        );

        return $requestPackets;
    }

    private function write(string $data): void
    {
        if (!is_resource($this->resource)) {
            throw new WriteException('Failed to write request to socket [broken pipe]');
        }

        $writeResult = @fwrite($this->resource, $data);
        $flushResult = @fflush($this->resource);

        if ($writeResult === false || !$flushResult) {
            $metadata = stream_get_meta_data($this->resource);
            if ($metadata['timed_out']) {
                throw new TimeoutException('Write timed out');
            }

            throw new WriteException('Failed to write request to socket [broken pipe]');
        }
    }

    public function fetchResponse(?int $timeoutMs = null): Response
    {
        if ($this->response !== null) {
            return $this->response;
        }

        // Set timeout
        $timeoutMs = $timeoutMs ?? $this->connection->getReadWriteTimeout();
        $this->setStreamTimeout($timeoutMs);

        $error = '';
        $output = '';

        do {
            $packet = $this->readPacket();

            if ($packet === null) {
                break;
            }

            $packetType = $packet['type'];

            if ($packetType === PacketType::STDERR->value) {
                $error .= $packet['content'];
                continue;
            }

            if ($packetType === PacketType::STDOUT->value) {
                $output .= $packet['content'];
                continue;
            }

            if ($packetType === PacketType::END_REQUEST->value &&
                $packet['requestId'] === $this->id->getValue()) {
                break;
            }

        } while ($packet !== null);

        $this->checkResponseErrors($packet, $error);

        $this->response = new Response(
            $output,
            $error,
            microtime(true) - $this->startTime
        );

        $this->status = self::SOCK_STATE_IDLE;

        return $this->response;
    }

    private function setStreamTimeout(int $timeoutMs): bool
    {
        if (!is_resource($this->resource)) {
            return false;
        }

        return stream_set_timeout(
            $this->resource,
            (int)floor($timeoutMs / 1000),
            ($timeoutMs % 1000) * 1000
        );
    }

    private function readPacket(): ?array
    {
        if (!is_resource($this->resource)) {
            return null;
        }

        $header = fread($this->resource, self::HEADER_LEN);

        if (!$header) {
            return null;
        }

        $packet = $this->packetEncoder->decodeHeader($header);
        $packet['content'] = '';

        if ($packet['contentLength'] > 0) {
            $length = $packet['contentLength'];

            while ($length > 0 && ($buffer = fread($this->resource, $length)) !== false) {
                $packet['content'] .= $buffer;
                $length -= strlen($buffer);
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
                throw new ReadException('Stream got blocked, or terminated');
            }

            throw new ReadException('Read failed');
        }

        if ($packet !== null && isset($packet['content']) && strlen($packet['content']) >= 5) {
            $status = ord($packet['content'][4]);

            // Check for protocol status errors
            match ($status) {
                ProtocolStatus::REQUEST_COMPLETE->value => null,
                ProtocolStatus::CANT_MPX_CONN->value =>
                throw new WriteException("This app can't multiplex [CANT_MPX_CONN]"),
                ProtocolStatus::OVERLOADED->value =>
                throw new WriteException("New request rejected; too busy [OVERLOADED]"),
                ProtocolStatus::UNKNOWN_ROLE->value =>
                throw new WriteException("Role value not known [UNKNOWN_ROLE]"),
                default => throw new ReadException("Unknown protocol status: $status")
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

    public function notifyCallbacks(Response $response): void
    {
        foreach ($this->responseCallbacks as $callback) {
            $callback($response);
        }
    }

    public function notifyFailureCallbacks(Throwable $throwable): void
    {
        foreach ($this->failureCallbacks as $callback) {
            $callback($throwable);
        }
    }
}
