<?php

namespace Laravel\Sanctum\Tests\Diagnostics;

use Laravel\Doctor\Facades\Doctor;
use Laravel\Doctor\Results\Status;
use Laravel\Sanctum\Diagnostics\SanctumSessionDomainCoversFrontend;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

class SanctumSessionDomainCoversFrontendTest extends DiagnosticTestCase
{
    public function test_diagnostic_is_registered_with_doctor()
    {
        $this->assertContains(SanctumSessionDomainCoversFrontend::class, Doctor::registered());
    }

    public function test_diagnostic_skips_when_the_stateful_middleware_is_not_enabled()
    {
        $result = (new SanctumSessionDomainCoversFrontend)->check();

        $this->assertSame(Status::Skip, $result->status);
        $this->assertSame('The stateful API middleware is not enabled, so the session cookie domain was not checked.', $result->summary);
    }

    public function test_diagnostic_passes_when_the_frontend_and_api_share_a_host()
    {
        $this->enableStatefulApi();

        $result = (new SanctumSessionDomainCoversFrontend)->check();

        $this->assertSame(Status::Pass, $result->status);
        $this->assertSame('The frontend and API share the [localhost] host, so no session cookie domain is required.', $result->summary);
    }

    public function test_diagnostic_ignores_ports_when_comparing_hosts()
    {
        $this->enableStatefulApi();

        config([
            'app.url' => 'http://localhost',
            'app.frontend_url' => 'http://localhost:5173',
        ]);

        $result = (new SanctumSessionDomainCoversFrontend)->check();

        $this->assertSame(Status::Pass, $result->status);
        $this->assertSame('The frontend and API share the [localhost] host, so no session cookie domain is required.', $result->summary);
    }

    public function test_diagnostic_fails_when_the_session_domain_is_not_set_for_cross_subdomain_hosts()
    {
        $this->enableStatefulApi();

        config([
            'app.url' => 'https://api.example.com',
            'app.frontend_url' => 'https://app.example.com',
        ]);

        $result = (new SanctumSessionDomainCoversFrontend)->check();

        $this->assertSame(Status::Fail, $result->status);
        $this->assertSame('The session cookie domain is not set, but the frontend [app.example.com] and API [api.example.com] are on different hosts.', $result->summary);
        $this->assertStringContainsString('[.example.com]', $result->remediation);
    }

    public function test_diagnostic_passes_when_the_session_domain_covers_both_hosts()
    {
        $this->enableStatefulApi();

        config([
            'app.url' => 'https://api.example.com',
            'app.frontend_url' => 'https://app.example.com',
            'session.domain' => '.example.com',
        ]);

        $result = (new SanctumSessionDomainCoversFrontend)->check();

        $this->assertSame(Status::Pass, $result->status);
        $this->assertSame('The session cookie domain [.example.com] covers the frontend and the API.', $result->summary);
    }

    public function test_diagnostic_fails_when_the_session_domain_covers_only_one_host()
    {
        $this->enableStatefulApi();

        config([
            'app.url' => 'https://api.example.com',
            'app.frontend_url' => 'https://app.example.com',
            'session.domain' => 'api.example.com',
        ]);

        $result = (new SanctumSessionDomainCoversFrontend)->check();

        $this->assertSame(Status::Fail, $result->status);
        $this->assertSame('The session cookie domain [api.example.com] does not cover both the frontend [app.example.com] and the API [api.example.com].', $result->summary);
        $this->assertStringContainsString('[.example.com]', $result->remediation);
    }

    public function test_diagnostic_fails_when_the_hosts_share_no_parent_domain()
    {
        $this->enableStatefulApi();

        config([
            'app.url' => 'https://api.example.com',
            'app.frontend_url' => 'https://app.another.dev',
        ]);

        $result = (new SanctumSessionDomainCoversFrontend)->check();

        $this->assertSame(Status::Fail, $result->status);
        $this->assertSame('The frontend [app.another.dev] and API [api.example.com] do not share a parent domain, so session cookies cannot be shared.', $result->summary);
        $this->assertStringContainsString('API tokens', $result->remediation);
    }

    /**
     * Register the stateful API middleware like Laravel's statefulApi() does.
     */
    private function enableStatefulApi(): void
    {
        $this->app['router']->middlewareGroup('api', [EnsureFrontendRequestsAreStateful::class]);
    }
}
