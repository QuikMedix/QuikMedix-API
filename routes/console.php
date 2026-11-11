<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

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
    $result = \App\Notifications::send_push($userId, 'QuikMedix', 'Test notification');
    $this->line("Result: {$result}");

    return $result === \App\Support\FirebaseMessaging::RESULT_SENT ? 0 : 1;
})->describe('Check Firebase push setup and optionally send a test notification');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->describe('Display an inspiring quote');
