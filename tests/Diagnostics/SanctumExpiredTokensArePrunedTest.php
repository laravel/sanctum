<?php

namespace Laravel\Sanctum\Tests\Diagnostics;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Doctor\Facades\Doctor;
use Laravel\Doctor\Results\Status;
use Laravel\Sanctum\Diagnostics\SanctumExpiredTokensArePruned;
use Laravel\Sanctum\PersonalAccessToken;

class SanctumExpiredTokensArePrunedTest extends DiagnosticTestCase
{
    public function test_diagnostic_is_registered_with_doctor()
    {
        $this->assertContains(SanctumExpiredTokensArePruned::class, Doctor::registered());
    }

    public function test_diagnostic_skips_when_no_model_uses_api_tokens()
    {
        config(['auth.providers.users.model' => \Illuminate\Foundation\Auth\User::class]);

        $result = (new SanctumExpiredTokensArePruned)->check();

        $this->assertSame(Status::Skip, $result->status);
        $this->assertSame('No authentication provider model uses Sanctum API tokens.', $result->summary);
    }

    public function test_diagnostic_passes_when_pruning_is_scheduled()
    {
        config(['sanctum.expiration' => 60]);

        $schedule = new Schedule;
        $schedule->command('sanctum:prune-expired')->daily();

        $this->app->instance(Schedule::class, $schedule);

        $result = (new SanctumExpiredTokensArePruned)->check();

        $this->assertSame(Status::Pass, $result->status);
        $this->assertSame('Expired Sanctum token pruning is scheduled.', $result->summary);
    }

    public function test_diagnostic_passes_when_tokens_never_expire()
    {
        $this->createTokensTable();

        $result = (new SanctumExpiredTokensArePruned)->check();

        $this->assertSame(Status::Pass, $result->status);
        $this->assertSame('Sanctum tokens are not configured to expire, so pruning is not required.', $result->summary);
    }

    public function test_diagnostic_warns_when_pruning_is_not_scheduled_in_production()
    {
        config(['sanctum.expiration' => 60]);

        $result = (new SanctumExpiredTokensArePruned)->check();

        $this->assertSame(Status::Warn, $result->status);
        $this->assertSame('Expired Sanctum tokens are not pruned.', $result->summary);
        $this->assertStringContainsString('sanctum:prune-expired', $result->remediation);
    }

    public function test_diagnostic_notices_when_pruning_is_not_scheduled_outside_production()
    {
        config([
            'sanctum.expiration' => 60,
            'doctor.environments' => ['local' => ['testing']],
        ]);

        $result = (new SanctumExpiredTokensArePruned)->check();

        $this->assertSame(Status::Notice, $result->status);
        $this->assertSame('Expired Sanctum tokens are not pruned.', $result->summary);
    }

    public function test_diagnostic_detects_expiring_tokens_from_the_database()
    {
        $this->createTokensTable();

        PersonalAccessToken::forceCreate([
            'tokenable_type' => 'user',
            'tokenable_id' => 1,
            'name' => 'test',
            'token' => hash('sha256', 'token'),
            'abilities' => ['*'],
            'expires_at' => now()->addDay(),
        ]);

        $result = (new SanctumExpiredTokensArePruned)->check();

        $this->assertSame(Status::Warn, $result->status);
        $this->assertSame('Expired Sanctum tokens are not pruned.', $result->summary);
    }

    public function test_diagnostic_skips_when_the_database_cannot_be_inspected()
    {
        $result = (new SanctumExpiredTokensArePruned)->check();

        $this->assertSame(Status::Skip, $result->status);
        $this->assertSame('The database could not be inspected for expiring Sanctum tokens.', $result->summary);
    }

    /**
     * Create the personal access tokens table.
     */
    private function createTokensTable(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $blueprint): void {
            $blueprint->id();
            $blueprint->string('tokenable_type');
            $blueprint->unsignedBigInteger('tokenable_id');
            $blueprint->string('name');
            $blueprint->string('token');
            $blueprint->text('abilities')->nullable();
            $blueprint->timestamp('expires_at')->nullable();
            $blueprint->timestamps();
        });
    }
}
