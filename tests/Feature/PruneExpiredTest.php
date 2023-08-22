<?php

namespace Laravel\Sanctum\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Workbench\Database\Factories\PersonalAccessTokenFactory;
use Workbench\Database\Factories\UserFactory;

class PruneExpiredTest extends TestCase
{
    use RefreshDatabase, WithWorkbench;

    protected function defineEnvironment($app)
    {
        $app['config']->set('database.default', 'testing');
    }

    public function test_can_delete_expired_tokens_with_integer_expiration()
    {
        config(['sanctum.expiration' => 60]);

        $user = UserFactory::new()->create();

        $token_1 = PersonalAccessTokenFactory::new()->for(
            $user, 'tokenable'
        )->create([
            'name' => 'Test_1',
            'token' => hash('sha256', 'test_1'),
            'created_at' => now()->subMinutes(181),
        ]);

        $token_2 = PersonalAccessTokenFactory::new()->for(
            $user, 'tokenable'
        )->create([
            'name' => 'Test_2',
            'token' => hash('sha256', 'test_2'),
            'created_at' => now()->subMinutes(179),
        ]);

        $token_3 = PersonalAccessTokenFactory::new()->for(
            $user, 'tokenable'
        )->create([
            'name' => 'Test_3',
            'token' => hash('sha256', 'test_3'),
            'created_at' => now()->subMinutes(121),
        ]);

        $this->artisan('sanctum:prune-expired --hours=2')
            ->expectsOutputToContain('Tokens expired for more than [2 hours] pruned successfully.');

        $this->assertDatabaseMissing('personal_access_tokens', ['name' => 'Test_1']);
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'Test_2']);
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'Test_3']);
    }

    public function test_cant_delete_expired_tokens_with_null_expiration()
    {
        config(['sanctum.expiration' => null]);

        $token = PersonalAccessTokenFactory::new()->for(
            UserFactory::new(), 'tokenable'
        )->create([
            'name' => 'Test',
            'token' => hash('sha256', 'test'),
            'created_at' => now()->subMinutes(70),
        ]);

        $this->artisan('sanctum:prune-expired --hours=2')
            ->expectsOutputToContain('Expiration value not specified in configuration file.');

        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'Test']);
    }

    public function test_can_delete_expired_tokens_with_expires_at_expiration()
    {
        config(['sanctum.expiration' => 60]);

        $user = UserFactory::new()->create();

        $token_1 = PersonalAccessTokenFactory::new()->for(
            $user, 'tokenable'
        )->create([
            'name' => 'Test_1',
            'token' => hash('sha256', 'test_1'),
            'expires_at' => now()->subMinutes(121),
        ]);

        $token_2 = PersonalAccessTokenFactory::new()->for(
            $user, 'tokenable'
        )->create([
            'name' => 'Test_2',
            'token' => hash('sha256', 'test_2'),
            'expires_at' => now()->subMinutes(119),
        ]);

        $token_3 = PersonalAccessTokenFactory::new()->for(
            $user, 'tokenable'
        )->create([
            'name' => 'Test_3',
            'token' => hash('sha256', 'test_3'),
            'expires_at' => null,
        ]);

        $this->artisan('sanctum:prune-expired --hours=2')
            ->expectsOutputToContain('Tokens expired for more than [2 hours] pruned successfully.');

        $this->assertDatabaseMissing('personal_access_tokens', ['name' => 'Test_1']);
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'Test_2']);
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'Test_3']);
    }
}
