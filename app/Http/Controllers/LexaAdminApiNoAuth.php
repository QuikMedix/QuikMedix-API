<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Notifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Twilio\Rest\Client;
use phpseclib\Net\SSH2;
use Pusher\PushNotifications\PushNotifications;

class LexaAdminApiNoAuth extends Controller
{
    public static function zadarma_get(Request $request) {
        if(isset($request->zd_echo)) {
            return $request->zd_echo;
        }
        return true;
    }

    public static function zadarma_post(Request $request) {
        if($request->event=="NOTIFY_ANSWER") {
            DB::table('calls')->insert(["created"=>$request->call_start,"call_id"=>$request->pbx_call_id,"to"=>$request->destination]);
        }
        if(isset($request->zd_echo)) {
            return $request->zd_echo;
        }
        return true;
    }

    public function telegramAuth(Request $request) {
        try {
            $bot = new \TelegramBot\Api\Client('5714507992:AAE2bpWIf3yCpslfLAB23THKzo6T5lXqCIc');
            $bot->command('start', function ($message) use ($bot) {
                $buttons = [[
                    ['text' => 'Send phone number', 'request_contact' => true]
                ]];
                $replyMarkup = new \TelegramBot\Api\Types\ReplyKeyboardMarkup($buttons, true, false, true);
                $bot->sendMessage($message->getChat()->getId(), 'Please, click "Send phone number" button for registration.', null, false, null, null, $replyMarkup);
            });
            $bot->on(function (\TelegramBot\Api\Types\Update $update) use ($bot) {
                $message = $update->getMessage();
                if ($message->getContact() != null) {
                    $phone0 = $message->getContact()->getPhoneNumber();
                    if((substr($phone0,0,2)=='38' || substr($phone0,0,2)=='99') && strlen($phone0)>11){
                        $phone0='1'.substr($phone0,2);
                    }
                    $phone0 = '+'.$phone0;
                    if(preg_match('/^\+\d(\d{3})(\d{3})(\d{4})$/', $phone0,  $matches )) {
                        $phone = '('.$matches[1].') ' .$matches[2] . '-' . $matches[3];
                    } else {
                        if(preg_match('/^(\d{3})(\d{3})(\d{4})$/', $phone0,  $matches )) {
                            $phone = '('.$matches[1].') ' .$matches[2] . '-' . $matches[3];
                        } else {
                            if(preg_match('/^(\d{3})\-(\d{3})\-(\d{4})$/', $phone0,  $matches )) {
                                $phone = '('.$matches[1].') ' .$matches[2] . '-' . $matches[3];
                            } else {
                                $phone = $phone0;
                            }
                        }
                    }
                    $user = DB::table('users')->where('isactive',1)->where('isblocked',0)->where('role','admin')->where('phone',$phone)->first();
                    if(empty($user)) {
                        $bot->sendMessage($message->getChat()->getId(), '❌ User admin with this phone was not found!');
                    } else {
                        DB::table('users')->where('id',$user->id)->update(['auth_chat_id'=>$message->getChat()->getId()]);
                        $bot->sendMessage($message->getChat()->getId(), '✅ Phone added successfully, continue authorization on the site, the confirmation code will be sent here. (Relogin or click "Resend Code?")');
                    }
                } else {
                    $bot->sendMessage($message->getChat()->getId(), 'Your message: ' . $message->getText());
                }
            }, function () {
                return true;
            });
            
            $bot->run();
        } catch (\Throwable $e) {
            file_get_contents("https://api.telegram.org/bot1067998687:AAFrchkKqMoxkMBjSXovy7Qdc_rz8h_pgfc/sendMessage?chat_id=354637912&text=test ".json_encode($e->getMessage()));
        }
        
        return true;
    }

    public function livetex_hook(Request $request) {
        file_get_contents("https://api.telegram.org/bot1067998687:AAFrchkKqMoxkMBjSXovy7Qdc_rz8h_pgfc/sendMessage?chat_id=354637912&text=test ".json_encode($request));
        return true;
    }
    
