<?php

namespace Laravel\Sanctum\Tests\Feature\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Laravel\Sanctum\Sanctum;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Workbench\App\Models\User;
use Workbench\Database\Factories\PersonalAccessTokenFactory;
use Workbench\Database\Factories\UserFactory;

class EnsureDeviceHasNotLoggedOutTest extends TestCase
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
        $webMiddleware = ['web', 'auth.session'];
        $apiMiddleware = [EnsureFrontendRequestsAreStateful::class, 'api', 'auth:sanctum'];

        $router->get('/sanctum/api/user', function (Request $request) {
            abort_if(is_null($request->user()), 401);

            return $request->user()->email;
        })->middleware($apiMiddleware);

        $router->get('/sanctum/web/user', function (Request $request) {
            abort_if(is_null($request->user()), 401);

            return $request->user()->email;
        })->middleware($apiMiddleware);

        $router->get('web/user', function (Request $request) {
            abort_if(is_null($request->user()), 401);

            return $request->user()->email;
        })->middleware($webMiddleware);
    }

    public function test_middleware_can_authorize_valid_user_using_header()
    {
        PersonalAccessTokenFactory::new()->for(
            $user = UserFactory::new()->create(), 'tokenable')
            ->create();

        $this->getJson('/sanctum/api/user', [
            'Authorization' => 'Bearer test',
        ])->assertOk()
            ->assertSee($user->email);

        $user->password = bcrypt('laravel');
        $user->save();

        $this->getJson('/sanctum/api/user', [
            'Authorization' => 'Bearer test',
        ])->assertOk()
            ->assertSee($user->email);
    }

    public function test_middleware_can_deauthorize_valid_user_using_acting_as_after_password_change_from_sanctum_guard()
    {
        $user = UserFactory::new()->create();

        Sanctum::actingAs($user, [], 'web');

        $this->getJson('/web/user', [
            'origin' => config('app.url'),
        ])
            ->assertOk()
            ->assertSee($user->email);

        $this->getJson('/sanctum/web/user', [
            'origin' => config('app.url'),
        ])
            ->assertOk()
            ->assertSee($user->email);

        $user->password = bcrypt('laravel');
        $user->save();

        $this->getJson('/sanctum/web/user', [
            'origin' => config('app.url'),
        ])->assertStatus(401);
    }

    public function test_middleware_can_deauthorize_valid_user_using_acting_as_after_password_change_coming_from_web_guard()
    {
        $user = UserFactory::new()->create();

        $this->actingAs($user)
            ->getJson('/web/user', [
                'origin' => config('app.url'),
            ])
            ->assertOk()
            ->assertSee($user->email);

        $this->getJson('/sanctum/web/user', [
            'origin' => config('app.url'),
        ])
            ->assertOk()
            ->assertSee($user->email);

        $user->password = bcrypt('laravel');
        $user->save();

        $this->getJson('/sanctum/web/user', [
            'origin' => config('app.url'),
        ])->assertStatus(401);
    }
}
