<?php

namespace Tests\Feature\Auth;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CreatesAuthenticationSchema;
use Tests\TestCase;

class OrderAccessTest extends TestCase
{
    use CreatesAuthenticationSchema;

    private User $pharmacyAdmin;

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
        Schema::create('orders', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('pharmacy_id');
            $table->integer('driver_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('statuse_id')->default(1);
            $table->string('ready')->default('0');
        });
        Schema::create('rxs', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('order_id');
        });
        DB::table('pharmacys')->insert([['id' => 2], ['id' => 3]]);
        DB::table('orders')->insert([['id' => 20, 'pharmacy_id' => 2], ['id' => 30, 'pharmacy_id' => 3]]);
        DB::table('rxs')->insert(['order_id' => 30]);
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('setex')->andReturn(true);

        $this->pharmacyAdmin = User::factory()->create(['role' => 'medic', 'pharmacy_id' => 2, 'isactive' => 1, 'isblocked' => 0]);
    }

    public function test_pharmacy_users_reach_only_their_own_orders(): void
    {
        $gate = Gate::forUser($this->pharmacyAdmin);

        $this->assertTrue($gate->allows('access-order', 20));
        $this->assertFalse($gate->allows('access-order', 30));
        $this->assertFalse($gate->allows('access-order', [20, 30]), 'every order in a batch must belong to the pharmacy');
        $this->assertFalse($gate->allows('access-order', 999), 'unknown orders are refused');
    }

    public function test_other_roles_keep_their_existing_access(): void
    {
        foreach (['superadmin', 'admin', 'dispadmin', 'logist'] as $role) {
            $this->assertTrue(Gate::forUser(new User(['role' => $role]))->allows('access-order', 30), $role);
        }
    }

    public function test_pharmacy_admin_cannot_delete_or_edit_another_pharmacys_order(): void
    {
        $this->actingAs($this->pharmacyAdmin)->post('/orders/2', ['order_id' => 30, 'remove' => 1])->assertForbidden();
        $this->actingAs($this->pharmacyAdmin)->get('/orders/2/edit/30')->assertForbidden();
        $this->actingAs($this->pharmacyAdmin)->post('/orders/2/edit/30', ['save' => 1])->assertForbidden();

        $this->assertDatabaseHas('orders', ['id' => 30]);
        $this->assertDatabaseHas('rxs', ['order_id' => 30]);
    }

    public function test_pharmacy_admin_cannot_route_other_pharmacies_orders_or_drivers(): void
    {
        $ownDriver = User::factory()->create(['role' => 'driver', 'pharmacy_id' => 2]);
        $otherDriver = User::factory()->create(['role' => 'driver', 'pharmacy_id' => 3]);

        $this->actingAs($this->pharmacyAdmin)->post("/routes-list/driver/{$otherDriver->id}", ['confirm_order_id' => 20])->assertForbidden();
        $this->actingAs($this->pharmacyAdmin)->post("/routes-list/driver/{$ownDriver->id}", ['confirm_order_id' => 30])->assertForbidden();
        $this->actingAs($this->pharmacyAdmin)->post('/routes-list/show/30', ['driver_id' => $ownDriver->id])->assertForbidden();

        $this->assertDatabaseHas('orders', ['id' => 30, 'driver_id' => null]);
    }

    public function test_pharmacy_admin_cannot_print_another_pharmacys_order_ticket(): void
    {
        $this->actingAs($this->pharmacyAdmin)->get('/orders/ticket/print?order_id=30')->assertForbidden();
    }

    public function test_call_recordings_are_limited_to_staff_and_the_orders_own_pharmacy(): void
    {
        $patient = User::factory()->create(['role' => 'user', 'pharmacy_id' => 3, 'isactive' => 1, 'isblocked' => 0]);

        $this->actingAs($this->pharmacyAdmin)->get('/orders/get_records/30')->assertForbidden();
        $this->actingAs($patient)->get('/orders/get_records/30')->assertForbidden();
    }

    public function test_pharmacy_admin_can_only_mark_their_own_pharmacys_orders_ready(): void
    {
        $this->actingAs($this->pharmacyAdmin)->post('/orders/3/ready')->assertForbidden();
        $this->actingAs($this->pharmacyAdmin)->post('/orders/2/ready')->assertOk();

        $this->assertDatabaseHas('orders', ['id' => 30, 'ready' => '0']);
        $this->assertDatabaseHas('orders', ['id' => 20, 'ready' => '1']);
    }
}
