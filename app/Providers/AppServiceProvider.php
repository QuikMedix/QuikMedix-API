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
    public function boot(): void
    {
        RateLimiter::for('api', fn (Request $request): Limit => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('password-reset', function (Request $request): array {
            $phone = $request->input('phone');

            return [
                Limit::perMinutes(10, 10)->by($request->ip()),
                Limit::perMinutes(10, 3)->by('phone|'.(is_string($phone) ? $phone : '')),
            ];
        });

        RateLimiter::for('login', function (Request $request): array {
            $phone = $request->input('phone');

            return [
                Limit::perMinute(30)->by($request->ip()),
                Limit::perMinute(5)->by($request->ip().'|'.(is_string($phone) ? $phone : '')),
            ];
        });
    }
}
