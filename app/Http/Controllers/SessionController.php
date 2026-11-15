<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersLegacyPages;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Twilio\Rest\Client;
use App\Support\AccessMessages;

/**
 * Signing out, returning from a signed-in-as session to the superadmin, and sending a patient new
 * sign-in details. Formerly actions of LexaAdmin, moved as they were.
 */
class SessionController extends Controller
{
    use RendersLegacyPages;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('2fa');
    }

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
        if(!empty($user)) {
            if(Auth::user()->hasAnyRole('superadmin', 'admin', 'dispadmin', 'logist', 'medic')) {
                $password = bin2hex(openssl_random_pseudo_bytes(4));
                DB::table('users')->where('id',$user_id)->update(['password'=>Hash::make($password)]);
                DB::table('action_log')->insert(['type'=>'change password','user_id'=>$user_id,'action_user_id'=>Auth::user()->id]);
                if(!empty($user->pharmacy_id)) {
                    $pharmacy = DB::table('pharmacys')->where('id',$user->pharmacy_id)->first();
                    if(!empty($pharmacy) && !empty($pharmacy->name)) {
                        $pharmacy_name = $pharmacy->name;
                    } else {
                        $pharmacy_name = "";
                    }
                } else {
                    $pharmacy_name = "";
                }
                try {
                    $twilio = new Client(config('app.twilio_sid'), config('app.twilio_auth_token'));
                    if(!empty($pharmacy_name)) {
                        try {
                            $twilio->messages->create("+1".str_replace(" ","",str_replace("-","",str_replace(")","",str_replace("(","",$user->phone)))), ["body" => "From: ".$pharmacy_name." \nHello, ".$user->name.". Account was created. \nLogin: ".$user->phone."\nPassword: ".$password."\n".\App\Support\Branding::appAccessMessage()." \nBest regards, QuikMedix", "from" => config('app.twilio_from_phone')]);
                            return json_encode([
                                'message' => 'OK'
                            ]);
                        } catch (\Throwable) {
                            return json_encode([
                                'message' => 'An error occurred while sending',
                                'errors' => 'Error'
                            ]);
                        }
                    } else {
                        try {
                            $twilio->messages->create("+1".str_replace(" ","",str_replace("-","",str_replace(")","",str_replace("(","",$user->phone)))), ["body" => "Hello, ".$user->name.". Account was created. \nLogin: ".$user->phone."\nPassword: ".$password."\n".\App\Support\Branding::appAccessMessage()." \nBest regards, QuikMedix", "from" => config('app.twilio_from_phone')]);
                            return json_encode([
                                'message' => 'OK'
                            ]);
                        } catch (\Throwable) {
                            return json_encode([
                                'message' => 'An error occurred while sending',
                                'errors' => 'Error'
                            ]);
                        }
                    }
                } catch (\Throwable) {
                    return json_encode([
                        'message' => 'An error occurred while sending',
                        'errors' => 'Error'
                    ]);
                }
            } else {
                return abort(403, AccessMessages::NO_PERMISSION);
            }
        } else {
            return abort(403, AccessMessages::NO_PERMISSION);
        }
    }
}
