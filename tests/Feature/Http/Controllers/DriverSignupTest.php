<?php

namespace Tests\Feature\Http\Controllers;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CreatesAuthenticationSchema;
use Tests\TestCase;

class DriverSignupTest extends TestCase
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
            foreach (['last_name', 'image', 'driving_license', 'driving_license_img', 'identification_cards', 'car_info'] as $column) {
                $table->string($column)->nullable();
            }
        });
        Schema::create('pharmacys', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('isactive')->default(1);
            $table->integer('isblocked')->default(0);
        });
        DB::table('pharmacys')->insert(['id' => 2]);
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('setex')->andReturn(true);
    }

    private function signup(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Casey', 'last_name' => 'Courier', 'email' => 'casey@example.com', 'phone' => '(555) 010-7001',
            'password' => 'secret123', 'password2' => 'secret123',
            'driving_license' => 'D7654321', 'identification_cards' => 'ID7654321', 'car_info' => 'Honda Civic',
        ], $overrides);
    }

    public function test_a_self_registered_courier_waits_for_approval(): void
    {
        $this->postJson('/api/driver-create', $this->signup())
            ->assertOk()->assertJson(['message' => 'Driver successfully created', 'status' => 'pending_approval']);

        $this->assertDatabaseHas('users', ['email' => 'casey@example.com', 'role' => 'driver', 'isactive' => 0, 'pharmacy_id' => null]);
    }

    public function test_a_driver_who_picks_a_pharmacy_also_waits_for_approval(): void
    {
        $this->postJson('/api/driver-create', $this->signup(['pharmacy_id' => 2]))->assertOk();

        $this->assertDatabaseHas('users', ['email' => 'casey@example.com', 'isactive' => 0, 'pharmacy_id' => 2]);
    }

    public function test_signing_up_for_a_pharmacy_that_does_not_exist_is_rejected(): void
    {
        $this->postJson('/api/driver-create', $this->signup(['pharmacy_id' => 999]))->assertStatus(400);

        $this->assertDatabaseMissing('users', ['email' => 'casey@example.com']);
    }

    public function test_a_pending_driver_cannot_see_any_orders(): void
    {
        $pending = User::factory()->create(['role' => 'driver', 'isactive' => 0, 'isblocked' => 0]);

        $this->actingAs($pending)->get('/orders')->assertForbidden();
        $this->actingAs($pending, 'api')->getJson('/api/orders')->assertForbidden();
    }
}
