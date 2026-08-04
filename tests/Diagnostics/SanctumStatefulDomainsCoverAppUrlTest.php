<?php

namespace Laravel\Sanctum\Tests\Diagnostics;

use Illuminate\Contracts\Http\Kernel;
use Laravel\Doctor\Facades\Doctor;
use Laravel\Doctor\Results\Status;
use Laravel\Sanctum\Diagnostics\SanctumStatefulDomainsCoverAppUrl;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Laravel\Sanctum\Sanctum;

class SanctumStatefulDomainsCoverAppUrlTest extends DiagnosticTestCase
{
    public function test_diagnostic_is_registered_with_doctor()
    {
        $this->assertContains(SanctumStatefulDomainsCoverAppUrl::class, Doctor::registered());
    }

    public function test_diagnostic_skips_when_the_stateful_middleware_is_not_enabled()
    {
        $result = (new SanctumStatefulDomainsCoverAppUrl)->check();

        $this->assertSame(Status::Skip, $result->status);
        $this->assertSame('The stateful API middleware is not enabled, so stateful domains were not checked.', $result->summary);
    }

    public function test_diagnostic_passes_when_the_default_domains_cover_the_app_url()
    {
        $this->enableStatefulApi();

        $result = (new SanctumStatefulDomainsCoverAppUrl)->check();

        $this->assertSame(Status::Pass, $result->status);
        $this->assertSame('The Sanctum stateful domains include the frontend host [localhost].', $result->summary);
    }

    public function test_diagnostic_sees_stateful_middleware_registered_through_the_application_bootstrap()
    {
        // Middleware from bootstrap/app.php lives on the kernel and is not synced
        // to the router in an artisan process.
        app(Kernel::class)->setMiddlewareGroups(['api' => [EnsureFrontendRequestsAreStateful::class]]);
        $this->app['router']->middlewareGroup('api', []);

        $result = (new SanctumStatefulDomainsCoverAppUrl)->check();

        $this->assertSame(Status::Pass, $result->status);
    }

    public function test_diagnostic_fails_when_the_app_url_is_not_covered()
    {
        $this->enableStatefulApi();

        config([
            'app.url' => 'https://example.com',
            'sanctum.stateful' => ['localhost', '127.0.0.1'],
        ]);

        $result = (new SanctumStatefulDomainsCoverAppUrl)->check();

        $this->assertSame(Status::Fail, $result->status);
        $this->assertSame('The Sanctum stateful domains do not include the frontend host [example.com].', $result->summary);
        $this->assertStringContainsString('localhost', $result->details);
        $this->assertStringContainsString('SANCTUM_STATEFUL_DOMAINS', $result->remediation);
    }

    public function test_diagnostic_prefers_the_frontend_url_over_the_app_url()
    {
        $this->enableStatefulApi();

        config([
            'app.url' => 'https://api.example.com',
            'app.frontend_url' => 'https://app.example.com',
            'sanctum.stateful' => ['app.example.com'],
        ]);

        $result = (new SanctumStatefulDomainsCoverAppUrl)->check();

        $this->assertSame(Status::Pass, $result->status);
        $this->assertSame('The Sanctum stateful domains include the frontend host [app.example.com].', $result->summary);
    }

    public function test_diagnostic_matches_ports_the_way_the_middleware_does()
    {
        $this->enableStatefulApi();

        config([
            'app.frontend_url' => 'http://localhost:5173',
            'sanctum.stateful' => ['localhost'],
        ]);

        $result = (new SanctumStatefulDomainsCoverAppUrl)->check();

        $this->assertSame(Status::Fail, $result->status);
        $this->assertSame('The Sanctum stateful domains do not include the frontend host [localhost:5173].', $result->summary);
    }

    public function test_diagnostic_supports_wildcard_domains()
    {
        $this->enableStatefulApi();

        config([
            'app.url' => 'https://tenant.example.com',
            'sanctum.stateful' => ['*.example.com'],
        ]);

        $result = (new SanctumStatefulDomainsCoverAppUrl)->check();

        $this->assertSame(Status::Pass, $result->status);
    }

    public function test_diagnostic_resolves_the_current_request_host_placeholder_to_the_app_host()
    {
        $this->enableStatefulApi();

        config([
            'app.url' => 'https://example.com',
            'sanctum.stateful' => [Sanctum::$currentRequestHostPlaceholder],
        ]);

        $result = (new SanctumStatefulDomainsCoverAppUrl)->check();

        $this->assertSame(Status::Pass, $result->status);
    }

    public function test_diagnostic_fails_when_no_domains_are_configured()
    {
        $this->enableStatefulApi();

        config(['sanctum.stateful' => []]);

        $result = (new SanctumStatefulDomainsCoverAppUrl)->check();

        $this->assertSame(Status::Fail, $result->status);
        $this->assertSame('No Sanctum stateful domains are configured.', $result->summary);
    }

    /**
     * Register the stateful API middleware like Laravel's statefulApi() does.
     */
    private function enableStatefulApi(): void
    {
        $this->app['router']->middlewareGroup('api', [EnsureFrontendRequestsAreStateful::class]);
    }
}
