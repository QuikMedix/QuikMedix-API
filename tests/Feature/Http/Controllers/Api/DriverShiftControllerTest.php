<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CreatesAuthenticationSchema;
use Tests\TestCase;

class DriverShiftControllerTest extends TestCase
{
    use CreatesAuthenticationSchema;

    private const FORBIDDEN = ['message' => 'You cannot open this page', 'errors' => 'Forbidden'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticationSchema();
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('user');
            $table->integer('isactive')->default(1);
            $table->integer('isblocked')->default(0);
            $table->integer('pharmacy_id')->nullable();
            $table->integer('work_now')->default(0);
        });
        Schema::create('pharmacys', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('isactive')->default(1);
            $table->integer('isblocked')->default(0);
        });
        Schema::create('locations', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('location');
        });
        Schema::create('tokens_driver', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('token');
        });
        Schema::create('payouts_driver', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('driver_id');
            $table->integer('withdraw')->default(0);
            $table->decimal('amount', 10, 2);
        });
    }

    private function account(string $role, array $attributes = []): User
    {
        // Reloaded so column defaults such as work_now are present, as they are for a real token.
        return User::factory()->create($attributes + ['role' => $role, 'isactive' => 1, 'isblocked' => 0])->fresh();
    }

    public function test_driver_reads_starts_and_finishes_a_shift(): void
    {
        $driver = $this->account('driver');
        $other = $this->account('driver');

        $this->actingAs($driver, 'api')->getJson('/api/driver/status')->assertOk()->assertExactJson(['work_now' => 0]);

        $this->getJson('/api/driver/status/start')->assertOk()->assertExactJson(['message' => 'Driver Status was updated']);
        $this->assertDatabaseHas('users', ['id' => $driver->id, 'work_now' => 1]);
        $this->assertDatabaseHas('users', ['id' => $other->id, 'work_now' => 0]);

        $this->getJson('/api/driver/status/finish')->assertOk()->assertExactJson(['message' => 'Driver Status was updated']);
        $this->assertDatabaseHas('users', ['id' => $driver->id, 'work_now' => 0]);
    }

    public function test_driver_and_admin_report_a_location(): void
    {
        $driver = $this->account('driver');
        $admin = $this->account('admin');

        $this->actingAs($driver, 'api')->postJson('/api/driver/add_location', ['location' => '40.7128,-74.0060'])
            ->assertOk()->assertExactJson(['message' => 'Location was added']);
        $this->actingAs($admin, 'api')->postJson('/api/driver/add_location', ['location' => '40.7,-74.1'])->assertOk();

        $this->assertDatabaseHas('locations', ['user_id' => $driver->id, 'location' => '40.7128,-74.0060']);
        $this->assertDatabaseHas('locations', ['user_id' => $admin->id, 'location' => '40.7,-74.1']);
    }

    public function test_location_is_required_and_limited_to_drivers_and_admins(): void
    {
        $this->actingAs($this->account('driver'), 'api')->postJson('/api/driver/add_location', ['location' => ''])
            ->assertBadRequest()->assertExactJson(['message' => 'Something is wrong with this field!', 'errors' => 'Bad Request']);

        $this->actingAs($this->account('user'), 'api')->postJson('/api/driver/add_location', ['location' => '40.7128,-74.0060'])
            ->assertForbidden()->assertExactJson(self::FORBIDDEN);

        $this->assertDatabaseCount('locations', 0);
    }

    public function test_driver_gets_a_one_time_qr_token(): void
    {
        $driver = $this->account('driver');

        $response = $this->actingAs($driver, 'api')->getJson('/api/driver/qr')->assertOk()->assertJsonStructure(['qr-code']);

        $token = DB::table('tokens_driver')->where('user_id', $driver->id)->value('token');
        $this->assertMatchesRegularExpression('/^[0-9a-f]{48}$/', $token);
        $this->assertSame("https://quickchart.io/qr?text=$token", $response->json('qr-code'));
    }

    public function test_only_drivers_get_a_qr_token(): void
    {
        $this->actingAs($this->account('admin'), 'api')->getJson('/api/driver/qr')
            ->assertForbidden()->assertExactJson(self::FORBIDDEN);

        $this->assertDatabaseCount('tokens_driver', 0);
    }

    public function test_payouts_total_only_the_drivers_unwithdrawn_amounts(): void
    {
        $driver = $this->account('driver');
        $other = $this->account('driver');
        DB::table('payouts_driver')->insert([
            ['driver_id' => $driver->id, 'withdraw' => 0, 'amount' => 10.25],
            ['driver_id' => $driver->id, 'withdraw' => 0, 'amount' => 5.5],
            ['driver_id' => $driver->id, 'withdraw' => 1, 'amount' => 99],
            ['driver_id' => $other->id, 'withdraw' => 0, 'amount' => 40],
        ]);

        $this->actingAs($driver, 'api')->getJson('/api/driver/payouts')->assertOk()->assertExactJson(['amount' => 15.8]);
    }

    public function test_payouts_are_limited_to_drivers_and_admins(): void
    {
        $this->actingAs($this->account('user'), 'api')->getJson('/api/driver/payouts')
            ->assertForbidden()->assertExactJson(self::FORBIDDEN);
    }
}
