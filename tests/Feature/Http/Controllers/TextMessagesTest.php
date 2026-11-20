<?php

namespace Tests\Feature\Http\Controllers;

use App\Actions\SendWelcomeSms;
use App\Models\User;
use App\Support\Notifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Tests\Support\FakesSms;
use Tests\Support\InsertsRows;
use Tests\TestCase;

/**
 * Every text message the application sends goes out from the registered number, and a password
 * is only replaced when the text carrying the new one was accepted.
 */
class TextMessagesTest extends TestCase
{
    use FakesSms;
    use InsertsRows;

    private const FROM = '+18888988284';

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        config(['services.fcm.credentials' => null, 'services.beams.instance_id' => null]);
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('setex')->andReturn(true);
        $this->row('pharmacys', ['id' => 1, 'name' => 'Corner Pharmacy', 'isactive' => 1, 'isblocked' => 0]);
        $this->row('users', ['id' => 1, 'name' => 'Root', 'email' => 'root@example.test', 'phone' => '(555) 010-0001', 'role' => 'superadmin', 'isactive' => 1, 'isblocked' => 0]);
        $this->row('users', ['id' => 2, 'name' => 'Pat', 'email' => 'pat@example.test', 'phone' => '(555) 010-0002', 'role' => 'user', 'pharmacy_id' => 1, 'password' => Hash::make('old-password'), 'isactive' => 1, 'isblocked' => 0]);
        $this->row('users', ['id' => 3, 'name' => 'Dee', 'email' => 'dee@example.test', 'phone' => '(555) 010-0003', 'role' => 'driver', 'isactive' => 0, 'isblocked' => 0]);
    }

    private function passwordOf(int $userId): string
    {
        return DB::table('users')->where('id', $userId)->value('password');
    }

    public function test_the_sign_in_code_is_texted_to_the_person_signing_in(): void
    {
        $this->fakeSms();
        $this->actingAs(User::findOrFail(1));

        User::findOrFail(1)->generateCode();

        $code = DB::table('user_codes')->where('user_id', 1)->value('code');
        $this->assertSame([['to' => '+15550100001', 'from' => self::FROM, 'body' => 'Authorization Code '.$code]], $this->texts);
    }

    public function test_a_notice_for_someone_without_the_app_goes_out_as_a_text(): void
    {
        $this->fakeSms();

        Notifications::send_push(2, 'Order', 'Your order is on its way.');

        $this->assertSame([['to' => '+15550100002', 'from' => self::FROM, 'body' => "QuikMedix is greeting you! \nCorner Pharmacy Your order is on its way."]], $this->texts);
    }

    public function test_a_new_account_is_texted_its_login(): void
    {
        $this->fakeSms();

        app(SendWelcomeSms::class)->handle((object) ['name' => 'Pat', 'phone' => '(555) 010-0002', 'pharmacy_id' => 1], 'new-secret');

        $this->assertCount(1, $this->texts);
        $this->assertSame(['+15550100002', self::FROM], [$this->texts[0]['to'], $this->texts[0]['from']]);
        $this->assertStringStartsWith("From: Corner Pharmacy \nHello, Pat. Account was created. \nLogin: (555) 010-0002\nPassword: new-secret\n", $this->texts[0]['body']);
    }

    public function test_a_driver_is_texted_when_their_account_is_activated(): void
    {
        $this->fakeSms();

        $this->actingAs(User::findOrFail(1))->post('/settings/drivers', ['activate' => 1, 'user_id' => 3]);

        $this->assertSame(1, (int) DB::table('users')->where('id', 3)->value('isactive'));
        $this->assertSame([['to' => '+15550100003', 'from' => self::FROM, 'body' => "Welcome to QuikMedix, Dee!\nYour account has been verified. Now you can use the app.\nAll the best,\nThe team at QuikMedix"]], $this->texts);
    }

    public function test_staff_can_text_a_patient_a_new_password(): void
    {
        $this->fakeSms();

        $this->actingAs(User::findOrFail(1))->post('/patients/2/resend')->assertOk()->assertExactJson(['message' => 'OK']);

        $this->assertCount(1, $this->texts);
        $this->assertSame(['+15550100002', self::FROM], [$this->texts[0]['to'], $this->texts[0]['from']]);
        $this->assertSame(1, preg_match('/^From: Corner Pharmacy \nHello, Pat\. Account was created\. \nLogin: \(555\) 010-0002\nPassword: ([0-9a-f]{8})\n/', $this->texts[0]['body'], $found));
        $this->assertTrue(Hash::check($found[1], $this->passwordOf(2)));
        $this->assertSame(1, DB::table('action_log')->where(['type' => 'change password', 'user_id' => 2, 'action_user_id' => 1])->count());
    }

    public function test_a_patient_can_ask_for_a_new_password_by_text(): void
    {
        $this->fakeSms();

        $this->postJson('/api/user/reset/', ['phone' => '(555) 010-0002'])->assertOk()->assertExactJson(['message' => 'OK']);

        $this->assertSame(1, preg_match('/^Hello, Pat\. Password has been reset\. \nLogin: \(555\) 010-0002\nPassword: ([0-9a-f]{8})\nBest regards, QuikMedix$/', $this->texts[0]['body'], $found));
        $this->assertSame(['+15550100002', self::FROM], [$this->texts[0]['to'], $this->texts[0]['from']]);
        $this->assertTrue(Hash::check($found[1], $this->passwordOf(2)));
    }

    public function test_the_old_password_stays_when_the_text_with_the_new_one_cannot_be_sent(): void
    {
        $unsubscribed = ['code' => 21610, 'message' => 'Attempt to send to unsubscribed recipient'];
        $error = ['message' => 'An error occurred while sending', 'errors' => 'Error'];

        // Twilio refuses the text.
        $this->fakeSms($unsubscribed);
        $this->actingAs(User::findOrFail(1))->post('/patients/2/resend')->assertExactJson($error);
        $this->assertCount(1, $this->texts);
        $this->fakeSms($unsubscribed);
        $this->postJson('/api/user/reset/', ['phone' => '(555) 010-0002'])->assertExactJson($error);
        $this->assertCount(1, $this->texts);

        // Twilio is not set up at all.
        config(['app.twilio_sid' => null, 'app.twilio_auth_token' => null, 'app.twilio_from_phone' => null]);
        $this->actingAs(User::findOrFail(1))->post('/patients/2/resend')->assertExactJson($error);
        $this->postJson('/api/user/reset/', ['phone' => '(555) 010-0002'])->assertExactJson($error);

        $this->assertTrue(Hash::check('old-password', $this->passwordOf(2)));
        $this->assertSame(0, DB::table('action_log')->count());
    }
}
