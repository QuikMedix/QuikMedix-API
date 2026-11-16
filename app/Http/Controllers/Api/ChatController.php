<?php

namespace App\Http\Controllers\Api;

use App\Models\Pharmacy;
use App\Http\Controllers\Controller;
use App\Support\Notifications;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Twilio\Rest\Client;

/**
 * Chat lists and unread counts for the mobile apps.
 */
class ChatController extends Controller
{
    public function chats() {
        $chats_db = DB::table('chats')->orWhere('user1',Auth::user()->id)->orWhere('user2',Auth::user()->id)->orderBy('last_message_date','desc')->get();
        $chats=array();
        foreach ($chats_db as $chat) {
            if($chat->user1==Auth::user()->id) {
                $user_id=$chat->user2;
                $count_noread=$chat->unread_user1;
            } else {
                $user_id=$chat->user1;
                $count_noread=$chat->unread_user2;
            }
            $last_message_date=$chat->last_message_date;
            $last_message_body=$chat->last_message_body;
            $user = DB::table('users')->where('id', $user_id)->first(['image', 'last_name', 'name', 'pharmacy_id']);
            $user_pharmacy = Pharmacy::query()->where('id', $user->pharmacy_id)->first();
            if(empty($user_pharmacy)) {
                $user_pharmacy='';
            } else {
                $user_pharmacy=' ('.$user_pharmacy->name.')';
            }
            array_push($chats,['link'=>"/api/chats/user/$user_id",'count_noread'=>$count_noread,'name'=>$user->name.' '.$user->last_name.$user_pharmacy,'image'=>$user->image,'last_message_date'=>$last_message_date,'last_message_body'=>$last_message_body]);
        }
        return response()->json([
            'chats' => $chats
        ], 200);
    }

    /**
     * @param int|string $user_id
     */
    public function chatUser($user_id) {
        $user = DB::table('users')->where('id', $user_id)->first();
        $user_pharmacy = Pharmacy::query()->where('id', $user->pharmacy_id)->first();
        $user_pharmacy2 = Pharmacy::query()->where('id', Auth::user()->pharmacy_id)->first();
        if(empty($user_pharmacy)) {
            $user_pharmacy='';
        } else {
            $user_pharmacy=' ('.$user_pharmacy->name.')';
        }
        if(empty($user_pharmacy2)) {
            $user_pharmacy2='';
        } else {
            $user_pharmacy2=' ('.$user_pharmacy2->name.')';
        }
        $chat = DB::table('chats')->orWhere('name', $user_id.'_'.Auth::user()->id)->orWhere('name', Auth::user()->id.'_'.$user_id)->first();
        if(!empty($chat->id)) {
            $chat_name = $chat->name;
        } else {
            $chat_name = Auth::user()->id.'_'.$user_id;
            DB::table('chats')->insert(['name'=>$chat_name,'user1'=>Auth::user()->id,'user2'=>$user_id]);
        }
        $chat = DB::table('chats')->orWhere('name', $user_id.'_'.Auth::user()->id)->orWhere('name', Auth::user()->id.'_'.$user_id)->first();
        $twilio = new Client(config('app.twilio_sid'), config('app.twilio_auth_token'));
        $channels = $twilio->chat->v2->services(config('app.twilio_chatServiceSid'))->channels->read(['uniqueName'=>$chat_name]);
        foreach ($channels as $record) {
            if($record->uniqueName==$chat_name) {
                $channel_sid = $record->sid;
            }
        }
        if(empty($channel_sid)) {
            $channel = $twilio->chat->v2->services(config('app.twilio_chatServiceSid'))->channels->create(['friendlyName'=>$chat_name,'uniqueName'=>$chat_name]);
            $channel_sid = $channel->sid;
        }
        $members = $twilio->chat->v2->services(config('app.twilio_chatServiceSid'))->channels($channel_sid)->members->read();
        foreach ($members as $record) {
            if($record->identity==Auth::user()->id) {
                $member_sid = $record->sid;
            }
            if($record->identity==$user_id) {
                $user_last_index = $record->lastConsumedMessageIndex;
            }
        }
        if(empty($member_sid)) {
            $member = $twilio->chat->v2->services(config('app.twilio_chatServiceSid'))->channels($channel_sid)->members->create(Auth::user()->id);
            $member_sid = $member->sid;
        }
        if(empty($user_last_index)) {
            $user_last_index=0;
        }
        $messages = $twilio->chat->v2->services(config('app.twilio_chatServiceSid'))->channels($channel_sid)->messages->read();
        if(!isset(end($messages)->index)) {
            $max_message_index=0;
            $last_message_date=null;
            $last_message_body=null;
        } else {
            $max_message_index=end($messages)->index;
            $last_message_date=end($messages)->dateCreated->setTimezone(new \DateTimeZone('America/New_York'))->format("Y-m-d H:i:s");
            $last_message_body=end($messages)->body;
        }
        $member = $twilio->chat->v2->services(config('app.twilio_chatServiceSid'))->channels($channel_sid)->members($member_sid)->update(["lastConsumedMessageIndex" => $max_message_index]);
        if($chat->user1==Auth::user()->id) {
            DB::table('chats')->where('name',$chat_name)->update(['unread_user1'=>0,'last_message_date'=>$last_message_date,'last_message_body'=>$last_message_body]);
        } else {
            DB::table('chats')->where('name',$chat_name)->update(['unread_user2'=>0,'last_message_date'=>$last_message_date,'last_message_body'=>$last_message_body]);
        }
        return view('chats.chat_api',['user'=>$user,'chat_name'=>$chat_name,'messages'=>$messages,'user_last_index'=>$user_last_index]);
    }

    public function userCountunread() {
        $count_noread=Auth::user()->get_unread_mess();
        return response()->json([
            'count_unread_message' => $count_noread
        ], 200);
    }

    public function get_unread_mess() {
        $count_noread=Auth::user()->get_unread_mess();
        return response()->json([
            'count_noread' => $count_noread
        ], 200);
    }

    /**
     * @param int|string $user_id
     */
    public function testNotification($user_id) {
        $bing = Notifications::send_push($user_id,"QuikMedix","Test Notification");
        return response()->json([
            'message' => $bing
        ], 200);
    }
}
