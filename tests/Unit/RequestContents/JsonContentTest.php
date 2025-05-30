<?php

use Rizwan\LaravelFcgiClient\RequestContents\JsonContent;

test('has correct content type', function () {
    $content = new JsonContent([]);

    expect($content->getContentType())
        ->toBe('application/json');
});

test('encodes json data correctly', function () {
    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'items' => ['apple', 'banana'],
    ];

    $content = new JsonContent($data);

    expect($content->getContent())
        ->toBe(json_encode($data));
});

test('uses custom json encoding options', function () {
    $data = ['message' => 'Hello World'];
    $options = JSON_PRETTY_PRINT;

    $content = new JsonContent($data, $options);

    expect($content->getContent())
        ->toBe(json_encode($data, $options));
});
