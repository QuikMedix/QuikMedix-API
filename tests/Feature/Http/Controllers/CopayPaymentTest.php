<?php

namespace Tests\Feature\Http\Controllers;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CreatesAuthenticationSchema;
use Tests\TestCase;

class CopayPaymentTest extends TestCase
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
        Schema::create('orders', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('statuse_id');
            $table->integer('statuse_copay')->nullable();
            $table->decimal('copay', 10, 2)->default(0);
        });
        Schema::create('payment_accounts', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('type');
            $table->string('payment_profile_id')->nullable();
            $table->string('profile_id')->nullable();
        });
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('setex')->andReturn(true);
    }

    public function test_a_patient_cannot_charge_another_patients_card_for_their_copay(): void
    {
        $owner = User::factory()->create(['role' => 'user', 'isactive' => 1, 'isblocked' => 0]);
        $stranger = User::factory()->create(['role' => 'user', 'isactive' => 1, 'isblocked' => 0]);
        $orderId = DB::table('orders')->insertGetId(['user_id' => $owner->id, 'statuse_id' => 2, 'copay' => 25]);
        DB::table('payment_accounts')->insert(['user_id' => $owner->id, 'type' => 'card', 'payment_profile_id' => 'ccof:saved-card', 'profile_id' => 'customer']);

        $this->actingAs($stranger, 'api')->postJson("/api/orders/copay/pay/$orderId")->assertForbidden();

        $this->assertDatabaseHas('orders', ['id' => $orderId, 'statuse_copay' => null]);
    }
}
