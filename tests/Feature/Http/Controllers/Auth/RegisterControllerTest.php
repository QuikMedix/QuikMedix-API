<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CreatesAuthenticationSchema;
use Tests\TestCase;

class RegisterControllerTest extends TestCase
{
    use CreatesAuthenticationSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticationSchema();
        Schema::table('users', function (Blueprint $table): void {
            $table->string('last_name')->nullable();
            $table->string('role')->default('user');
            $table->integer('isactive')->default(1);
            $table->integer('pharmacy_id')->nullable();
        });
        Schema::create('pharmacys', function (Blueprint $table): void {
            $table->increments('id');
            foreach (['name', 'email', 'phone', 'address', 'location', 'logo', 'site', 'ip'] as $column) {
                $table->string($column)->nullable();
            }
            $table->integer('admin_id')->nullable();
            $table->integer('ref_id')->nullable();
        });
        config(['app.googlemaps_apikey' => 'test-key']);
    }

    private function fakeGeocoder(string $status): void
    {
        Http::fake(['maps.googleapis.com/*' => Http::response($status === 'OK'
            ? ['status' => 'OK', 'results' => [['geometry' => ['location' => ['lat' => 40.7485, 'lng' => -73.9857]]]]]
            : ['status' => $status, 'results' => []])]);
    }

    private function form(array $overrides = []): array
    {
        return array_merge([
            'pharmacyName' => 'Second Pharmacy',
            'pharmacyEmail' => 'pharmacy@example.com',
            'pharmacyPhone' => '(555) 010-2002',
            'pharmacyAddress' => '350 5th Ave, New York, NY 10118',
            'name' => 'Pat',
            'last_name' => 'Admin',
            'email' => 'pat.admin@example.com',
            'phone' => '(555) 010-2001',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ], $overrides);
    }

    public function test_registers_a_pharmacy_admin_without_the_optional_website(): void
    {
        $this->fakeGeocoder('OK');

        $this->post('/register', $this->form())->assertRedirect()->assertSessionHasNoErrors();

        $user = User::where('email', 'pat.admin@example.com')->firstOrFail();
        $pharmacy = DB::table('pharmacys')->find($user->pharmacy_id);
        $this->assertSame('medic', $user->role);
        $this->assertSame($user->id, $pharmacy->admin_id);
        $this->assertSame('40.7485,-73.9857', $pharmacy->location);
        $this->assertNull($pharmacy->site);
        $this->assertAuthenticatedAs($user);
    }

    public function test_an_address_google_cannot_find_is_a_field_error_and_saves_nothing(): void
    {
        $this->fakeGeocoder('ZERO_RESULTS');

        $this->from('/register')->post('/register', $this->form())
            ->assertRedirect('/register')
            ->assertSessionHasErrors('pharmacyAddress');

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('pharmacys', 0);
    }

    public function test_server_errors_are_visible_next_to_their_field(): void
    {
        $this->fakeGeocoder('ZERO_RESULTS');

        $html = $this->followingRedirects()->from('/register')->post('/register', $this->form())->getContent();

        // Bootstrap only displays .invalid-feedback after an input marked .is-invalid.
        $this->assertMatchesRegularExpression(
            '/<input[^>]*class="[^"]*\bis-invalid\b[^"]*"[^>]*name="pharmacyAddress"[^>]*>\s*<div class="invalid-feedback"[^>]*>We could not find this address\./',
            $html,
        );
    }

    public function test_rejects_an_email_or_phone_that_is_already_registered(): void
    {
        $this->fakeGeocoder('OK');
        $this->post('/register', $this->form())->assertSessionHasNoErrors();
        $this->app['auth']->guard()->logout();

        $this->post('/register', $this->form(['pharmacyName' => 'Another Pharmacy']))
            ->assertSessionHasErrors([
                'email' => 'An account with this e-mail already exists. Try logging in instead.',
                'phone' => 'An account with this phone number already exists. Try logging in instead.',
            ]);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_rejects_values_that_do_not_fit_the_columns(): void
    {
        $this->fakeGeocoder('OK');

        $this->post('/register', $this->form(['phone' => str_repeat('5', 21), 'password_confirmation' => 'different']))
            ->assertSessionHasErrors(['phone', 'password' => 'The passwords do not match.']);
    }
}
