<?php

namespace Tests\Feature\Http\Controllers;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesAuthenticationSchema;
use Tests\TestCase;

class OrderStatusApiTest extends TestCase
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
            $table->string('last_name')->nullable();
            foreach (['address', 'apartment', 'zip', 'location'] as $column) {
                foreach (['', '2', '3'] as $suffix) {
                    $table->string($column.$suffix)->nullable();
                }
            }
        });
        Schema::create('pharmacys', function (Blueprint $table): void {
            $table->increments('id');
            foreach (['name', 'address', 'phone', 'location'] as $column) {
                $table->string($column)->nullable();
            }
            $table->integer('isactive')->default(1);
            $table->integer('isblocked')->default(0);
        });
        foreach (['statuses', 'statuses_copay', 'delivery_methods', 'delivery_times'] as $name) {
            Schema::create($name, function (Blueprint $table): void {
                $table->increments('id');
                $table->string('name');
                $table->string('color')->nullable();
            });
        }
        Schema::create('orders', function (Blueprint $table): void {
            $table->increments('id');
            foreach (['pharmacy_id', 'user_id', 'driver_id', 'statuse_id', 'statuse_copay', 'delivery_method_id', 'delivery_time_id', 'count_bags', 'signature', 'fridge', 'actual'] as $column) {
                $table->integer($column)->nullable();
            }
            foreach (['created', 'finish', 'special_instructions', 'drop_off_photo', 'signature_photo', 'delivery_address', 'delivery_location'] as $column) {
                $table->string($column)->nullable();
            }
            $table->decimal('copay')->default(0);
        });
        Schema::create('medicine', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('order_id');
            $table->integer('count');
        });
        Schema::create('rxs', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('order_id');
        });
        Schema::create('wishes', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('category_id');
            $table->string('text');
        });
        Schema::create('wishes_category', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('status');
        });
    }

    private function createOrders(array $statuses): User
    {
        DB::table('pharmacys')->insert(['id' => 2, 'name' => 'Test Pharmacy']);
        DB::table('delivery_methods')->insert(['id' => 1, 'name' => 'Delivery']);
        DB::table('delivery_times')->insert(['id' => 1, 'name' => 'Next day']);
        DB::table('statuses')->insert([
            ['id' => 1, 'name' => 'New'], ['id' => 3, 'name' => 'Status 3 (dev)'],
            ['id' => 7, 'name' => 'Office'], ['id' => 11, 'name' => 'Awaiting review'],
        ]);
        $patient = User::factory()->create(['role' => 'user', 'pharmacy_id' => 2, 'isactive' => 1, 'isblocked' => 0]);
        foreach ($statuses as $status) {
            DB::table('orders')->insert([
                'id' => $status, 'statuse_id' => $status, 'pharmacy_id' => 2, 'user_id' => $patient->id,
                'delivery_method_id' => 1, 'delivery_time_id' => 1,
            ]);
        }

        return $patient;
    }

    public function test_order_list_and_filters_return_confirmed_labels_without_renaming_lookup_rows(): void
    {
        $patient = $this->createOrders([1, 3, 7, 11]);

        $this->actingAs($patient, 'api')->getJson('/api/orders')
            ->assertJsonPath('orders.0.statusename', 'Awaiting review')
            ->assertJsonPath('orders.1.statusename', 'Hub')
            ->assertJsonPath('orders.2.statusename', 'On the way')
            ->assertJsonPath('orders.3.statusename', 'Ready for pickup')
            ->assertJsonPath('statuses.0.name', 'Ready for pickup')
            ->assertJsonPath('statuses.1.name', 'On the way')
            ->assertJsonPath('statuses.2.name', 'Hub')
            ->assertJsonPath('statuses.3.name', 'Awaiting review');

        $this->assertDatabaseHas('statuses', ['id' => 7, 'name' => 'Office']);
        $this->assertDatabaseHas('statuses', ['id' => 1, 'name' => 'New']);
    }

    public function test_hub_order_details_return_the_same_readable_status(): void
    {
        $patient = $this->createOrders([7]);

        $this->actingAs($patient, 'api')->getJson('/api/orders/show/7')
            ->assertJsonPath('order.statuse_id', 7)->assertJsonPath('order.statusename', 'Hub');
    }

    public function test_patient_home_shows_ready_for_pickup_for_a_new_order(): void
    {
        $patient = $this->createOrders([1]);

        $this->actingAs($patient, 'api')->getJson('/api/patient/home')
            ->assertJsonPath('last_order.status', 'Ready for pickup');
    }

    public static function driverScanStatuses(): array
    {
        return ['hub pickup' => [7, 3], 'delivered order' => [4, 4]];
    }

    #[DataProvider('driverScanStatuses')]
    public function test_assigned_driver_scans_leave_the_hub_without_reopening_delivered_orders(int $status, int $expectedStatus): void
    {
        $this->createOrders([7]);
        $driver = User::factory()->create(['role' => 'driver', 'isactive' => 1, 'isblocked' => 0]);
        DB::table('orders')->where('id', 7)->update(['driver_id' => $driver->id, 'statuse_id' => $status]);
        config(['services.fcm.server_key' => null, 'app.twilio_sid' => null]);

        $this->actingAs($driver, 'api')->postJson('/api/orders/qr_scaned', ['order_id' => '7_1'])
            ->assertJson(['message' => 'Order was changed']);

        $this->assertDatabaseHas('orders', ['id' => 7, 'statuse_id' => $expectedStatus, 'driver_id' => $driver->id]);
    }

    public function test_another_driver_cannot_scan_a_hub_order_out(): void
    {
        $this->createOrders([7]);
        $assignedDriver = User::factory()->create(['role' => 'driver']);
        $otherDriver = User::factory()->create(['role' => 'driver', 'isactive' => 1, 'isblocked' => 0]);
        DB::table('orders')->where('id', 7)->update(['driver_id' => $assignedDriver->id]);

        $this->actingAs($otherDriver, 'api')->postJson('/api/orders/qr_scaned', ['order_id' => '7_1'])
            ->assertBadRequest()->assertJson(['errors' => 'Forbidden']);

        $this->assertDatabaseHas('orders', ['id' => 7, 'statuse_id' => 7, 'driver_id' => $assignedDriver->id]);
    }
}
