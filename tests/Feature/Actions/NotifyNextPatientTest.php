<?php

namespace Tests\Feature\Actions;

use App\Http\Controllers\LexaAdmin;
use App\Http\Controllers\LexaAdminApi;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesAuthenticationSchema;
use Tests\Support\FakeHereRouting;
use Tests\TestCase;

/**
 * "Your delivery is next": when a driver finishes a stop, the next patient gets a push with the
 * travel time from the driver's last location to the patient's primary address, and the order's ETA is set.
 */
class NotifyNextPatientTest extends TestCase
{
    use CreatesAuthenticationSchema;

    private User $driver;

    private User $patient;

    private int $orderId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticationSchema();
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('user');
            $table->integer('pharmacy_id')->nullable();
            $table->string('device_token')->nullable();
            $table->string('transport')->nullable();
            $table->integer('primary_address')->default(1);
            foreach (['', '2', '3'] as $suffix) {
                foreach (['address', 'location', 'apartment', 'zip'] as $column) {
                    $table->string($column.$suffix)->nullable();
                }
            }
        });
        Schema::create('locations', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('location');
        });
        Schema::create('orders', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('eta')->nullable();
        });

        $this->driver = User::factory()->create(['role' => 'driver']);
        DB::table('locations')->insert([
            ['user_id' => $this->driver->id, 'location' => '40.60,-74.60'],
            ['user_id' => $this->driver->id, 'location' => '40.70,-74.00'],
        ]);
        $this->patient = User::factory()->create([
            'role' => 'user', 'device_token' => 'patient-device',
            'location' => '40.71, -74.01', 'location2' => '40.82,-74.02', 'location3' => '40.93,-74.03',
        ]);
        $this->orderId = DB::table('orders')->insertGetId(['eta' => null]);

        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $privateKey);
        config(['services.fcm.credentials' => json_encode([
            'project_id' => 'quikmedix', 'client_email' => 'push@quikmedix.iam.gserviceaccount.com',
            'private_key' => $privateKey, 'token_uri' => 'https://oauth2.googleapis.com/token',
        ])]);
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'access-123', 'expires_in' => 3600]),
            'fcm.googleapis.com/*' => Http::response(['name' => 'projects/quikmedix/messages/1']),
        ]);
        Redis::shouldReceive('get')->with('here_access_token')->andReturn('here-token');
        FakeHereRouting::install();
        FakeHereRouting::$legDuration = 1500;
    }

    protected function tearDown(): void
    {
        FakeHereRouting::uninstall();
        parent::tearDown();
    }

    /**
     * The two places the notification is sent from: dispatch pages and the driver app.
     *
     * @return array<string, array{class-string}>
     */
    public static function entryPoints(): array
    {
        return ['dispatch pages' => [LexaAdmin::class], 'driver app' => [LexaAdminApi::class]];
    }

    private function nextStop(): object
    {
        return (object) ['type' => 'patient', 'type_id' => $this->patient->id, 'order_id' => $this->orderId];
    }

    /**
     * @return list<string> the body of every push sent to the patient's device
     */
    private function pushes(): array
    {
        return Http::recorded(fn (HttpRequest $request): bool => str_contains($request->url(), 'fcm.googleapis.com'))
            ->map(fn (array $exchange): string => $exchange[0]['message']['token'].': '.$exchange[0]['message']['notification']['body'])->values()->all();
    }

    #[DataProvider('entryPoints')]
    public function test_patient_is_told_the_travel_time_from_the_driver(string $controller): void
    {
        $this->assertTrue($controller::next_patient_push($this->driver->id, $this->nextStop()));

        $this->assertCount(1, FakeHereRouting::$requests);
        $this->assertSame('Authorization: Bearer here-token', FakeHereRouting::$requests[0]['header']);
        $this->assertSame(
            ['via' => [], 'transportMode' => 'car', 'origin' => '40.70,-74.00', 'destination' => '40.71,-74.01', 'return' => 'summary'],
            FakeHereRouting::query(0),
        );
        $this->assertSame(
            ["patient-device: Your delivery is next. \nPlease track your order #{$this->orderId} via our app. ETA: 0 hours 25 minutes \nThank you for using our service."],
            $this->pushes(),
        );
        $this->assertDatabaseHas('orders', ['id' => $this->orderId, 'eta' => 1]);
    }

    #[DataProvider('entryPoints')]
    public function test_long_trips_are_reported_in_hours_and_minutes_by_bicycle_for_a_bicycle_driver(string $controller): void
    {
        User::whereKey($this->driver->id)->update(['transport' => '2']);
        FakeHereRouting::$legDuration = 4500;

        $this->assertTrue($controller::next_patient_push($this->driver->id, $this->nextStop()));

        $this->assertSame('bicycle', FakeHereRouting::query(0)['transportMode']);
        $this->assertStringContainsString('ETA: 1 hours 15 minutes', $this->pushes()[0]);
        $this->assertDatabaseHas('orders', ['id' => $this->orderId, 'eta' => 2]);
    }

    /**
     * @return array<string, array{class-string, int, string}>
     */
    public static function primaryAddresses(): array
    {
        return [
            'driver app, second address' => [LexaAdminApi::class, 2, '40.82,-74.02'],
            'driver app, third address' => [LexaAdminApi::class, 3, '40.93,-74.03'],
            'dispatch pages, second address' => [LexaAdmin::class, 2, '40.82,-74.02'],
            'dispatch pages, third address' => [LexaAdmin::class, 3, '40.93,-74.03'],
        ];
    }

    #[DataProvider('primaryAddresses')]
    public function test_travel_time_is_measured_to_the_patients_primary_address(string $controller, int $primary, string $destination): void
    {
        User::whereKey($this->patient->id)->update(['primary_address' => $primary]);

        $this->assertTrue($controller::next_patient_push($this->driver->id, $this->nextStop()));

        $this->assertSame($destination, FakeHereRouting::query(0)['destination']);
    }

    #[DataProvider('entryPoints')]
    public function test_only_the_primary_address_needs_a_location(string $controller): void
    {
        User::whereKey($this->patient->id)->update(['primary_address' => 2, 'location' => null]);

        $this->assertTrue($controller::next_patient_push($this->driver->id, $this->nextStop()));
        $this->assertCount(1, $this->pushes());

        // A primary address without a location is not replaced by another address.
        User::whereKey($this->patient->id)->update(['location' => '40.71,-74.01', 'location2' => null]);
        $this->assertFalse($controller::next_patient_push($this->driver->id, $this->nextStop()));
        $this->assertCount(1, $this->pushes());
    }

    #[DataProvider('entryPoints')]
    public function test_nothing_is_sent_without_a_driver_location_a_patient_or_a_patient_location(string $controller): void
    {
        User::whereKey($this->patient->id)->update(['location' => null]);
        $this->assertFalse($controller::next_patient_push($this->driver->id, $this->nextStop()));

        User::whereKey($this->patient->id)->update(['location' => '40.71,-74.01']);
        $this->assertFalse($controller::next_patient_push($this->driver->id, (object) ['type_id' => 999, 'order_id' => $this->orderId]));
        $this->assertFalse($controller::next_patient_push(999, $this->nextStop()));

        DB::table('locations')->delete();
        $this->assertFalse($controller::next_patient_push($this->driver->id, $this->nextStop()));

        $this->assertSame([], FakeHereRouting::$requests);
        $this->assertSame([], $this->pushes());
        $this->assertDatabaseHas('orders', ['id' => $this->orderId, 'eta' => null]);
    }

    #[DataProvider('entryPoints')]
    public function test_patient_is_still_notified_when_no_route_is_found(string $controller): void
    {
        FakeHereRouting::$withoutRoutes = true;

        $this->assertTrue($controller::next_patient_push($this->driver->id, $this->nextStop()));

        $this->assertStringContainsString('ETA: 0 hours 0 minutes', $this->pushes()[0]);
        $this->assertDatabaseHas('orders', ['id' => $this->orderId, 'eta' => 0]);
    }
}
