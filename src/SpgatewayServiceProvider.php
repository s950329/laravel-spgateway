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

    protected function registerConfig()
    {
        $this->publishes([
            __DIR__ . '/../config/spgateway.php' => config_path('spgateway.php'),
        ]);
    }

    protected function registerResources()
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'spgateway');
    }
}
