<?php

namespace Tests\Feature\Http\Controllers;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesAuthenticationSchema;
use Tests\TestCase;

class DriverRouteOrdersTest extends TestCase
{
    use CreatesAuthenticationSchema;

    private User $driver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticationSchema();
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('user');
            $table->integer('pharmacy_id')->nullable();
            $table->integer('isactive')->default(1);
            $table->integer('isblocked')->default(0);
            $table->string('device_token')->nullable();
        });
        Schema::create('pharmacys', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('isactive')->default(1);
            $table->integer('isblocked')->default(0);
        });
        Schema::create('orders', function (Blueprint $table): void {
            $table->increments('id');
            foreach (['driver_id', 'pharmacy_id', 'user_id', 'statuse_id', 'ready'] as $column) {
                $table->integer($column)->nullable();
            }
        });
        Schema::create('offices', function (Blueprint $table): void {
            $table->increments('id');
        });
        Schema::create('routes_priority', function (Blueprint $table): void {
            $table->increments('id');
            foreach (['driver_id', 'order_id', 'type_id', 'priority'] as $column) {
                $table->integer($column);
            }
            $table->string('type');
            $table->integer('type_pay')->default(4);
            $table->decimal('pay_value')->default(-1);
        });
        Schema::create('locations', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('location');
        });
        config(['services.fcm.credentials' => null, 'app.twilio_sid' => null]);
        $this->withSession(['user_2fa' => true]);
    }

    private function createRoute(): void
    {
        DB::table('pharmacys')->insert(['id' => 2]);
        DB::table('offices')->insert(['id' => 1]);
        $this->driver = User::factory()->state(['isactive' => 1, 'isblocked' => 0])->create(['role' => 'driver', 'pharmacy_id' => 2]);
        DB::table('orders')->insert([
            ['id' => 4, 'driver_id' => $this->driver->id, 'pharmacy_id' => 2, 'user_id' => 20, 'statuse_id' => 3, 'ready' => 2],
            ['id' => 5, 'driver_id' => null, 'pharmacy_id' => 2, 'user_id' => 21, 'statuse_id' => 7, 'ready' => 1],
        ]);
        DB::table('routes_priority')->insert([
            'driver_id' => $this->driver->id, 'order_id' => 4, 'type' => 'patient', 'type_id' => 20,
            'priority' => 1, 'type_pay' => 4, 'pay_value' => -1,
        ]);
    }

    private function form(array $overrides = []): array
    {
        return array_replace([
            'order_id' => ['4', '4,5', ''], 'type' => ['pharmacy', 'office', ''], 'type_id' => ['2', '1', ''],
            'type_pay' => 4, 'pay_value' => 90,
        ], $overrides);
    }

    public function test_dispatch_saves_selected_orders_under_the_office_without_a_map_request(): void
    {
        $this->createRoute();

        $this->actingAs(User::factory()->state(['isactive' => 1, 'isblocked' => 0])->create(['role' => 'dispadmin']))
            ->post('/routes-list/driver/'.$this->driver->id, $this->form())->assertRedirect('/routes-list/driver/'.$this->driver->id)->assertSessionHasNoErrors();

        $this->assertDatabaseCount('routes_priority', 3);
        foreach ([4, 5] as $orderId) {
            $this->assertDatabaseHas('routes_priority', ['driver_id' => $this->driver->id, 'order_id' => $orderId, 'type' => 'office', 'type_id' => 1, 'priority' => 2]);
            $this->assertDatabaseHas('orders', ['id' => $orderId, 'driver_id' => $this->driver->id, 'ready' => 2]);
        }
        $this->assertDatabaseMissing('routes_priority', ['type' => 'patient']);
        Http::assertNothingSent();
    }

    public function test_an_empty_office_selection_preserves_the_existing_route(): void
    {
        $this->createRoute();

        $this->actingAs(User::factory()->state(['isactive' => 1, 'isblocked' => 0])->create(['role' => 'admin']))->post('/routes-list/driver/'.$this->driver->id, $this->form([
            'order_id' => ['', '4', ''], 'type' => ['office', 'pharmacy', ''], 'type_id' => ['1', '2', ''],
        ]))->assertSessionHasErrors(['order_id.0' => 'Select at least one order for each office stop before saving.']);

        $this->assertDatabaseCount('routes_priority', 1);
        $this->assertDatabaseHas('routes_priority', ['order_id' => 4, 'type' => 'patient', 'type_id' => 20]);
        $this->assertDatabaseHas('orders', ['id' => 4, 'driver_id' => $this->driver->id, 'statuse_id' => 3]);
        Http::assertNothingSent();
    }

    public function test_empty_grid_slots_do_not_shift_office_order_pairings(): void
    {
        $this->createRoute();

        $this->actingAs(User::factory()->state(['isactive' => 1, 'isblocked' => 0])->create(['role' => 'logist']))->post('/routes-list/driver/'.$this->driver->id, $this->form([
            'order_id' => ['', '4', '', '5'], 'type' => ['', 'pharmacy', '', 'office'], 'type_id' => ['', '2', '', '1'],
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseCount('routes_priority', 2);
        $this->assertDatabaseHas('routes_priority', ['order_id' => 4, 'type' => 'pharmacy', 'type_id' => 2, 'priority' => 1]);
        $this->assertDatabaseHas('routes_priority', ['order_id' => 5, 'type' => 'office', 'type_id' => 1, 'priority' => 2]);
    }

    public static function invalidStops(): array
    {
        return [
            'missing arrays' => [['type_id' => null], 'type_id'],
            'mismatched arrays' => [['type_id' => ['2']], 'order_id'],
            'invalid orders' => [['order_id' => ['invalid', '4', '']], 'order_id.0'],
            'deleted order' => [['order_id' => ['999', '4', '']], 'order_id.0'],
            'deleted office' => [['type_id' => ['2', '999', '']], 'type_id.1'],
            'unknown stop type' => [['type' => ['pharmacy', 'unknown', '']], 'type.1'],
        ];
    }

    #[DataProvider('invalidStops')]
    public function test_invalid_route_inputs_do_not_delete_existing_stops(array $payload, string $error): void
    {
        $this->createRoute();

        $this->actingAs(User::factory()->state(['isactive' => 1, 'isblocked' => 0])->create(['role' => 'admin']))
            ->post('/routes-list/driver/'.$this->driver->id, $this->form($payload))->assertSessionHasErrors($error);

        $this->assertDatabaseCount('routes_priority', 1);
        $this->assertDatabaseHas('routes_priority', ['order_id' => 4, 'type' => 'patient', 'type_id' => 20]);
        $this->assertDatabaseHas('orders', ['id' => 4, 'driver_id' => $this->driver->id]);
    }

    public function test_route_write_failure_rolls_back_existing_route_and_assignments(): void
    {
        $this->createRoute();
        DB::statement("CREATE TRIGGER reject_route_insert BEFORE INSERT ON routes_priority BEGIN SELECT RAISE(ABORT, 'Route write failed'); END");

        $this->actingAs(User::factory()->state(['isactive' => 1, 'isblocked' => 0])->create(['role' => 'dispadmin']))
            ->post('/routes-list/driver/'.$this->driver->id, $this->form())->assertServerError();

        $this->assertDatabaseCount('routes_priority', 1);
        $this->assertDatabaseHas('routes_priority', ['order_id' => 4, 'type' => 'patient', 'type_id' => 20]);
        $this->assertDatabaseHas('orders', ['id' => 4, 'driver_id' => $this->driver->id]);
        $this->assertDatabaseHas('orders', ['id' => 5, 'driver_id' => null]);
    }

    public function test_pharmacy_cannot_route_orders_from_another_pharmacy(): void
    {
        $this->createRoute();
        DB::table('orders')->where('id', 5)->update(['pharmacy_id' => 3]);

        $this->actingAs(User::factory()->state(['isactive' => 1, 'isblocked' => 0])->create(['role' => 'medic', 'pharmacy_id' => 2]))
            ->post('/routes-list/driver/'.$this->driver->id, $this->form())->assertForbidden();

        $this->assertDatabaseCount('routes_priority', 1);
        $this->assertDatabaseHas('orders', ['id' => 5, 'driver_id' => null]);
    }

    public function test_pharmacy_can_save_its_own_drivers_office_stops(): void
    {
        $this->createRoute();

        $this->actingAs(User::factory()->state(['isactive' => 1, 'isblocked' => 0])->create(['role' => 'medic', 'pharmacy_id' => 2]))
            ->post('/routes-list/driver/'.$this->driver->id, $this->form())->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('routes_priority', ['order_id' => 4, 'type' => 'office', 'type_id' => 1, 'driver_id' => $this->driver->id]);
        $this->assertDatabaseHas('routes_priority', ['order_id' => 5, 'type' => 'office', 'type_id' => 1, 'driver_id' => $this->driver->id]);
    }

    public function test_route_view_loads_the_selector_fix_and_explains_missing_office_orders(): void
    {
        $this->createRoute();
        $this->actingAs(User::factory()->state(['isactive' => 1, 'isblocked' => 0])->create(['role' => 'dispadmin']));
        $directory = sys_get_temp_dir().'/quikmedix-office-view-'.bin2hex(random_bytes(6));
        mkdir($directory.'/layouts', 0700, true);
        file_put_contents($directory.'/layouts/master.blade.php', "@yield('headerCss')\n@yield('content')\n@yield('footerScript')");
        app('view')->getFinder()->prependLocation($directory);

        try {
            $this->view('routes.driver', [
                'driver' => (object) ['id' => $this->driver->id, 'name' => 'Test', 'last_name' => 'Driver', 'email' => 'driver@example.org', 'phone' => '555-0100', 'isactive' => 1, 'isblocked' => 0, 'transport' => 1, 'image' => null, 'eta' => 0, 'distance' => 0],
                'locations' => null, 'orders' => collect(), 'pharmacys' => collect(), 'pharmacys_list' => collect(),
                'delivery_times' => collect(), 'order_statuses' => collect(), 'offices' => collect(),
                'routes_priority' => [(object) ['order_id' => '4', 'type' => 'office', 'type_id' => 1, 'eta' => 0]],
                'orders_id' => collect([(object) ['order_id' => 4]]), 'patient_routes_priority' => [], 'pharmacy_routes_priority' => [],
                'filter' => ['start' => '', 'end' => '', 'pharmacy' => [], 'status' => [], 'delivery_time' => []], 'type_pay' => 4, 'pay_value' => -1, 'patients_locations' => [], 'pharmacy_locations' => [],
                'show_ids' => [], 'show_priority' => [], 'errors' => new \Illuminate\Support\ViewErrorBag,
            ])->assertSee('/js/route-order-select.js', false)
                ->assertSee('window.routeOrderSelect.options', false)
                ->assertSee('window.routeOrderSelect.updateOfficeId(ids)', false)
                ->assertSee('active-yellow office1', false)
                ->assertSee('Select the order numbers at each office stop before saving.');
        } finally {
            unlink($directory.'/layouts/master.blade.php');
            rmdir($directory.'/layouts');
            rmdir($directory);
        }
    }

    public function test_route_save_requires_login_and_dispatch_or_pharmacy_permission(): void
    {
        $this->createRoute();
        $this->post('/routes-list/driver/'.$this->driver->id, $this->form())->assertRedirectToRoute('login');
        $this->actingAs($this->driver)->post('/routes-list/driver/'.$this->driver->id, $this->form())->assertForbidden();

        $this->assertDatabaseCount('routes_priority', 1);
    }
}
