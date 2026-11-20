<?php

namespace Tests\Feature;

use App\Http\Middleware\RemoveSecretsFromJson;
use App\Providers\AppServiceProvider;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * What bootstrap/app.php, bootstrap/providers.php and routes/console.php set up: the framework's
 * own middleware stacks plus the few settings that belong to this application.
 */
class ApplicationWiringTest extends TestCase
{
    public function test_the_stacks_are_the_frameworks_own_plus_the_api_rate_limit_and_the_secrets_check(): void
    {
        $defaults = new Middleware;
        $global = (fn (): array => $this->middleware)->call($this->app->make(Kernel::class));
        $groups = $this->app['router']->getMiddlewareGroups();

        $this->assertSame($defaults->getGlobalMiddleware(), $global);
        // Both groups end with the application's own check that no answer carries a password hash.
        $this->assertSame([...$defaults->getMiddlewareGroups()['web'], RemoveSecretsFromJson::class], $groups['web']);
        $this->assertSame(['throttle:api', SubstituteBindings::class, RemoveSecretsFromJson::class], $groups['api']);
        $this->assertNotNull(RateLimiter::limiter('api'));
        $this->assertNotNull(RateLimiter::limiter('login'));
    }

    public function test_this_applications_own_middleware_names(): void
    {
        $this->app->make(Kernel::class);
        $aliases = $this->app['router']->getMiddleware();

        foreach ([
            '2fa' => \App\Http\Middleware\Check2FA::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
            'active.api' => \App\Http\Middleware\EnsureApiAccountIsActive::class,
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
            'allowed' => \App\Http\Middleware\EnsureUserIsAllowed::class,
            'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
            'guest' => \Illuminate\Auth\Middleware\RedirectIfAuthenticated::class,
        ] as $alias => $class) {
            $this->assertSame($class, $aliases[$alias] ?? null, $alias);
        }
    }

    public function test_the_phone_webhook_is_exempt_from_the_forgery_check(): void
    {
        $this->app->make(Kernel::class);
        $middleware = $this->app->make(PreventRequestForgery::class);

        $this->assertContains('zadarma', $middleware->getExcludedPaths());
    }

    public function test_forwarded_headers_are_trusted_from_the_load_balancer(): void
    {
        $this->get('/login', ['X-Forwarded-Proto' => 'https', 'X-Forwarded-For' => '203.0.113.9']);

        $this->assertTrue(request()->isSecure());
        $this->assertSame('203.0.113.9', request()->ip());
    }

    public function test_guests_go_to_the_login_page_and_signed_in_people_to_the_dashboard(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->getJson('/')->assertUnauthorized();
        $this->getJson('/api/orders')->assertUnauthorized();

        $this->actingAs((new User)->forceFill(['id' => 1]))->get('/login')->assertRedirect('/');
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

    public function test_the_applications_providers_are_loaded(): void
    {
        $loaded = array_keys($this->app->getLoadedProviders());

        foreach ([AppServiceProvider::class, \App\Providers\AuthServiceProvider::class] as $provider) {
            $this->assertContains($provider, $loaded);
        }
        // The verification email listener is registered once, not once per events provider.
        $this->assertCount(1, Event::getRawListeners()[Registered::class] ?? []);
    }
}
