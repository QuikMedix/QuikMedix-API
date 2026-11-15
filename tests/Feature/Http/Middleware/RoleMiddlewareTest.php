<?php

namespace Tests\Feature\Http\Middleware;

use App\Support\AccessMessages;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CreatesAuthenticationSchema;
use App\Enums\Role;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

/**
 * `role:` and `allowed:` on a route decide who may use it, after `auth`, `2fa` and `active`.
 */
class RoleMiddlewareTest extends TestCase
{
    use CreatesAuthenticationSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticationSchema();
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('user');
            $table->integer('isactive')->default(1);
            $table->integer('isblocked')->default(0);
            $table->integer('pharmacy_id')->nullable();
        });
        Schema::create('pharmacys', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('isactive')->default(1);
            $table->integer('isblocked')->default(0);
        });
        Route::middleware(['web', 'auth', '2fa', 'active', 'role:superadmin,logist'])->get('/_test/by-role', fn () => 'ok');
        Route::middleware(['web', 'auth', '2fa', 'active', 'allowed:admin'])->get('/_test/by-ability', fn () => 'ok');
    }

    private function signIn(string $role, array $attributes = []): void
    {
        $account = User::factory()->create($attributes + ['role' => $role, 'isactive' => 1, 'isblocked' => 0])->fresh();
        $this->actingAs($account)->withSession(['user_2fa' => $account->id]);
    }

    public function test_listed_roles_pass_and_others_see_the_permission_message(): void
    {
        foreach (['superadmin', 'logist'] as $role) {
            $this->signIn($role);
            $this->get('/_test/by-role')->assertOk()->assertSee('ok');
        }
        foreach (['admin', 'dispadmin', 'medic', 'driver', 'user'] as $role) {
            $this->signIn($role);
            $this->get('/_test/by-role')->assertForbidden()->assertSee(AccessMessages::NO_PERMISSION);
        }
    }

    public function test_an_ability_is_checked_as_the_application_defines_it(): void
    {
        foreach (['superadmin', 'admin', 'dispadmin'] as $role) {
            $this->signIn($role);
            $this->get('/_test/by-ability')->assertOk();
        }
        foreach (['logist', 'medic', 'driver', 'user'] as $role) {
            $this->signIn($role);
            $this->get('/_test/by-ability')->assertForbidden()->assertSee(AccessMessages::NO_PERMISSION);
        }
    }

    public function test_guests_are_sent_to_login_and_blocked_accounts_are_told_so_before_the_role_matters(): void
    {
        $this->get('/_test/by-role')->assertRedirectToRoute('login');

        $this->signIn('driver', ['isblocked' => 1]);
        $this->get('/_test/by-role')->assertForbidden()->assertSee(AccessMessages::ACCOUNT_INACTIVE);
        $this->signIn('superadmin', ['isblocked' => 1]);
        $this->get('/_test/by-role')->assertForbidden()->assertSee(AccessMessages::ACCOUNT_INACTIVE);
    }

    public function test_the_role_enum_is_the_one_list_of_roles(): void
    {
        $this->assertSame(['superadmin', 'admin', 'dispadmin', 'logist', 'sale', 'medic', 'driver', 'user', 'facility'], Role::values());
        $this->assertSame(['superadmin', 'admin', 'dispadmin'], Role::adminValues());
        $this->assertTrue(Role::DispatchAdmin->isAdmin());
        $this->assertFalse(Role::Logist->isAdmin());
        $this->assertTrue((new User)->forceFill(['role' => 'dispadmin'])->isAdmin());
        $this->assertFalse((new User)->forceFill(['role' => 'no-such-role'])->isAdmin());
    }

    public function test_a_misspelled_role_on_a_route_is_an_error_not_a_refusal(): void
    {
        $request = Request::create('/x');
        $request->setUserResolver(fn () => (new User)->forceFill(['role' => 'admin']));

        $this->expectException(\ValueError::class);
        (new EnsureUserHasRole)->handle($request, fn () => new Response('ok'), 'admin', 'admn');
    }

    public function test_every_role_named_on_a_route_exists(): void
    {
        $named = collect(Route::getRoutes()->getRoutes())
            ->flatMap(fn ($route) => $route->gatherMiddleware())
            ->filter(fn ($middleware) => is_string($middleware) && str_starts_with($middleware, 'role:'))
            ->flatMap(fn (string $middleware) => explode(',', substr($middleware, 5)))
            ->unique();

        $this->assertGreaterThan(5, $named->count());
        $this->assertSame([], $named->reject(fn (string $role) => Role::tryFrom($role) !== null)->values()->all());
    }

    public function test_every_role_named_in_a_has_role_call_exists(): void
    {
        $named = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path('app')));
        $views = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));
        foreach ([$files, $views] as $tree) {
            foreach ($tree as $file) {
                if (! $file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }
                preg_match_all('/->has(?:Any)?Role\(([^()$]*)\)/', (string) file_get_contents($file->getPathname()), $calls);
                foreach ($calls[1] as $arguments) {
                    preg_match_all('/[\'"](\w+)[\'"]/', $arguments, $roles);
                    $named = [...$named, ...$roles[1]];
                }
            }
        }

        $this->assertGreaterThan(100, count($named));
        $this->assertSame([], array_values(array_diff(array_unique($named), Role::values())));
    }
}
