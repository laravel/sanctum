<?php

namespace Laravel\Sanctum\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDeviceHasNotBeenLoggedOut
{
    public function __construct(protected AuthFactory $auth)
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasSession()
            && $request->user()
            && $request->session()->has($key = 'password_hash_'.$this->auth->getDefaultDriver())
            && $request->session()->get($key) !== $request->user()->getAuthPassword()) {
            $this->logout($request);

            throw new AuthenticationException('Unauthenticated.', [$this->auth->getDefaultDriver()]);
        }

        return $next($request);
    }

    protected function logout(Request $request)
    {
        $this->auth->logoutCurrentDevice();

        $request->session()->flush();
    }
}
