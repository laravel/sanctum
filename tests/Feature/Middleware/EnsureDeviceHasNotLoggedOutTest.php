<?php

namespace Laravel\Sanctum\Tests\Feature\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
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
        $router->get('/sanctum/user', function (Request $request) {
            abort_if(is_null($request->user()), 401);

            return $request->user()->name;
        })->middleware('auth:sanctum');
    }

    public function test_middleware_can_authorize_valid_user_using_header()
    {
        $token = PersonalAccessTokenFactory::new()->for(
            $user = UserFactory::new()->create(), 'tokenable')
        ->create();

        $this->getJson('/sanctum/user', [
            'Authorization' => 'Bearer test'
        ])->assertOk()
            ->assertSee($user->name);
    }

    public function test_middleware_can_authorize_valid_user_using_acting_as()
    {
        $token = PersonalAccessTokenFactory::new()->for(
            $user = UserFactory::new()->create(), 'tokenable')
        ->create();

        Sanctum::actingAs($user);

        $this->getJson('/sanctum/user')
            ->assertOk()
            ->assertSee($user->name);
    }

    public function test_middleware_can_deauthorize_valid_user_using_acting_as_after_password_change()
    {
        $token = PersonalAccessTokenFactory::new()->for(
            $user = UserFactory::new()->create(), 'tokenable')
        ->create();

        Sanctum::actingAs($user);

        $user->password = bcrypt('laravel');
        $user->save();

        $this->getJson('/sanctum/user')
            ->assertStatus(401);
    }
}
