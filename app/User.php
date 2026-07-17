<?php

namespace App;

use Laravel\Passport\HasApiTokens;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Zadarma_API\Api as Zadarma_API;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Redis;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;
    
    protected $table = 'users';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'last_name', 'phone', 'email', 'password', 'role', 'address', 'location', 'apartment', 'zip', 'pharmacy_id', 'isactive', 'ip', 'last_login'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    //protected $appends = ['notifications','generateCode','notification_count','isblocked_or_isactive','get_unread_mess','pharmacy_name'];

    public function generateCode() {
        $code = rand(100000, 999999);

        DB::table('user_codes')->insert(['user_id' => auth()->user()->id,'code' => $code]);
        $message = "Authorization Code ". $code;
        if(empty(auth()->user()->auth_chat_id)){
            $receiverNumber = auth()->user()->phone;
           
            try {
                $twilio = new Client(config('app.twilio_sid'), config('app.twilio_auth_token'));
                $twilio->messages->create("+1".str_replace(" ","",str_replace("-","",str_replace(")","",str_replace("(","",$receiverNumber)))), ["body" => $message, "from" => config('app.twilio_from_phone')]);
            } catch (Exception $e) {
                info("Error: ". $e->getMessage());
            }
        } else {
            $bot = new \TelegramBot\Api\Client('5714507992:AAE2bpWIf3yCpslfLAB23THKzo6T5lXqCIc');
            $bot->sendMessage(auth()->user()->auth_chat_id, $message);
        }    
    }

    public function get_unread_mess() {
        $count_noread=ChMessage::where('to_id',$this->id)->where('seen',0)->count();
        return $count_noread;
    }

    public function get_unread_news() {
        $res=Redis::get(request()->getHttpHost().':get_unread_news:'.$this->id);
        if($res!=NULL) {
            return (int)$res;
        }
        $news = DB::table('news')->count();
        $news_read = DB::table('news_read')->where("user_id",$this->id)->count();
        $res = ($news-$news_read);
        Redis::set(request()->getHttpHost().':get_unread_news:'.$this->id, $res, 'EX', 300);
        return (int)$res;
    }

    public function get_pharmacy_route() {
        $res=Redis::get(request()->getHttpHost().':get_pharmacy_route');
        if($res!=NULL) {
            return (int)$res;
        }
        $res = DB::table('orders')->where("orders.statuse_id",1)->where("ready",'1')->select(DB::raw("count(DISTINCT orders.pharmacy_id) as count"));
        if(!empty($this->zone_id)){
            $res=$res->join('pharmacys','pharmacys.id','=','orders.pharmacy_id')->where('pharmacys.zone_id',$this->zone_id);
        }
        $res=$res->first()->count;
        Redis::set(request()->getHttpHost().':get_pharmacy_route', $res, 'EX', 120);
        return (int)$res;
    }

    public function get_pharmacy_notReady($pharmacy_id) {
        $pharmacys = DB::table('orders')->where('orders.pharmacy_id',$pharmacy_id)->where("orders.statuse_id",1)->where("ready",'0');
        if(!empty($this->zone_id)){
            $pharmacys=$pharmacys->join('pharmacys','pharmacys.id','=','orders.pharmacy_id')->where('pharmacys.zone_id',$this->zone_id);
        }
        $pharmacys=$pharmacys->count();
        return (int)($pharmacys);
    }

    public function pharmacy_name() {
        $res=Redis::get(request()->getHttpHost().':pharmacy_name:'.$this->pharmacy_id);
        if(!empty($res)) {
            return $res;
        }
        if($this->pharmacy_id>0) {
            $res = DB::table('pharmacys')->where('id', $this->pharmacy_id)->value('name') ?? '';
        } else {
            $res = '';
        }
        Redis::set(request()->getHttpHost().':pharmacy_name:'.$this->pharmacy_id, $res, 'EX', 600);
        return $res;
    }

    public function pharmacy_balance() {
        $res=Redis::get(request()->getHttpHost().':pharmacy_balance:'.$this->pharmacy_id);
        if($res!=NULL) {
            return $res;
        }
        if($this->pharmacy_id>0) {
            $res = DB::table('pharmacys')->where('id', $this->pharmacy_id)->value('balance') ?? 0;
        } else {
            $res = 0;
        }
        Redis::set(request()->getHttpHost().':pharmacy_balance:'.$this->pharmacy_id, $res, 'EX', 120);
        return $res;
    }

    public function pharmacy_balance_ban() {
        $res=Redis::get(request()->getHttpHost().':pharmacy_balance_ban:'.$this->pharmacy_id);
        if($res!=NULL) {
            return $res;
        }
        if($this->pharmacy_id>0) {
            $pharmacy = DB::table('pharmacys')->where('id', $this->pharmacy_id)->select('balance', 'balance_ban')->first();
            $pharmacy_balance = $pharmacy->balance ?? 0;
            $pharmacy_balance_ban = $pharmacy->balance_ban ?? '0';
            if($pharmacy_balance_ban==='1') {
                $res = 1;
                if($pharmacy_balance>=0) {
                    DB::table('pharmacys')->where('id', $this->pharmacy_id)->update(['balance_ban'=>'0']);
                    $res = 0;
                }
            } else {
                $res = 0;
            }
        } else {
            $res = 0;
        }
        Redis::set(request()->getHttpHost().':pharmacy_balance_ban:'.$this->pharmacy_id, $res, 'EX', 120);
        return $res;
    }

    public function income_today() {
        $res=Redis::get(request()->getHttpHost().':income_today');
        if($res!=NULL) {
            return number_format($res,2);
        }
        $res = DB::table('pharmacy_payments')->whereDate("pharmacy_payments.created",date("Y-m-d"));
        if(!empty($this->zone_id)){
            $res=$res->join('pharmacys','pharmacys.id','=','pharmacy_payments.pharmacy_id')->where('pharmacys.zone_id',$this->zone_id);
        }
        $res=$res->sum("pharmacy_payments.amount");
        Redis::set(request()->getHttpHost().':income_today', $res, 'EX', 300);
        return number_format($res,2);
    }

    public function copay_cash_today() {
        $res=Redis::get(request()->getHttpHost().':copay_cash_today');
        if($res!=NULL) {
            return number_format($res,2);
        }
        $res = DB::table('cash_log')->whereDate("cash_log.created",date("Y-m-d"));
        if(!empty($this->zone_id)){
            $res=$res->join('orders','orders.id','=','cash_log.order_id')->join('pharmacys','pharmacys.id','=','orders.pharmacy_id')->where('pharmacys.zone_id',$this->zone_id);
        }
        $res=$res->sum("cash_log.copay");
        Redis::set(request()->getHttpHost().':copay_cash_today', $res, 'EX', 300);
        return number_format($res,2);
    }

    public function copay_app_today() {
        $res=Redis::get(request()->getHttpHost().':copay_app_today');
        if($res!=NULL) {
            return number_format($res,2);
        }
        $res = DB::table('payments')->join('orders','payments.order_id','=','orders.id')->where('payments.type','copay')->whereDate("payments.created",date("Y-m-d"));
        if(!empty($this->zone_id)){
            $res=$res->join('pharmacys','pharmacys.id','=','orders.pharmacy_id')->where('pharmacys.zone_id',$this->zone_id);
        }
        $res=$res->sum("orders.copay");
        Redis::set(request()->getHttpHost().':copay_app_today', $res, 'EX', 300);
        return number_format($res,2);
    }

    public function notifications() {
        $notifications = DB::table('notifications')->where('user_id', $this->id)->where('viewed',0)->orderBy('id','desc')->limit(5)->get();
        return $notifications;
    }

    public function notification_count() {
        $res=Redis::get(request()->getHttpHost().':notification_count:'.$this->id);
        if($res!=NULL) {
            return (int)$res;
        }
        $res = DB::table('notifications')->where('user_id', $this->id)->where('viewed',0)->count();
        Redis::set(request()->getHttpHost().':notification_count:'.$this->id, $res, 'EX', 300);
        return $res;
    }

    public function ready_pickup_count() { 
        if($this->pharmacy_id>0) {
            $orders = DB::table('orders')->join('users', 'orders.user_id', '=', 'users.id')->where('orders.pharmacy_id',$this->pharmacy_id)->where('orders.statuse_id',1);
        } else {
            $orders = DB::table('orders')->join('users', 'orders.user_id', '=', 'users.id')->where('orders.statuse_id',1);
        }
        if(!empty($this->zone_id)){
            $orders=$orders->join('pharmacys','pharmacys.id','=','orders.pharmacy_id')->where('pharmacys.zone_id',$this->zone_id);
        }
        $orders=$orders->select(DB::raw('count(distinct orders.id) as count'))->first()->count;
        return $orders;
    }
    
    public function isblocked_or_isactive() {
        if($this->isactive == 0 || $this->isblocked == 1) {
            return true;
        }
        $pharmacy = DB::table('pharmacys')->where('pharmacys.id',$this->pharmacy_id)->first();
        if(!empty($pharmacy)) {
            if($this->role == 'medic' && ($pharmacy->isblocked == 1 || $pharmacy->isactive == 0)) {
                return true;
            }
        }
        return false;
    }

    public function is_massiveBagsTransfer() {
        $pharmacy = DB::table('pharmacys')->where('pharmacys.id',$this->pharmacy_id)->first();
        if(!empty($pharmacy)) {
            if($pharmacy->massiveBagsTransfer>0) {
                return true;
            }
        }
        return false;
    }

    public function zadarma_key() {
        if($this->role == 'superadmin' || $this->role == 'admin' || $this->role == 'logist') {
            $api = new Zadarma_API("35add9dd339d64c38f55", "a444d1a5d8ea9ca6eb43");
            $zadarma_sip = "329486-100";
            $zadarma_key = $api->getWebrtcKey($zadarma_sip);
        } else {
            $zadarma_key = '';
            $zadarma_sip = '';
        }
        return json_encode(["zadarma_sip"=>$zadarma_sip,"zadarma_key"=>$zadarma_key]);
    }

}
