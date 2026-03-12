<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;

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
        ResetPassword::createUrlUsing(function (object $notifiable, string $token): string {
            $frontend = rtrim((string) config('app.frontend_url', 'http://localhost:3000'), '/');
            $email = urlencode((string) $notifiable->getEmailForPasswordReset());

            return "{$frontend}/reset-password?token={$token}&email={$email}";
        });
    }
}
