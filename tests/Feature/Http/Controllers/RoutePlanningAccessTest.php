<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\LexaAdmin;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesAuthenticationSchema;
use Tests\TestCase;

/**
 * Who may open the route planning pages: the ready-order lists and a driver's route.
 * Saving a route is covered by DriverRouteOrdersTest and OrderAccessTest.
 */
class RoutePlanningAccessTest extends TestCase
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
    }

    private function signIn(string $role, array $attributes = []): void
    {
        $account = User::factory()->create($attributes + ['role' => $role, 'isactive' => 1, 'isblocked' => 0])->fresh();
        $this->actingAs($account)->withSession(['user_2fa' => $account->id]);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function pages(): array
    {
        return [
            'ready orders' => ['get', '/routes-list'],
            'drivers' => ['get', '/routes-drivers'],
            'ready orders by pharmacy' => ['get', '/routes-pharmacys'],
            'order' => ['get', '/routes-list/show/1'],
            'assign order' => ['post', '/routes-list/show/1'],
            'driver route' => ['get', '/routes-list/driver/1'],
            'save driver route' => ['post', '/routes-list/driver/1'],
        ];
    }

    #[DataProvider('pages')]
    public function test_guest_is_sent_to_login(string $method, string $uri): void
    {
        $this->{$method}($uri)->assertRedirectToRoute('login');
    }

    /**
     * @return array<string, array{string, string, list<string>}>
     */
    public static function refusedRoles(): array
    {
        $others = ['sale', 'driver', 'user', 'facility'];

        return [
            'ready orders' => ['get', '/routes-list', $others],
            'drivers' => ['get', '/routes-drivers', $others],
            // Pharmacy staff see their own orders and drivers, not the list across pharmacies.
            'ready orders by pharmacy' => ['get', '/routes-pharmacys', [...$others, 'medic']],
            'driver route' => ['get', '/routes-list/driver/1', $others],
            'save driver route' => ['post', '/routes-list/driver/1', $others],
        ];
    }

    /**
     * @param list<string> $roles
     */
    #[DataProvider('refusedRoles')]
    public function test_other_roles_are_refused(string $method, string $uri, array $roles): void
    {
        foreach ($roles as $role) {
            $this->signIn($role);
            $this->{$method}($uri)->assertForbidden()->assertSee(LexaAdmin::$err_perm);
        }
    }

    /**
     * @param list<string> $roles
     */
    #[DataProvider('refusedRoles')]
    public function test_blocked_staff_are_stopped_first(string $method, string $uri, array $roles): void
    {
        $this->signIn('admin', ['isblocked' => 1]);

        $this->{$method}($uri)->assertForbidden()->assertSee(LexaAdmin::$err_act_ban);
    }
}
