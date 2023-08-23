<?php

namespace Laravel\Sanctum\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

class EnsureDeviceHasNotBeenLoggedOut
{
    /**
     * The authentication factory implementation.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(AuthFactory $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasSession()) {
            if ($request->session()->has($key = 'password_hash_'.$this->auth->getDefaultDriver())) {
                if ($request->session()->get($key) !== $request->user()->getAuthPassword()) {
                    $this->logout($request);
                }
            } else {
                $this->storePasswordHashInSession($request);
            }
        }

        return tap($next($request), fn () => $this->storePasswordHashInSession($request));
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    protected function logout(Request $request)
    {
        foreach (Arr::wrap(config('sanctum.guard')) as $guard) {
            tap($this->auth->guard($guard), function ($guard) {
                if ($guard instanceof SessionGuard) {
                    $guard->logoutCurrentDevice();
                }
            });
        }

        $request->session()->flush();

        throw new AuthenticationException('Unauthenticated.', [$this->auth->getDefaultDriver()]);
    }

    /**
     * Store the user's current password hash in the session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function storePasswordHashInSession($request)
    {
        if (! $request->user()) {
            return;
        }

        $request->session()->put([
            'password_hash_'.$this->auth->getDefaultDriver() => $request->user()->getAuthPassword(),
        ]);
    }
}
