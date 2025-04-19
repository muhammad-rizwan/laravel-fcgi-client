<?php

namespace Rizwan\LaravelFcgiClient\RequestContents;

interface ContentInterface
{
    public function getContentType(): string;

    public function getContent(): string;
}
