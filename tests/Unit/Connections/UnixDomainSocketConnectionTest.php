<?php

use Rizwan\LaravelFcgiClient\Connections\UnixDomainSocketConnection;

test('gets correct socket address', function () {
    $socketPath = '/var/run/php-fpm.sock';
    $connection = new UnixDomainSocketConnection($socketPath);

    expect($connection->getSocketAddress())->toBe("unix://{$socketPath}");
});

test('returns configured timeouts', function () {
    $connectTimeout = 3000;
    $readWriteTimeout = 5000;
    $socketPath = '/var/run/php-fpm.sock';
    $connection = new UnixDomainSocketConnection($socketPath, $connectTimeout, $readWriteTimeout);

    expect($connection->getConnectTimeout())->toBe($connectTimeout);
    expect($connection->getReadWriteTimeout())->toBe($readWriteTimeout);
});
