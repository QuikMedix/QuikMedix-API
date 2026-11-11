<?php

namespace Tests\Feature\Http\Controllers;

use App\Notifications;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CreatesAuthenticationSchema;
use Tests\TestCase;

class AppStoreReadinessTest extends TestCase
{
    use CreatesAuthenticationSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticationSchema();
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('driver');
            $table->integer('isactive')->default(1);
            $table->integer('isblocked')->default(0);
            $table->integer('pharmacy_id')->nullable();
            foreach (['last_name', 'image', 'driving_license', 'driving_license_img',
                         'identification_cards', 'car_info', 'device_token'] as $column) {
                $table->string($column)->nullable();
            }
        });
        Schema::create('pharmacys', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('isactive')->default(1);
            $table->integer('isblocked')->default(0);
        });
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('setex')->andReturn(true);
        config(['app.twilio_sid' => null, 'app.twilio_auth_token' => null]);
    }

    private function driver(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'driver', 'isactive' => 1, 'isblocked' => 0, 'name' => 'Casey', 'last_name' => 'Courier', 'phone' => '(555) 010-7001',
            'driving_license' => 'D7654321', 'identification_cards' => '123-45-6789', 'car_info' => 'Honda Civic',
        ], $attributes));
    }

    private function useServiceAccount(): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $privateKey);
        config(['services.fcm.credentials' => json_encode([
            'project_id' => 'quikmedix',
            'client_email' => 'push@quikmedix.iam.gserviceaccount.com',
            'private_key' => $privateKey,
            'token_uri' => 'https://oauth2.googleapis.com/token',
        ])]);
    }

    public function test_push_notifications_use_the_fcm_http_v1_api(): void
    {
        $this->useServiceAccount();
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'access-123', 'expires_in' => 3600]),
            'fcm.googleapis.com/*' => Http::response(['name' => 'projects/quikmedix/messages/1']),
        ]);
        $driver = $this->driver(['device_token' => 'device-abc']);

        $this->assertSame('sent', Notifications::send_push($driver->id, 'New route', 'You have 3 stops'));

        Http::assertSent(fn (HttpRequest $request) => $request->url() === 'https://fcm.googleapis.com/v1/projects/quikmedix/messages:send'
            && $request->hasHeader('Authorization', 'Bearer access-123')
            && $request['message']['token'] === 'device-abc'
            && $request['message']['notification'] === ['title' => 'New route', 'body' => 'You have 3 stops']);
        Http::assertNotSent(fn (HttpRequest $request) => str_contains($request->url(), 'fcm/send'));
    }

    public function test_a_token_from_an_uninstalled_app_is_forgotten(): void
    {
        $this->useServiceAccount();
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'access-123']),
            'fcm.googleapis.com/*' => Http::response(['error' => ['status' => 'NOT_FOUND',
                'details' => [['errorCode' => 'UNREGISTERED']]]], 404),
        ]);
        $driver = $this->driver(['device_token' => 'stale-token']);

        $this->assertSame('invalid_token', Notifications::send_push($driver->id, 'Hi', 'There'));
        $this->assertDatabaseHas('users', ['id' => $driver->id, 'device_token' => null]);
    }

    public function test_no_push_is_attempted_without_a_service_account(): void
    {
        config(['services.fcm.credentials' => null]);
        Http::fake();

        Notifications::send_push($this->driver(['device_token' => 'device-abc'])->id, 'Hi', 'There');

        Http::assertNothingSent();
    }

    public function test_the_push_test_command_reports_missing_credentials(): void
    {
        config(['services.fcm.credentials' => null]);

        $this->artisan('push:test')->expectsOutputToContain('FCM_CREDENTIALS is missing')->assertExitCode(1);
    }

    public function test_the_push_test_command_confirms_working_credentials_and_sends(): void
    {
        $this->useServiceAccount();
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'access-123']),
            'fcm.googleapis.com/*' => Http::response(['name' => 'projects/quikmedix/messages/1']),
        ]);
        $driver = $this->driver(['device_token' => 'device-abc']);

        $this->artisan('push:test', ['user_id' => $driver->id])
            ->expectsOutputToContain('Firebase credentials work')
            ->expectsOutputToContain('Result: sent')
            ->assertExitCode(0);
    }

    public function test_the_device_token_is_saved_even_when_twilio_is_not_configured(): void
    {
        $driver = $this->driver();

        $this->actingAs($driver, 'api')->postJson('/api/update/device', ['device_token' => 'fcm-token-1'])
            ->assertOk()->assertJson(['message' => 'Device Token was updated']);

        $this->assertDatabaseHas('users', ['id' => $driver->id, 'device_token' => 'fcm-token-1']);
    }

    public function test_a_driver_can_delete_their_account(): void
    {
        $driver = $this->driver(['device_token' => 'fcm-token-1', 'driving_license_img' => '/images/driving_license/x.jpg']);

        $this->actingAs($driver, 'api')->postJson('/api/account/delete')
            ->assertOk()->assertJson(['message' => 'Account deleted']);

        $row = DB::table('users')->where('id', $driver->id)->first();
        $this->assertSame('Deleted', $row->name);
        $this->assertSame("deleted-{$driver->id}@deleted.invalid", $row->email);
        $this->assertSame("deleted-{$driver->id}", $row->phone);
        foreach (['driving_license', 'driving_license_img', 'identification_cards', 'car_info', 'device_token'] as $column) {
            $this->assertNull($row->{$column}, "$column should be erased");
        }
        $this->assertSame(0, (int) $row->isactive);
        $this->assertSame(1, (int) $row->isblocked);
    }

    public function test_a_driver_waiting_for_approval_can_still_delete_their_account(): void
    {
        $pending = $this->driver(['isactive' => 0]);

        $this->actingAs($pending, 'api')->postJson('/api/account/delete')->assertOk();

        $this->assertDatabaseHas('users', ['id' => $pending->id, 'name' => 'Deleted', 'identification_cards' => null]);
    }

    public function test_deleting_an_account_requires_signing_in(): void
    {
        $this->postJson('/api/account/delete')->assertUnauthorized();
    }
}
