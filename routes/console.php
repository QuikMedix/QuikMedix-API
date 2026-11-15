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
