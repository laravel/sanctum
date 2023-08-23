<?php

namespace Laravel\Sanctum\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
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
            $guards = Collection::make(Arr::wrap(config('sanctum.guard')))
                ->mapWithKeys(fn ($guard) => [$guard => $this->auth->guard($guard)])
                ->filter(fn ($guard) => $guard instanceof SessionGuard);

            $shouldLoggedOut = $guards->filter(fn ($guard, $driver) => $request->session()->has('password_hash_'.$driver))
                ->filter(fn ($quard, $driver) => $request->session()->get('password_hash_'.$driver) !== $request->user()->getAuthPassword());

            if ($shouldLoggedOut->isNotEmpty()) {
                $shouldLoggedOut->each(fn ($guard) => $this->logout($request, $guard));

                throw new AuthenticationException('Unauthenticated.', [...$shouldLoggedOut->keys()->all(), 'sanctum']);
            }

            $this->storePasswordHashInSession($request, $guards->keys()->first());
        }

        return $next($request);
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\SessionGuard  $guard
     * @return void
     */
    protected function logout(Request $request, SessionGuard $guard)
    {
        $guard->logoutCurrentDevice();

        $request->session()->flush();
    }

    /**
     * Store the user's current password hash in the session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $guard
     * @return void
     */
    protected function storePasswordHashInSession($request, string $guard)
    {
        if (! $request->user()) {
            return;
        }

        $request->session()->put([
            "password_hash_{$guard}" => $request->user()->getAuthPassword(),
        ]);
    }
}
