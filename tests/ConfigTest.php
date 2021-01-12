<?php

namespace Laravel\Sanctum\Tests;

use Illuminate\Foundation\Testing\WithFaker;
use Orchestra\Testbench\TestCase;

class ConfigTest extends TestCase
{
    use WithFaker;

    public function test_default_stateful_value_contains_app_url()
    {
        $previousAppUrl = getenv('APP_URL');
        $domain = $this->faker->domainName;
        putenv("APP_URL={$domain}");

        $config = require __DIR__ . '/../config/sanctum.php';
        $stateful = $config['stateful'] ?? [];

        $this->assertContains($domain, $stateful);

        putenv("APP_URL={$previousAppUrl}");
    }
}
