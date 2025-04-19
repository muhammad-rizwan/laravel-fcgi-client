<?php

use Rizwan\LaravelFcgiClient\RequestContents\UrlEncodedContent;

test('has correct content type', function () {
    $content = new UrlEncodedContent([]);

    expect($content->getContentType())->toBe('application/x-www-form-urlencoded');
});

test('encodes form data correctly', function () {
    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'items' => ['item1', 'item2']
    ];

    $content = new UrlEncodedContent($data);

    expect($content->getContent())->toBe(http_build_query($data));
});
