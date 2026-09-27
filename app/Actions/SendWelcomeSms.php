<?php

namespace App\Actions;

use App\Support\Branding;
use Illuminate\Support\Facades\DB;
use Twilio\Rest\Client;

class SendWelcomeSms
{
    /**
     * Texts a newly created patient or facility their login. Failures are reported, never thrown,
     * so an SMS outage cannot undo the account that was just created.
     */
    public function handle(object $account, string $password): void
    {
        $pharmacyName = DB::table('pharmacys')->where('id', $account->pharmacy_id)->value('name');
        $body = ($pharmacyName ? 'From: '.$pharmacyName." \n" : '')
            .'Hello, '.$account->name.". Account was created. \nLogin: ".$account->phone."\nPassword: ".$password."\n"
            .Branding::appAccessMessage()." \nBest regards, QuikMedix";

        try {
            $twilio = new Client(config('app.twilio_sid'), config('app.twilio_auth_token'));
            $twilio->messages->create('+1'.preg_replace('/[\s()\-]/', '', $account->phone), ['body' => $body, 'from' => config('app.twilio_from_phone')]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
