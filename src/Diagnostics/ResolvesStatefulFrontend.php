<?php

namespace Laravel\Sanctum\Diagnostics;

use Illuminate\Contracts\Http\Kernel;
use Laravel\Doctor\Support\Configured;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

trait ResolvesStatefulFrontend
{
    /**
     * Determine whether the stateful API middleware is enabled.
     */
    protected function statefulApiIsEnabled(): bool
    {
        // Middleware registered in bootstrap/app.php lives on the HTTP kernel and is
        // only synced to the router once the kernel is resolved, which never happens
        // in an artisan process, so both sources must be consulted.
        $groups = app('router')->getMiddlewareGroups();
        $kernel = app(Kernel::class);

        if (method_exists($kernel, 'getMiddlewareGroups')) {
            $groups = array_merge_recursive($groups, $kernel->getMiddlewareGroups());
        }

        foreach ($groups as $middleware) {
            if (in_array(EnsureFrontendRequestsAreStateful::class, $middleware, true)) {
                return true;
            }
        }

        return method_exists($kernel, 'getGlobalMiddleware')
            && in_array(EnsureFrontendRequestsAreStateful::class, $kernel->getGlobalMiddleware(), true);
    }

    /**
     * Get the host browsers send frontend requests from.
     *
     * Browsers send the frontend's origin as the referer, so a configured
     * frontend URL supersedes the application URL, which may be an API
     * host that never serves the frontend itself.
     */
    protected function frontendHost(bool $withPort = false): ?string
    {
        foreach (['app.frontend_url', 'app.url'] as $key) {
            $host = $this->urlHost(Configured::string($key), $withPort);

            if ($host !== null) {
                return $host;
            }
        }

        return null;
    }

    /**
     * Get the host the application is served from.
     */
    protected function applicationHost(bool $withPort = false): ?string
    {
        return $this->urlHost(Configured::string('app.url'), $withPort);
    }

    /**
     * Extract the host from a URL.
     */
    private function urlHost(?string $url, bool $withPort): ?string
    {
        $host = $url === null ? null : parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        $port = $url === null ? null : parse_url($url, PHP_URL_PORT);

        return $withPort && is_int($port) ? "{$host}:{$port}" : $host;
    }
}
