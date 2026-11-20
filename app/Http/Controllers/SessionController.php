<?php

namespace App\Http\Controllers;

use App\Support\Sms;
use App\Models\Pharmacy;
use App\Models\ActionLog;
use App\Http\Controllers\Concerns\RendersPages;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Support\AccessMessages;

/**
 * Signing out, returning from a signed-in-as session to the superadmin, and sending a patient new
 * sign-in details. Formerly actions of LexaAdmin, moved as they were.
 */
class SessionController extends Controller
{
    use RendersPages;


    public function logout()
    {
        Auth::logout();
        return redirect()->route('login');
    }

    public function back_to_superadmin_user() {
        if(!empty(session('login_superadmin'))) {
            Auth::loginUsingId(session('login_superadmin'));
            session(['login_superadmin' => NULL]);
            return redirect('/');
        } else {
            Auth::logout();
            return redirect('/login');
        }
    }

    /**
     * @param int|string $user_id
     */
    public function reSendAuthMessage($user_id) {
        $user = DB::table('users')->where('id',$user_id)->first();
        abort_unless(!empty($user), 403, AccessMessages::NO_PERMISSION);
        abort_unless(Auth::user()->hasAnyRole('superadmin', 'admin', 'dispadmin', 'logist', 'medic'), 403, AccessMessages::NO_PERMISSION);
        $pharmacy_name = empty($user->pharmacy_id) ? null : Pharmacy::query()->where('id',$user->pharmacy_id)->value('name');
        // The new password is kept only once the text carrying it was accepted, so a text that cannot be sent locks nobody out.
        $password = bin2hex(openssl_random_pseudo_bytes(4));
        $sent = app(Sms::class)->send($user->phone, (!empty($pharmacy_name) ? "From: ".$pharmacy_name." \n" : "")."Hello, ".$user->name.". Account was created. \nLogin: ".$user->phone."\nPassword: ".$password."\n".\App\Support\Branding::appAccessMessage()." \nBest regards, QuikMedix");
        if (! $sent) {
            return json_encode([
                'message' => 'An error occurred while sending',
                'errors' => 'Error'
            ]);
        }
        DB::table('users')->where('id',$user_id)->update(['password'=>Hash::make($password)]);
        ActionLog::query()->insert(['type'=>'change password','user_id'=>$user_id,'action_user_id'=>Auth::user()->id]);
        return json_encode([
            'message' => 'OK'
        ]);
    }
}
