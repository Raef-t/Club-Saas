<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Event::subscribe(\App\Listeners\GlobalAuditSubscriber::class);

        // General API rate limiter: 60 requests per minute
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Login rate limiter: 5 attempts per minute per username + IP
        RateLimiter::for('login', function (Request $request) {
            $username = (string) $request->input('username', '');
            return Limit::perMinute(5)->by(strtolower(trim($username)) . '|' . $request->ip());
        });
    }
}
