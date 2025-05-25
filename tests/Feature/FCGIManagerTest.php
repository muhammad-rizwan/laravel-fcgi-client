<?php

use Rizwan\LaravelFcgiClient\Client\Client;
use Rizwan\LaravelFcgiClient\Client\SocketCollection;
use Rizwan\LaravelFcgiClient\Connections\NetworkConnection;
use Rizwan\LaravelFcgiClient\Encoders\NameValuePairEncoder;
use Rizwan\LaravelFcgiClient\Encoders\PacketEncoder;
use Rizwan\LaravelFcgiClient\FCGIManager;
use Rizwan\LaravelFcgiClient\Responses\Response;

function makeSockets(): SocketCollection
{
    $socket = new NetworkConnection('127.0.0.1', 9000);

    return new SocketCollection([$socket]);
}

beforeEach(function () {
    $this->response = new Response("Content-Type: text/html\r\n\r\nBody", '', 0.1);

    $this->clientMock = $this->getMockBuilder(Client::class)
        ->setConstructorArgs([
            makeSockets(),
            new PacketEncoder,
            new NameValuePairEncoder,
        ])
        ->onlyMethods(['sendRequest'])
        ->getMock();

    $this->clientMock
        ->method('sendRequest')
        ->willReturn($this->response);

    $this->manager = new FCGIManager($this->clientMock);
});

test('can set headers and query params', function () {
    $result = $this->manager
        ->withHeaders(['X-Test' => 'Value'])
        ->withQuery(['foo' => 'bar']);

    expect($result)->toBeInstanceOf(FCGIManager::class);
});

test('can retry on failure', function () {
    $callCount = 0;

    $clientMock = $this->getMockBuilder(Client::class)
        ->setConstructorArgs([
            makeSockets(),
            new PacketEncoder,
            new NameValuePairEncoder,
        ])
        ->onlyMethods(['sendRequest'])
        ->getMock();

    $clientMock->method('sendRequest')
        ->willReturnCallback(function () use (&$callCount) {
            $callCount++;
            if ($callCount < 2) {
                throw new RuntimeException('Simulated failure');
            }

            return new Response("Content-Type: text/html\r\n\r\nBody", '', 0.1);
        });

    $manager = new FCGIManager($clientMock);
    $response = $manager->retry(2)->get('tcp://127.0.0.1:9000', '/index.php');

    expect($response)->toBeInstanceOf(Response::class);
    expect($callCount)->toBe(2)
        ->and($response->getAttempts())->toBe(2);
});
