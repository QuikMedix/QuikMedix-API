<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/';

    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request): array {
            $phone = $request->input('phone');

            return [
                Limit::perMinute(30)->by($request->ip()),
                Limit::perMinute(5)->by($request->ip().'|'.(is_string($phone) ? $phone : '')),
            ];
        });

        $this->routes(function (): void {
            Route::prefix('api')->middleware('api')->group(base_path('routes/api.php'));
            Route::middleware('web')->group(base_path('routes/web.php'));
        });
    }
}
