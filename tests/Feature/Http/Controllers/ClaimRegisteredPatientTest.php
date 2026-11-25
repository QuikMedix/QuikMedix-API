<?php

namespace Tests\Feature\Http\Controllers;

use App\Actions\ClaimRegisteredPatient;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\ClientRepository;
use Tests\Support\FakesSms;
use Tests\Support\InsertsRows;
use Tests\TestCase;

/**
 * A patient who registered in the app has no pharmacy. When a pharmacy adds that phone number the
 * same account joins the pharmacy, with a new password texted to the number, so there is never a
 * second account that sign-in cannot reach.
 */
class ClaimRegisteredPatientTest extends TestCase
{
    use FakesSms;
    use InsertsRows;

    private const PHONE = '(555) 010-3002';

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        config(['services.fcm.credentials' => null, 'services.beams.instance_id' => null, 'app.googlemaps_apikey' => 'test-key']);
        Http::fake(['maps.googleapis.com/*' => fn () => Http::response(['status' => 'OK', 'results' => [[
            'formatted_address' => '1 Wall St, New York, NY 10005, USA',
            'geometry' => ['location' => ['lat' => 40.7069, 'lng' => -74.0113]],
        ]]])]);
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('setex')->andReturn(true);
        $this->row('pharmacys', ['id' => 1, 'name' => 'Corner Pharmacy', 'isactive' => 1, 'isblocked' => 0]);
        $this->row('pharmacys', ['id' => 2, 'name' => 'Other Pharmacy', 'isactive' => 1, 'isblocked' => 0]);
        $this->row('users', ['id' => 1, 'name' => 'Mia', 'email' => 'mia@example.test', 'phone' => '(555) 010-0001', 'role' => 'medic', 'pharmacy_id' => 1, 'isactive' => 1, 'isblocked' => 0]);
        $this->fakeSms();
    }

    /** Registers the way the app does and answers the new account's id. */
    private function registerInTheApp(array $overrides = []): int
    {
        $this->postJson('/api/register', array_merge([
            'name' => 'Reggie', 'last_name' => 'Stone', 'phone' => self::PHONE, 'email' => 'reggie@example.test',
            'password' => 'own-secret', 'password_confirmation' => 'own-secret',
        ], $overrides))->assertOk();

        return (int) DB::table('users')->where('phone', $overrides['phone'] ?? self::PHONE)->value('id');
    }

    private function form(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Regina', 'last_name' => 'Stone', 'phone' => self::PHONE,
            'address' => '1 Wall St, New York, NY', 'zip' => '10005',
        ], $overrides);
    }

    /** The password in the one text that was sent. */
    private function textedPassword(): string
    {
        $this->assertCount(1, $this->texts);
        $this->assertSame(1, preg_match('/\nPassword: (\S+)\n/', $this->texts[0]['body'], $found));

        return $found[1];
    }

    public function test_adding_a_phone_that_registered_in_the_app_attaches_that_account(): void
    {
        $id = $this->registerInTheApp();
        $this->assertNull(DB::table('users')->where('id', $id)->value('pharmacy_id'));

        $this->actingAs(User::findOrFail(1))->post('/patients/1/add', $this->form())
            ->assertRedirect('patients/1')
            ->assertSessionHas('success', 'This phone number already had a QuikMedix app account, so that account was added to your patients. A new password was texted to the patient.');

        $this->assertSame(1, DB::table('users')->where('phone', self::PHONE)->count());
        $patient = DB::table('users')->where('id', $id)->first();
        $this->assertSame(
            [1, 'user', 'Regina', '1 Wall St, New York, NY 10005, USA', '40.7069,-74.0113', '10005', 'reggie@example.test'],
            [(int) $patient->pharmacy_id, $patient->role, $patient->name, $patient->address, $patient->location, $patient->zip, $patient->email],
        );

        $this->assertSame('+15550103002', $this->texts[0]['to']);
        $this->assertStringStartsWith("From: Corner Pharmacy \nHello, Regina. Corner Pharmacy added your QuikMedix account to its patients. For your security the password was replaced. \nLogin: (555) 010-3002\nPassword: ", $this->texts[0]['body']);
        $this->assertTrue(Hash::check($this->textedPassword(), $patient->password));
        $this->assertFalse(Hash::check('own-secret', $patient->password));
        $this->assertSame(
            ['attached to pharmacy', $id, 1],
            array_values((array) DB::table('action_log')->where('user_id', $id)->first(['type', 'user_id', 'action_user_id'])),
        );
    }

    public function test_adding_from_the_order_flow_continues_with_the_attached_account(): void
    {
        $id = $this->registerInTheApp();

        $this->actingAs(User::findOrFail(1))->post('/patients/1/add', $this->form(['order_add' => 1]))
            ->assertRedirect("orders/1/add?patient=$id");
    }

    public function test_the_patient_signs_in_with_the_texted_password_and_sees_the_pharmacy(): void
    {
        app(ClientRepository::class)->createPersonalAccessGrantClient('Test client', 'users');
        $id = $this->registerInTheApp();
        $before = $this->postJson('/api/login', ['phone' => self::PHONE, 'password' => 'own-secret'])->assertOk()->json('token');
        DB::table('users')->where('id', $id)->update(['device_token' => 'a-device']);

        $this->assertSame($id, app(ClaimRegisteredPatient::class)->handle(self::PHONE, 1, ['name' => 'Regina']));

        // Whoever held the account before the pharmacy vouched for the number is signed out.
        $this->assertNull(DB::table('users')->where('id', $id)->value('device_token'));
        $this->app['auth']->forgetGuards();
        $this->withToken($before)->getJson('/api/patient/home')->assertUnauthorized();
        $this->app['auth']->forgetGuards();
        $this->postJson('/api/login', ['phone' => self::PHONE, 'password' => 'own-secret'])->assertUnauthorized();

        $after = $this->postJson('/api/login', ['phone' => self::PHONE, 'password' => $this->textedPassword()])->assertOk()->json('token');
        $this->app['auth']->forgetGuards();
        $this->withToken($after)->getJson('/api/patient/home')->assertOk()->assertJsonPath('pharmacy_name', 'Corner Pharmacy');
    }

    public function test_what_the_pharmacy_leaves_blank_keeps_the_patients_own_value(): void
    {
        $id = $this->registerInTheApp();

        app(ClaimRegisteredPatient::class)->handle(self::PHONE, 1, ['name' => 'Regina', 'last_name' => '', 'email' => null, 'home_phone' => null]);

        $patient = DB::table('users')->where('id', $id)->first();
        $this->assertSame(['Regina', 'Stone', 'reggie@example.test'], [$patient->name, $patient->last_name, $patient->email]);
    }

    public function test_a_number_stored_as_digits_is_the_same_number(): void
    {
        $id = $this->registerInTheApp(['phone' => '5550103002']);

        $this->assertSame($id, app(ClaimRegisteredPatient::class)->handle(self::PHONE, 1));
        $this->assertSame(self::PHONE, DB::table('users')->where('id', $id)->value('phone'));
    }

    public function test_an_account_that_a_pharmacy_already_had_is_left_alone(): void
    {
        $claim = app(ClaimRegisteredPatient::class);

        // Another pharmacy's patient with the same number.
        $this->row('users', ['id' => 10, 'email' => 'a@example.test', 'phone' => self::PHONE, 'role' => 'user', 'pharmacy_id' => 2]);
        $this->assertNull($claim->handle(self::PHONE, 1));

        // A patient another pharmacy removed keeps that pharmacy's history.
        $this->row('users', ['id' => 11, 'email' => 'b@example.test', 'phone' => '(555) 010-3011', 'role' => 'user', 'pharmacy_id' => null]);
        DB::table('deleted_patients')->insert(['user_id' => 11, 'medic_id' => 1, 'reason' => 'moved']);
        $this->assertNull($claim->handle('(555) 010-3011', 1));

        // An account that already has orders.
        $this->row('users', ['id' => 12, 'email' => 'c@example.test', 'phone' => '(555) 010-3012', 'role' => 'user', 'pharmacy_id' => null]);
        $this->row('statuses', ['id' => 1, 'name' => 'New']);
        $this->row('delivery_methods', ['id' => 1]);
        $this->row('delivery_times', ['id' => 1]);
        $this->row('orders', ['id' => 20, 'pharmacy_id' => 2, 'user_id' => 12, 'statuse_id' => 1, 'delivery_method_id' => 1, 'delivery_time_id' => 1]);
        $this->assertNull($claim->handle('(555) 010-3012', 1));

        // Staff and drivers are not patients.
        $this->row('users', ['id' => 13, 'email' => 'd@example.test', 'phone' => '(555) 010-3013', 'role' => 'driver', 'pharmacy_id' => null]);
        $this->assertNull($claim->handle('(555) 010-3013', 1));

        $this->assertNull($claim->handle(null, 1));
        $this->assertNull($claim->handle('(555) 010-9999', 1));
        $this->assertSame([], $this->texts);
        $this->assertSame([2, null, null, null], DB::table('users')->whereIn('id', [10, 11, 12, 13])->orderBy('id')->pluck('pharmacy_id')->all());
    }

    public function test_a_pharmacy_adding_another_pharmacys_number_still_gets_its_own_patient(): void
    {
        $this->row('users', ['id' => 10, 'email' => 'a@example.test', 'phone' => self::PHONE, 'role' => 'user', 'pharmacy_id' => 2]);

        $this->actingAs(User::findOrFail(1))->post('/patients/1/add', $this->form())
            ->assertRedirect('patients/1')->assertSessionHas('success', 'Patient added.');

        $this->assertSame([1, 2], DB::table('users')->where('phone', self::PHONE)->orderBy('pharmacy_id')->pluck('pharmacy_id')->map(fn ($id) => (int) $id)->all());
        $this->assertStringContainsString('Account was created.', $this->texts[0]['body']);
    }

    public function test_an_order_from_the_pharmacys_software_goes_to_the_attached_account(): void
    {
        $id = $this->registerInTheApp();
        DB::table('pharmacys')->where('id', 1)->update(['bestrx_pharmacy_id' => 'BRX1', 'bestrxKey' => 'key', 'bestrxSecret' => 'secret']);
        // The webhook files its orders under status 1, delivery method 4 and delivery time 1 or 2.
        $this->row('statuses', ['id' => 1, 'name' => 'New']);
        $this->row('delivery_methods', ['id' => 4]);
        foreach ([1, 2] as $time) {
            $this->row('delivery_times', ['id' => $time]);
        }

        $this->withHeader('Authorization', 'Basic '.base64_encode('key:secret'))->postJson('/api/bestrx/Order/CreateOrder', [
            'bestrx_order_id' => 'B-1', 'bestrx_unique_order_id' => 'BU-1', 'amount_to_collect' => 0,
            'refrigeration_needed' => false, 'signature_required' => false,
            'store' => ['bestrx_pharmacy_id' => 'BRX1'],
            'recipient' => [
                'first_name' => 'Regina', 'last_name' => 'Stone', 'mobile_phone' => '5550103002', 'home_phone' => '', 'email' => '',
                'address' => ['address_line_1' => '1 Wall St', 'address_line_2' => '', 'city' => 'New York', 'state' => 'NY', 'zip_code' => '10005'],
            ],
            'prescriptions' => [['rx_number' => '700 1', 'partial_fill_seqno' => 0, 'fill_date' => '2026-11-20']],
        ])->assertOk();

        $this->assertSame(1, DB::table('users')->where('phone', self::PHONE)->count());
        $this->assertSame(1, (int) DB::table('users')->where('id', $id)->value('pharmacy_id'));
        $this->assertSame([$id], DB::table('orders')->where('pharmacy_id', 1)->pluck('user_id')->map(fn ($user) => (int) $user)->all());
        $this->assertStringContainsString('added your QuikMedix account to its patients', $this->texts[0]['body']);
    }

    public function test_a_patient_list_import_attaches_the_account_too(): void
    {
        $id = $this->registerInTheApp();
        $file = '/imports/claim-test-'.bin2hex(random_bytes(4)).'.csv';
        @mkdir(public_path('imports'), 0777, true);
        file_put_contents(public_path($file), "First,Last,Phone,Address,Zip\nRegina,Stone,5550103002,1 Wall St,10005\nNew,Person,5550103050,2 Wall St,10005");

        try {
            $this->actingAs(User::findOrFail(1))->post('/patients/1/import', [
                'step' => 2, 'src' => $file, 'delimiter' => ',',
                'name' => 'First', 'last_name' => 'Last', 'phone' => 'Phone', 'address' => 'Address', 'zip' => 'Zip',
            ])->assertRedirect('patients/1');
        } finally {
            @unlink(public_path($file));
        }

        $this->assertSame([$id], DB::table('users')->where('phone', self::PHONE)->pluck('id')->map(fn ($user) => (int) $user)->all());
        $this->assertSame([1, 'Regina'], [(int) DB::table('users')->where('id', $id)->value('pharmacy_id'), DB::table('users')->where('id', $id)->value('name')]);
        $this->assertSame(1, DB::table('users')->where('phone', '(555) 010-3050')->where('pharmacy_id', 1)->count());
    }

    public function test_a_text_that_cannot_be_sent_does_not_leave_the_old_password_working(): void
    {
        $id = $this->registerInTheApp();
        $this->fakeSms(['code' => 21211, 'message' => 'Invalid phone number']);

        $this->assertSame($id, app(ClaimRegisteredPatient::class)->handle(self::PHONE, 1));

        $patient = DB::table('users')->where('id', $id)->first();
        $this->assertSame(1, (int) $patient->pharmacy_id);
        $this->assertFalse(Hash::check('own-secret', $patient->password));
    }
}
