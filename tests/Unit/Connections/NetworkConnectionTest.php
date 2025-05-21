<?php

use Rizwan\LaravelFcgiClient\Connections\NetworkConnection;

test('gets correct socket address', function () {
    $host = '127.0.0.1';
    $port = 9000;

    $connection = new NetworkConnection($host, $port);

    expect($connection->getSocketAddress())
        ->toBe("tcp://{$host}:{$port}");
});

test('returns configured timeouts', function () {
    $connectTimeout = 3000;
    $readWriteTimeout = 5000;

    $connection = new NetworkConnection('127.0.0.1', 9000, $connectTimeout, $readWriteTimeout);

    expect($connection->getConnectTimeout())->toBe($connectTimeout);
    expect($connection->getReadWriteTimeout())->toBe($readWriteTimeout);
});
