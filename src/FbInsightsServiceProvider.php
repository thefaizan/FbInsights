<?php

namespace SmartHub\FbInsights;

use Illuminate\Support\ServiceProvider;

class FbInsightsServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'smarthub');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'smarthub');
        $this->loadMigrationsFrom(__DIR__.'/Migrations');
        $this->loadRoutesFrom(__DIR__.'/Routes/web.php');
        // include __DIR__.'/Routes/web.php';

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/fbinsights.php', 'fbinsights');

        // Register the service the package provides.
        $this->app->singleton('fbinsights', function ($app) {
            return new FbInsights;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['fbinsights'];
    }
    
    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole()
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/fbinsights.php' => config_path('fbinsights.php'),
        ], 'fbinsights.config');

        // Publishing the views.
        /*$this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/smarthub'),
        ], 'fbinsights.views');*/

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/smarthub'),
        ], 'fbinsights.views');*/

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/smarthub'),
        ], 'fbinsights.views');*/

        // Registering package commands.
        // $this->commands([]);
    }
}
