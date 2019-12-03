<?php

namespace LeoChien\Spgateway;

use Illuminate\Support\ServiceProvider;

class SpgatewayServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerConfig();
        $this->registerResources();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $app = $this->app;

        // create image
        $app->singleton('mpg', function ($app) {
            return new MPG();
        });

        // create image
        $app->singleton('receipt', function ($app) {
            return new Receipt();
        });

        // create image
        $app->singleton('encrypt', function ($app) {
            return new EncryptLibrary();
        });

        // create image
        $app->singleton('refund', function ($app) {
            return new Refund();
        });

        // create image
        $app->singleton('transfer', function ($app) {
            return new Transfer();
        });
    }

    protected function registerConfig()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/spgateway.php', 'spgateway'
        );
        
        $this->publishes([
            __DIR__ . '/../config/spgateway.php' => config_path('spgateway.php'),
        ]);
    }

    protected function registerResources()
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'spgateway');
    }
}
