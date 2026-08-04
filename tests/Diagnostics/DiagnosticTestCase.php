<?php

namespace Laravel\Sanctum\Tests\Diagnostics;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Application;
use Laravel\Doctor\DoctorServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Laravel\Sanctum\SanctumServiceProvider;
use Orchestra\Testbench\TestCase;
use Workbench\App\Models\User;

abstract class DiagnosticTestCase extends TestCase
{
    protected function setUp(): void
    {
        if (! class_exists(DoctorServiceProvider::class)) {
            $this->markTestSkipped('laravel/doctor is not installed.');
        }

        parent::setUp();
    }

    /**
     * Get the package providers.
     *
     * @param  Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            SanctumServiceProvider::class,
            DoctorServiceProvider::class,
        ];
    }

    /**
     * Define the test environment.
     *
     * @param  Application  $app
     */
    protected function defineEnvironment($app)
    {
        $config = $app->make(Repository::class);

        $config->set([
            'app.url' => 'http://localhost',
            'app.frontend_url' => null,
            'auth.providers.users' => [
                'driver' => 'eloquent',
                'model' => User::class,
            ],
            'database.default' => 'testing',
        ]);
    }

    protected function tearDown(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        parent::tearDown();
    }
}
