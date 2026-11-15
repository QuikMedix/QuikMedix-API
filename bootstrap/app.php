<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // The stacks are the ones App\Http\Kernel declared, class for class, so every request
        // passes through exactly what it did before. The App\Http\Middleware subclasses carry
        // this application's settings: trusted proxies, untrimmed fields, the CSRF exception.
        $middleware->use([
            \App\Http\Middleware\TrustProxies::class,
            \Illuminate\Http\Middleware\HandleCors::class,
            \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
            \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
            \App\Http\Middleware\TrimStrings::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        ]);

        $middleware->group('web', [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\PreventRequestForgery::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        $middleware->group('api', [
            'throttle:60,1',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            '2fa' => \App\Http\Middleware\Check2FA::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
            'active.api' => \App\Http\Middleware\EnsureApiAccountIsActive::class,
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
            'allowed' => \App\Http\Middleware\EnsureUserIsAllowed::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $exception): bool => $request->is('api/*') || $request->expectsJson()
        );
    })
    ->create();
