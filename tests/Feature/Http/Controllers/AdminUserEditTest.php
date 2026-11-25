<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Tests\Support\InsertsRows;
use Tests\TestCase;

/**
 * Settings > Users > Edit. The Admin Zone field is shown for admins and drivers only; while it was
 * required even when hidden, the browser refused to send the form for everyone else and said nothing.
 */
class AdminUserEditTest extends TestCase
{
    use InsertsRows;

    private array $geocoded = ['status' => 'OK', 'results' => [[
        'formatted_address' => '12801 Fair Oaks Blvd, Citrus Heights, CA 95610, USA',
        'geometry' => ['location' => ['lat' => 38.6836, 'lng' => -121.2633]],
    ]]];

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        config(['app.googlemaps_apikey' => 'test-key']);
        Http::fake(['maps.googleapis.com/*' => fn () => Http::response($this->geocoded)]);
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('setex')->andReturn(true);
        $this->row('pharmacys', ['id' => 1, 'name' => 'QA Test Pharmacy', 'address' => '350 5th Ave, New York, NY 10118', 'isactive' => 1, 'isblocked' => 0]);
        $this->row('admin_areas', ['id' => 1, 'name' => 'North']);
        $this->row('users', ['id' => 1, 'name' => 'Root', 'email' => 'root@example.test', 'phone' => '(555) 010-0001', 'role' => 'superadmin', 'isactive' => 1, 'isblocked' => 0]);
        $this->row('users', ['id' => 14, 'name' => 'Test', 'last_name' => 'Patient', 'email' => 'test@example.test', 'phone' => '(555) 010-1111', 'role' => 'user', 'pharmacy_id' => null, 'zone_id' => null, 'isactive' => 1, 'isblocked' => 0]);
        $this->row('users', ['id' => 15, 'name' => 'Dee', 'last_name' => 'Driver', 'email' => 'dee@example.test', 'phone' => '(555) 010-2222', 'role' => 'driver', 'zone_id' => null, 'isactive' => 1, 'isblocked' => 0]);
    }

    private function asSuperadmin(): static
    {
        return $this->actingAs(User::findOrFail(1))->withSession(['user_2fa' => 1]);
    }

    /** The opening tag of the Admin Zone field on a user's edit page. */
    private function zoneField(int $userId): string
    {
        $page = $this->asSuperadmin()->get("/settings/users/edit/$userId")->assertOk()->getContent();
        $this->assertSame(1, preg_match('/<select name="zone_id"[^>]*>/', $page, $found));

        return $found[0];
    }

    /** What the browser sends for a patient: every field of the form, the hidden zone field empty. */
    private function patientForm(array $overrides = []): array
    {
        return array_merge([
            'save' => 1, 'name' => 'Test', 'last_name' => 'Patient', 'email' => 'test@example.test', 'phone' => '(555) 010-1111',
            'password' => '', 'password2' => '', 'pharmacy' => 1, 'address' => '12801 Fair Oaks Boulevard, Citrus Heights, CA',
            'apartment' => '', 'zip' => '95610', 'role' => 'user', 'zone_id' => '',
        ], $overrides);
    }

    public function test_the_zone_field_is_required_only_on_the_pages_that_show_it(): void
    {
        $this->assertStringNotContainsString('required', $this->zoneField(14));
        $this->assertStringContainsString('required', $this->zoneField(15));
    }

    public function test_an_admin_gives_a_patient_a_pharmacy(): void
    {
        $this->asSuperadmin()->post('/settings/users/edit/14', $this->patientForm())->assertOk();

        $patient = DB::table('users')->where('id', 14)->first();
        $this->assertSame(
            [1, 'user', null, '12801 Fair Oaks Blvd, Citrus Heights, CA 95610, USA', '38.6836,-121.2633', '95610'],
            [(int) $patient->pharmacy_id, $patient->role, $patient->zone_id, $patient->address, $patient->location, $patient->zip],
        );
    }

    public function test_an_address_google_cannot_find_comes_back_as_a_message(): void
    {
        $this->geocoded = ['status' => 'ZERO_RESULTS', 'results' => []];

        $this->asSuperadmin()->from('/settings/users/edit/14')->post('/settings/users/edit/14', $this->patientForm())
            ->assertRedirect('/settings/users/edit/14')
            ->assertSessionHasErrors(['address' => 'We could not find this address. Check the street, city and ZIP code, or pick a suggestion from the list.']);

        $this->assertNull(DB::table('users')->where('id', 14)->value('pharmacy_id'));
    }
}
