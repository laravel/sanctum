<?php

namespace Laravel\Sanctum\Tests\Feature;

use DateTimeInterface;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Events\TokenAuthenticated;
use Laravel\Sanctum\Guard;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use stdClass;
use Workbench\App\Models\User;
use Workbench\Database\Factories\PersonalAccessTokenFactory;
use Workbench\Database\Factories\UserFactory;

class GuardTest extends TestCase
{
    use RefreshDatabase, WithWorkbench;

    protected function defineEnvironment($app)
    {
        $app['config']->set([
            'auth.guards.sanctum.provider' => 'users',
            'auth.providers.users.model' => User::class,
            'database.default' => 'testing',
        ]);
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
        $factory = Mockery::mock(AuthFactory::class);

        $guard = new Guard($factory, 1, 'users');

        $webGuard = Mockery::mock(stdClass::class);

        $factory->shouldReceive('guard')
                ->with('web')
                ->andReturn($webGuard);

        $webGuard->shouldReceive('user')->once()->andReturn(null);

        $request = Request::create('/', 'GET');
        $request->headers->set('Authorization', 'Bearer test');

        PersonalAccessTokenFactory::new()->for(
            $user = UserFactory::new()->create(), 'tokenable'
        )->create([
            'name' => 'Test',
            'created_at' => now()->subMinutes(60),
        ]);

        $user = $guard->__invoke($request);

        $this->assertNull($user);
    }

    public function test_authentication_with_token_fails_if_expires_at_has_passed()
    {
        $factory = Mockery::mock(AuthFactory::class);

        $guard = new Guard($factory, null, 'users');

        $webGuard = Mockery::mock(stdClass::class);

        $factory->shouldReceive('guard')
            ->with('web')
            ->andReturn($webGuard);

        $webGuard->shouldReceive('user')->once()->andReturn(null);

        $request = Request::create('/', 'GET');
        $request->headers->set('Authorization', 'Bearer test');

        PersonalAccessTokenFactory::new()->for(
            $user = UserFactory::new()->create(), 'tokenable'
        )->create([
            'name' => 'Test',
            'expires_at' => now()->subMinutes(60),
        ]);

        $user = $guard->__invoke($request);

        $this->assertNull($user);
    }

    public function test_authentication_with_token_succeeds_if_expires_at_not_passed()
    {
        $factory = Mockery::mock(AuthFactory::class);

        $guard = new Guard($factory, null, 'users');

        $webGuard = Mockery::mock(stdClass::class);

        $factory->shouldReceive('guard')
            ->with('web')
            ->andReturn($webGuard);

        $webGuard->shouldReceive('user')->once()->andReturn(null);

        $request = Request::create('/', 'GET');
        $request->headers->set('Authorization', 'Bearer test');

        $token = PersonalAccessTokenFactory::new()->for(
            $user = UserFactory::new()->create(), 'tokenable'
        )->create([
            'name' => 'Test',
            'expires_at' => now()->addMinutes(60),
        ]);

        $returnedUser = $guard->__invoke($request);

        $this->assertEquals($user->id, $returnedUser->id);
        $this->assertEquals($token->id, $returnedUser->currentAccessToken()->id);
        $this->assertInstanceOf(DateTimeInterface::class, $returnedUser->currentAccessToken()->last_used_at);
    }

    public function test_authentication_is_successful_with_token_if_no_session_present()
    {
        $factory = Mockery::mock(AuthFactory::class);

        $guard = new Guard($factory, null);

        $webGuard = Mockery::mock(stdClass::class);

        $factory->shouldReceive('guard')
                ->with('web')
                ->andReturn($webGuard);

        $webGuard->shouldReceive('user')->once()->andReturn(null);

        $request = Request::create('/', 'GET');
        $request->headers->set('Authorization', 'Bearer test');

        $token = PersonalAccessTokenFactory::new()->for(
            $user = UserFactory::new()->create(), 'tokenable'
        )->create([
            'name' => 'Test',
        ]);

        $returnedUser = $guard->__invoke($request);

        $this->assertEquals($user->id, $returnedUser->id);
        $this->assertEquals($token->id, $returnedUser->currentAccessToken()->id);
        $this->assertInstanceOf(DateTimeInterface::class, $returnedUser->currentAccessToken()->last_used_at);
    }

    public function test_authentication_with_token_fails_if_user_provider_is_invalid()
    {
        config(['auth.providers.users.model' => 'App\Models\User']);

        $factory = $this->app->make(AuthFactory::class);
        $requestGuard = $factory->guard('sanctum');

        Event::fake([
            TokenAuthenticated::class,
        ]);

        $request = Request::create('/', 'GET');
        $request->headers->set('Authorization', 'Bearer test');

        PersonalAccessTokenFactory::new()->for(
            UserFactory::new(), 'tokenable'
        )->create([
            'name' => 'Test',
        ]);

        $returnedUser = $requestGuard->setRequest($request)->user();

        $this->assertNull($returnedUser);
        $this->assertInstanceOf(EloquentUserProvider::class, $requestGuard->getProvider());
        Event::assertNotDispatched(TokenAuthenticated::class);
    }

