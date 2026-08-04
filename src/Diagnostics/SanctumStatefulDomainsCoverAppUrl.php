<?php

namespace Laravel\Sanctum\Diagnostics;

use Illuminate\Support\Str;
use Laravel\Doctor\Diagnostic;
use Laravel\Doctor\Results\DiagnosticResult;
use Laravel\Doctor\Results\Link;
use Laravel\Doctor\Results\Message;
use Laravel\Doctor\Support\Details;
use Laravel\Sanctum\Sanctum;

class SanctumStatefulDomainsCoverAppUrl extends Diagnostic
{
    use ResolvesStatefulFrontend;

    public string $name = 'Sanctum stateful domains cover the frontend';

    public string $group = 'sanctum';

    /**
     * Get the diagnostic's named message definitions.
     *
     * @return array<string, string|Message>
     */
    protected function messages(): array
    {
        return [
            'not-stateful' => 'The stateful API middleware is not enabled, so stateful domains were not checked.',
            'no-app-url' => 'The application URL is not configured, so stateful domains were not checked.',
            'no-domains' => Message::make(
                summary: 'No Sanctum stateful domains are configured.',
                remediation: 'Set SANCTUM_STATEFUL_DOMAINS to the hosts the frontend is served from so its requests receive session cookies.',
            )->link(Link::docs('sanctum', 'spa-authentication')),
            'not-covered' => Message::make(
                summary: 'The Sanctum stateful domains do not include the frontend host [{host}].',
                remediation: 'Add [{host}] to SANCTUM_STATEFUL_DOMAINS so frontend requests receive session cookies.',
            )->link(Link::docs('sanctum', 'spa-authentication')),
            'covered' => 'The Sanctum stateful domains include the frontend host [{host}].',
        ];
    }

    /**
     * Run the diagnostic.
     */
    public function check(): DiagnosticResult
    {
        if (! $this->statefulApiIsEnabled()) {
            return $this->skip('not-stateful');
        }

        $host = $this->frontendHost(withPort: true);

        if ($host === null) {
            return $this->skip('no-app-url');
        }

        $stateful = config('sanctum.stateful');
        $domains = is_array($stateful) ? array_filter($stateful) : [];

        if ($domains === []) {
            return $this->fail('no-domains');
        }

        if (! Str::is($this->statefulPatterns($domains), $host.'/')) {
            return $this->fail('not-covered', ['host' => $host])
                ->withDetails(Details::bullets($this->configuredDomains($domains)));
        }

        return $this->pass('covered', ['host' => $host]);
    }

    /**
     * Build the wildcard patterns the stateful middleware matches frontend requests against.
     *
     * Mirrors EnsureFrontendRequestsAreStateful::fromFrontend(), with the
     * current-request-host placeholder resolved to the application host —
     * the host requests hit when the frontend and API share an origin.
     *
     * @param  array<array-key, mixed>  $domains
     * @return list<string>
     */
    private function statefulPatterns(array $domains): array
    {
        return collect($domains)
            ->map(fn ($domain) => $domain === Sanctum::$currentRequestHostPlaceholder
                ? $this->applicationHost(withPort: true)
                : $domain)
            ->filter(fn ($domain): bool => is_string($domain) && trim($domain) !== '')
            ->map(fn (string $domain): string => trim($domain).'/*')
            ->values()
            ->all();
    }

    /**
     * Get the configured stateful domains as displayable strings.
     *
     * @param  array<array-key, mixed>  $domains
     * @return list<string>
     */
    private function configuredDomains(array $domains): array
    {
        return collect($domains)
            ->filter(fn ($domain): bool => is_string($domain) && trim($domain) !== '')
            ->map(fn (string $domain): string => trim($domain))
            ->values()
            ->all();
    }
}
