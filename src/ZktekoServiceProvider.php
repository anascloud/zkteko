<?php

namespace Anascloud\Zkteko;

use Illuminate\Support\ServiceProvider;

class ZktekoServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/zkteko.php' => config_path('zkteko.php'),
            ], 'zkteko-config');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/zkteko.php', 'zkteko');

        // Register the main class to use with the facade
        $this->app->singleton('zkteko', function () {
            return new ZKTeco(
                config('zkteko.ip'),
                config('zkteko.port'),
                config('zkteko.password')
            );
        });
    }
}
