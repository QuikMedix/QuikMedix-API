<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

/**
 * Text messages, sent through Twilio from the business's registered number (TWILIO_FROM_PHONE).
 */
final class Sms
{
    /** Whether the Twilio account and the sending number are set. */
    public function isConfigured(): bool
    {
        return config('app.twilio_sid') && config('app.twilio_auth_token') && $this->from() !== null;
    }

    /** The number messages are sent from, in the form Twilio expects. */
    public function from(): ?string
    {
        return self::e164(config('app.twilio_from_phone'));
    }

    /**
     * Sends one message. A failure is reported and answered with false, never thrown: no caller
     * should fail because a text could not be sent.
     *
     * @param  string|null  $phone  as stored, for example "(555) 010-0002"
     * @return bool whether Twilio accepted the message
     */
    public function send(?string $phone, string $body): bool
    {
        $to = self::e164($phone);
        if (! $this->isConfigured() || $to === null) {
            Log::warning('Text message not sent: '.($to === null ? 'the phone number is not usable.' : 'Twilio is not configured.'));

            return false;
        }
        try {
            app(Client::class)->messages->create($to, ['from' => $this->from(), 'body' => $body]);

            return true;
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }
    }

    /**
     * A phone number as +<country><number>. Ten digits are a US number; a leading 1 or +1 is
     * accepted, and so is any other number already written with its + country code.
     */
    public static function e164(?string $phone): ?string
    {
        $phone = trim((string) $phone);
        $digits = preg_replace('/\D/', '', $phone);
        if (strlen($digits) === 10) {
            return '+1'.$digits;
        }
        if (strlen($digits) === 11 && $digits[0] === '1') {
            return '+'.$digits;
        }

        return str_starts_with($phone, '+') && strlen($digits) >= 8 && strlen($digits) <= 15 ? '+'.$digits : null;
    }
}
