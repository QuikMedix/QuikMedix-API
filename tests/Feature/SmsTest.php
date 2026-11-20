<?php

namespace Tests\Feature;

use App\Support\Sms;
use Illuminate\Support\Facades\Exceptions;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\FakesSms;
use Tests\TestCase;
use Twilio\Exceptions\RestException;
use Twilio\Rest\Client;

/**
 * The one place the application sends a text message from.
 */
class SmsTest extends TestCase
{
    use FakesSms;

    /** @return array<string, array{0: ?string, 1: ?string}> */
    public static function phoneNumbers(): array
    {
        return [
            'as the app stores it' => ['(555) 010-0002', '+15550100002'],
            'ten digits' => ['8888988284', '+18888988284'],
            'dashes and spaces' => ['888-898 8284', '+18888988284'],
            'with a leading 1' => ['1 (888) 898-8284', '+18888988284'],
            'already complete' => ['+18888988284', '+18888988284'],
            'another country, written with its code' => ['+44 20 7946 0958', '+442079460958'],
            'too short' => ['898-8284', null],
            'eleven digits without a leading 1' => ['25550100002', null],
            'empty' => ['', null],
            'missing' => [null, null],
        ];
    }

    #[DataProvider('phoneNumbers')]
    public function test_phone_numbers_are_written_the_way_twilio_expects(?string $stored, ?string $expected): void
    {
        $this->assertSame($expected, Sms::e164($stored));
    }

    public function test_a_text_goes_out_from_the_registered_number(): void
    {
        $this->fakeSms(from: '8888988284');

        $this->assertTrue(app(Sms::class)->send('(555) 010-0002', "Hello\nthere"));

        $this->assertSame([['to' => '+15550100002', 'from' => '+18888988284', 'body' => "Hello\nthere"]], $this->texts);
        $this->assertSame(['POST https://api.twilio.com/2010-04-01/Accounts/ACtest/Messages.json'], $this->smsRequests);
    }

    public function test_a_text_twilio_refuses_is_reported_and_answered_with_false(): void
    {
        Exceptions::fake();
        $this->fakeSms(['code' => 21610, 'message' => 'Attempt to send to unsubscribed recipient']);

        $this->assertFalse(app(Sms::class)->send('(555) 010-0002', 'Hello'));

        Exceptions::assertReported(fn (RestException $e) => $e->getCode() === 21610);
    }

    public function test_nothing_is_asked_of_twilio_without_a_sending_number_or_a_usable_recipient(): void
    {
        $this->fakeSms(from: null);
        $this->assertFalse(app(Sms::class)->isConfigured());
        $this->assertFalse(app(Sms::class)->send('(555) 010-0002', 'Hello'));

        $this->fakeSms();
        $this->assertTrue(app(Sms::class)->isConfigured());
        $this->assertFalse(app(Sms::class)->send('898-8284', 'Hello'));
        $this->assertFalse(app(Sms::class)->send(null, 'Hello'));

        config(['app.twilio_auth_token' => null]);
        $this->assertFalse(app(Sms::class)->send('(555) 010-0002', 'Hello'));

        $this->assertSame([], $this->texts);
    }

    public function test_the_application_builds_its_twilio_client_from_the_configured_account(): void
    {
        config(['app.twilio_sid' => 'ACconfigured', 'app.twilio_auth_token' => 'configured-token']);

        $this->assertSame('ACconfigured', app(Client::class)->getAccountSid());
    }

    public function test_the_test_suite_starts_without_twilio_credentials(): void
    {
        $this->assertFalse(app(Sms::class)->isConfigured());
        $this->assertEmpty(config('app.twilio_sid'));
        $this->assertEmpty(config('app.twilio_auth_token'));
    }
}
