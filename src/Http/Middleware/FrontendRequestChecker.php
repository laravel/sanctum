<?php

namespace Laravel\Sanctum\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

class FrontendRequestChecker
{
    /**
     * Determine if the given request is from the first-party application frontend.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public static function isFromFrontend(Request $request): bool
    {
        $domain = self::getDomain($request);

        if (is_null($domain)) {
            return false;
        }

        $stateful = array_filter(config('sanctum.stateful', []));

        $patterns = self::getStatefulDomainPatterns($stateful, $request);

        return Str::is($patterns, $domain);
    }

    /**
     * Get the domain from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    private static function getDomain(Request $request): ?string
    {
        $domain = $request->headers->get('referer') ?: $request->headers->get('origin');

        if (is_null($domain)) {
            return null;
        }

        $domain = Str::of($domain)->after('://')->finish('/');

        return (string) $domain;
    }

    /**
     * Get the stateful domain patterns.
     *
     * @param  array  $stateful
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    private static function getStatefulDomainPatterns(array $stateful, Request $request): array
    {
        return Collection::make($stateful)->map(function ($uri) use ($request) {
            if ($uri === Sanctum::$currentRequestHostPlaceholder) {
                return trim($request->getHttpHost()).'/*';
            }

            return trim($uri).'/*';
        })->all();
    }
}
