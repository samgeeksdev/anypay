<?php

namespace samgeeksdev\AnyPay;

use Illuminate\Support\ServiceProvider;

class AnyPayServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('payto', function ($app) {
            return new AnyPay();
        });
    }

    /**
     * Bootstrap any application services.
    
     * @return void
     */
    public function boot()
    {
        // Here you can bootstrap your package services.
    }
}
