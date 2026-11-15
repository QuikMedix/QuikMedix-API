<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * What bootstrap/app.php, bootstrap/providers.php and routes/console.php set up. These were
 * App\Http\Kernel, App\Console\Kernel, the exception handler and two providers; the values
 * here are the ones those classes declared.
 */
class ApplicationWiringTest extends TestCase
{
    public function test_every_request_passes_the_same_global_middleware(): void
    {
        $global = (fn (): array => $this->middleware)->call($this->app->make(Kernel::class));

        $this->assertSame([
            \App\Http\Middleware\TrustProxies::class,
            \Illuminate\Http\Middleware\HandleCors::class,
            \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
            \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
            \App\Http\Middleware\TrimStrings::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        ], $global);
    }

    public function test_web_and_api_groups_and_the_applications_aliases(): void
    {
        // The HTTP kernel hands its groups and aliases to the router when it is first resolved.
        $this->app->make(Kernel::class);
        $router = $this->app['router'];

        $this->assertSame([
            'web' => [
                \App\Http\Middleware\EncryptCookies::class,
                \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
                \Illuminate\Session\Middleware\StartSession::class,
                \Illuminate\View\Middleware\ShareErrorsFromSession::class,
                \App\Http\Middleware\PreventRequestForgery::class,
                \Illuminate\Routing\Middleware\SubstituteBindings::class,
            ],
            'api' => ['throttle:60,1', \Illuminate\Routing\Middleware\SubstituteBindings::class],
        ], array_intersect_key($router->getMiddlewareGroups(), ['web' => true, 'api' => true]));

        $aliases = $router->getMiddleware();
        foreach ([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            '2fa' => \App\Http\Middleware\Check2FA::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
            'active.api' => \App\Http\Middleware\EnsureApiAccountIsActive::class,
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
            'allowed' => \App\Http\Middleware\EnsureUserIsAllowed::class,
            'can' => \Illuminate\Auth\Middleware\Authorize::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ] as $alias => $class) {
            $this->assertSame($class, $aliases[$alias] ?? null, $alias);
        }
    }

    public function test_the_seven_scheduled_tasks_keep_their_times(): void
    {
        $events = $this->app->make(Schedule::class)->events();

        $this->assertSame(
            ['*/3 * * * *', '*/15 * * * *', '* * * * *', '0 15 * * 4', '0 0 * * 1', '0 0 * * *', '0 12 * * *'],
            array_map(fn ($event): string => $event->expression, $events),
        );
        $this->assertSame([config('app.timezone')], array_values(array_unique(array_map(fn ($event) => (string) $event->timezone, $events))));
    }

    public function test_errors_are_json_for_the_api_and_for_clients_that_ask(): void
    {
        $this->get('/api/no-such-endpoint')->assertNotFound()->assertHeader('Content-Type', 'application/json');
        $this->getJson('/no-such-page')->assertNotFound()->assertHeader('Content-Type', 'application/json');
        $this->get('/no-such-page')->assertNotFound()->assertHeader('Content-Type', 'text/html; charset=utf-8');
    }

    public function test_providers_login_limiter_and_home(): void
    {
        $loaded = array_keys($this->app->getLoadedProviders());

        foreach ([AppServiceProvider::class, \App\Providers\AuthServiceProvider::class, \App\Providers\TwilioAccessTokenProvider::class, \App\Providers\TwilioChatGrantProvider::class] as $provider) {
            $this->assertContains($provider, $loaded);
        }
        $this->assertNotNull(RateLimiter::limiter('login'));
        $this->assertSame('/', AppServiceProvider::HOME);
        // The verification email listener is registered once, not once per events provider.
        $this->assertCount(1, Event::getRawListeners()[Registered::class] ?? []);
    }
}
