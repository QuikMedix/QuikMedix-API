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
        $middleware->trustProxies(at: '*');
        $middleware->preventRequestForgery(except: ['zadarma']);
        $middleware->throttleApi();
        $middleware->web(append: \App\Http\Middleware\RemoveSecretsFromJson::class);
        $middleware->api(append: \App\Http\Middleware\RemoveSecretsFromJson::class);

        $middleware->alias([
            '2fa' => \App\Http\Middleware\Check2FA::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
            'active.api' => \App\Http\Middleware\EnsureApiAccountIsActive::class,
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
            'allowed' => \App\Http\Middleware\EnsureUserIsAllowed::class,
            'chat.viewer' => \App\Http\Middleware\EnsureChatViewer::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $exception): bool => $request->is('api/*') || $request->expectsJson()
        );
    })
    ->create();
