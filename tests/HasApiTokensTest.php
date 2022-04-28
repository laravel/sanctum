<?php

namespace Laravel\Sanctum\Tests;

use Illuminate\Foundation\Auth\User;
use Laravel\Sanctum\Contracts\HasApiTokens as HasApiTokensContract;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\TransientToken;
use Laravel\Sanctum\SanctumServiceProvider;
use Orchestra\Testbench\TestCase;

class HasApiTokensTest extends TestCase
{
    public function test_tokens_can_be_created()
    {
        $class = new ClassThatHasApiTokens;

        $newToken = $class->createToken('test', ['foo']);

        [$id, $token] = explode('|', $newToken->plainTextToken);

        $this->assertEquals(
            $newToken->accessToken->token,
            hash('sha256', $token)
        );

        $this->assertEquals(
            $newToken->accessToken->id,
            $id
        );
    }

    public function test_can_check_token_abilities()
    {
        $class = new ClassThatHasApiTokens;

        $class->withAccessToken(new TransientToken);

        $this->assertTrue($class->tokenCan('foo'));
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testbench');

        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [SanctumServiceProvider::class];
    }

    public function test_revoke_all_tokens_except_current_token()
    {
        $this->loadLaravelMigrations(['--database' => 'testbench']);
        $this->artisan('migrate', ['--database' => 'testbench'])->run();
        $user = UserForTest::forceCreate([
            'name' => 'Taylor Otwell',
            'email' => 'taylor@laravel.com',
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        ]);
        $currentAccessToken = PersonalAccessToken::forceCreate([
            'tokenable_id' => $user->id,
            'tokenable_type' => get_class($user),
            'name' => 'Current',
            'token' => hash('sha256', 'current'),
            'created_at' => now()->subMinutes(181),
        ]);
        $otherAccessToken = PersonalAccessToken::forceCreate([
            'tokenable_id' => $user->id,
            'tokenable_type' => get_class($user),
            'name' => 'Other',
            'token' => hash('sha256', 'other'),
            'created_at' => now()->subMinutes(179),
        ]);
        $user->withAccessToken($currentAccessToken);

        $user->revokeOtherAccessTokens();

        $this->assertEquals($user->tokens->count(), 1);
        $this->assertContains($currentAccessToken->id, $user->tokens->pluck('id')->all());
        $this->assertNotContains($otherAccessToken->id, $user->tokens->pluck('id')->all());
    }
}

class ClassThatHasApiTokens implements HasApiTokensContract
{
    use HasApiTokens;

    public function tokens()
    {
        return new class
        {
            public function create(array $attributes)
            {
                return new PersonalAccessToken($attributes);
            }
        };
    }
}

class UserForTest extends User implements HasApiTokensContract
{
    use HasApiTokens;

    protected $table = 'users';
}
