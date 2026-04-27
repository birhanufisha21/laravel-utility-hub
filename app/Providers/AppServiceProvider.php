<?php

namespace App\Providers;

use App\Services\CurrencyService;
use App\Services\WeatherService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CurrencyService::class, function ($app) {
            return new CurrencyService();
        });

        $this->app->singleton(WeatherService::class, function ($app) {
            return new WeatherService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
