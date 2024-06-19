<?php

namespace Vladang\MtCustom;

use Illuminate\Support\ServiceProvider;

class MtCustomProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/mt-custom.php' => config_path('mt-custom.php'),
        ], 'mt-custom-config');

        $this->app->singleton('mt-custom', function ($app) {

            $client = new Client();

            $client->connect();

            return $client;
        });
    }
}