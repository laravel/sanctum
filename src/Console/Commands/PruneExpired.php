<?php

namespace Laravel\Sanctum\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Sanctum\Sanctum;

class PruneExpired extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sanctum:prune-expired {--hours=24 : The number of hours to retain expired Sanctum tokens}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune tokens expired for more than specified number of hours.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ($expiration = config('sanctum.expiration')) {
            $model = Sanctum::$personalAccessTokenModel;

            $hours = $this->option('hours');

            $model::where('created_at', '<', now()->subMinutes($expiration + ($hours * 60)))->delete();

            $this->info("Tokens expired for more than {$hours} hours pruned successfully.");

            return 0;
        }

        $this->warn('Expiration value not specified in configuration file.');

        return 1;
    }
}
