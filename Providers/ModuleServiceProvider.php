<?php

namespace Modules\PaymentMethodBsc\Providers;

use Caffeinated\Modules\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the module services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadTranslationsFrom(__DIR__.'/../Resources/Lang', 'payment-method-bsc');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'payment-method-bsc');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations', 'payment-method-bsc');
    }

    /**
     * Register the module services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
