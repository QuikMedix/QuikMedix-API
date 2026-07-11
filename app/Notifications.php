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
        if(!empty($user) && !empty($user->device_token)) {
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
                'icon'	=> 'myicon',/*Default Icon*/
                'sound'	=> 1,
            );
            $fields = array(
                'to' => $user->device_token,
                'notification'=> $msg
            );
            $headers = array(
                'Authorization: key=AAAAdQbuxsY:APA91bFdvwYwIxAOK1mAfMcbkLguwJnIKs2u19MuOdemG7Cr36c83MGd7nqTBIvg8MSmqF7MbOwsq-BXD9rLWhSDCN3EDStpY9kB2AY77LLuWMoOJHm6ZxJPzFj2u_NZ5HtMn41GdtJv',
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
        } else if(!empty($user)) {
            if($user->role=='user' && !empty($user->pharmacy_id)) {
                $pharmacy = DB::table('pharmacys')->where('id',$user->pharmacy_id)->first();
                if(!empty($pharmacy) && !empty($pharmacy->name)) {
                    $title = "A2B Rx is greeting you! \n".$pharmacy->name;                    
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
        $beamsClient = new PushNotifications(
            array(
              "instanceId" => "686711de-e011-415d-9a38-c8a05adfaae2",
              "secretKey" => "FDC16BE22D1C3F3F8E2595D8056231930FB856732BA999F99B577A5AD1944DD9",
            )
        );
        foreach($user_id as $user) {
            DB::table('notifications')->insert(['user_id'=>$user,'type'=>'warning','link'=>$url,'text'=>$title.":\n ".$body,"type_text"=>$type_text]);
        }
        $publishResponse = $beamsClient->publishToUsers($user_id,
            [
                "web" => array(
                    "notification" => array(
                        "title" => $title,
                        "body" => $body,
                        "icon" => "https://cp.a2brx.com/images/users/0116195432icon.png",
                        "deep_link" => $url
                    )
                )
            ]
        );
    }
}
