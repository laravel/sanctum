<?php

namespace Laravel\Airlock\Tests\Feature;

use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Foundation\Auth\User;
use Laravel\Airlock\Airlock;
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

        /** @var Registrar $router */
        $router = $this->app->make(Registrar::class);

        $router->get('/foo', function () {
            return 'bar';
        })->middleware('auth:api');

        Airlock::actingAs(new AirlockUser());

        $response = $this->get('/foo');
        $response->assertSuccessful();
        $response->assertSee('bar');
    }

    public function testActingAsWhenTheRouteIsProtectedUsingAbilities()
    {
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        $this->withoutExceptionHandling();

        /** @var Registrar $router */
        $router = $this->app->make(Registrar::class);

        $router->get('/foo', function () {
            if (auth()->user()->tokenCan('baz')) {
                return 'bar';
            }

            return response(403);
        })->middleware('auth:api');

        $user = new AirlockUser();
        $user->createToken('test-token', ['baz'])->plainTextToken;

        Airlock::actingAs($user);

        $response = $this->get('/foo');
        $response->assertSuccessful();
        $response->assertSee('bar');
    }
}

class AirlockUser extends User
{
    use HasApiTokens;
}