    public static function pusher_auth(Request $request) {
        if(!empty($request->user())) {
            $userID = $request->user()->id; // If you use a different auth system, do your checks here
            $userIDInQueryParam = $request->input('user_id');
    
            if ($userID != $userIDInQueryParam) {
                return response('Inconsistent request', 401);
            } else {
                $beamsClient = new PushNotifications(
                    array(
                    "instanceId" => "686711de-e011-415d-9a38-c8a05adfaae2",
                    "secretKey" => "FDC16BE22D1C3F3F8E2595D8056231930FB856732BA999F99B577A5AD1944DD9",
                    )
                );
                $beamsToken = $beamsClient->generateToken((string)$userID);
                return response()->json($beamsToken);
            }
        } else {
            return response('Inconsistent request', 401);
        }
    }

    public static function github_pull(Request $request) {
        $ssh = new SSH2("50.21.190.242");
        if (!$ssh->login("a2brx", "3H2o7G8p")) {
            $output ='Login Failed';
        } else {
            $output = $ssh->exec("cd www/test.a2brx.com && git pull");
        }
        $ssh->disconnect();
        return $output;
    }

    public static function github_pull_zoz(Request $request) {
        $ssh = new SSH2("198.71.61.246");
        if (!$ssh->login("zoz", "rE2fT1zM0ubN9e")) {
            $output ='Login Failed';
        } else {
            $output = $ssh->exec("cd www/zozland.com && git pull");
        }
        $ssh->disconnect();
        return $output;
    }

    public static function pharmacyList() {
        $pharmacys = DB::table('pharmacys')->select('id','name','address')->get();
        return response()->json([
            'pharmacys' => $pharmacys
        ], 200);
    }

