<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Pusher\PushNotifications\PushNotifications;

class Notifications
{
    /**
     * @param int|string $user_id
     * @param string $title
     * @param string $body
     */
    public static function send_push($user_id,$title,$body) {
        $user = DB::table('users')->where('id',$user_id)->first();
        if(!empty($user) && !empty($user->device_token) && FirebaseMessaging::isConfigured()) {
            if($user->role=='user' && !empty($user->pharmacy_id)) {
                $pharmacy = DB::table('pharmacys')->where('id',$user->pharmacy_id)->first();
                if(!empty($pharmacy) && !empty($pharmacy->name)) {
                    $title = $pharmacy->name;
                }
            }
            $result = FirebaseMessaging::send($user->device_token, $title, $body);
            if($result === FirebaseMessaging::RESULT_INVALID_TOKEN) {
                // The app was removed from this device; stop sending to the dead token.
                DB::table('users')->where('id',$user->id)->update(['device_token'=>NULL]);
            }
            return $result;
        } else if(!empty($user) && app(Sms::class)->isConfigured()) {
            // No app on a device to notify: the notice goes out as a text message.
            if($user->role=='user' && !empty($user->pharmacy_id)) {
                $pharmacy = DB::table('pharmacys')->where('id',$user->pharmacy_id)->first();
                if(!empty($pharmacy) && !empty($pharmacy->name)) {
                    $title = "QuikMedix is greeting you! \n".$pharmacy->name;
                }
            }
            app(Sms::class)->send($user->phone, $title." ".$body);
        }
    }

    /**
     * Notifies a user's app and nothing else: without the app on a device, no text message is
     * sent in its place. For notices sent to many people at once.
     *
     * @param  object  $user  a users row
     */
    public static function pushToApp(object $user, string $title, string $body): void
    {
        if (empty($user->device_token) || ! FirebaseMessaging::isConfigured()) {
            return;
        }
        if (FirebaseMessaging::send($user->device_token, $title, $body) === FirebaseMessaging::RESULT_INVALID_TOKEN) {
            DB::table('users')->where('id', $user->id)->update(['device_token' => null]);
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
