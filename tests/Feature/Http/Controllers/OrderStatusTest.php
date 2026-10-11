<?php

namespace Tests\Feature\Http\Controllers;

use App\Actions\SendOrderStatusToBestRx;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesAuthenticationSchema;
use Tests\TestCase;

class OrderStatusTest extends TestCase
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
            $table->integer('primary_address')->default(1);
            foreach (['address', 'zip', 'apartment', 'location', 'address2', 'zip2', 'apartment2', 'location2', 'device_token'] as $column) {
                $table->string($column)->nullable();
            }
        });
        foreach (['pharmacys', 'plans'] as $name) {
            Schema::create($name, function (Blueprint $table): void {
                $table->increments('id');
                $table->integer('plan_id')->nullable();
                $table->integer('isactive')->default(1);
                $table->integer('isblocked')->default(0);
                foreach (['tariff', 'tariff_area2', 'tariff_area3', 'tariff_area_more', 'tariff_next_day', 'tariff_same_day', 'tariff_asap', 'tariff_after_hours', 'tariff_fridge'] as $column) {
                    $table->decimal($column)->nullable();
                }
            });
        }
        Schema::create('statuses', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
        });
        Schema::create('orders', function (Blueprint $table): void {
            $table->increments('id');
            foreach (['pharmacy_id', 'user_id', 'driver_id', 'statuse_id', 'type_driver', 'delivery_method_id', 'delivery_time_id', 'fridge', 'count_bags', 'family_id'] as $column) {
                $table->integer($column)->nullable();
            }
            $table->decimal('extra_charge_driver')->default(0);
            $table->decimal('tariff')->nullable();
            $table->decimal('copay')->default(0);
            foreach (['delivery_address', 'delivery_location', 'bestrx_order_id', 'statuse_copay', 'special_instructions', 'delivery_time_range'] as $column) {
                $table->string($column)->nullable();
            }
            $table->date('delivery_date')->nullable();
            $table->timestamp('finish')->nullable();
        });
        Schema::create('pharmacy_areas', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('pharmacy_id');
            $table->integer('area_id');
            $table->integer('type');
        });
        Schema::create('routes_priority', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('order_id');
        });
        Schema::create('rxs', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('order_id');
            $table->string('rx_id')->nullable();
            $table->date('rx_date')->nullable();
            $table->integer('rx_count')->nullable();
            $table->integer('rx_recipient')->nullable();
            $table->decimal('rx_copay')->nullable();
        });
        Schema::create('medicine', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('order_id');
        });
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('setex')->andReturn(true);
        config(['services.fcm.server_key' => null]);
        $this->withSession(['user_2fa' => true]);
    }

    private function createOrder(array $attributes = []): void
    {
        DB::table('statuses')->insert([
            ['id' => 1, 'name' => 'New'], ['id' => 3, 'name' => 'On the way'],
            ['id' => 4, 'name' => 'Delivered'], ['id' => 5, 'name' => 'Canceled'], ['id' => 7, 'name' => 'Office'],
        ]);
        DB::table('plans')->insert(['id' => 1, 'tariff_area_more' => 8, 'tariff_same_day' => 2, 'tariff_fridge' => 3]);
        DB::table('pharmacys')->insert(['id' => 2, 'plan_id' => 1, 'tariff_same_day' => 4]);
        $patient = User::factory()->state(['isactive' => 1, 'isblocked' => 0])->create(['role' => 'user', 'pharmacy_id' => 2, 'address' => '12 Test St', 'zip' => '00001', 'apartment' => '1', 'location' => '40,-73']);
        DB::table('orders')->insert(array_merge([
            'id' => 20, 'pharmacy_id' => 2, 'user_id' => $patient->id, 'statuse_id' => 1,
            'type_driver' => 1, 'delivery_method_id' => 1, 'delivery_time_id' => 2, 'fridge' => 1,
            'extra_charge_driver' => 1.5, 'count_bags' => 2,
        ], $attributes));
    }

    public static function statusPermissions(): array
    {
        return [['superadmin', true], ['admin', true], ['dispadmin', true], ['logist', true], ['medic', false], ['driver', false], ['user', false], ['sale', false]];
    }

    #[DataProvider('statusPermissions')]
    public function test_only_operations_staff_can_change_any_status(string $role, bool $allowed): void
    {
        $this->assertSame($allowed, Gate::forUser(new User(['role' => $role]))->allows('change-order-status'));
    }

    public static function activeStatuses(): array
    {
        return [['logist', 7], ['admin', 3], ['dispadmin', 5]];
    }

    #[DataProvider('activeStatuses')]
    public function test_staff_can_change_status_without_editing_order_contents(string $role, int $status): void
    {
        $this->createOrder();
        DB::table('rxs')->insert(['order_id' => 20, 'rx_id' => 'RX100-0']);
        $staff = User::factory()->state(['isactive' => 1, 'isblocked' => 0])->create(['role' => $role]);

        $this->actingAs($staff)->post('/orders/2/status/20', ['statuse' => $status])
            ->assertRedirect('/orders/2/show/20')->assertSessionHas('success', 'Order status updated.');

        $this->assertDatabaseHas('orders', ['id' => 20, 'statuse_id' => $status, 'count_bags' => 2, 'finish' => null]);
        $this->assertDatabaseHas('rxs', ['order_id' => 20, 'rx_id' => 'RX100-0']);
    }

    public function test_delivered_sets_completion_details_and_removes_only_this_orders_route(): void
    {
        $this->createOrder(['bestrx_order_id' => 'test-external-order']);
        $this->travelTo(now()->setDate(2026, 9, 29)->setTime(12, 0));
        DB::table('routes_priority')->insert([['order_id' => 20], ['order_id' => 21]]);
        $this->mock(SendOrderStatusToBestRx::class)->shouldReceive('handle')->once()->with(20)
            ->andReturnUsing(function (): bool {
                $this->assertDatabaseHas('orders', ['id' => 20, 'statuse_id' => 4, 'finish' => '2026-09-29 12:00:00']);
                return true;
            });

        $this->actingAs(User::factory()->state(['isactive' => 1, 'isblocked' => 0])->create(['role' => 'admin']))->post('/orders/2/status/20', ['statuse' => 4])->assertRedirect();

        $this->assertDatabaseHas('orders', ['id' => 20, 'statuse_id' => 4, 'finish' => '2026-09-29 12:00:00', 'tariff' => 16.5, 'delivery_address' => '12 Test St, 00001, Apt 1', 'delivery_location' => '40,-73']);
        $this->assertDatabaseMissing('routes_priority', ['order_id' => 20]);
        $this->assertDatabaseHas('routes_priority', ['order_id' => 21]);
    }

    public function test_reopening_clears_completion_date_and_saving_unchanged_status_does_not_redeliver(): void
    {
        $this->createOrder(['statuse_id' => 4, 'finish' => '2026-09-28 12:00:00', 'tariff' => 10]);
        $staff = User::factory()->state(['isactive' => 1, 'isblocked' => 0])->create(['role' => 'admin']);
        $this->mock(SendOrderStatusToBestRx::class)->shouldNotReceive('handle');

        $this->actingAs($staff)->post('/orders/2/status/20', ['statuse' => 4])->assertRedirect();
        $this->assertDatabaseHas('orders', ['id' => 20, 'finish' => '2026-09-28 12:00:00', 'tariff' => 10]);
        $this->post('/orders/2/status/20', ['statuse' => 7])->assertRedirect();
        $this->assertDatabaseHas('orders', ['id' => 20, 'statuse_id' => 7, 'finish' => null]);
    }

    public static function invalidStatuses(): array
    {
        return [[null, 'Choose an order status.'], [999, 'Choose a valid order status.'], ['invalid', 'Choose a valid order status.']];
    }

    #[DataProvider('invalidStatuses')]
    public function test_invalid_status_is_rejected_without_changing_order(mixed $status, string $message): void
    {
        $this->createOrder();

        $this->actingAs(User::factory()->state(['isactive' => 1, 'isblocked' => 0])->create(['role' => 'admin']))->from('/orders/2/show/20')
            ->post('/orders/2/status/20', ['statuse' => $status])->assertRedirect('/orders/2/show/20')->assertSessionHasErrors(['statuse' => $message]);

        $this->assertDatabaseHas('orders', ['id' => 20, 'statuse_id' => 1]);
    }

    public function test_status_update_requires_login_and_rejects_patient_and_pharmacy_roles(): void
    {
        $this->createOrder();
        $this->post('/orders/2/status/20', ['statuse' => 7])->assertRedirectToRoute('login');
        $this->actingAs(User::factory()->state(['isactive' => 1, 'isblocked' => 0])->create(['role' => 'medic', 'pharmacy_id' => 3]))
            ->post('/orders/2/status/20', ['statuse' => 7])->assertForbidden();
        $this->actingAs(User::factory()->state(['isactive' => 1, 'isblocked' => 0])->create(['role' => 'user', 'pharmacy_id' => 2]))
            ->post('/orders/2/status/20', ['statuse' => 7])->assertForbidden();
        $this->assertDatabaseHas('orders', ['id' => 20, 'statuse_id' => 1]);
    }

    public function test_missing_order_or_mismatched_pharmacy_is_not_found(): void
    {
        $this->createOrder();
        $this->actingAs(User::factory()->state(['isactive' => 1, 'isblocked' => 0])->create(['role' => 'admin']))
            ->post('/orders/3/status/20', ['statuse' => 7])->assertNotFound();
        $this->post('/orders/2/status/999', ['statuse' => 7])->assertNotFound();
        $this->assertDatabaseHas('orders', ['id' => 20, 'statuse_id' => 1]);
    }

    public function test_failed_completion_rolls_back_status_and_keeps_route(): void
    {
        $this->createOrder(['delivery_time_id' => 99]);
        DB::table('routes_priority')->insert(['order_id' => 20]);

        $this->actingAs(User::factory()->state(['isactive' => 1, 'isblocked' => 0])->create(['role' => 'admin']))->post('/orders/2/status/20', ['statuse' => 4])->assertServerError();

        $this->assertDatabaseHas('orders', ['id' => 20, 'statuse_id' => 1, 'finish' => null]);
        $this->assertDatabaseHas('routes_priority', ['order_id' => 20]);
    }

    public static function editPaths(): array
    {
        return [['edit'], ['facilitys_edit']];
    }

    #[DataProvider('editPaths')]
    public function test_editing_an_order_with_a_disabled_status_preserves_status_and_bags(string $path): void
    {
        $this->createOrder(['statuse_id' => 7]);
        DB::connection()->getPdo()->sqliteCreateFunction('CURDATE', fn (): string => '2026-09-29');
        $pharmacyUser = User::factory()->state(['isactive' => 1, 'isblocked' => 0])->create(['role' => 'medic', 'pharmacy_id' => 2]);

        $this->actingAs($pharmacyUser)->post('/orders/2/'.$path.'/20', [
            'save' => 1, 'delivery_method' => 1, 'delivery_time' => 2, 'type_driver' => 1,
            'rx_id' => ['RX100'], 'rf_id' => ['0'], 'rx_count' => [1], 'rx_date' => ['2026-09-29'],
        ])->assertRedirect('/orders/2')->assertSessionHasNoErrors();

        $this->assertDatabaseHas('orders', ['id' => 20, 'statuse_id' => 7, 'count_bags' => 2, 'finish' => null]);
        $this->assertDatabaseHas('rxs', ['order_id' => 20, 'rx_id' => 'RX100-0']);
    }

    #[DataProvider('editPaths')]
    public function test_pharmacy_users_cannot_bypass_the_status_control_through_edit(string $path): void
    {
        $this->createOrder(['statuse_id' => 7]);
        DB::table('rxs')->insert(['order_id' => 20, 'rx_id' => 'RX100-0']);
        $pharmacyUser = User::factory()->create(['role' => 'medic', 'pharmacy_id' => 2, 'isactive' => 1, 'isblocked' => 0]);

        $this->actingAs($pharmacyUser)->post('/orders/2/'.$path.'/20', ['save' => 1, 'statuse' => 4])->assertForbidden();

        $this->assertDatabaseHas('orders', ['id' => 20, 'statuse_id' => 7]);
        $this->assertDatabaseHas('rxs', ['order_id' => 20, 'rx_id' => 'RX100-0']);
    }

    #[DataProvider('editPaths')]
    public function test_empty_status_and_invalid_bags_are_rejected_before_editing_order(string $path): void
    {
        $this->createOrder();
        DB::table('rxs')->insert(['order_id' => 20, 'rx_id' => 'RX100-0']);
        $staff = User::factory()->create(['role' => 'superadmin', 'isactive' => 1, 'isblocked' => 0]);

        $this->actingAs($staff)->post('/orders/2/'.$path.'/20', ['save' => 1, 'statuse' => '', 'count_bags' => 0])
            ->assertSessionHasErrors(['statuse', 'count_bags']);

        $this->assertDatabaseHas('orders', ['id' => 20, 'statuse_id' => 1, 'count_bags' => 2]);
        $this->assertDatabaseHas('rxs', ['order_id' => 20, 'rx_id' => 'RX100-0']);
    }

    public function test_staff_can_restore_a_missing_status_on_a_legacy_order(): void
    {
        $this->createOrder(['statuse_id' => null]);

        $this->actingAs(User::factory()->create(['role' => 'superadmin', 'isactive' => 1, 'isblocked' => 0]))
            ->post('/orders/2/status/20', ['statuse' => 7, 'count_bags' => 9, 'pharmacy_id' => 3])->assertRedirect('/orders/2/show/20');

        $this->assertDatabaseHas('orders', ['id' => 20, 'statuse_id' => 7, 'count_bags' => 2, 'pharmacy_id' => 2]);
    }

    public function test_tracking_panel_shows_current_status_qr_codes_and_staff_controls(): void
    {
        $this->actingAs(User::factory()->state(['isactive' => 1, 'isblocked' => 0])->create(['role' => 'logist']));
        $this->view('orders.tracking', [
            'errors' => new \Illuminate\Support\ViewErrorBag,
            'order' => (object) ['id' => 20, 'pharmacy_id' => 2, 'statuse_id' => 3, 'statusename' => 'On the way', 'count_bags' => 2],
            'statuses' => [(object) ['id' => 1, 'name' => 'New'], (object) ['id' => 7, 'name' => 'Office'], (object) ['id' => 3, 'name' => 'On the way'], (object) ['id' => 4, 'name' => 'Delivered']],
        ])->assertSee('Current status:')->assertSee('Ready for pickup')->assertSee('On the way')->assertSee('Hub')->assertDontSee('Warehouse')->assertDontSee('Office')->assertSee('Delivered')
            ->assertSee('Update status')->assertSee('QR code for order 20, bag 2')->assertSee('Print QR labels');
    }

    public function test_pharmacy_users_see_unknown_status_and_labels_without_staff_controls(): void
    {
        $this->actingAs(User::factory()->state(['isactive' => 1, 'isblocked' => 0])->create(['role' => 'medic', 'pharmacy_id' => 2]));
        $this->view('orders.tracking', [
            'errors' => new \Illuminate\Support\ViewErrorBag,
            'order' => (object) ['id' => 20, 'pharmacy_id' => 2, 'statuse_id' => null, 'statusename' => null, 'count_bags' => null],
            'statuses' => [],
        ])->assertSee('Unknown status')->assertSee('QR code for order 20, bag 1')->assertDontSee('Update status');
    }

    public static function displayedStatuses(): array
    {
        return [
            'new pharmacy order' => [1, 'New', 'Ready for pickup'],
            'legacy ready placeholder' => ['1', 'Status 1 (dev)', 'Ready for pickup'],
            'hub check-in' => [7, 'Office', 'Hub'],
            'hub placeholder' => ['7', 'Status 7 (dev)', 'Hub'],
            'hub check-out' => [3, 'Status 3 (dev)', 'On the way'],
            'configured status' => [4, 'Delivered', 'Delivered'],
            'custom status' => [11, 'Awaiting review', 'Awaiting review'],
            'missing status' => [null, null, 'Unknown status'],
            'blank unknown status' => [99, ' ', 'Unknown status'],
            'escaped configured status' => [11, '<script>alert(1)</script>', '<script>alert(1)</script>'],
        ];
    }

    #[DataProvider('displayedStatuses')]
    public function test_order_badges_use_confirmed_labels_and_preserve_other_configured_statuses(int|string|null $id, ?string $name, string $expected): void
    {
        $this->blade('<x-order-status :status-id="$id" :name="$name" color="warning" />', compact('id', 'name'))
            ->assertSee($expected)->assertSee('data-tone="warning"', false);
    }
}
