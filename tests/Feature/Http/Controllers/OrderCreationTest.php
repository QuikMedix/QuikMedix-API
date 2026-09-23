<?php

namespace Tests\Feature\Http\Controllers;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CreatesAuthenticationSchema;
use Tests\TestCase;

class OrderCreationTest extends TestCase
{
    use CreatesAuthenticationSchema;

    private User $pharmacyAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticationSchema();
        Schema::table('users', function (Blueprint $table): void {
            $table->string('last_name')->nullable();
            $table->string('role')->default('user');
            $table->integer('isactive')->default(1);
            $table->integer('isblocked')->default(0);
            $table->integer('pharmacy_id')->nullable();
            $table->integer('primary_address')->default(1);
            foreach (['address', 'apartment', 'zip', 'device_token'] as $column) {
                $table->string($column)->nullable();
            }
        });
        Schema::create('pharmacys', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            $table->integer('isactive')->default(1);
            $table->integer('isblocked')->default(0);
        });
        foreach (['delivery_methods', 'delivery_times'] as $lookup) {
            Schema::create($lookup, function (Blueprint $table): void {
                $table->increments('id');
                $table->string('name');
            });
        }
        Schema::create('orders', function (Blueprint $table): void {
            $table->increments('id');
            foreach (['pharmacy_id', 'medic_id', 'driver_id', 'user_id', 'delivery_method_id', 'delivery_time_id', 'count_bags', 'type_driver', 'family_id'] as $column) {
                $table->integer($column)->nullable();
            }
            $table->boolean('facility')->default(false);
            $table->decimal('copay')->default(0);
            $table->string('statuse_copay')->nullable();
            $table->string('fridge')->nullable();
            $table->decimal('extra_charge_driver')->default(0);
            $table->string('delivery_time_range')->nullable();
            $table->date('delivery_date')->nullable();
            $table->text('special_instructions')->nullable();
        });
        Schema::create('rxs', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('order_id');
            $table->string('rx_id', 20)->nullable();
            $table->date('rx_date')->nullable();
            $table->integer('rx_count')->nullable();
            $table->integer('rx_recipient')->nullable();
        });

        DB::table('pharmacys')->insert([['id' => 2, 'name' => 'Second Pharmacy'], ['id' => 3, 'name' => 'Other Pharmacy']]);
        DB::table('delivery_methods')->insert(['id' => 1, 'name' => 'Method']);
        DB::table('delivery_times')->insert([['id' => 1, 'name' => 'Next day'], ['id' => 2, 'name' => 'Same day']]);
        $this->pharmacyAdmin = User::factory()->create(['role' => 'medic', 'pharmacy_id' => 2, 'isactive' => 1, 'isblocked' => 0]);
        User::factory()->create(['id' => 40, 'role' => 'user', 'pharmacy_id' => 2, 'address' => '1 Wall St']);
        User::factory()->create(['id' => 41, 'role' => 'user', 'pharmacy_id' => 3]);

        // The legacy balance/ban checks cache in Redis; keep tests off any real server.
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('setex')->andReturn(true);
    }

    private function order(array $overrides = []): array
    {
        return array_merge([
            'save' => 1, 'user' => 40, 'count_bags' => 1, 'delivery_method' => 1, 'delivery_time' => 2,
            'type_driver' => 1, 'rx_id' => ['RX100001'], 'rf_id' => ['0'], 'rx_count' => [1], 'rx_date' => ['2026-09-22'],
        ], $overrides);
    }

    public function test_creates_the_order_and_its_prescriptions_together(): void
    {
        Carbon::setTestNow('2026-09-22 21:30:00'); // evening in New York, already the 23rd in UTC

        $this->actingAs($this->pharmacyAdmin)->post('/orders/2/add', $this->order())
            ->assertRedirect('/orders/2?added=1')->assertSessionHasNoErrors();

        $order = DB::table('orders')->first();
        $this->assertSame(40, (int) $order->user_id);
        $this->assertSame('2026-09-22', $order->delivery_date);
        $this->assertSame(['RX100001-0'], DB::table('rxs')->where('order_id', $order->id)->pluck('rx_id')->all());
    }

    public function test_missing_fields_are_reported_and_nothing_is_saved(): void
    {
        $this->actingAs($this->pharmacyAdmin)->from('/orders/2/add')
            ->post('/orders/2/add', ['save' => 1, 'rx_id' => [''], 'rf_id' => ['x'], 'rx_count' => [1]])
            ->assertRedirect('/orders/2/add')
            ->assertSessionHasErrors([
                'user' => 'Choose a customer.',
                'delivery_method' => 'Choose a delivery option.',
                'delivery_time' => 'Choose a preferred delivery time.',
                'rx_id.0' => 'Every RX row needs an RX#.',
                'rf_id.0' => 'Rf# must be a number of up to 4 digits.',
            ]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('rxs', 0);
    }

    public function test_rejects_a_customer_from_another_pharmacy_and_an_rx_that_would_be_truncated(): void
    {
        $this->actingAs($this->pharmacyAdmin)
            ->post('/orders/2/add', $this->order(['user' => 41, 'rx_id' => [str_repeat('9', 16)]]))
            ->assertSessionHasErrors([
                'user' => 'The selected customer does not belong to this pharmacy.',
                'rx_id.0' => 'RX# can be at most 15 characters.',
            ]);

        $this->assertDatabaseCount('rxs', 0);
    }
}
