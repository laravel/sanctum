<?php

namespace Laravel\Sanctum\Tests\Feature;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Laravel\Sanctum\Http\Middleware\CheckForAnyScope;
use Laravel\Sanctum\Http\Middleware\CheckScopes;
use Laravel\Sanctum\Sanctum;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Workbench\App\Models\User;

class ActingAsTest extends TestCase
{
    use WithWorkbench;

    protected function defineEnvironment($app)
    {
        $app['config']->set('database.default', 'testing');
    }

    public function testActingAsWhenTheRouteIsProtectedByAuthMiddlware()
    {
        $this->withoutExceptionHandling();

        Route::get('/foo', function () {
            return 'bar';
        })->middleware('auth:sanctum');

        Sanctum::actingAs($user = new User);
        $user->id = 1;

        $response = $this->get('/foo');

        $response->assertStatus(200);
        $response->assertSee('bar');
    }

    public function testActingAsWhenTheRouteIsProtectedByCheckScopesMiddleware()
    {
        $this->withoutExceptionHandling();

        Route::get('/foo', function () {
            return 'bar';
        })->middleware(CheckScopes::class.':admin,footest');

        Sanctum::actingAs(new User(), ['admin', 'footest']);

        $response = $this->get('/foo');
        $response->assertSuccessful();
        $response->assertSee('bar');
    }

    public function testActingAsWhenTheRouteIsProtectedByCheckForAnyScopeMiddleware()
    {
        $this->withoutExceptionHandling();

        Route::get('/foo', function () {
            return 'bar';
        })->middleware(CheckForAnyScope::class.':admin,footest');

        Sanctum::actingAs(new User(), ['footest']);

        $response = $this->get('/foo');
        $response->assertSuccessful();
        $response->assertSee('bar');
    }

    public function testActingAsWhenTheRouteIsProtectedByCheckAbilitiesMiddleware()
    {
        $this->withoutExceptionHandling();

        Route::get('/foo', function () {
            return 'bar';
        })->middleware(CheckAbilities::class.':admin,footest');

        Sanctum::actingAs(new User(), ['admin', 'footest']);

        $response = $this->get('/foo');
        $response->assertSuccessful();
        $response->assertSee('bar');
    }

    public function testActingAsWhenTheRouteIsProtectedByCheckForAnyAbilityMiddleware()
    {
        $this->withoutExceptionHandling();

        Route::get('/foo', function () {
            return 'bar';
        })->middleware(CheckForAnyAbility::class.':admin,footest');

        Sanctum::actingAs(new User(), ['footest']);

        $response = $this->get('/foo');
        $response->assertSuccessful();
        $response->assertSee('bar');
    }

    public function testActingAsWhenTheRouteIsProtectedUsingAbilities()
    {
        $this->artisan('migrate', ['--database' => 'testing'])->run();

        $this->withoutExceptionHandling();

        Route::get('/foo', function () {
            if (Auth::user()->tokenCan('baz')) {
                return 'bar';
            }

            return response(403);
        })->middleware('auth:sanctum');

        $user = new User;
        $user->id = 1;

        Sanctum::actingAs($user, ['baz']);

        $response = $this->get('/foo');

        $response->assertStatus(200);
        $response->assertSee('bar');
    }

    public function testActingAsWhenKeyHasAnyAbility()
    {
        $this->artisan('migrate', ['--database' => 'testing'])->run();

        $this->withoutExceptionHandling();

        Route::get('/foo', function () {
            if (Auth::user()->tokenCan('baz')) {
                return 'bar';
            }

            return response(403);
        })->middleware('auth:sanctum');

        $user = new User;
        $user->id = 1;

        Sanctum::actingAs($user, ['*']);

        $response = $this->get('/foo');

        $response->assertStatus(200);
        $response->assertSee('bar');
    }
}
