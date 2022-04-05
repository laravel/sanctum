<?php

namespace Laravel\Sanctum\Tests;

use DateTimeInterface;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Laravel\Sanctum\Contracts\HasApiTokens as HasApiTokensContract;
use Laravel\Sanctum\Events\TokenAuthenticated;
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

        $guard = new Guard($factory, null, 'users');

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

        $guard = new Guard($factory, null, 'users');

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

        $guard = new Guard($factory, 1, 'users');

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

        $guard = new Guard($factory, null);

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

        Event::fake([
            TokenAuthenticated::class,
        ]);

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
        Event::assertNotDispatched(TokenAuthenticated::class);
    }

    public function test_authentication_is_successful_with_token_if_user_provider_is_valid()
    {
        $this->loadLaravelMigrations(['--database' => 'testbench']);
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        config(['auth.guards.sanctum.provider' => 'users']);
        config(['auth.providers.users.model' => User::class]);

        $factory = $this->app->make(AuthFactory::class);
        $requestGuard = $factory->guard('sanctum');

        Event::fake([
            TokenAuthenticated::class,
        ]);

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
        Event::assertDispatched(TokenAuthenticated::class);
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

        Sanctum::$accessTokenAuthenticationCallback = null;
    }

    public function test_authentication_is_successful_with_token_in_custom_header()
    {
        $this->loadLaravelMigrations(['--database' => 'testbench']);
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        $factory = Mockery::mock(AuthFactory::class);

        $guard = new Guard($factory, null);

        $webGuard = Mockery::mock(stdClass::class);

        $factory->shouldReceive('guard')
                ->with('web')
                ->andReturn($webGuard);

        $webGuard->shouldReceive('user')->once()->andReturn(null);

        $request = Request::create('/', 'GET');
        $request->headers->set('X-Auth-Token', 'test');

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

        Sanctum::getAccessTokenFromRequestUsing(function (Request $request) {
            return $request->header('X-Auth-Token');
        });

        $returnedUser = $guard->__invoke($request);

        $this->assertEquals($user->id, $returnedUser->id);
        $this->assertEquals($token->id, $returnedUser->currentAccessToken()->id);
        $this->assertInstanceOf(DateTimeInterface::class, $returnedUser->currentAccessToken()->last_used_at);

        Sanctum::$accessTokenRetrievalCallback = null;
    }

    public function test_authentication_fails_with_token_in_authorization_header_when_using_custom_header()
    {
        $this->loadLaravelMigrations(['--database' => 'testbench']);
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        $factory = Mockery::mock(AuthFactory::class);

        $guard = new Guard($factory, null);

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
        ]);

        Sanctum::getAccessTokenFromRequestUsing(function (Request $request) {
            return $request->header('X-Auth-Token');
        });

        $returnedUser = $guard->__invoke($request);

        $this->assertNull($returnedUser);

        Sanctum::$accessTokenRetrievalCallback = null;
    }

    public function test_authentication_fails_with_token_in_custom_header_when_using_default_authorization_header()
    {
        $this->loadLaravelMigrations(['--database' => 'testbench']);
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        $factory = Mockery::mock(AuthFactory::class);

        $guard = new Guard($factory, null);

        $webGuard = Mockery::mock(stdClass::class);

        $factory->shouldReceive('guard')
                ->with('web')
                ->andReturn($webGuard);

        $webGuard->shouldReceive('user')->once()->andReturn(null);

        $request = Request::create('/', 'GET');
        $request->headers->set('X-Auth-Token', 'test');

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

        $this->assertNull($returnedUser);
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
