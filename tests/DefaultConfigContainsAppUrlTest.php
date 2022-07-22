<?php

namespace Laravel\Sanctum\Tests;

use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Laravel\Sanctum\SanctumServiceProvider;
use Orchestra\Testbench\TestCase;

class DefaultConfigContainsAppUrlTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        putenv('APP_URL=https://www.example.com');
        $config = require __DIR__.'/../config/sanctum.php';

        $app['config']->set('sanctum.stateful', $config['stateful']);
    }

    public function test_default_config_contains_app_url()
    {
        $config = require __DIR__.'/../config/sanctum.php';

        $app_host = parse_url(env('APP_URL'), PHP_URL_HOST);

        $this->assertContains($app_host, $config['stateful']);
    }

    public function test_app_url_is_not_parsed_when_missing_from_env()
    {
        putenv('APP_URL');

        $config = require __DIR__.'/../config/sanctum.php';

        $this->assertNull(env('APP_URL'));
        $this->assertNotContains('', $config['stateful']);
    }

    public function test_request_from_app_url_is_stateful_with_default_config()
    {
        $request = Request::create('/');

        $request->headers->set('referer', env('APP_URL'));

        $this->assertTrue(EnsureFrontendRequestsAreStateful::fromFrontend($request));
    }

    public function test_csrf_cookie_route_can_be_named()
    {
        $app = app();
        $app['config']->set('sanctum.cookie_route_name', 'csrf.token');
        (new SanctumServiceProvider($app))->boot();

        $app->router->getRoutes()->compile();

        $this->assertTrue($app->router->getRoutes()->hasNamedRoute('csrf.token'));
    }
}
