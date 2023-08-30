<?php

namespace Laravel\Sanctum\Tests\Controller;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Laravel\Sanctum\Sanctum;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Workbench\App\Models\User;
use Workbench\Database\Factories\PersonalAccessTokenFactory;
use Workbench\Database\Factories\UserFactory;

class AuthenticateRequestsTest extends TestCase
{
    use RefreshDatabase, WithWorkbench;

    protected function defineEnvironment($app)
    {
        $app['config']->set([
            'app.key' => 'AckfSECXIvnK5r28GVIWUAxmbBSjTsmF',
            'auth.guards.sanctum.provider' => 'users',
            'auth.providers.users.model' => User::class,
            'database.default' => 'testing',
            'sanctum.middleware.encrypt_cookies' => \Illuminate\Cookie\Middleware\EncryptCookies::class,
            'sanctum.middleware.verify_csrf_token' => \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
        ]);
    }

    protected function defineRoutes($router)
    {
        $apiMiddleware = [EnsureFrontendRequestsAreStateful::class, 'api', 'auth:sanctum'];

        $router->get('/sanctum/api/user', function (Request $request) {
            abort_if(is_null($request->user()), 401);

            return $request->user()->email;
        })->middleware($apiMiddleware);

        $router->get('/sanctum/web/user', function (Request $request) {
            abort_if(is_null($request->user()), 401);

            return $request->user()->email;
        })->middleware($apiMiddleware);
    }

    public function test_can_authorize_valid_user_using_authorization_header()
    {
        PersonalAccessTokenFactory::new()->for(
            $user = UserFactory::new()->create(), 'tokenable'
        )->create();

        $this->getJson('/sanctum/api/user', ['Authorization' => 'Bearer test'])
            ->assertOk()
            ->assertSee($user->email);
    }

    /**
     * @dataProvider sanctumGuardsDataProvider
     */
    public function test_can_authorize_valid_user_using_sanctum_acting_as($guard)
    {
        PersonalAccessTokenFactory::new()->for(
            $user = UserFactory::new()->create(), 'tokenable'
        )->create();

        Sanctum::actingAs($user, [], $guard);

        $this->getJson('/sanctum/api/user')
            ->assertOk()
            ->assertSee($user->email);
    }

    public static function sanctumGuardsDataProvider()
    {
        yield [null];
        yield ['web'];
    }
}
