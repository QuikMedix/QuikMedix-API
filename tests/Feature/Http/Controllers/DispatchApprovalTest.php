<?php

namespace Tests\Feature\Http\Controllers;

use App\User;
use Chatify\Facades\ChatifyMessenger as Chatify;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CreatesAuthenticationSchema;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class DispatchApprovalTest extends TestCase
{
    use CreatesAuthenticationSchema;

    private User $driver;

    private User $patient;

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
            foreach (['last_name', 'address', 'zip', 'apartment', 'location', 'device_token', 'active_status'] as $column) {
                $table->string($column)->nullable();
            }
        });
        foreach (['pharmacys', 'plans'] as $name) {
            Schema::create($name, function (Blueprint $table): void {
                $table->increments('id');
                foreach (['name', 'address', 'phone'] as $column) {
                    $table->string($column)->nullable();
                }
                $table->integer('plan_id')->nullable();
                $table->integer('isactive')->default(1);
                $table->integer('isblocked')->default(0);
                foreach (['tariff', 'tariff_area2', 'tariff_area3', 'tariff_area_more', 'tariff_next_day', 'tariff_same_day', 'tariff_asap', 'tariff_after_hours', 'tariff_fridge'] as $column) {
                    $table->decimal($column)->nullable();
                }
            });
        }
        Schema::create('orders', function (Blueprint $table): void {
            $table->increments('id');
            foreach (['pharmacy_id', 'user_id', 'driver_id', 'statuse_id', 'type_driver', 'delivery_time_id', 'fridge', 'count_bags', 'not_delivered'] as $column) {
                $table->integer($column)->nullable();
            }
            $table->decimal('extra_charge_driver')->default(0);
            $table->decimal('tariff')->nullable();
            foreach (['special_instructions', 'drop_off_photo', 'signature_photo', 'signature_type'] as $column) {
                $table->string($column)->nullable();
            }
            $table->timestamp('finish')->nullable();
        });
        Schema::create('pharmacy_areas', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('pharmacy_id');
            $table->integer('area_id');
            $table->integer('type');
        });
        Schema::create('area', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('polygon')->nullable();
        });
        foreach (['routes_priority', 'routes_priority_logs'] as $name) {
            Schema::create($name, function (Blueprint $table): void {
                $table->increments('id');
                foreach (['driver_id', 'order_id', 'type_id', 'type_pay', 'priority'] as $column) {
                    $table->integer($column)->nullable();
                }
                $table->string('type');
                $table->decimal('pay_value')->nullable();
            });
        }
        Schema::create('payouts_driver', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('driver_id');
            $table->integer('order_id');
            $table->decimal('amount');
        });
        Schema::create('locations', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('location');
        });
        Schema::create('packages_transitions', function (Blueprint $table): void {
            $table->increments('id');
            foreach (['order_id', 'user_id', 'driver_id'] as $column) {
                $table->integer($column);
            }
            $table->string('target');
            $table->string('location')->nullable();
        });
        Schema::create('ch_messages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->bigInteger('from_id');
            $table->bigInteger('to_id');
            $table->string('body', 5000)->nullable();
            $table->string('attachment')->nullable();
            $table->boolean('seen')->default(false);
            $table->timestamps();
        });
        Schema::create('notifications', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('user_id');
            foreach (['type', 'link', 'text', 'type_text'] as $column) {
                $table->string($column)->nullable();
            }
        });
        (require database_path('migrations/2026_11_11_000000_create_order_exceptions_table.php'))->up();

        DB::connection()->getPdo()->sqliteCreateFunction('ST_CONTAINS', fn (): int => 0);
        DB::connection()->getPdo()->sqliteCreateFunction('POINT', fn (): string => '');
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('setex')->andReturn(true);
        // No real pushes, SMS or web notifications leave the test run.
        config(['services.fcm.credentials' => null, 'app.twilio_sid' => null, 'services.beams.instance_id' => null]);
        Chatify::partialMock()->shouldReceive('push')->andReturn(true)->byDefault();
        $this->withSession(['user_2fa' => true]);

        DB::table('plans')->insert(['id' => 1, 'tariff_area_more' => 8]);
        DB::table('pharmacys')->insert(['id' => 2, 'plan_id' => 1, 'name' => 'Wall St Pharmacy', 'address' => '5 Broad St', 'phone' => '212-555-0100']);
        $this->patient = $this->user(['role' => 'user', 'pharmacy_id' => 2, 'name' => 'QA', 'last_name' => 'Patient', 'phone' => '212-555-0199', 'address' => '1 Wall St', 'apartment' => '4B', 'zip' => '10005', 'location' => '40,-73']);
        $this->driver = $this->user(['role' => 'driver', 'name' => 'Dan', 'last_name' => 'Driver', 'phone' => '212-555-0142']);
        DB::table('orders')->insert([
            'id' => 2, 'pharmacy_id' => 2, 'user_id' => $this->patient->id, 'driver_id' => $this->driver->id, 'statuse_id' => 3,
            'type_driver' => 0, 'delivery_time_id' => 1, 'count_bags' => 2, 'special_instructions' => 'Ring twice',
        ]);
        DB::table('routes_priority')->insert([
            ['driver_id' => $this->driver->id, 'order_id' => 2, 'type' => 'patient', 'type_id' => $this->patient->id, 'type_pay' => 1, 'pay_value' => 6, 'priority' => 1],
            ['driver_id' => $this->driver->id, 'order_id' => 9, 'type' => 'office', 'type_id' => 4, 'type_pay' => 1, 'pay_value' => 3, 'priority' => 5],
        ]);
    }

    private function user(array $attributes): User
    {
        return User::factory()->state(['isactive' => 1, 'isblocked' => 0])->create($attributes);
    }

    private function reportRefused(?string $note = 'Patient says wrong medication'): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->driver, 'api')->postJson('/api/orders/update_status', ['order_id' => '2_1', 'status_id' => 9, 'note' => $note]);
    }

    public function test_driver_report_waits_for_dispatch_and_forwards_order_pharmacy_and_patient_details(): void
    {
        $dispatchers = [];
        foreach (['superadmin', 'admin', 'dispadmin', 'logist'] as $role) {
            $dispatchers[] = $this->user(['role' => $role]);
        }
        $this->user(['role' => 'medic', 'pharmacy_id' => 2]);
        $this->user(['role' => 'medic', 'pharmacy_id' => 3]);
        $this->user(['role' => 'sale']);
        $this->user(['role' => 'dispadmin', 'isactive' => 0]);
        $this->user(['role' => 'logist', 'isblocked' => 1]);
        foreach ($dispatchers as $dispatcher) {
            Chatify::shouldReceive('push')->once()->with('private-chatify.'.$dispatcher->id, 'messaging', \Mockery::on(
                fn (array $payload): bool => (int) $payload['from_id'] === $this->driver->id
                    && (int) $payload['to_id'] === $dispatcher->id
                    && str_contains($payload['message'], 'Order #2'),
            ))->andReturn(true);
        }

        $this->reportRefused()->assertOk()->assertJson(['message' => 'OK', 'pending_approval' => true]);

        $this->assertDatabaseHas('orders', ['id' => 2, 'statuse_id' => 3, 'finish' => null]);
        $this->assertDatabaseHas('routes_priority', ['order_id' => 2, 'type' => 'patient']);
        $this->assertDatabaseHas('order_exceptions', ['order_id' => 2, 'driver_id' => $this->driver->id, 'requested_status_id' => 9, 'status' => 'pending', 'driver_note' => 'Patient says wrong medication']);

        $this->assertSame(array_map(fn (User $user): int => $user->id, $dispatchers), DB::table('ch_messages')->orderBy('to_id')->pluck('to_id')->map(fn ($id): int => (int) $id)->all());
        $body = html_entity_decode(DB::table('ch_messages')->value('body'), ENT_QUOTES);
        $this->assertSame($this->driver->id, (int) DB::table('ch_messages')->value('from_id'));
        foreach (['Order #2', 'Delivery refused', 'Dan Driver, 212-555-0142', 'Patient says wrong medication', 'Wall St Pharmacy', '5 Broad St', '212-555-0100', 'QA Patient', '212-555-0199', '1 Wall St, Apt 4B, 10005', 'Ring twice', '/orders/2/show/2'] as $detail) {
            $this->assertStringContainsString($detail, $body);
        }

        $this->assertSame(4, DB::table('notifications')->where('type_text', 'dispatch_approval')->count());
        $this->assertStringNotContainsString('QA Patient', DB::table('notifications')->value('text'));
    }

    public function test_repeated_report_does_not_duplicate_the_request_or_messages(): void
    {
        $this->user(['role' => 'dispadmin']);

        $first = $this->reportRefused()->json('exception_id');
        $this->reportRefused()->assertJson(['exception_id' => $first]);

        $this->assertDatabaseCount('order_exceptions', 1);
        $this->assertDatabaseCount('ch_messages', 1);
    }

    public function test_reports_go_to_dispatch_even_when_the_pharmacy_has_no_staff_account(): void
    {
        $this->user(['role' => 'medic', 'pharmacy_id' => 3]);
        $superadmin = $this->user(['role' => 'superadmin']);

        $this->actingAs($this->driver, 'api')->postJson('/api/orders/update_status', ['order_id' => '2', 'status_id' => 8])->assertOk();

        $this->assertDatabaseHas('ch_messages', ['to_id' => $superadmin->id]);
        $this->assertStringContainsString('Patient unavailable', DB::table('ch_messages')->value('body'));
    }

    public function test_dispatcher_approval_closes_the_stop_for_the_reporting_driver(): void
    {
        $this->reportRefused();
        $exceptionId = DB::table('order_exceptions')->value('id');
        DB::table('locations')->insert(['user_id' => $this->driver->id, 'location' => '40.7,-74.0']);
        $dispatcher = $this->user(['role' => 'dispadmin']);

        $this->actingAs($dispatcher)->post("/orders/2/exceptions/2/{$exceptionId}/approve", ['pharmacy_approval' => 1, 'statuse' => 9])
            ->assertRedirect('/orders/2/show/2')->assertSessionHas('success');

        $order = DB::table('orders')->where('id', 2)->first();
        $this->assertSame(9, (int) $order->statuse_id);
        $this->assertNotNull($order->finish);
        $this->assertEquals(8, $order->tariff);
        $this->assertDatabaseMissing('routes_priority', ['order_id' => 2, 'type' => 'patient']);
        $this->assertDatabaseHas('routes_priority', ['order_id' => 2, 'type' => 'office', 'type_id' => 4, 'driver_id' => $this->driver->id]);
        $this->assertDatabaseHas('payouts_driver', ['order_id' => 2, 'driver_id' => $this->driver->id, 'amount' => 6]);
        $this->assertDatabaseHas('packages_transitions', ['order_id' => 2, 'driver_id' => $this->driver->id, 'target' => 'faild']);
        $this->assertDatabaseHas('order_exceptions', ['id' => $exceptionId, 'status' => 'approved', 'reviewed_by' => $dispatcher->id]);
        $this->assertStringContainsString('Pharmacy approval obtained through live chat', DB::table('order_exceptions')->value('review_note'));
        $this->assertNotNull(DB::table('order_exceptions')->value('reviewed_at'));

        $this->post("/orders/2/exceptions/2/{$exceptionId}/approve", ['pharmacy_approval' => 1, 'statuse' => 9])->assertSessionHas('error', 'This report was already reviewed.');
        $this->assertDatabaseCount('payouts_driver', 1);
    }

    public function test_rejection_needs_instructions_and_keeps_the_stop_open(): void
    {
        $this->reportRefused();
        $exceptionId = DB::table('order_exceptions')->value('id');
        $this->actingAs($this->user(['role' => 'logist']));

        $this->post("/orders/2/exceptions/2/{$exceptionId}/reject", ['review_note' => ''])->assertSessionHasErrors('review_note');
        $this->assertDatabaseHas('order_exceptions', ['id' => $exceptionId, 'status' => 'pending']);

        $this->post("/orders/2/exceptions/2/{$exceptionId}/reject", ['review_note' => 'Pharmacy confirmed the order; leave with doorman'])
            ->assertRedirect('/orders/2/show/2')->assertSessionHas('success');

        $this->assertDatabaseHas('order_exceptions', ['id' => $exceptionId, 'status' => 'rejected', 'review_note' => 'Pharmacy confirmed the order; leave with doorman']);
        $this->assertDatabaseHas('orders', ['id' => 2, 'statuse_id' => 3, 'finish' => null]);
        $this->assertDatabaseHas('routes_priority', ['order_id' => 2, 'type' => 'patient']);

        $this->reportRefused()->assertJson(['pending_approval' => true]);
        $this->assertSame(1, DB::table('order_exceptions')->where('status', 'pending')->count());
    }

    public function test_an_order_closed_meanwhile_is_not_overwritten(): void
    {
        $this->reportRefused();
        $exceptionId = DB::table('order_exceptions')->value('id');
        DB::table('orders')->where('id', 2)->update(['statuse_id' => 4]);

        $this->actingAs($this->user(['role' => 'admin']))->post("/orders/2/exceptions/2/{$exceptionId}/approve", ['pharmacy_approval' => 1, 'statuse' => 9])->assertSessionHas('error');

        $this->assertDatabaseHas('orders', ['id' => 2, 'statuse_id' => 4]);
        $this->assertDatabaseHas('order_exceptions', ['id' => $exceptionId, 'status' => 'superseded']);
    }

    public function test_the_orders_pharmacy_staff_cannot_finalize_or_reject(): void
    {
        $this->reportRefused();
        $exceptionId = DB::table('order_exceptions')->value('id');
        $pharmacist = $this->user(['role' => 'medic', 'pharmacy_id' => 2]);

        $this->actingAs($pharmacist)->post("/orders/2/exceptions/2/{$exceptionId}/approve", ['pharmacy_approval' => 1, 'statuse' => 9])->assertForbidden();
        $this->post("/orders/2/exceptions/2/{$exceptionId}/reject", ['review_note' => 'Approved in chat'])->assertForbidden();

        $this->assertDatabaseHas('orders', ['id' => 2, 'statuse_id' => 3]);
        $this->assertDatabaseHas('routes_priority', ['order_id' => 2, 'type' => 'patient']);
        $this->assertDatabaseHas('order_exceptions', ['id' => $exceptionId, 'status' => 'pending', 'reviewed_by' => null]);
    }

    public function test_other_pharmacies_and_drivers_cannot_review_reports(): void
    {
        $this->reportRefused();
        $exceptionId = DB::table('order_exceptions')->value('id');

        $this->actingAs($this->user(['role' => 'medic', 'pharmacy_id' => 3]))->post("/orders/2/exceptions/2/{$exceptionId}/approve")->assertForbidden();
        $this->actingAs($this->user(['role' => 'medic', 'pharmacy_id' => 3]))->post("/orders/3/exceptions/2/{$exceptionId}/approve")->assertForbidden();
        $this->actingAs($this->driver)->post("/orders/2/exceptions/2/{$exceptionId}/approve")->assertForbidden();
        $this->actingAs($this->user(['role' => 'admin']))->post("/orders/3/exceptions/2/{$exceptionId}/approve")->assertNotFound();

        $this->assertDatabaseHas('order_exceptions', ['id' => $exceptionId, 'status' => 'pending']);
        $this->assertDatabaseHas('orders', ['id' => 2, 'statuse_id' => 3]);
    }

    public function test_admin_api_cannot_bypass_dispatch_review(): void
    {
        $admin = $this->user(['role' => 'admin']);

        $this->actingAs($admin, 'api')->postJson('/api/orders/update_status', ['order_id' => '2', 'status_id' => 8])
            ->assertForbidden()->assertJsonPath('message', 'Use dispatch review after obtaining pharmacy approval in live chat.');

        $this->assertDatabaseHas('orders', ['id' => 2, 'statuse_id' => 3, 'finish' => null]);
        $this->assertDatabaseHas('routes_priority', ['order_id' => 2, 'type' => 'patient']);
        $this->assertDatabaseCount('order_exceptions', 0);
    }

    public static function reviewerPermissions(): array
    {
        return [
            ['superadmin', true], ['admin', true], ['dispadmin', true], ['logist', true],
            ['medic', false], ['driver', false], ['user', false], ['sale', false], ['facility', false],
        ];
    }

    #[DataProvider('reviewerPermissions')]
    public function test_only_dispatch_roles_can_finalize_delivery_reports(string $role, bool $allowed): void
    {
        $this->assertSame($allowed, Gate::forUser(new User(['role' => $role, 'pharmacy_id' => 2]))->allows('review-delivery-report', 2));
    }

    public static function invalidApprovals(): array
    {
        return [
            'missing approval' => [['statuse' => 9], 'pharmacy_approval'],
            'unconfirmed approval' => [['statuse' => 9, 'pharmacy_approval' => 0], 'pharmacy_approval'],
            'missing selection' => [['pharmacy_approval' => 1], 'statuse'],
            'other status' => [['statuse' => 4, 'pharmacy_approval' => 1], 'statuse'],
            'noninteger status' => [['statuse' => 'refused', 'pharmacy_approval' => 1], 'statuse'],
        ];
    }

    #[DataProvider('invalidApprovals')]
    public function test_dispatch_must_confirm_pharmacy_live_chat_approval_and_select_a_valid_status(array $payload, string $error): void
    {
        $this->reportRefused();
        $exceptionId = DB::table('order_exceptions')->value('id');

        $this->actingAs($this->user(['role' => 'dispadmin']))
            ->post("/orders/2/exceptions/2/{$exceptionId}/approve", $payload)->assertSessionHasErrors([
                $error => $error === 'pharmacy_approval'
                    ? 'Obtain pharmacy approval in live chat and confirm it before closing this delivery.'
                    : 'Select Unavailable or Refused.',
            ]);

        $this->assertDatabaseHas('orders', ['id' => 2, 'statuse_id' => 3, 'finish' => null]);
        $this->assertDatabaseHas('order_exceptions', ['id' => $exceptionId, 'status' => 'pending']);
        $this->assertDatabaseHas('routes_priority', ['order_id' => 2, 'type' => 'patient']);
        $this->assertDatabaseCount('payouts_driver', 0);
    }

    public function test_dispatch_can_select_unavailable_after_pharmacy_approval(): void
    {
        $this->reportRefused();
        $exceptionId = DB::table('order_exceptions')->value('id');

        $this->actingAs($this->user(['role' => 'logist']))->post("/orders/2/exceptions/2/{$exceptionId}/approve", [
            'pharmacy_approval' => 1, 'statuse' => 8,
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('orders', ['id' => 2, 'statuse_id' => 8]);
        $this->assertDatabaseMissing('routes_priority', ['order_id' => 2, 'type' => 'patient']);
        $this->assertDatabaseHas('routes_priority', ['order_id' => 2, 'type' => 'office']);
        $this->assertDatabaseHas('order_exceptions', [
            'id' => $exceptionId, 'status' => 'approved', 'requested_status_id' => 9,
            'review_note' => 'Pharmacy approval obtained through live chat. Final status: Patient unavailable.',
        ]);
    }

    public function test_a_report_does_not_close_a_reassigned_order(): void
    {
        $this->reportRefused();
        $exceptionId = DB::table('order_exceptions')->value('id');
        $newDriver = $this->user(['role' => 'driver']);
        DB::table('orders')->where('id', 2)->update(['driver_id' => $newDriver->id]);
        DB::table('routes_priority')->where('order_id', 2)->update(['driver_id' => $newDriver->id]);

        $this->actingAs($this->user(['role' => 'dispadmin']))->post("/orders/2/exceptions/2/{$exceptionId}/approve", [
            'pharmacy_approval' => 1, 'statuse' => 9,
        ])->assertSessionHas('error');

        $this->assertDatabaseHas('orders', ['id' => 2, 'statuse_id' => 3, 'driver_id' => $newDriver->id, 'finish' => null]);
        $this->assertDatabaseHas('routes_priority', ['order_id' => 2, 'type' => 'patient', 'driver_id' => $newDriver->id]);
        $this->assertDatabaseHas('order_exceptions', ['id' => $exceptionId, 'status' => 'superseded']);
        $this->assertDatabaseCount('payouts_driver', 0);
    }

    public function test_other_drivers_cannot_submit_reports_and_closed_orders_return_409(): void
    {
        $this->actingAs($this->user(['role' => 'driver']), 'api')->postJson('/api/orders/update_status', [
            'order_id' => '2', 'status_id' => 8,
        ])->assertForbidden();
        $this->assertDatabaseCount('order_exceptions', 0);

        DB::table('orders')->where('id', 2)->update(['statuse_id' => 4]);
        $this->reportRefused()->assertConflict();

        $this->assertDatabaseCount('order_exceptions', 0);
        $this->assertDatabaseHas('orders', ['id' => 2, 'statuse_id' => 4]);
    }

    public function test_new_drivers_report_supersedes_the_previous_drivers_pending_report(): void
    {
        $oldExceptionId = $this->reportRefused()->json('exception_id');
        $newDriver = $this->user(['role' => 'driver']);
        DB::table('orders')->where('id', 2)->update(['driver_id' => $newDriver->id]);
        DB::table('routes_priority')->where('order_id', 2)->update(['driver_id' => $newDriver->id]);

        $this->actingAs($newDriver, 'api')->postJson('/api/orders/update_status', [
            'order_id' => 2, 'status_id' => 8,
        ])->assertOk()->assertJsonPath('pending_approval', true);

        $this->assertDatabaseHas('order_exceptions', ['id' => $oldExceptionId, 'status' => 'superseded']);
        $this->assertSame(1, DB::table('order_exceptions')->where('order_id', 2)->where('status', 'pending')->count());
        $this->assertDatabaseHas('order_exceptions', ['order_id' => 2, 'driver_id' => $newDriver->id, 'status' => 'pending', 'requested_status_id' => 8]);
        $this->assertDatabaseHas('routes_priority', ['order_id' => 2, 'type' => 'patient', 'driver_id' => $newDriver->id]);
    }

    public static function malformedReports(): array
    {
        return [
            'fractional status' => [['status_id' => 7.9], 'status_id'],
            'text status' => [['status_id' => '8 invalid'], 'status_id'],
            'array order' => [['order_id' => [2]], 'order_id'],
            'invalid order' => [['order_id' => '2_not-a-bag'], 'order_id'],
            'array note' => [['note' => ['invalid']], 'note'],
            'long note' => [['note' => str_repeat('x', 1001)], 'note'],
        ];
    }

    #[DataProvider('malformedReports')]
    public function test_malformed_reports_return_422_without_changing_the_order(array $payload, string $error): void
    {
        $this->actingAs($this->driver, 'api')->postJson('/api/orders/update_status', array_replace([
            'order_id' => '2_1', 'status_id' => 9,
        ], $payload))->assertUnprocessable()->assertJsonValidationErrors($error);

        $this->assertDatabaseHas('orders', ['id' => 2, 'statuse_id' => 3, 'finish' => null]);
        $this->assertDatabaseHas('routes_priority', ['order_id' => 2, 'type' => 'patient']);
        $this->assertDatabaseCount('order_exceptions', 0);
        $this->assertDatabaseCount('ch_messages', 0);
    }

    public function test_reports_are_still_saved_and_notified_when_chat_broadcasting_fails(): void
    {
        $first = $this->user(['role' => 'dispadmin']);
        $second = $this->user(['role' => 'logist']);
        Chatify::shouldReceive('push')->twice()->andThrow(new \RuntimeException('Chat unavailable'));

        $this->reportRefused()->assertOk()->assertJsonPath('pending_approval', true);

        $this->assertDatabaseHas('ch_messages', ['to_id' => $first->id]);
        $this->assertDatabaseHas('ch_messages', ['to_id' => $second->id]);
        $this->assertDatabaseCount('notifications', 2);
        $this->assertDatabaseHas('order_exceptions', ['order_id' => 2, 'status' => 'pending']);
        $this->assertDatabaseHas('routes_priority', ['order_id' => 2, 'type' => 'patient']);
    }

    public function test_review_requires_authentication(): void
    {
        $this->post('/orders/2/exceptions/2/1/approve')->assertRedirectToRoute('login');
        $this->post('/orders/2/exceptions/2/1/reject')->assertRedirectToRoute('login');
        $this->postJson('/api/orders/update_status', ['order_id' => '2', 'status_id' => 8])->assertUnauthorized();

        $this->assertDatabaseHas('orders', ['id' => 2, 'statuse_id' => 3]);
        $this->assertDatabaseCount('order_exceptions', 0);
    }

    public function test_review_controls_are_hidden_from_pharmacy_staff_and_driver(): void
    {
        $this->reportRefused();
        $pendingException = DB::table('order_exceptions')->first();
        foreach ([$this->user(['role' => 'medic', 'pharmacy_id' => 2]), $this->driver] as $user) {
            $this->actingAs($user);
            $this->view('orders.dispatch-review', [
                'order' => (object) ['id' => 2, 'pharmacy_id' => 2],
                'pendingException' => $pendingException,
                'pharmacyStaff' => collect(),
            ])->assertDontSee('dispatch-status', false)->assertDontSee('pharmacy_approval', false);
        }
    }

    public function test_review_panel_links_dispatch_to_the_pharmacy_chat(): void
    {
        $this->reportRefused();
        $medic = $this->user(['role' => 'medic', 'pharmacy_id' => 2, 'name' => 'Pat', 'last_name' => 'Pharmacist']);
        $this->actingAs($this->user(['role' => 'dispadmin']));

        $pendingException = DB::table('order_exceptions')->first();
        $pendingException->driver_name = 'Dan';
        $pendingException->driver_last_name = 'Driver';
        $pendingException->driver_phone = '212-555-0142';
        $html = view('orders.dispatch-review', [
            'pendingException' => $pendingException,
            'pharmacyStaff' => DB::table('users')->where('id', $medic->id)->get(['id', 'name', 'last_name']),
            'order' => (object) ['id' => 2, 'pharmacy_id' => 2, 'pharmacyphone' => '212-555-0100'],
            'errors' => new \Illuminate\Support\ViewErrorBag,
        ])->render();

        $this->assertStringContainsString('Driver report needs approval: Delivery refused', $html);
        $this->assertStringContainsString(url('chat/'.$this->driver->id), $html);
        $this->assertStringContainsString('Patient says wrong medication', $html);
        $this->assertStringContainsString(url('chat/'.$medic->id), $html);
        $this->assertStringContainsString('Pharmacy chat: Pat Pharmacist', $html);
        $this->assertStringContainsString('name="pharmacy_approval"', $html);
        $this->assertStringContainsString('I obtained pharmacy approval for this status through live chat.', $html);
        $this->assertStringContainsString('>Unavailable</option>', $html);
        $this->assertStringContainsString('>Refused</option>', $html);
        $this->assertStringContainsString('/orders/2/exceptions/2/'.$pendingException->id.'/approve', $html);
    }
}
