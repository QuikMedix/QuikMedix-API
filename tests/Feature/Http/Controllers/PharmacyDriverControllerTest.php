<?php

namespace Tests\Feature\Http\Controllers;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CreatesAuthenticationSchema;
use Tests\TestCase;

class PharmacyDriverControllerTest extends TestCase
{
    use CreatesAuthenticationSchema;

    private User $pharmacyAdmin;

    private array $geocoderResponse = ['status' => 'OK', 'results' => [[
        'formatted_address' => '350 5th Ave, New York, NY 10118, USA',
        'geometry' => ['location' => ['lat' => 40.7485, 'lng' => -73.9857]],
    ]]];

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticationSchema();
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('user');
            $table->integer('isactive')->default(1);
            $table->integer('isblocked')->default(0);
            $table->integer('pharmacy_id')->nullable();
            foreach (['last_name', 'address', 'location', 'zip', 'apartment', 'image', 'driving_license', 'driving_license_img', 'identification_cards', 'transport', 'car_info', 'car_img', 'payment_card'] as $column) {
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
            $table->text('comment');
            $table->integer('user_id');
            $table->integer('action_user_id');
        });
        Schema::create('locations', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('location');
        });
        DB::table('pharmacys')->insert([['id' => 2], ['id' => 3]]);
        config(['app.googlemaps_apikey' => 'test-key']);
        Http::fake(['maps.googleapis.com/*' => fn () => Http::response($this->geocoderResponse)]);
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('setex')->andReturn(true);

        $this->pharmacyAdmin = User::factory()->create(['role' => 'medic', 'pharmacy_id' => 2, 'isactive' => 1, 'isblocked' => 0]);
    }

    private function driver(int $pharmacyId, array $attributes = []): User
    {
        return User::factory()->create($attributes + ['role' => 'driver', 'pharmacy_id' => $pharmacyId, 'phone' => fake()->unique()->numerify('(555) 01#-####')]);
    }

    private function form(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Dana', 'last_name' => 'Driver', 'email' => 'dana.driver@example.com', 'phone' => '(555) 010-4002',
            'password' => 'secret123', 'address' => '350 5th Ave, New York, NY', 'zip' => '10118',
            'driving_license' => 'D1234567', 'identification_cards' => 'ID1234567', 'car_info' => 'Toyota Camry', 'payment_card' => '0000',
        ], $overrides);
    }

    public function test_pharmacy_admin_adds_a_driver_to_their_pharmacy(): void
    {
        $this->actingAs($this->pharmacyAdmin)->post('/drivers/2/users/add', $this->form())
            ->assertRedirect('/drivers/2/users')->assertSessionHas('success', 'Driver added.');

        $this->assertDatabaseHas('users', ['email' => 'dana.driver@example.com', 'role' => 'driver', 'pharmacy_id' => 2, 'location' => '40.7485,-73.9857']);
    }

    public function test_invalid_driver_is_reported_and_not_saved(): void
    {
        $this->driver(3, ['email' => 'taken@example.com']);

        $this->actingAs($this->pharmacyAdmin)->from('/drivers/2/users/add')
            ->post('/drivers/2/users/add', $this->form(['email' => 'taken@example.com', 'payment_card' => '', 'password' => '123']))
            ->assertRedirect('/drivers/2/users/add')
            ->assertSessionHasErrors(['email' => 'A user with this e-mail already exists.', 'payment_card', 'password']);

        $this->assertDatabaseMissing('users', ['name' => 'Dana']);
    }

    public function test_an_address_google_cannot_find_is_a_field_error(): void
    {
        $this->geocoderResponse = ['status' => 'ZERO_RESULTS', 'results' => []];

        $this->actingAs($this->pharmacyAdmin)->post('/drivers/2/users/add', $this->form())
            ->assertSessionHasErrors('address');

        $this->assertDatabaseMissing('users', ['name' => 'Dana']);
    }

    public function test_pharmacy_admin_cannot_add_drivers_to_another_pharmacy(): void
    {
        $this->actingAs($this->pharmacyAdmin)->post('/drivers/3/users/add', $this->form())->assertForbidden();
    }

    public function test_pharmacy_admin_cannot_edit_or_save_another_pharmacys_driver(): void
    {
        $otherDriver = $this->driver(3, ['email' => 'other@example.com']);

        $this->actingAs($this->pharmacyAdmin)->get("/drivers/2/users/edit/{$otherDriver->id}")->assertForbidden();
        $this->actingAs($this->pharmacyAdmin)
            ->post("/drivers/2/users/edit/{$otherDriver->id}", $this->form(['email' => 'hijacked@example.com', 'password' => null]))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $otherDriver->id, 'email' => 'other@example.com']);
    }

    public function test_list_actions_only_reach_this_pharmacys_drivers_and_never_change_roles(): void
    {
        $ownDriver = $this->driver(2);
        $otherDriver = $this->driver(3);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($this->pharmacyAdmin)->post('/drivers/2/users', ['user_id' => $otherDriver->id, 'block' => 1])->assertForbidden();
        $this->actingAs($this->pharmacyAdmin)->post('/drivers/2/users', ['user_id' => $admin->id, 'tomedic' => 1])->assertForbidden();
        $this->actingAs($this->pharmacyAdmin)->post('/drivers/2/users', ['user_id' => $ownDriver->id, 'block' => 1, 'tomedic' => 1])
            ->assertRedirect('/drivers/2/users');

        $this->assertDatabaseHas('users', ['id' => $ownDriver->id, 'isblocked' => 1, 'role' => 'driver']);
        $this->assertDatabaseHas('users', ['id' => $otherDriver->id, 'isblocked' => 0]);
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => 'admin']);
    }

    public function test_pharmacy_admin_approves_a_pending_driver_from_the_list(): void
    {
        $pending = $this->driver(2, ['name' => 'Pending', 'isactive' => 0, 'isblocked' => 0]);

        $this->actingAs($this->pharmacyAdmin)->post('/drivers/2/users', ['user_id' => $pending->id, 'activate' => 1])
            ->assertRedirect('/drivers/2/users')->assertSessionHas('success', 'Driver approved.');

        $this->assertDatabaseHas('users', ['id' => $pending->id, 'isactive' => 1]);
    }

    public function test_a_removed_driver_goes_back_to_pending_instead_of_becoming_a_courier(): void
    {
        $driver = $this->driver(2, ['isactive' => 1, 'isblocked' => 0]);

        $this->actingAs($this->pharmacyAdmin)->post('/drivers/2/users', ['user_id' => $driver->id, 'remove' => 1])
            ->assertSessionHas('success', 'Driver removed.');

        $this->assertDatabaseHas('users', ['id' => $driver->id, 'pharmacy_id' => null, 'isactive' => 0]);
    }

    public function test_saving_a_driver_updates_the_profile_and_logs_changes(): void
    {
        $driver = $this->driver(2, ['name' => 'Old', 'email' => 'driver@example.com', 'phone' => '(555) 010-4003']);

        $this->actingAs($this->pharmacyAdmin)
            ->post("/drivers/2/users/edit/{$driver->id}", $this->form(['name' => 'New', 'email' => 'driver@example.com', 'phone' => '(555) 010-4003', 'password' => null]))
            ->assertRedirect("/drivers/2/users/edit/{$driver->id}")->assertSessionHas('success', 'Driver saved.');

        $this->assertDatabaseHas('users', ['id' => $driver->id, 'name' => 'New', 'address' => '350 5th Ave, New York, NY 10118, USA']);
        $this->assertDatabaseHas('action_log', ['user_id' => $driver->id, 'type' => 'change name', 'comment' => 'from Old to New']);
    }

    public function test_uploads_never_keep_the_clients_filename(): void
    {
        $this->app->usePublicPath($publicPath = sys_get_temp_dir().'/qm-public-'.uniqid());

        // A genuine JPEG passes the content check even when the client names it page.html.
        $jpeg = UploadedFile::fake()->image('photo.jpg');
        $upload = new UploadedFile($jpeg->getPathname(), 'page.html', 'text/html', null, true);

        $this->actingAs($this->pharmacyAdmin)
            ->post('/drivers/2/users/add', $this->form(['image' => $upload]))
            ->assertSessionHasNoErrors();

        $stored = DB::table('users')->where('email', 'dana.driver@example.com')->value('image');
        $this->assertMatchesRegularExpression('#^/images/users/[A-Za-z0-9]{40}\.jpg$#', $stored);
        $this->assertFileExists($publicPath.$stored);
        $this->assertSame([], glob($publicPath.'/images/users/*.html'));
    }

    public function test_blocked_pharmacy_staff_are_stopped(): void
    {
        DB::table('pharmacys')->where('id', 2)->update(['isblocked' => 1]);

        $this->actingAs($this->pharmacyAdmin)->post('/drivers/2/users/add', $this->form())->assertForbidden();
    }
}
