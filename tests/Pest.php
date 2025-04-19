<?php

// tests/Pest.php

use Rizwan\LaravelFcgiClient\Tests\TestCase;

uses(TestCase::class)->in('Feature');
uses(TestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| Define custom expect methods here if needed.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});
