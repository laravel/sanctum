<?php

namespace Laravel\Sanctum\Tests;

use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Orchestra\Testbench\TestCase;

class DefaultConfigContainsAppUrlTest extends TestCase
{
    protected function useDefaultStatefulConfiguration($app)
    {
        $config = require __DIR__.'/../config/sanctum.php';

        $app->config->set('sanctum.stateful', $config['stateful']);
    }

    public function test_default_config_contains_app_url()
    {
        $config = require __DIR__.'/../config/sanctum.php';

        $app_host = parse_url(env('APP_URL'), PHP_URL_HOST);

        $this->assertContains($app_host, $config['stateful']);
    }

    /**
     * @environment-setup useDefaultStatefulConfiguration
     */
    public function test_request_from_app_url_is_stateful_with_default_config()
    {
        $request = Request::create('/');
        $request->headers->set('referer', env('APP_URL'));

        $this->assertTrue(EnsureFrontendRequestsAreStateful::fromFrontend($request));
    }
}
