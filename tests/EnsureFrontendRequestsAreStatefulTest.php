<?php

namespace Laravel\Airlock\Tests;

use Illuminate\Http\Request;
use Laravel\Airlock\AirlockServiceProvider;
use Laravel\Airlock\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Orchestra\Testbench\TestCase;

class EnsureFrontendRequestsAreStatefulTest extends TestCase
{
    public function test_request_referer_is_parsed_against_configuration()
    {
        config()->set('airlock.stateful', 'test.com');

        $request = Request::create('/');
        $request->headers->set('referer', 'https://test.com');

        $this->assertTrue(EnsureFrontendRequestsAreStateful::fromFrontend($request));

        $request = Request::create('/');
        $request->headers->set('referer', 'https://wrong.com');

        $this->assertFalse(EnsureFrontendRequestsAreStateful::fromFrontend($request));

        $request = Request::create('/');
        $request->headers->set('referer', 'https://subdomain.test.com');

        $this->assertFalse(EnsureFrontendRequestsAreStateful::fromFrontend($request));
    }

    public function test_request_referer_allows_subdomains()
    {
        config()->set('airlock.stateful', '*.test.com');

        $request = Request::create('/');
        $request->headers->set('referer', 'https://test.com');

        $this->assertFalse(EnsureFrontendRequestsAreStateful::fromFrontend($request));

        $request = Request::create('/');
        $request->headers->set('referer', 'https://wrong.com');

        $this->assertFalse(EnsureFrontendRequestsAreStateful::fromFrontend($request));

        $request = Request::create('/');
        $request->headers->set('referer', 'https://subdomain.test.com');

        $this->assertTrue(EnsureFrontendRequestsAreStateful::fromFrontend($request));

        $request = Request::create('/');
        $request->headers->set('referer', 'https://subdomain.subdomain.test.com');

        $this->assertFalse(EnsureFrontendRequestsAreStateful::fromFrontend($request));
    }

    protected function getPackageProviders($app)
    {
        return [AirlockServiceProvider::class];
    }
}
