<?php

namespace App\Providers;

use App\Services\PaytechService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        $this->app->bind(PaytechService::class, function ($app) {
            return new PaytechService(
                config('services.paytech.api_key'),
                config('services.paytech.api_secret')
            );
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
