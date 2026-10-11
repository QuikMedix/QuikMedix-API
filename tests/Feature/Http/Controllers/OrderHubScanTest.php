<?php

namespace Tests\Feature\Http\Controllers;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CreatesAuthenticationSchema;
use Tests\TestCase;

class OrderHubScanTest extends TestCase
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
            $table->integer('office_id')->nullable();
        });
        Schema::create('orders', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('statuse_id');
            $table->integer('driver_id')->nullable();
            $table->integer('count_bags');
            $table->integer('eta')->nullable();
        });
        Schema::create('packages_transitions', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('order_id');
            $table->integer('office_id');
            $table->integer('driver_id');
            $table->integer('bag');
            $table->string('target');
            $table->timestamp('created')->default('2026-10-09 12:00:00');
        });
        Schema::create('routes_priority', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('order_id');
            $table->integer('driver_id');
            $table->string('type');
            $table->integer('priority');
        });
        DB::connection()->getPdo()->sqliteCreateFunction('CURDATE', fn (): string => '2026-10-09');
        $this->withSession(['user_2fa' => true]);
    }

    public function test_complete_hub_check_in_sets_hub_and_check_out_sets_on_the_way(): void
    {
        $staff = User::factory()->create(['role' => 'admin', 'office_id' => 5]);
        $driver = User::factory()->create(['role' => 'driver']);
        DB::table('orders')->insert(['id' => 20, 'statuse_id' => 3, 'driver_id' => $driver->id, 'count_bags' => 2]);

        $this->actingAs($staff)->postJson('/drivers/qr_order', ['code' => '20_1', 'driver_id' => $driver->id])
            ->assertJson(['bag_added_in' => 20]);

        $this->assertDatabaseHas('orders', ['id' => 20, 'statuse_id' => 3, 'driver_id' => $driver->id]);
        $this->assertDatabaseHas('packages_transitions', ['order_id' => 20, 'office_id' => 5, 'bag' => 1, 'target' => 'in']);

        $this->postJson('/drivers/qr_order', ['code' => '20_2', 'driver_id' => $driver->id])
            ->assertJson(['order_id_in' => 20]);

        $this->assertDatabaseHas('orders', ['id' => 20, 'statuse_id' => 7, 'driver_id' => null]);
        $this->assertDatabaseHas('packages_transitions', ['order_id' => 20, 'office_id' => 5, 'bag' => 2, 'target' => 'in']);

        DB::table('orders')->where('id', 20)->update(['driver_id' => $driver->id]);

        $this->postJson('/drivers/qr_order', ['code' => '20_1', 'driver_id' => $driver->id])
            ->assertJson(['bag_added' => 20]);

        $this->assertDatabaseHas('orders', ['id' => 20, 'statuse_id' => 7, 'driver_id' => $driver->id]);

        $this->postJson('/drivers/qr_order', ['code' => '20_2', 'driver_id' => $driver->id])
            ->assertJson(['order_id' => 20]);

        $this->assertDatabaseHas('orders', ['id' => 20, 'statuse_id' => 3, 'driver_id' => $driver->id]);
        $this->assertDatabaseHas('packages_transitions', ['order_id' => 20, 'office_id' => 5, 'bag' => 1, 'target' => 'out']);
        $this->assertDatabaseHas('packages_transitions', ['order_id' => 20, 'office_id' => 5, 'bag' => 2, 'target' => 'out']);
        $this->assertDatabaseCount('packages_transitions', 4);
    }
}
