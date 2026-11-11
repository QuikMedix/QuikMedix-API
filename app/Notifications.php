<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Twilio\Rest\Client;
use Pusher\PushNotifications\PushNotifications;

class Notifications extends Model
{
    /**
     * @param int|string $user_id
     * @param string $title
     * @param string $body
     */
    public static function send_push($user_id,$title,$body) {
        $user = DB::table('users')->where('id',$user_id)->first();
        if(!empty($user) && !empty($user->device_token) && \App\Support\FirebaseMessaging::isConfigured()) {
            if($user->role=='user' && !empty($user->pharmacy_id)) {
                $pharmacy = DB::table('pharmacys')->where('id',$user->pharmacy_id)->first();
                if(!empty($pharmacy) && !empty($pharmacy->name)) {
                    $title = $pharmacy->name;
                }
            }
            $result = \App\Support\FirebaseMessaging::send($user->device_token, $title, $body);
            if($result === \App\Support\FirebaseMessaging::RESULT_INVALID_TOKEN) {
                // The app was removed from this device; stop sending to the dead token.
                DB::table('users')->where('id',$user->id)->update(['device_token'=>NULL]);
            }
            return $result;
        } else if(!empty($user) && config('app.twilio_sid') && config('app.twilio_auth_token') && config('app.twilio_from_phone')) {
            if($user->role=='user' && !empty($user->pharmacy_id)) {
                $pharmacy = DB::table('pharmacys')->where('id',$user->pharmacy_id)->first();
                if(!empty($pharmacy) && !empty($pharmacy->name)) {
                    $title = "QuikMedix is greeting you! \n".$pharmacy->name;
                }
            }
            $twilio = new Client(config('app.twilio_sid'), config('app.twilio_auth_token'));
            try {
                $twilio->messages->create("+1".str_replace(" ","",str_replace("-","",str_replace(")","",str_replace("(","",$user->phone)))), ["body" => $title." ".$body, "from" => config('app.twilio_from_phone')]);
            } catch (\Throwable) {
                //throw $th;
            }
        }
    }

    /**
     * @param string[] $user_id
     * @param string $title
     * @param string $body
     * @param string $url
     */
    public static function send_push_web($user_id,$title,$body,$url,$type_text="") {
        foreach($user_id as $user) {
            DB::table('notifications')->insert(['user_id'=>$user,'type'=>'warning','link'=>$url,'text'=>$title.":\n ".$body,"type_text"=>$type_text]);
        }
        if (!config('services.beams.instance_id') || !config('services.beams.secret_key')) {
            return;
        }
        $beamsClient = new PushNotifications([
            'instanceId' => config('services.beams.instance_id'),
            'secretKey' => config('services.beams.secret_key'),
        ]);
        $beamsClient->publishToUsers($user_id,
            [
                "web" => array(
                    "notification" => array(
                        "title" => $title,
                        "body" => $body,
                        "icon" => asset('images/branding/quikmedix-icon-192.png?v=transparent-1'),
                        "deep_link" => $url
                    )
                )
            ]
        );
    }
}
