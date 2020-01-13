<?php

namespace Laravel\Airlock;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Airlock\Http\Controllers\CsrfCookieController;
use Laravel\Airlock\Http\Middleware\EnsureFrontendRequestsAreStateful;

class AirlockServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        config([
            'auth.guards.airlock' => array_merge([
                'driver' => 'airlock',
                'provider' => 'users',
            ], config('auth.guards.airlock', [])),
        ]);

        if (! $this->app->configurationIsCached()) {
            $this->mergeConfigFrom(__DIR__.'/../config/airlock.php', 'airlock');
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->registerMigrations();

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'airlock-migrations');

            $this->publishes([
                __DIR__.'/../config/airlock.php' => config_path('airlock.php'),
            ], 'airlock-config');
        }

        $this->defineRoutes();
        $this->configureGuard();
        $this->configureMiddleware();
    }

    /**
     * Register Airlock's migration files.
     *
     * @return void
     */
    protected function registerMigrations()
    {
        if (Airlock::shouldRunMigrations()) {
            return $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }
    }

    /**
     * Define the Airlock routes.
     *
     * @return void
     */
    protected function defineRoutes()
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        Route::group(['prefix' => config('airlock.prefix', 'airlock')], function () {
            Route::get(
                '/csrf-cookie',
                CsrfCookieController::class.'@show'
            )->middleware('web');
        });
    }

    /**
     * Configure the Airlock authentication guard.
     *
     * @return void
     */
    protected function configureGuard()
    {
        Auth::resolved(function ($auth) {
            $auth->viaRequest('airlock', new Guard($auth, config('airlock.expiration')));
        });
    }

    /**
     * Configure the Airlock middleware and priority.
     *
     * @return void
     */
    protected function configureMiddleware()
    {
        $kernel = $this->app->make(Kernel::class);

        $kernel->prependToMiddlewarePriority(EnsureFrontendRequestsAreStateful::class);
        // $kernel->prependMiddlewareToGroup('api', EnsureFrontendRequestsAreStateful::class);
    }
}
