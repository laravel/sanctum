<?php

namespace Laravel\Sanctum\Tests\Unit;

use Laravel\Sanctum\Sanctum;
use Orchestra\Testbench\TestCase;

class CsrfMiddlewareTest extends TestCase
{
    public function test_csrf_middleware_resolves_to_an_existing_class()
    {
        $middleware = Sanctum::csrfMiddleware();

        $this->assertTrue(class_exists($middleware));
    }
}
