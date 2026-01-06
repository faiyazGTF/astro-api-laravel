<?php

namespace App\Providers;
use Illuminate\Http\Request;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        date_default_timezone_set('Asia/Kolkata');
        // $this->app->bind('path.public', function() {
        //     return '/var/www/html/astroera/public';
        // });
        RateLimiter::for('api', function (Request $request) {
            return Limit::none(); // No rate limiting at all
        });
    }
}
