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

class PatientControllerTest extends TestCase
{
    use CreatesAuthenticationSchema;

    private User $pharmacyAdmin;

    private array $geocoderResponse = ['status' => 'OK', 'results' => [[
        'formatted_address' => '1 Wall St, New York, NY 10005, USA',
        'geometry' => ['location' => ['lat' => 40.7069, 'lng' => -74.0113]],
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
            $table->integer('primary_address')->default(1);
            foreach (['last_name', 'home_phone', 'birth_date', 'image', 'address', 'address2', 'address3', 'location', 'location2', 'location3', 'zip', 'zip2', 'zip3', 'apartment', 'apartment2', 'apartment3'] as $column) {
                $table->string($column)->nullable();
            }
        });
        Schema::create('pharmacys', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->integer('isactive')->default(1);
            $table->integer('isblocked')->default(0);
        });
        foreach (['family_members', 'additional_recipients'] as $recipients) {
            Schema::create($recipients, function (Blueprint $table): void {
                $table->increments('id');
                $table->integer('user_id');
                foreach (['family_type', 'family_name', 'family_phone', 'family_address', 'location'] as $column) {
                    $table->string($column)->nullable();
                }
            });
        }
        Schema::create('deleted_patients', function (Blueprint $table): void {
            $table->integer('user_id');
            $table->integer('medic_id');
            $table->string('reason')->nullable();
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
        Http::fake(['maps.googleapis.com/*' => fn () => Http::response($this->geocoderResponse)]);
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('setex')->andReturn(true);

        $this->pharmacyAdmin = User::factory()->create(['role' => 'medic', 'pharmacy_id' => 2, 'isactive' => 1, 'isblocked' => 0]);
    }

    private function patient(int $pharmacyId, array $attributes = []): User
    {
        return User::factory()->create($attributes + ['role' => 'user', 'pharmacy_id' => $pharmacyId, 'phone' => fake()->unique()->numerify('(555) 01#-####')]);
    }

    private function form(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Pat', 'last_name' => 'Patient', 'phone' => '(555) 010-3002',
            'address' => '1 Wall St, New York, NY', 'zip' => '10005',
        ], $overrides);
    }

    public function test_pharmacy_admin_adds_a_patient_with_the_geocoded_address(): void
    {
        $this->actingAs($this->pharmacyAdmin)->post('/patients/2/add', $this->form())
            ->assertRedirect('/patients/2')->assertSessionHas('success', 'Patient added.');

        $this->assertDatabaseHas('users', ['phone' => '(555) 010-3002', 'role' => 'user', 'pharmacy_id' => 2, 'address' => '1 Wall St, New York, NY 10005, USA', 'location' => '40.7069,-74.0113']);
    }

    public function test_adding_from_the_order_flow_continues_to_the_order_form(): void
    {
        $this->actingAs($this->pharmacyAdmin)->post('/patients/2/add', $this->form(['order_add' => 'on']))
            ->assertRedirectContains('/orders/2/add?patient=');
    }

    public function test_phone_must_be_unique_within_the_pharmacy_only(): void
    {
        $this->patient(2, ['phone' => '(555) 010-3002']);
        $this->patient(3, ['phone' => '(555) 010-3003']);

        $this->actingAs($this->pharmacyAdmin)->post('/patients/2/add', $this->form())
            ->assertSessionHasErrors(['phone' => 'A patient with this phone number already exists in this pharmacy.']);
        $this->actingAs($this->pharmacyAdmin)->post('/patients/2/add', $this->form(['phone' => '(555) 010-3003']))
            ->assertSessionHasNoErrors();
    }

    public function test_an_address_google_cannot_find_is_a_field_error(): void
    {
        $this->geocoderResponse = ['status' => 'ZERO_RESULTS', 'results' => []];

        $this->actingAs($this->pharmacyAdmin)->post('/patients/2/add', $this->form())->assertSessionHasErrors('address');
        $this->assertDatabaseMissing('users', ['phone' => '(555) 010-3002']);
    }

    public function test_pharmacy_admin_cannot_touch_another_pharmacys_patient(): void
    {
        $other = $this->patient(3, ['name' => 'Other']);
        $otherMember = DB::table('family_members')->insertGetId(['user_id' => $other->id, 'family_name' => 'Kin']);

        $this->actingAs($this->pharmacyAdmin)->get("/patients/2/edit/{$other->id}")->assertForbidden();
        $this->actingAs($this->pharmacyAdmin)->post("/patients/2/edit/{$other->id}", $this->form(['save' => 1, 'name' => 'Hijacked']))->assertForbidden();
        $this->actingAs($this->pharmacyAdmin)->post('/patients/2', ['user_id' => $other->id, 'block' => 1])->assertForbidden();
        $this->actingAs($this->pharmacyAdmin)->get("/patients/{$other->id}/family")->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $other->id, 'name' => 'Other', 'isblocked' => 0]);
        $this->assertDatabaseHas('family_members', ['id' => $otherMember]);
    }

    public function test_family_members_can_only_be_removed_from_the_patient_they_belong_to(): void
    {
        $own = $this->patient(2);
        $other = $this->patient(3);
        $otherMember = DB::table('family_members')->insertGetId(['user_id' => $other->id, 'family_name' => 'Kin']);

        $this->actingAs($this->pharmacyAdmin)->post("/patients/2/edit/{$own->id}", ['family_member_remove' => $otherMember])
            ->assertRedirect("/patients/2/edit/{$own->id}");

        $this->assertDatabaseHas('family_members', ['id' => $otherMember]);
    }

    public function test_family_options_are_escaped(): void
    {
        $own = $this->patient(2);
        DB::table('family_members')->insert(['user_id' => $own->id, 'family_name' => '<script>x</script>', 'family_phone' => '1', 'family_type' => 'son', 'family_address' => 'Home']);

        $this->actingAs($this->pharmacyAdmin)->get("/patients/{$own->id}/family")
            ->assertOk()
            ->assertSee('&lt;script&gt;x&lt;/script&gt;', false)
            ->assertDontSee('<script>x</script>', false);
    }

    public function test_removing_a_patient_requires_the_users_password(): void
    {
        $own = $this->patient(2);

        $this->actingAs($this->pharmacyAdmin)->post('/patients/2', ['user_id' => $own->id, 'remove' => 1, 'password' => 'wrong'])
            ->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $own->id, 'pharmacy_id' => 2]);

        $this->actingAs($this->pharmacyAdmin)->post('/patients/2', ['user_id' => $own->id, 'remove' => 1, 'password' => 'password', 'reason' => 'Moved'])
            ->assertSessionHas('success', 'Patient removed.');
        $this->assertDatabaseHas('users', ['id' => $own->id, 'pharmacy_id' => null]);
        $this->assertDatabaseHas('deleted_patients', ['user_id' => $own->id, 'reason' => 'Moved']);
    }

    public function test_saving_a_patient_geocodes_every_address_and_keeps_a_valid_primary(): void
    {
        $own = $this->patient(2, ['name' => 'Old']);

        $this->actingAs($this->pharmacyAdmin)
            ->post("/patients/2/edit/{$own->id}", $this->form(['save' => 1, 'name' => 'New', 'phone' => $own->phone, 'address2' => '2 Wall St', 'primary_address' => 3]))
            ->assertRedirect("/patients/2/edit/{$own->id}")->assertSessionHas('success', 'Patient saved.');

        $this->assertDatabaseHas('users', ['id' => $own->id, 'name' => 'New', 'address2' => '1 Wall St, New York, NY 10005, USA', 'address3' => null, 'primary_address' => 1]);
        $this->assertDatabaseHas('action_log', ['user_id' => $own->id, 'type' => 'change name', 'comment' => 'from Old to New']);
    }
}
