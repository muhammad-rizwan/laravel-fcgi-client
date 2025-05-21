<?php

use Rizwan\LaravelFcgiClient\RequestContents\UrlEncodedContent;

test('has correct content type', function () {
    $content = new UrlEncodedContent([]);

    expect($content->getContentType())
        ->toBe('application/x-www-form-urlencoded');
});

test('encodes form data correctly', function () {
    $data = [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'interests' => ['php', 'laravel'],
    ];

    $content = new UrlEncodedContent($data);

    expect($content->getContent())
        ->toBe(http_build_query($data));
});
