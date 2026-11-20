<?php

namespace App\Actions;

use App\Support\Branding;
use App\Support\Sms;
use Illuminate\Support\Facades\DB;

class SendWelcomeSms
{
    public function __construct(private Sms $sms) {}

    /**
     * Texts a newly created patient or facility their login. A text that cannot be sent is reported
     * and does not undo the account that was just created.
     */
    public function handle(object $account, string $password): void
    {
        $pharmacyName = DB::table('pharmacys')->where('id', $account->pharmacy_id)->value('name');
        $body = ($pharmacyName ? 'From: '.$pharmacyName." \n" : '')
            .'Hello, '.$account->name.". Account was created. \nLogin: ".$account->phone."\nPassword: ".$password."\n"
            .Branding::appAccessMessage()." \nBest regards, QuikMedix";

        $this->sms->send($account->phone, $body);
    }
}
