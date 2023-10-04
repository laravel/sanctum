<?php

namespace Laravel\Sanctum\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;

class EnsureFrontendRequestsAreStatefulTest extends TestCase
{
    use WithWorkbench;

    protected function defineEnvironment($app)
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

    public function test_request_stateful_when_token_not_present()
    {
        $this->app['config']->set('sanctum.stateful', ['test.com']);
        $this->app['config']->set('sanctum.middleware.encrypt_cookies', \Illuminate\Cookie\Middleware\EncryptCookies::class);
        $this->app['config']->set('sanctum.middleware.verify_csrf_token', \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        $this->app['config']->set('app.key', Str::random(32));

        $request = Request::create('/');
        $request->headers->set('referer', 'https://test.com');

        $middleware = new EnsureFrontendRequestsAreStateful();
        $handled = $middleware->handle($request, fn ($request) => new \Symfony\Component\HttpFoundation\Response(''));
        $this->assertNotEmpty($handled->headers->getCookies());
    }

    public function test_request_not_stateful_when_token_present()
    {
        $this->app['config']->set('sanctum.stateful', ['test.com']);
        $this->app['config']->set('sanctum.middleware.encrypt_cookies', \Illuminate\Cookie\Middleware\EncryptCookies::class);
        $this->app['config']->set('sanctum.middleware.verify_csrf_token', \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        $this->app['config']->set('app.key', Str::random(32));

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer foobar');
        $request->headers->set('referer', 'https://test.com');

        Auth::shouldReceive('guard')->andReturnSelf();
        Auth::shouldReceive('user')->andReturn(true);

        $middleware = new EnsureFrontendRequestsAreStateful();
        $handled = $middleware->handle($request, fn ($request) => new \Symfony\Component\HttpFoundation\Response(''));
        $this->assertEmpty($handled->headers->getCookies());
    }
}
