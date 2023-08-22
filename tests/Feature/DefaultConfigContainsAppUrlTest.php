<?php

namespace Laravel\Sanctum\Tests\Feature;

use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;

use function Orchestra\Testbench\package_path;

class DefaultConfigContainsAppUrlTest extends TestCase
{
    use WithWorkbench;

    protected function defineEnvironment($app)
    {
        putenv('APP_URL=https://www.example.com');
        $app['config']->set('app.url', 'https://www.example.com');

        $config = require package_path('config/sanctum.php');

        $app['config']->set('sanctum.stateful', $config['stateful']);
    }

    public function test_default_config_contains_app_url()
    {
        $config = require package_path('config/sanctum.php');

        $app_host = parse_url(env('APP_URL'), PHP_URL_HOST);

        $this->assertContains($app_host, $config['stateful']);
    }

    public function test_app_url_is_not_parsed_when_missing_from_env()
    {
        putenv('APP_URL');
        config(['app.url' => null]);

        $config = require package_path('config/sanctum.php');

        $this->assertNull(env('APP_URL'));
        $this->assertNotContains('', $config['stateful']);

        putenv('APP_URL=https://www.example.com');
        config(['app.url' => 'https://www.example.com']);
    }

    public function test_request_from_app_url_is_stateful_with_default_config()
    {
        $request = Request::create('/');

        $request->headers->set('referer', config('app.url'));

        $this->assertTrue(EnsureFrontendRequestsAreStateful::fromFrontend($request));
    }
}
