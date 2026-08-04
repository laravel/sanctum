<?php

namespace Laravel\Sanctum\Diagnostics;

use Illuminate\Support\Str;
use Laravel\Doctor\Diagnostic;
use Laravel\Doctor\Results\DiagnosticResult;
use Laravel\Doctor\Results\Link;
use Laravel\Doctor\Results\Message;
use Laravel\Doctor\Support\Configured;

class SanctumSessionDomainCoversFrontend extends Diagnostic
{
    use ResolvesStatefulFrontend;

    public string $name = 'Sanctum session cookies reach the frontend';

    public string $group = 'sanctum';

    /**
     * Get the diagnostic's named message definitions.
     *
     * @return array<string, string|Message>
     */
    protected function messages(): array
    {
        return [
            'not-stateful' => 'The stateful API middleware is not enabled, so the session cookie domain was not checked.',
            'no-app-url' => 'The application URL is not configured, so the session cookie domain was not checked.',
            'same-host' => 'The frontend and API share the [{host}] host, so no session cookie domain is required.',
            'no-shared-domain' => Message::make(
                summary: 'The frontend [{frontend}] and API [{app}] do not share a parent domain, so session cookies cannot be shared.',
                remediation: 'Serve the frontend and API from a common parent domain, or authenticate with API tokens instead.',
            )->link(Link::docs('sanctum', 'spa-authentication')),
            'not-set' => Message::make(
                summary: 'The session cookie domain is not set, but the frontend [{frontend}] and API [{app}] are on different hosts.',
                remediation: 'Set SESSION_DOMAIN to [{suggested}] so authentication cookies are shared between the frontend and the API.',
            )->link(Link::docs('sanctum', 'spa-authentication')),
            'not-covered' => Message::make(
                summary: 'The session cookie domain [{domain}] does not cover both the frontend [{frontend}] and the API [{app}].',
                remediation: 'Set SESSION_DOMAIN to [{suggested}] so authentication cookies are shared between the frontend and the API.',
            )->link(Link::docs('sanctum', 'spa-authentication')),
            'covered' => 'The session cookie domain [{domain}] covers the frontend and the API.',
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

        $frontend = $this->frontendHost();
        $app = $this->applicationHost();

        if ($frontend === null || $app === null) {
            return $this->skip('no-app-url');
        }

        if (strcasecmp($frontend, $app) === 0) {
            return $this->pass('same-host', ['host' => $app]);
        }

        $suggested = $this->sharedParentDomain($frontend, $app);

        if ($suggested === null) {
            return $this->fail('no-shared-domain', ['frontend' => $frontend, 'app' => $app]);
        }

        $domain = Configured::string('session.domain');

        if ($domain === null) {
            return $this->fail('not-set', [
                'frontend' => $frontend,
                'app' => $app,
                'suggested' => $suggested,
            ]);
        }

        if (! $this->covers($domain, $frontend) || ! $this->covers($domain, $app)) {
            return $this->fail('not-covered', [
                'domain' => $domain,
                'frontend' => $frontend,
                'app' => $app,
                'suggested' => $suggested,
            ]);
        }

        return $this->pass('covered', ['domain' => $domain]);
    }

    /**
     * Determine whether a cookie domain covers a host.
     */
    private function covers(string $domain, string $host): bool
    {
        $domain = strtolower(ltrim($domain, '.'));
        $host = strtolower($host);

        return $host === $domain || Str::endsWith($host, '.'.$domain);
    }

    /**
     * Get the parent domain shared by two hosts, if any.
     */
    private function sharedParentDomain(string $first, string $second): ?string
    {
        if (filter_var($first, FILTER_VALIDATE_IP) || filter_var($second, FILTER_VALIDATE_IP)) {
            return null;
        }

        $first = array_reverse(explode('.', strtolower($first)));
        $second = array_reverse(explode('.', strtolower($second)));

        $shared = [];

        foreach ($first as $index => $label) {
            if (($second[$index] ?? null) !== $label) {
                break;
            }

            $shared[] = $label;
        }

        return count($shared) >= 2 ? '.'.implode('.', array_reverse($shared)) : null;
    }
}
