<?php

namespace Laravel\Sanctum\Tests;

use Illuminate\Foundation\Auth\User;
use Laravel\Sanctum\Contracts\HasApiTokens as HasApiTokensContract;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\SanctumServiceProvider;
use Orchestra\Testbench\TestCase;

class PruneExpiredTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testbench');

        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    public function test_can_delete_expired_tokens_with_integer_expiration()
    {
        $this->loadLaravelMigrations(['--database' => 'testbench']);
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        config(['sanctum.expiration' => 60]);

        $user = UserForPruneExpiredTest::forceCreate([
            'name' => 'Taylor Otwell',
            'email' => 'taylor@laravel.com',
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        ]);

        $token_1 = PersonalAccessToken::forceCreate([
            'tokenable_id' => $user->id,
            'tokenable_type' => get_class($user),
            'name' => 'Test_1',
            'token' => hash('sha256', 'test_1'),
            'created_at' => now()->subMinutes(181),
        ]);

        $token_2 = PersonalAccessToken::forceCreate([
            'tokenable_id' => $user->id,
            'tokenable_type' => get_class($user),
            'name' => 'Test_2',
            'token' => hash('sha256', 'test_2'),
            'created_at' => now()->subMinutes(179),
        ]);

        $token_3 = PersonalAccessToken::forceCreate([
            'tokenable_id' => $user->id,
            'tokenable_type' => get_class($user),
            'name' => 'Test_3',
            'token' => hash('sha256', 'test_3'),
            'created_at' => now()->subMinutes(121),
        ]);

        $this->artisan('sanctum:prune-expired --hours=2')
            ->expectsOutput('Tokens expired for more than 2 hours pruned successfully.');

        $this->assertDatabaseMissing('personal_access_tokens', ['name' => 'Test_1']);
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'Test_2']);
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'Test_3']);
    }

    public function test_cant_delete_expired_tokens_with_null_expiration()
    {
        $this->loadLaravelMigrations(['--database' => 'testbench']);
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        config(['sanctum.expiration' => null]);

        $user = UserForPruneExpiredTest::forceCreate([
            'name' => 'Taylor Otwell',
            'email' => 'taylor@laravel.com',
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        ]);

        $token = PersonalAccessToken::forceCreate([
            'tokenable_id' => $user->id,
            'tokenable_type' => get_class($user),
            'name' => 'Test',
            'token' => hash('sha256', 'test'),
            'created_at' => now()->subMinutes(70),
        ]);

        $this->artisan('sanctum:prune-expired --hours=2')
            ->expectsOutput('Expiration value not specified in configuration file.');

        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'Test']);
    }

    protected function getPackageProviders($app)
    {
        return [SanctumServiceProvider::class];
    }
}

class UserForPruneExpiredTest extends User implements HasApiTokensContract
{
    use HasApiTokens;

    protected $table = 'users';
}
