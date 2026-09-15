<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Twilio\Rest\Client;
use Pusher\PushNotifications\PushNotifications;

class Notifications extends Model
{
    public static function send_push($user_id,$title,$body) {
        $user = DB::table('users')->where('id',$user_id)->first();
        if(!empty($user) && !empty($user->device_token) && config('services.fcm.server_key')) {
            if($user->role=='user' && !empty($user->pharmacy_id)) {
                $pharmacy = DB::table('pharmacys')->where('id',$user->pharmacy_id)->first();
                if(!empty($pharmacy) && !empty($pharmacy->name)) {
                    $title = $pharmacy->name;
                }
            }
            $msg = array(
                'body' 	=> $body,
                'title'	=> $title,
                'vibrate'=> 1,
                'icon'	=> config('services.fcm.notification_icon'),
                'sound'	=> 1,
            );
            $fields = array(
                'to' => $user->device_token,
                'notification'=> $msg
            );
            $headers = array(
                'Authorization: key='.config('services.fcm.server_key'),
                'Content-Type: application/json'
            );
            $ch = curl_init();
            curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
            curl_setopt( $ch,CURLOPT_POST, true );
            curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
            curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
            curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
            $result = json_decode(curl_exec($ch));
            curl_close( $ch );
            if(isset($result->success) && $result->success==1)  {
                return $result;
            } else {
                return $result;
            }
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
            } catch (\Throwable $th) {
                //throw $th;
            }
        }
    }

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
        $publishResponse = $beamsClient->publishToUsers($user_id,
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
