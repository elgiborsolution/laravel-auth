<?php

namespace ElgiborSolution\Authentication\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

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

        if ($this->app->runningInConsole()) {
            $this->commands([
                \ElgiborSolution\Authentication\Console\InstallCommand::class,
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
        Route::prefix(config('authentication.prefix', 'api/auth'))
            ->middleware(config('authentication.middleware', ['api']))
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
