<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Console\Tasks\NotifyStaffOfWaitingOrders;
use App\Console\Tasks\RecalculateDriverEtas;
use App\Console\Tasks\CountDownEtas;
use App\Console\Tasks\FlagPharmaciesWithNegativeBalance;
use App\Console\Tasks\CreateWeeklyInvoices;
use App\Console\Tasks\EndExpiredTrials;
use App\Console\Tasks\CancelStalePickupOrders;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('push:test {user_id? : Send a test notification to this user\'s device}', function () {
    $error = \App\Support\FirebaseMessaging::verifyCredentials();
    if ($error !== null) {
        $this->error($error);

        return 1;
    }
    $this->info('Firebase credentials work. The server can send push notifications.');

    $userId = $this->argument('user_id');
    if ($userId === null) {
        return 0;
    }
    $user = \Illuminate\Support\Facades\DB::table('users')->where('id', $userId)->first();
    if (empty($user?->device_token)) {
        $this->warn("User {$userId} has no device token yet. Sign in on the phone and allow notifications first.");

        return 1;
    }
    $result = \App\Support\Notifications::send_push($userId, 'QuikMedix', 'Test notification');
    $this->line("Result: {$result}");

    return $result === \App\Support\FirebaseMessaging::RESULT_SENT ? 0 : 1;
})->describe('Check Firebase push setup and optionally send a test notification');

Artisan::command('sms:test {phone : Send a test text message to this number}', function (\App\Support\Sms $sms) {
    $missing = array_keys(array_filter([
        'TWILIO_SID' => ! config('app.twilio_sid'),
        'TWILIO_AUTH_TOKEN' => ! config('app.twilio_auth_token'),
        'TWILIO_FROM_PHONE' => $sms->from() === null,
    ]));
    if ($missing !== []) {
        $this->error('Not set or not usable in the environment: '.implode(', ', $missing).'.');

        return 1;
    }
    $to = \App\Support\Sms::e164($this->argument('phone'));
    if ($to === null) {
        $this->error('That is not a phone number the app can text. Use ten digits, or + and the country code.');

        return 1;
    }
    $this->line("From {$sms->from()} to {$to}");
    try {
        $this->info('Twilio accepted the message: '.$sms->deliver($to, 'QuikMedix test message.'));

        return 0;
    } catch (\Throwable $exception) {
        $this->error('Twilio refused the message: '.$exception->getMessage());

        return 1;
    }
})->describe('Check the Twilio setup by texting a number you choose');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->describe('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
|
| Each task is a class in app/Console/Tasks. They run in the application's
| timezone.
|
*/

Schedule::call(NotifyStaffOfWaitingOrders::class)->everyThreeMinutes();
Schedule::call(RecalculateDriverEtas::class)->everyFifteenMinutes();
Schedule::call(CountDownEtas::class)->everyMinute();
Schedule::call(FlagPharmaciesWithNegativeBalance::class)->weeklyOn(4, '15:00');
Schedule::call(CreateWeeklyInvoices::class)->weeklyOn(1, '0:00');
Schedule::call(EndExpiredTrials::class)->dailyAt('0:00');
Schedule::call(CancelStalePickupOrders::class)->dailyAt('12:00');
