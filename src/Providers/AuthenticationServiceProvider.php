<?php

namespace ElgiborSolution\Authentication\Providers;

use ElgiborSolution\Authentication\Console\InstallCommand;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AuthenticationServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/authentication.php', 'authentication'
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerRoutes();
        $this->registerPublishing();
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        // Register Passport scope for two-step login central token
        // The 'central' scope gates access to POST /api/tenant-login (step 2)
        if (config('authentication.two_step_login.enabled', false)) {
            Passport::tokensCan([
                'central' => 'Authenticate to central — required for tenant login (step 2)',
            ]);
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);
        }
    }

    /**
     * Register the package routes.
     *
     * @return void
     */
    protected function registerRoutes()
    {
        Route::middleware(config('authentication.middleware', ['api']))
            ->group(function () {
                $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
            });
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    protected function registerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/authentication.php' => config_path('authentication.php'),
            ], 'authentication-config');

            // Publish routes if the user wants to customize them
            $this->publishes([
                __DIR__.'/../../routes/api.php' => base_path('routes/auth-api.php'),
            ], 'authentication-routes');

            // Publish migrations so the user can see/edit them in their main project
            $this->publishes([
                __DIR__.'/../../database/migrations' => database_path('migrations'),
            ], 'authentication-migrations');
        }
    }
}
