<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CreatesAuthenticationSchema;
use Tests\Support\FakeHttpsStream;
use Tests\TestCase;

/**
 * The mobile profile saves only the fields that were filled in, and the action log
 * records what the request changed.
 */
class ProfileChangeLogTest extends TestCase
{
    use CreatesAuthenticationSchema;

    private User $driver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticationSchema();
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('user');
            $table->integer('isactive')->default(1);
            $table->integer('isblocked')->default(0);
            $table->integer('pharmacy_id')->nullable();
            foreach (['last_name', 'address', 'location', 'city', 'state', 'zip', 'apartment', 'driving_license',
                'identification_cards', 'car_info', 'payment_card', 'image', 'driving_license_img'] as $column) {
                $table->string($column)->nullable();
            }
        });
        Schema::create('pharmacys', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('isactive')->default(1);
            $table->integer('isblocked')->default(0);
        });
        Schema::create('action_log', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('type');
            $table->text('comment')->nullable();
            $table->integer('user_id');
            $table->integer('action_user_id');
        });

        $this->driver = User::factory()->create([
            'role' => 'driver', 'pharmacy_id' => 4, 'name' => 'Casey', 'last_name' => 'Courier', 'email' => 'casey@example.test',
            'phone' => '(555) 010-7001', 'address' => '1 Old St, New York, NY 10001, USA', 'location' => '40.70,-74.00',
            'zip' => '10001', 'apartment' => '2A', 'car_info' => 'Honda Civic',
        ])->fresh();
        config(['app.googlemaps_apikey' => 'test-key']);
        FakeHttpsStream::install();
        FakeHttpsStream::respondWith('maps.googleapis.com/maps/api/geocode', ['results' => [[
            'formatted_address' => '9 New Ave, New York, NY 10002, USA',
            'geometry' => ['location' => ['lat' => 40.72, 'lng' => -73.99]],
        ]]]);
    }

    protected function tearDown(): void
    {
        FakeHttpsStream::uninstall();
        parent::tearDown();
    }

    /**
     * @param array<string, mixed> $fields
     * @return list<string> "type: comment" for every logged change, in order
     */
    private function updateProfile(array $fields): array
    {
        $this->actingAs($this->driver, 'api')->postJson('/api/profile', $fields)
            ->assertOk()->assertExactJson(['message' => 'Profile successfully updated']);

        $rows = DB::table('action_log')->orderBy('id')->get();
        foreach ($rows as $row) {
            $this->assertSame([$this->driver->id, $this->driver->id], [(int) $row->user_id, (int) $row->action_user_id], 'The driver is both the subject and the author.');
        }

        return $rows->map(fn ($row): string => $row->type.': '.$row->comment)->all();
    }

    public function test_every_changed_field_of_a_full_update_is_logged(): void
    {
        $changes = $this->updateProfile([
            'name' => 'Jordan', 'last_name' => 'Rider', 'email' => 'jordan@example.test', 'phone' => '(555) 010-7002',
            'address' => '9 New Ave', 'zip' => '10002', 'apartment' => '5C', 'car_info' => 'Toyota Prius',
        ]);

        $this->assertSame([
            'change name: from Casey to Jordan',
            'change last_name: from Courier to Rider',
            'change email: from casey@example.test to jordan@example.test',
            'change phone: from (555) 010-7001 to (555) 010-7002',
            'change address: from 1 Old St, New York, NY 10001, USA to 9 New Ave',
            'change zip: from 10001 to 10002',
            'change apartment: from 2A to 5C',
            'change car_info: from Honda Civic to Toyota Prius',
        ], $changes);
        $this->assertStringContainsString('address='.urlencode('9 New Ave 10002').'&key=test-key', FakeHttpsStream::$requests[0]['url']);
        $this->assertDatabaseHas('users', [
            'id' => $this->driver->id, 'name' => 'Jordan', 'last_name' => 'Rider', 'phone' => '(555) 010-7002',
            'address' => '9 New Ave, New York, NY 10002, USA', 'location' => '40.72,-73.99', 'zip' => '10002', 'apartment' => '5C', 'car_info' => 'Toyota Prius',
        ]);
    }

    public function test_resubmitting_the_stored_profile_logs_nothing(): void
    {
        FakeHttpsStream::respondWith('maps.googleapis.com/maps/api/geocode', ['results' => [[
            'formatted_address' => '1 Old St, New York, NY 10001, USA',
            'geometry' => ['location' => ['lat' => 40.70, 'lng' => -74.00]],
        ]]]);

        $changes = $this->updateProfile([
            'name' => 'Casey', 'last_name' => 'Courier', 'email' => 'casey@example.test', 'phone' => '(555) 010-7001',
            'address' => '1 Old St', 'zip' => '10001', 'apartment' => '2A', 'car_info' => 'Honda Civic',
        ]);

        $this->assertSame([], $changes);
    }

    public function test_a_partial_update_logs_only_the_field_it_changes(): void
    {
        $changes = $this->updateProfile(['phone' => '(555) 010-7002']);

        $this->assertSame(['change phone: from (555) 010-7001 to (555) 010-7002'], $changes);
        $this->assertSame([], FakeHttpsStream::$requests);
        $this->assertDatabaseHas('users', [
            'id' => $this->driver->id, 'name' => 'Casey', 'last_name' => 'Courier', 'email' => 'casey@example.test',
            'phone' => '(555) 010-7002', 'zip' => '10001', 'apartment' => '2A', 'car_info' => 'Honda Civic', 'pharmacy_id' => 4,
        ]);
    }

    public function test_blank_fields_and_fields_the_profile_does_not_save_are_not_logged(): void
    {
        $changes = $this->updateProfile(['name' => '', 'email' => null, 'zip' => '', 'payment_card' => '4111', 'pharmacy_id' => 9]);

        $this->assertSame([], $changes);
        $this->assertDatabaseHas('users', ['id' => $this->driver->id, 'name' => 'Casey', 'zip' => '10001', 'payment_card' => '4111', 'pharmacy_id' => 4]);
    }
}
