<?php

use Rizwan\LaravelFcgiClient\Enums\RequestMethod;
use Rizwan\LaravelFcgiClient\RequestContents\UrlEncodedContent;
use Rizwan\LaravelFcgiClient\Requests\Request;

test('request has correct method and script path', function () {
    $method = RequestMethod::GET;
    $scriptPath = '/index.php';

    $request = new Request($method, $scriptPath);

    expect($request->getRequestMethod())->toBe($method);
    expect($request->getScriptFilename())->toBe($scriptPath);
});

test('request includes content when provided', function () {
    $content = new UrlEncodedContent(['key' => 'value']);

    $request = new Request(RequestMethod::POST, '/submit.php', $content);

    expect($request->getContent())->toBe($content);
    expect($request->getContentLength())->toBe(strlen($content->getContent()));
});

test('request includes default parameters', function () {
    $method = RequestMethod::GET;
    $scriptPath = '/index.php';

    $request = new Request($method, $scriptPath);

    $params = $request->getParams();

    expect($params)->toHaveKey('REQUEST_METHOD');
    expect($params)->toHaveKey('SCRIPT_FILENAME');

    expect($params['REQUEST_METHOD'])->toBe($method->value);
    expect($params['SCRIPT_FILENAME'])->toBe($scriptPath);
});

test('request supports custom variables', function () {
    $request = new Request(RequestMethod::GET, '/index.php');

    $request = $request->withCustomVar('CUSTOM_VAR', 'custom_value');

    $params = $request->getParams();

    expect($params)->toHaveKey('CUSTOM_VAR');
    expect($params['CUSTOM_VAR'])->toBe('custom_value');
});