    /**
     * @dataProvider invalidTokenDataProvider
     */
    public function test_authentication_with_token_fails_if_token_has_invalid_format($invalidToken)
    {
        $factory = Mockery::mock(AuthFactory::class);

        $guard = new Guard($factory, null, 'users');

        $webGuard = Mockery::mock(stdClass::class);

        $factory->shouldReceive('guard')
            ->with('web')
            ->andReturn($webGuard);

        $webGuard->shouldReceive('user')->once()->andReturn(null);

        $request = Request::create('/', 'GET');

        PersonalAccessTokenFactory::new()->for(
            UserFactory::new(), 'tokenable'
        )->create([
            'name' => 'Test',
            'expires_at' => now()->subMinutes(60),
        ]);

        $request->headers->set('Authorization', $invalidToken);
        $returnedUser = $guard->__invoke($request);
        $this->assertNull($returnedUser);
    }

    public function test_authentication_is_successful_with_token_if_user_provider_is_valid()
    {
        $factory = $this->app->make(AuthFactory::class);
        $requestGuard = $factory->guard('sanctum');

        Event::fake([
            TokenAuthenticated::class,
        ]);

        $request = Request::create('/', 'GET');
        $request->headers->set('Authorization', 'Bearer test');

        PersonalAccessTokenFactory::new()->for(
            $user = UserFactory::new()->create(), 'tokenable'
        )->create([
            'name' => 'Test',
        ]);

        $returnedUser = $requestGuard->setRequest($request)->user();

        $this->assertEquals($user->id, $returnedUser->id);
        $this->assertInstanceOf(EloquentUserProvider::class, $requestGuard->getProvider());
        Event::assertDispatched(TokenAuthenticated::class);
    }

    public function test_authentication_fails_if_callback_returns_false()
    {
        $factory = $this->app->make(AuthFactory::class);
        $requestGuard = $factory->guard('sanctum');

        $request = Request::create('/', 'GET');
        $request->headers->set('Authorization', 'Bearer test');

        PersonalAccessTokenFactory::new()->for(
            $user = UserFactory::new()->create(), 'tokenable'
        )->create([
            'name' => 'Test',
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
        $factory = Mockery::mock(AuthFactory::class);

        $guard = new Guard($factory, null);

        $webGuard = Mockery::mock(stdClass::class);

        $factory->shouldReceive('guard')
                ->with('web')
                ->andReturn($webGuard);

        $webGuard->shouldReceive('user')->once()->andReturn(null);

        $request = Request::create('/', 'GET');
        $request->headers->set('X-Auth-Token', 'test');

        $token = PersonalAccessTokenFactory::new()->for(
            $user = UserFactory::new()->create(), 'tokenable'
        )->create([
            'name' => 'Test',
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
        $factory = Mockery::mock(AuthFactory::class);

        $guard = new Guard($factory, null);

        $webGuard = Mockery::mock(stdClass::class);

        $factory->shouldReceive('guard')
                ->with('web')
                ->andReturn($webGuard);

        $webGuard->shouldReceive('user')->once()->andReturn(null);

        $request = Request::create('/', 'GET');
        $request->headers->set('Authorization', 'Bearer test');

        PersonalAccessTokenFactory::new()->for(
            UserFactory::new(), 'tokenable'
        )->create([
            'name' => 'Test',
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
        $factory = Mockery::mock(AuthFactory::class);

        $guard = new Guard($factory, null);

        $webGuard = Mockery::mock(stdClass::class);

        $factory->shouldReceive('guard')
                ->with('web')
                ->andReturn($webGuard);

        $webGuard->shouldReceive('user')->once()->andReturn(null);

        $request = Request::create('/', 'GET');
        $request->headers->set('X-Auth-Token', 'test');

        PersonalAccessTokenFactory::new()->for(
            UserFactory::new(), 'tokenable'
        )->create([
            'name' => 'Test',
        ]);

        $returnedUser = $guard->__invoke($request);

        $this->assertNull($returnedUser);
    }

    public static function invalidTokenDataProvider(): array
    {
        return [
            [''],
            ['|'],
            ['test'],
            ['|test'],
            ['1ABC|test'],
            ['1ABC|'],
            ['1,2|test'],
            ['Bearer'],
            ['Bearer |test'],
            ['Bearer 1,2|test'],
            ['Bearer 1ABC|test'],
            ['Bearer 1ABC|'],
        ];
    }
}
