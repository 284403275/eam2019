<?php


namespace App\SAP\Core;


use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Support\ServiceProvider;
use Laravel\Lumen\Application as LumenApplication;

class SapServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $source = realpath($raw = __DIR__ . '/sap-config.php') ?: $raw;

        if ($this->app instanceof LaravelApplication && $this->app->runningInConsole()) {
            //Loads Config
            $this->publishes([$source => config_path('sap-config.php')]);
            //Load Migrations
//            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        } elseif ($this->app instanceof LumenApplication) {
            $this->app->configure('sap');
        }


        if ($this->app instanceof LaravelApplication && !$this->app->configurationIsCached()) {
            $this->mergeConfigFrom($source, 'sap');
        }
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('App\SAP\Core\SAP', function ($app) {
            return new SAP();
        });

//        $this->app->alias('SAP', 'SAP');
    }
}