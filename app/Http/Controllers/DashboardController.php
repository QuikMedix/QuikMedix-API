<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\LoadsOrderLookups;
use App\Http\Controllers\Concerns\RendersLegacyPages;
use App\Support\PageLinks;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Support\AccessMessages;

/**
 * The dashboard, the search box's results, the notifications list and the delivery calendar.
 * Formerly actions of LexaAdmin, moved as they were.
 */
class DashboardController extends Controller
{
    use RendersLegacyPages;
    use LoadsOrderLookups;

    public function __construct()
    {
        // Kept from LexaAdmin, whose pages load whole result sets at once.
        ini_set('memory_limit', '-1');
    }

    public function home() {
        return redirect()->route('home');
    }

    public function root() {
        if(Auth::user()->role == 'medic'){
            $res_arr=self::get_cached_value(request()->getHttpHost().':medic_dash:'.Auth::user()->pharmacy_id);
            if(!empty($res_arr)) {
                return view('dashboard.index',$res_arr);
            }
            $count_orders = DB::table('orders')->select(DB::raw('count(orders.id) as count0'))->where('pharmacy_id', Auth::user()->pharmacy_id)->where('statuse_id', '4')->whereYear('created', '=', date('Y', strtotime('now')))->whereMonth('created', '=', date('m', strtotime('now')))->first()->count0;
            $total_count_orders = DB::table('orders')->select(DB::raw('count(orders.id) as count0'))->where('pharmacy_id', Auth::user()->pharmacy_id)->where('statuse_id', '4')->first()->count0;
            $total_count_orders_quikmedix = DB::table('orders')->join('users',function ($join) {
                $join->on('orders.driver_id', '=' , 'users.id') ;
                $join->whereNull('users.pharmacy_id');
            })->select(DB::raw('count(orders.id) as count0'))->where('orders.pharmacy_id', Auth::user()->pharmacy_id)->where('orders.statuse_id', '4')->first()->count0;
            $orders_this = DB::table('orders')->select(DB::raw('count(orders.id) as count0'))->where('pharmacy_id', Auth::user()->pharmacy_id)->whereYear('created', '=', date('Y', strtotime('now')))->whereMonth('created', '=', date('m', strtotime('now')))->where('statuse_id', '4')->first()->count0;
            $orders_prev = DB::table('orders')->select(DB::raw('count(orders.id) as count0'))->where('pharmacy_id', Auth::user()->pharmacy_id)->whereYear('created', '=', date('Y', strtotime('now')))->whereMonth('created', '=', date('m', strtotime('now -1 month')))->where('statuse_id', '4')->first()->count0;
            if($orders_prev>0) {
                $orders_proc = ceil(($orders_this-$orders_prev)/$orders_prev*100);
            } else {
                $orders_proc = 0;
            }
            $new_patients = DB::table('users')->select(DB::raw('count(users.id) as count'))->where('pharmacy_id', Auth::user()->pharmacy_id)->where('isactive', '1')->where('role', 'user')->first()->count;
            $patients_this = DB::table('users')->select(DB::raw('count(users.id) as count0'))->where('pharmacy_id', Auth::user()->pharmacy_id)->where('isactive', '1')->where('role', 'user')->whereYear('created_at', '=', date('Y', strtotime('now')))->whereMonth('created_at', '=', date('m', strtotime('now')))->first()->count0;
            $patients_prev = DB::table('users')->select(DB::raw('count(users.id) as count0'))->where('pharmacy_id', Auth::user()->pharmacy_id)->where('isactive', '1')->where('role', 'user')->whereYear('created_at', '=', date('Y', strtotime('now')))->whereMonth('created_at', '=', date('m', strtotime('now -1 month')))->first()->count0;
            if($patients_prev>0) {
                $patients_proc = ceil(($patients_this-$patients_prev)/$patients_prev*100);
            } else {
                $patients_proc = 0;
            }
            $new_patients_app = DB::table('users')->select(DB::raw('count(users.id) as count'))->where('pharmacy_id', Auth::user()->pharmacy_id)->where('isactive', '1')->where('role', 'user')->whereNotNull("os")->first()->count;
            $patients = DB::table('users')->where('pharmacy_id', Auth::user()->pharmacy_id)->where('isactive', '1')->orderBy('id','desc')->limit(6)->get();
            $orders = DB::table('orders')->join('statuses', 'orders.statuse_id', '=', 'statuses.id')->join('users', 'orders.user_id', '=', 'users.id')->select('orders.id','orders.statuse_id','orders.created','users.image','users.name','users.last_name','statuses.name as statusename','statuses.color as statusecolor')->where('orders.pharmacy_id', Auth::user()->pharmacy_id)->where('orders.statuse_id', 4)->orderBy('orders.id','desc')->limit(6)->get();
            $chartDelivered = DB::table('orders')->where('statuse_id',4)->where('orders.pharmacy_id', Auth::user()->pharmacy_id)->select(DB::raw("count(id) as count"), DB::raw("DATE_FORMAT(created, '%m-%Y') date"))->groupby(DB::raw("DATE_FORMAT(created, '%m-%Y')"))->orderBy('id','desc')->limit(12)->get()->reverse();
            $orders0 = DB::table('orders')->join('statuses', 'orders.statuse_id', '=', 'statuses.id')->join('users', 'orders.user_id', '=', 'users.id')->select('orders.id','orders.statuse_id','orders.created','users.image','users.name','users.last_name','statuses.name as statusename','statuses.color as statusecolor')->where('orders.pharmacy_id', Auth::user()->pharmacy_id)->where('orders.statuse_id', 1)->orderBy('orders.id','desc')->limit(6)->get();
            $app_android_users = DB::table("users")->where("role","user")->where("os","1")->count();
            $app_android_drivers = DB::table("users")->where("role","driver")->where("os","1")->count();
            $app_ios_users = DB::table("users")->where("role","user")->where("os","2")->count();
            $app_ios_drivers = DB::table("users")->where("role","driver")->where("os","2")->count();
            $pageviews = array();
            $count_orders_todays = DB::table('orders')->select("orders.statuse_id",DB::raw('count(orders.id) as count'))->where('orders.pharmacy_id', Auth::user()->pharmacy_id)->whereDate('orders.created', Carbon::today())->groupBy("orders.statuse_id")->get();
            $count_orders_today = array();
            foreach($count_orders_todays as $item) {
                $count_orders_today[$item->statuse_id] = $item->count;
            }
            $count_orders_today[4] = DB::table('orders')->select(DB::raw('count(orders.id) as count0'))->where('orders.pharmacy_id', Auth::user()->pharmacy_id)->whereDate('orders.finish', Carbon::today())->where('statuse_id', '4')->first()->count0;
            $count_orders_today[201] = DB::table('orders')->select(DB::raw('count(orders.id) as count0'))->where('orders.pharmacy_id', Auth::user()->pharmacy_id)->whereDate('orders.created', Carbon::today())->where('delivery_time_id','2')->first()->count0;
            $count_orders_today[202] = DB::table('orders')->select(DB::raw('count(orders.id) as count0'))->where('orders.pharmacy_id', Auth::user()->pharmacy_id)->whereDate('orders.created', Carbon::today())->whereIn('delivery_time_id',['3','4'])->first()->count0;
            $count_orders_alls = DB::table('orders')->select("orders.statuse_id",DB::raw('count(orders.id) as count'))->where('orders.pharmacy_id', Auth::user()->pharmacy_id)->groupBy("orders.statuse_id")->get();
            $count_orders_all = array();
            foreach($count_orders_alls as $item) {
                $count_orders_all[$item->statuse_id] = $item->count;
            }
            $count_orders_merchant = DB::table('orders')->select(DB::raw('count(orders.id) as count0'))->where('pharmacy_id', Auth::user()->pharmacy_id)->where('merchantOrder','1')->where('statuse_id', '1')->first()->count0;
            $orders_7days = DB::table('orders')->select(DB::raw('DATE(orders.finish) as date'),DB::raw('count(orders.id) as count'))->whereBetween('finish', [Carbon::now()->subDays(7),Carbon::now()])->where('statuse_id', '4')->where('orders.pharmacy_id', Auth::user()->pharmacy_id)->groupBy(DB::raw('DATE(orders.finish)'))->orderBy(DB::raw('DATE(orders.finish)'),'asc')->get();
            $orders_7days_c = DB::table('orders')->select(DB::raw('DATE(orders.created) as date'),DB::raw('count(orders.id) as count'))->whereBetween('created', [Carbon::now()->subDays(7),Carbon::now()])->where('orders.pharmacy_id', Auth::user()->pharmacy_id)->groupBy(DB::raw('DATE(orders.created)'))->orderBy(DB::raw('DATE(orders.created)'),'asc')->get();
            $res_arr = ['count_orders'=>$count_orders,'total_count_orders'=>$total_count_orders,'total_count_orders_quikmedix'=>$total_count_orders_quikmedix,'new_patients'=>$new_patients,'new_patients_app'=>$new_patients_app,'pageviews'=>$pageviews,'patients'=>$patients,'orders'=>$orders,'orders0'=>$orders0,'chartDelivered'=>$chartDelivered,'app_android_users'=>$app_android_users,'app_android_drivers'=>$app_android_drivers,'app_ios_users'=>$app_ios_users,'app_ios_drivers'=>$app_ios_drivers,'orders_proc'=>$orders_proc,
            'patients_proc'=>$patients_proc,
            'orders_7days'=>$orders_7days,
            'orders_7days_c'=>$orders_7days_c,
            'count_orders_today'=>$count_orders_today,
            'count_orders_all'=>$count_orders_all,
            'count_orders_merchant'=>$count_orders_merchant,
            'title'=>'Dashboard','br1'=>'Dashboard'];
            Redis::setex(request()->getHttpHost().':medic_dash:'.Auth::user()->pharmacy_id, 200, serialize($res_arr));
            return view('dashboard.index',$res_arr);
        } else {
            $res_arr=self::get_cached_value(request()->getHttpHost().':admin_dash');
            if(!empty($res_arr)) {
                return view('dashboard.index',$res_arr);
            }
            $count_orders = DB::table('orders')->select(DB::raw('count(orders.id) as count0'))->whereYear('orders.created', '=', date('Y', strtotime('now')))->whereMonth('orders.created', '=', date('m', strtotime('now')))->where('statuse_id', '4');
            if(!empty(Auth::user()->zone_id)){
                $count_orders=$count_orders->join('pharmacys','pharmacys.id','=','orders.pharmacy_id')->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $count_orders=$count_orders->first()->count0;
            $total_count_orders = DB::table('orders')->select(DB::raw('count(orders.id) as count0'))->where('statuse_id', '4');
            if(!empty(Auth::user()->zone_id)){
                $total_count_orders=$total_count_orders->join('pharmacys','pharmacys.id','=','orders.pharmacy_id')->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $total_count_orders=$total_count_orders->first()->count0;
            $total_count_orders_quikmedix = DB::table('orders')->join('users',function ($join) {
                $join->on('orders.driver_id', '=' , 'users.id') ;
                $join->whereNull('users.pharmacy_id');
            })->select(DB::raw('count(orders.id) as count0'))->where('orders.statuse_id', '4');
            if(!empty(Auth::user()->zone_id)){
                $total_count_orders_quikmedix=$total_count_orders_quikmedix->join('pharmacys','pharmacys.id','=','orders.pharmacy_id')->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $total_count_orders_quikmedix=$total_count_orders_quikmedix->first()->count0;
            $orders_this = DB::table('orders')->select(DB::raw('count(orders.id) as count0'))->whereBetween('orders.created', [Carbon::now()->startOfMonth(),Carbon::now()->addDays(1)])->where('statuse_id', '4');
            if(!empty(Auth::user()->zone_id)){
                $orders_this=$orders_this->join('pharmacys','pharmacys.id','=','orders.pharmacy_id')->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $orders_this=$orders_this->first()->count0;
            $orders_prev = DB::table('orders')->select(DB::raw('count(orders.id) as count0'))->whereBetween('orders.created', [Carbon::now()->startOfMonth()->subMonth(),Carbon::now()->startOfMonth()->subMonth()->addDays(intval(date('d', strtotime('now'))))])->where('statuse_id', '4');
            if(!empty(Auth::user()->zone_id)){
                $orders_prev=$orders_prev->join('pharmacys','pharmacys.id','=','orders.pharmacy_id')->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $orders_prev=$orders_prev->first()->count0;
            if($orders_prev>0) {
                $orders_proc = ceil(($orders_this-$orders_prev)/$orders_prev*100);
            } else {
                $orders_proc = 0;
            }
            $count_pharmacies = DB::table('pharmacys')->select(DB::raw('count(pharmacys.id) as count0'));
            if(!empty(Auth::user()->zone_id)){
                $count_pharmacies=$count_pharmacies->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $count_pharmacies=$count_pharmacies->first()->count0;
            $count_pharmacies_active = DB::table('pharmacys')->select(DB::raw('count(pharmacys.id) as count0'))->where("isactive",1)->where("isblocked",0);
            if(!empty(Auth::user()->zone_id)){
                $count_pharmacies_active=$count_pharmacies_active->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $count_pharmacies_active=$count_pharmacies_active->first()->count0;
            $pharmacies_this = DB::table('pharmacys')->select(DB::raw('count(pharmacys.id) as count0'))->whereBetween('created', [Carbon::now()->startOfMonth(),Carbon::now()->addDays(1)]);
            if(!empty(Auth::user()->zone_id)){
                $pharmacies_this=$pharmacies_this->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $pharmacies_this=$pharmacies_this->first()->count0;
            $pharmacies_prev = DB::table('pharmacys')->select(DB::raw('count(pharmacys.id) as count0'))->whereBetween('created', [Carbon::now()->startOfMonth()->subMonth(),Carbon::now()->startOfMonth()->subMonth()->addDays(intval(date('d', strtotime('now'))))]);
            if(!empty(Auth::user()->zone_id)){
                $pharmacies_prev=$pharmacies_prev->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $pharmacies_prev=$pharmacies_prev->first()->count0;
            if($pharmacies_prev>0) {
                $pharmacies_proc = ceil(($pharmacies_this-$pharmacies_prev)/$pharmacies_prev*100);
            } else {
                $pharmacies_proc = 0;
            }
            $count_drivers = DB::table('users')->select(DB::raw('count(users.id) as count0'))->where('isactive', '1')->where('role', 'driver');
            if(!empty(Auth::user()->zone_id)){
                $count_drivers=$count_drivers->where(function ($query) {
                    $query->where('users.zone_id',Auth::user()->zone_id)
                        ->orWhereNull('users.zone_id');
                });
            }
            $count_drivers=$count_drivers->first()->count0;
            $count_drivers_all = DB::table('users')->select(DB::raw('count(users.id) as count0'))->where('role', 'driver');
            if(!empty(Auth::user()->zone_id)){
                $count_drivers_all=$count_drivers_all->where(function ($query) {
                    $query->where('users.zone_id',Auth::user()->zone_id)
                        ->orWhereNull('users.zone_id');
                });
            }
            $count_drivers_all=$count_drivers_all->first()->count0;
            $count_drivers_pharmacy = DB::table('users')->select(DB::raw('count(users.id) as count0'))->where('role', 'driver')->whereNotNull("pharmacy_id");
            if(!empty(Auth::user()->zone_id)){
                $count_drivers_pharmacy=$count_drivers_pharmacy->where(function ($query) {
                    $query->where('users.zone_id',Auth::user()->zone_id)
                        ->orWhereNull('users.zone_id');
                });
            }
            $count_drivers_pharmacy=$count_drivers_pharmacy->first()->count0;
            $drivers_this = DB::table('users')->select(DB::raw('count(users.id) as count0'))->where('isactive', '1')->where('role', 'driver')->whereBetween('created_at', [Carbon::now()->startOfMonth(),Carbon::now()->addDays(1)]);
            if(!empty(Auth::user()->zone_id)){
                $drivers_this=$drivers_this->where(function ($query) {
                    $query->where('users.zone_id',Auth::user()->zone_id)
                        ->orWhereNull('users.zone_id');
                });
            }
            $drivers_this=$drivers_this->first()->count0;
            $drivers_prev = DB::table('users')->select(DB::raw('count(users.id) as count0'))->where('isactive', '1')->where('role', 'driver')->whereBetween('created_at', [Carbon::now()->startOfMonth()->subMonth(),Carbon::now()->startOfMonth()->subMonth()->addDays(intval(date('d', strtotime('now'))))]);
            if(!empty(Auth::user()->zone_id)){
                $drivers_prev=$drivers_prev->where(function ($query) {
                    $query->where('users.zone_id',Auth::user()->zone_id)
                        ->orWhereNull('users.zone_id');
                });
            }
            $drivers_prev=$drivers_prev->first()->count0;
            if($drivers_prev>0) {
                $drivers_proc = ceil(($drivers_this-$drivers_prev)/$drivers_prev*100);
            } else {
                $drivers_proc = 0;
            }
            $count_patients = DB::table('users')->select(DB::raw('count(users.id) as count0'))->where('users.isactive', '1')->where('role', 'user');
            if(!empty(Auth::user()->zone_id)){
                $count_patients=$count_patients->join('pharmacys','pharmacys.id','=','users.pharmacy_id')->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $count_patients=$count_patients->first()->count0;
            $patients_this = DB::table('users')->select(DB::raw('count(users.id) as count0'))->where('users.isactive', '1')->where('role', 'user')->whereBetween('created_at', [Carbon::now()->startOfMonth(),Carbon::now()->addDays(1)]);
            if(!empty(Auth::user()->zone_id)){
                $patients_this=$patients_this->join('pharmacys','pharmacys.id','=','users.pharmacy_id')->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $patients_this=$patients_this->first()->count0;
            $patients_prev = DB::table('users')->select(DB::raw('count(users.id) as count0'))->where('users.isactive', '1')->where('role', 'user')->whereBetween('created_at', [Carbon::now()->startOfMonth()->subMonth(),Carbon::now()->startOfMonth()->subMonth()->addDays(intval(date('d', strtotime('now'))))]);
            if(!empty(Auth::user()->zone_id)){
                $patients_prev=$patients_prev->join('pharmacys','pharmacys.id','=','users.pharmacy_id')->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $patients_prev=$patients_prev->first()->count0;
            if($patients_prev>0) {
                $patients_proc = ceil(($patients_this-$patients_prev)/$patients_prev*100);
            } else {
                $patients_proc = 0;
            }
            $count_orders_todays = DB::table('orders')->select("orders.statuse_id",DB::raw('count(orders.id) as count'))->whereDate('orders.created', Carbon::today())->groupBy("orders.statuse_id");
            if(!empty(Auth::user()->zone_id)){
                $count_orders_todays=$count_orders_todays->join('pharmacys','pharmacys.id','=','orders.pharmacy_id')->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $count_orders_todays=$count_orders_todays->get();
            $count_orders_today = array();
            foreach($count_orders_todays as $item) {
                $count_orders_today[$item->statuse_id] = $item->count;
            }
            $count_orders_today[4] = DB::table('orders')->select(DB::raw('count(orders.id) as count0'))->whereDate('orders.finish', Carbon::today())->where('statuse_id', '4');
            if(!empty(Auth::user()->zone_id)){
                $count_orders_today[4]=$count_orders_today[4]->join('pharmacys','pharmacys.id','=','orders.pharmacy_id')->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $count_orders_today[4]=$count_orders_today[4]->first()->count0;
            $count_orders_today[201] = DB::table('orders')->select(DB::raw('count(orders.id) as count0'))->whereDate('orders.created', Carbon::today())->where('delivery_time_id','2');
            if(!empty(Auth::user()->zone_id)){
                $count_orders_today[201]=$count_orders_today[201]->join('pharmacys','pharmacys.id','=','orders.pharmacy_id')->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $count_orders_today[201]=$count_orders_today[201]->first()->count0;
            $count_orders_today[202] = DB::table('orders')->select(DB::raw('count(orders.id) as count0'))->whereDate('orders.created', Carbon::today())->whereIn('delivery_time_id',['3','4']);
            if(!empty(Auth::user()->zone_id)){
                $count_orders_today[202]=$count_orders_today[202]->join('pharmacys','pharmacys.id','=','orders.pharmacy_id')->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $count_orders_today[202]=$count_orders_today[202]->first()->count0;
            $count_orders_alls = DB::table('orders')->select("orders.statuse_id",DB::raw('count(orders.id) as count'))->groupBy("orders.statuse_id");
            if(!empty(Auth::user()->zone_id)){
                $count_orders_alls=$count_orders_alls->join('pharmacys','pharmacys.id','=','orders.pharmacy_id')->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $count_orders_alls=$count_orders_alls->get();
            $count_orders_all = array();
            foreach($count_orders_alls as $item) {
                $count_orders_all[$item->statuse_id] = $item->count;
            }
            $patients = DB::table('users')->where('users.isactive', '1')->orderBy('users.id','desc')->limit(6);
            if(!empty(Auth::user()->zone_id)){
                $patients=$patients->join('pharmacys','pharmacys.id','=','users.pharmacy_id')->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $patients=$patients->get();
            $pharmacys = DB::table('pharmacys')->where('isactive', '1')->orderBy('id','desc')->limit(4);
            if(!empty(Auth::user()->zone_id)){
                $pharmacys=$pharmacys->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $pharmacys=$pharmacys->get();
            $chartDelivered = DB::table('orders')->where('statuse_id',4)->select(DB::raw("count(orders.id) as count"), DB::raw("DATE_FORMAT(orders.created, '%m-%Y') date"))->groupby(DB::raw("DATE_FORMAT(orders.created, '%m-%Y')"))->orderBy('orders.id','desc')->limit(12);
            if(!empty(Auth::user()->zone_id)){
                $chartDelivered=$chartDelivered->join('pharmacys','pharmacys.id','=','orders.pharmacy_id')->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $chartDelivered=$chartDelivered->get()->reverse();
            $orders = DB::table('orders')->join('statuses', 'orders.statuse_id', '=', 'statuses.id')->join('users', 'orders.user_id', '=', 'users.id')->select('orders.id','orders.statuse_id','orders.created','users.image','users.name','users.last_name','statuses.name as statusename','statuses.color as statusecolor')->where('orders.statuse_id', 4)->orderBy('orders.id','desc')->limit(6);
            if(!empty(Auth::user()->zone_id)){
                $orders=$orders->join('pharmacys','pharmacys.id','=','orders.pharmacy_id')->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $orders=$orders->get();
            $orders0 = DB::table('orders')->join('statuses', 'orders.statuse_id', '=', 'statuses.id')->join('users', 'orders.user_id', '=', 'users.id')->select('orders.id','orders.statuse_id','orders.created','users.image','users.name','users.last_name','statuses.name as statusename','statuses.color as statusecolor')->where('orders.statuse_id', 1)->orderBy('orders.id','desc')->limit(6);
            if(!empty(Auth::user()->zone_id)){
                $orders0=$orders0->join('pharmacys','pharmacys.id','=','orders.pharmacy_id')->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $orders0=$orders0->get();
            $app_android_users = DB::table("users")->where("role","user")->where("os","1");
            if(!empty(Auth::user()->zone_id)){
                $app_android_users=$app_android_users->join('pharmacys','pharmacys.id','=','users.pharmacy_id')->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $app_android_users=$app_android_users->count();
            $app_android_drivers = DB::table("users")->where("role","driver")->where("os","1");
            if(!empty(Auth::user()->zone_id)){
                $app_android_drivers=$app_android_drivers->where(function ($query) {
                    $query->where('users.zone_id',Auth::user()->zone_id)
                        ->orWhereNull('users.zone_id');
                });
            }
            $app_android_drivers=$app_android_drivers->count();
            $app_ios_users = DB::table("users")->where("role","user")->where("os","2");
            if(!empty(Auth::user()->zone_id)){
                $app_ios_users=$app_ios_users->join('pharmacys','pharmacys.id','=','users.pharmacy_id')->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $app_ios_users=$app_ios_users->count();
            $app_ios_drivers = DB::table("users")->where("role","driver")->where("os","2");
            if(!empty(Auth::user()->zone_id)){
                $app_ios_drivers=$app_ios_drivers->where(function ($query) {
                    $query->where('users.zone_id',Auth::user()->zone_id)
                        ->orWhereNull('users.zone_id');
                });
            }
            $app_ios_drivers=$app_ios_drivers->count();
            $orders_without_sign = DB::table('orders')->where('statuse_id','4')->where('signature','1')->whereNull('signature_photo')->whereDate('orders.created', '>', date('Y-m-d', strtotime('now -1 month')));
            if(!empty(Auth::user()->zone_id)){
                $orders_without_sign=$orders_without_sign->join('pharmacys','pharmacys.id','=','orders.pharmacy_id')->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $orders_without_sign=$orders_without_sign->count();
            $orders_without_photo = DB::table('orders')->where('statuse_id','4')->whereNull('drop_off_photo')->whereDate('orders.created', '>', date('Y-m-d', strtotime('now -1 month')));
            if(!empty(Auth::user()->zone_id)){
                $orders_without_photo=$orders_without_photo->join('pharmacys','pharmacys.id','=','orders.pharmacy_id')->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $orders_without_photo=$orders_without_photo->count();
            $orders_copay_process = DB::table('orders')->where('statuse_id','4')->where('statuse_copay','2')->where('copay','>','0')->whereDate('orders.created', '>', date('Y-m-d', strtotime('now -1 month')));
            if(!empty(Auth::user()->zone_id)){
                $orders_copay_process=$orders_copay_process->join('pharmacys','pharmacys.id','=','orders.pharmacy_id')->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $orders_copay_process=$orders_copay_process->count();
            $orders_without_driver = DB::table('orders')->where('statuse_id','4')->whereNull('driver_id')->whereDate('orders.created', '>', date('Y-m-d', strtotime('now -1 month')));
            if(!empty(Auth::user()->zone_id)){
                $orders_without_driver=$orders_without_driver->join('pharmacys','pharmacys.id','=','orders.pharmacy_id')->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $orders_without_driver=$orders_without_driver->count();
            $users_without_app = DB::table('users')->where('role','user')->whereNull('os')->whereDate('created_at', '>', date('Y-m-d', strtotime('now -7 day')));
            if(!empty(Auth::user()->zone_id)){
                $users_without_app=$users_without_app->join('pharmacys','pharmacys.id','=','users.pharmacy_id')->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $users_without_app=$users_without_app->count();
            $pharmacys_without_orders = DB::table('pharmacys')->where('isactive','1')->where('isblocked','0')->leftJoin('orders', function($query) {
                $query->on('pharmacys.id','=','orders.pharmacy_id')
                ->whereDate('orders.created', '>', date('Y-m-d', strtotime('now -7 day')));
            })->whereNull('orders.id');
            if(!empty(Auth::user()->zone_id)){
                $pharmacys_without_orders=$pharmacys_without_orders->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $pharmacys_without_orders=$pharmacys_without_orders->count();
            $pharmacys_balance = DB::table('pharmacys')->where('isactive', '1')->where('isblocked', '0')->where('balance','<',0);
            if(!empty(Auth::user()->zone_id)){
                $pharmacys_balance=$pharmacys_balance->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $pharmacys_balance=$pharmacys_balance->sum("balance");
            $pharmacys_blocked = DB::table('pharmacys')->where('isactive', '1')->where('isblocked', '0')->where('balance_ban','1');
            if(!empty(Auth::user()->zone_id)){
                $pharmacys_blocked=$pharmacys_blocked->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $pharmacys_blocked=$pharmacys_blocked->count();
            $orders_without_notes = DB::table('orders')->whereIn('orders.statuse_id', ['8','9','10'])->leftJoin('notes','notes.order_id','=','orders.id')->whereNull('notes.id')->whereDate('orders.created', '>', date('Y-m-d', strtotime('now -1 month')));
            if(!empty(Auth::user()->zone_id)){
                $orders_without_notes=$orders_without_notes->join('pharmacys','pharmacys.id','=','orders.pharmacy_id')->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $orders_without_notes=$orders_without_notes->count();
            $income_today = DB::table('pharmacy_payments')->whereDate("pharmacy_payments.created",date("Y-m-d"));
            if(!empty(Auth::user()->zone_id)){
                $income_today=$income_today->join('pharmacys','pharmacys.id','=','pharmacy_payments.pharmacy_id')->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $income_today=$income_today->sum("pharmacy_payments.amount");
            $income_week = DB::table('pharmacy_payments')->whereBetween('pharmacy_payments.created', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
            if(!empty(Auth::user()->zone_id)){
                $income_week=$income_week->join('pharmacys','pharmacys.id','=','pharmacy_payments.pharmacy_id')->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $income_week=$income_week->sum("pharmacy_payments.amount");
            $income_month = DB::table('pharmacy_payments')->whereMonth('pharmacy_payments.created', '=', date('m', strtotime('now')));
            if(!empty(Auth::user()->zone_id)){
                $income_month=$income_month->join('pharmacys','pharmacys.id','=','pharmacy_payments.pharmacy_id')->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $income_month=$income_month->sum("pharmacy_payments.amount");
            $income_today_prev = DB::table('pharmacy_payments')->whereDate("pharmacy_payments.created",date("Y-m-d", strtotime('now -1 day')));
            if(!empty(Auth::user()->zone_id)){
                $income_today_prev=$income_today_prev->join('pharmacys','pharmacys.id','=','pharmacy_payments.pharmacy_id')->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $income_today_prev=$income_today_prev->sum("pharmacy_payments.amount");
            $income_week_prev = DB::table('pharmacy_payments')->whereBetween('pharmacy_payments.created', [Carbon::now()->subDays(7)->startOfWeek(), Carbon::now()->subDays(7)->endOfWeek()]);
            if(!empty(Auth::user()->zone_id)){
                $income_week_prev=$income_week_prev->join('pharmacys','pharmacys.id','=','pharmacy_payments.pharmacy_id')->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $income_week_prev=$income_week_prev->sum("pharmacy_payments.amount");
            $income_month_prev = DB::table('pharmacy_payments')->whereMonth('pharmacy_payments.created', '=', date('m', strtotime('now -1 month')));
            if(!empty(Auth::user()->zone_id)){
                $income_month_prev=$income_month_prev->join('pharmacys','pharmacys.id','=','pharmacy_payments.pharmacy_id')->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $income_month_prev=$income_month_prev->sum("pharmacy_payments.amount");
            $orders_7days = DB::table('orders')->select(DB::raw('DATE(orders.finish) as date'),DB::raw('count(orders.id) as count'))->whereBetween('finish', [Carbon::now()->subDays(7),Carbon::now()])->where('statuse_id', '4')->groupBy(DB::raw('DATE(orders.finish)'))->orderBy(DB::raw('DATE(orders.finish)'),'asc');
            if(!empty(Auth::user()->zone_id)){
                $orders_7days=$orders_7days->join('pharmacys','pharmacys.id','=','orders.pharmacy_id')->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $orders_7days=$orders_7days->get();
            $orders_7days_c = DB::table('orders')->select(DB::raw('DATE(orders.created) as date'),DB::raw('count(orders.id) as count'))->whereBetween('orders.created', [Carbon::now()->subDays(7),Carbon::now()])->groupBy(DB::raw('DATE(orders.created)'))->orderBy(DB::raw('DATE(orders.created)'),'asc');
            if(!empty(Auth::user()->zone_id)){
                $orders_7days_c=$orders_7days_c->join('pharmacys','pharmacys.id','=','orders.pharmacy_id')->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $orders_7days_c=$orders_7days_c->get();
            $res_arr = [
                'patients'=>$patients,
                'pharmacys'=>$pharmacys,
                'orders'=>$orders,
                'orders0'=>$orders0,
                'count_orders'=>$count_orders,
                'total_count_orders'=>$total_count_orders,
                'count_orders_today'=>$count_orders_today,
                'count_orders_all'=>$count_orders_all,
                'total_count_orders_quikmedix'=>$total_count_orders_quikmedix,
                'count_pharmacies'=>$count_pharmacies,
                'count_pharmacies_active'=>$count_pharmacies_active,
                'count_drivers_all'=>$count_drivers_all,
                'count_drivers'=>$count_drivers,
                'count_drivers_pharmacy'=>$count_drivers_pharmacy,
                'count_patients'=>$count_patients,
                'chartDelivered'=>$chartDelivered,
                'app_android_users'=>$app_android_users,
                'app_android_drivers'=>$app_android_drivers,
                'app_ios_users'=>$app_ios_users,
                'app_ios_drivers'=>$app_ios_drivers,
                'orders_proc'=>$orders_proc,
                'pharmacies_proc'=>$pharmacies_proc,
                'drivers_proc'=>$drivers_proc,
                'patients_proc'=>$patients_proc,
                'orders_without_sign'=>$orders_without_sign,
                'orders_without_photo'=>$orders_without_photo,
                'orders_copay_process'=>$orders_copay_process,
                'orders_without_driver'=>$orders_without_driver,
                'users_without_app'=>$users_without_app,
                'pharmacys_without_orders'=>$pharmacys_without_orders,
                'pharmacys_balance'=>$pharmacys_balance,
                'pharmacys_blocked'=>$pharmacys_blocked,
                'orders_without_notes'=>$orders_without_notes,
                'income_today'=>$income_today,
                'income_week'=>$income_week,
                'income_month'=>$income_month,
                'income_today_prev'=>$income_today_prev,
                'income_week_prev'=>$income_week_prev,
                'income_month_prev'=>$income_month_prev,
                'orders_7days'=>$orders_7days,
                'orders_7days_c'=>$orders_7days_c,
                'title'=>'Dashboard','br1'=>'Dashboard'];
            Redis::setex(request()->getHttpHost().':admin_dash', 200, serialize($res_arr));
            return view('dashboard.index',$res_arr);
        }
    }

    public function searchJson() {
        if((Auth::user()->role == 'medic' && !empty(Auth::user()->pharmacy_id)) || ((Auth::user()->can('admin')))) {
            $search = request()->query('search', '');
            $max_res = 100;
            if(!empty($search)) {
                $orders_results = [];
                $rxs_results = [];
                $users_results = [];
                $orders = DB::table('orders')->where('orders.id','LIKE','%'.$search.'%');
                if(!empty(Auth::user()->pharmacy_id)) {
                    $orders=$orders->where('orders.pharmacy_id',Auth::user()->pharmacy_id);
                }
                $orders=$orders->leftJoin('users','users.id','=','orders.user_id')->select('orders.id','orders.created','orders.finish','orders.copay','orders.statuse_id','orders.pharmacy_id','orders.user_id',DB::raw("CONCAT(users.name, ' ', users.last_name) as user_name"))->get();
                $statuses = self::get_statuses();
                $pharmacys = self::get_pharmacys($orders->pluck('pharmacy_id')->toArray());
                foreach($orders as $order) {
                    $data = [];
                    $pharmacy_name = '';
                    if(isset($pharmacys[$order->pharmacy_id])){
                        $pharmacy_name = $pharmacys[$order->pharmacy_id]->name;
                    }
                    $data['id']=$order->id;
                    $data['created']=date('m/d/Y g:i A', strtotime($order->created ?? ''));
                    $data['finish']=date('m/d/Y g:i A', strtotime($order->finish ?? ''));
                    $data['copay']=number_format($order->copay ?? 0,2);
                    $data['pharmacy_id']=$order->pharmacy_id;
                    $data['user_id']=$order->user_id;
                    $data['status_name']=$statuses[$order->statuse_id]->name;
                    $data['status_color']=$statuses[$order->statuse_id]->color;
                    $data['pharmacy_name']=$pharmacy_name;
                    $data['user_name']=$order->user_name;
                    $data['link']="/orders/{$order->pharmacy_id}/show/{$order->id}";
                    $orders_results[]=$data;
                }
                if(count($orders_results)<$max_res) {
                    $rxs = DB::table('rxs')->join('orders','orders.id','=','rxs.order_id')->where('rx_id','LIKE','%'.$search.'%')->select('rxs.*','orders.pharmacy_id','orders.created','orders.finish','orders.copay');
                    if(!empty(Auth::user()->pharmacy_id)) {
                        $rxs=$rxs->where('orders.pharmacy_id',Auth::user()->pharmacy_id);
                    }
                    $rxs=$rxs->get();
                    foreach($rxs as $rx) {
                        $data = [];
                        $data['rx_id']=$rx->rx_id;
                        $data['order_id']=$rx->order_id;
                        $data['created']=date('m/d/Y g:i A', strtotime($rx->created ?? ''));
                        $data['finish']=date('m/d/Y g:i A', strtotime($rx->finish ?? ''));
                        $data['copay']=number_format($rx->copay ?? 0,2);
                        $data['link']="/orders/{$rx->pharmacy_id}/show/{$rx->order_id}";
                        $rxs_results[]=$data;
                    }
                }
                if(count($orders_results)+count($rxs_results)<$max_res) {
                    $users = DB::table('users')->where('users.role','user')->whereNotNull('users.pharmacy_id')->where(function($query) use ($search) {
                        $query->where(DB::raw("CONCAT(users.name, ' ', users.last_name)"),'LIKE','%'.$search.'%')
                            ->orWhere(DB::raw("CONCAT(users.last_name, ' ', users.name)"),'LIKE','%'.$search.'%')
                            ->orWhere('users.phone','LIKE','%'.$search.'%')
                            ->orWhere('users.address','LIKE','%'.$search.'%')
                            ->orWhere('users.zip','LIKE','%'.$search.'%')
                            ->orWhere('users.id','LIKE','%'.$search.'%');
                    })->leftJoin('orders','orders.user_id','=','users.id')->select('users.id','users.name','users.last_name','users.phone','users.address','users.zip','users.pharmacy_id',DB::raw('count(distinct orders.id) as count_orders'))->groupBy('users.id','users.name','users.last_name','users.phone','users.address','users.zip','users.pharmacy_id');
                    if(!empty(Auth::user()->pharmacy_id)) {
                        $users=$users->where('users.pharmacy_id',Auth::user()->pharmacy_id);
                    }
                    $users=$users->get();
                    foreach($users as $user) {
                        $data = [];
                        $pharmacy_name = '';
                        if(isset($pharmacys[$user->pharmacy_id])){
                            $pharmacy_name = $pharmacys[$user->pharmacy_id]->name;
                        }
                        $data['name']=$user->name;
                        $data['last_name']=$user->last_name;
                        $data['id']=$user->id;
                        $data['phone']=$user->phone;
                        $data['address']=$user->address;
                        $data['zip']=$user->zip;
                        $data['count_orders']=$user->count_orders;
                        $data['pharmacy_name']=$pharmacy_name;
                        $data['pharmacy_id']=$user->pharmacy_id;
                        $data['link']="/patients/{$user->pharmacy_id}/edit/{$user->id}";
                        $users_results[]=$data;
                    }
                }
                $results['orders']=$orders_results;
                $results['rxs']=$rxs_results;
                $results['users']=$users_results;
                $results = array_slice($results,0,$max_res);
                return json_encode([
                    'results'=>$results,
                    'message' => 'OK'
                ]);
            } else {
                return json_encode([
                    'results'=>[],
                    'message' => 'OK'
                ]);
            }
        } else {
            return abort(403, AccessMessages::NO_PERMISSION);
        }
    }

    public function notifications() {
        $countOnPage=30;
        $max_pages=ceil(DB::table('notifications')->where('user_id', Auth::user()->id)->count()/$countOnPage);
        $page = $this->currentPage(request());
        $pages = PageLinks::around($page, $max_pages);
        $notifications = DB::table('notifications')->where('user_id', Auth::user()->id)->orderBy('id','desc')->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
        DB::table('notifications')->whereIn('id',DB::table('notifications')->where('user_id', Auth::user()->id)->orderBy('id','desc')->pluck('id')->toArray())->update(['viewed' => 1]);
        Redis::del(request()->getHttpHost().':notification_count:'.Auth::user()->id);
        $res_view = view('notifications.list',['notifications'=>$notifications,'pages'=>$pages,'title'=>'Notifications','br1'=>'Notifications','br2'=>'List']);
        return $this->page(request(), $res_view);
    }

    public function deliveryCalendar() {
        if(request()->query->has('get_zones') && !empty(request()->query('get_zones')) && request()->query->has('state') && !empty(request()->query('state'))){
            $zones = DB::table('area')->where('state',request()->query('state'))->select('id','name')->get();
            return json_encode($zones);
        }
        if(request()->query->has('month') && !empty(request()->query('month'))){
            $month = date("Y-m",strtotime(request()->query('month'))).'-01';
        } else {
            $month = date("Y-m").'-01';
        }
        $dates = [];
        $stats = [];
        $week = 1;
        $date = new \DateTime($month);
        $days = (int)$date->format('t'); // total number of days in the month
        $oneDay = new \DateInterval('P1D');
        for ($day = 1; $day <= $days; $day++) {
            $dayOfWeek0 = $date->format('w');
            $dates["Week $week"][$dayOfWeek0]= $date->format('Y-m-d');
            if(Auth::user()->role=='medic'){
                if(request()->query->has('state')) {
                    $state = request()->query('state');
                    $pol_id=0;
                    if(request()->query->has('zone')) {
                        $pol_id = request()->query('zone');
                    }
                    $orders = DB::table('users')->join('orders','orders.user_id','=','users.id')->where('orders.pharmacy_id',Auth::user()->pharmacy_id)->whereDate('delivery_date', $date->format('Y-m-d'))->whereIn('statuse_id',[1,7,4,3,8,9])->join('statuses','statuses.id','=','orders.statuse_id')->select('statuses.id','statuses.name',DB::raw('count(distinct orders.id) as count'))->groupBy('statuses.id','statuses.name')->orderBy('statuses.id','asc')->join('area', function($join) use($state,$pol_id) {
                        $join->on('area.polygon','!=','orders.id');
                        if(!empty($state)) {
                            $join->where('area.state',$state);
                        }
                        if(!empty($pol_id)) {
                            $join->where('area.id',$pol_id);
                        }
                    })->whereRaw("ST_CONTAINS(area.polygon, POINT(SUBSTRING_INDEX(users.location,',',1),SUBSTRING_INDEX(users.location,',',-1)))");
                } else {
                    $orders = DB::table('orders')->where('pharmacy_id',Auth::user()->pharmacy_id)->whereDate('delivery_date', $date->format('Y-m-d'))->whereIn('statuse_id',[1,7,4,3,8,9])->join('statuses','statuses.id','=','orders.statuse_id')->select('statuses.id','statuses.name',DB::raw('count(distinct orders.id) as count'))->groupBy('statuses.id','statuses.name')->orderBy('statuses.id','asc');
                }
                $stats[$date->format('Y-m-d')]= $orders->get();
            } else {
                if(request()->query->has('state')) {
                    $state = request()->query('state');
                    $pol_id=0;
                    if(request()->query->has('zone')) {
                        $pol_id = request()->query('zone');
                    }
                    $orders = DB::table('users')->join('orders','orders.user_id','=','users.id')->whereDate('delivery_date', $date->format('Y-m-d'))->whereIn('statuse_id',[1,7,4,3,8,9])->join('statuses','statuses.id','=','orders.statuse_id')->select('statuses.id','statuses.name',DB::raw('count(distinct orders.id) as count'))->groupBy('statuses.id','statuses.name')->orderBy('statuses.id','asc')->join('area', function($join) use($state,$pol_id) {
                        $join->on('area.polygon','!=','orders.id');
                        if(!empty($state)) {
                            $join->where('area.state',$state);
                        }
                        if(!empty($pol_id)) {
                            $join->where('area.id',$pol_id);
                        }
                    })->whereRaw("ST_CONTAINS(area.polygon, POINT(SUBSTRING_INDEX(users.location,',',1),SUBSTRING_INDEX(users.location,',',-1)))");
                } else {
                    $orders = DB::table('orders')->whereDate('delivery_date', $date->format('Y-m-d'))->whereIn('statuse_id',[1,7,4,3,8,9])->join('statuses','statuses.id','=','orders.statuse_id')->select('statuses.id','statuses.name',DB::raw('count(distinct orders.id) as count'))->groupBy('statuses.id','statuses.name')->orderBy('statuses.id','asc');
                }
                if(!empty(Auth::user()->zone_id)){
                    $orders=$orders->join('pharmacys','pharmacys.id','=','orders.pharmacy_id')->where('pharmacys.zone_id',Auth::user()->zone_id);
                }
                $stats[$date->format('Y-m-d')]= $orders->get();
            }
            $dayOfWeek = $date->format('l');
            if ($dayOfWeek === 'Saturday') {
                $week++;
            }
            $date->add($oneDay);
        }
        if(Auth::user()->role=='medic'){
            $pharmacy_id = Auth::user()->pharmacy_id;
        } else {
            $pharmacy_id = '';
        }
        $states = DB::table('states')->get();
        $zones = DB::table('area')->select('id','name','state')->get();
        return view('routes.delivery-calendar',['month'=>$month,'weeks'=>$dates,'stats'=>$stats,'states'=>$states,'zones'=>$zones,'pharmacy_id'=>$pharmacy_id,'alert'=>'','title'=>'Delivery Calendar','br1'=>'Routes','br2'=>'Delivery Calendar']);
    }

    public function ready_call() {
        $user = Auth::user();
        if($user->call_ready=='1') {
            $user->call_ready='0';
        } else {
            $user->call_ready='1';
        }
        $user->save();
        return redirect()->back();
    }
}