    public static function driversUsersAddHandler(Request $request) {
        $validator = Validator::make($request->all(), [
            'password' => 'required|min:8',
            'password2' => 'required|min:8',
            'name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'email' => 'required|max:155',
            'phone' => 'required|max:155',
            'driving_license' => 'required|max:155',
            'driving_license_img' => 'mimes:jpeg,jpg,png|max:10048',
            'identification_cards' => 'required|max:155',
            'car_info' => 'required|max:255',
            'pharmacy_id' => 'max:255',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Something is wrong with this field!',
                'errors' => 'Bad Request'
            ], 400);
        }
        if(!empty(DB::table('users')->where('email', $request->input('email'))->first())) {
            return response()->json([
                'message' => 'User with this email already exists',
                'errors' => 'User already exists'
            ], 409);
        } else {
            if(!empty(DB::table('users')->where('phone', $request->input('phone'))->first())) {
                return response()->json([
                    'message' => 'User with this phone already exists',
                    'errors' => 'User already exists'
                ], 409);
            } else {
                if($request->hasFile('image')) {
                    $file = $request->file('image');
                    $file->move(public_path() . '/images/users/',date('mdHis').$request->file('image')->getClientOriginalName());
                    $src = '/images/users/'.date('mdHis').$request->file('image')->getClientOriginalName();
                } else {
                    $src = '';
                }
                $driving_license_img = NULL;
                if($request->hasFile('driving_license_img')) {
                    $file = $request->file('driving_license_img');
                    $file->move(public_path() . '/images/driving_license/',date('mdHis').$request->file('driving_license_img')->getClientOriginalName());
                    $driving_license_img = '/images/driving_license/'.date('mdHis').$request->file('driving_license_img')->getClientOriginalName();
                }
                if($request->input('password')!='') {
                    if($request->input('password')==$request->input('password2')) {
                        $password = Hash::make($request->input('password'));
                    } else {
                        return response()->json([
                            'message' => 'Passwords must match',
                            'errors' => 'Bad Request'
                        ], 400);
                    }
                }
                if($request->input('pharmacy_id')>0) {
                    $pharmacy_id = $request->input('pharmacy_id');
                    DB::table('users')->insert(['role' => 'driver','name' => $request->input('name'),'last_name' => $request->input('last_name'),'email' => $request->input('email'),'phone' => $request->input('phone'),'password' => $password,'driving_license' => $request->input('driving_license'),'driving_license_img' => $driving_license_img,'identification_cards' => $request->input('identification_cards'),'car_info' => $request->input('car_info'),'pharmacy_id' => $pharmacy_id]);
                } else {
                    DB::table('users')->insert(['role' => 'driver','name' => $request->input('name'),'last_name' => $request->input('last_name'),'email' => $request->input('email'),'phone' => $request->input('phone'),'password' => $password,'driving_license' => $request->input('driving_license'),'driving_license_img' => $driving_license_img,'identification_cards' => $request->input('identification_cards'),'car_info' => $request->input('car_info')]);
                }
                return response()->json([
                    'message' => 'Driver successfully created'
                ], 200);
            }
        }
    }

    public function new_message(Request $request) {
        if(!empty($request->input('chat_name')) && !empty($request->input('created')) && !empty($request->input('body')) && !empty($request->input('user'))) {
            $user=$request->input('user');
            $chat_name=$request->input('chat_name');
            $created=date("Y-m-d H:i:s",strtotime($request->input('created')));
            $body=$request->input('body');
            $chat = DB::table('chats')->where('name',$chat_name)->first();
            if($chat->user1==$user) {
                if($request->input('not_me_author')>0) {
                    $unread_user=0;
                } else {
                    $unread_user=$chat->unread_user2+1;
                }
                $user = DB::table('users')->where('id', $chat->user1)->first();
                $user_pharmacy = DB::table('pharmacys')->where('id', $user->pharmacy_id)->first();
                if(empty($user_pharmacy)) {
                    $user_pharmacy='';
                } else {
                    $user_pharmacy=' ('.$user_pharmacy->name.')';
                }
                $user2 = DB::table('users')->where('id', $chat->user2)->first();
                Notifications::send_push($user2->id,"A2BRx","You have a new incoming message from ".$user->name.' '.$user->last_name.$user_pharmacy);
                DB::table('chats')->where('name',$chat_name)->update(['unread_user1'=>0,'unread_user2'=>$unread_user,'last_message_date'=>$created,'last_message_body'=>$body]);
            } else {
                if($request->input('not_me_author')>0) {
                    $unread_user=0;
                } else {
                    $unread_user=$chat->unread_user1+1;
                }
                $user = DB::table('users')->where('id', $chat->user2)->first();
                $user_pharmacy = DB::table('pharmacys')->where('id', $user->pharmacy_id)->first();
                if(empty($user_pharmacy)) {
                    $user_pharmacy='';
                } else {
                    $user_pharmacy=' ('.$user_pharmacy->name.')';
                }
                $user2 = DB::table('users')->where('id', $chat->user1)->first();
                Notifications::send_push($user2->id,"A2BRx","You have a new incoming message from ".$user->name.' '.$user->last_name.$user_pharmacy);
                DB::table('chats')->where('name',$chat_name)->update(['unread_user2'=>0,'unread_user1'=>$unread_user,'last_message_date'=>$created,'last_message_body'=>$body]);
            }
            
            return json_encode([
                'result' => 'true'
            ]);
        } else {
            return json_encode([
                'message' => 'Failed data',
                'errors' => 'Not Found'
            ]);
        }
    }

    public function getUserInfo(Request $request) {
        $user = DB::table('users')->where('id', $request->input('identity'))->first();
        if(!empty($user->id)) {
            return json_encode([
                'name' => $user->name.' '.$user->last_name,
                'image' => $user->image
            ]);
        } else {
            return json_encode([
                'message' => 'User not found',
                'errors' => 'Not Found'
            ]);
        }
    }

    public function reSendAuthMessage(Request $request) {
        $user = DB::table('users')->where('phone',$request->input('phone'))->first();
        if(!empty($user)) {
            $password = bin2hex(openssl_random_pseudo_bytes(4));
            DB::table('users')->where('id',$user->id)->update(['password'=>Hash::make($password)]);
            DB::table('action_log')->insert(['type'=>'change password','user_id'=>$user->id,'action_user_id'=>$user->id]);
            $twilio = new Client(config('app.twilio_sid'), config('app.twilio_auth_token'));
            try {
                $twilio->messages->create("+1".str_replace(" ","",str_replace("-","",str_replace(")","",str_replace("(","",$user->phone)))), ["body" => "Hello, ".$user->name.". Password has been reset. \nLogin: ".$user->phone."\nPassword: ".$password."\nBest regards, A2B Rx Inc.", "from" => config('app.twilio_from_phone')]);
                return response()->json([
                    'message' => 'OK'
                ], 200);
            } catch (\Throwable $th) {
                return json_encode([
                    'message' => 'An error occurred while sending',
                    'errors' => 'Error'
                ]);
            }
        } else {
            return response()->json([
                'message' => 'User with this phone',
                'errors' => 'Not Found'
            ], 404);
        }
    }

}
