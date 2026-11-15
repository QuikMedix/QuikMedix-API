<?php

namespace Tests\Feature\Http\Requests;

use App\Http\Requests\SaveOfficeRequest;
use App\Http\Requests\SavePharmacyRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Tests\Support\InsertsRows;
use Tests\TestCase;

/**
 * The user, pharmacy and office forms refuse what their own "required" marks refuse, on the
 * server as well as in the browser, and say so on the form.
 */
class FormValidationTest extends TestCase
{
    use InsertsRows;

    private const USER = ['name' => 'Test', 'last_name' => 'Person', 'email' => 'test.person@example.test', 'phone' => '(555) 010-0000', 'password' => 'long-enough', 'address' => '1 Main St', 'zip' => '10001'];

    private const PHARMACY = ['name' => 'Test Pharmacy', 'email' => 'pharmacy@example.test', 'phone' => '(555) 010-0001', 'address' => '1 Main St', 'site' => 'example.test'];

    private const OFFICE = ['name' => 'Test Office', 'email' => 'office@example.test', 'phone' => '(555) 010-0002', 'address' => '1 Main St', 'zone_id' => 1];

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        $this->row('users', ['id' => 1, 'email' => 'root@example.test', 'role' => 'superadmin', 'isactive' => 1, 'isblocked' => 0]);
        $this->row('users', ['id' => 2, 'email' => 'someone@example.test', 'role' => 'user', 'isactive' => 1, 'isblocked' => 0]);
        $this->actingAs(User::findOrFail(1));
        // The page layout reads the menu counters from Redis.
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('setex')->andReturn(true);
    }

    public function test_an_empty_user_form_is_sent_back_with_a_message_per_required_field(): void
    {
        $this->from('/settings/users/add')->post('/settings/users/add', ['save' => 1])
            ->assertRedirect('/settings/users/add')
            ->assertSessionHasErrors(['name', 'last_name', 'email', 'phone', 'password', 'address', 'zip']);
        $this->from('/settings/users/add')->post('/settings/users/add', ['save' => 1, 'password' => 'short', 'email' => 'not-an-email'] + self::USER)
            ->assertSessionHasErrors(['password', 'email'])->assertSessionDoesntHaveErrors(['name', 'phone']);
        $this->assertSame(2, DB::table('users')->count());

        $this->get('/settings/users/add')->assertOk()->assertSee('The password must be at least 8 characters.')->assertSee('The email must be a valid email address.');
    }

    public function test_the_add_user_form_keeps_what_was_typed(): void
    {
        $this->from('/settings/users/add')->post('/settings/users/add', ['save' => 1, 'name' => 'Kept Name', 'zip' => '10001']);

        $this->get('/settings/users/add')->assertOk()->assertSee('value="Kept Name"', false)->assertSee('value="10001"', false);
    }

    public function test_an_empty_edit_user_pharmacy_or_office_form_is_sent_back(): void
    {
        $this->from('/settings/users/edit/2')->post('/settings/users/edit/2', ['save' => 1])->assertRedirect('/settings/users/edit/2')->assertSessionHasErrors(['name', 'last_name', 'email', 'phone']);
        $this->from('/pharmacys/add')->post('/pharmacys/add', ['save' => 1])->assertRedirect('/pharmacys/add')->assertSessionHasErrors(['name', 'email', 'phone', 'address', 'site']);
        $this->from('/pharmacys/edit/1')->post('/pharmacys/edit/1', ['save' => 1, 'zone_id' => ''] + self::PHARMACY)->assertSessionHasErrors(['zone_id'])->assertSessionDoesntHaveErrors(['name', 'plan_id']);
        $this->from('/offices/add')->post('/offices/add', ['save' => 1])->assertRedirect('/offices/add')->assertSessionHasErrors(['name', 'email', 'phone', 'address', 'zone_id']);
        $this->from('/offices/edit/1')->post('/offices/edit/1', ['save' => 1, 'email' => 'nope'] + self::OFFICE)->assertSessionHasErrors(['email']);

        // Every one of these forms has a place that shows the messages.
        foreach (['settings/user_add', 'settings/user_edit', 'pharmacy/pharmacy_add', 'pharmacy/pharmacy_edit', 'offices/form'] as $view) {
            $this->assertMatchesRegularExpression('/<x-form-errors \/>|\$errors->any\(\)/', (string) file_get_contents(resource_path("views/$view.blade.php")), $view);
        }
        $this->assertSame(0, DB::table('pharmacys')->count() + DB::table('offices')->count());
        $this->assertSame('x', DB::table('users')->where('id', 2)->value('name'));
    }

    public function test_what_the_forms_normally_send_passes(): void
    {
        $this->assertTrue(Validator::make(self::USER + ['apartment' => '', 'pharmacy' => ''], (new StoreUserRequest)->rules())->passes());
        $this->assertTrue(Validator::make(['password' => null, 'address' => null, 'zip' => null] + self::USER, (new UpdateUserRequest)->rules())->passes());
        $this->assertTrue(Validator::make(self::PHARMACY, (new SavePharmacyRequest)->rules())->passes());
        $this->assertTrue(Validator::make(self::PHARMACY + ['zone_id' => '3', 'plan_id' => '1'], (new SavePharmacyRequest)->rules())->passes());
        $this->assertTrue(Validator::make(self::OFFICE, (new SaveOfficeRequest)->rules())->passes());
    }

    public function test_permission_is_checked_before_the_fields(): void
    {
        $this->actingAs(User::findOrFail(2));

        $this->post('/pharmacys/edit/1', ['save' => 1])->assertForbidden();
        $this->post('/settings/users/edit/1', ['save' => 1])->assertForbidden();
        // A person with the plain user role may register a pharmacy, so they get the form back instead.
        $this->from('/pharmacys/add')->post('/pharmacys/add', ['save' => 1])->assertRedirect('/pharmacys/add');
    }

    public function test_the_pharmacy_edit_form_shows_what_was_refused(): void
    {
        $this->row('pharmacys', ['id' => 1, 'name' => 'Corner Pharmacy', 'plan_id' => 1]);
        $this->row('plans', ['id' => 1]);

        $this->from('/pharmacys/edit/1')->post('/pharmacys/edit/1', ['save' => 1, 'name' => '', 'email' => 'not-an-email'] + self::PHARMACY)->assertRedirect('/pharmacys/edit/1');

        $this->get('/pharmacys/edit/1')->assertOk()->assertSee('The name field is required.')->assertSee('The email must be a valid email address.');
        $this->assertSame('Corner Pharmacy', DB::table('pharmacys')->where('id', 1)->value('name'));
    }
}
