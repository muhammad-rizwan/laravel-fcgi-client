<?php

use Rizwan\LaravelFcgiClient\Enums\RequestMethod;
use Rizwan\LaravelFcgiClient\RequestContents\UrlEncodedContent;
use Rizwan\LaravelFcgiClient\Requests\Request;

test('request has correct method and script path', function () {
    $method = RequestMethod::GET;
    $scriptPath = '/path/to/script.php';
    $request = new Request($method, $scriptPath);

    expect($request->getRequestMethod())->toBe($method);
    expect($request->getScriptFilename())->toBe($scriptPath);
});

test('request includes content when provided', function () {
    $method = RequestMethod::POST;
    $scriptPath = '/path/to/script.php';
    $content = new UrlEncodedContent(['key' => 'value']);
    $request = new Request($method, $scriptPath, $content);

    expect($request->getContent())->toBe($content);
    expect($request->getContentLength())->toBe(strlen($content->getContent()));
});

test('request params include server parameters', function () {
    $method = RequestMethod::GET;
    $scriptPath = '/path/to/script.php';
    $request = new Request($method, $scriptPath);

    $params = $request->getParams();

    expect($params)->toHaveKey('GATEWAY_INTERFACE');
    expect($params)->toHaveKey('REQUEST_METHOD');
    expect($params)->toHaveKey('SCRIPT_FILENAME');
    expect($params['REQUEST_METHOD'])->toBe($method->value);
    expect($params['SCRIPT_FILENAME'])->toBe($scriptPath);
});

test('request can have custom variables', function () {
    $method = RequestMethod::GET;
    $scriptPath = '/path/to/script.php';
    $request = new Request($method, $scriptPath);

    $customKey = 'CUSTOM_VAR';
    $customValue = 'custom value';
    $requestWithCustomVar = $request->withCustomVar($customKey, $customValue);

    $params = $requestWithCustomVar->getParams();
    expect($params)->toHaveKey($customKey);
    expect($params[$customKey])->toBe($customValue);
});

test('request can register callbacks', function () {
    $method = RequestMethod::GET;
    $scriptPath = '/path/to/script.php';
    $request = new Request($method, $scriptPath);

    $callback = fn () => true;
    $requestWithCallback = $request->addResponseCallback($callback);

    expect($requestWithCallback->getResponseCallbacks())->toContain($callback);
});
