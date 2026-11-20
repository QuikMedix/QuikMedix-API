<?php

namespace App\Http\Controllers\Api;

use App\Models\Pharmacy;
use App\Http\Controllers\Controller;
use App\Support\ChatConversations;
use App\Support\Notifications;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
    public function chatUser($user_id, ChatConversations $conversations) {
        $user = DB::table('users')->where('id', $user_id)->first(['id', 'name', 'last_name', 'image']);
        abort_if(empty($user), 404, 'User not found');
        abort_unless($conversations->isConfigured(), 503, 'Chat is not set up.');
        $me = Auth::user()->id;
        $chat = DB::table('chats')->orWhere('name', $user_id.'_'.$me)->orWhere('name', $me.'_'.$user_id)->first();
        if(empty($chat)) {
            DB::table('chats')->insert(['name'=>$me.'_'.$user_id,'user1'=>$me,'user2'=>$user_id]);
            $chat = DB::table('chats')->where('name', $me.'_'.$user_id)->first();
        }
        $opened = $conversations->open($chat->name, (string) $me, (string) $user->id);
        $last = end($opened['messages']) ?: null;
        DB::table('chats')->where('name',$chat->name)->update([
            $chat->user1==$me ? 'unread_user1' : 'unread_user2' => 0,
            'last_message_date' => $last?->dateCreated->setTimezone(new \DateTimeZone('America/New_York'))->format("Y-m-d H:i:s"),
            'last_message_body' => $last?->body,
        ]);
        return view('chats.chat_api',['user'=>$user,'chat_name'=>$chat->name,'messages'=>$opened['messages'],'user_last_index'=>$opened['other_last_read']]);
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
