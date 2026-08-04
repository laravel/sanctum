<?php

namespace Laravel\Sanctum\Diagnostics;

use Laravel\Doctor\Diagnostic;
use Laravel\Doctor\Results\DiagnosticResult;
use Laravel\Doctor\Results\Link;
use Laravel\Doctor\Results\Message;
use Laravel\Sanctum\Sanctum;
use Throwable;

class SanctumSchemaIsReady extends Diagnostic
{
    use FindsApiTokenModels;

    public string $name = 'Sanctum database schema is ready';

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
            'not-ready' => Message::make(
                summary: 'The Sanctum personal access tokens table is missing.',
                remediation: 'Run `php artisan vendor:publish --tag=sanctum-migrations` and `php artisan migrate` to create the table.',
            )->link(Link::docs('sanctum', 'api-token-authentication')),
            'cannot-inspect' => 'The database could not be inspected for the personal access tokens table.',
            'ready' => 'The [{table}] table is available for Sanctum personal access tokens.',
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

        $model = new (Sanctum::personalAccessTokenModel());

        try {
            $connection = $model->getConnection();
            $table = $model->getTable();

            $exists = $connection->getSchemaBuilder()->hasTable($table);
        } catch (Throwable $e) {
            return $this->skip('cannot-inspect')->withDetails($e->getMessage());
        }

        if (! $exists) {
            return $this->fail('not-ready')
                ->withDetails("The [{$connection->getName()}.{$table}] table does not exist.");
        }

        return $this->pass('ready', ['table' => $table]);
    }
}
