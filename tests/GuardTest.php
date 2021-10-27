<?php

namespace Laravel\Sanctum\Tests;

use DateTimeInterface;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Sanctum\Contracts\HasApiTokens as HasApiTokensContract;
use Laravel\Sanctum\Guard;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Laravel\Sanctum\SanctumServiceProvider;
use Mockery;
use Orchestra\Testbench\TestCase;
use stdClass;

class GuardTest extends TestCase
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

    public function tearDown(): void
    {
        parent::tearDown();

        Mockery::close();
    }

    public function test_authentication_is_attempted_with_web_middleware()
    {
        $factory = Mockery::mock(AuthFactory::class);

        $guard = new Guard('api', $factory, null, 'users');

        $webGuard = Mockery::mock(stdClass::class);

        $factory->shouldReceive('guard')
                ->with('web')
                ->andReturn($webGuard);

        $webGuard->shouldReceive('user')->once()->andReturn($fakeUser = new User);

        $user = $guard->__invoke(Request::create('/', 'GET'));

        $this->assertSame($user, $fakeUser);
        $this->assertTrue($user->tokenCan('foo'));
    }

    public function test_authentication_is_attempted_with_token_if_no_session_present()
    {
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        $factory = Mockery::mock(AuthFactory::class);

        $guard = new Guard('api', $factory, null, 'users');

        $webGuard = Mockery::mock(stdClass::class);

        $factory->shouldReceive('guard')
                ->with('web')
                ->andReturn($webGuard);

        $webGuard->shouldReceive('user')->once()->andReturn(null);

        $request = Request::create('/', 'GET');
        $request->headers->set('Authorization', 'Bearer test');

        $user = $guard->__invoke($request);

        $this->assertNull($user);
    }

    public function test_authentication_with_token_fails_if_expired()
    {
        $this->loadLaravelMigrations(['--database' => 'testbench']);
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        $factory = Mockery::mock(AuthFactory::class);

        $guard = new Guard('api', $factory, 1, 'users');

        $webGuard = Mockery::mock(stdClass::class);

        $factory->shouldReceive('guard')
                ->with('web')
                ->andReturn($webGuard);

        $webGuard->shouldReceive('user')->once()->andReturn(null);

        $request = Request::create('/', 'GET');
        $request->headers->set('Authorization', 'Bearer test');

        $user = User::forceCreate([
            'name' => 'Taylor Otwell',
            'email' => 'taylor@laravel.com',
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'remember_token' => Str::random(10),
        ]);

        $token = PersonalAccessToken::forceCreate([
            'tokenable_id' => $user->id,
            'tokenable_type' => get_class($user),
            'name' => 'Test',
            'token' => hash('sha256', 'test'),
            'created_at' => now()->subMinutes(60),
        ]);

        $user = $guard->__invoke($request);

        $this->assertNull($user);
    }

    public function test_authentication_is_successful_with_token_if_no_session_present()
    {
        $this->loadLaravelMigrations(['--database' => 'testbench']);
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        $factory = Mockery::mock(AuthFactory::class);

        $guard = new Guard('api', $factory, null);
        $guard->setDispatcher($events = Mockery::mock(Dispatcher::class));

        $webGuard = Mockery::mock(stdClass::class);

        $factory->shouldReceive('guard')
                ->with('web')
                ->andReturn($webGuard);

        $webGuard->shouldReceive('user')->once()->andReturn(null);

        $events->shouldReceive('dispatch')->once()->with(Mockery::type(Authenticated::class));

        $request = Request::create('/', 'GET');
        $request->headers->set('Authorization', 'Bearer test');

        $user = User::forceCreate([
            'name' => 'Taylor Otwell',
            'email' => 'taylor@laravel.com',
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'remember_token' => Str::random(10),
        ]);

        $token = PersonalAccessToken::forceCreate([
            'tokenable_id' => $user->id,
            'tokenable_type' => get_class($user),
            'name' => 'Test',
            'token' => hash('sha256', 'test'),
        ]);

        $returnedUser = $guard->__invoke($request);

        $this->assertEquals($user->id, $returnedUser->id);
        $this->assertEquals($token->id, $returnedUser->currentAccessToken()->id);
        $this->assertInstanceOf(DateTimeInterface::class, $returnedUser->currentAccessToken()->last_used_at);
    }

    public function test_authentication_with_token_fails_if_user_provider_is_invalid()
    {
        $this->loadLaravelMigrations(['--database' => 'testbench']);
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        config(['auth.guards.sanctum.provider' => 'users']);
        config(['auth.providers.users.model' => 'App\Models\User']);

        $factory = $this->app->make(AuthFactory::class);
        $requestGuard = $factory->guard('sanctum');

        $request = Request::create('/', 'GET');
        $request->headers->set('Authorization', 'Bearer test');

        $user = User::forceCreate([
            'name' => 'Taylor Otwell',
            'email' => 'taylor@laravel.com',
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'remember_token' => Str::random(10),
        ]);

        $token = PersonalAccessToken::forceCreate([
            'tokenable_id' => $user->id,
            'tokenable_type' => get_class($user),
            'name' => 'Test',
            'token' => hash('sha256', 'test'),
        ]);

        $returnedUser = $requestGuard->setRequest($request)->user();

        $this->assertNull($returnedUser);
        $this->assertInstanceOf(EloquentUserProvider::class, $requestGuard->getProvider());
    }

    public function test_authentication_is_successful_with_token_if_user_provider_is_valid()
    {
        $this->loadLaravelMigrations(['--database' => 'testbench']);
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        config(['auth.guards.sanctum.provider' => 'users']);
        config(['auth.providers.users.model' => User::class]);

        $factory = $this->app->make(AuthFactory::class);
        $requestGuard = $factory->guard('sanctum');

        $request = Request::create('/', 'GET');
        $request->headers->set('Authorization', 'Bearer test');

        $user = User::forceCreate([
            'name' => 'Taylor Otwell',
            'email' => 'taylor@laravel.com',
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'remember_token' => Str::random(10),
        ]);

        $token = PersonalAccessToken::forceCreate([
            'tokenable_id' => $user->id,
            'tokenable_type' => get_class($user),
            'name' => 'Test',
            'token' => hash('sha256', 'test'),
        ]);

        $returnedUser = $requestGuard->setRequest($request)->user();

        $this->assertEquals($user->id, $returnedUser->id);
        $this->assertInstanceOf(EloquentUserProvider::class, $requestGuard->getProvider());
    }

    public function test_authentication_fails_if_callback_returns_false()
    {
        $this->loadLaravelMigrations(['--database' => 'testbench']);
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        config(['auth.guards.sanctum.provider' => 'users']);
        config(['auth.providers.users.model' => User::class]);

        $factory = $this->app->make(AuthFactory::class);
        $requestGuard = $factory->guard('sanctum');

        $request = Request::create('/', 'GET');
        $request->headers->set('Authorization', 'Bearer test');

        $user = User::forceCreate([
            'name' => 'Taylor Otwell',
            'email' => 'taylor@laravel.com',
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'remember_token' => Str::random(10),
        ]);

        $token = PersonalAccessToken::forceCreate([
            'tokenable_id' => $user->id,
            'tokenable_type' => get_class($user),
            'name' => 'Test',
            'token' => hash('sha256', 'test'),
        ]);

        Sanctum::authenticateAccessTokensUsing(function ($accessToken, bool $isValid) {
            $this->assertInstanceOf(PersonalAccessToken::class, $accessToken);
            $this->assertTrue($isValid);

            return false;
        });

        $user = $requestGuard->setRequest($request)->user();
        $this->assertNull($user);
    }

    protected function getPackageProviders($app)
    {
        return [SanctumServiceProvider::class];
    }
}

class User extends Model implements HasApiTokensContract
{
    use HasApiTokens;
}
