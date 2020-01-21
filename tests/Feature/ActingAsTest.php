<?php

namespace Laravel\Airlock\Tests\Feature;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Airlock\Airlock;
use Laravel\Airlock\AirlockServiceProvider;
use Laravel\Airlock\HasApiTokens;
use Orchestra\Testbench\TestCase;

class ActingAsTest extends TestCase
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

    public function testActingAsWhenTheRouteIsProtectedByAuthMiddlware()
    {
        $this->withoutExceptionHandling();

        Route::get('/foo', function () {
            return 'bar';
        })->middleware('auth:airlock');

        Airlock::actingAs($user = new AirlockUser);
        $user->id = 1;

        $response = $this->get('/foo');

        $response->assertStatus(200);
        $response->assertSee('bar');
    }

    public function testActingAsWhenTheRouteIsProtectedUsingAbilities()
    {
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        $this->withoutExceptionHandling();

        Route::get('/foo', function () {
            if (Auth::user()->tokenCan('baz')) {
                return 'bar';
            }

            return response(403);
        })->middleware('auth:airlock');

        $user = new AirlockUser;
        $user->id = 1;

        Airlock::actingAs($user, ['baz']);

        $response = $this->get('/foo');

        $response->assertStatus(200);
        $response->assertSee('bar');
    }

    protected function getPackageProviders($app)
    {
        return [AirlockServiceProvider::class];
    }
}

class AirlockUser extends User
{
    use HasApiTokens;
}
