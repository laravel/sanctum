<?php

namespace Laravel\Sanctum\Tests\Diagnostics;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Doctor\Facades\Doctor;
use Laravel\Doctor\Results\Status;
use Laravel\Sanctum\Diagnostics\SanctumSchemaIsReady;
use Laravel\Sanctum\Sanctum;
use Laravel\Sanctum\Tests\Diagnostics\Fixtures\CustomPersonalAccessToken;

class SanctumSchemaIsReadyTest extends DiagnosticTestCase
{
    public function test_diagnostic_is_registered_with_doctor()
    {
        $this->assertContains(SanctumSchemaIsReady::class, Doctor::registered());
    }

    public function test_diagnostic_skips_when_no_model_uses_api_tokens()
    {
        config(['auth.providers.users.model' => \Illuminate\Foundation\Auth\User::class]);

        $result = (new SanctumSchemaIsReady)->check();

        $this->assertSame(Status::Skip, $result->status);
        $this->assertSame('No authentication provider model uses Sanctum API tokens.', $result->summary);
    }

    public function test_diagnostic_passes_when_the_tokens_table_exists()
    {
        $this->createTokensTable('personal_access_tokens');

        $result = (new SanctumSchemaIsReady)->check();

        $this->assertSame(Status::Pass, $result->status);
        $this->assertSame('The [personal_access_tokens] table is available for Sanctum personal access tokens.', $result->summary);
    }

    public function test_diagnostic_fails_when_the_tokens_table_is_missing()
    {
        $result = (new SanctumSchemaIsReady)->check();

        $this->assertSame(Status::Fail, $result->status);
        $this->assertSame('The Sanctum personal access tokens table is missing.', $result->summary);
        $this->assertStringContainsString('personal_access_tokens', $result->details);
        $this->assertStringContainsString('sanctum-migrations', $result->remediation);
        $this->assertStringContainsString('php artisan migrate', $result->remediation);
    }

    public function test_diagnostic_honors_a_custom_token_model()
    {
        Sanctum::usePersonalAccessTokenModel(CustomPersonalAccessToken::class);

        $this->createTokensTable('custom_personal_access_tokens');

        $result = (new SanctumSchemaIsReady)->check();

        $this->assertSame(Status::Pass, $result->status);
        $this->assertStringContainsString('custom_personal_access_tokens', $result->summary);
    }

    public function test_diagnostic_skips_when_the_database_cannot_be_inspected()
    {
        config(['database.default' => 'missing']);

        $result = (new SanctumSchemaIsReady)->check();

        $this->assertSame(Status::Skip, $result->status);
        $this->assertSame('The database could not be inspected for the personal access tokens table.', $result->summary);
        $this->assertStringContainsString('missing', $result->details);
    }

    /**
     * Create a personal access tokens table.
     */
    private function createTokensTable(string $table): void
    {
        Schema::create($table, function (Blueprint $blueprint): void {
            $blueprint->id();
            $blueprint->timestamp('expires_at')->nullable();
        });
    }
}
