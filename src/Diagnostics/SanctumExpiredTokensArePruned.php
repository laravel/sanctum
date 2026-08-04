<?php

namespace Laravel\Sanctum\Diagnostics;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Doctor\Diagnostic;
use Laravel\Doctor\EnvironmentMode;
use Laravel\Doctor\Results\DiagnosticResult;
use Laravel\Doctor\Results\Link;
use Laravel\Doctor\Results\Message;
use Laravel\Sanctum\Sanctum;
use Throwable;

class SanctumExpiredTokensArePruned extends Diagnostic
{
    use FindsApiTokenModels;

    public string $name = 'Expired Sanctum tokens are pruned';

    public string $group = 'sanctum';

    /**
     * Get the diagnostic's named message definitions.
     *
     * @return array<string, string|Message>
     */
    protected function messages(): array
    {
        return [
            'not-used' => 'No authentication provider model uses Sanctum API tokens.',
            'scheduled' => 'Expired Sanctum token pruning is scheduled.',
            'no-expiration' => 'Sanctum tokens are not configured to expire, so pruning is not required.',
            'cannot-inspect' => 'The database could not be inspected for expiring Sanctum tokens.',
            'not-scheduled' => Message::make(
                summary: 'Expired Sanctum tokens are not pruned.',
                remediation: 'Schedule `sanctum:prune-expired` daily so the personal access tokens table does not grow unbounded.',
            )->link(Link::docs('sanctum', 'token-expiration')),
        ];
    }

    /**
     * Run the diagnostic.
     */
    public function check(): DiagnosticResult
    {
        if ($this->apiTokenModels() === []) {
            return $this->skip('not-used');
        }

        if ($this->pruneIsScheduled()) {
            return $this->pass('scheduled');
        }

        $expiring = $this->tokensExpire();

        if ($expiring === null) {
            return $this->skip('cannot-inspect');
        }

        if ($expiring === false) {
            return $this->pass('no-expiration');
        }

        return EnvironmentMode::current()->isProduction()
            ? $this->warn('not-scheduled')
            : $this->notice('not-scheduled');
    }

    /**
     * Determine whether token pruning is on the application's schedule.
     */
    private function pruneIsScheduled(): bool
    {
        foreach (app(Schedule::class)->events() as $event) {
            if (str_contains((string) $event->command, 'sanctum:prune-expired')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether any issued tokens expire, or null when the database cannot be inspected.
     *
     * Tokens expire through the global expiration setting or through
     * per-token expires_at values passed when the token was created.
     */
    private function tokensExpire(): ?bool
    {
        if (is_numeric(config('sanctum.expiration'))) {
            return true;
        }

        try {
            return Sanctum::personalAccessTokenModel()::query()->whereNotNull('expires_at')->exists();
        } catch (Throwable) {
            return null;
        }
    }
}
