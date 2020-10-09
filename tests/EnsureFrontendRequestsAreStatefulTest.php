<?php

namespace Laravel\Sanctum\Tests;

use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Laravel\Sanctum\SanctumServiceProvider;
use Orchestra\Testbench\TestCase;

class EnsureFrontendRequestsAreStatefulTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('sanctum.stateful', ['test.com', '*.test.com']);
    }

    public function test_request_referer_is_parsed_against_configuration()
    {
        $request = Request::create('/');
        $request->headers->set('referer', 'https://test.com');

        $this->assertTrue(EnsureFrontendRequestsAreStateful::fromFrontend($request));

        $request = Request::create('/');
        $request->headers->set('referer', 'https://wrong.com');

        $this->assertFalse(EnsureFrontendRequestsAreStateful::fromFrontend($request));

        $request = Request::create('/');
        $request->headers->set('referer', 'https://test.com.x');

        $this->assertFalse(EnsureFrontendRequestsAreStateful::fromFrontend($request));

        $request = Request::create('/');
        $request->headers->set('referer', 'https://foobar.test.com/');

        $this->assertTrue(EnsureFrontendRequestsAreStateful::fromFrontend($request));
    }

    public function test_request_origin_fallback()
    {
        $request = Request::create('/');
        $request->headers->set('origin', 'test.com');

        $this->assertTrue(EnsureFrontendRequestsAreStateful::fromFrontend($request));

        $request = Request::create('/');
        $request->headers->set('referer', null);
        $request->headers->set('origin', 'test.com');

        $this->assertTrue(EnsureFrontendRequestsAreStateful::fromFrontend($request));

        $request = Request::create('/');
        $request->headers->set('referer', '');
        $request->headers->set('origin', 'test.com');

        $this->assertTrue(EnsureFrontendRequestsAreStateful::fromFrontend($request));
    }

    public function test_wildcard_matching()
    {
        $request = Request::create('/');
        $request->headers->set('referer', 'https://foo.test.com');

        $this->assertTrue(EnsureFrontendRequestsAreStateful::fromFrontend($request));
    }

    public function test_requests_are_not_stateful_without_referer()
    {
        $this->app['config']->set('sanctum.stateful', ['']);

        $request = Request::create('/');

        $this->assertFalse(EnsureFrontendRequestsAreStateful::fromFrontend($request));
    }

    protected function getPackageProviders($app)
    {
        return [SanctumServiceProvider::class];
    }
}
