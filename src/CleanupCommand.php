<?php


namespace Laravel\Sanctum;

use Illuminate\Console\Command;

class CleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sanctum:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete expired tokens';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        PersonalAccessToken::where('last_used_at', '<', now()->subMinutes(config('sanctum.expiration')))->delete();
        $this->info('Cleaning up done successfully.');
    }
}
