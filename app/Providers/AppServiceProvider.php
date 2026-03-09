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
        RateLimiter::for('auth', function (Request $request): array {
            $emailKey = (string) $request->input('email', 'guest');

            return [
                Limit::perMinute(8)->by($request->ip()),
                Limit::perMinute(5)->by(strtolower($emailKey) . '|' . $request->ip()),
            ];
        });

        RateLimiter::for('sensitive', function (Request $request): Limit {
            $userKey = $request->user()?->id ?? $request->ip();

            return Limit::perMinute(20)->by((string) $userKey);
        });
    }
}
