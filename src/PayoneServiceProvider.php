<?php

namespace Birim\Laravel\Payone;

use Illuminate\Support\ServiceProvider;

/**
 * Class PayoneServiceProvider
 * @package Birim\Laravel\Payone
 */
class PayoneServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom( __DIR__ . '/config/payone.php', 'laravel-payone');

        $this->app->singleton('Payone', function() {
            return new Payone();
        });
    }

    /**
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/payone.php' => config_path('payone.php'),
        ], 'laravel-payone');
    }
}