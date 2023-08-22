<?php

namespace Laravel\Sanctum\Tests\Feature\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\EnsureDeviceHasNotBeenLoggedOut;
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
        ]);
    }

    protected function defineRoutes($router)
    {
        $router->get('/sanctum/api/user', function (Request $request) {
            abort_if(is_null($request->user()), 401);

            return $request->user()->name;
        })->middleware('auth:sanctum', EnsureDeviceHasNotBeenLoggedOut::class);

        $router->get('/sanctum/web/user', function (Request $request) {
            abort_if(is_null($request->user()), 401);

            return $request->user()->name;
        })->middleware('web', 'auth:sanctum', EnsureDeviceHasNotBeenLoggedOut::class);
    }

    public function test_middleware_can_authorize_valid_user_using_header()
    {
        PersonalAccessTokenFactory::new()->for(
            $user = UserFactory::new()->create(), 'tokenable')
        ->create();

        $this->getJson('/sanctum/api/user', [
            'Authorization' => 'Bearer test',
        ])->assertOk()
            ->assertSee($user->name);


        $user->password = bcrypt('laravel');
        $user->save();

        $this->getJson('/sanctum/api/user', [
            'Authorization' => 'Bearer test',
        ])->assertOk()
            ->assertSee($user->name);
    }


    public function test_middleware_can_deauthorize_valid_user_using_acting_as_after_password_change()
    {
        $user = UserFactory::new()->create();

        Sanctum::actingAs($user);

        $this->getJson('/sanctum/web/user')
            ->assertOk()
            ->assertSee($user->name);

        $user->password = bcrypt('laravel');
        $user->save();

        $this->getJson('/sanctum/web/user')
            ->assertStatus(401);
    }
}
