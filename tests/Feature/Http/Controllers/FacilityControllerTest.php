<?php

namespace Tests\Feature\Http\Controllers;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CreatesAuthenticationSchema;
use Tests\TestCase;

class FacilityControllerTest extends TestCase
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
            foreach (['last_name', 'home_phone', 'birth_date', 'image', 'address', 'location', 'zip', 'apartment'] as $column) {
                $table->string($column)->nullable();
            }
        });
        Schema::create('pharmacys', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->integer('isactive')->default(1);
            $table->integer('isblocked')->default(0);
        });
        Schema::create('additional_recipients', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('user_id');
            foreach (['family_type', 'family_name', 'family_phone'] as $column) {
                $table->string($column)->nullable();
            }
        });
        Schema::create('action_log', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('type');
            $table->text('comment');
            $table->integer('user_id');
            $table->integer('action_user_id');
        });
        DB::table('pharmacys')->insert([['id' => 2, 'name' => 'Second Pharmacy'], ['id' => 3, 'name' => 'Other Pharmacy']]);
        config(['app.googlemaps_apikey' => 'test-key']);
        Http::fake(['maps.googleapis.com/*' => Http::response(['status' => 'OK', 'results' => [[
            'formatted_address' => '5 Care Way, New York, NY 10001, USA',
            'geometry' => ['location' => ['lat' => 40.75, 'lng' => -73.99]],
        ]]])]);
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('setex')->andReturn(true);

        $this->pharmacyAdmin = User::factory()->create(['role' => 'medic', 'pharmacy_id' => 2, 'isactive' => 1, 'isblocked' => 0]);
    }

    private function account(string $role, int $pharmacyId, array $attributes = []): User
    {
        return User::factory()->create($attributes + ['role' => $role, 'pharmacy_id' => $pharmacyId, 'phone' => fake()->unique()->numerify('(555) 01#-####')]);
    }

    public function test_pharmacy_admin_adds_a_facility_without_a_last_name(): void
    {
        $this->actingAs($this->pharmacyAdmin)
            ->post('/facilitys/2/add', ['name' => 'Sunrise Care', 'phone' => '(555) 010-5001', 'address' => '5 Care Way', 'zip' => '10001'])
            ->assertRedirect('/facilitys/2')->assertSessionHas('success', 'Facility added.');

        $this->assertDatabaseHas('users', ['name' => 'Sunrise Care', 'role' => 'facility', 'pharmacy_id' => 2, 'location' => '40.75,-73.99']);
    }

    public function test_remove_detaches_only_this_pharmacys_facility_and_never_deletes_accounts(): void
    {
        $own = $this->account('facility', 2);
        $admin = User::factory()->create(['role' => 'superadmin']);

        $this->actingAs($this->pharmacyAdmin)->post('/facilitys/2', ['user_id' => $admin->id, 'remove' => 1])->assertForbidden();
        $this->actingAs($this->pharmacyAdmin)->post('/facilitys/2', ['user_id' => $own->id, 'remove' => 1])
            ->assertRedirect('/facilitys/2')->assertSessionHas('success', 'Facility removed.');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->assertDatabaseHas('users', ['id' => $own->id, 'pharmacy_id' => null]);
    }

    public function test_pharmacy_admin_cannot_edit_another_pharmacys_facility_or_its_recipients(): void
    {
        $other = $this->account('facility', 3, ['name' => 'Other']);
        $recipient = DB::table('additional_recipients')->insertGetId(['user_id' => $other->id, 'family_name' => 'Resident']);
        $own = $this->account('facility', 2);

        $this->actingAs($this->pharmacyAdmin)->get("/facilitys/2/edit/{$other->id}")->assertForbidden();
        $this->actingAs($this->pharmacyAdmin)->post("/facilitys/2/edit/{$other->id}", ['save' => 1, 'name' => 'Hijacked'])->assertForbidden();
        $this->actingAs($this->pharmacyAdmin)->post("/facilitys/2/edit/{$own->id}", ['additional_recipient_remove' => $recipient]);

        $this->assertDatabaseHas('users', ['id' => $other->id, 'name' => 'Other']);
        $this->assertDatabaseHas('additional_recipients', ['id' => $recipient]);
    }

    public function test_only_this_pharmacys_patients_can_become_recipients(): void
    {
        $facility = $this->account('facility', 2);
        $ownPatient = $this->account('user', 2, ['name' => 'Ann', 'last_name' => 'Own']);
        $otherPatient = $this->account('user', 3, ['name' => 'Bob', 'last_name' => 'Other']);

        $this->actingAs($this->pharmacyAdmin)->post("/facilitys/2/edit/{$facility->id}", ['patients_db' => [$ownPatient->id, $otherPatient->id]])
            ->assertSessionHas('success', 'Recipients added.');

        $this->assertSame(['Ann Own'], DB::table('additional_recipients')->where('user_id', $facility->id)->pluck('family_name')->all());
    }

    public function test_saving_a_facility_updates_the_geocoded_profile(): void
    {
        $facility = $this->account('facility', 2, ['name' => 'Old Home']);

        $this->actingAs($this->pharmacyAdmin)
            ->post("/facilitys/2/edit/{$facility->id}", ['save' => 1, 'name' => 'New Home', 'last_name' => 'LLC', 'phone' => $facility->phone, 'address' => '5 Care Way', 'zip' => '10001'])
            ->assertSessionHas('success', 'Facility saved.');

        $this->assertDatabaseHas('users', ['id' => $facility->id, 'name' => 'New Home', 'address' => '5 Care Way, New York, NY 10001, USA']);
    }
}
