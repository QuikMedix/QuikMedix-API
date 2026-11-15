<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\LoadsOrderLookups;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Twilio\Rest\Client;
use App\User;
use App\Notifications;
use App\Support\PageLinks;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;
use Carbon\Carbon;
use Zadarma_API\Api as Zadarma_API;
use URL;
use Smalot\PdfParser\Parser;
use App\Actions\GeocodeAddress;
use App\Actions\LogUserChanges;
use App\Actions\NotifyNextPatient;
use App\Actions\RecalculateDriverEta;
use App\Actions\RotateSignature;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2LoginHelper;

class LexaAdmin extends Controller
{
    use LoadsOrderLookups;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('2fa');
        ini_set('memory_limit', '-1');
    }

    public static $err_act_ban = "We're sorry for the inconvenience, but it seems we have a little hiccup here. Our team is already on the case, working hard to get things back on track as quickly as possible.";
    public static $err_perm = 'Permision error.';

    /**
     * Pages are also loaded into the dashboard over AJAX, which wants only the sections.
     *
     * @return View|array<string, string>
     */
    private static function renderPage(View $view): View|array {
        return request()->query->has('ajax') ? $view->renderSections() : $view;
    }

    /**
     * The page number from the query string; a missing, empty or zero value is the first page.
     */
    private static function currentPage(): int {
        return empty(request()->query('page')) ? 1 : intval(request()->query('page'));
    }

    public function home() {
        return redirect()->route('home');
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

    public static function root() {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
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

    /**
     * @param int|string $pharmacy_id
     */
    public static function pharmacyUsers($pharmacy_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if(Auth::user()->can('manage-pharmacy', $pharmacy_id)) {
            $users = User::where('role','medic')->where('pharmacy_id',$pharmacy_id);
            if(!empty(request()->query('search'))) {
                $search = request()->query('search');
                $users = $users->where(function($query) use ($search) {
                    $query->where(DB::raw("CONCAT(name, ' ', last_name)"),'LIKE','%'.$search.'%')
                          ->orWhere('email','LIKE','%'.$search.'%')
                          ->orWhere('phone','LIKE','%'.$search.'%');
                    });
            } else {
                $search='';
            }
            $countOnPage=30;
            $max_pages=ceil($users->count()/$countOnPage);
            $page = self::currentPage();
            $pages = PageLinks::around($page, $max_pages);
            $users = $users->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
            $res_view = view('pharmacy.users',['pages'=>$pages,'page0'=>$page,'search'=>$search,'users'=>$users,'pharmacy_id'=>$pharmacy_id,'title'=>'Pharmacy Users','br1'=>'Pharmacy','br2'=>'Users']);
            return self::renderPage($res_view);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    /**
     * @param int|string $pharmacy_id
     */
    public static function pharmacyUsersHandler(Request $request, $pharmacy_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if(Auth::user()->can('manage-pharmacy', $pharmacy_id)) {
            if($request->filled('user_id')) {
                Gate::authorize('manage-account', [$request->input('user_id'), $pharmacy_id]);
            }
            if($request->input('activate')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['isactive' => 1]);
            }
            if($request->input('block')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['isblocked' => 1]);
            }
            if($request->input('unblock')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['isblocked' => 0]);
            }
            if($request->input('remove')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['pharmacy_id' => NULL]);
            }
            if($request->input('todriver')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['role' => 'driver']);
            }
            if($request->input('touser')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['role' => 'user']);
            }
            if($request->input('tomedic')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['role' => 'medic']);
            }
            $users = User::where('role','medic')->where('pharmacy_id',$pharmacy_id);
            if(!empty(request()->query('search'))) {
                $search = request()->query('search');
                $users = $users->where(function($query) use ($search) {
                    $query->where(DB::raw("CONCAT(name, ' ', last_name)"),'LIKE','%'.$search.'%')
                          ->orWhere('email','LIKE','%'.$search.'%')
                          ->orWhere('phone','LIKE','%'.$search.'%');
                    });
            } else {
                $search='';
            }
            $countOnPage=30;
            $max_pages=ceil($users->count()/$countOnPage);
            $page = self::currentPage();
            $pages = PageLinks::around($page, $max_pages);
            $users = $users->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
            return view('pharmacy.users',['pages'=>$pages,'page0'=>$page,'search'=>$search,'users'=>$users,'pharmacy_id'=>$pharmacy_id,'title'=>'Pharmacy Users','br1'=>'Pharmacy','br2'=>'Users']);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    /**
     * @param int|string $pharmacy_id
     * @param int|string $user_id
     */
    public static function pharmacyUsersEdit($pharmacy_id,$user_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if(Auth::user()->role == 'medic' && Auth::user()->pharmacy_id==$pharmacy_id && DB::table('users')->where('id', $user_id)->where('pharmacy_id', $pharmacy_id)->exists()  || (Auth::user()->can('admin'))) {
            $user = DB::table('users')->where('id', $user_id)->first();
            $user_actions = DB::table('action_log')->where('user_id', $user_id)->get();
            $pharmacys = DB::table('pharmacys')->get();
            $res_view = view('pharmacy.user_edit',['user'=> $user, 'user_actions'=>$user_actions, 'pharmacys'=>$pharmacys, 'title'=>'User Edit','br1'=>'Pharmacy','br2'=>'Users','br3'=>'User Edit','alert'=>'']);
            return self::renderPage($res_view);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    /**
     * @param int|string $pharmacy_id
     * @param int|string $user_id
     */
    public static function pharmacyUsersEditHandler(Request $request,$pharmacy_id,$user_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if(Auth::user()->can('manage-pharmacy', $pharmacy_id)) {
            Gate::authorize('manage-account', [$user_id, $pharmacy_id]);
            if($request->input('save')>0) {
                if($request->hasFile('image')) {
                    $file = $request->file('image');
                    $file->move(public_path() . '/images/users/',\App\Support\PublicUpload::name($request->file('image')));
                    $src = '/images/users/'.\App\Support\PublicUpload::name($request->file('image'));
                } else {
                    $user = DB::table('users')->where('id', $user_id)->first();
                    $src = $user->image;
                }
                if($request->input('remove_photo')>0) {
                    $src = '/images/users/default-user-image.png';
                }
                if(!empty($request->input('zip'))) {
                    $address = $request->input('address').' '.$request->input('zip');
                } else {
                    $address = $request->input('address');
                }
                $data = app(\App\Actions\GeocodeAddress::class)->response($address);
                $address = $data->results[0]->formatted_address;
                $location = $data->results[0]->geometry->location->lat.','.$data->results[0]->geometry->location->lng;
                app(LogUserChanges::class)->handle($request,$address,$user_id);
                DB::table('users')->where('id', $user_id)->update(['name' => $request->input('name'),'last_name' => $request->input('last_name'),'email' => $request->input('email'),'phone' => $request->input('phone'),'image' => $src,'address' => $address,'location' => $location,'zip' => $request->input('zip'),'apartment' => $request->input('apartment')]);
            }
            return redirect("pharmacy/$pharmacy_id/users");
        } else {
            return abort(403, self::$err_perm);
        }
    }

    /**
     * @param int|string $pharmacy_id
     */
    public static function pharmacyUsersAdd($pharmacy_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if(Auth::user()->can('manage-pharmacy', $pharmacy_id)) {
            $input['name']='';
            $input['last_name']='';
            $input['email']='';
            $input['phone']='';
            $input['image']='';
            $input['address']='';
            $input['location']='';
            $input['password']='';
            $input['zip']='';
            $input['apartment']='';
            $input['pharmacy']=$pharmacy_id;
            $pharmacys = DB::table('pharmacys')->get();
            $res_view = view('pharmacy.user_add',['pharmacys'=>$pharmacys,'title'=>'User Add','br1'=>'Pharmacy','br2'=>'Users','br3'=>'User Add','alert'=>'','input'=>$input]);
            return self::renderPage($res_view);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    /**
     * @param int|string $pharmacy_id
     */
    public static function pharmacyUsersAddHandler(Request $request,$pharmacy_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if(Auth::user()->can('manage-pharmacy', $pharmacy_id)) {
            if($request->input('save')>0) {
                if(!empty(DB::table('users')->where('email', $request->input('email'))->first())) {
                    $input['name']=$request->input('name');
                    $input['last_name']=$request->input('name');
                    $input['email']=$request->input('email');
                    $input['phone']=$request->input('phone');
                    $input['image']='';
                    $input['address']=$request->input('address');
                    $input['password']=$request->input('password');
                    $input['zip']=$request->input('zip');
                    $input['apartment']=$request->input('apartment');
                    $input['pharmacy']=$pharmacy_id;
                    $pharmacys = DB::table('pharmacys')->get();
                    return view('pharmacy.user_add',['pharmacys'=>$pharmacys,'title'=>'User Add','br1'=>'Pharmacy','br2'=>'Users','br3'=>'User Add','alert'=>'User with this email already exists','input'=>$input]);
                } else {
                    if(!empty(DB::table('users')->where('phone', $request->input('phone'))->first())) {
                        $input['name']=$request->input('name');
                        $input['last_name']=$request->input('name');
                        $input['email']=$request->input('email');
                        $input['phone']=$request->input('phone');
                        $input['image']='';
                        $input['address']=$request->input('address');
                        $input['password']=$request->input('password');
                        $input['zip']=$request->input('zip');
                        $input['apartment']=$request->input('apartment');
                        $input['pharmacy']=$pharmacy_id;
                        $pharmacys = DB::table('pharmacys')->get();
                        return view('pharmacy.user_add',['pharmacys'=>$pharmacys,'title'=>'User Add','br1'=>'Pharmacy','br2'=>'Users','br3'=>'User Add','alert'=>'User with this phone already exists','input'=>$input]);
                    } else {
                        if($request->hasFile('image')) {
                            $file = $request->file('image');
                            $file->move(public_path() . '/images/users/',\App\Support\PublicUpload::name($request->file('image')));
                            $src = '/images/users/'.\App\Support\PublicUpload::name($request->file('image'));
                        } else {
                            $src = '';
                        }
                        $data = app(\App\Actions\GeocodeAddress::class)->response($request->input('address'));
                        $location = $data->results[0]->geometry->location->lat.','.$data->results[0]->geometry->location->lng;
                        DB::table('users')->insert(['isactive' => '1', 'role' => 'medic','name' => $request->input('name'),'last_name' => $request->input('last_name'),'email' => $request->input('email'),'phone' => $request->input('phone'),'image' => $src,'address' => $request->input('address'),'location' => $location,'password' => Hash::make($request->input('password')),'zip' => $request->input('zip'),'apartment' => $request->input('apartment'),'pharmacy_id' => $pharmacy_id]);
                    }
                }
            }
            return redirect("pharmacy/$pharmacy_id/users");
        } else {
            return abort(403, self::$err_perm);
        }
    }

    /**
     * @param int|string $pharmacy_id
     */
    public function pharmacysTariffMap($pharmacy_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->can('admin'))) {
            $pharmacy = DB::table('pharmacys')->where('id', $pharmacy_id)->first();
            $pharmacy_areas = DB::table('pharmacys')->where('id', $pharmacy_id)->get();
            $pharmacy_plan = DB::table('plans')->where('id', $pharmacy->plan_id)->first();
            $polygons = DB::table('area')->join('pharmacy_areas','pharmacy_areas.area_id',"=","area.id")->where('pharmacy_areas.pharmacy_id',$pharmacy_id)->select('area.name',DB::raw('ST_AsText(area.polygon) as polygon'),'pharmacy_areas.type')->get();
            foreach($polygons as $key=>$pol) {
                if(!empty($pol->polygon)) {
                    $polygons[$key]->polygon = \App\Support\WktPolygon::toJsonPoints($pol->polygon);
                } else {
                    $polygons[$key]->polygon = "";
                }   
            }
            $res_view = view('pharmacy.tariff_map',['pharmacy'=>$pharmacy,'polygons'=>$polygons,'pharmacy_plan'=>$pharmacy_plan,'pharmacy_areas'=>$pharmacy_areas,'alert'=>'','title'=>'Tariff Map','br1'=>'Tariff','br2'=>'Tariff Map']);
            return self::renderPage($res_view);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public static function pharmacysList() {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->can('admin'))) {
            $filter = [];
            if(!empty(request()->query('start')) && !empty(request()->query('filter'))) {
                $filter["start"] = request()->query('start');
            } else {
                $filter["start"] = "";
            }
            if(!empty(request()->query('end')) && !empty(request()->query('filter'))) {
                $filter["end"] = request()->query('end');
            } else {
                if(!empty($filter["start"])) {
                    $filter["end"] = date('m/d/Y');
                } else {
                    $filter["end"] = "";
                }
            }
            if(!empty(request()->query('advanced')) && !empty(request()->query('filter'))) {
                $filter["advanced"] = request()->query('advanced');
            } else {
                $filter["advanced"] = "";
            }
            $pharmacys = DB::table('pharmacys')->leftJoin('orders', function($query) {
                $query->on('pharmacys.id','=','orders.pharmacy_id')
                ->whereDate('orders.created', '>', date('Y-m-d', strtotime('now -7 day')));
             })->leftJoin('users','pharmacys.admin_id','=','users.id')->select("pharmacys.id","pharmacys.isactive","pharmacys.isblocked","pharmacys.email","pharmacys.phone","pharmacys.name","pharmacys.tariff","pharmacys.address","pharmacys.location","pharmacys.logo","pharmacys.image_front","pharmacys.admin_id",DB::raw("users.isactive as isactiveuser"),"pharmacys.ref_id","pharmacys.balance");
            if(!empty(request()->query('search'))) {
                $search = request()->query('search');
                $pharmacys = $pharmacys->where(function($query) use ($search) {
                    $query->where('pharmacys.name','LIKE','%'.$search.'%')
                          ->orWhere('pharmacys.email','LIKE','%'.$search.'%')
                          ->orWhere('pharmacys.phone','LIKE','%'.$search.'%')
                          ->orWhere('pharmacys.address','LIKE','%'.$search.'%');
                    });
            } else {
                $search='';
            }
            if(!empty($filter["start"]) && !empty($filter["end"])) {
                $pharmacys = $pharmacys->whereBetween('pharmacys.created',[\DateTime::createFromFormat('m/d/Y',$filter["start"])->format('Y-m-d'),\DateTime::createFromFormat('m/d/Y',$filter["end"])->format('Y-m-d')]);
            }
            if(!empty(request()->query('without_orders')) || $filter["advanced"]=='without_orders') {
                $pharmacys = $pharmacys->whereNull('orders.id');
            }
            if(!empty(request()->query('pharmacys_balance')) || $filter["advanced"]=='pharmacys_balance') {
                $pharmacys = $pharmacys->where('pharmacys.balance','<',0);
            }
            if(!empty(request()->query('pharmacys_blocked')) || $filter["advanced"]=='pharmacys_blocked') {
                $pharmacys = $pharmacys->where('pharmacys.balance_ban','1');
            }
            if($filter["advanced"]=='pharmacys_blocked_permanently') {
                $pharmacys = $pharmacys->where('pharmacys.isblocked','1');
            }
            if(!empty(Auth::user()->zone_id)){
                $pharmacys=$pharmacys->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $pharmacys = $pharmacys->groupBy("pharmacys.id","pharmacys.isactive","pharmacys.isblocked","pharmacys.email","pharmacys.phone","pharmacys.name","pharmacys.tariff","pharmacys.address","pharmacys.location","pharmacys.logo","pharmacys.image_front","pharmacys.admin_id","users.isactive","pharmacys.ref_id","pharmacys.balance")->orderBy(DB::raw("count(DISTINCT orders.id)"),"desc");
            $countOnPage=30;
            $max_pages=ceil($pharmacys->count()/$countOnPage);
            $page = self::currentPage();
            $pages = PageLinks::around($page, $max_pages);
            $pharmacys = $pharmacys->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
            $res_arr = ['pages'=>$pages,'page0'=>$page,'search'=>$search,'pharmacys'=>$pharmacys,'filter'=>$filter,'title'=>'Pharmacies List','br1'=>'Pharmacy','br2'=>'List'];
            return view('pharmacy.list',$res_arr);
        } else if(Auth::user()->role == 'sale') {
            $pharmacys = DB::table('pharmacys')->where("ref_id",Auth::user()->id);
            if(!empty(request()->query('search'))) {
                $search = request()->query('search');
                $pharmacys = $pharmacys->where(function($query) use ($search) {
                    $query->where('name','LIKE','%'.$search.'%')
                          ->orWhere('email','LIKE','%'.$search.'%')
                          ->orWhere('phone','LIKE','%'.$search.'%')
                          ->orWhere('address','LIKE','%'.$search.'%');
                    });
            } else {
                $search='';
            }
            $countOnPage=30;
            $max_pages=ceil($pharmacys->count()/$countOnPage);
            $page = self::currentPage();
            $pages = PageLinks::around($page, $max_pages);
            $pharmacys = $pharmacys->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
            $res_view = view('pharmacy.list_sale',['pages'=>$pages,'page0'=>$page,'search'=>$search,'pharmacys'=>$pharmacys,'title'=>'Pharmacies List','br1'=>'Pharmacy','br2'=>'List']);
            return self::renderPage($res_view);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public static function pharmacysListHandler(Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->can('admin'))) {
            if($request->input('block')>0) {
                DB::table('pharmacys')->where('id', $request->input('user_id'))->update(['isblocked' => 1]);
            }
            if($request->input('unblock')>0) {
                DB::table('pharmacys')->where('id', $request->input('user_id'))->update(['isblocked' => 0]);
            }
            if($request->input('activate')>0) {
                DB::table('pharmacys')->where('id', $request->input('user_id'))->update(['isactive' => 1]);
            }
            if($request->input('activateuser')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['isactive' => 1]);
            }
            if($request->input('remove')>0) {
                DB::table('pharmacys')->where('id', $request->input('user_id'))->delete();
            }
            return redirect('pharmacys');
        } else {
            return abort(403, self::$err_perm);
        }
    }

    /**
     * @param int|string $pharmacy_id
     */
    public static function pharmacysListEdit($pharmacy_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->can('admin'))) {
            $pharmacy = DB::table('pharmacys')->where('id', $pharmacy_id)->first();
            if(!empty($pharmacy->schedule)){
                $pharmacy->schedule=json_decode($pharmacy->schedule);
            }
            $pharmacy_areas1 = DB::table('pharmacy_areas')->where('pharmacy_id', $pharmacy_id)->where('type', 1)->pluck("area_id")->toArray();
            $pharmacy_areas2 = DB::table('pharmacy_areas')->where('pharmacy_id', $pharmacy_id)->where('type', 2)->pluck("area_id")->toArray();
            $pharmacy_areas3 = DB::table('pharmacy_areas')->where('pharmacy_id', $pharmacy_id)->where('type', 3)->pluck("area_id")->toArray();
            $plans = DB::table('plans')->orderBy('id','desc')->get();
            $pharmacy_plan = DB::table('plans')->where('id', $pharmacy->plan_id)->first();
            $areas = DB::table('area')->get();
            $admin= DB::table('users')->where('id', $pharmacy->admin_id)->first();
            $admin_areas = DB::table('admin_areas')->get();
            $res_view = view('pharmacy.pharmacy_edit',['pharmacy'=>$pharmacy,'admin_areas'=>$admin_areas,'pharmacy_areas1'=>$pharmacy_areas1,'pharmacy_areas2'=>$pharmacy_areas2,'pharmacy_areas3'=>$pharmacy_areas3,'plans'=>$plans,'pharmacy_plan'=>$pharmacy_plan,'areas'=>$areas,'admin'=>$admin, 'title'=>'Pharmacy Edit','br1'=>'Pharmacies','br2'=>'Pharmacy','br3'=>'Pharmacy Edit','alert'=>'']);
            return self::renderPage($res_view);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    /**
     * @param int|string $pharmacy_id
     */
    public static function pharmacysListEditHandler(Request $request,$pharmacy_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->can('admin'))) {
            if($request->input('save')>0) {
                $pharmacy = DB::table('pharmacys')->where('id', $pharmacy_id)->first();
                if($request->hasFile('image')) {
                    $file = $request->file('image');
                    $name_f = \App\Support\PublicUpload::name($request->file('image'));
                    $file->move(public_path() . '/images/pharmacys/',$name_f);
                    $src = '/images/pharmacys/'.$name_f;
                } else {
                    $src = $pharmacy->logo;
                }
                if($request->hasFile('image_front')) {
                    $file = $request->file('image_front');
                    $name_f = \App\Support\PublicUpload::name($request->file('image_front'));
                    $file->move(public_path() . '/images/pharmacys/',$name_f);
                    $src2 = '/images/pharmacys/'.$name_f;
                } else {
                    $src2 = $pharmacy->image_front;
                }
                if(!empty($request->input('massiveBagsTransfer')) && $request->input('massiveBagsTransfer')>0) {
                    $massiveBagsTransfer = "1";
                } else {
                    $massiveBagsTransfer = "0";
                }
                if(!empty($request->input('copay_bill')) && $request->input('copay_bill')>0) {
                    $copay_bill = "1";
                } else {
                    $copay_bill = "0";
                }
                $schedule=[];
                if(!empty($request->input('schedule_open')) && !empty($request->input('schedule_close'))) {
                    foreach($request->input('schedule_open') as $key=>$open) {
                        $close = $request->input('schedule_close')[$key];
                        if(!empty($open) && !empty($close)) {
                            $schedule[$key]=new \StdClass;
                            $schedule[$key]->open=$open;
                            $schedule[$key]->close=$close;
                        }
                    }
                }
                if($pharmacy->copay_bill!=$copay_bill) {
                    $invoices = DB::table('invoices')->where('pharmacy_id',$pharmacy_id)->where("payed","0")->get();
                    $sum_amount = 0;
                    foreach($invoices as $invoice) {
                        if($copay_bill=="1"){
                            if((($invoice->amount+$invoice->corrections)-$invoice->copay)<0){
                                $sum_amount = $sum_amount+0;
                            } else {
                                $sum_amount = $sum_amount+(($invoice->amount+$invoice->corrections)-$invoice->copay);
                            }
                        } else {
                            $sum_amount = $sum_amount+($invoice->amount+$invoice->corrections);
                        }
                    }
                    $balance = floatval($sum_amount)*-1;
                    DB::table('pharmacys')->where("id",$pharmacy_id)->update(['balance'=>$balance]);
                }
                if(!empty($request->input('npi'))){
                    $npi = $request->input('npi');
                } else {
                    $npi = $pharmacy->npi;
                }
                if(!empty($request->input('bestrx_pharmacy_id'))){
                    $bestrx_pharmacy_id = $request->input('bestrx_pharmacy_id');
                } else {
                    $bestrx_pharmacy_id = $pharmacy->bestrx_pharmacy_id;
                }
                if(!empty($request->input('ncpdp'))){
                    $ncpdp = $request->input('ncpdp');
                } else {
                    $ncpdp = $pharmacy->ncpdp;
                }
                if(!empty($request->input('dea'))){
                    $dea = $request->input('dea');
                } else {
                    $dea = $pharmacy->dea;
                }
                if(!empty($request->input('merchantFunc')) && $request->input('merchantFunc')>0) {
                    $merchantFunc = "1";
                    if(empty($pharmacy->merchantKey)){
                        $merchantKey = bin2hex(random_bytes(14));
                        $merchantSecret = bin2hex(random_bytes(20));
                    } else {
                        $merchantKey = $pharmacy->merchantKey;
                        $merchantSecret = $pharmacy->merchantSecret;
                    }
                } else {
                    $merchantFunc = "0";
                    $merchantKey = NULL;
                    $merchantSecret = NULL;
                }
                if(!empty($request->input('bestrxFunc')) && $request->input('bestrxFunc')>0) {
                    $bestrxFunc = "1";
                    if(empty($pharmacy->bestrxKey)){
                        $bestrxKey = bin2hex(random_bytes(14));
                        $bestrxSecret = bin2hex(random_bytes(20));
                    } else {
                        $bestrxKey = $pharmacy->bestrxKey;
                        $bestrxSecret = $pharmacy->bestrxSecret;
                    }
                } else {
                    $bestrxFunc = "0";
                    $bestrxKey = NULL;
                    $bestrxSecret = NULL;
                }
                DB::table('pharmacy_areas')->where('pharmacy_id',$pharmacy_id)->delete();
                $tariff_areas1 = $request->input('tariff_areas1');
                $tariff_areas2 = $request->input('tariff_areas2');
                $tariff_areas3 = $request->input('tariff_areas3');
                $data=[];
                if(!empty($tariff_areas1)) {
                    foreach($tariff_areas1 as $key=>$value){
                        $data[]=["pharmacy_id"=>$pharmacy_id,"area_id"=>$value,"type"=>1];
                    }
                }
                if(!empty($tariff_areas2)) {
                    foreach($tariff_areas2 as $key=>$value){
                        $data[]=["pharmacy_id"=>$pharmacy_id,"area_id"=>$value,"type"=>2];
                    }
                }
                if(!empty($tariff_areas3)) {
                    foreach($tariff_areas3 as $key=>$value){
                        $data[]=["pharmacy_id"=>$pharmacy_id,"area_id"=>$value,"type"=>3];
                    }
                }
                DB::table('pharmacy_areas')->insert($data);
                if($request->input('plan_id')=="2"){
                    if(!empty($request->input('date_end_trial'))) {
                        $date_end_trial = $request->input('date_end_trial');
                    } else {
                        if(empty($pharmacy->date_end_trial)){
                            $date_end_trial = date('Y-m-d', strtotime('now +3 day'));
                        } else {
                            $date_end_trial = $pharmacy->date_end_trial;
                        }
                    }
                } else {
                    $date_end_trial = NULL;
                }
                $data = app(\App\Actions\GeocodeAddress::class)->response($request->input('address'));
                $location = $data->results[0]->geometry->location->lat.','.$data->results[0]->geometry->location->lng;
                DB::table('pharmacys')->where('id', $pharmacy_id)->update(['name' => $request->input('name'),'email' => $request->input('email'),'plan_id' => $request->input('plan_id'),'date_end_trial'=>$date_end_trial,'tariff' => $request->input('tariff'),'tariff_next_day' => $request->input('tariff_next_day'),'tariff_same_day' => $request->input('tariff_same_day'),'tariff_asap' => $request->input('tariff_asap'),'tariff_after_hours' => $request->input('tariff_after_hours'),'tariff_fridge' => $request->input('tariff_fridge'),'tariff_area2' => $request->input('tariff_area2'),'tariff_area3' => $request->input('tariff_area3'),'tariff_area_more' => $request->input('tariff_area_more'),'phone' => $request->input('phone'),'logo' => $src,'image_front'=>$src2,'site' => $request->input('site'),'copay_bill'=>$copay_bill,'massiveBagsTransfer'=>$massiveBagsTransfer,'merchantFunc'=>$merchantFunc,'merchantKey'=>$merchantKey,'merchantSecret'=>$merchantSecret,'npi'=>$npi,'ncpdp'=>$ncpdp,'dea'=>$dea,'bestrxFunc'=>$bestrxFunc,'bestrx_pharmacy_id'=>$bestrx_pharmacy_id,'bestrxKey'=>$bestrxKey,'bestrxSecret'=>$bestrxSecret,'address' => $request->input('address'),'schedule'=>json_encode($schedule),'location' => $location,'zone_id'=>$request->input('zone_id')]);
            }
            return redirect("pharmacys/edit/$pharmacy_id");
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public static function pharmacysListAdd() {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if(Auth::user()->hasAnyRole('superadmin', 'admin', 'dispadmin', 'user')) {
            $plans = DB::table('plans')->orderBy('id','desc')->get();
            $areas = DB::table('area')->get();
            $input['name']='';
            $input['email']='';
            $input['phone']='';
            $input['image']='';
            $input['image_front']='';
            $input['site']='';
            $input['zone_id']='';
            $input['address']='';
            $input['tariff']='';
            $input['tariff_next_day']='';
            $input['tariff_same_day']='';
            $input['tariff_asap']='';
            $input['tariff_after_hours']='';
            $input['tariff_fridge']='';
            $input['plan_id']='';
            $input['tariff_areas']=[];
            $admin_areas = DB::table('admin_areas')->get();
            $res_view = view('pharmacy.pharmacy_add',['plans'=>$plans,'areas'=>$areas,'admin_areas'=>$admin_areas,'title'=>'Pharmacy Add','br1'=>'Pharmacies','br2'=>'Pharmacy','br3'=>'Pharmacy Add','alert'=>'','input'=>$input]);
            return self::renderPage($res_view);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public static function pharmacysListAddHandler(Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->can('admin'))) {
            if($request->input('save')>0) {
                $tariffSelection = $request->validate([
                    'plan_id' => 'required|integer|exists:plans,id',
                    'tariff_areas' => 'nullable|array',
                    'tariff_areas.*' => 'integer|distinct|exists:area,id',
                ], [
                    'plan_id.required' => 'Create and select a tariff plan before saving the pharmacy.',
                    'plan_id.exists' => 'The selected tariff plan no longer exists. Please select another plan.',
                    'tariff_areas.*.exists' => 'One of the selected tariff areas no longer exists. Please select it again.',
                ]);
                if(!empty(DB::table('pharmacys')->where('email', $request->input('email'))->first())) {
                    $plans = DB::table('plans')->orderBy('id','desc')->get();
                    $areas = DB::table('area')->get();
                    $input['name']=$request->input('name');
                    $input['email']=$request->input('email');
                    $input['phone']=$request->input('phone');
                    $input['image']='';
                    $input['image_front']='';
                    $input['site']=$request->input('site');
                    $input['zone_id']=$request->input('zone_id');
                    $input['address']=$request->input('address');
                    $input['tariff']=$request->input('tariff');
                    $input['tariff_next_day']=$request->input('tariff_next_day');
                    $input['tariff_same_day']=$request->input('tariff_same_day');
                    $input['tariff_asap']=$request->input('tariff_asap');
                    $input['tariff_after_hours']=$request->input('tariff_after_hours');
                    $input['tariff_fridge']=$request->input('tariff_fridge');
                    $input['plan_id']=$request->input('plan_id');
                    $input['tariff_areas']=$request->input('tariff_areas', []);
                    $admin_areas = DB::table('admin_areas')->get();
                    return view('pharmacy.pharmacy_add',['plans'=>$plans,'areas'=>$areas,'admin_areas'=>$admin_areas,'title'=>'Pharmacy Add','br1'=>'Pharmacies','br2'=>'Pharmacy','br3'=>'Pharmacy Add','alert'=>'Pharmacy with this email already exists','input'=>$input]);
                } else {
                    $location = self::pharmacyCreationLocation($request);
                    if($request->hasFile('image')) {
                        $file = $request->file('image');
                        $name_f = \App\Support\PublicUpload::name($request->file('image'));
                        $file->move(public_path() . '/images/pharmacys/',$name_f);
                        $src = '/images/pharmacys/'.$name_f;
                    } else {
                        $src = "";
                    }
                    if($request->hasFile('image_front')) {
                        $file = $request->file('image_front');
                        $name_f = \App\Support\PublicUpload::name($request->file('image_front'));
                        $file->move(public_path() . '/images/pharmacys/',$name_f);
                        $src2 = '/images/pharmacys/'.$name_f;
                    } else {
                        $src2 = "";
                    }
                    if(!empty($request->input('massiveBagsTransfer')) && $request->input('massiveBagsTransfer')>0) {
                        $massiveBagsTransfer = "1";
                    } else {
                        $massiveBagsTransfer = "0";
                    }
                    DB::transaction(function () use ($request, $src, $src2, $massiveBagsTransfer, $location, $tariffSelection) {
                        $pharmacy_id = DB::table('pharmacys')->insertGetId(['name' => $request->input('name'),'email' => $request->input('email'),'plan_id' => $request->input('plan_id'),'tariff' => $request->input('tariff'),'tariff_next_day' => $request->input('tariff_next_day'),'tariff_same_day' => $request->input('tariff_same_day'),'tariff_asap' => $request->input('tariff_asap'),'tariff_after_hours' => $request->input('tariff_after_hours'),'tariff_fridge' => $request->input('tariff_fridge'),'phone' => $request->input('phone'),'logo' => $src,'image_front' => $src2,'site' => $request->input('site'),'massiveBagsTransfer'=>$massiveBagsTransfer,'address' => $request->input('address'),'location' => $location,'zone_id'=>$request->input('zone_id')]);
                        $areas = array_map(function ($area_id) use ($pharmacy_id) {
                            return ['pharmacy_id' => $pharmacy_id, 'area_id' => $area_id, 'type' => 1];
                        }, $tariffSelection['tariff_areas'] ?? []);
                        if ($areas) {
                            DB::table('pharmacy_areas')->insert($areas);
                        }
                    });
                }
            }
            return redirect("pharmacys");
        } else if(Auth::user()->role == 'user') {
            if($request->input('save')>0) {
                if(!empty(DB::table('pharmacys')->where('email', $request->input('email'))->first())) {
                    $input['name']=$request->input('name');
                    $input['email']=$request->input('email');
                    $input['phone']=$request->input('phone');
                    $input['image']='';
                    $input['image_front']='';
                    $input['site']='';
                    $input['address']=$request->input('address');
                    return view('pharmacy.pharmacy_add',['title'=>'Pharmacy Add','br1'=>'Pharmacies','br2'=>'Pharmacy','br3'=>'Pharmacy Add','alert'=>'Pharmacy with this email already exists','input'=>$input]);
                } else {
                    if($request->hasFile('image')) {
                        $file = $request->file('image');
                        $name_f = \App\Support\PublicUpload::name($request->file('image'));
                        $file->move(public_path() . '/images/pharmacys/',$name_f);
                        $src = '/images/pharmacys/'.$name_f;
                    } else {
                        $src = '';
                    }
                    if($request->hasFile('image_front')) {
                        $file = $request->file('image_front');
                        $name_f = \App\Support\PublicUpload::name($request->file('image_front'));
                        $file->move(public_path() . '/images/pharmacys/',$name_f);
                        $src2 = '/images/pharmacys/'.$name_f;
                    } else {
                        $src2 = '';
                    }
                    $data = app(\App\Actions\GeocodeAddress::class)->response($request->input('address'));
                    $location = $data->results[0]->geometry->location->lat.','.$data->results[0]->geometry->location->lng;
                    DB::table('pharmacys')->insert(['isactive'=>'1','name' => $request->input('name'),'email' => $request->input('email'),'phone' => $request->input('phone'),'logo' => $src,'image_front' => $src2,'site' => $request->input('site'),'address' => $request->input('address'),'location' => $location,'admin_id'=>Auth::user()->id,'ip'=>$request->ip()]);
                    DB::table('users')->where('id', Auth::user()->id)->update(['role' => 'medic','pharmacy_id' => DB::table('pharmacys')->where('admin_id', Auth::user()->id)->value('id')]);
                }
            }
            return redirect("pharmacys");
        } else {
            return abort(403, self::$err_perm);
        }
    }

    private static function pharmacyCreationLocation(Request $request) {
        $request->validate(['address' => ['required', 'string', 'max:500']]);
        return app(GeocodeAddress::class)->handle($request->input('address'));
    }

    public static function officesList() {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->role == 'superadmin')) {
            $offices = DB::table('offices');
            if(!empty(request()->query('search'))) {
                $search = request()->query('search');
                $offices = $offices->where(function($query) use ($search) {
                    $query->where('name','LIKE','%'.$search.'%')
                          ->orWhere('email','LIKE','%'.$search.'%')
                          ->orWhere('phone','LIKE','%'.$search.'%')
                          ->orWhere('address','LIKE','%'.$search.'%');
                    });
            } else {
                $search='';
            }
            $countOnPage=30;
            $max_pages=ceil($offices->count()/$countOnPage);
            $page = self::currentPage();
            $pages = PageLinks::around($page, $max_pages);
            $offices = $offices->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
            $res_view = view('offices.list',['pages'=>$pages,'page0'=>$page,'search'=>$search,'offices'=>$offices,'title'=>'Offices List','br1'=>'Office','br2'=>'List']);
            return self::renderPage($res_view);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public static function officesListHandler(Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->role == 'superadmin')) {
            if($request->input('remove')>0) {
                DB::table('offices')->where('id', $request->input('user_id'))->delete();
            }
            $offices = DB::table('offices');
            if(!empty(request()->query('search'))) {
                $search = request()->query('search');
                $offices = $offices->where(function($query) use ($search) {
                    $query->where('name','LIKE','%'.$search.'%')
                          ->orWhere('email','LIKE','%'.$search.'%')
                          ->orWhere('phone','LIKE','%'.$search.'%')
                          ->orWhere('address','LIKE','%'.$search.'%');
                    });
            } else {
                $search='';
            }
            $countOnPage=30;
            $max_pages=ceil($offices->count()/$countOnPage);
            $page = self::currentPage();
            $pages = PageLinks::around($page, $max_pages);
            $offices = $offices->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
            $res_view = view('offices.list',['pages'=>$pages,'page0'=>$page,'search'=>$search,'offices'=>$offices,'title'=>'Offices List','br1'=>'Office','br2'=>'List']);
            return self::renderPage($res_view);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    /**
     * @param int|string $office_id
     */
    public static function officesListEdit($office_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->role == 'superadmin')) {
            $office = DB::table('offices')->where('id', $office_id)->first();
            if(empty($office)) {
                return abort(404, 'Office not found');
            }
            $admin_areas = DB::table('admin_areas')->get();
            $res_view = view('offices.form',['office'=>$office,'admin_areas'=>$admin_areas, 'title'=>'Office Edit','br1'=>'Offices','br2'=>'Office','br3'=>'Office Edit','alert'=>'']);
            return self::renderPage($res_view);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    /**
     * @param int|string $office_id
     */
    public static function officesListEditHandler(Request $request,$office_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->role == 'superadmin')) {
            if($request->input('save')>0) {
                if(!empty(DB::table('offices')->where('id','!=',$office_id)->where(function($query) use ($request) {
                        $query->where('email', $request->input('email'))->orWhere('zone_id', $request->input('zone_id'));
                    })->first())) {
                    $office=new \StdClass();
                    $office->name=$request->input('name');
                    $office->email=$request->input('email');
                    $office->phone=$request->input('phone');
                    $office->logo='';
                    $office->address=$request->input('address');
                    $office->zone_id=$request->input('zone_id');
                    $admin_areas = DB::table('admin_areas')->get();
                    return view('offices.form',['office'=>$office,'admin_areas'=>$admin_areas,'title'=>'Office Add','br1'=>'Offices','br2'=>'Office','br3'=>'Office Add','alert'=>'Office with this email or zone already exists']);
                } else {
                    if($request->hasFile('image')) {
                        $file = $request->file('image');
                        $file->move(public_path() . '/images/offices/',\App\Support\PublicUpload::name($request->file('image')));
                        $src = '/images/offices/'.\App\Support\PublicUpload::name($request->file('image'));
                    } else {
                        $office = DB::table('offices')->where('id', $office_id)->first();
                        $src = $office->logo;
                    }
                    $data = app(\App\Actions\GeocodeAddress::class)->response($request->input('address'));
                    $location = $data->results[0]->geometry->location->lat.','.$data->results[0]->geometry->location->lng;
                    DB::table('offices')->where('id', $office_id)->update(['name' => $request->input('name'),'email' => $request->input('email'),'phone' => $request->input('phone'),'logo' => $src,'address' => $request->input('address'),'location' => $location,'zone_id' => $request->input('zone_id')]);
                }
            }
            return redirect("offices");
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public static function officesListAdd() {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->role == 'superadmin')) {
            $office=new \StdClass();
            $office->name='';
            $office->email='';
            $office->phone='';
            $office->logo='';
            $office->address='';
            $office->zone_id='';
            $admin_areas = DB::table('admin_areas')->get();
            $res_view = view('offices.form',['office'=>$office,'admin_areas'=>$admin_areas,'title'=>'Office Add','br1'=>'Offices','br2'=>'Office','br3'=>'Office Add','alert'=>'']);
            return self::renderPage($res_view);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public static function officesListAddHandler(Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->role == 'superadmin')) {
            if($request->input('save')>0) {
                if(!empty(DB::table('offices')->where('email', $request->input('email'))->orWhere('zone_id', $request->input('zone_id'))->first())) {
                    $office=new \StdClass();
                    $office->name=$request->input('name');
                    $office->email=$request->input('email');
                    $office->phone=$request->input('phone');
                    $office->logo='';
                    $office->address=$request->input('address');
                    $office->zone_id=$request->input('zone_id');
                    $admin_areas = DB::table('admin_areas')->get();
                    return view('offices.form',['office'=>$office,'admin_areas'=>$admin_areas,'title'=>'Office Add','br1'=>'Offices','br2'=>'Office','br3'=>'Office Add','alert'=>'Office with this email or zone already exists']);
                } else {
                    if($request->hasFile('image')) {
                        $file = $request->file('image');
                        $file->move(public_path() . '/images/offices/',\App\Support\PublicUpload::name($request->file('image')));
                        $src = '/images/offices/'.\App\Support\PublicUpload::name($request->file('image'));
                    } else {
                        $src = '';
                    }
                    $data = app(\App\Actions\GeocodeAddress::class)->response($request->input('address'));
                    $location = $data->results[0]->geometry->location->lat.','.$data->results[0]->geometry->location->lng;
                    DB::table('offices')->insert(['name' => $request->input('name'),'email' => $request->input('email'),'phone' => $request->input('phone'),'logo' => $src,'address' => $request->input('address'),'location' => $location,'zone_id' => $request->input('zone_id')]);
                }
            }
            return redirect("offices");
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public static function pharmacysIntegrations(){
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->can('admin'))) {
            $pharmacys = DB::table('pharmacys')->where('pharmacys.merchantFunc','1')->where('pharmacys.isactive',1)->where('pharmacys.isblocked',0);
            if(!empty(request()->query('search'))) {
                $search = request()->query('search');
                $pharmacys = $pharmacys->where(function($query) use ($search) {
                    $query->where('pharmacys.name','LIKE','%'.$search.'%')
                          ->orWhere('pharmacys.email','LIKE','%'.$search.'%')
                          ->orWhere('pharmacys.phone','LIKE','%'.$search.'%')
                          ->orWhere('pharmacys.address','LIKE','%'.$search.'%')
                          ->orWhere('pharmacys.npi','LIKE','%'.$search.'%');
                    });
            } else {
                $search='';
            }
            $countOnPage=30;
            $max_pages=ceil($pharmacys->count()/$countOnPage);
            $page = self::currentPage();
            $pages = PageLinks::around($page, $max_pages);
            if(!empty(Auth::user()->zone_id)){
                $pharmacys=$pharmacys->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $pharmacys = $pharmacys->orderBy("pharmacys.id","desc")->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
            return view('pharmacy.integrations',['pages'=>$pages,'page0'=>$page,'search'=>$search,'pharmacys'=>$pharmacys,'title'=>'Pharmacys Integrations','br1'=>'Pharmacys','br2'=>'Integrations']);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public static function pharmacysIntegrationsHandler(Request $request){
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->can('admin'))) {
            if($request->hasFile('merchant_agreement') && !empty($request->input('pharmacy_id'))) {
                $name=\App\Support\PublicUpload::name($request->file('merchant_agreement'));
                $file = $request->file('merchant_agreement');
                $file->move(public_path() . '/agreements/pharmacys/',$name);
                $src = '/agreements/pharmacys/'.$name;
                DB::table('pharmacys')->where('pharmacys.merchantFunc','1')->where('id',$request->input('pharmacy_id'))->update(['merchant_agreement'=>$src]);
            }
            return redirect("/pharmacys/integrations");
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public static function driversInquiries() {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->can('admin'))) {
            $users = User::get();
            return view('drivers.inquiries',['users'=>$users,'title'=>'Drivers Inquiries','br1'=>'Drivers','br2'=>'Inquiries']);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    /**
     * @param int|string $driver_id
     */
    public static function driversPayouts($driver_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if(Auth::user()->hasAnyRole('superadmin', 'admin', 'dispadmin', 'driver')) {
            $user = User::find($driver_id);
            $orders = DB::table('cash_log')->where('driver_id',$driver_id)->orderBy('id','desc');
            $max_page = DB::table('cash_log')->where('driver_id',$driver_id);
            if(!empty(request()->query('created'))) {
                $orders = $orders->whereDate('created','=',request()->query('created'));
                $max_page = $max_page->whereDate('created','=',request()->query('created'));
            }
            $countOnPage=30;
            $max_pages=ceil($max_page->count()/$countOnPage);
            $page = self::currentPage();
            $pages = PageLinks::around($page, $max_pages);
            $orders = $orders->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
            $duty = DB::table('cash_log')->where('driver_id',$driver_id)->where('return','0')->sum('copay');
            $res_view = view('drivers.payouts',['user'=>$user,'orders'=>$orders,'pages'=>$pages,'duty'=>$duty,'title'=>'Drivers Payouts','br1'=>'Drivers','br2'=>'Payouts']);
            return self::renderPage($res_view);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    /**
     * @param int|string $pharmacy_id
     */
    public static function patientsImport($pharmacy_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if(Auth::user()->can('manage-pharmacy', $pharmacy_id)) {
            $res_view = view('patients.import',['step'=>"1",'title'=>'Patients Import','br1'=>'Patients','br2'=>'Patient Import','alert'=>'']);
            return self::renderPage($res_view);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    /**
     * @param int|string $pharmacy_id
     */
    public static function patientsImportHandler(Request $request,$pharmacy_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if(Auth::user()->can('manage-pharmacy', $pharmacy_id)) {
            if($request->input('step')==1) {
                $delimiter = $request->input('delimiter');
                if($request->hasFile('file')) {
                    $file = $request->file('file');
                    $file->move(public_path() . '/imports/',\App\Support\PublicUpload::name($request->file('file')));
                    $src = '/imports/'.\App\Support\PublicUpload::name($request->file('file'));
                    $csv = file_get_contents(public_path().$src);
                    $rows = explode(PHP_EOL, $csv);
                    $data = explode($delimiter, $rows[0]);
                    return view('patients.import',['step'=>"2",'column'=>$data,'src'=>$src,'delimiter'=>$delimiter,'title'=>'Patients Import','br1'=>'Patients','br2'=>'Patient Import','alert'=>'']);
                } else {
                    $src = '';
                    return view('patients.import',['step'=>"1",'title'=>'Patients Import','br1'=>'Patients','br2'=>'Patient Import','alert'=>'File was not uploaded.']);
                }
            } else if ($request->input('step')==2) {
                $src = $request->input('src');
                $delimiter = $request->input('delimiter');
                $csv = file_get_contents(public_path().$src);
                $rows = explode(PHP_EOL, $csv);
                $data = explode($delimiter, $rows[0]);
                foreach($data as $key=>$r) {
                    $data[$key] = str_replace(["\r\n","\r","\n"], '', $r);
                }
                $key_name = array_search($request->input('name'), $data);
                $key_last_name = array_search($request->input('last_name'), $data);
                if(empty($request->input('email'))) {
                    $key_email = NULL;
                } else {
                    $key_email = array_search($request->input('email'), $data);
                }
                $key_phone = array_search($request->input('phone'), $data);
                if(empty($request->input('home_phone'))) {
                    $key_home_phone = NULL;
                } else {
                    $key_home_phone = array_search($request->input('home_phone'), $data);
                }
                $key_address = array_search($request->input('address'), $data);
                if(empty($request->input('apartment'))) {
                    $key_apartment = NULL;
                } else {
                    $key_apartment = array_search($request->input('apartment'), $data);
                }
                $key_zip = array_search($request->input('zip'), $data);
                if(empty($request->input('birth_date'))) {
                    $key_birth_date = NULL;
                } else {
                    $key_birth_date = array_search($request->input('birth_date'), $data);
                }
                foreach ($rows as $key => $row) {
                    if($key>0 && $row!='') {
                        $data0 = explode($delimiter, $row);
                        if(!empty($key_email) || !empty($data0[$key_email])){
                            if(!empty(DB::table('users')->where('email', $data0[$key_email])->where('pharmacy_id', $pharmacy_id)->first())) {
                                unset($rows[$key]);
                                //return view('patients.import',['step'=>"1",'title'=>'Patients Import','br1'=>'Patients','br2'=>'Patient Import','alert'=>"User with this email (".$data0[$key_email].") already exists"]);
                            } 
                        }
                        if(!empty($key_phone) || !empty($data0[$key_phone])){
                            if(preg_match( '/^\+\d(\d{3})(\d{3})(\d{4})$/', $data0[$key_phone],  $matches )) {
                                $phone = '('.$matches[1].') ' .$matches[2] . '-' . $matches[3];
                            } else {
                                if(preg_match('/^(\d{3})(\d{3})(\d{4})$/', $data0[$key_phone],  $matches )) {
                                    $phone = '('.$matches[1].') ' .$matches[2] . '-' . $matches[3];
                                } else {
                                    if(preg_match('/^(\d{3})\-(\d{3})\-(\d{4})$/', $data0[$key_phone],  $matches )) {
                                        $phone = '('.$matches[1].') ' .$matches[2] . '-' . $matches[3];
                                    } else {
                                        $phone = $data0[$key_phone];
                                    }
                                }
                            }
                            if(!empty(DB::table('users')->where('phone', $phone)->where('pharmacy_id', $pharmacy_id)->first())) {
                                unset($rows[$key]);
                                //return view('patients.import',['step'=>"1",'title'=>'Patients Import','br1'=>'Patients','br2'=>'Patient Import','alert'=>"User with this phone (".$data0[$key_phone].") already exists"]);
                            }
                        }
                    }
                }
                foreach ($rows as $key => $row) {
                    if($key>0 && $row!='') {
                        $data0 = explode($delimiter, $row);
                        if(empty($key_email) || empty($data0[$key_email])){
                            $email = 'patients'.DB::table('users')->max('id').'@'.config('branding.account_email_domain');
                        } else {
                            $email = $data0[$key_email];
                        }
                        if(empty($key_phone) || empty($data0[$key_phone])){
                            $phone = NULL;
                        } else {
                            if(preg_match( '/^\+\d(\d{3})(\d{3})(\d{4})$/', $data0[$key_phone],  $matches )) {
                                $phone = '('.$matches[1].') ' .$matches[2] . '-' . $matches[3];
                            } else {
                                if(preg_match('/^(\d{3})(\d{3})(\d{4})$/', $data0[$key_phone],  $matches )) {
                                    $phone = '('.$matches[1].') ' .$matches[2] . '-' . $matches[3];
                                } else {
                                    if(preg_match('/^(\d{3})\-(\d{3})\-(\d{4})$/', $data0[$key_phone],  $matches )) {
                                        $phone = '('.$matches[1].') ' .$matches[2] . '-' . $matches[3];
                                    } else {
                                        $phone = $data0[$key_phone];
                                    }
                                }
                            }
                        }
                        if(empty($key_home_phone) || empty($data0[$key_home_phone])){
                            $home_phone = NULL;
                        } else {
                            $home_phone = $data0[$key_home_phone];
                        }
                        if(empty($key_apartment) || empty($data0[$key_apartment])){
                            $apartment = NULL;
                        } else {
                            $apartment = $data0[$key_apartment];
                        }
                        if(empty($key_birth_date) || empty($data0[$key_birth_date])){
                            $birth_date = NULL;
                        } else {
                            $birth_date = $data0[$key_birth_date];
                        }
                        $password = bin2hex(openssl_random_pseudo_bytes(4));
                        DB::table('users')->insert(['isactive' => '1','name' => $data0[$key_name],'last_name' => $data0[$key_last_name],'email' => $email,'phone' => $phone,'home_phone' => $home_phone,'address' => $data0[$key_address],'password' => Hash::make($password),'apartment' => $apartment,'zip' => $data0[$key_zip],'birth_date' => date("Y-m-d",strtotime($birth_date ?? '')),'pharmacy_id' => $pharmacy_id]);
                    }
                }
                return redirect("patients/$pharmacy_id");
            }
        } else {
            return abort(403, self::$err_perm);
        }
    }

    
    /**
     * @param int|string $pharmacy_id
     */
    public static function billing2($pharmacy_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->role == 'medic' && Auth::user()->pharmacy_id==$pharmacy_id) || ((Auth::user()->can('admin'))) || (Auth::user()->role == 'driver') || (Auth::user()->role == 'user' && Auth::user()->pharmacy_id==$pharmacy_id)) {
            $orders = DB::table('orders')->join('users', 'orders.user_id', '=', 'users.id')->join('statuses', 'orders.statuse_id', '=', 'statuses.id')->join('delivery_methods', 'orders.delivery_method_id', '=', 'delivery_methods.id')->join('delivery_times', 'orders.delivery_time_id', '=', 'delivery_times.id')->join('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id')->select('orders.id', 'orders.created' , 'orders.statuse_id', 'orders.pharmacy_id', 'orders.copay', 'users.name as username', 'delivery_methods.name as delivery_method', 'delivery_times.name as delivery_time', 'users.last_name as last_name', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end as useraddress'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end as userapartment'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end as userzip'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end as userlocation'), 'users.phone as userphone', DB::raw('case when orders.tariff is null then pharmacys.tariff else orders.tariff end as tariff'),'pharmacys.name as pharmacyname', 'pharmacys.address as pharmacyaddress','pharmacys.phone as pharmacyphone', 'statuses.name as statusename','statuses.color as statusecolor')->where('orders.pharmacy_id',$pharmacy_id)->where('orders.statuse_id','4')->groupBy('orders.id', 'orders.statuse_id', 'orders.created', 'delivery_methods.name', 'delivery_times.name', 'orders.copay', 'orders.pharmacy_id', 'users.name', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end'), 'users.phone','pharmacys.name', DB::raw('case when orders.tariff is null then pharmacys.tariff else orders.tariff end'), 'pharmacys.address','pharmacys.phone', 'statuses.name','statuses.color','users.last_name')->get();
            $sum_amount = 0;
            foreach($orders as $order) {
                $sum_amount = $sum_amount+$order->tariff;
            }
            return view('billing.list2',['orders'=>$orders,'sum_amount'=>$sum_amount,'pharmacy_id'=>$pharmacy_id,'title'=>'Billing','br1'=>'Billings','br2'=>'List']);
        } else {
            return abort(403, self::$err_perm);
        }
    }


    /**
     * @param int|string $pharmacy_id
     */
    public static function billing($pharmacy_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
        if(!empty($pharmacy)) {
            if(Auth::user()->can('manage-pharmacy', $pharmacy_id)) {
                $invoices = DB::table('invoices')->where('pharmacy_id',$pharmacy_id)->where("payed","0")->get();
                foreach($invoices as $invoice) {
                    $invoice_exclusions = DB::table('invoice_exclusion')->where('invoice_id',$invoice->id)->pluck('order_id')->toArray();
                    $orders = DB::table('orders')->where('pharmacy_id', $pharmacy_id)->where('invoice_payed','0')->whereIn('statuse_id',[4,8,9,10])->whereDate('finish', '>=', $invoice->date_from)->whereDate('finish', '<=', $invoice->date_to)->whereNotIn('id',$invoice_exclusions);
                    $count_orders=$orders->count();
                    $sum_amount = 0;
                    $sum_copay = 0;
                    $orders = $orders->get();
                    foreach($orders as $order) {
                        if(!empty($order->tariff)) {
                            $sum_amount = $sum_amount+$order->tariff;
                            if($order->statuse_copay==3 || $order->statuse_copay==4){
                                $sum_copay = $sum_copay+floatval($order->copay);
                            }
                        }
                    }
                    DB::table('invoices')->where('pharmacy_id',$pharmacy_id)->where('id',$invoice->id)->update(['count'=>$count_orders, 'amount'=>$sum_amount, 'copay'=>$sum_copay]);
                }
                $invoices = DB::table('invoices')->where('pharmacy_id',$pharmacy_id)->where("payed","0")->get();
                $sum_amount = 0;
                foreach($invoices as $invoice) {
                    if($pharmacy->copay_bill=='1') {
                        if((($invoice->amount+$invoice->corrections)-$invoice->copay)<0){
                            $sum_amount = $sum_amount+0;
                        } else {
                            $sum_amount = $sum_amount+(($invoice->amount+$invoice->corrections)-$invoice->copay);
                        }
                    } else {
                        $sum_amount = $sum_amount+($invoice->amount+$invoice->corrections);
                    }
                }
                if(floatval($sum_amount)>0) {
                    $balance = floatval($sum_amount)*-1;
                } else {
                    $balance = floatval($sum_amount);
                }
                $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
                if($pharmacy->balance<=0){
                    DB::table('pharmacys')->where("id",$pharmacy_id)->update(['balance'=>$balance]);
                }
                $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
                $invoices = DB::table('invoices')->where('invoices.pharmacy_id',$pharmacy_id)->leftJoin("pharmacy_payments","pharmacy_payments.invoice_id","=","invoices.id")->select("invoices.id","invoices.created","invoices.pharmacy_id","invoices.date_from","invoices.date_to","invoices.count","invoices.amount","invoices.copay","invoices.corrections","invoices.payed","pharmacy_payments.amount as payed_amount")->groupBy("invoices.id","invoices.created","invoices.pharmacy_id","invoices.date_from","invoices.date_to","invoices.count","invoices.amount","invoices.copay","invoices.corrections","invoices.payed","pharmacy_payments.amount")->orderBy('id','desc')->get();
                $payment_account = DB::table('payment_pharmacy_accounts')->where('pharmacy_id',$pharmacy_id)->first();
                $error = session()->get('error');
                $pharmacy_plan = DB::table('plans')->where('id', $pharmacy->plan_id)->first();
                $res_view = view('billing.list',['invoices'=>$invoices,'pharmacy'=>$pharmacy,'pharmacy_plan'=>$pharmacy_plan,'payment_account'=>$payment_account,'error'=>$error,'pharmacy_id'=>$pharmacy_id,'title'=>'Billing','br1'=>'Billings','br2'=>'List']);
                return self::renderPage($res_view);
            } else {
                return abort(403, self::$err_perm);
            }
        } else {
            return abort(404, "Not found pharmacy");
        }
    }

    /**
     * @param int|string $pharmacy_id
     * @param int|string $invoice_id
     */
    public static function billingOrders($pharmacy_id,$invoice_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
        if(!empty($pharmacy)) {
            if(Auth::user()->can('manage-pharmacy', $pharmacy_id)) {
                $invoice = DB::table('invoices')->where('pharmacy_id',$pharmacy_id)->where('id',$invoice_id)->first();
                if(empty($invoice)) {
                    return abort(404, 'Invoice not found');
                }
                $invoice_exclusions = DB::table('invoice_exclusion')->where('invoice_id',$invoice_id)->pluck('order_id')->toArray();
                $orders = DB::table('orders')->join('delivery_times', 'orders.delivery_time_id', '=', 'delivery_times.id')->select("orders.id","delivery_times.name as delivery_time","orders.finish","orders.created","orders.copay","orders.tariff","orders.pharmacy_id")->where('pharmacy_id', $pharmacy_id)->whereIn('statuse_id',[4,8,9,10])->whereDate('finish', '>=', $invoice->date_from)->whereDate('finish', '<=', $invoice->date_to)->whereNotIn('id',$invoice_exclusions)->get();
                return view('billing.orders',['invoice'=>$invoice,'orders'=>$orders,'title'=>'Billing','br1'=>'Billings','br2'=>'List']);
            } else {
                return abort(403, self::$err_perm);
            }
        } else {
            return abort(404, "Not found pharmacy");
        }
    }

    /**
     * @param int|string $pharmacy_id
     * @param int|string $invoice_id
     */
    public static function billingPrint($pharmacy_id,$invoice_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
        if(!empty($pharmacy)) {
            if(Auth::user()->can('manage-pharmacy', $pharmacy_id)) {
                $invoice = DB::table('invoices')->where('invoices.pharmacy_id',$pharmacy_id)->where('invoices.id',$invoice_id)->leftJoin("pharmacy_payments","pharmacy_payments.invoice_id","=","invoices.id")->select("invoices.id","invoices.created","invoices.pharmacy_id","invoices.date_from","invoices.date_to","invoices.count","invoices.amount","invoices.copay","invoices.corrections","invoices.payed","pharmacy_payments.amount as payed_amount")->first();
                if(empty($invoice)) {
                    return abort(404, 'Invoice not found');
                }
                $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
                $payment_account = DB::table('payment_pharmacy_accounts')->where('pharmacy_id',$pharmacy_id)->first();
                $invoice_exclusions = DB::table('invoice_exclusion')->where('invoice_id',$invoice_id)->pluck('order_id')->toArray();
                $orders = DB::table('orders')->where('pharmacy_id', $pharmacy_id)->whereIn('statuse_id',[4,8,9,10])->whereDate('finish', '>=', $invoice->date_from)->whereDate('finish', '<=', $invoice->date_to)->whereNotIn('id',$invoice_exclusions)->get();
                $pharmacy_driver = ["amount"=>0,"count"=>0];
                $next_day = ["amount"=>0,"count"=>0];
                $same_day = ["amount"=>0,"count"=>0];
                $asap = ["amount"=>0,"count"=>0];
                $after_hours = ["amount"=>0,"count"=>0];
                foreach($orders as $order) {
                    if($order->type_driver==2) {
                        $pharmacy_driver["amount"]+=$order->tariff;
                        $pharmacy_driver["count"]+=1;
                    } else {
                        if($order->delivery_time_id==1) {
                            $next_day["amount"]+=$order->tariff;
                            $next_day["count"]+=1;
                        }
                        if($order->delivery_time_id==2) {
                            $same_day["amount"]+=$order->tariff;
                            $same_day["count"]+=1;
                        }
                        if($order->delivery_time_id==3) {
                            $asap["amount"]+=$order->tariff;
                            $asap["count"]+=1;
                        }
                        if($order->delivery_time_id==4) {
                            $after_hours["amount"]+=$order->tariff;
                            $after_hours["count"]+=1;
                        }
                    }
                }
                return view('billing.print',['invoice'=>$invoice,'orders'=>$orders,'pharmacy'=>$pharmacy,'pharmacy_driver'=>$pharmacy_driver,'next_day'=>$next_day,'same_day'=>$same_day,'asap'=>$asap,'after_hours'=>$after_hours,'payment_account'=>$payment_account,'title'=>'Billing','br1'=>'Billings','br2'=>'List']);
            } else {
                return abort(403, self::$err_perm);
            }
        } else {
            return abort(404, "Not found pharmacy");
        }
    }

    /**
     * @param int|string $pharmacy_id
     * @param int|string $invoice_id
     */
    public static function billingPrintReport($pharmacy_id,$invoice_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
        if(!empty($pharmacy)) {
            if(Auth::user()->can('manage-pharmacy', $pharmacy_id)) {
                $invoice = DB::table('invoices')->where('invoices.pharmacy_id',$pharmacy_id)->where('invoices.id',$invoice_id)->leftJoin("pharmacy_payments","pharmacy_payments.invoice_id","=","invoices.id")->select("invoices.id","invoices.created","invoices.pharmacy_id","invoices.date_from","invoices.date_to","invoices.count","invoices.amount","invoices.copay","invoices.corrections","invoices.payed","pharmacy_payments.amount as payed_amount")->first();
                if(empty($invoice)) {
                    return abort(404, 'Invoice not found');
                }
                $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
                $invoice_exclusions = DB::table('invoice_exclusion')->where('invoice_id',$invoice_id)->pluck('order_id')->toArray();
                $orders = DB::table('orders')->join('delivery_times', 'orders.delivery_time_id', '=', 'delivery_times.id')->join('statuses', 'orders.statuse_id', '=', 'statuses.id')->join('users', 'orders.user_id', '=', 'users.id')->select("orders.id","delivery_times.name as delivery_time","statuses.name as status",DB::raw("CONCAT(users.name, ' ', users.last_name) as username"),"orders.finish","orders.created","orders.copay","orders.statuse_copay","orders.tariff","orders.pharmacy_id")->where('orders.pharmacy_id', $pharmacy_id)->whereIn('statuse_id',[4,8,9,10])->whereDate('finish', '>=', $invoice->date_from)->whereDate('finish', '<=', $invoice->date_to)->whereNotIn('orders.id',$invoice_exclusions)->get();
                return view('billing.report',['invoice'=>$invoice,'orders'=>$orders,'pharmacy'=>$pharmacy,'title'=>'Billing','br1'=>'Billings','br2'=>'List']);
            } else {
                return abort(403, self::$err_perm);
            }
        } else {
            return abort(404, "Not found pharmacy");
        }
    }

    /**
     * @param int|string $pharmacy_id
     */
    public static function billingHandler(Request $request,$pharmacy_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
        if(!empty($pharmacy)) {
            if(Auth::user()->can('manage-pharmacy', $pharmacy_id)) {
                $error='';
                // One payment attempt per invoice at a time, so a double click cannot charge twice.
                $payment_lock = !empty($request->input('pay')) && !empty($request->input('invoice_id')) ? Cache::lock('pay-invoice:'.$pharmacy_id.':'.$request->input('invoice_id'), 120) : null;
                if($payment_lock && !$payment_lock->get()) {
                    $error = "This invoice is already being paid. Please wait a moment and refresh.";
                    $payment_lock = null;
                } else if($payment_lock) {
                  try {
                    $invoice = DB::table('invoices')->where('id',$request->input('invoice_id'))->where('pharmacy_id',$pharmacy_id)->first();
                    $payment_account = DB::table('payment_pharmacy_accounts')->where('pharmacy_id',$pharmacy_id)->first();
                    if(!empty($invoice) && $invoice->payed=='1'){
                        $error = "This invoice is already paid.";
                    } else if(!empty($invoice)){
                        if($pharmacy->copay_bill=='1') {
                            if((($invoice->amount+$invoice->corrections)-$invoice->copay)<0) {
                                $amount = 0;
                            } else {
                                $amount = round((($invoice->amount+$invoice->corrections)-$invoice->copay),2);
                            }
                        } else {
                            $amount = round(($invoice->amount+$invoice->corrections),2);
                        }
                        // Creating an invoice already debited the balance; a balance of zero or more means it is covered.
                        if($amount==0 || floatval($pharmacy->balance)>=0) {
                            DB::table('pharmacy_payments')->insert(['pharmacy_id'=>$pharmacy_id,'invoice_id'=>$invoice->id,'amount'=>$amount,'transaction_id'=>'balance','type'=>'pay']);
                            DB::table('invoices')->where('id',$request->input('invoice_id'))->where('pharmacy_id',$pharmacy_id)->update(["payed"=>'1']);
                            $invoice_exclusions = DB::table('invoice_exclusion')->where('invoice_id',$request->input('invoice_id'))->pluck('order_id')->toArray();
                            DB::table('orders')->where('pharmacy_id', $pharmacy_id)->whereIn('statuse_id',[4,8,9,10])->whereDate('finish', '>=', $invoice->date_from)->whereDate('finish', '<=', $invoice->date_to)->whereNotIn('id',$invoice_exclusions)->update(["invoice_payed"=>"1"]);
                        } else if(!empty($payment_account) && $payment_account->type=="card" && !empty($payment_account->payment_profile_id)){
                            $client = new \Square\SquareClient([
                                'accessToken' => config('app.SQUARE_ACCESS_TOKEN'),
                                'environment' => config('app.SQUARE_ENVIRONMENT'),
                            ]);
                            $amount_money = new \Square\Models\Money();
                            $amount_money->setAmount(\App\Support\Money::cents($amount));
                            $amount_money->setCurrency('USD');
                            $unid = uniqid("",true).rand(0,100);
                            $body = new \Square\Models\CreatePaymentRequest(
                                $payment_account->payment_profile_id,
                                $unid
                            );
                            $body->setAmountMoney($amount_money);
                            $body->setAutocomplete(true);
                            $body->setCustomerId($payment_account->profile_id);
                            $body->setLocationId(config('app.SQUARE_LOCATION_ID'));
                            $body->setNote('Invoice #'.$invoice->id);
                            $api_response = $client->getPaymentsApi()->createPayment($body);
                            if ($api_response->isSuccess()) {
                                $result = $api_response->getResult();
                                $payment_status = $result->getPayment()->getStatus();
                                if($payment_status=="COMPLETED" || $payment_status=="APPROVED") {
                                    $balance = floatval($pharmacy->balance)+$amount;
                                    DB::table('pharmacy_payments')->insert(['pharmacy_id'=>$pharmacy_id,'invoice_id'=>$invoice->id,'amount'=>$amount,'transaction_id'=>$result->getPayment()->getId(),'type'=>'pay']);
                                    DB::table('pharmacys')->where("id",$pharmacy_id)->update(['balance'=>$balance]);
                                    DB::table('invoices')->where('id',$request->input('invoice_id'))->where('pharmacy_id',$pharmacy_id)->update(["payed"=>'1']);
                                    $invoice_exclusions = DB::table('invoice_exclusion')->where('invoice_id',$request->input('invoice_id'))->pluck('order_id')->toArray();
                                    DB::table('orders')->where('pharmacy_id', $pharmacy_id)->whereIn('statuse_id',[4,8,9,10])->whereDate('finish', '>=', $invoice->date_from)->whereDate('finish', '<=', $invoice->date_to)->whereNotIn('id',$invoice_exclusions)->update(["invoice_payed"=>"1"]);
                                } else {
                                    $error = "Payment not completed!";
                                }
                            } else {
                                $error = json_encode($api_response->getErrors());
                            }
                        } else if(!empty($payment_account) && $payment_account->type=="bank") {
                            if(!empty($request->input('card')) && !empty($request->input('name'))) {
                                $card_token = $request->input('card');
                                $name=explode(' ',$request->input('name'));
                                if(count($name)==1) {
                                    $name[1]="";
                                }
                                $client = new \Square\SquareClient([
                                    'accessToken' => config('app.SQUARE_ACCESS_TOKEN'),
                                    'environment' => config('app.SQUARE_ENVIRONMENT'),
                                ]);
                                $amount_money = new \Square\Models\Money();
                                $amount_money->setAmount(\App\Support\Money::cents($amount));
                                $amount_money->setCurrency('USD');
                                $unid = uniqid("",true).rand(0,100);
                                $body = new \Square\Models\CreatePaymentRequest(
                                    $card_token,
                                    $unid
                                );
                                $body->setAmountMoney($amount_money);
                                $body->setAutocomplete(true);
                                $body->setCustomerId($payment_account->profile_id);
                                $body->setLocationId(config('app.SQUARE_LOCATION_ID'));
                                $body->setNote('Invoice #'.$invoice->id);
                                $api_response = $client->getPaymentsApi()->createPayment($body);
                                if ($api_response->isSuccess()) {
                                    $result = $api_response->getResult();
                                    $payment_status = $result->getPayment()->getStatus();
                                    if($payment_status=="COMPLETED" || $payment_status=="APPROVED" || $payment_status=="PENDING") {
                                        $balance = floatval($pharmacy->balance)+$amount;
                                        DB::table('pharmacy_payments')->insert(['pharmacy_id'=>$pharmacy_id,'invoice_id'=>$invoice->id,'amount'=>$amount,'transaction_id'=>$result->getPayment()->getId(),'type'=>'pay']);
                                        DB::table('pharmacys')->where("id",$pharmacy_id)->update(['balance'=>$balance]);
                                        DB::table('invoices')->where('id',$request->input('invoice_id'))->where('pharmacy_id',$pharmacy_id)->update(["payed"=>'1']);
                                        $invoice_exclusions = DB::table('invoice_exclusion')->where('invoice_id',$request->input('invoice_id'))->pluck('order_id')->toArray();
                                        DB::table('orders')->where('pharmacy_id', $pharmacy_id)->whereIn('statuse_id',[4,8,9,10])->whereDate('finish', '>=', $invoice->date_from)->whereDate('finish', '<=', $invoice->date_to)->whereNotIn('id',$invoice_exclusions)->update(["invoice_payed"=>"1"]);
                                    } else {
                                        $error = "Payment not completed!";
                                    }
                                } else {
                                    $error = json_encode($api_response->getErrors());
                                }
                            } else {
                                $payment_account = DB::table('payment_pharmacy_accounts')->where('pharmacy_id',$pharmacy_id)->first();
                                $invoice = DB::table('invoices')->where('id',$request->input('invoice_id'))->where('pharmacy_id',$pharmacy_id)->first();
                                $success='';
                                $error='';
                                return view('card_pharmacy.pay',['payment_account'=>$payment_account,'invoice'=>$invoice,'success'=>$success,'error'=>$error,'title'=>'Cards','br1'=>'Cards','br2'=>'Card','br3'=>'Cards','alert'=>'']);
                            }
                        } else {
                            $error = "PAYMENT METHOD IS EMPTY! PLEASE, ADD YOUR PAYMENT METHOD.";
                        }
                    } else {
                        $error = "Invoice with this ID not found!";
                    }
                  } finally {
                    $payment_lock->release();
                  }
                }
                if(!empty($request->input('refill-amount'))) {
                    $payment_account = DB::table('payment_pharmacy_accounts')->where('pharmacy_id',$pharmacy_id)->first();
                    $amount = round(floatval($request->input('refill-amount')),2);
                    if($amount < 1) {
                        $error = "Enter a refill amount of at least $1.00.";
                    } else if(!empty($payment_account)){
                        if(!empty($payment_account) && $payment_account->type=="card" && !empty($payment_account->payment_profile_id)){
                            $client = new \Square\SquareClient([
                                'accessToken' => config('app.SQUARE_ACCESS_TOKEN'),
                                'environment' => config('app.SQUARE_ENVIRONMENT'),
                            ]);
                            $amount_money = new \Square\Models\Money();
                            $amount_money->setAmount(\App\Support\Money::cents($amount));
                            $amount_money->setCurrency('USD');
                            $unid = uniqid("",true).rand(0,100);
                            $body = new \Square\Models\CreatePaymentRequest(
                                $payment_account->payment_profile_id,
                                $unid
                            );
                            $body->setAmountMoney($amount_money);
                            $body->setAutocomplete(true);
                            $body->setCustomerId($payment_account->profile_id);
                            $body->setLocationId(config('app.SQUARE_LOCATION_ID'));
                            $body->setNote('Refill #'.$pharmacy_id);
                            $api_response = $client->getPaymentsApi()->createPayment($body);
                            if ($api_response->isSuccess()) {
                                $result = $api_response->getResult();
                                $payment_status = $result->getPayment()->getStatus();
                                if($payment_status=="COMPLETED" || $payment_status=="APPROVED") {
                                    $balance = floatval($pharmacy->balance)+$amount;
                                    DB::table('pharmacy_payments')->insert(['pharmacy_id'=>$pharmacy_id,'amount'=>$amount,'transaction_id'=>$result->getPayment()->getId(),'type'=>'refill']);
                                    DB::table('pharmacys')->where("id",$pharmacy_id)->update(['balance'=>$balance]);
                                } else {
                                    $error = "Payment not completed!";
                                }
                            } else {
                                $error = json_encode($api_response->getErrors());
                            }
                        } else if(!empty($payment_account) && $payment_account->type=="bank") {
                            $error = "PAYMENT METHOD BANK ACCOUNT CAN`T REFILL BALANCE.";
                        } else {
                            $error = "PAYMENT METHOD IS EMPTY! PLEASE, ADD YOUR PAYMENT METHOD.";
                        }
                    } else {
                        $error = "PAYMENT METHOD IS EMPTY! PLEASE, ADD YOUR PAYMENT METHOD.";
                    }
                }
                if((Auth::user()->can('admin')) && !empty($request->input('paid')) && !empty($request->input('invoice_id'))) {
                    $invoice = DB::table('invoices')->where('id',$request->input('invoice_id'))->where('pharmacy_id',$pharmacy_id)->first();
                    if(!empty($invoice) && $invoice->payed=='1'){
                        $error = "This invoice is already paid.";
                    } else if(!empty($invoice)){
                        if($pharmacy->copay_bill=='1') {
                            if((($invoice->amount+$invoice->corrections)-$invoice->copay)<0) {
                                $amount = 0;
                            } else {
                                $amount = round((($invoice->amount+$invoice->corrections)-$invoice->copay),2);
                            }
                        } else {
                            $amount = round(($invoice->amount+$invoice->corrections),2);
                        }
                        $balance = floatval($pharmacy->balance)+$amount;
                        DB::table('pharmacy_payments')->insert(['pharmacy_id'=>$pharmacy_id,'invoice_id'=>$invoice->id,'amount'=>$amount,'transaction_id'=>'','type'=>$request->input('type')]);
                        DB::table('pharmacys')->where("id",$pharmacy_id)->update(['balance'=>$balance]);
                        DB::table('invoices')->where('id',$request->input('invoice_id'))->where('pharmacy_id',$pharmacy_id)->update(["payed"=>'1']);
                        $invoice_exclusions = DB::table('invoice_exclusion')->where('invoice_id',$request->input('invoice_id'))->pluck('order_id')->toArray();
                        DB::table('orders')->where('pharmacy_id', $pharmacy_id)->whereIn('statuse_id',[4,8,9,10])->whereDate('finish', '>=', $invoice->date_from)->whereDate('finish', '<=', $invoice->date_to)->whereNotIn('id',$invoice_exclusions)->update(["invoice_payed"=>"1"]);
                    }
                }
                if((Auth::user()->can('admin')) && is_numeric($request->input('balance-change'))) {
                    if(floatval($request->input('balance-change'))===0){
                        DB::table('pharmacys')->where("id",$pharmacy_id)->update(["balance_ban"=>"0"]);
                    }
                    DB::table('pharmacys')->where("id",$pharmacy_id)->update(['balance'=>floatval($request->input('balance-change'))]);
                }
                if((Auth::user()->can('admin')) && is_numeric($request->input('corrections-change')) && !empty($invoice = DB::table('invoices')->where('id',$request->input('invoice_id'))->where('pharmacy_id',$pharmacy_id)->first())) {
                    $corrections=floatval($request->input('corrections-change'));
                    DB::table('invoices')->where('id',$request->input('invoice_id'))->where('pharmacy_id',$pharmacy_id)->update(["corrections"=>$corrections]);
                    if($pharmacy->balance<0) {
                        $balance = floatval($pharmacy->balance)+$invoice->corrections-$corrections;
                    } else {
                        $balance = floatval($pharmacy->balance)-$invoice->corrections+$corrections;
                    }
                    DB::table('pharmacys')->where("id",$pharmacy_id)->update(['balance'=>$balance]);
                }
                if((Auth::user()->can('admin')) && !empty($request->input('unblock'))) {
                    if($request->input('unblock')>0){
                        DB::table('pharmacys')->where("id",$pharmacy_id)->update(["balance_ban"=>"0"]);
                    }
                }
                if((Auth::user()->can('admin')) && !empty($request->input('remove')) && !empty($request->input('invoice_id'))) {
                    $invoice = DB::table('invoices')->where('id',$request->input('invoice_id'))->where('pharmacy_id',$pharmacy_id)->delete();
                    DB::table('invoice_exclusion')->where('invoice_id',$request->input('invoice_id'))->delete();
                }
                return redirect("/billing/$pharmacy_id")->with([ 'error' => $error ]);
            } else {
                return abort(403, self::$err_perm);
            }
        } else {
            return abort(404, "Not found pharmacy");
        }
    }

    /**
     * @param int|string $pharmacy_id
     */
    public function billingInvoiceAdd($pharmacy_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
        if(!empty($pharmacy)) {
            if((Auth::user()->can('admin'))) {
                $invoice = new \stdClass();
                $invoice->date_from="";
                $invoice->date_to="";
                $invoice_exclusions=[];
                $res_view = view('billing.invoice_form',['invoice'=>$invoice,'invoice_exclusions'=>$invoice_exclusions,'alert'=>'','title'=>'Billing Invoice Add','br1'=>'Billings','br2'=>'Invoice Add']);
                return self::renderPage($res_view);
            } else {
                return abort(403, self::$err_perm);
            }
        } else {
            return abort(404, "Not found pharmacy");
        }
    }

    /**
     * @param int|string $pharmacy_id
     */
    public function billingInvoiceAddHandler($pharmacy_id,Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
        if(!empty($pharmacy)) {
            if((Auth::user()->can('admin'))) {
                if($request->input('save')>0 && !empty($request->input('date_from')) && !empty($request->input('date_to'))) {
                    $orders = DB::table('orders')->where('pharmacy_id', $pharmacy_id)->where('invoice_payed','0')->whereIn('statuse_id',[4,8,9,10])->whereDate('finish', '>=', date($request->input('date_from')))->whereDate('finish', '<=', date($request->input('date_to')));
                    $invoice_exclusions = [];
                    if(!empty($request->input('invoice_exclusions'))){
                        $invoice_exclusions = array_filter(explode("\n",$request->input('invoice_exclusions')));
                        $orders=$orders->whereNotIn('id',$invoice_exclusions);
                    }
                    $count_orders=$orders->count();
                    $sum_amount = 0;
                    $sum_copay = 0;
                    $orders = $orders->get();
                    foreach($orders as $order) {
                        if(!empty($order->tariff)) {
                            $sum_amount = $sum_amount+$order->tariff;
                            if($order->statuse_copay==3 || $order->statuse_copay==4){
                                $sum_copay = $sum_copay+floatval($order->copay);
                            }
                        } else {
                            $pharmacy=DB::table('pharmacys')->where('pharmacys.id',$order->pharmacy_id)->first();
                            $pharmacy_plan=DB::table('plans')->where('plans.id',$pharmacy->plan_id)->first();
                            $patient=DB::table('users')->where('users.id',$order->user_id)->first();
                            $pharmacy_areas=DB::table('pharmacy_areas')->where('pharmacy_id',$order->pharmacy_id)->where('type',1)->pluck('area_id')->toArray();
                            $pharmacy_areas2=DB::table('pharmacy_areas')->where('pharmacy_id',$order->pharmacy_id)->where('type',2)->pluck('area_id')->toArray();
                            $pharmacy_areas3=DB::table('pharmacy_areas')->where('pharmacy_id',$order->pharmacy_id)->where('type',3)->pluck('area_id')->toArray();
                            $zip_tariff=DB::table('area')->whereIn('area.id',$pharmacy_areas)->whereRaw('ST_CONTAINS(polygon, POINT(?, ?))', \App\Support\GeoPoint::bindings($patient->location))->select("area.id")->first();
                            $zip_tariff2=DB::table('area')->whereIn('area.id',$pharmacy_areas2)->whereRaw('ST_CONTAINS(polygon, POINT(?, ?))', \App\Support\GeoPoint::bindings($patient->location))->select("area.id")->first();
                            $zip_tariff3=DB::table('area')->whereIn('area.id',$pharmacy_areas3)->whereRaw('ST_CONTAINS(polygon, POINT(?, ?))', \App\Support\GeoPoint::bindings($patient->location))->select("area.id")->first();
                            if(!empty($zip_tariff)){
                                if(is_numeric($pharmacy->tariff)) {
                                    $tariff = $pharmacy->tariff;
                                } else {
                                    $tariff = $pharmacy_plan->tariff;
                                }
                            } else if(!empty($zip_tariff2)){
                                if(is_numeric($pharmacy->tariff_area2)) {
                                    $tariff = $pharmacy->tariff_area2;
                                } else {
                                    $tariff = $pharmacy_plan->tariff_area2;
                                }
                            } else if(!empty($zip_tariff3)){
                                if(is_numeric($pharmacy->tariff_area3)) {
                                    $tariff = $pharmacy->tariff_area3;
                                } else {
                                    $tariff = $pharmacy_plan->tariff_area3;
                                }
                            } else {
                                if(is_numeric($pharmacy->tariff_area_more)) {
                                    $tariff = $pharmacy->tariff_area_more;
                                } else {
                                    $tariff = $pharmacy_plan->tariff_area_more;
                                }
                            }
                            if(is_numeric($pharmacy->tariff_next_day)) {
                                $tariff_next_day = $pharmacy->tariff_next_day;
                            } else {
                                $tariff_next_day = $pharmacy_plan->tariff_next_day;
                            }
                            if(is_numeric($pharmacy->tariff_same_day)) {
                                $tariff_same_day = $pharmacy->tariff_same_day;
                            } else {
                                $tariff_same_day = $pharmacy_plan->tariff_same_day;
                            }
                            if(is_numeric($pharmacy->tariff_asap)) {
                                $tariff_asap = $pharmacy->tariff_asap;
                            } else {
                                $tariff_asap = $pharmacy_plan->tariff_asap;
                            }
                            if(is_numeric($pharmacy->tariff_after_hours)) {
                                $tariff_after_hours = $pharmacy->tariff_after_hours;
                            } else {
                                $tariff_after_hours = $pharmacy_plan->tariff_after_hours;
                            }
                            if(is_numeric($pharmacy->tariff_fridge)) {
                                $tariff_fridge = $pharmacy->tariff_fridge;
                            } else {
                                $tariff_fridge = $pharmacy_plan->tariff_fridge;
                            }
                            if($order->type_driver==1) {
                                if($order->delivery_time_id==1) {
                                    $tariff_res = (floatval($tariff)+floatval($tariff_next_day)+floatval($order->extra_charge_driver));
                                } elseif($order->delivery_time_id==2) {
                                    $tariff_res = (floatval($tariff)+floatval($tariff_same_day)+floatval($order->extra_charge_driver));
                                } elseif($order->delivery_time_id==3) {
                                    $tariff_res = (floatval($tariff)+floatval($tariff_asap)+floatval($order->extra_charge_driver));
                                } elseif($order->delivery_time_id==4) {
                                    $tariff_res = (floatval($tariff)+floatval($tariff_after_hours)+floatval($order->extra_charge_driver));
                                } else {
                                    throw new \UnexpectedValueException("Unknown delivery time {$order->delivery_time_id} for order {$order->id}");
                                }
                                if($order->fridge==1) {
                                    $tariff_res+= floatval($tariff_fridge);
                                }
                            } else {
                                $tariff_res = floatval($tariff);
                            }
                            DB::table('orders')->where('id', $order->id)->update(['tariff'=>$tariff_res]);
                            $sum_amount = $sum_amount+$tariff_res;
                            if($order->statuse_copay==3 || $order->statuse_copay==4){
                                $sum_copay = $sum_copay+floatval($order->copay);
                            }
                        }
                    }
                    if($pharmacy->copay_bill=='1') {
                        if((($sum_amount)-$sum_copay)<0) {
                            $amount2 = 0;
                        } else {
                            $amount2 = round((($sum_amount)-$sum_copay),2);
                        }
                    } else {
                        $amount2 = round(($sum_amount),2);
                    }
                    $balance = floatval($pharmacy->balance)-$amount2;
                    DB::table('pharmacys')->where("id",$pharmacy_id)->update(['balance'=>$balance]);
                    $invoice_id = DB::table('invoices')->insertGetId(['pharmacy_id'=>$pharmacy_id,'date_from' => $request->input('date_from'),'date_to' => $request->input('date_to'), 'count'=>$count_orders, 'amount'=>$sum_amount, 'copay'=>$sum_copay]);
                    if(!empty($request->input('invoice_exclusions'))){
                        foreach($invoice_exclusions as $invoice_exclusion){
                            if(!empty($invoice_exclusion)){
                                DB::table('invoice_exclusion')->insert(['invoice_id' => $invoice_id,'order_id' => intval(preg_replace('/\s+/', '', $invoice_exclusion))]);
                            }
                        }
                    }
                }
                return redirect("billing/$pharmacy_id");
            } else {
                return abort(403, self::$err_perm);
            }
        } else {
            return abort(404, "Not found pharmacy");
        }
    }

    /**
     * @param int|string $pharmacy_id
     * @param int|string $invoice_id
     */
    public function billingInvoiceEdit($pharmacy_id,$invoice_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
        if(!empty($pharmacy)) {
            if((Auth::user()->can('admin'))) {
                $invoice = DB::table('invoices')->where('id', $invoice_id)->where('pharmacy_id', $pharmacy_id)->first();
                if(empty($invoice)) {
                    return abort(404, 'Invoice not found');
                }
                $invoice_exclusions = DB::table('invoice_exclusion')->where('invoice_id', $invoice_id)->get();
                $res_view = view('billing.invoice_form',['invoice'=>$invoice,'invoice_exclusions'=>$invoice_exclusions,'alert'=>'','title'=>'Billing Invoice Edit','br1'=>'Billings','br2'=>'Invoice Edit']);
                return self::renderPage($res_view);
            } else {
                return abort(403, self::$err_perm);
            }
        } else {
            return abort(404, "Not found pharmacy");
        }
    }

    /**
     * @param int|string $pharmacy_id
     * @param int|string $invoice_id
     */
    public function billingInvoiceEditHandler($pharmacy_id,$invoice_id, Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
        if(!empty($pharmacy)) {
            if((Auth::user()->can('admin'))) {
                if($request->input('save')>0 && !empty($request->input('date_from')) && !empty($request->input('date_to')) && !empty($request->input('tariff'))) {
                    $count_orders = DB::table('orders')->where('pharmacy_id', $pharmacy_id)->whereIn('statuse_id',[4,8,9,10])->whereDate('created', '>=', date($request->input('date_from')))->whereDate('created', '<=', date($request->input('date_to')));
                    $invoice_exclusions = [];
                    if(!empty($request->input('invoice_exclusions'))){
                        $invoice_exclusions = array_filter(explode("\n",$request->input('invoice_exclusions')));
                        $count_orders=$count_orders->whereNotIn('id',$invoice_exclusions);
                    }
                    $count_orders=$count_orders->count();
                    DB::table('invoices')->where('id', $invoice_id)->where('pharmacy_id', $pharmacy_id)->update(['date_from' => $request->input('date_from'),'date_to' => $request->input('date_to'), 'count'=>$count_orders, 'tariff'=>floatval($request->input('tariff'))]);
                    DB::table('invoice_exclusion')->where('invoice_id', $invoice_id)->delete();
                    if(!empty($request->input('invoice_exclusions'))){
                        foreach($invoice_exclusions as $invoice_exclusion){
                            if(!empty($invoice_exclusion)){
                                DB::table('invoice_exclusion')->insert(['invoice_id' => $invoice_id,'order_id' => intval(preg_replace('/\s+/', '', $invoice_exclusion))]);
                            }
                        }
                    }
                }
                return redirect("billing/$pharmacy_id");
            } else {
                return abort(403, self::$err_perm);
            }
        } else {
            return abort(404, "Not found pharmacy");
        }
    }



    public static function searchJson() {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
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
            return abort(403, self::$err_perm);
        }
    }



















    public static function settingsAdmins() {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->role == 'superadmin')) {
            $users = User::where('role','admin');
            if(!empty(request()->query('search'))) {
                $search = request()->query('search');
                $users = $users->where(function($query) use ($search) {
                    $query->where(DB::raw("CONCAT(name, ' ', last_name)"),'LIKE','%'.$search.'%')
                          ->orWhere('email','LIKE','%'.$search.'%')
                          ->orWhere('phone','LIKE','%'.$search.'%');
                    });
            } else {
                $search='';
            }
            if(!empty(request()->query('without_app'))) {
                $users = $users->whereNull('os')->whereDate('created_at', '>', date('Y-m-d', strtotime('now -7 day')));
            }
            $countOnPage=30;
            $max_pages=ceil($users->count()/$countOnPage);
            $page = self::currentPage();
            $pages = PageLinks::around($page, $max_pages);
            $users = $users->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
            return view('settings.admins',['users'=>$users,'pages'=>$pages,'page0'=>$page,'search'=>$search,'title'=>'Settings Admins','br1'=>'Settings','br2'=>'Admins','alert'=>'']);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public static function settingsAdminsHandler(Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->role == 'superadmin')) {
            if($request->input('activate')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['isactive' => 1]);
            }
            if($request->input('block')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['isblocked' => 1]);
            }
            if($request->input('unblock')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['isblocked' => 0]);
            }
            if($request->input('login')>0) {
                session(['login_superadmin' => Auth::user()->id]);
                Auth::loginUsingId($request->input('user_id'));
                return redirect('/');
            }
            return redirect('/settings/admins');
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public static function settingsAdminAreas() {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->role == 'superadmin')) {
            $admin_areas = DB::table('admin_areas')->get();
            return view('settings.admin_areas',['admin_areas'=>$admin_areas,'title'=>'Settings Admin Zones','br1'=>'Settings','br2'=>'Admin Zones','alert'=>'']);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public static function settingsAdminAreasAdd() {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->role == 'superadmin')) {
            $admin_area= new \stdClass();
            $admin_area->id=NULL;
            $admin_area->name=NULL;
            $admin_area->office_id=NULL;
            $admin_area_zones = DB::table('admin_area_zones')->pluck("area_id")->toArray();
            $areas = DB::table('area')->whereNotIn('id',$admin_area_zones)->get();
            return view('settings.admin_areas_form',['admin_area'=>$admin_area,'admin_area_zones'=>$admin_area_zones,'areas'=>$areas,'title'=>'Settings Admin Zones Add','br1'=>'Settings','br2'=>'Admin Zones Add','alert'=>'']);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public static function settingsAdminAreasAddHandler(Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->role == 'superadmin')) {
            $row = DB::table('admin_areas')->where('name',$request->input('name'))->first();
            if(empty($row)){
                $admin_area_id = DB::table('admin_areas')->insertGetId(['name'=>$request->input('name')]);
                DB::table('admin_area_zones')->where('admin_area_id',$admin_area_id)->delete();
                $tariff_areas = $request->input('tariff_areas');
                $data=[];
                if(!empty($tariff_areas)) {
                    foreach($tariff_areas as $value){
                        $data[]=["admin_area_id"=>$admin_area_id,"area_id"=>$value];
                    }
                }
                DB::table('admin_area_zones')->insert($data);
                return redirect('/settings/admin_areas');
            } else {
                $admin_area= new \stdClass();
                $admin_area->id=NULL;
                $admin_area->name=$request->input('name');
                $admin_area->office_id=$request->input('office_id');
                $admin_area_zones = DB::table('admin_area_zones')->pluck("area_id")->toArray();
                $areas = DB::table('area')->whereNotIn('id',$admin_area_zones)->get();
                $alert = 'Admin Zone with this name already exist!';
                return view('settings.admin_areas_form',['admin_area'=>$admin_area,'admin_area_zones'=>$admin_area_zones,'areas'=>$areas,'title'=>'Settings Admin Zones Add','br1'=>'Settings','br2'=>'Admin Zones Add','alert'=>$alert]);
            }
        } else {
            return abort(403, self::$err_perm);
        }
    }

    /**
     * @param int|string $admin_area_id
     */
    public static function settingsAdminAreasEdit($admin_area_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->role == 'superadmin')) {
            $admin_area= DB::table('admin_areas')->where('id',$admin_area_id)->first();
            $admin_area_zones = DB::table('admin_area_zones')->where('admin_area_id',$admin_area_id)->pluck("area_id")->toArray();
            $admin_area_zones2 = DB::table('admin_area_zones')->where('admin_area_id','!=',$admin_area_id)->pluck("area_id")->toArray();
            $areas = DB::table('area')->whereNotIn('id',$admin_area_zones2)->get();
            return view('settings.admin_areas_form',['admin_area'=>$admin_area,'admin_area_zones'=>$admin_area_zones,'areas'=>$areas,'title'=>'Settings Admin Zones Add','br1'=>'Settings','br2'=>'Admin Zones Add','alert'=>'']);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    /**
     * @param int|string $admin_area_id
     */
    public static function settingsAdminAreasEditHandler(Request $request,$admin_area_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->role == 'superadmin')) {
            $row = DB::table('admin_areas')->where('name',$request->input('name'))->where('id','!=',$admin_area_id)->first();
            if(empty($row)){
                DB::table('admin_areas')->where('id',$admin_area_id)->update(['name'=>$request->input('name')]);
                DB::table('admin_area_zones')->where('admin_area_id',$admin_area_id)->delete();
                $tariff_areas = $request->input('tariff_areas');
                $data=[];
                if(!empty($tariff_areas)) {
                    foreach($tariff_areas as $value){
                        $data[]=["admin_area_id"=>$admin_area_id,"area_id"=>$value];
                    }
                }
                DB::table('admin_area_zones')->insert($data);
                return redirect('/settings/admin_areas');
            } else {
                $admin_area= DB::table('admin_areas')->where('id',$admin_area_id)->first();
                $admin_area_zones = DB::table('admin_area_zones')->where('admin_area_id',$admin_area_id)->pluck("area_id")->toArray();
                $admin_area_zones2 = DB::table('admin_area_zones')->where('admin_area_id','!=',$admin_area_id)->pluck("area_id")->toArray();
                $areas = DB::table('area')->whereNotIn('id',$admin_area_zones2)->get();
                $alert = 'Admin Zone with this name already exist!';
                return view('settings.admin_areas_form',['admin_area'=>$admin_area,'admin_area_zones'=>$admin_area_zones,'areas'=>$areas,'title'=>'Settings Admin Zones Add','br1'=>'Settings','br2'=>'Admin Zones Add','alert'=>$alert]);
            }
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public static function settingsUsers() {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->can('admin'))) {
            $users = User::where('role','!=','driver')->where('role','!=','logist')->where('role','!=','medic');
            if(!empty(request()->query('search'))) {
                $search = request()->query('search');
                $users = $users->where(function($query) use ($search) {
                    $query->where(DB::raw("CONCAT(name, ' ', last_name)"),'LIKE','%'.$search.'%')
                          ->orWhere('email','LIKE','%'.$search.'%')
                          ->orWhere('phone','LIKE','%'.$search.'%');
                    });
            } else {
                $search='';
            }
            if(!empty(request()->query('without_app'))) {
                $users = $users->whereNull('os')->whereDate('created_at', '>', date('Y-m-d', strtotime('now -7 day')));
            }
            $countOnPage=30;
            $max_pages=ceil($users->count()/$countOnPage);
            $page = self::currentPage();
            $pages = PageLinks::around($page, $max_pages);
            $users = $users->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
            $res_view = view('settings.users',['users'=>$users,'pages'=>$pages,'page0'=>$page,'search'=>$search,'title'=>'Settings Users','br1'=>'Settings','br2'=>'Users','alert'=>'']);
            return self::renderPage($res_view);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public static function settingsUsersHandler(Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->can('admin'))) {
            if($request->filled('user_id')) {
                Gate::authorize('manage-account', [$request->input('user_id')]);
            }
            if($request->input('touseradmin')>0) {
                Gate::authorize('assign-role', 'admin');
            }
            $alert='';
            if($request->input('activate')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['isactive' => 1]);
            }
            if($request->input('block')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['isblocked' => 1]);
            }
            if($request->input('unblock')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['isblocked' => 0]);
            }
            if($request->input('remove')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->delete();
            }
            if($request->input('todriver')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['role' => 'driver']);
            }
            if($request->input('touser')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['role' => 'user']);
            }
            if($request->input('tologist')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['role' => 'logist']);
            }
            if($request->input('touseradmin')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['role' => 'admin']);
            }
            if($request->input('tomedic')>0) {
                if((int) DB::table('users')->where('id', $request->input('user_id'))->value('pharmacy_id')>0) {
                    DB::table('users')->where('id', $request->input('user_id'))->update(['role' => 'medic']);
                } else {
                    $alert="Error, this user dont have pharmacy_id!";
                }
            }
            $users = User::where('role','!=','driver')->where('role','!=','logist')->where('role','!=','medic');
            if(!empty(request()->query('search'))) {
                $search = request()->query('search');
                $users = $users->where(function($query) use ($search) {
                    $query->where(DB::raw("CONCAT(name, ' ', last_name)"),'LIKE','%'.$search.'%')
                          ->orWhere('email','LIKE','%'.$search.'%')
                          ->orWhere('phone','LIKE','%'.$search.'%');
                    });
            } else {
                $search='';
            }
            $countOnPage=30;
            $max_pages=ceil($users->count()/$countOnPage);
            $page = self::currentPage();
            $pages = PageLinks::around($page, $max_pages);
            $users = $users->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
            return view('settings.users',['users'=>$users,'pages'=>$pages,'page0'=>$page,'search'=>$search,'title'=>'Users','br1'=>'Settings','br2'=>'Users','alert'=>$alert]);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public static function settingsMedics() {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->can('admin'))) {
            $users = User::where('role','medic');
            if(!empty(request()->query('search'))) {
                $search = request()->query('search');
                $users = $users->where(function($query) use ($search) {
                    $query->where(DB::raw("CONCAT(name, ' ', last_name)"),'LIKE','%'.$search.'%')
                          ->orWhere('email','LIKE','%'.$search.'%')
                          ->orWhere('phone','LIKE','%'.$search.'%');
                    });
            } else {
                $search='';
            }
            $countOnPage=30;
            $max_pages=ceil($users->count()/$countOnPage);
            $page = self::currentPage();
            $pages = PageLinks::around($page, $max_pages);
            $users = $users->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
            $res_view = view('settings.medics',['users'=>$users,'pages'=>$pages,'page0'=>$page,'search'=>$search,'title'=>'Settings Users','br1'=>'Settings','br2'=>'Users','alert'=>'']);
            return self::renderPage($res_view);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public static function settingsMedicsHandler(Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->can('admin'))) {
            if($request->filled('user_id')) {
                Gate::authorize('manage-account', [$request->input('user_id')]);
            }
            if($request->input('touseradmin')>0) {
                Gate::authorize('assign-role', 'admin');
            }
            $alert='';
            if($request->input('activate')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['isactive' => 1]);
            }
            if($request->input('block')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['isblocked' => 1]);
            }
            if($request->input('unblock')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['isblocked' => 0]);
            }
            if($request->input('remove')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->delete();
            }
            if($request->input('todriver')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['role' => 'driver']);
            }
            if($request->input('touser')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['role' => 'user']);
            }
            if($request->input('tologist')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['role' => 'logist']);
            }
            if($request->input('touseradmin')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['role' => 'admin']);
            }
            if($request->input('tomedic')>0) {
                if((int) DB::table('users')->where('id', $request->input('user_id'))->value('pharmacy_id')>0) {
                    DB::table('users')->where('id', $request->input('user_id'))->update(['role' => 'medic']);
                } else {
                    $alert="Error, this user dont have pharmacy_id!";
                }
            }
            $users = User::where('role','medic');
            if(!empty(request()->query('search'))) {
                $search = request()->query('search');
                $users = $users->where(function($query) use ($search) {
                    $query->where(DB::raw("CONCAT(name, ' ', last_name)"),'LIKE','%'.$search.'%')
                          ->orWhere('email','LIKE','%'.$search.'%')
                          ->orWhere('phone','LIKE','%'.$search.'%');
                    });
            } else {
                $search='';
            }
            $countOnPage=30;
            $max_pages=ceil($users->count()/$countOnPage);
            $page = self::currentPage();
            $pages = PageLinks::around($page, $max_pages);
            $users = $users->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
            return view('settings.medics',['users'=>$users,'pages'=>$pages,'page0'=>$page,'search'=>$search,'title'=>'Users','br1'=>'Settings','br2'=>'Users','alert'=>$alert]);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public static function settingsDrivers() {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->can('admin'))) {
            $users = User::where('role','driver');
            if(!empty(request()->query('search'))) {
                $search = request()->query('search');
                $users = $users->where(function($query) use ($search) {
                    $query->where(DB::raw("CONCAT(name, ' ', last_name)"),'LIKE','%'.$search.'%')
                          ->orWhere('email','LIKE','%'.$search.'%')
                          ->orWhere('phone','LIKE','%'.$search.'%');
                    });
            } else {
                $search='';
            }
            $countOnPage=30;
            $max_pages=ceil($users->count()/$countOnPage);
            $page = self::currentPage();
            $pages = PageLinks::around($page, $max_pages);
            $users = $users->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
            $res_view = view('settings.drivers',['users'=>$users,'pages'=>$pages,'page0'=>$page,'search'=>$search,'title'=>'Settings Users','br1'=>'Settings','br2'=>'Users','alert'=>'']);
            return self::renderPage($res_view);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public static function settingsDriversHandler(Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->can('admin'))) {
            if($request->filled('user_id')) {
                Gate::authorize('manage-account', [$request->input('user_id')]);
            }
            if($request->input('touseradmin')>0) {
                Gate::authorize('assign-role', 'admin');
            }
            $alert='';
            if($request->input('activate')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['isactive' => 1]);
                $user = DB::table('users')->where('id', $request->input('user_id'))->first();
                try {
                    $twilio = new Client(config('app.twilio_sid'), config('app.twilio_auth_token'));
                    $twilio->messages->create("+1".str_replace(" ","",str_replace("-","",str_replace(")","",str_replace("(","",$user->phone)))), ["body" => "Welcome to QuikMedix, ".$user->name."!\nYour account has been verified. Now you can use the app.\nAll the best,\nThe team at QuikMedix", "from" => config('app.twilio_from_phone')]);
                } catch (\Throwable $exception) {
                    // The work around it continues, but the failure is no longer lost.
                    report($exception);
                }
            }
            if($request->input('block')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['isblocked' => 1]);
            }
            if($request->input('unblock')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['isblocked' => 0]);
            }
            if($request->input('remove')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->delete();
            }
            if($request->input('todriver')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['role' => 'driver']);
            }
            if($request->input('touser')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['role' => 'user']);
            }
            if($request->input('tologist')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['role' => 'logist']);
            }
            if($request->input('touseradmin')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['role' => 'admin']);
            }
            if($request->input('tomedic')>0) {
                if((int) DB::table('users')->where('id', $request->input('user_id'))->value('pharmacy_id')>0) {
                    DB::table('users')->where('id', $request->input('user_id'))->update(['role' => 'medic']);
                } else {
                    $alert="Error, this user dont have pharmacy_id!";
                }
            }
            $users = User::where('role','driver');
            if(!empty(request()->query('search'))) {
                $search = request()->query('search');
                $users = $users->where(function($query) use ($search) {
                    $query->where(DB::raw("CONCAT(name, ' ', last_name)"),'LIKE','%'.$search.'%')
                          ->orWhere('email','LIKE','%'.$search.'%')
                          ->orWhere('phone','LIKE','%'.$search.'%');
                    });
            } else {
                $search='';
            }
            $countOnPage=30;
            $max_pages=ceil($users->count()/$countOnPage);
            $page = self::currentPage();
            $pages = PageLinks::around($page, $max_pages);
            $users = $users->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
            return view('settings.drivers',['users'=>$users,'pages'=>$pages,'page0'=>$page,'search'=>$search,'title'=>'Users','br1'=>'Settings','br2'=>'Users','alert'=>$alert]);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public static function settingsLogists() {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->can('admin'))) {
            $users = User::where('role','logist');
            if(!empty(request()->query('search'))) {
                $search = request()->query('search');
                $users = $users->where(function($query) use ($search) {
                    $query->where(DB::raw("CONCAT(name, ' ', last_name)"),'LIKE','%'.$search.'%')
                          ->orWhere('email','LIKE','%'.$search.'%')
                          ->orWhere('phone','LIKE','%'.$search.'%');
                    });
            } else {
                $search='';
            }
            $countOnPage=30;
            $max_pages=ceil($users->count()/$countOnPage);
            $page = self::currentPage();
            $pages = PageLinks::around($page, $max_pages);
            $users = $users->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
            $res_view = view('settings.logists',['users'=>$users,'pages'=>$pages,'page0'=>$page,'search'=>$search,'title'=>'Settings Users','br1'=>'Settings','br2'=>'Users','alert'=>'']);
            return self::renderPage($res_view);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public static function settingsLogistsHandler(Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->can('admin'))) {
            if($request->filled('user_id')) {
                Gate::authorize('manage-account', [$request->input('user_id')]);
            }
            if($request->input('touseradmin')>0) {
                Gate::authorize('assign-role', 'admin');
            }
            $alert='';
            if($request->input('activate')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['isactive' => 1]);
            }
            if($request->input('block')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['isblocked' => 1]);
            }
            if($request->input('unblock')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['isblocked' => 0]);
            }
            if($request->input('remove')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->delete();
            }
            if($request->input('todriver')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['role' => 'driver']);
            }
            if($request->input('touser')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['role' => 'user']);
            }
            if($request->input('tologist')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['role' => 'logist']);
            }
            if($request->input('touseradmin')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['role' => 'admin']);
            }
            if($request->input('tomedic')>0) {
                if((int) DB::table('users')->where('id', $request->input('user_id'))->value('pharmacy_id')>0) {
                    DB::table('users')->where('id', $request->input('user_id'))->update(['role' => 'medic']);
                } else {
                    $alert="Error, this user dont have pharmacy_id!";
                }
            }
            $users = User::where('role','logist');
            if(!empty(request()->query('search'))) {
                $search = request()->query('search');
                $users = $users->where(function($query) use ($search) {
                    $query->where(DB::raw("CONCAT(name, ' ', last_name)"),'LIKE','%'.$search.'%')
                          ->orWhere('email','LIKE','%'.$search.'%')
                          ->orWhere('phone','LIKE','%'.$search.'%');
                    });
            } else {
                $search='';
            }
            $countOnPage=30;
            $max_pages=ceil($users->count()/$countOnPage);
            $page = self::currentPage();
            $pages = PageLinks::around($page, $max_pages);
            $users = $users->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
            return view('settings.logists',['users'=>$users,'pages'=>$pages,'page0'=>$page,'search'=>$search,'title'=>'Users','br1'=>'Settings','br2'=>'Users','alert'=>$alert]);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    /**
     * @param int|string $user_id
     */
    public static function settingsUsersedit($user_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->can('admin'))) {
            Gate::authorize('manage-account', [$user_id]);
            $user = DB::table('users')->where('id', $user_id)->first();
            $pharmacys = DB::table('pharmacys')->get();
            $user_actions = DB::table('action_log')->where('user_id', $user_id)->get(); 
            $admin_areas = DB::table('admin_areas')->get();
            $res_view = view('settings.user_edit',['user'=> $user,'admin_areas'=>$admin_areas, 'user_actions'=>$user_actions, 'pharmacys'=>$pharmacys, 'title'=>'User Edit','br1'=>'Settings','br2'=>'Users','br3'=>'User Edit','alert'=>'']);
            return self::renderPage($res_view);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    /**
     * @param int|string $user_id
     */
    public static function settingsUserseditHandler(Request $request,$user_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->can('admin'))) {
            Gate::authorize('manage-account', [$user_id]);
            $user = DB::table('users')->where('id', $user_id)->first();
            if($request->filled('role') && $request->input('role') !== $user->role) {
                Gate::authorize('assign-role', $request->input('role'));
            }
            if($request->input('save')>0) {
                if($request->hasFile('image')) {
                    $file = $request->file('image');
                    $file->move(public_path() . '/images/users/',\App\Support\PublicUpload::name($request->file('image')));
                    $src = '/images/users/'.\App\Support\PublicUpload::name($request->file('image'));
                } else {
                    $src = $user->image;
                }
                if($request->input('remove_photo')>0) {
                    $src = '/images/users/default-user-image.png';
                }
                if($request->hasFile('driving_license_img')) {
                    $file = $request->file('driving_license_img');
                    $file->move(public_path() . '/images/driving_license/',\App\Support\PublicUpload::name($request->file('driving_license_img')));
                    $driving_license_img = '/images/driving_license/'.\App\Support\PublicUpload::name($request->file('driving_license_img'));
                } else {
                    $driving_license_img = $user->driving_license_img;
                }
                if($request->hasFile('car_img')) {
                    $file = $request->file('car_img');
                    $file->move(public_path() . '/images/users/',\App\Support\PublicUpload::name($request->file('car_img')));
                    $car_img = '/images/users/'.\App\Support\PublicUpload::name($request->file('car_img'));
                } else {
                    $car_img = $user->car_img;
                }
                if(!empty($request->input('role')) && $request->input('role') !== $user->role) {
                    $role = $request->input('role');
                    DB::table('action_log')->insert(['type'=>'change role ('.$role.')','user_id'=>$user_id,'action_user_id'=>Auth::user()->id]);
                } else {
                    $role = $user->role;
                }
                $zone_id=NULL;
                if(!empty($request->input('zone_id'))) {
                    $zone_id=$request->input('zone_id');
                }
                if($request->input('password')!='') {
                    if($request->input('password')==$request->input('password2')) {
                        if(!empty($request->input('zip'))) {
                            $address = $request->input('address').' '.$request->input('zip');
                        } else {
                            $address = $request->input('address');
                        }
                        $data = app(\App\Actions\GeocodeAddress::class)->response($address);
                        $address = $data->results[0]->formatted_address;
                        $location = $data->results[0]->geometry->location->lat.','.$data->results[0]->geometry->location->lng;
                        app(LogUserChanges::class)->handle($request,$address,$user_id);
                        DB::table('users')->where('id', $user_id)->update(['name' => $request->input('name'),'last_name' => $request->input('last_name'),'email' => $request->input('email'),'phone' => $request->input('phone'), 'image' => $src,'address' => $address,'location' => $location,'password' => Hash::make($request->input('password')),'zip' => $request->input('zip'),'apartment' => $request->input('apartment'),'driving_license' => $request->input('driving_license'),'driving_license_img' => $driving_license_img,'identification_cards' => $request->input('identification_cards'),'transport' => $request->input('transport'), 'car_info' => $request->input('car_info'),'car_img' => $car_img,'payment_card' => $request->input('payment_card'), 'pharmacy_id' => $request->input('pharmacy'), 'role' => $role, 'zone_id'=>$zone_id]);
                        DB::table('action_log')->insert(['type'=>'change password','user_id'=>$user_id,'action_user_id'=>Auth::user()->id]);
                    } else {
                        $pharmacys = DB::table('pharmacys')->get();
                        $user = DB::table('users')->where('id', $user_id)->first();
                        $user_actions = DB::table('action_log')->where('user_id', $user_id)->get();
                        $admin_areas = DB::table('admin_areas')->get();
                        return view('settings.user_edit',['user'=> $user,'admin_areas'=>$admin_areas,'user_actions'=>$user_actions,'pharmacys'=>$pharmacys,'title'=>'User Edit','br1'=>'Settings','br2'=>'Users','br3'=>'User Edit','alert'=>'Passwords must match']);
                    }
                } else {
                    if(!empty($request->input('zip'))) {
                        $address = $request->input('address').' '.$request->input('zip');
                    } else {
                        $address = $request->input('address');
                    }
                    $data = app(\App\Actions\GeocodeAddress::class)->response($address);
                    $address = $data->results[0]->formatted_address;
                    $location = $data->results[0]->geometry->location->lat.','.$data->results[0]->geometry->location->lng;
                    app(LogUserChanges::class)->handle($request,$address,$user_id);
                    DB::table('users')->where('id', $user_id)->update(['name' => $request->input('name'),'last_name' => $request->input('last_name'),'email' => $request->input('email'),'phone' => $request->input('phone'), 'image' => $src,'address' => $address,'location' => $location,'zip' => $request->input('zip'),'apartment' => $request->input('apartment'),'driving_license' => $request->input('driving_license'),'driving_license_img' => $driving_license_img,'identification_cards' => $request->input('identification_cards'),'transport' => $request->input('transport'), 'car_info' => $request->input('car_info'),'car_img' => $car_img,'payment_card' => $request->input('payment_card'), 'pharmacy_id' => $request->input('pharmacy'),'role' => $role, 'zone_id'=>$zone_id]);
                }
            }
            $pharmacys = DB::table('pharmacys')->get();
            $user = DB::table('users')->where('id', $user_id)->first();
            $user_actions = DB::table('action_log')->where('user_id', $user_id)->get();
            $admin_areas = DB::table('admin_areas')->get();
            return view('settings.user_edit',['user'=> $user,'admin_areas'=>$admin_areas,'user_actions'=>$user_actions,'pharmacys'=>$pharmacys,'title'=>'User Edit','br1'=>'Settings','br2'=>'Users','br3'=>'User Edit','alert'=>'']);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public static function profile() {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        $pharmacys = DB::table('pharmacys')->get();
        $user = Auth::user();
        $admin_areas = DB::table('admin_areas')->get();
        $res_view = view('settings.user_edit',['user'=> $user,'admin_areas'=>$admin_areas,'pharmacys'=>$pharmacys,'title'=>'Profile','br1'=>'Profile','alert'=>'']);
        return self::renderPage($res_view);
    }

    public static function profileHandler(Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        $user_id = Auth::user()->id;
        if($user_id) {
            if($request->input('save')>0) {
                if($request->hasFile('image')) {
                    $file = $request->file('image');
                    $file->move(public_path() . '/images/users/',\App\Support\PublicUpload::name($request->file('image')));
                    $src = '/images/users/'.\App\Support\PublicUpload::name($request->file('image'));
                } else {
                    $src = Auth::user()->image;
                }
                if($request->input('remove_photo')>0) {
                    $src = '/images/users/default-user-image.png';
                }
                if($request->hasFile('driving_license_img')) {
                    $file = $request->file('driving_license_img');
                    $file->move(public_path() . '/images/driving_license/',\App\Support\PublicUpload::name($request->file('driving_license_img')));
                    $driving_license_img = '/images/driving_license/'.\App\Support\PublicUpload::name($request->file('driving_license_img'));
                } else {
                    $driving_license_img = Auth::user()->driving_license_img;
                }
                if($request->input('password')!='') {
                    if($request->input('password')==$request->input('password2')) {
                        if(!empty($request->input('zip'))) {
                            $address = $request->input('address').' '.$request->input('zip');
                        } else {
                            $address = $request->input('address');
                        }
                        $data = app(\App\Actions\GeocodeAddress::class)->response($address);
                        $address = $data->results[0]->formatted_address;
                        $location = $data->results[0]->geometry->location->lat.','.$data->results[0]->geometry->location->lng;
                        app(LogUserChanges::class)->handle($request,$address,$user_id);
                        DB::table('users')->where('id', $user_id)->update(['name' => $request->input('name'),'last_name' => $request->input('last_name'),'email' => $request->input('email'),'phone' => $request->input('phone'),'image' => $src,'address' => $address,'location' => $location,'password' => Hash::make($request->input('password')),'zip' => $request->input('zip'),'apartment' => $request->input('apartment'),'driving_license' => $request->input('driving_license'),'driving_license_img' => $driving_license_img,'identification_cards' => $request->input('identification_cards'),'car_info' => $request->input('car_info'),'payment_card' => $request->input('payment_card'),'pharmacy_id' => $request->input('pharmacy')]);
                        DB::table('action_log')->insert(['type'=>'change password','user_id'=>$user_id,'action_user_id'=>Auth::user()->id]);
                    } else {
                        $pharmacys = DB::table('pharmacys')->get();
                        $admin_areas = DB::table('admin_areas')->get();
                        $user = Auth::user();
                        return view('settings.user_edit',['user'=> $user,'admin_areas'=>$admin_areas,'pharmacys'=>$pharmacys,'title'=>'Profile','br1'=>'Profile','alert'=>'Passwords must match']);
                    }
                } else {
                    if(!empty($request->input('zip'))) {
                        $address = $request->input('address').' '.$request->input('zip');
                    } else {
                        $address = $request->input('address');
                    }
                    $data = app(\App\Actions\GeocodeAddress::class)->response($address);
                    $address = $data->results[0]->formatted_address;
                    $location = $data->results[0]->geometry->location->lat.','.$data->results[0]->geometry->location->lng;
                    app(LogUserChanges::class)->handle($request,$address,$user_id);
                    DB::table('users')->where('id', $user_id)->update(['name' => $request->input('name'),'last_name' => $request->input('last_name'),'email' => $request->input('email'),'phone' => $request->input('phone'),'image' => $src,'address' => $address,'location' => $location,'zip' => $request->input('zip'),'apartment' => $request->input('apartment'),'driving_license' => $request->input('driving_license'),'driving_license_img' => $driving_license_img,'identification_cards' => $request->input('identification_cards'),'car_info' => $request->input('car_info'),'payment_card' => $request->input('payment_card'),'pharmacy_id' => $request->input('pharmacy')]);
                }
                return redirect("profile");
            }
        } else {
            return abort(403, self::$err_perm);
        }
    }


    public static function settingsUsersAdd() {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->can('admin'))) {
            $input['name']='';
            $input['last_name']='';
            $input['email']='';
            $input['phone']='';
            $input['image']='';
            $input['address']='';
            $input['password']='';
            $input['apartment']='';
            $input['zip']='';
            $input['pharmacy']='';
            $pharmacys = DB::table('pharmacys')->get();
            $res_view = view('settings.user_add',['pharmacys'=>$pharmacys,'title'=>'User Add','br1'=>'Settings','br2'=>'Users','br3'=>'User Add','alert'=>'','input'=>$input]);
            return self::renderPage($res_view);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public static function settingsUsersAddHandler(Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->can('admin'))) {
            if($request->input('save')>0) {
                if(!empty(DB::table('users')->where('email', $request->input('email'))->where('pharmacy_id', $request->input('pharmacy'))->first())) {
                    $input['name']=$request->input('name');
                    $input['last_name']=$request->input('last_name');
                    $input['email']=$request->input('email');
                    $input['phone']=$request->input('phone');
                    $input['image']='';
                    $input['address']=$request->input('address');
                    $input['password']=$request->input('password');
                    $input['zip']=$request->input('zip');
                    $input['apartment']=$request->input('apartment');
                    $input['pharmacy']=$request->input('pharmacy');
                    $pharmacys = DB::table('pharmacys')->get();
                    return view('settings.user_add',['pharmacys'=>$pharmacys,'title'=>'User Add','br1'=>'Settings','br2'=>'Users','br3'=>'User Add','alert'=>'User with this email already exists','input'=>$input]);
                } else {
                    if(!empty(DB::table('users')->where('phone', $request->input('phone'))->where('pharmacy_id', $request->input('pharmacy'))->first())) {
                        $input['name']=$request->input('name');
                        $input['last_name']=$request->input('last_name');
                        $input['email']=$request->input('email');
                        $input['phone']=$request->input('phone');
                        $input['image']='';
                        $input['address']=$request->input('address');
                        $input['password']=$request->input('password');
                        $input['zip']=$request->input('zip');
                        $input['apartment']=$request->input('apartment');
                        $input['pharmacy']=$request->input('pharmacy');
                        $pharmacys = DB::table('pharmacys')->get();
                        return view('settings.user_add',['pharmacys'=>$pharmacys,'title'=>'User Add','br1'=>'Settings','br2'=>'Users','br3'=>'User Add','alert'=>'User with this phone already exists','input'=>$input]);
                    } else {
                        if($request->hasFile('image')) {
                            $file = $request->file('image');
                            $file->move(public_path() . '/images/users/',\App\Support\PublicUpload::name($request->file('image')));
                            $src = '/images/users/'.\App\Support\PublicUpload::name($request->file('image'));
                        } else {
                            $src = '';
                        }
                        if(!empty($request->input('zip'))) {
                            $address = $request->input('address').' '.$request->input('zip');
                        } else {
                            $address = $request->input('address');
                        }
                        $data = app(\App\Actions\GeocodeAddress::class)->response($address);
                        $address = $data->results[0]->formatted_address;
                        $location = $data->results[0]->geometry->location->lat.','.$data->results[0]->geometry->location->lng;
                        DB::table('users')->insert(['name' => $request->input('name'),'last_name' => $request->input('last_name'),'email' => $request->input('email'),'phone' => $request->input('phone'),'image' => $src,'address' => $address,'location' => $location,'password' => Hash::make($request->input('password')),'zip' => $request->input('zip'),'apartment' => $request->input('apartment'),'pharmacy_id' => $request->input('pharmacy')]);
                    }
                }
            }
            return redirect('settings/users');
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public static function cardAdd() {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if(Auth::user()->hasAnyRole('superadmin', 'admin', 'dispadmin', 'logist')) {
            $payment_account = DB::table('payment_accounts')->where('user_id',Auth::user()->id)->first();
            $success='';
            $error='';
            return view('card.add',['payment_account'=>$payment_account,'success'=>$success,'error'=>$error,'title'=>'Cards','br1'=>'Cards','br2'=>'Card','br3'=>'Cards','alert'=>'']);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public static function cardAddHandler(Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if(Auth::user()->hasAnyRole('superadmin', 'admin', 'dispadmin', 'logist')) {
            $success='';
            $error='';
            if(!empty($request->input('ncard')) && !empty($request->input('name')) && !empty($request->input('expire')) && !empty($request->input('ccv')) && !empty($request->input('address')) && !empty($request->input('zip'))) {
                $ncard=str_replace(' ','',$request->input('ncard'));
                $name=explode(' ',$request->input('name'));
                if(count($name)==1) {
                    $name[1]="";
                }
                $expire=explode('/',str_replace(' ','',$request->input('expire')));
                $ccv=$request->input('ccv');
                $zip=$request->input('zip');
                $user_address = explode(',',$request->input('address'));
                if(count($user_address)==1) {
                    $user_address[1]="";
                    $user_address[2]="";
                }
                if(count($user_address)==2) {
                    $user_address[2]="";
                }
                $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
                $merchantAuthentication->setName(config('app.MERCHANT_LOGIN_ID'));
                $merchantAuthentication->setTransactionKey(config('app.MERCHANT_TRANSACTION_KEY'));
                // Set the transaction's refId
                $refId = 'ref' . time();
                // Set credit card information for payment profile
                $payment_account = DB::table('payment_accounts')->where('user_id',Auth::user()->id)->first();
                if(!empty($payment_account)) {
                    $request = new AnetAPI\UpdateCustomerPaymentProfileRequest();
                    $request->setMerchantAuthentication($merchantAuthentication);
                    $request->setCustomerProfileId($payment_account->profile_id);
                    $controller = new AnetController\GetCustomerProfileController($request);
                    $creditCard = new AnetAPI\CreditCardType();
                    $creditCard->setCardNumber($ncard);
                    $creditCard->setExpirationDate($expire[1].'-'.$expire[0]);
                    $creditCard->setCardCode($ccv);
                    $paymentCreditCard = new AnetAPI\PaymentType();
                    $paymentCreditCard->setCreditCard($creditCard);
                    $billTo = new AnetAPI\CustomerAddressType();
                    $billTo->setFirstName($name[0]);
                    $billTo->setLastName($name[1]);
                    $billTo->setCompany("QuikMedix");
                    $billTo->setAddress($user_address[0]);
                    $billTo->setCity($user_address[1]);
                    $billTo->setState($user_address[2]);
                    $billTo->setZip($zip);
                    $billTo->setCountry("USA");
                    $billTo->setPhoneNumber(Auth::user()->phone);
                    $billTo->setfaxNumber("999-999-9999");
                    $paymentprofile = new AnetAPI\CustomerPaymentProfileExType();
                    $paymentprofile->setCustomerPaymentProfileId($payment_account->payment_profile_id);
                    $paymentprofile->setBillTo($billTo);
                    $paymentprofile->setPayment($paymentCreditCard);	
                    // Submit a UpdatePaymentProfileRequest
                    $request->setPaymentProfile($paymentprofile);
                    $controller = new AnetController\UpdateCustomerPaymentProfileController($request);
                    /** @var AnetAPI\UpdateCustomerPaymentProfileResponse $response */
                    $response = $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::PRODUCTION);
                    if (($response != null) && ($response->getMessages()->getResultCode() == "Ok") )
                    {
                        $success= "New Card successfully added!";
                        DB::table('payment_accounts')->where('user_id',Auth::user()->id)->update(['card'=>$ncard]);
                    } else if ($response != null) {
                        $errorMessages = $response->getMessages()->getMessage();
                        $error=$errorMessages[0]->getText();
                    }
                } else {
                    $creditCard = new AnetAPI\CreditCardType();
                    $creditCard->setCardNumber($ncard);
                    $creditCard->setExpirationDate($expire[1].'-'.$expire[0]);
                    $creditCard->setCardCode($ccv);
                    $paymentCreditCard = new AnetAPI\PaymentType();
                    $paymentCreditCard->setCreditCard($creditCard);
                    // Create the Bill To info for new payment type
                    $billTo = new AnetAPI\CustomerAddressType();
                    $billTo->setFirstName($name[0]);
                    $billTo->setLastName($name[1]);
                    $billTo->setCompany("QuikMedix");
                    $billTo->setAddress($user_address[0]);
                    $billTo->setCity($user_address[1]);
                    $billTo->setState($user_address[2]);
                    $billTo->setZip($zip);
                    $billTo->setCountry("USA");
                    $billTo->setPhoneNumber(Auth::user()->phone);
                    $billTo->setfaxNumber("999-999-9999");
                    // Create a customer shipping address
                    $customerShippingAddress = new AnetAPI\CustomerAddressType();
                    $customerShippingAddress->setFirstName($name[0]);
                    $customerShippingAddress->setLastName($name[1]);
                    $customerShippingAddress->setCompany("QuikMedix");
                    $customerShippingAddress->setAddress($user_address[0]);
                    $customerShippingAddress->setCity($user_address[1]);
                    $customerShippingAddress->setState($user_address[2]);
                    $customerShippingAddress->setZip($zip);
                    $customerShippingAddress->setCountry("USA");
                    $customerShippingAddress->setPhoneNumber(Auth::user()->phone);
                    $customerShippingAddress->setFaxNumber("999-999-9999");
                    // Create an array of any shipping addresses
                    $shippingProfiles[] = $customerShippingAddress;
                    // Create a new CustomerPaymentProfile object
                    $paymentProfile = new AnetAPI\CustomerPaymentProfileType();
                    $paymentProfile->setCustomerType('individual');
                    $paymentProfile->setBillTo($billTo);
                    $paymentProfile->setPayment($paymentCreditCard);
                    $paymentProfiles[] = $paymentProfile;
                    // Create a new CustomerProfileType and add the payment profile object
                    $customerProfile = new AnetAPI\CustomerProfileType();
                    $customerProfile->setDescription("Customer ".Auth::user()->id);
                    $customerProfile->setMerchantCustomerId(Auth::user()->id);
                    $customerProfile->setEmail(Auth::user()->email);
                    $customerProfile->setpaymentProfiles($paymentProfiles);
                    $customerProfile->setShipToList($shippingProfiles);
                    // Assemble the complete transaction request
                    $request = new AnetAPI\CreateCustomerProfileRequest();
                    $request->setMerchantAuthentication($merchantAuthentication);
                    $request->setRefId($refId);
                    $request->setProfile($customerProfile);
                    // Create the controller and get the response
                    $controller = new AnetController\CreateCustomerProfileController($request);
                    /** @var AnetAPI\CreateCustomerProfileResponse $response */
                    $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::PRODUCTION);
                    if (($response != null) && ($response->getMessages()->getResultCode() == "Ok")) {
                        $paymentProfiles = $response->getCustomerPaymentProfileIdList();
                        DB::table('payment_accounts')->insert(['user_id'=>Auth::user()->id,'profile_id'=>$response->getCustomerProfileId(),'payment_profile_id'=>$paymentProfiles[0],'card'=>$ncard]);
                        $success="Card successfully added!";
                    } else {
                        $errorMessages = $response->getMessages()->getMessage();
                        $error=$errorMessages[0]->getText();
                    }
                }
            } else {
                $error='All card input is required.';
            }
            $payment_account = DB::table('payment_accounts')->where('user_id',Auth::user()->id)->first();
            return view('card.add',['payment_account'=>$payment_account,'success'=>$success,'error'=>$error,'title'=>'Cards','br1'=>'Cards','br2'=>'Card','br3'=>'Cards','alert'=>'']);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    /**
     * @param int|string $pharmacy_id
     */
    public static function cardPharmacyAdd($pharmacy_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
        if(!empty($pharmacy)) {
            if(Auth::user()->can('manage-pharmacy', $pharmacy_id)) {
                $payment_account = DB::table('payment_pharmacy_accounts')->where('pharmacy_id',$pharmacy_id)->first();
                $success='';
                $error='';
                return view('card_pharmacy.add',['payment_account'=>$payment_account,'success'=>$success,'error'=>$error,'title'=>'Cards','br1'=>'Cards','br2'=>'Card','br3'=>'Cards','alert'=>'']);
            } else {
                return abort(403, self::$err_perm);
            }
        } else {
            return abort(404, "Not found pharmacy");
        }
    }

    /**
     * @param int|string $pharmacy_id
     */
    public static function cardPharmacyAddHandler($pharmacy_id,Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
        if(!empty($pharmacy)) {
            if(Auth::user()->can('manage-pharmacy', $pharmacy_id)) {
                $success='';
                $error='';
                if(!empty($request->input('type')) && !empty($request->input('name')) && !empty($request->input('address')) && !empty($request->input('zip'))) {
                    $card_token = $request->input('card');
                    $type = $request->input('type');
                    $name=explode(' ',$request->input('name'));
                    if(count($name)==1) {
                        $name[1]="";
                    }
                    $zip=$request->input('zip');
                    $user_address = explode(',',$request->input('address'));
                    if(count($user_address)==1) {
                        $user_address[1]="";
                        $user_address[2]="";
                    }
                    if(count($user_address)==2) {
                        $user_address[2]="";
                    }
                    if(count($user_address)==3) {
                        $user_address[3]="NY";
                    }
                    $amount = 1;
                    $payment_account = DB::table('payment_pharmacy_accounts')->where('pharmacy_id',$pharmacy_id)->first();
                    $client = new \Square\SquareClient([
                        'accessToken' => config('app.SQUARE_ACCESS_TOKEN'),
                        'environment' => config('app.SQUARE_ENVIRONMENT'),
                    ]);
                    $address = new \Square\Models\Address();
                    $address->setAddressLine1($user_address[0]);
                    $address->setAddressLine2($user_address[1]);
                    $address->setLocality($user_address[2]);
                    $address->setAdministrativeDistrictLevel1($user_address[3]);
                    $address->setPostalCode($zip);
                    $address->setCountry('US');
                    if(empty($payment_account)) {
                        $body = new \Square\Models\CreateCustomerRequest();
                        $body->setGivenName($name[0]);
                        $body->setFamilyName($name[1]);
                        $body->setEmailAddress($pharmacy->email);
                        $body->setAddress($address);
                        $body->setPhoneNumber(str_replace(" ","",str_replace("-","",str_replace(")","",str_replace("(","",$pharmacy->phone)))));
                        $body->setReferenceId($pharmacy_id);
                        $body->setNote('Pharmacy #'.$pharmacy_id);
                        $api_response = $client->getCustomersApi()->createCustomer($body);
                        if ($api_response->isSuccess()) {
                            $result = $api_response->getResult();
                            DB::table('payment_pharmacy_accounts')->insert(['pharmacy_id'=>$pharmacy_id,"type"=>$type,"profile_id"=>$result->getCustomer()->getId()]);
                            $payment_account = DB::table('payment_pharmacy_accounts')->where('pharmacy_id',$pharmacy_id)->first();
                        } else {
                            $error = json_encode($api_response->getErrors());
                            return view('card_pharmacy.add',['payment_account'=>$payment_account,'success'=>$success,'error'=>$error,'title'=>'Cards','br1'=>'Cards','br2'=>'Card','br3'=>'Cards','alert'=>'']);
                        }
                    } else {
                        DB::table('payment_pharmacy_accounts')->where('pharmacy_id',$pharmacy_id)->update(["type"=>$type]);
                    }
                    if($type=="card") {
                        $amount_money = new \Square\Models\Money();
                        $amount_money->setAmount(\App\Support\Money::cents($amount));
                        $amount_money->setCurrency('USD');
                        $unid = uniqid("",true).rand(0,100);
                        $body = new \Square\Models\CreatePaymentRequest(
                            $card_token,
                            $unid
                        );
                        $body->setAmountMoney($amount_money);
                        $body->setAutocomplete(true);
                        $body->setCustomerId($payment_account->profile_id);
                        $body->setLocationId(config('app.SQUARE_LOCATION_ID'));
                        $body->setNote('Authorization Card');
                        $api_response = $client->getPaymentsApi()->createPayment($body);
                        if ($api_response->isSuccess()) {
                            $result = $api_response->getResult();
                            $payment_id = $result->getPayment()->getId();
                            $payment_status = $result->getPayment()->getStatus();
                            if($payment_status=="COMPLETED" || $payment_status=="APPROVED" || $payment_status=="PENDING") {
                                DB::table('payment_pharmacy_accounts')->where('pharmacy_id',$pharmacy_id)->update(["payment_id"=>$payment_id]);
                                $card = new \Square\Models\Card();
                                $card->setCardholderName($name[0].' '.$name[1]);
                                $card->setBillingAddress($address);
                                $card->setCustomerId($payment_account->profile_id);
                                $unid = uniqid("",true).rand(0,100);
                                $body = new \Square\Models\CreateCardRequest(
                                    $unid,
                                    $payment_id,
                                    $card
                                );
                                $api_response = $client->getCardsApi()->createCard($body);
                                if ($api_response->isSuccess()) {
                                    $result = $api_response->getResult();
                                    $unid = uniqid("",true).rand(0,100);
                                    DB::table('payment_pharmacy_accounts')->where('pharmacy_id',$pharmacy_id)->update(["payment_profile_id"=>$result->getCard()->getId(),"card"=>$result->getCard()->getLast4()]);
                                    $body = new \Square\Models\RefundPaymentRequest($unid, $amount_money);
                                    $body->setPaymentId($payment_id);
                                    $api_response = $client->getRefundsApi()->refundPayment($body);
                                    if ($api_response->isSuccess()) {
                                        $success="Payment Method successfully added!";
                                    } else {
                                        $error = "Payment Method successfully added, but refund payment not completed. ".json_encode($api_response->getErrors());
                                        return view('card_pharmacy.add',['payment_account'=>$payment_account,'success'=>$success,'error'=>$error,'title'=>'Cards','br1'=>'Cards','br2'=>'Card','br3'=>'Cards','alert'=>'']);
                                    }
                                } else {
                                    $error = json_encode($api_response->getErrors());
                                    return view('card_pharmacy.add',['payment_account'=>$payment_account,'success'=>$success,'error'=>$error,'title'=>'Cards','br1'=>'Cards','br2'=>'Card','br3'=>'Cards','alert'=>'']);
                                }
                            } else {
                                $error = "Payment not completed!";
                                return view('card_pharmacy.add',['payment_account'=>$payment_account,'success'=>$success,'error'=>$error,'title'=>'Cards','br1'=>'Cards','br2'=>'Card','br3'=>'Cards','alert'=>'']);
                            }
                        } else {
                            $error = json_encode($api_response->getErrors());
                            return view('card_pharmacy.add',['payment_account'=>$payment_account,'success'=>$success,'error'=>$error,'title'=>'Cards','br1'=>'Cards','br2'=>'Card','br3'=>'Cards','alert'=>'']);
                        }
                    }
                } else {
                    $error='All Payment Method input is required.';
                }
                $payment_account = DB::table('payment_pharmacy_accounts')->where('pharmacy_id',$pharmacy_id)->first();
                return view('card_pharmacy.add',['payment_account'=>$payment_account,'success'=>$success,'error'=>$error,'title'=>'Cards','br1'=>'Cards','br2'=>'Card','br3'=>'Cards','alert'=>'']);
            } else {
                return abort(403, self::$err_perm);
            }
        } else {
            return abort(404, "Not found pharmacy");
        }
    }

    /**
     * @param int|string $pharmacy_id
     */
    public static function cardPharmacyAddHandlerOld($pharmacy_id,Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
        if(!empty($pharmacy)) {
            if(Auth::user()->can('manage-pharmacy', $pharmacy_id)) {
                $success='';
                $error='';
                if(!empty($request->input('ncard')) && !empty($request->input('name')) && !empty($request->input('expire')) && !empty($request->input('ccv')) && !empty($request->input('address')) && !empty($request->input('zip'))) {
                    $ncard=str_replace(' ','',$request->input('ncard'));
                    $name=explode(' ',$request->input('name'));
                    if(count($name)==1) {
                        $name[1]="";
                    }
                    $expire=explode('/',str_replace(' ','',$request->input('expire')));
                    $ccv=$request->input('ccv');
                    $zip=$request->input('zip');
                    $user_address = explode(',',$request->input('address'));
                    if(count($user_address)==1) {
                        $user_address[1]="";
                        $user_address[2]="";
                    }
                    if(count($user_address)==2) {
                        $user_address[2]="";
                    }
                    $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
                    $merchantAuthentication->setName(config('app.MERCHANT_LOGIN_ID'));
                    $merchantAuthentication->setTransactionKey(config('app.MERCHANT_TRANSACTION_KEY'));
                    // Set the transaction's refId
                    $refId = 'ref' . time();
                    // Set credit card information for payment profile
                    $payment_account = DB::table('payment_pharmacy_accounts')->where('pharmacy_id',$pharmacy_id)->first();
                    if(!empty($payment_account)) {
                        $request = new AnetAPI\UpdateCustomerPaymentProfileRequest();
                        $request->setMerchantAuthentication($merchantAuthentication);
                        $request->setCustomerProfileId($payment_account->profile_id);
                        $controller = new AnetController\GetCustomerProfileController($request);
                        $creditCard = new AnetAPI\CreditCardType();
                        $creditCard->setCardNumber($ncard);
                        $creditCard->setExpirationDate($expire[1].'-'.$expire[0]);
                        $creditCard->setCardCode($ccv);
                        $paymentCreditCard = new AnetAPI\PaymentType();
                        $paymentCreditCard->setCreditCard($creditCard);
                        $billTo = new AnetAPI\CustomerAddressType();
                        $billTo->setFirstName($name[0]);
                        $billTo->setLastName($name[1]);
                        $billTo->setCompany($pharmacy->name);
                        $billTo->setAddress($user_address[0]);
                        $billTo->setCity($user_address[1]);
                        $billTo->setState($user_address[2]);
                        $billTo->setZip($zip);
                        $billTo->setCountry("USA");
                        $billTo->setPhoneNumber($pharmacy->phone);
                        $billTo->setfaxNumber($pharmacy->phone);
                        $paymentprofile = new AnetAPI\CustomerPaymentProfileExType();
                        $paymentprofile->setCustomerPaymentProfileId($payment_account->payment_profile_id);
                        $paymentprofile->setBillTo($billTo);
                        $paymentprofile->setPayment($paymentCreditCard);	
                        // Submit a UpdatePaymentProfileRequest
                        $request->setPaymentProfile($paymentprofile);
                        $controller = new AnetController\UpdateCustomerPaymentProfileController($request);
                        /** @var AnetAPI\UpdateCustomerPaymentProfileResponse $response */
                        $response = $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::PRODUCTION);
                        if (($response != null) && ($response->getMessages()->getResultCode() == "Ok") )
                        {
                            $success= "New Card successfully added!";
                            DB::table('payment_pharmacy_accounts')->where('pharmacy_id',$pharmacy_id)->update(['card'=>$ncard]);
                        } else if ($response != null) {
                            $errorMessages = $response->getMessages()->getMessage();
                            $error=$errorMessages[0]->getText();
                        }
                    } else {
                        $creditCard = new AnetAPI\CreditCardType();
                        $creditCard->setCardNumber($ncard);
                        $creditCard->setExpirationDate($expire[1].'-'.$expire[0]);
                        $creditCard->setCardCode($ccv);
                        $paymentCreditCard = new AnetAPI\PaymentType();
                        $paymentCreditCard->setCreditCard($creditCard);
                        // Create the Bill To info for new payment type
                        $billTo = new AnetAPI\CustomerAddressType();
                        $billTo->setFirstName($name[0]);
                        $billTo->setLastName($name[1]);
                        $billTo->setCompany("QuikMedix");
                        $billTo->setAddress($user_address[0]);
                        $billTo->setCity($user_address[1]);
                        $billTo->setState($user_address[2]);
                        $billTo->setZip($zip);
                        $billTo->setCountry("USA");
                        $billTo->setPhoneNumber($pharmacy->phone);
                        $billTo->setfaxNumber($pharmacy->phone);
                        // Create a customer shipping address
                        $customerShippingAddress = new AnetAPI\CustomerAddressType();
                        $customerShippingAddress->setFirstName($name[0]);
                        $customerShippingAddress->setLastName($name[1]);
                        $customerShippingAddress->setCompany("QuikMedix");
                        $customerShippingAddress->setAddress($user_address[0]);
                        $customerShippingAddress->setCity($user_address[1]);
                        $customerShippingAddress->setState($user_address[2]);
                        $customerShippingAddress->setZip($zip);
                        $customerShippingAddress->setCountry("USA");
                        $customerShippingAddress->setPhoneNumber($pharmacy->phone);
                        $customerShippingAddress->setFaxNumber($pharmacy->phone);
                        // Create an array of any shipping addresses
                        $shippingProfiles[] = $customerShippingAddress;
                        // Create a new CustomerPaymentProfile object
                        $paymentProfile = new AnetAPI\CustomerPaymentProfileType();
                        $paymentProfile->setCustomerType('individual');
                        $paymentProfile->setBillTo($billTo);
                        $paymentProfile->setPayment($paymentCreditCard);
                        $paymentProfiles[] = $paymentProfile;
                        // Create a new CustomerProfileType and add the payment profile object
                        $customerProfile = new AnetAPI\CustomerProfileType();
                        $customerProfile->setDescription("Pharmacy ".$pharmacy->id);
                        $customerProfile->setMerchantCustomerId($pharmacy->id);
                        $customerProfile->setEmail($pharmacy->email);
                        $customerProfile->setpaymentProfiles($paymentProfiles);
                        $customerProfile->setShipToList($shippingProfiles);
                        // Assemble the complete transaction request
                        $request = new AnetAPI\CreateCustomerProfileRequest();
                        $request->setMerchantAuthentication($merchantAuthentication);
                        $request->setRefId($refId);
                        $request->setProfile($customerProfile);
                        // Create the controller and get the response
                        $controller = new AnetController\CreateCustomerProfileController($request);
                        /** @var AnetAPI\CreateCustomerProfileResponse $response */
                        $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::PRODUCTION);
                        if (($response != null) && ($response->getMessages()->getResultCode() == "Ok")) {
                            $paymentProfiles = $response->getCustomerPaymentProfileIdList();
                            DB::table('payment_pharmacy_accounts')->insert(['pharmacy_id'=>$pharmacy_id,'profile_id'=>$response->getCustomerProfileId(),'payment_profile_id'=>$paymentProfiles[0],'card'=>$ncard]);
                            $success="Card successfully added!";
                        } else {
                            $errorMessages = $response->getMessages()->getMessage();
                            $error=$errorMessages[0]->getText();
                        }
                    }
                } else {
                    $error='All card input is required.';
                }
                $payment_account = DB::table('payment_pharmacy_accounts')->where('pharmacy_id',$pharmacy_id)->first();
                return view('card_pharmacy.add',['payment_account'=>$payment_account,'success'=>$success,'error'=>$error,'title'=>'Cards','br1'=>'Cards','br2'=>'Card','br3'=>'Cards','alert'=>'']);
            } else {
                return abort(403, self::$err_perm);
            }
        } else {
            return abort(404, "Not found pharmacy");
        }
    }

    /**
     * @param int|string $pharmacy_id
     */
    public static function refillPharmacyBalance($pharmacy_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
        if(!empty($pharmacy)) {
            if(Auth::user()->can('manage-pharmacy', $pharmacy_id)) {
                $payment_account = DB::table('payment_pharmacy_accounts')->where('pharmacy_id',$pharmacy_id)->first();
                $success='';
                $error='';
                return view('card_pharmacy.pay',['payment_account'=>$payment_account,'pharmacy'=>$pharmacy,'success'=>$success,'error'=>$error,'title'=>'Cards','br1'=>'Cards','br2'=>'Card','br3'=>'Cards','alert'=>'']);
            } else {
                return abort(403, self::$err_perm);
            }
        } else {
            return abort(403, self::$err_perm);
        }
    }

    /**
     * @param int|string $pharmacy_id
     */
    public static function refillPharmacyBalanceHandler(Request $request, $pharmacy_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
        if(!empty($pharmacy)) {
            if(Auth::user()->can('manage-pharmacy', $pharmacy_id)) {
                $payment_account = DB::table('payment_pharmacy_accounts')->where('pharmacy_id',$pharmacy_id)->first();
                $success='';
                $error='';
                $amount = round(floatval($request->input('amount')),2);
                if(!empty($request->input('pay')) && !empty($payment_account) && $amount>0) {
                    $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
                    $merchantAuthentication->setName(config('app.MERCHANT_LOGIN_ID'));
                    $merchantAuthentication->setTransactionKey(config('app.MERCHANT_TRANSACTION_KEY'));
                    // Set the transaction's refId
                    $refId = 'ref' . time();
                    $profileToCharge = new AnetAPI\CustomerProfilePaymentType();
                    $profileToCharge->setCustomerProfileId($payment_account->profile_id);
                    $paymentProfile = new AnetAPI\PaymentProfileType();
                    $paymentProfile->setPaymentProfileId($payment_account->payment_profile_id);
                    $profileToCharge->setPaymentProfile($paymentProfile);
                    $transactionRequestType = new AnetAPI\TransactionRequestType();
                    $transactionRequestType->setTransactionType("authCaptureTransaction"); 
                    $transactionRequestType->setAmount($amount);
                    $transactionRequestType->setProfile($profileToCharge);
                    $authorizationIndicatorType = new AnetAPI\AuthorizationIndicatorType();
                    $authorizationIndicatorType->setAuthorizationIndicator("final");
                    $transactionRequestType->setAuthorizationIndicatorType($authorizationIndicatorType);
                    $request0 = new AnetAPI\CreateTransactionRequest();
                    $request0->setMerchantAuthentication($merchantAuthentication);
                    $request0->setRefId($refId);
                    $request0->setTransactionRequest($transactionRequestType);
                    $controller = new AnetController\CreateTransactionController($request0);
                    /** @var AnetAPI\CreateTransactionResponse $response */
                    $response = $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::PRODUCTION);
                    if ($response != null) {
                        if($response->getMessages()->getResultCode() == "Ok") {
                            $tresponse = $response->getTransactionResponse();
                            if ($tresponse != null && $tresponse->getMessages() != null) {
                                $balance = round(floatval($amount+floatval($pharmacy->balance)),2);
                                DB::table('pharmacys')->where('id',$pharmacy_id)->update(['balance'=>$balance]);
                                DB::table('pharmacy_payments')->insert(['pharmacy_id'=>$pharmacy_id,'amount'=>$amount,'transaction_id'=>$tresponse->getTransId(),'type'=>'refill']);
                                return response('<script>window.close();</script><a href="/billing/'.$pharmacy_id.'">Go Back</a> OR Close Window');
                            } else {
                                $error= "Transaction Failed: ";
                                if($tresponse->getErrors() != null) {
                                    $error .= $tresponse->getErrors()[0]->getErrorText();     
                                }
                            }
                        } else {
                            $error= "Transaction Failed: ";
                            $tresponse = $response->getTransactionResponse();
                            if($tresponse != null && $tresponse->getErrors() != null) {
                                $error .= $tresponse->getErrors()[0]->getErrorText();                      
                            } else {
                                $error .= $response->getMessages()->getMessage()[0]->getText();
                            }
                        }
                    } else {
                        $error= "No response returned.";
                    }
                }
                return view('card_pharmacy.pay',['payment_account'=>$payment_account,'pharmacy'=>$pharmacy,'success'=>$success,'error'=>$error,'title'=>'Billing','br1'=>'Billings','br2'=>'List']);
            } else {
                return abort(403, self::$err_perm);
            }
        } else {
            return abort(403, self::$err_perm);
        }
    }


    /**
     * @param int|string $user_id
     */
    public function chatUser($user_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        $user = DB::table('users')->where('id', $user_id)->first();
        $user_pharmacy = DB::table('pharmacys')->where('id', $user->pharmacy_id)->first();
        $user_pharmacy2 = DB::table('pharmacys')->where('id', Auth::user()->pharmacy_id)->first();
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
            //DB::table('notifications')->insert(['user_id'=>$user_id,'type'=>'primary','link'=>url('/').'chats/user/'.Auth::user()->id,'text'=>'User '.Auth::user()->name.' '.Auth::user()->last_name.$user_pharmacy2.' started a chat with you. Open correspondence.']);
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
        return view('chats.chat',['user'=>$user,'chat_name'=>$chat_name,'messages'=>$messages,'user_last_index'=>$user_last_index,'title'=>'Chat with '.$user->name.' '.$user->last_name.$user_pharmacy,'br1'=>'Chats','br2'=>'Chat']);
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
                Notifications::send_push($user2->id,"QuikMedix","You have a new incoming message from ".$user->name.' '.$user->last_name.$user_pharmacy);
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
                Notifications::send_push($user2->id,"QuikMedix","You have a new incoming message from ".$user->name.' '.$user->last_name.$user_pharmacy);
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

    public function chats() {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
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
            $user = DB::table('users')->where('id', $user_id)->first();
            $user_pharmacy = DB::table('pharmacys')->where('id', $user->pharmacy_id)->first();
            if(empty($user_pharmacy)) {
                $user_pharmacy='';
            } else {
                $user_pharmacy=' ('.$user_pharmacy->name.')';
            }
            array_push($chats,['link'=>"/chats/user/$user_id",'count_noread'=>$count_noread,'name'=>$user->name.' '.$user->last_name.$user_pharmacy,'image'=>$user->image,'last_message_date'=>$last_message_date,'last_message_body'=>$last_message_body]);
        }
        return view('chats.chats',['chats'=>$chats,'title'=>'Chats List','br1'=>'Chats','br2'=>'List']);
    }

    public function getUserInfo(Request $request) {
        $user = DB::table('users')->where('id', $request->input('identity'))->first();
        if(!empty($user->id)) {
            return json_encode([
                'name' => $user->name.' '.$user->last_name,
                'phone' => $user->phone,
                'image' => $user->image
            ]);
        } else {
            return json_encode([
                'message' => 'User not found',
                'errors' => 'Not Found'
            ]);
        }
    }

    public static function notifications() {
        $countOnPage=30;
        $max_pages=ceil(DB::table('notifications')->where('user_id', Auth::user()->id)->count()/$countOnPage);
        $page = self::currentPage();
        $pages = PageLinks::around($page, $max_pages);
        $notifications = DB::table('notifications')->where('user_id', Auth::user()->id)->orderBy('id','desc')->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
        DB::table('notifications')->whereIn('id',DB::table('notifications')->where('user_id', Auth::user()->id)->orderBy('id','desc')->pluck('id')->toArray())->update(['viewed' => 1]);
        Redis::del(request()->getHttpHost().':notification_count:'.Auth::user()->id);
        $res_view = view('notifications.list',['notifications'=>$notifications,'pages'=>$pages,'title'=>'Notifications','br1'=>'Notifications','br2'=>'List']);
        return self::renderPage($res_view);
    }

    public static function driversQr(Request $request) {
        if(!empty($request->input('code'))) {
            $token = DB::table('tokens_driver')->where('token',$request->input('code'))->where('created', '>', DB::raw("NOW() - INTERVAL 15 MINUTE"))->first();
            if(isset($token)) {
                return json_encode([
                    'user_id' => $token->user_id,
                ]);
            } else {
                return json_encode([
                    'message' => 'Driver with such a token was not found, or the token has expired',
                    'errors' => 'Not Found'
                ]);
            }
        } else {
            return json_encode([
                'message' => 'Driver with such a token was not found, or the token has expired',
                'errors' => 'Not Found'
            ]);
        }
    }

    public static function driversQrOrder(Request $request) {
        if(!empty($request->input('code')) || !empty($request->input('count')) || !empty($request->input('driver_id'))) {
            $driver_id = $request->input('driver_id');
            $count = 1;
            $code = explode("_",$request->input('code'));
            $order_id = $code[0];
            if(isset($code[1])) {
                $bag = $code[1];
            } else {
                $bag = 1;
            }
            if((Auth::user()->can('admin'))) {
                $order = DB::table('orders')->where('id',$order_id)->where('driver_id', $driver_id)->whereIn('statuse_id', [1,2,3,5,6,7,8,9])->first();
                if(isset($order)) {
                    $count_added = DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('driver_id', $driver_id)->where('office_id',Auth::user()->office_id)->where('target','in')->count();
                    $count_gived = DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('driver_id', $driver_id)->where('office_id',Auth::user()->office_id)->where('target','out')->count();
                    $order_transfer = DB::table('orders')->where('driver_id', $driver_id)->whereIn('statuse_id', [7])->where('id',$order->id)->first();
                    if(!empty($order_transfer)) {
                        if($count+$count_gived>=$order->count_bags) {
                            if(DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('office_id',Auth::user()->office_id)->where('driver_id', $driver_id)->where('target','out')->where('bag',$bag)->count()>0) {
                                return json_encode([
                                    'message' => 'This bag has already been scanned, please scan the next bag for this order.',
                                    'errors' => 'Not Found'
                                ]);
                            } else {
                                DB::table('packages_transitions')->insert(['order_id'=>$order->id,'office_id'=>Auth::user()->office_id,'driver_id'=>$order->driver_id,'target'=>'out','bag'=>$bag]);
                                DB::table('orders')->where('id',$order_id)->update(['statuse_id'=>3]);
                                $route = DB::table('routes_priority')->where('driver_id', $driver_id)->where('type','office')->where('order_id',$order->id);
                                foreach($route->get() as $routes_priority) {
                                    DB::table('routes_priority_logs')->insert(["id"=>$routes_priority->id,"driver_id"=>$routes_priority->driver_id,"order_id"=>$routes_priority->order_id,"type"=>$routes_priority->type,"type_id"=>$routes_priority->type_id,"type_pay"=>$routes_priority->type_pay,"pay_value"=>$routes_priority->pay_value]);
                                    if($routes_priority->type_pay==2) {
                                        DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                                    } else if($routes_priority->type_pay==3) {
                                        DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value*intval($order->count_bags)]);
                                    } else {
                                        DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                                    }
                                }
                                $route->delete();
                                if(empty($order->eta) || $order->eta>60) {
                                    //Notifications::send_push($order->user_id,"QuikMedix","Your order #$order_id will be delivered by 1 p.m. \nFor more information about your order (medication) please contact ".\App\Support\Branding::supportContact()." \nBest regards, QuikMedix");
                                } else {
                                    //Notifications::send_push($order->user_id,"QuikMedix","Your order #$order_id will be delivered from 1 p.m. until 9 p.m. \nFor more information about your order (medication) please contact ".\App\Support\Branding::supportContact()." \nBest regards, QuikMedix");
                                }
                                $next_route = DB::table('routes_priority')->where('driver_id',$driver_id)->orderBy("priority","asc")->first();
                                if(!empty($next_route)) {
                                    if($next_route->type=='patient') {
                                        self::next_patient_push($driver_id,$next_route);
                                    }
                                }
                                return json_encode([
                                    'order_id' => $order->id,
                                ]);
                            }
                        } else if($count+$count_gived<$order->count_bags) {
                            if(DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('office_id',Auth::user()->office_id)->where('driver_id', $driver_id)->where('target','out')->where('bag',$bag)->count()>0) {
                                return json_encode([
                                    'message' => 'This bag has already been scanned, please scan the next bag for this order.',
                                    'errors' => 'Not Found'
                                ]);
                            } else {
                                DB::table('packages_transitions')->insert(['order_id'=>$order->id,'office_id'=>Auth::user()->office_id,'driver_id'=>$order->driver_id,'target'=>'out','bag'=>$bag]);
                                return json_encode([
                                    'bag_added' => $order->id,
                                ]);
                            }
                        } else {
                            return json_encode([
                                'message' => 'The indicated number of bags does not match the number of bags in the order, check the quantity or change it in the order settings.',
                                'errors' => 'Not Found'
                            ]);
                        }
                    } else {
                        if($count+$count_added>=$order->count_bags) {
                            DB::table('packages_transitions')->insert(['order_id'=>$order->id,'office_id'=>Auth::user()->office_id,'driver_id'=>$order->driver_id,'target'=>'in','bag'=>$bag]);
                            DB::table('orders')->where('id',$order_id)->update(['statuse_id'=>7,'driver_id'=>null]);
                            $route = DB::table('routes_priority')->where('driver_id', $driver_id)->where('order_id',$order->id)->where('type','office');
                            foreach($route->get() as $routes_priority) {
                                DB::table('routes_priority_logs')->insert(["id"=>$routes_priority->id,"driver_id"=>$routes_priority->driver_id,"order_id"=>$routes_priority->order_id,"type"=>$routes_priority->type,"type_id"=>$routes_priority->type_id,"type_pay"=>$routes_priority->type_pay,"pay_value"=>$routes_priority->pay_value]);
                                if($routes_priority->type_pay==2) {
                                    DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                                } else if($routes_priority->type_pay==3) {
                                    DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value*intval($order->count_bags)]);
                                } else {
                                    DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                                }
                            }
                            $route->delete();
                            $next_route = DB::table('routes_priority')->where('driver_id',$driver_id)->orderBy("priority","asc")->first();
                            if(!empty($next_route)) {
                                if($next_route->type=='patient') {
                                    self::next_patient_push($driver_id,$next_route);
                                }
                            }
                            return json_encode([
                                'order_id_in' => $order->id,
                            ]);
                        } else if($count+$count_added<$order->count_bags) {
                            if(DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('office_id',Auth::user()->office_id)->where('driver_id', $driver_id)->where('target','in')->where('bag',$bag)->count()>0) {
                                return json_encode([
                                    'message' => 'This bag has already been scanned, please scan the next bag for this order.',
                                    'errors' => 'Not Found'
                                ]);
                            } else {
                                DB::table('packages_transitions')->insert(['order_id'=>$order->id,'office_id'=>Auth::user()->office_id,'driver_id'=>$order->driver_id,'target'=>'in','bag'=>$bag]);
                                return json_encode([
                                    'bag_added_in' => $order->id,
                                ]);
                            }
                        } else {
                            return json_encode([
                                'message' => 'The indicated number of bags does not match the number of bags in the order, check the quantity or change it in the order settings.',
                                'errors' => 'Not Found'
                            ]);
                        }
                    }
                } else {
                    return json_encode([
                        'message' => 'Order with such a QR code was not found.',
                        'errors' => 'Not Found'
                    ]);
                }
            } else {
                $order = DB::table('orders')->where('id',$order_id)->where('driver_id', $driver_id)->where('pharmacy_id', Auth::user()->pharmacy_id)->whereIn('statuse_id', [1,2,3,5,6,7,8,9])->first();
                if(isset($order)) {
                    $count_added = DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('pharmacy_id', Auth::user()->pharmacy_id)->where('driver_id', $driver_id)->where('target','in')->count();
                    $count_gived = DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('pharmacy_id', Auth::user()->pharmacy_id)->where('driver_id', $driver_id)->where('target','out')->count();
                    $order_transfer = DB::table('orders')->where('driver_id', $driver_id)->where('pharmacy_id', Auth::user()->pharmacy_id)->whereIn('statuse_id', [1,2])->where('id', $order->id)->first();
                    if(!empty($order_transfer)) {
                        if($count+$count_gived>=$order->count_bags) {
                            if(DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('pharmacy_id',Auth::user()->pharmacy_id)->where('driver_id', $driver_id)->where('target','out')->where('bag',$bag)->count()>0) {
                                return json_encode([
                                    'message' => 'This bag has already been scanned, please scan the next bag for this order.',
                                    'errors' => 'Not Found'
                                ]);
                            } else {
                                DB::table('packages_transitions')->insert(['order_id'=>$order->id,'pharmacy_id'=>Auth::user()->pharmacy_id,'driver_id'=>$order->driver_id,'target'=>'out','bag'=>$bag]);
                                DB::table('orders')->where('id',$order_id)->update(['statuse_id'=>3]);
                                $route = DB::table('routes_priority')->where('driver_id', $driver_id)->where('type','pharmacy')->where('type_id',Auth::user()->pharmacy_id)->where('order_id',$order->id);
                                foreach($route->get() as $routes_priority) {
                                    DB::table('routes_priority_logs')->insert(["id"=>$routes_priority->id,"driver_id"=>$routes_priority->driver_id,"order_id"=>$routes_priority->order_id,"type"=>$routes_priority->type,"type_id"=>$routes_priority->type_id,"type_pay"=>$routes_priority->type_pay,"pay_value"=>$routes_priority->pay_value]);
                                    if($routes_priority->type_pay==2) {
                                        DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                                    } else if($routes_priority->type_pay==3) {
                                        DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value*intval($order->count_bags)]);
                                    } else {
                                        DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                                    }
                                }
                                $route->delete();
                                Notifications::send_push($order->user_id,"QuikMedix","Your order #$order_id has been confirmed. \nFor more information about your order (medication) please contact ".\App\Support\Branding::supportContact()." \nBest regards, QuikMedix");
                                $next_route = DB::table('routes_priority')->where('driver_id',$driver_id)->orderBy("priority","asc")->first();
                                if(!empty($next_route)) {
                                    if($next_route->type=='patient') {
                                        self::next_patient_push($driver_id,$next_route);
                                    }
                                }
                                return json_encode([
                                    'order_id' => $order->id,
                                ]);
                            }
                        } else if($count+$count_gived<$order->count_bags) {
                            if(DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('pharmacy_id',Auth::user()->pharmacy_id)->where('driver_id', $driver_id)->where('target','out')->where('bag',$bag)->count()>0) {
                                return json_encode([
                                    'message' => 'This bag has already been scanned, please scan the next bag for this order.',
                                    'errors' => 'Not Found'
                                ]);
                            } else {
                                DB::table('packages_transitions')->insert(['order_id'=>$order->id,'pharmacy_id'=>Auth::user()->pharmacy_id,'driver_id'=>$order->driver_id,'target'=>'out','bag'=>$bag]);
                                return json_encode([
                                    'bag_added' => $order->id,
                                ]);
                            }
                        } else {
                            return json_encode([
                                'message' => 'The indicated number of bags does not match the number of bags in the order, check the quantity or change it in the order settings.',
                                'errors' => 'Not Found'
                            ]);
                        }
                    } else {
                        if($count+$count_added>=$order->count_bags) {
                            DB::table('packages_transitions')->insert(['order_id'=>$order->id,'pharmacy_id'=>Auth::user()->pharmacy_id,'driver_id'=>$order->driver_id,'target'=>'in','bag'=>$bag]);
                            DB::table('orders')->where('id',$order_id)->update(['statuse_id'=>10,'driver_id'=>null]);
                            $pharmacy=DB::table('pharmacys')->where('pharmacys.id',$order->pharmacy_id)->first();
                            $pharmacy_plan=DB::table('plans')->where('plans.id',$pharmacy->plan_id)->first();
                            $patient=DB::table('users')->where('users.id',$order->user_id)->first();
                            $pharmacy_areas=DB::table('pharmacy_areas')->where('pharmacy_id',$order->pharmacy_id)->where('type',1)->pluck('area_id')->toArray();
                            $pharmacy_areas2=DB::table('pharmacy_areas')->where('pharmacy_id',$order->pharmacy_id)->where('type',2)->pluck('area_id')->toArray();
                            $pharmacy_areas3=DB::table('pharmacy_areas')->where('pharmacy_id',$order->pharmacy_id)->where('type',3)->pluck('area_id')->toArray();
                            $zip_tariff=DB::table('area')->whereIn('area.id',$pharmacy_areas)->whereRaw('ST_CONTAINS(polygon, POINT(?, ?))', \App\Support\GeoPoint::bindings($patient->location))->select("area.id")->first();
                            $zip_tariff2=DB::table('area')->whereIn('area.id',$pharmacy_areas2)->whereRaw('ST_CONTAINS(polygon, POINT(?, ?))', \App\Support\GeoPoint::bindings($patient->location))->select("area.id")->first();
                            $zip_tariff3=DB::table('area')->whereIn('area.id',$pharmacy_areas3)->whereRaw('ST_CONTAINS(polygon, POINT(?, ?))', \App\Support\GeoPoint::bindings($patient->location))->select("area.id")->first();
                            if(!empty($zip_tariff)){
                                if(is_numeric($pharmacy->tariff)) {
                                    $tariff = $pharmacy->tariff;
                                } else {
                                    $tariff = $pharmacy_plan->tariff;
                                }
                            } else if(!empty($zip_tariff2)){
                                if(is_numeric($pharmacy->tariff_area2)) {
                                    $tariff = $pharmacy->tariff_area2;
                                } else {
                                    $tariff = $pharmacy_plan->tariff_area2;
                                }
                            } else if(!empty($zip_tariff3)){
                                if(is_numeric($pharmacy->tariff_area3)) {
                                    $tariff = $pharmacy->tariff_area3;
                                } else {
                                    $tariff = $pharmacy_plan->tariff_area3;
                                }
                            } else {
                                if(is_numeric($pharmacy->tariff_area_more)) {
                                    $tariff = $pharmacy->tariff_area_more;
                                } else {
                                    $tariff = $pharmacy_plan->tariff_area_more;
                                }
                            }
                            if(is_numeric($pharmacy->tariff_next_day)) {
                                $tariff_next_day = $pharmacy->tariff_next_day;
                            } else {
                                $tariff_next_day = $pharmacy_plan->tariff_next_day;
                            }
                            if(is_numeric($pharmacy->tariff_same_day)) {
                                $tariff_same_day = $pharmacy->tariff_same_day;
                            } else {
                                $tariff_same_day = $pharmacy_plan->tariff_same_day;
                            }
                            if(is_numeric($pharmacy->tariff_asap)) {
                                $tariff_asap = $pharmacy->tariff_asap;
                            } else {
                                $tariff_asap = $pharmacy_plan->tariff_asap;
                            }
                            if(is_numeric($pharmacy->tariff_after_hours)) {
                                $tariff_after_hours = $pharmacy->tariff_after_hours;
                            } else {
                                $tariff_after_hours = $pharmacy_plan->tariff_after_hours;
                            }
                            if(is_numeric($pharmacy->tariff_fridge)) {
                                $tariff_fridge = $pharmacy->tariff_fridge;
                            } else {
                                $tariff_fridge = $pharmacy_plan->tariff_fridge;
                            }
                            if($order->type_driver==1) {
                                if($order->delivery_time_id==1) {
                                    $tariff_res = (floatval($tariff)+floatval($tariff_next_day)+floatval($order->extra_charge_driver));
                                } elseif($order->delivery_time_id==2) {
                                    $tariff_res = (floatval($tariff)+floatval($tariff_same_day)+floatval($order->extra_charge_driver));
                                } elseif($order->delivery_time_id==3) {
                                    $tariff_res = (floatval($tariff)+floatval($tariff_asap)+floatval($order->extra_charge_driver));
                                } elseif($order->delivery_time_id==4) {
                                    $tariff_res = (floatval($tariff)+floatval($tariff_after_hours)+floatval($order->extra_charge_driver));
                                } else {
                                    throw new \UnexpectedValueException("Unknown delivery time {$order->delivery_time_id} for order {$order->id}");
                                }
                                if($order->fridge==1) {
                                    $tariff_res+= floatval($tariff_fridge);
                                }
                            } else {
                                $tariff_res = floatval($tariff);
                            }
                            DB::table('orders')->where('orders.id',$order_id)->update(['finish'=>date('Y-m-d H:i:s'),'tariff'=>$tariff_res]);
                            $route = DB::table('routes_priority')->where('driver_id', $driver_id)->where('order_id',$order->id)->where('type','pharmacy')->where('type_id',Auth::user()->pharmacy_id);
                            foreach($route->get() as $routes_priority) {
                                DB::table('routes_priority_logs')->insert(["id"=>$routes_priority->id,"driver_id"=>$routes_priority->driver_id,"order_id"=>$routes_priority->order_id,"type"=>$routes_priority->type,"type_id"=>$routes_priority->type_id,"type_pay"=>$routes_priority->type_pay,"pay_value"=>$routes_priority->pay_value]);
                                if($routes_priority->type_pay==2) {
                                    DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                                } else if($routes_priority->type_pay==3) {
                                    DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value*intval($order->count_bags)]);
                                } else {
                                    DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                                }
                            }
                            $route->delete();
                            $next_route = DB::table('routes_priority')->where('driver_id',$driver_id)->orderBy("priority","asc")->first();
                            if(!empty($next_route)) {
                                if($next_route->type=='patient') {
                                    self::next_patient_push($driver_id,$next_route);
                                }
                            }
                            return json_encode([
                                'order_id_in' => $order->id,
                            ]);
                        } else if($count+$count_added<$order->count_bags) {
                            if(DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('pharmacy_id', Auth::user()->pharmacy_id)->where('driver_id', $driver_id)->where('target','in')->where('bag',$bag)->count()>0) {
                                return json_encode([
                                    'message' => 'This bag has already been scanned, please scan the next bag for this order.',
                                    'errors' => 'Not Found'
                                ]);
                            } else {
                                DB::table('packages_transitions')->insert(['order_id'=>$order->id,'pharmacy_id'=>Auth::user()->pharmacy_id,'driver_id'=>$order->driver_id,'target'=>'in','bag'=>$bag]);
                                return json_encode([
                                    'bag_added_in' => $order->id,
                                ]);
                            }
                        } else {
                            return json_encode([
                                'message' => 'The indicated number of bags does not match the number of bags in the order, check the quantity or change it in the order settings.',
                                'errors' => 'Not Found'
                            ]);
                        }
                    }
                } else {
                    return json_encode([
                        'message' => 'Order with such a QR code was not found.',
                        'errors' => 'Not Found'
                    ]);
                }
            }
        } else {
            return json_encode([
                'message' => 'QR code or Count Bags Order are not filled.',
                'errors' => 'Not Found'
            ]);
        }
    }

    /**
     * @param int|string $order_id
     * @param int|string $bag
     * @param int|string $driver_id
     */
    public function driversBagsOrder($order_id, $bag, $driver_id, $count=1) {
        if((Auth::user()->can('admin'))) {
            $order = DB::table('orders')->where('id',$order_id)->where('driver_id', $driver_id)->whereIn('statuse_id', [1,2,3,5,6,7,8,9])->first();
            if(isset($order)) {
                $count_added = DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('driver_id', $driver_id)->where('office_id',Auth::user()->office_id)->where('target','in')->count();
                $count_gived = DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('driver_id', $driver_id)->where('office_id',Auth::user()->office_id)->where('target','out')->count();
                $order_transfer = DB::table('orders')->where('driver_id', $driver_id)->whereIn('statuse_id', [7])->where('id',$order->id)->first();
                if(!empty($order_transfer)) {
                    if($count+$count_gived>=$order->count_bags) {
                        if(DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('driver_id', $driver_id)->where('order_id',$order->id)->where('office_id',Auth::user()->office_id)->where('target','out')->where('bag',$bag)->count()>0) {
                            return json_encode([
                                'message' => 'This bag has already been scanned, please scan the next bag for this order.',
                                'errors' => 'Not Found'
                            ]);
                        } else {
                            DB::table('packages_transitions')->insert(['order_id'=>$order->id,'office_id'=>Auth::user()->office_id,'driver_id'=>$order->driver_id,'target'=>'out','bag'=>$bag]);
                            DB::table('orders')->where('id',$order_id)->update(['statuse_id'=>3]);
                            $route = DB::table('routes_priority')->where('driver_id', $driver_id)->where('type','office')->where('order_id',$order->id);
                            foreach($route->get() as $routes_priority) {
                                DB::table('routes_priority_logs')->insert(["id"=>$routes_priority->id,"driver_id"=>$routes_priority->driver_id,"order_id"=>$routes_priority->order_id,"type"=>$routes_priority->type,"type_id"=>$routes_priority->type_id,"type_pay"=>$routes_priority->type_pay,"pay_value"=>$routes_priority->pay_value]);
                                if($routes_priority->type_pay==2) {
                                    DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                                } else if($routes_priority->type_pay==3) {
                                    DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value*intval($order->count_bags)]);
                                } else {
                                    DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                                }
                            }
                            $route->delete();
                            $next_route = DB::table('routes_priority')->where('driver_id',$driver_id)->orderBy("priority","asc")->first();
                            if(!empty($next_route)) {
                                if($next_route->type=='patient') {
                                    self::next_patient_push($driver_id,$next_route);
                                }
                            }
                            return json_encode([
                                'order_id' => $order->id,
                            ]);
                        }
                    } else if($count+$count_gived<$order->count_bags) {
                        if(DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('office_id',Auth::user()->office_id)->where('driver_id', $driver_id)->where('target','out')->where('bag',$bag)->count()>0) {
                            return json_encode([
                                'message' => 'This bag has already been scanned, please scan the next bag for this order.',
                                'errors' => 'Not Found'
                            ]);
                        } else {
                            DB::table('packages_transitions')->insert(['order_id'=>$order->id,'office_id'=>Auth::user()->office_id,'driver_id'=>$order->driver_id,'target'=>'out','bag'=>$bag]);
                            return json_encode([
                                'bag_added' => $order->id,
                            ]);
                        }
                    } else {
                        return json_encode([
                            'message' => 'The indicated number of bags does not match the number of bags in the order, check the quantity or change it in the order settings.',
                            'errors' => 'Not Found'
                        ]);
                    }
                } else {
                    if($count+$count_added>=$order->count_bags) {
                        DB::table('packages_transitions')->insert(['order_id'=>$order->id,'office_id'=>Auth::user()->office_id,'driver_id'=>$order->driver_id,'target'=>'in','bag'=>$bag]);
                        DB::table('orders')->where('id',$order_id)->update(['statuse_id'=>7,'driver_id'=>null]);
                        $route = DB::table('routes_priority')->where('driver_id', $driver_id)->where('order_id',$order->id)->where('type','office');
                        foreach($route->get() as $routes_priority) {
                            DB::table('routes_priority_logs')->insert(["id"=>$routes_priority->id,"driver_id"=>$routes_priority->driver_id,"order_id"=>$routes_priority->order_id,"type"=>$routes_priority->type,"type_id"=>$routes_priority->type_id,"type_pay"=>$routes_priority->type_pay,"pay_value"=>$routes_priority->pay_value]);
                            if($routes_priority->type_pay==2) {
                                DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                            } else if($routes_priority->type_pay==3) {
                                DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value*intval($order->count_bags)]);
                            } else {
                                DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                            }
                        }
                        $route->delete();
                        $next_route = DB::table('routes_priority')->where('driver_id',$driver_id)->orderBy("priority","asc")->first();
                        if(!empty($next_route)) {
                            if($next_route->type=='patient') {
                                self::next_patient_push($driver_id,$next_route);
                            }
                        }
                        return json_encode([
                            'order_id_in' => $order->id,
                        ]);
                    } else if($count+$count_added<$order->count_bags) {
                        if(DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('office_id',Auth::user()->office_id)->where('driver_id', $driver_id)->where('target','in')->where('bag',$bag)->count()>0) {
                            return json_encode([
                                'message' => 'This bag has already been scanned, please scan the next bag for this order.',
                                'errors' => 'Not Found'
                            ]);
                        } else {
                            DB::table('packages_transitions')->insert(['order_id'=>$order->id,'office_id'=>Auth::user()->office_id,'driver_id'=>$order->driver_id,'target'=>'in','bag'=>$bag]);
                            return json_encode([
                                'bag_added_in' => $order->id,
                            ]);
                        }
                    } else {
                        return json_encode([
                            'message' => 'The indicated number of bags does not match the number of bags in the order, check the quantity or change it in the order settings.',
                            'errors' => 'Not Found'
                        ]);
                    }
                }
            } else {
                return json_encode([
                    'message' => 'Order with such a QR code was not found.',
                    'errors' => 'Not Found'
                ]);
            }
        } else {
            $order = DB::table('orders')->where('id',$order_id)->where('driver_id', $driver_id)->where('pharmacy_id', Auth::user()->pharmacy_id)->whereIn('statuse_id', [1,2,3,5,6,7,8,9])->first();
            if(isset($order)) {
                $count_added = DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('pharmacy_id', Auth::user()->pharmacy_id)->where('driver_id', $driver_id)->where('target','in')->count();
                $count_gived = DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('pharmacy_id', Auth::user()->pharmacy_id)->where('driver_id', $driver_id)->where('target','out')->count();
                $order_transfer = DB::table('orders')->where('driver_id', $driver_id)->where('pharmacy_id', Auth::user()->pharmacy_id)->whereIn('statuse_id', [1,2])->where('id', $order->id)->first();
                if(!empty($order_transfer)) {
                    if($count+$count_gived>=$order->count_bags) {
                        if(DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('pharmacy_id',Auth::user()->pharmacy_id)->where('driver_id', $driver_id)->where('target','out')->where('bag',$bag)->count()>0) {
                            return json_encode([
                                'message' => 'This bag has already been scanned, please scan the next bag for this order.',
                                'errors' => 'Not Found'
                            ]);
                        } else {
                            DB::table('packages_transitions')->insert(['order_id'=>$order->id,'pharmacy_id'=>Auth::user()->pharmacy_id,'driver_id'=>$order->driver_id,'target'=>'out','bag'=>$bag]);
                            DB::table('orders')->where('id',$order_id)->update(['statuse_id'=>3]);
                            $route = DB::table('routes_priority')->where('driver_id', $driver_id)->where('type','pharmacy')->where('type_id',Auth::user()->pharmacy_id)->where('order_id',$order->id);
                            foreach($route->get() as $routes_priority) {
                                DB::table('routes_priority_logs')->insert(["id"=>$routes_priority->id,"driver_id"=>$routes_priority->driver_id,"order_id"=>$routes_priority->order_id,"type"=>$routes_priority->type,"type_id"=>$routes_priority->type_id,"type_pay"=>$routes_priority->type_pay,"pay_value"=>$routes_priority->pay_value]);
                                if($routes_priority->type_pay==2) {
                                    DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                                } else if($routes_priority->type_pay==3) {
                                    DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value*intval($order->count_bags)]);
                                } else {
                                    DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                                }
                            }
                            $route->delete();
                            Notifications::send_push($order->user_id,"QuikMedix","Your order #$order_id has been confirmed. \nFor more information about your order (medication) please contact ".\App\Support\Branding::supportContact()." \nBest regards, QuikMedix");
                            $next_route = DB::table('routes_priority')->where('driver_id',$driver_id)->orderBy("priority","asc")->first();
                            if(!empty($next_route)) {
                                if($next_route->type=='patient') {
                                    self::next_patient_push($driver_id,$next_route);
                                }
                            }
                            return json_encode([
                                'order_id' => $order->id,
                            ]);
                        }
                    } else if($count+$count_gived<$order->count_bags) {
                        if(DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('pharmacy_id',Auth::user()->pharmacy_id)->where('driver_id', $driver_id)->where('target','out')->where('bag',$bag)->count()>0) {
                            return json_encode([
                                'message' => 'This bag has already been scanned, please scan the next bag for this order.',
                                'errors' => 'Not Found'
                            ]);
                        } else {
                            DB::table('packages_transitions')->insert(['order_id'=>$order->id,'pharmacy_id'=>Auth::user()->pharmacy_id,'driver_id'=>$order->driver_id,'target'=>'out','bag'=>$bag]);
                            return json_encode([
                                'bag_added' => $order->id,
                            ]);
                        }
                    } else {
                        return json_encode([
                            'message' => 'The indicated number of bags does not match the number of bags in the order, check the quantity or change it in the order settings.',
                            'errors' => 'Not Found'
                        ]);
                    }
                } else {
                    if($count+$count_added>=$order->count_bags) {
                        DB::table('packages_transitions')->insert(['order_id'=>$order->id,'pharmacy_id'=>Auth::user()->pharmacy_id,'driver_id'=>$order->driver_id,'target'=>'in','bag'=>$bag]);
                        DB::table('orders')->where('id',$order_id)->update(['statuse_id'=>10,'driver_id'=>null]);
                        $pharmacy=DB::table('pharmacys')->where('pharmacys.id',$order->pharmacy_id)->first();
                        $pharmacy_plan=DB::table('plans')->where('plans.id',$pharmacy->plan_id)->first();
                        $patient=DB::table('users')->where('users.id',$order->user_id)->first();
                        $pharmacy_areas=DB::table('pharmacy_areas')->where('pharmacy_id',$order->pharmacy_id)->where('type',1)->pluck('area_id')->toArray();
                        $pharmacy_areas2=DB::table('pharmacy_areas')->where('pharmacy_id',$order->pharmacy_id)->where('type',2)->pluck('area_id')->toArray();
                        $pharmacy_areas3=DB::table('pharmacy_areas')->where('pharmacy_id',$order->pharmacy_id)->where('type',3)->pluck('area_id')->toArray();
                        $zip_tariff=DB::table('area')->whereIn('area.id',$pharmacy_areas)->whereRaw('ST_CONTAINS(polygon, POINT(?, ?))', \App\Support\GeoPoint::bindings($patient->location))->select("area.id")->first();
                        $zip_tariff2=DB::table('area')->whereIn('area.id',$pharmacy_areas2)->whereRaw('ST_CONTAINS(polygon, POINT(?, ?))', \App\Support\GeoPoint::bindings($patient->location))->select("area.id")->first();
                        $zip_tariff3=DB::table('area')->whereIn('area.id',$pharmacy_areas3)->whereRaw('ST_CONTAINS(polygon, POINT(?, ?))', \App\Support\GeoPoint::bindings($patient->location))->select("area.id")->first();
                        if(!empty($zip_tariff)){
                            if(is_numeric($pharmacy->tariff)) {
                                $tariff = $pharmacy->tariff;
                            } else {
                                $tariff = $pharmacy_plan->tariff;
                            }
                        } else if(!empty($zip_tariff2)){
                            if(is_numeric($pharmacy->tariff_area2)) {
                                $tariff = $pharmacy->tariff_area2;
                            } else {
                                $tariff = $pharmacy_plan->tariff_area2;
                            }
                        } else if(!empty($zip_tariff3)){
                            if(is_numeric($pharmacy->tariff_area3)) {
                                $tariff = $pharmacy->tariff_area3;
                            } else {
                                $tariff = $pharmacy_plan->tariff_area3;
                            }
                        } else {
                            if(is_numeric($pharmacy->tariff_area_more)) {
                                $tariff = $pharmacy->tariff_area_more;
                            } else {
                                $tariff = $pharmacy_plan->tariff_area_more;
                            }
                        }
                        if(is_numeric($pharmacy->tariff_next_day)) {
                            $tariff_next_day = $pharmacy->tariff_next_day;
                        } else {
                            $tariff_next_day = $pharmacy_plan->tariff_next_day;
                        }
                        if(is_numeric($pharmacy->tariff_same_day)) {
                            $tariff_same_day = $pharmacy->tariff_same_day;
                        } else {
                            $tariff_same_day = $pharmacy_plan->tariff_same_day;
                        }
                        if(is_numeric($pharmacy->tariff_asap)) {
                            $tariff_asap = $pharmacy->tariff_asap;
                        } else {
                            $tariff_asap = $pharmacy_plan->tariff_asap;
                        }
                        if(is_numeric($pharmacy->tariff_after_hours)) {
                            $tariff_after_hours = $pharmacy->tariff_after_hours;
                        } else {
                            $tariff_after_hours = $pharmacy_plan->tariff_after_hours;
                        }
                        if(is_numeric($pharmacy->tariff_fridge)) {
                            $tariff_fridge = $pharmacy->tariff_fridge;
                        } else {
                            $tariff_fridge = $pharmacy_plan->tariff_fridge;
                        }
                        if($order->type_driver==1) {
                            if($order->delivery_time_id==1) {
                                $tariff_res = (floatval($tariff)+floatval($tariff_next_day)+floatval($order->extra_charge_driver));
                            } elseif($order->delivery_time_id==2) {
                                $tariff_res = (floatval($tariff)+floatval($tariff_same_day)+floatval($order->extra_charge_driver));
                            } elseif($order->delivery_time_id==3) {
                                $tariff_res = (floatval($tariff)+floatval($tariff_asap)+floatval($order->extra_charge_driver));
                            } elseif($order->delivery_time_id==4) {
                                $tariff_res = (floatval($tariff)+floatval($tariff_after_hours)+floatval($order->extra_charge_driver));
                            } else {
                                throw new \UnexpectedValueException("Unknown delivery time {$order->delivery_time_id} for order {$order->id}");
                            }
                            if($order->fridge==1) {
                                $tariff_res+= floatval($tariff_fridge);
                            }
                        } else {
                            $tariff_res = floatval($tariff);
                        }
                        DB::table('orders')->where('orders.id',$order_id)->update(['finish'=>date('Y-m-d H:i:s'),'tariff'=>$tariff_res]);
                        $route = DB::table('routes_priority')->where('driver_id', $driver_id)->where('order_id',$order->id)->where('type','pharmacy')->where('type_id',Auth::user()->pharmacy_id);
                        foreach($route->get() as $routes_priority) {
                            DB::table('routes_priority_logs')->insert(["id"=>$routes_priority->id,"driver_id"=>$routes_priority->driver_id,"order_id"=>$routes_priority->order_id,"type"=>$routes_priority->type,"type_id"=>$routes_priority->type_id,"type_pay"=>$routes_priority->type_pay,"pay_value"=>$routes_priority->pay_value]);
                            if($routes_priority->type_pay==2) {
                                DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                            } else if($routes_priority->type_pay==3) {
                                DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value*intval($order->count_bags)]);
                            } else {
                                DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                            }
                        }
                        $route->delete();
                        $next_route = DB::table('routes_priority')->where('driver_id',$driver_id)->orderBy("priority","asc")->first();
                        if(!empty($next_route)) {
                            if($next_route->type=='patient') {
                                self::next_patient_push($driver_id,$next_route);
                            }
                        }
                        return json_encode([
                            'order_id_in' => $order->id,
                        ]);
                    } else if($count+$count_added<$order->count_bags) {
                        if(DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('pharmacy_id', Auth::user()->pharmacy_id)->where('driver_id', $driver_id)->where('target','in')->where('bag',$bag)->count()>0) {
                            return json_encode([
                                'message' => 'This bag has already been scanned, please scan the next bag for this order.',
                                'errors' => 'Not Found'
                            ]);
                        } else {
                            DB::table('packages_transitions')->insert(['order_id'=>$order->id,'pharmacy_id'=>Auth::user()->pharmacy_id,'driver_id'=>$order->driver_id,'target'=>'in','bag'=>$bag]);
                            return json_encode([
                                'bag_added_in' => $order->id,
                            ]);
                        }
                    } else {
                        return json_encode([
                            'message' => 'The indicated number of bags does not match the number of bags in the order, check the quantity or change it in the order settings.',
                            'errors' => 'Not Found'
                        ]);
                    }
                }
            } else {
                return json_encode([
                    'message' => 'Order with such a QR code was not found.',
                    'errors' => 'Not Found'
                ]);
            }
        }
    }

    /**
     * @param int|string $user_id
     */
    public static function driversPackages($user_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if(Auth::user()->hasAnyRole('medic', 'superadmin', 'admin', 'dispadmin')) {
            $driver = DB::table('users')->where('id', $user_id)->first();
            if((Auth::user()->can('admin'))) {
                $routes_priority0 = DB::table('routes_priority')->select("driver_id", DB::raw("GROUP_CONCAT(order_id SEPARATOR ',') as order_id"), "type", "type_id", "priority")->where('driver_id', $user_id)->where('type', 'office')->where('type_id', Auth::user()->office_id)->groupBy("type","type_id","driver_id", "priority")->orderBy("priority","asc")->first();
                if(empty($routes_priority0)) {
                    $routes_priority0 = new \stdClass();
                    $routes_priority0->order_id = '';
                }
                $orders_transfer = DB::table('orders')->where('driver_id', $user_id)->whereIn('statuse_id', [7])->whereIn('id', explode(',',$routes_priority0->order_id))->get();
                foreach($orders_transfer as $key=>$value) {
                    $orders_transfer[$key]->count_bags_transfer = DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$value->id)->where('office_id',Auth::user()->office_id)->where('driver_id',$value->driver_id)->where('target','out')->count();
                }
                $orders_pick_up = DB::table('orders')->where('driver_id', $user_id)->whereIn('statuse_id', [2,3,5,6,8,9])->whereIn('id', explode(',',$routes_priority0->order_id))->get();
                foreach($orders_pick_up as $key=>$value) {
                    $orders_pick_up[$key]->count_bags_transfer = DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$value->id)->where('office_id',Auth::user()->office_id)->where('driver_id',$value->driver_id)->where('target','in')->count();
                }
                $need_cash = DB::table('cash_log')->where('driver_id', $user_id)->where("return","0")->sum('copay');
            } else {
                $routes_priority0 = DB::table('routes_priority')->select("driver_id", DB::raw("GROUP_CONCAT(order_id SEPARATOR ',') as order_id"), "type", "type_id", "priority")->where('driver_id', $user_id)->where('type', 'pharmacy')->where('type_id', Auth::user()->pharmacy_id)->groupBy("type","type_id","driver_id", "priority")->orderBy("priority","asc")->first();
                if(empty($routes_priority0)) {
                    $routes_priority0 = new \stdClass();
                    $routes_priority0->order_id = '';
                }
                $orders_transfer = DB::table('orders')->where('driver_id', $user_id)->where('pharmacy_id', Auth::user()->pharmacy_id)->whereIn('statuse_id', [1,2])->whereIn('id', explode(',',$routes_priority0->order_id))->get();
                foreach($orders_transfer as $key=>$value) {
                    $orders_transfer[$key]->count_bags_transfer = DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$value->id)->where('pharmacy_id',Auth::user()->pharmacy_id)->where('driver_id',$value->driver_id)->where('target','out')->count();
                }
                $orders_pick_up = DB::table('orders')->where('driver_id', $user_id)->whereIn('statuse_id', [2,3,5,6,8,9])->whereIn('id', explode(',',$routes_priority0->order_id))->get();
                foreach($orders_pick_up as $key=>$value) {
                    $orders_pick_up[$key]->count_bags_transfer = DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$value->id)->where('pharmacy_id',Auth::user()->pharmacy_id)->where('driver_id',$value->driver_id)->where('target','in')->count();
                }
                $need_cash = DB::table('cash_log')->where('driver_id', $user_id)->where("return","0")->sum('copay');
            }
            $res_view = view('drivers.packages',['driver'=>$driver,'need_cash'=>$need_cash,'orders_transfer'=>$orders_transfer,'orders_pick_up'=>$orders_pick_up,'title'=>'Drivers Packages','br1'=>'Drivers','br2'=>'Drivers Packages','alert'=>'']);
            return self::renderPage($res_view);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    /**
     * @param int|string $user_id
     */
    public function driversPackagesHandler(Request $request, $user_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if(Auth::user()->hasAnyRole('medic', 'superadmin', 'admin', 'dispadmin')) {
            if($request->input('confirm_receipt')>0) {
                $driver_id = $user_id;
                $orders = DB::table('cash_log')->where('driver_id', $driver_id)->where("return","0")->get();
                foreach($orders as $order0) {
                    $order =  DB::table('orders')->where('id',$order0->order_id)->first();
                    if((Auth::user()->can('admin'))) {
                        $route = DB::table('routes_priority')->where('driver_id', $driver_id)->where('order_id',$order->id)->where('type','office');
                    } else {
                        $route = DB::table('routes_priority')->where('driver_id', $driver_id)->where('order_id',$order->id)->where('type','pharmacy');
                    }
                    foreach($route->get() as $routes_priority) {
                        DB::table('routes_priority_logs')->insert(["id"=>$routes_priority->id,"driver_id"=>$routes_priority->driver_id,"order_id"=>$routes_priority->order_id,"type"=>$routes_priority->type,"type_id"=>$routes_priority->type_id,"type_pay"=>$routes_priority->type_pay,"pay_value"=>$routes_priority->pay_value]);
                        if($routes_priority->type_pay==2) {
                            DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                        } else if($routes_priority->type_pay==3) {
                            DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value*intval($order->count_bags)]);
                        } else {
                            DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                        }
                    }
                    $route->delete();
                    $next_route = DB::table('routes_priority')->where('driver_id',$driver_id)->orderBy("priority","asc")->first();
                    if(!empty($next_route)) {
                        if($next_route->type=='patient') {
                            self::next_patient_push($driver_id,$next_route);
                        }
                    }
                }
                DB::table('cash_log')->where('driver_id', $user_id)->where("return","0")->update(["return_at"=>DB::raw('NOW()'),"return"=>"1","admin_id"=>Auth::user()->id]);
            }
            if($request->input('massiveBagsTransfer')>0) {
                if((Auth::user()->can('admin'))) {
                    $routes_priority0 = DB::table('routes_priority')->select("driver_id", DB::raw("GROUP_CONCAT(order_id SEPARATOR ',') as order_id"), "type", "type_id", "priority")->where('driver_id', $user_id)->where('type', 'office')->where('type_id', Auth::user()->office_id)->groupBy("type","type_id","driver_id", "priority")->orderBy("priority","asc")->first();
                    if(empty($routes_priority0)) {
                        $routes_priority0 = new \stdClass();
                        $routes_priority0->order_id = '';
                    }
                    $orders_transfer = DB::table('orders')->where('driver_id', $user_id)->whereIn('statuse_id', [7])->whereIn('id', explode(',',$routes_priority0->order_id))->get();
                } else {
                    $routes_priority0 = DB::table('routes_priority')->select("driver_id", DB::raw("GROUP_CONCAT(order_id SEPARATOR ',') as order_id"), "type", "type_id", "priority")->where('driver_id', $user_id)->where('type', 'pharmacy')->where('type_id', Auth::user()->pharmacy_id)->groupBy("type","type_id","driver_id", "priority")->orderBy("priority","asc")->first();
                    if(empty($routes_priority0)) {
                        $routes_priority0 = new \stdClass();
                        $routes_priority0->order_id = '';
                    }
                    $orders_transfer = DB::table('orders')->where('driver_id', $user_id)->where('pharmacy_id', Auth::user()->pharmacy_id)->whereIn('statuse_id', [1,2])->whereIn('id', explode(',',$routes_priority0->order_id))->get();
                }
                foreach($orders_transfer as $order) {
                    for ($i=1; $i <= $order->count_bags; $i++) { 
                        $this->driversBagsOrder($order->id, $i, $user_id);
                    }
                }
            }
            return redirect("drivers/$user_id/packages");
        } else {
            return abort(403, self::$err_perm);
        }
    }

    /**
     * @param int|string $order_id
     */
    public static function payCopay($order_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        $order = DB::table('orders')->where('id',$order_id)->first();
        if(!empty($order)) {
            if(!in_array($order->statuse_id,[1,5,8,9,10]) && ((Auth::user()->can('admin')) || Auth::user()->role == 'logist') && $order->copay>0) {
                $payment_account = DB::table('payment_accounts')->where('user_id',$order->user_id)->first();
                $success='';
                $error='';
                $res_view = view('card.pay-copay',['payment_account'=>$payment_account,'order'=>$order,'success'=>$success,'error'=>$error,'title'=>'Billing','br1'=>'Billings','br2'=>'List']);
                return self::renderPage($res_view);
            } else {
                return abort(403, self::$err_perm);
            }
        } else {
            return abort(403, self::$err_perm);
        }
    }

    /**
     * @param int|string $order_id
     */
    public static function payCopayHandler(Request $request, $order_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        $order = DB::table('orders')->where('id',$order_id)->first();
        if(!empty($order)) {
            $user = DB::table('users')->where('id',$order->user_id)->first();
            if(!in_array($order->statuse_id,[1,5,8,9,10]) && ((Auth::user()->can('admin')) || Auth::user()->role == 'logist') && !in_array($order->statuse_copay,[1,3,4,6]) && $order->copay>0) {
                $payment_account = DB::table('payment_accounts')->where('user_id',$order->user_id)->first();
                $success='';
                $error='';
                if(!empty($request->input('pay')) && !empty($payment_account)) {
                    $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
                    $merchantAuthentication->setName(config('app.MERCHANT_LOGIN_ID'));
                    $merchantAuthentication->setTransactionKey(config('app.MERCHANT_TRANSACTION_KEY'));
                    // Set the transaction's refId
                    $refId = 'ref' . time();
                    $profileToCharge = new AnetAPI\CustomerProfilePaymentType();
                    $profileToCharge->setCustomerProfileId($payment_account->profile_id);
                    $paymentProfile = new AnetAPI\PaymentProfileType();
                    $paymentProfile->setPaymentProfileId($payment_account->payment_profile_id);
                    $profileToCharge->setPaymentProfile($paymentProfile);
                    $transactionRequestType = new AnetAPI\TransactionRequestType();
                    $transactionRequestType->setTransactionType("authCaptureTransaction"); 
                    $transactionRequestType->setAmount(floatval(round($order->copay*1.04,2)));
                    $transactionRequestType->setProfile($profileToCharge);
                    $authorizationIndicatorType = new AnetAPI\AuthorizationIndicatorType();
                    $authorizationIndicatorType->setAuthorizationIndicator("final");
                    $transactionRequestType->setAuthorizationIndicatorType($authorizationIndicatorType);
                    $request0 = new AnetAPI\CreateTransactionRequest();
                    $request0->setMerchantAuthentication($merchantAuthentication);
                    $request0->setRefId($refId);
                    $request0->setTransactionRequest($transactionRequestType);
                    $controller = new AnetController\CreateTransactionController($request0);
                    /** @var AnetAPI\CreateTransactionResponse $response */
                    $response = $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::PRODUCTION);
                    if ($response != null) {
                        if($response->getMessages()->getResultCode() == "Ok") {
                            $tresponse = $response->getTransactionResponse();
                            if ($tresponse != null && $tresponse->getMessages() != null) {
                                DB::table('orders')->where('id',$order_id)->update(['statuse_copay'=>3]);
                                DB::table('payments')->insert(['order_id'=>$order_id,'transaction_id'=>$tresponse->getTransId(),'type'=>'copay']);
                                return response("<script>window.close();</script>");
                            } else {
                                $error= "Transaction Failed: ";
                                if($tresponse->getErrors() != null) {
                                    $error .= $tresponse->getErrors()[0]->getErrorText();     
                                }
                            }
                        } else {
                            $error= "Transaction Failed: ";
                            $tresponse = $response->getTransactionResponse();
                            if($tresponse != null && $tresponse->getErrors() != null) {
                                $error .= $tresponse->getErrors()[0]->getErrorText();                      
                            } else {
                                $error .= $response->getMessages()->getMessage()[0]->getText();
                            }
                        }
                    } else {
                        $error= "No response returned.";
                    }
                }
                if(!empty($request->input('ncard')) && !empty($request->input('name')) && !empty($request->input('expire')) && !empty($request->input('ccv'))) {
                    $ncard=str_replace(' ','',$request->input('ncard'));
                    $name=explode(' ',$request->input('name'));
                    if(count($name)==1) {
                        $name[1]="";
                    }
                    $expire=explode('/',str_replace(' ','',$request->input('expire')));
                    $ccv=$request->input('ccv');
                    $zip=$request->input('zip');
                    $user_address = explode(',',$request->input('address'));
                    if(count($user_address)==1) {
                        $user_address[1]="";
                        $user_address[2]="";
                    }
                    if(count($user_address)==2) {
                        $user_address[2]="";
                    }
                    $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
                    $merchantAuthentication->setName(config('app.MERCHANT_LOGIN_ID'));
                    $merchantAuthentication->setTransactionKey(config('app.MERCHANT_TRANSACTION_KEY'));
                    // Set the transaction's refId
                    $refId = 'ref' . time();
                    // Set credit card information for payment profile
                    if(!empty($payment_account)) {
                        $request0 = new AnetAPI\GetCustomerPaymentProfileRequest();
                        $request0->setMerchantAuthentication($merchantAuthentication);
                        $request0->setRefId($refId);
                        $request0->setCustomerProfileId($payment_account->profile_id);
                        $request0->setCustomerPaymentProfileId($payment_account->payment_profile_id);
                        $controller = new AnetController\GetCustomerPaymentProfileController($request0);
                        /** @var AnetAPI\GetCustomerPaymentProfileResponse $response */
                        $response = $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::PRODUCTION);
                        if (($response != null) && ($response->getMessages()->getResultCode() == "Ok")) {
                            $billTo = new AnetAPI\CustomerAddressType();
                            $billTo = $response->getPaymentProfile()->getbillTo();
                            $creditCard = new AnetAPI\CreditCardType();
                            $creditCard->setCardNumber($ncard);
                            $creditCard->setExpirationDate($expire[1].'-'.$expire[0]);
                            $creditCard->setCardCode($ccv);
                            $paymentCreditCard = new AnetAPI\PaymentType();
                            $paymentCreditCard->setCreditCard($creditCard);
                            $paymentprofile = new AnetAPI\CustomerPaymentProfileExType();
                            $paymentprofile->setBillTo($billTo);
                            $paymentprofile->setCustomerPaymentProfileId($payment_account->payment_profile_id);
                            $paymentprofile->setPayment($paymentCreditCard);	
                            // Submit a UpdatePaymentProfileRequest
                            $request0 = new AnetAPI\UpdateCustomerPaymentProfileRequest();
                            $request0->setMerchantAuthentication($merchantAuthentication);
                            $request0->setCustomerProfileId($payment_account->profile_id);
                            $request0->setPaymentProfile($paymentprofile);
                            $controller = new AnetController\UpdateCustomerPaymentProfileController($request0);
                            /** @var AnetAPI\UpdateCustomerPaymentProfileResponse $response */
                            $response = $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::PRODUCTION);
                            if (($response != null) && ($response->getMessages()->getResultCode() == "Ok") )
                            {
                                $success= "New Card successfully added!";
                                DB::table('payment_accounts')->where('user_id',$user->id)->update(['card'=>$ncard]);
                            } else if ($response != null) {
                                $errorMessages = $response->getMessages()->getMessage();
                                $error=$errorMessages[0]->getText();
                            }
                        } else {
                            $error="Payment profile not found.";
                        }
                    } else {
                        $creditCard = new AnetAPI\CreditCardType();
                        $creditCard->setCardNumber($ncard);
                        $creditCard->setExpirationDate($expire[1].'-'.$expire[0]);
                        $creditCard->setCardCode($ccv);
                        $paymentCreditCard = new AnetAPI\PaymentType();
                        $paymentCreditCard->setCreditCard($creditCard);
                        // Create the Bill To info for new payment type
                        $billTo = new AnetAPI\CustomerAddressType();
                        $billTo->setFirstName($name[0]);
                        $billTo->setLastName($name[1]);
                        $billTo->setCompany("QuikMedix");
                        $billTo->setAddress($user_address[0]);
                        $billTo->setCity($user_address[1]);
                        $billTo->setState($user_address[2]);
                        $billTo->setZip($zip);
                        $billTo->setCountry("USA");
                        $billTo->setPhoneNumber($user->phone);
                        $billTo->setfaxNumber("999-999-9999");
                        // Create a customer shipping address
                        $customerShippingAddress = new AnetAPI\CustomerAddressType();
                        $customerShippingAddress->setFirstName($name[0]);
                        $customerShippingAddress->setLastName($name[1]);
                        $customerShippingAddress->setCompany("QuikMedix");
                        $customerShippingAddress->setAddress($user_address[0]);
                        $customerShippingAddress->setCity($user_address[1]);
                        $customerShippingAddress->setState($user_address[2]);
                        $customerShippingAddress->setZip($zip);
                        $customerShippingAddress->setCountry("USA");
                        $customerShippingAddress->setPhoneNumber($user->phone);
                        $customerShippingAddress->setFaxNumber("999-999-9999");
                        // Create an array of any shipping addresses
                        $shippingProfiles[] = $customerShippingAddress;
                        // Create a new CustomerPaymentProfile object
                        $paymentProfile = new AnetAPI\CustomerPaymentProfileType();
                        $paymentProfile->setCustomerType('individual');
                        $paymentProfile->setBillTo($billTo);
                        $paymentProfile->setPayment($paymentCreditCard);
                        $paymentProfiles[] = $paymentProfile;
                        // Create a new CustomerProfileType and add the payment profile object
                        $customerProfile = new AnetAPI\CustomerProfileType();
                        $customerProfile->setDescription("Customer ".$user->id);
                        $customerProfile->setMerchantCustomerId($user->id);
                        $customerProfile->setEmail($user->email);
                        $customerProfile->setpaymentProfiles($paymentProfiles);
                        $customerProfile->setShipToList($shippingProfiles);
                        // Assemble the complete transaction request
                        $request0 = new AnetAPI\CreateCustomerProfileRequest();
                        $request0->setMerchantAuthentication($merchantAuthentication);
                        $request0->setRefId($refId);
                        $request0->setProfile($customerProfile);
                        // Create the controller and get the response
                        $controller = new AnetController\CreateCustomerProfileController($request0);
                        /** @var AnetAPI\CreateCustomerProfileResponse $response */
                        $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::PRODUCTION);
                        if (($response != null) && ($response->getMessages()->getResultCode() == "Ok")) {
                            $paymentProfiles = $response->getCustomerPaymentProfileIdList();
                            DB::table('payment_accounts')->insert(['user_id'=>$user->id,'profile_id'=>$response->getCustomerProfileId(),'payment_profile_id'=>$paymentProfiles[0],'card'=>$ncard]);
                            $success="Card successfully added!";
                        } else {
                            $errorMessages = $response->getMessages()->getMessage();
                            $error=$errorMessages[0]->getText();
                        }
                    }
                }
                $payment_account = DB::table('payment_accounts')->where('user_id',$order->user_id)->first();
                return view('card.pay-copay',['payment_account'=>$payment_account,'order'=>$order,'success'=>$success,'error'=>$error,'title'=>'Billing','br1'=>'Billings','br2'=>'List']);
            } else {
                return abort(403, self::$err_perm);
            }
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('login');
    }

    /**
     * @param int|string $user_id
     */
    public static function reSendAuthMessage($user_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
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
                return abort(403, self::$err_perm);
            }
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public static function news() {
        $countOnPage=20;
        $max_pages=ceil(DB::table('news')->where('user_id', Auth::user()->id)->count()/$countOnPage);
        $page = self::currentPage();
        $pages = PageLinks::around($page, $max_pages);
        $news = DB::table('news')->orderBy('id','desc')->leftJoin('news_read',function ($join) {
            $join->on("news_read.news_id","=","news.id") ;
            $join->where("news_read.user_id",Auth::user()->id);
        })->select("news.*","news_read.id as viewed")->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
        foreach($news as $new){
            if(empty($new->viewed)) {
                DB::table('news_read')->insert(["news_id"=>$new->id,"user_id"=>Auth::user()->id]);
                Redis::del(request()->getHttpHost().':get_unread_news:'.Auth::user()->id);
            }
        }
        $res_view = view('news.list',['news'=>$news,'pages'=>$pages,'title'=>'News','br1'=>'News','br2'=>'List']);
        return self::renderPage($res_view);
    }

    public static function newsAdd() {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->can('admin'))) {
            $input['link']='';
            $input['title']='';
            $input['text']='';
            $res_view = view('news.add',['title'=>'News Add','br1'=>'News list','br2'=>'News','br3'=>'News Add','alert'=>'','input'=>$input]);
            return self::renderPage($res_view);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public static function newsAddHandler(Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->can('admin'))) {
            if($request->input('save')>0 && !empty($request->input('title')) && !empty($request->input('text'))) {
                DB::table('news')->insert(["user_id"=>Auth::user()->id,"type"=>$request->input('type'),"link"=>$request->input('link'),"title"=>$request->input('title'),"text"=>$request->input('text')]);
            }
            return redirect("news");
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public static function news_patient() {
        $countOnPage=20;
        $max_pages=ceil(DB::table('news')->where('user_id', Auth::user()->id)->count()/$countOnPage);
        $page = self::currentPage();
        $pages = PageLinks::around($page, $max_pages);
        $news = DB::table('news_patient')->where("pharmacy_id",Auth::user()->pharmacy_id)->orderBy('id','desc')->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
        $res_view = view('news.list_patient',['news'=>$news,'pages'=>$pages,'title'=>'Patients','br1'=>'News','br2'=>'List']);
        return self::renderPage($res_view);
    }

    public static function news_patientAdd() {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if(Auth::user()->role == 'medic' && !empty(Auth::user()->pharmacy_id)) {
            $input['link']='';
            $input['title']='';
            $input['text']='';
            $res_view = view('news.add',['title'=>'News Add','br1'=>'News list','br2'=>'News','br3'=>'News Add','alert'=>'','input'=>$input]);
            return self::renderPage($res_view);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public static function news_patientAddHandler(Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if(Auth::user()->role == 'medic' && !empty(Auth::user()->pharmacy_id)) {
            if($request->input('save')>0 && !empty($request->input('title')) && !empty($request->input('text'))) {
                DB::table('news_patient')->insert(["user_id"=>Auth::user()->id,"pharmacy_id"=>Auth::user()->pharmacy_id,"type"=>$request->input('type'),"link"=>$request->input('link'),"title"=>$request->input('title'),"text"=>$request->input('text')]);
            }
            return redirect("news_patient");
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public function settingsWishesCategory() {
        if((Auth::user()->can('admin'))) {
            $countOnPage=20;
            $max_pages=ceil(DB::table('wishes_category')->count()/$countOnPage);
            $page = self::currentPage();
            $pages = PageLinks::around($page, $max_pages);
            $wishes_categorys = DB::table('wishes_category')->orderBy('id','desc')->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
            $res_view = view('wishes.category',['wishes_categorys'=>$wishes_categorys,'pages'=>$pages,'title'=>'Print Text Category','br1'=>'Print Text','br2'=>'Category']);
            return self::renderPage($res_view);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public function settingsWishesCategoryHandler(Request $request) {
        if((Auth::user()->can('admin'))) {
            if($request->input('remove')>0) {
                DB::table('wishes')->where('category_id', $request->input('wishes_category_id'))->delete();
                DB::table('wishes_category')->where('id', $request->input('wishes_category_id'))->delete();
            }
            if($request->input('activate')>0) {
                DB::table('wishes_category')->where('id', $request->input('wishes_category_id'))->update(["status"=>1]);
            }
            if($request->input('noactivate')>0) {
                DB::table('wishes_category')->where('id', $request->input('wishes_category_id'))->update(["status"=>0]);
            }
            return redirect("settings/wishes");
        } else {
            return abort(403, self::$err_perm);
        }
    }

    /**
     * @param int|string $driver_id
     */
    public function driversProfile($driver_id) {
        if((Auth::user()->can('admin'))) {
            $user = DB::table('users')->where('id', $driver_id)->where('role','driver')->first();
            if(!empty($user)){
                $pharmacys = DB::table('pharmacys')->get()->keyBy('id');
                $user_actions = DB::table('action_log')->where('user_id', $driver_id)->get();
                $orders_stat = DB::table('orders')->where('user_id',$driver_id)->where('statuse_id',4)->select(DB::raw('count(distinct id) as count'),DB::raw('sum(copay) as copay'))->first();
                $duty = DB::table('cash_log')->where('driver_id',$driver_id)->where('return','0')->sum('copay');
                $last_cash = DB::table('cash_log')->where('driver_id',$driver_id)->where('return','1')->orderBy('return_at','desc')->first();
                $orders = DB::table('orders')->where('user_id',$driver_id)->join('users', 'orders.user_id', '=', 'users.id')->leftJoin('users as driver', 'orders.driver_id', '=', 'driver.id')->join('statuses', 'orders.statuse_id', '=', 'statuses.id')->leftJoin('statuses_copay', 'orders.statuse_copay', '=', 'statuses_copay.id')->join('delivery_methods', 'orders.delivery_method_id', '=', 'delivery_methods.id')->join('delivery_times', 'orders.delivery_time_id', '=', 'delivery_times.id')->join('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id')->select('orders.id', 'orders.created', 'orders.driver_id', 'orders.merchantOrder','orders.special_instructions', 'orders.rating','orders.fridge','orders.actual','orders.eta','orders.facility', 'driver.name as drivername', 'driver.last_name as driverlast_name', 'driver.pharmacy_id as driverpharmacy_id', 'orders.count_bags', 'orders.signature','orders.statuse_id', 'orders.pharmacy_id', 'orders.copay', 'users.name as username', 'delivery_methods.name as delivery_method', 'delivery_times.name as delivery_time', 'users.last_name as last_name', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end as useraddress'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end as userapartment'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end as userzip'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end as userlocation'), 'users.os as useros', 'users.phone as userphone', 'pharmacys.name as pharmacyname', 'pharmacys.address as pharmacyaddress','pharmacys.phone as pharmacyphone', 'orders.tariff', 'statuses.name as statusename','statuses.color as statusecolor','statuses_copay.name as statuse_copay_name','statuses_copay.color as statuse_copay_color')->groupBy('orders.id', 'orders.statuse_id', 'orders.merchantOrder', 'orders.actual','orders.eta', 'orders.driver_id', 'orders.rating','orders.signature','orders.fridge','driver.name', 'driver.last_name', 'driver.pharmacy_id','orders.special_instructions', 'orders.count_bags', 'orders.created', 'orders.facility','orders.copay', 'orders.pharmacy_id', 'orders.tariff', 'users.name', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end'), 'users.phone','pharmacys.name', 'pharmacys.address', 'delivery_methods.name', 'delivery_times.name', 'pharmacys.phone', 'statuses.name','statuses.color','users.last_name', 'statuses_copay.name','statuses_copay.color', 'users.os')->orderBy('orders.id','desc')->limit(8)->get();
                $res_view = view('drivers.profile',['user'=>$user,'pharmacys'=>$pharmacys,'duty'=>$duty,'last_cash'=>$last_cash,'user_actions'=>$user_actions,'orders_stat'=>$orders_stat,'orders'=>$orders,'alert'=>'','title'=>'Driver Profile','br1'=>'Drivers','br2'=>'Profile']);
                return self::renderPage($res_view);
            }
            return abort(404);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    /**
     * @param int|string $driver_id
     */
    public function driversProfileHandler(Request $request,$driver_id) {
        if((Auth::user()->can('admin'))) {
            $user = DB::table('users')->where('id', $driver_id)->where('role','driver')->first();
            if(!empty($user)){
                $user_id=$driver_id;
                if($request->input('save')>0) {
                    if($request->hasFile('image')) {
                        $file = $request->file('image');
                        $file->move(public_path() . '/images/users/',\App\Support\PublicUpload::name($request->file('image')));
                        $src = '/images/users/'.\App\Support\PublicUpload::name($request->file('image'));
                    } else {
                        $src = $user->image;
                    }
                    if($request->input('remove_photo')>0) {
                        $src = '/images/users/default-user-image.png';
                    }
                    if($request->hasFile('driving_license_img')) {
                        $file = $request->file('driving_license_img');
                        $file->move(public_path() . '/images/driving_license/',\App\Support\PublicUpload::name($request->file('driving_license_img')));
                        $driving_license_img = '/images/driving_license/'.\App\Support\PublicUpload::name($request->file('driving_license_img'));
                    } else {
                        $driving_license_img = $user->driving_license_img;
                    }
                    if($request->hasFile('car_img')) {
                        $file = $request->file('car_img');
                        $file->move(public_path() . '/images/users/',\App\Support\PublicUpload::name($request->file('car_img')));
                        $car_img = '/images/users/'.\App\Support\PublicUpload::name($request->file('car_img'));
                    } else {
                        $car_img = $user->car_img;
                    }
                    if(!empty($request->input('zip'))) {
                        $address = $request->input('address').' '.$request->input('zip');
                    } else {
                        $address = $request->input('address');
                    }
                    $data = app(\App\Actions\GeocodeAddress::class)->response($address);
                    $address = $data->results[0]->formatted_address;
                    $location = $data->results[0]->geometry->location->lat.','.$data->results[0]->geometry->location->lng;
                    app(LogUserChanges::class)->handle($request,$address,$user_id);
                    DB::table('users')->where('id', $user_id)->update(['name' => $request->input('name'),'last_name' => $request->input('last_name'),'email' => $request->input('email'),'phone' => $request->input('phone'),'image' => $src,'address' => $address,'location' => $location,'zip' => $request->input('zip'),'apartment' => $request->input('apartment'),'driving_license' => $request->input('driving_license'),'driving_license_img' => $driving_license_img,'identification_cards' => $request->input('identification_cards'),'transport' => $request->input('transport'),'car_info' => $request->input('car_info'),'car_img' => $car_img]);
                }
                if($request->input('ajax_stat')>0) {
                    $date=date("Y-m-d",strtotime($request->input('date_stat')));
                    $routes_logs = DB::table('routes_priority_logs')->where('driver_id',$driver_id)->whereDate('created', $date)->orderBy('id','asc')->get();
                    $id=1;
                    $routes_logs_group = [];
                    $order_ids=[];
                    $copay=[];
                    $orders=[];
                    $copay_sum=0;
                    foreach($routes_logs as $key=>$row){
                        if(!in_array($row->order_id,$order_ids)){
                            $order_ids[]=$row->order_id;
                            $order = DB::table('orders')->where('orders.id',$row->order_id)->join('users', 'orders.user_id', '=', 'users.id')->leftJoin('users as driver', 'orders.driver_id', '=', 'driver.id')->join('statuses', 'orders.statuse_id', '=', 'statuses.id')->leftJoin('statuses_copay', 'orders.statuse_copay', '=', 'statuses_copay.id')->join('delivery_methods', 'orders.delivery_method_id', '=', 'delivery_methods.id')->join('delivery_times', 'orders.delivery_time_id', '=', 'delivery_times.id')->join('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id')->select('orders.id', 'orders.statuse_copay', 'orders.created', 'orders.driver_id', 'orders.merchantOrder','orders.special_instructions', 'orders.rating','orders.fridge','orders.actual','orders.eta','orders.facility', 'driver.name as drivername', 'driver.last_name as driverlast_name', 'driver.pharmacy_id as driverpharmacy_id', 'orders.count_bags', 'orders.signature','orders.statuse_id', 'orders.pharmacy_id', 'orders.copay', 'users.name as username', 'delivery_methods.name as delivery_method', 'delivery_times.name as delivery_time', 'users.last_name as last_name', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end as useraddress'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end as userapartment'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end as userzip'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end as userlocation'), 'users.os as useros', 'users.phone as userphone', 'pharmacys.name as pharmacyname', 'pharmacys.address as pharmacyaddress','pharmacys.phone as pharmacyphone', 'orders.tariff', 'statuses.name as statusename','statuses.color as statusecolor','statuses_copay.name as statuse_copay_name','statuses_copay.color as statuse_copay_color')->groupBy('orders.id', 'orders.statuse_copay', 'orders.statuse_id', 'orders.merchantOrder', 'orders.actual','orders.eta', 'orders.driver_id', 'orders.rating','orders.signature','orders.fridge','driver.name', 'driver.last_name', 'driver.pharmacy_id','orders.special_instructions', 'orders.count_bags', 'orders.created', 'orders.facility','orders.copay', 'orders.pharmacy_id', 'orders.tariff', 'users.name', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end'), 'users.phone','pharmacys.name', 'pharmacys.address', 'delivery_methods.name', 'delivery_times.name', 'pharmacys.phone', 'statuses.name','statuses.color','users.last_name', 'statuses_copay.name','statuses_copay.color', 'users.os')->orderBy('orders.id','desc')->first();
                            if($order->statuse_copay=='4') {
                                $copay[]=$order->copay;
                                $copay_sum+=$order->copay;
                            }
                            $orders[]=$order;
                        }
                        if(isset($routes_logs[$key+1]) && $row->type.$row->type_id==$routes_logs[$key+1]->type.$routes_logs[$key+1]->type_id){} else {
                            $row->area_name=NULL;
                            if($row->type=='office'){
                                $loc = DB::table('offices')->where('id',$row->type_id)->first();
                                if(!empty($loc)){
                                    $area = DB::table('area')->whereRaw('ST_CONTAINS(polygon, POINT(?, ?))', \App\Support\GeoPoint::bindings($loc->location))->select("area.name")->first();
                                    if(!empty($area)){
                                        $row->area_name = $area->name;
                                    }
                                }
                            }
                            if($row->type=='patient'){
                                $loc = DB::table('users')->where('id',$row->type_id)->first();
                                if(!empty($loc)){
                                    $area = DB::table('area')->whereRaw('ST_CONTAINS(polygon, POINT(?, ?))', \App\Support\GeoPoint::bindings($loc->location))->select("area.name")->first();
                                    if(!empty($area)){
                                        $row->area_name = $area->name;
                                    }
                                }
                            }
                            if($row->type=='pharmacy'){
                                $loc = DB::table('pharmacys')->where('id',$row->type_id)->first();
                                if(!empty($loc)){
                                    $area = DB::table('area')->whereRaw('ST_CONTAINS(polygon, POINT(?, ?))', \App\Support\GeoPoint::bindings($loc->location))->select("area.name")->first();
                                    if(!empty($area)){
                                        $row->area_name = $area->name;
                                    }
                                }
                            }
                            $row->priority=$id;
                            $row->order_id=$order_ids;
                            $row->orders=$orders;
                            $row->copay=$copay;
                            array_push($routes_logs_group,$row);
                            $order_ids=[];
                            $orders=[];
                            $copay=[];
                            $id++;
                        }
                    }
                    return view('drivers.ajax_stat',['routes_logs_groups'=>$routes_logs_group,'copay_sum'=>$copay_sum]);
                }
                return redirect("/drivers/$driver_id/profile");
            }
            return abort(404);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public function settingsWishesCategoryAdd() {
        if((Auth::user()->can('admin'))) {
            $input['name']='';
            $res_view = view('wishes.category_add',['input'=>$input,'alert'=>'','title'=>'Print Text Category Add','br1'=>'Print Text','br2'=>'Category Add']);
            return self::renderPage($res_view);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public function settingsWishesCategoryAddHandler(Request $request) {
        if((Auth::user()->can('admin'))) {
            if($request->input('save')>0) {
                if(!empty(DB::table('wishes_category')->where('name', $request->input('name'))->first())) {
                    $input['name']=$request->input('name');
                    return view('wishes.category_add',['input'=>$input,'alert'=>'Print Text Category with this name is alredy exist.','title'=>'Print Text Category Add','br1'=>'Print Text','br2'=>'Category Add']);
                } else {
                    DB::table('wishes_category')->insert(['name' => $request->input('name')]);
                }
            }
            return redirect("settings/wishes");
        } else {
            return abort(403, self::$err_perm);
        }
    }

    /**
     * @param int|string $wish_id
     */
    public function settingsWishes($wish_id) {
        if((Auth::user()->can('admin'))) {
            $countOnPage=20;
            $max_pages=ceil(DB::table('wishes')->where("category_id",$wish_id)->count()/$countOnPage);
            $page = self::currentPage();
            $pages = PageLinks::around($page, $max_pages);
            $wishes = DB::table('wishes')->where("category_id",$wish_id)->orderBy('id','desc')->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
            $res_view = view('wishes.wishes',['wishes'=>$wishes,'wish_id'=>$wish_id,'pages'=>$pages,'title'=>'Print Text','br1'=>'Print Text','br2'=>'List']);
            return self::renderPage($res_view);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    /**
     * @param int|string $wish_id
     */
    public function settingsWishesHandler($wish_id,Request $request) {
        if((Auth::user()->can('admin'))) {
            if($request->input('remove')>0) {
                DB::table('wishes')->where('id', $request->input('wishes_id'))->delete();
            }
            return redirect("settings/wishes/$wish_id/list");
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public function settingsWishesAdd() {
        if((Auth::user()->can('admin'))) {
            $input['text']='';
            $res_view = view('wishes.add',['input'=>$input,'alert'=>'','title'=>'Print Text Add','br1'=>'Print Text','br2'=>'Add']);
            return self::renderPage($res_view);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    /**
     * @param int|string $wish_id
     */
    public function settingsWishesAddHandler($wish_id,Request $request) {
        if((Auth::user()->can('admin'))) {
            if($request->input('save')>0) {
                DB::table('wishes')->insert(['category_id'=>$wish_id,'text' => $request->input('text')]);
            }
            return redirect("settings/wishes/$wish_id/list");
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public function settingsStates() {
        if((Auth::user()->can('admin'))) {
            $countOnPage=20;
            $max_pages=ceil(DB::table('area')->count()/$countOnPage);
            $page = self::currentPage();
            $pages = PageLinks::around($page, $max_pages);
            $areas = DB::table('area')->orderBy('id','desc')->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
            $res_view = view('area.list',['areas'=>$areas,'pages'=>$pages,'title'=>'Area Tariff List','br1'=>'Area','br2'=>'Tariff List']);
            return self::renderPage($res_view);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public function settingsStatesHandler(Request $request) {
        if((Auth::user()->can('admin'))) {
            if($request->input('remove')>0) {
                DB::table('area')->where('id', $request->input('area_id'))->delete();
                DB::table('area_zip')->where("area_id",$request->input('area_id'))->delete();
            }
            return redirect("settings/area");
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public function settingsStatesAdd() {
        if((Auth::user()->can('admin'))) {
            $area = new \stdClass();
            $area->name="";
            $area->state="";
            $polygon="";
            $states=[];
            $states_list = DB::table('states')->get();
            $polygons = DB::table('area')->select('name',DB::raw('ST_AsText(polygon) as polygon'))->get();
            foreach($polygons as $key=>$pol) {
                if(!empty($pol->polygon)) {
                    $polygons[$key]->polygon = $this->encodePolygon($pol->polygon);
                } else {
                    $polygons[$key]->polygon = "";
                }   
            }
            $res_view = view('area.form',['area'=>$area,'polygon'=>$polygon,'polygons'=>$polygons,'states'=>$states,'states_list'=>$states_list,'alert'=>'','title'=>'Area Tariff Add','br1'=>'Area','br2'=>'Tariff Add']);
            return self::renderPage($res_view);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public function settingsStatesAddHandler(Request $request) {
        if((Auth::user()->can('admin'))) {
            if($request->input('save')>0) {
                $area_id = DB::table('area')->insertGetId($this->tariffAreaData($request));
                if(!empty($request->input('zip'))){
                    $zips = array_filter(explode("\n",$request->input('zip')));
                    foreach($zips as $zip){
                        if(!empty($zip)){
                            DB::table('area_zip')->insert(['area_id' => $area_id,'zip' => preg_replace('/\s+/', '', $zip)]);
                        }
                    }
                }
            }
            return redirect("settings/area");
        } else {
            return abort(403, self::$err_perm);
        }
    }

    /**
     * @param int|string $area_id
     */
    public function settingsStatesEdit($area_id) {
        if((Auth::user()->can('admin'))) {
            $area = DB::table('area')->where('id', $area_id)->select('id','name','state','tariff',DB::raw('ST_AsText(polygon) as polygon'))->first();
            if(!empty($area->polygon)) {
                $polygon = $this->encodePolygon($area->polygon);
            } else {
                $polygon = "";
            }
            $polygons = DB::table('area')->where('id', '!=', $area_id)->where('state',$area->state)->select('name',DB::raw('ST_AsText(polygon) as polygon'))->get();
            foreach($polygons as $key=>$pol) {
                if(!empty($pol->polygon)) {
                    $polygons[$key]->polygon = $this->encodePolygon($pol->polygon);
                } else {
                    $polygons[$key]->polygon = "";
                }   
            }
            $states = DB::table('area_zip')->where('area_id', $area_id)->get();
            $states_list = DB::table('states')->get();
            $res_view = view('area.form',['area'=>$area,'polygon'=>$polygon,'polygons'=>$polygons,'states'=>$states,'states_list'=>$states_list,'alert'=>'','title'=>'Area Tariff Edit','br1'=>'Area','br2'=>'Tariff Edit']);
            return self::renderPage($res_view);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    /**
     * @param int|string $area_id
     */
    public function settingsStatesEditHandler($area_id, Request $request) {
        if((Auth::user()->can('admin'))) {
            if($request->input('save')>0) {
                DB::table('area')->where("id",$area_id)->update($this->tariffAreaData($request));
                //DB::table('area_zip')->where("area_id",$area_id)->delete();
                if(!empty($request->input('zip'))){
                    $zips = array_filter(explode("\r\n",$request->input('zip')));
                    foreach($zips as $zip){
                        if(!empty($zip)){
                            DB::table('area_zip')->insert(['area_id' => $area_id,'zip' => preg_replace('/\s+/', '', $zip)]);
                        }
                    }
                }
            }
            return redirect("settings/area");
        } else {
            return abort(403, self::$err_perm);
        }
    }

    private function tariffAreaData(Request $request) {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'polygon' => 'required|json',
        ], ['polygon.required' => 'Draw a tariff area on the map before saving.']);
        $points = json_decode($data['polygon'], true);
        $invalid = function () {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'polygon' => 'Draw an area with at least three distinct corners on the map.',
            ]);
        };
        if (!is_array($points) || !array_is_list($points) || count($points) < 3) {
            $invalid();
        }
        $coordinates = [];
        foreach ($points as $point) {
            if (!is_array($point) || !isset($point['lat'], $point['lng'])
                || !is_numeric($point['lat']) || !is_numeric($point['lng'])
                || !is_finite((float) $point['lat']) || !is_finite((float) $point['lng'])
                || abs((float) $point['lat']) > 90 || abs((float) $point['lng']) > 180) {
                $invalid();
            }
            // Keep the latitude/longitude order used by existing area queries.
            $coordinates[] = (float) $point['lat'].' '.(float) $point['lng'];
        }
        if (count(array_unique($coordinates)) < 3) {
            $invalid();
        }
        if (end($coordinates) !== $coordinates[0]) {
            $coordinates[] = $coordinates[0];
        }
        $data['polygon'] = DB::raw("ST_GeomFromText('POLYGON((".implode(',', $coordinates)."))')");
        return $data;
    }

    /**
     * @param string $polygon_json
     */
    function decodePolygon($polygon_json) {
        $polygon_str = "";
        $data = json_decode($polygon_json);
        foreach($data as $key=>$row) {
            $polygon_str.=$row->lat.' ';
            $polygon_str.=$row->lng;
            if($key<count($data)-1) {
                $polygon_str.=',';
            } else {
                $polygon_str.=','.$data[0]->lat.' '.$data[0]->lng;
            }
        }
        return $polygon_str;
    }

    /**
     * @param string $polygon_str
     */
    function encodePolygon($polygon_str) {
        $polygon_json = [];
        $data = explode(',',str_replace(['POLYGON((','))'],'',$polygon_str));
        foreach($data as $key=>$row) {
            if($key<count($data)-1) {
                $a = explode(' ',$row);
                if(count($a)>1) {
                    array_push($polygon_json,["lat"=>floatval($a[0]),"lng"=>floatval($a[1])]);
                }
            }
        }
        return json_encode($polygon_json);
    }

    public function settingsPlans() {
        if((Auth::user()->can('admin'))) {
            $countOnPage=20;
            $max_pages=ceil(DB::table('plans')->count()/$countOnPage);
            $page = self::currentPage();
            $pages = PageLinks::around($page, $max_pages);
            $plans = DB::table('plans')->orderBy('id','desc')->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
            $res_view = view('plans.list',['plans'=>$plans,'pages'=>$pages,'title'=>'Tariff Plans List','br1'=>'Tariff','br2'=>'Tariff Plans']);
            return self::renderPage($res_view);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public function settingsPlansHandler(Request $request) {
        if((Auth::user()->can('admin'))) {
            if($request->input('remove')>0) {
                DB::table('plans')->where('id', $request->input('plan_id'))->delete();
            }
            return redirect("settings/plans");
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public function settingsPlansAdd() {
        if((Auth::user()->can('admin'))) {
            $plan = new \stdClass();
            $plan->name="";
            $plan->order_rate="";
            $plan->tariff="";
            $plan->tariff_next_day="";
            $plan->tariff_same_day="";
            $plan->tariff_asap="";
            $plan->tariff_after_hours="";
            $plan->tariff_fridge="";
            $plan->tariff_area2="";
            $plan->tariff_area3="";
            $plan->tariff_area_more="";
            $res_view = view('plans.form',['plan'=>$plan,'alert'=>'','title'=>'Tariff Plan Add','br1'=>'Tariff','br2'=>'Tariff Plan Add']);
            return self::renderPage($res_view);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public function settingsPlansAddHandler(Request $request) {
        if((Auth::user()->can('admin'))) {
            if($request->input('save')>0) {
                DB::table('plans')->insertGetId(['name' => $request->input('name'),'order_rate' => $request->input('order_rate'),'tariff' => $request->input('tariff'),'tariff_next_day' => $request->input('tariff_next_day'),'tariff_same_day' => $request->input('tariff_same_day'),'tariff_asap' => $request->input('tariff_asap'),'tariff_after_hours' => $request->input('tariff_after_hours'),'tariff_fridge' => $request->input('tariff_fridge'),'tariff_area2' => $request->input('tariff_area2'),'tariff_area3' => $request->input('tariff_area3'),'tariff_area_more' => $request->input('tariff_area_more')]);
            }
            return redirect("settings/plans");
        } else {
            return abort(403, self::$err_perm);
        }
    }

    /**
     * @param int|string $plan_id
     */
    public function settingsPlansEdit($plan_id) {
        if((Auth::user()->can('admin'))) {
            $plan = DB::table('plans')->where('id', $plan_id)->first();
            $res_view = view('plans.form',['plan'=>$plan,'alert'=>'','title'=>'Tariff Plan Edit','br1'=>'Tariff','br2'=>'Tariff Plan Edit']);
            return self::renderPage($res_view);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    /**
     * @param int|string $plan_id
     */
    public function settingsPlansEditHandler($plan_id, Request $request) {
        if((Auth::user()->can('admin'))) {
            if($request->input('save')>0) {
                DB::table('plans')->where("id",$plan_id)->update(['name' => $request->input('name'),'order_rate' => $request->input('order_rate'),'tariff' => $request->input('tariff'),'tariff_next_day' => $request->input('tariff_next_day'),'tariff_same_day' => $request->input('tariff_same_day'),'tariff_asap' => $request->input('tariff_asap'),'tariff_after_hours' => $request->input('tariff_after_hours'),'tariff_fridge' => $request->input('tariff_fridge'),'tariff_area2' => $request->input('tariff_area2'),'tariff_area3' => $request->input('tariff_area3'),'tariff_area_more' => $request->input('tariff_area_more')]);
            }
            return redirect("settings/plans");
        } else {
            return abort(403, self::$err_perm);
        }
    }


    /**
     * @param int|string $pharmacy_id
     */
    public function import_order($pharmacy_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if(Auth::user()->pharmacy_balance_ban()) {
            return redirect("billing/".Auth::user()->pharmacy_id, 302);
        }
        if(Auth::user()->can('manage-pharmacy', $pharmacy_id)) {
            return view('import.step1',['alert'=>'','title'=>'Import Order','br1'=>'Import','br2'=>'Import Order']);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    /**
     * @param int|string $pharmacy_id
     */
    public function import_orderHandler($pharmacy_id,Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if(Auth::user()->pharmacy_balance_ban()) {
            return redirect("billing/".Auth::user()->pharmacy_id, 302);
        }
        if(Auth::user()->can('manage-pharmacy', $pharmacy_id)) {
            if($request->input('step2')>0) {
                $request->validate([
                    'import' => 'required|mimes:pdf|max:2048'
                ]);
                $parser = new Parser();
                $pdf = $parser->parseFile($request->file('import'));
                $text = $pdf->getPages()[0]->getDataTm();
                $user = new User;
                if(strpos($text[11][1],',')===false) {
                    $user->name=str_replace(' ','',explode(',',$text[12][1])[0]);
                    $user->last_name=str_replace(' ','',explode(',',$text[12][1])[1]);
                } else {
                    $user->name=str_replace(' ','',explode(',',$text[11][1])[0]);
                    $user->last_name=str_replace(' ','',explode(',',$text[11][1])[1]);
                }
                $user->phone=(strpos($text[2][1],'Cell#: () -')!==false)?str_replace('Ph#: ','',$text[1][1]):str_replace('Cell#: ','',$text[2][1]);
                $user->home_phone='';
                $user->address=$text[0][1];
                $user->apartment=(strpos($text[0][1],'APT')!==false)?str_replace(' ','',explode(',',explode('APT',$text[0][1])[1])[0]):'';
                $addr = explode(' ',$text[0][1]);
                $user->zip=end($addr);
                $user->pharmacy_id=$pharmacy_id;
                $copay=floatval(preg_replace("/[^-0-9\.]/","",$text[(array_search('Total Rx Count:', array_column($text, 1))+1)][1]));
                $rxs = [];
                for ($i=(array_search('Rf#', array_column($text, 1))+1); $i < (array_search('Total Rx Count:', array_column($text, 1))-1); $i++) {
                    if(floatval($text[$i][0][4])>9 && floatval($text[$i][0][4])<20) {
                        $rx['rx_date']=date("Y-m-d",strtotime($text[$i][1]));
                        $rx['rx_id']='';
                        for ($i2=($i+1); $i2 < (array_search('Total Rx Count:', array_column($text, 1))-1); $i2++) {
                            if(floatval($text[$i2][0][4])>47 && floatval($text[$i2][0][4])<59) {
                                $rx['rx_id']=$text[$i2][1];
                                for ($i3=($i2+1); $i3 < (array_search('Total Rx Count:', array_column($text, 1))-1); $i3++) {
                                    if(floatval($text[$i3][0][4])>106 && floatval($text[$i3][0][4])<120) {
                                        $rx['rx_id'].='-'.$text[$i3][1];
                                        break;
                                    }
                                }
                                break;
                            }
                        }
                        $rxs[]=$rx;
                    }
                }
                $delivery_methods = DB::table('delivery_methods')->get();
                $delivery_times = DB::table('delivery_times')->get();
                $pharmacy=DB::table('pharmacys')->where('pharmacys.id',$pharmacy_id)->first();
                $drivers = DB::table('users')->where('role', 'driver')->where("pharmacy_id",$pharmacy_id)->get();
                $time_ranges = ["9:00 AM","10:00 AM","11:00 AM","12:00 PM", "1:00 PM","2:00 PM","3:00 PM","4:00 PM","5:00 PM","6:00 PM","7:00 PM","8:00 PM","9:00 PM","10:00 PM","11:00 PM","12:00 AM"];
                return view('import.step2',['user'=>$user,'pharmacy'=>$pharmacy,'time_ranges'=>$time_ranges,'drivers'=>$drivers,'delivery_methods'=>$delivery_methods,'delivery_times'=>$delivery_times,'copay'=>$copay,'rxs'=>$rxs,'alert'=>'','title'=>'Import Order','br1'=>'Import','br2'=>'Import Order']);
            }
            if($request->input('save')>0) {
                $user = DB::table('users')->where('phone', $request->input('phone'))->where('pharmacy_id', $pharmacy_id)->first();
                if(empty($user)) {
                    $src = '';
                    $email = $request->input('email');
                    if(empty($email)){
                        $email = 'patients'.(intval(DB::table('users')->max('id'))+1).'@'.config('branding.account_email_domain');
                    }
                    $address = $request->input('address');
                    $data = app(\App\Actions\GeocodeAddress::class)->response($address);
                    $address = $data->results[0]->formatted_address;
                    $location = $data->results[0]->geometry->location->lat.','.$data->results[0]->geometry->location->lng;
                    DB::table('users')->insert(['isactive'=>1,'name' => $request->input('name'),'last_name' => $request->input('last_name'),'email' => $email,'phone' => $request->input('phone'),'image' => $src,'address' => $address,'location' => $location,'password' => Hash::make($request->input('password')),'zip' => $request->input('zip'),'apartment' => $request->input('apartment'),'pharmacy_id' => $pharmacy_id]);
                } else {
                    $address = $request->input('address');
                    $data = app(\App\Actions\GeocodeAddress::class)->response($address);
                    $address = $data->results[0]->formatted_address;
                    $location = $data->results[0]->geometry->location->lat.','.$data->results[0]->geometry->location->lng;
                    DB::table('users')->where('id',$user->id)->update(['name' => $request->input('name'),'last_name' => $request->input('last_name'),'address' => $address,'location' => $location,'zip' => $request->input('zip'),'apartment' => $request->input('apartment')]);
                }
                $user = DB::table('users')->where('phone', $request->input('phone'))->where('pharmacy_id', $pharmacy_id)->first();
                $id_max = DB::table('orders')->max('id')+1;
                $copay = (empty($request->input('copay')))?'0':round($request->input('copay'),2);
                $statuse_copay = (empty($request->input('copay')))?'1':'2';
                if(!empty($request->input('copay_paid_pharm'))) {
                    $statuse_copay='6';
                }
                $fridge = (empty($request->input('fridge')))?'0':$request->input('fridge');
                $special_instructions = (empty($request->input('special_instructions')))?NULL:addslashes($request->input('special_instructions'));
                $rx_ids = $request->input('rx_id');
                $rx_dates = $request->input('rx_date');
                $data=[];
                foreach(array_keys($rx_ids) as $key){
                    $data[]=["order_id"=>$id_max,"rx_id"=>str_replace(" ",'',$rx_ids[$key]),"rx_date"=>$rx_dates[$key]];
                }
                DB::table('rxs')->insert($data);
                if($request->input('delivery_time')=="1"){
                    $delivery_date = DB::raw("DATE_ADD(CURDATE(), INTERVAL 1 DAY)");
                } else {
                    $delivery_date = DB::raw("CURDATE()");
                }
                $time_ranges = ["9:00 AM","10:00 AM","11:00 AM","12:00 PM", "1:00 PM","2:00 PM","3:00 PM","4:00 PM","5:00 PM","6:00 PM","7:00 PM","8:00 PM","9:00 PM","10:00 PM","11:00 PM","12:00 AM"];
                if(empty($request->input('delivery_time_range'))) {
                    $delivery_time_range = $time_ranges[0].";".end($time_ranges);
                } else {
                    $delivery_time_range = $request->input('delivery_time_range');
                }
                if(!empty($request->input('driver'))) {
                    $driver_id = $request->input('driver');
                } else {
                    $driver_id = NULL;
                }
                DB::table('orders')->insert(['id'=>$id_max,'pharmacy_id' => $pharmacy_id, 'medic_id' => Auth::user()->id, 'driver_id'=>$driver_id, 'user_id' => $user->id, 'copay' => $copay, 'statuse_copay' => $statuse_copay, 'delivery_method_id' => $request->input('delivery_method'), 'special_instructions' => $special_instructions, 'count_bags' => $request->input('count_bags'), 'extra_charge_driver'=>floatval($request->input('extra_charge_driver')), 'type_driver' => $request->input('type_driver'), 'delivery_time_id' => $request->input('delivery_time'), 'delivery_time_range' => $delivery_time_range, 'delivery_date'=>$delivery_date, 'fridge' => $fridge]);
                Notifications::send_push($user->id,"QuikMedix","QuikMedix is greeting you! Your order #$id_max is ready to be shipped to this address: $user->address If the address is wrong, please contact ".\App\Support\Branding::supportContact()." as soon as possible");
                if($request->input('delivery_time')==3 || $request->input('delivery_time')==4) {
                    $pharmacy = DB::table('pharmacys')->where('id', $pharmacy_id)->first();
                    Notifications::send_push_web(array_map('strval', User::where('role', "admin")->orWhere("role","logist")->pluck('id')->toArray()),
                        "Attention!",
                        "Urgent order No.".$id_max." has been created, which needs to be processed promptly.",
                        url('/')."/orders/".$pharmacy_id."?statuse%5B%5D=1",
                        "rush_order"
                    );
                }
            }
            return redirect("orders/$pharmacy_id?added=$id_max");
        } else {
            return abort(403, self::$err_perm);
        }
    }



    public function test() {
        /*$date_from = "2023-06-12";
        $date_to = "2023-06-18";
        $pharmacys = [188];
        foreach($pharmacys as $pharmacy_id) {
            $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
            $orders = DB::table('orders')->where('pharmacy_id', $pharmacy_id)->whereIn('statuse_id',[4,8,9,10])->whereDate('finish', '>=', $date_from)->whereDate('finish', '<=', $date_to);
            $count_orders=$orders->count();
            $sum_amount = 0;
            $sum_copay = 0;
            $orders = $orders->get();
            foreach($orders as $order) {
                if(!empty($order->tariff)) {
                    $sum_amount = $sum_amount+$order->tariff;
                    if($order->statuse_copay==3 || $order->statuse_copay==4){
                        $sum_copay = $sum_copay+floatval($order->copay);
                    }
                }
            }
            if($pharmacy->copay_bill=='1') {
                if((($sum_amount)-$sum_copay)<0) {
                    $amount2 = 0;
                } else {
                    $amount2 = round((($sum_amount)-$sum_copay),2);
                }
            } else {
                $amount2 = round(($sum_amount),2);
            }
            $balance = floatval($pharmacy->balance)-$amount2;
            DB::table('pharmacys')->where("id",$pharmacy_id)->update(['balance'=>$balance]);
            $invoice_id = DB::table('invoices')->insertGetId(['pharmacy_id'=>$pharmacy_id,'date_from' => $date_from,'date_to' => $date_to, 'count'=>$count_orders, 'amount'=>$sum_amount, 'copay'=>$sum_copay]);
            if(floatval($pharmacy->balance)>=$amount2) {
                DB::table('pharmacy_payments')->insert(['pharmacy_id'=>$pharmacy_id,'invoice_id'=>$invoice_id,'amount'=>$amount2,'transaction_id'=>'balance','type'=>'pay']);
                DB::table('invoices')->where('id',$invoice_id)->where('pharmacy_id',$pharmacy_id)->update(["payed"=>'1']);
                $invoice_exclusions = DB::table('invoice_exclusion')->where('invoice_id',$invoice_id)->pluck('order_id')->toArray();
                DB::table('orders')->where('pharmacy_id', $pharmacy_id)->whereIn('statuse_id',[4,8,9,10])->whereDate('finish', '>=', $date_from)->whereDate('finish', '<=', $date_to)->whereNotIn('id',$invoice_exclusions)->update(["invoice_payed"=>"1"]);
            }
        }*/
        /*$start = "2023-06-12";
        $end = "2023-06-19";
        $pharmacys = [188]; //DB::table('orders')->whereDate('finish', '>=', $start)->whereDate('finish', '<=', $end)->groupBy("pharmacy_id")->pluck('pharmacy_id')->toArray();
        foreach($pharmacys as $pharmacy_id) {
            $orders = DB::table('orders')->where("pharmacy_id",$pharmacy_id)->whereBetween('orders.finish', [$start, $end])->get();
            foreach($orders as $order) {
                $pharmacy=DB::table('pharmacys')->where('pharmacys.id',$order->pharmacy_id)->first();
                $pharmacy_plan=DB::table('plans')->where('plans.id',$pharmacy->plan_id)->first();
                $patient=DB::table('users')->where('users.id',$order->user_id)->first();
                $pharmacy_areas=DB::table('pharmacy_areas')->where('pharmacy_id',$order->pharmacy_id)->where('type',1)->pluck('area_id')->toArray();
                $pharmacy_areas2=DB::table('pharmacy_areas')->where('pharmacy_id',$order->pharmacy_id)->where('type',2)->pluck('area_id')->toArray();
                $pharmacy_areas3=DB::table('pharmacy_areas')->where('pharmacy_id',$order->pharmacy_id)->where('type',3)->pluck('area_id')->toArray();
                $zip_tariff=DB::table('area')->whereIn('area.id',$pharmacy_areas)->whereRaw('ST_CONTAINS(polygon, POINT(?, ?))', \App\Support\GeoPoint::bindings($patient->location))->select("area.id")->first();
                $zip_tariff2=DB::table('area')->whereIn('area.id',$pharmacy_areas2)->whereRaw('ST_CONTAINS(polygon, POINT(?, ?))', \App\Support\GeoPoint::bindings($patient->location))->select("area.id")->first();
                $zip_tariff3=DB::table('area')->whereIn('area.id',$pharmacy_areas3)->whereRaw('ST_CONTAINS(polygon, POINT(?, ?))', \App\Support\GeoPoint::bindings($patient->location))->select("area.id")->first();
                if(!empty($zip_tariff)){
                    if(is_numeric($pharmacy->tariff)) {
                        $tariff = $pharmacy->tariff;
                    } else {
                        $tariff = $pharmacy_plan->tariff;
                    }
                } else if(!empty($zip_tariff2)){
                    if(is_numeric($pharmacy->tariff_area2)) {
                        $tariff = $pharmacy->tariff_area2;
                    } else {
                        $tariff = $pharmacy_plan->tariff_area2;
                    }
                } else if(!empty($zip_tariff3)){
                    if(is_numeric($pharmacy->tariff_area3)) {
                        $tariff = $pharmacy->tariff_area3;
                    } else {
                        $tariff = $pharmacy_plan->tariff_area3;
                    }
                } else {
                    if(is_numeric($pharmacy->tariff_area_more)) {
                        $tariff = $pharmacy->tariff_area_more;
                    } else {
                        $tariff = $pharmacy_plan->tariff_area_more;
                    }
                }
                if(is_numeric($pharmacy->tariff_next_day)) {
                    $tariff_next_day = $pharmacy->tariff_next_day;
                } else {
                    $tariff_next_day = $pharmacy_plan->tariff_next_day;
                }
                if(is_numeric($pharmacy->tariff_same_day)) {
                    $tariff_same_day = $pharmacy->tariff_same_day;
                } else {
                    $tariff_same_day = $pharmacy_plan->tariff_same_day;
                }
                if(is_numeric($pharmacy->tariff_asap)) {
                    $tariff_asap = $pharmacy->tariff_asap;
                } else {
                    $tariff_asap = $pharmacy_plan->tariff_asap;
                }
                if(is_numeric($pharmacy->tariff_after_hours)) {
                    $tariff_after_hours = $pharmacy->tariff_after_hours;
                } else {
                    $tariff_after_hours = $pharmacy_plan->tariff_after_hours;
                }
                if(is_numeric($pharmacy->tariff_fridge)) {
                    $tariff_fridge = $pharmacy->tariff_fridge;
                } else {
                    $tariff_fridge = $pharmacy_plan->tariff_fridge;
                }
                if($order->type_driver==1) {
                    if($order->delivery_time_id==1) {
                        $tariff_res = (floatval($tariff)+floatval($tariff_next_day)+floatval($order->extra_charge_driver));
                    } elseif($order->delivery_time_id==2) {
                        $tariff_res = (floatval($tariff)+floatval($tariff_same_day)+floatval($order->extra_charge_driver));
                    } elseif($order->delivery_time_id==3) {
                        $tariff_res = (floatval($tariff)+floatval($tariff_asap)+floatval($order->extra_charge_driver));
                    } elseif($order->delivery_time_id==4) {
                        $tariff_res = (floatval($tariff)+floatval($tariff_after_hours)+floatval($order->extra_charge_driver));
                    } else {
                        throw new \UnexpectedValueException("Unknown delivery time {$order->delivery_time_id} for order {$order->id}");
                    }
                    if($order->fridge==1) {
                        $tariff_res+= floatval($tariff_fridge);
                    }
                } else {
                    $tariff_res = floatval($tariff);
                }
                DB::table('orders')->where('id', $order->id)->update(['tariff'=>$tariff_res,'invoice_payed'=>'0']);
                if($tariff_res>25) {
                    echo $order->id."<br>";
                }
            }
        }*/
        return "end";
    }

    public function quickbook() {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->can('admin'))) {
            $accessTokenDb = DB::table('quickbook')->where('id',1)->value('data');
            try {
                $accessToken = !empty($accessTokenDb) ? unserialize($accessTokenDb) : null;
            } catch (\Throwable) {
                $accessTokenDb = null;
                $accessToken = null;
            }
            $dataService = DataService::Configure(array(
                'auth_mode' => 'oauth2',
                'ClientID' => config('app.quick_client_id'),
                'ClientSecret' =>  config('app.quick_client_secret'),
                'RedirectURI' => URL::to('/').config('app.quick_oauth_redirect_uri'),
                'scope' => config('app.quick_oauth_scope'),
                'baseUrl' => config('app.quick_baseUrl')
            ));
            $OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();
            assert($OAuth2LoginHelper instanceof OAuth2LoginHelper);
            if(empty($accessTokenDb) || strtotime($accessToken->getRefreshTokenExpiresAt())<time()) {
                $authUrl = $OAuth2LoginHelper->getAuthorizationCodeURL();
                $userInfo="";
            } else {
                $authUrl = NULL;
                $dataService->updateOAuth2Token($accessToken);
                if(strtotime($accessToken->getAccessTokenExpiresAt())<time()){
                    $refreshedAccessTokenObj = $OAuth2LoginHelper->refreshAccessTokenWithRefreshToken($accessToken->getRefreshToken());
                    $dataService->updateOAuth2Token($refreshedAccessTokenObj);
                    DB::table('quickbook')->where('id',1)->update(["data"=>serialize($refreshedAccessTokenObj)]);
                    $accessToken = $refreshedAccessTokenObj;
                }
                $oauthLoginHelper = $dataService->getOAuth2LoginHelper();
                assert($oauthLoginHelper instanceof OAuth2LoginHelper);
                $userInfo = $oauthLoginHelper->getUserInfo($accessToken->getAccessToken());
            }
            $res_view = view('quickbook.index',['alert'=>'','title'=>'Quickbook','br1'=>'Quickbook','br2'=>'Index','authUrl'=>$authUrl,'userInfo'=>$userInfo]);
            return self::renderPage($res_view);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public function quickbookCallback() {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->can('admin'))) {
            $dataService = DataService::Configure(array(
                'auth_mode' => 'oauth2',
                'ClientID' => config('app.quick_client_id'),
                'ClientSecret' =>  config('app.quick_client_secret'),
                'RedirectURI' => URL::to('/').config('app.quick_oauth_redirect_uri'),
                'scope' => config('app.quick_oauth_scope'),
                'baseUrl' => config('app.quick_baseUrl')
            ));
            $OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();
            assert($OAuth2LoginHelper instanceof OAuth2LoginHelper);
            if(request()->query->has('code') && request()->query->has('realmId')) {
                $accessToken = $OAuth2LoginHelper->exchangeAuthorizationCodeForToken(request()->query('code'), request()->query('realmId'));
                $dataService->updateOAuth2Token($accessToken);
                DB::table('quickbook')->where('id',1)->update(["data"=>serialize($accessToken)]);
                return response("<script>window.close();</script>");
            }
            return abort(400, 'QuickBooks authorization was not completed.');
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public function reports() {
        $res_view = view('reports.index',['alert'=>'','title'=>'Reports','br1'=>'Reports','br2'=>'Index']);
        return self::renderPage($res_view);
    }    

    public function reportsBilling() {
        $payments = DB::table('pharmacy_payments')->join("pharmacys","pharmacy_payments.pharmacy_id","=","pharmacys.id")->leftJoin("invoices","pharmacy_payments.invoice_id","=","invoices.id")->select("pharmacy_payments.id","invoices.id as invoice_id","pharmacy_payments.type","pharmacy_payments.created","pharmacys.name","pharmacy_payments.pharmacy_id","pharmacy_payments.transaction_id","invoices.date_from","invoices.date_to","pharmacy_payments.amount","invoices.count");
        $countOnPage=30;
        $max_pages=ceil($payments->count()/$countOnPage);
        $page = self::currentPage();
        $pages = PageLinks::around($page, $max_pages);
        $payments = $payments->orderBy('pharmacy_payments.id','desc')->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
        $client = null;
        foreach($payments as $key=>$payment) {
            if(!empty($payment->transaction_id)){
                try {
                    if($client === null) {
                        $client = new \Square\SquareClient([
                            'accessToken' => config('app.SQUARE_ACCESS_TOKEN'),
                            'environment' => config('app.SQUARE_ENVIRONMENT'),
                        ]);
                    }
                    $api_response = $client->getPaymentsApi()->getPayment($payment->transaction_id);
                    if($api_response->isSuccess()) {
                        $result = $api_response->getResult();
                        if($result->getPayment() !== null) {
                            $payments[$key]->status = $result->getPayment()->getStatus();
                        }
                    }
                } catch (\Throwable $exception) {
                    Log::warning('Unable to retrieve Square payment status.', [
                        'payment_id' => $payment->id,
                        'exception' => $exception,
                    ]);
                }
            }
        }
        $res_view = view('reports.billing',['payments'=>$payments,'pages'=>$pages,'alert'=>'','title'=>'Reports Billing','br1'=>'Reports','br2'=>'Billing']);
        return self::renderPage($res_view);
    }

    public function reportsApps() {
        $res_view = view('reports.apps',['alert'=>'','title'=>'Reports Apps','br1'=>'Reports','br2'=>'Apps']);
        return self::renderPage($res_view);
    }

    public function reportsInvoices() {
        $chartData = DB::table('invoices')->select(DB::raw('CONCAT(date_from," ",date_to) as y'),DB::raw('ROUND(sum((amount+corrections)),2) as a'),DB::raw('count(id) as b'),DB::raw('ROUND(sum(count),2) as c'))->groupBy(DB::raw('CONCAT(date_from," ",date_to)'))->orderBy(DB::raw('CONCAT(date_from," ",date_to)'),'desc')->limit(46)->get();
        $res_view = view('reports.invoices',['chartData'=>$chartData,'alert'=>'','title'=>'Reports Invoices','br1'=>'Reports','br2'=>'Invoices']);
        return self::renderPage($res_view);
    }

    public function reportsDrivers() {
        $res_view = view('reports.drivers',['alert'=>'','title'=>'Reports Drivers','br1'=>'Reports','br2'=>'Drivers']);
        return self::renderPage($res_view);
    }

    public function reportsPharmacies() {
        $res_view = view('reports.pharmacies',['alert'=>'','title'=>'Reports Pharmacies','br1'=>'Reports','br2'=>'Pharmacies']);
        return self::renderPage($res_view);
    }

    public function reportsMap() {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->can('admin'))) {
            $date = date('Y-m-d');
            $polygons = DB::table('area')->select('id','name',DB::raw('ST_AsText(polygon) as polygon'))->get();
            foreach($polygons as $key=>$pol) {
                if(!empty($pol->polygon)) {
                    $polygons[$key]->polygon = \App\Support\WktPolygon::toJsonPoints($pol->polygon);
                    $polygons[$key]->count = DB::table('orders')->join('users', 'orders.user_id', '=', 'users.id')->join('area', function($join) use($pol) {
                        $join->on('area.polygon','!=','orders.id');
                        $join->where('area.id',$pol->id);
                    })->select('orders.id')->whereRaw("ST_CONTAINS(area.polygon, POINT(SUBSTRING_INDEX(users.location,',',1),SUBSTRING_INDEX(users.location,',',-1)))");
                    $polygons[$key]->count=$polygons[$key]->count->whereDate('created', $date);
                    $polygons[$key]->count=$polygons[$key]->count->get()->count();
                } else {
                    $polygons[$key]->polygon = "";
                    $polygons[$key]->count = "0";
                }   
            }
            return view('reports.map',['date'=>$date,'polygons'=>$polygons,'alert'=>'','title'=>'Reports Map','br1'=>'Reports','br2'=>'Map']);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public function reportsMapHandler(Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->can('admin'))) {
            $date = date('Y-m-d',strtotime($request->input('date')));
            $polygons = DB::table('area')->select('id','name',DB::raw('ST_AsText(polygon) as polygon'))->get();
            foreach($polygons as $key=>$pol) {
                if(!empty($pol->polygon)) {
                    $polygons[$key]->polygon = \App\Support\WktPolygon::toJsonPoints($pol->polygon);
                    $polygons[$key]->count = DB::table('orders')->join('users', 'orders.user_id', '=', 'users.id')->join('area', function($join) use($pol) {
                        $join->on('area.polygon','!=','orders.id');
                        $join->where('area.id',$pol->id);
                    })->select('orders.id')->whereRaw("ST_CONTAINS(area.polygon, POINT(SUBSTRING_INDEX(users.location,',',1),SUBSTRING_INDEX(users.location,',',-1)))");
                    $polygons[$key]->count=$polygons[$key]->count->whereDate('created', $date);
                    $polygons[$key]->count=$polygons[$key]->count->get()->count();
                } else {
                    $polygons[$key]->polygon = "";
                    $polygons[$key]->count = "0";
                }   
            }
            return view('reports.map',['date'=>$date,'polygons'=>$polygons,'alert'=>'','title'=>'Reports Map','br1'=>'Reports','br2'=>'Map']);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public function reportsCustomers() {
        $areas = DB::table('area')->select('id','name',DB::raw('ST_AsText(polygon) as polygon'))->get();
        $count_all = 0;
        foreach($areas as $key=>$pol) {
            if(!empty($pol->polygon)) {
                $areas[$key]->count = DB::table('users')->leftJoin('orders', 'orders.user_id', '=', 'users.id')->join('area', function($join) use($pol) {
                    $join->on('area.polygon','!=','orders.id');
                    $join->where('area.id',$pol->id);
                })->where('users.role','user')->where('users.isactive','1')->where('isblocked','0')->select(DB::raw('count(distinct orders.id) as count_order'),DB::raw('count(distinct users.id) as count_all'),DB::raw('count(distinct case when users.os is not null then users.id end) as count_app'))->whereRaw("ST_CONTAINS(area.polygon, POINT(SUBSTRING_INDEX(users.location,',',1),SUBSTRING_INDEX(users.location,',',-1)))")->first();
                $count_all+=$areas[$key]->count->count_all;
            } else {
                $areas[$key]->count = new \StdClass;
                $areas[$key]->count->count_all=0;
                $areas[$key]->count->count_app=0;
                $areas[$key]->count->count_order=0;
            } 
        }
        $res_view = view('reports.customers',['areas'=>$areas,'count_all'=>$count_all,'alert'=>'','title'=>'Reports Customers','br1'=>'Reports','br2'=>'Customers']);
        return self::renderPage($res_view);
    }

    public function feedback() {
        $res_view = view('feedback.index',['alert'=>'','title'=>'Feedback','br1'=>'Feedback','br2'=>'Index']);
        return self::renderPage($res_view);
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

    public function happyHolidays() {
        $res_view = view('happyHolidays.index',['alert'=>'','title'=>'happyHolidays','br1'=>'happyHolidays','br2'=>'Index']);
        return self::renderPage($res_view);
    }
    public function ads() {
        $res_view = view('ads.index',['alert'=>'','title'=>'ads','br1'=>'ads','br2'=>'Index']);
        return self::renderPage($res_view);
    }
    public function payroll() {
        Gate::authorize('admin');
        $res_view = view('payroll.index',['alert'=>'','title'=>'payroll','br1'=>'payroll','br2'=>'Index']);
        return self::renderPage($res_view);
    }
    public function supportChat() {
        return redirect('/chat');
    }

    /**
     * @param int|string $driver_id
     */
    public static function eta_calculate($driver_id,$primary=FALSE) {
        return app(RecalculateDriverEta::class)->handle($driver_id, (bool) $primary);
    }

    /**
     * @param int|string $pharmacy_id
     */
    public function process($pharmacy_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if(Auth::user()->can('manage-pharmacy', $pharmacy_id)) {
            $etas = DB::table('drivers_eta')->select(DB::raw("MAX(id) as id"))->groupBy("driver_id")->pluck('id');
            $users = User::where('role','driver')->where('pharmacy_id',$pharmacy_id)->where('work_now','1')->where('isblocked',0)->leftJoin("routes_priority","routes_priority.driver_id","=","users.id")->leftJoin('routes_priority_logs',function ($join) {
                $join->on('routes_priority_logs.driver_id', '=' , 'users.id') ;
                $join->whereRaw('Date(routes_priority_logs.created) = CURDATE()');
            })->leftJoin('drivers_eta', function($query) use($etas) {
               $query->on('users.id','=','drivers_eta.driver_id')
               ->whereIn('drivers_eta.id',$etas);
            })->having(DB::raw("count(distinct routes_priority.type,routes_priority.type_id)+count(distinct routes_priority_logs.type,routes_priority_logs.type_id)"),">",0)->select("users.id","users.image", "users.car_img", "users.name","users.last_name","users.phone","users.isblocked","users.isactive","users.os",DB::raw("count(distinct routes_priority.type,routes_priority.type_id) as count_delivery"), DB::raw("count(distinct routes_priority_logs.type,routes_priority_logs.type_id) as count_delivered"), "drivers_eta.eta")->groupBy("users.id","users.image","users.car_img","users.name","users.last_name","users.phone","users.isblocked","users.isactive","users.os","drivers_eta.eta");
            if(!empty(request()->query('search'))) {
                $search = request()->query('search');
                $users = $users->where(function($query) use ($search) {
                    $query->where(DB::raw("CONCAT(name, ' ', last_name)"),'LIKE','%'.$search.'%')
                          ->orWhere('email','LIKE','%'.$search.'%')
                          ->orWhere('phone','LIKE','%'.$search.'%')
                          ->orWhere('users.id','=',$search);
                    });
            } else {
                $search='';
            }
            $countOnPage=100;
            $page = self::currentPage();
            $max_pages=ceil(1/$countOnPage);
            $users = $users->orderBy(DB::raw("count(distinct routes_priority.id)+count(distinct routes_priority_logs.id)"),"desc")->orderBy(DB::raw("count(distinct routes_priority.id)"),"asc");
            $pages = PageLinks::around($page, $max_pages);
            $users = $users->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
            $res_view = view('process.drivers',['users'=>$users,'pharmacy_id'=>$pharmacy_id,'pages'=>$pages,'page0'=>$page,'search'=>$search,'title'=>'Process','br1'=>'Drivers','br2'=>'Process','alert'=>'']);
            return self::renderPage($res_view);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    /**
     * @param int|string $pharmacy_id
     * @param int|string $driver_id
     */
    public static function processShow($pharmacy_id,$driver_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if(Auth::user()->hasAnyRole('superadmin', 'admin', 'dispadmin', 'medic')) {
            $driver = DB::table('users')->where('users.id',$driver_id)->leftJoin('drivers_eta', function($query) {
                $query->on('users.id','=','drivers_eta.driver_id')
                ->whereRaw('drivers_eta.id IN (select MAX(a2.id) from drivers_eta as a2 join users as u2 on u2.id = a2.driver_id group by u2.id)');
            })->select("users.*","drivers_eta.eta","drivers_eta.distance")->first();
            $orders = DB::table('orders')->join('users', 'orders.user_id', '=', 'users.id')->join('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id')->join('delivery_times', 'orders.delivery_time_id', '=', 'delivery_times.id')->select('orders.id', 'orders.pharmacy_id', 'orders.user_id', 'users.name as username', 'users.last_name as userlast_name', 'users.phone as userphone', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end as useraddress'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end as userapartment'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end as userzip'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end as userlocation'), 'delivery_times.name as delivery_time', 'pharmacys.location as pharmacylocation','orders.not_delivered','pharmacys.name as pharmacyname', "statuse_id")->whereIn("statuse_id",[1,2,3,7,8,9])->where('orders.pharmacy_id',$pharmacy_id)->where('driver_id',$driver_id)->orderBy("orders.id","desc");
            $orders = $orders->get();
            $patients_locations=array();
            $pharmacy_locations=array();
            foreach($orders as $row) {
                array_push($patients_locations,array('id'=>$row->user_id,'order_id'=>$row->id,'location'=>$row->userlocation));
                array_push($pharmacy_locations,array('id'=>$row->pharmacy_id,'location'=>$row->pharmacylocation));
            }
            $locations = DB::table('locations')->where('user_id', $driver_id)->orderBy("id","desc")->first();
            $routes_priority = DB::table('routes_priority')->where('driver_id', $driver_id)->orderBy("priority","asc")->get();
            $patient_routes_priority=array();
            $pharmacy_routes_priority=array();
            foreach($routes_priority as $row) {
                if($row->type=='patient') {
                    array_push($patient_routes_priority,$row->order_id.','.$row->type_id);
                }
                if($row->type=='pharmacy') {
                    array_push($pharmacy_routes_priority,$row->order_id.','.$row->type_id);
                }
            }
            $patients_locations=array_unique($patients_locations,SORT_REGULAR);
            $pharmacy_locations=array_unique($pharmacy_locations,SORT_REGULAR);
            $routes_priority1 = DB::table('routes_priority')->select("driver_id", DB::raw("GROUP_CONCAT(order_id SEPARATOR ',') as order_id"), "type", "type_id", DB::raw("min(priority) as priority"))->where('driver_id', $driver_id)->where('type', '!=', 'office')->groupBy("type","type_id","driver_id")->orderBy("priority","asc")->get();
            $routes_priority0 = DB::table('routes_priority')->select("driver_id", DB::raw("GROUP_CONCAT(order_id SEPARATOR ',') as order_id"), "type", "type_id", "priority")->where('driver_id', $driver_id)->where('type', 'office')->groupBy("type","type_id","driver_id", "priority")->orderBy("priority","asc")->get();
            $routes_priority = array();
            foreach ($routes_priority1 as $value) {
                array_push($routes_priority,$value);
            }
            foreach ($routes_priority0 as $value) {
                array_push($routes_priority,$value);
            }
            usort($routes_priority, function($a, $b){
                return ($a->priority - $b->priority);
            });
            $show_ids = [];
            $show_priority = [];
            $res_view = view('process.show',['orders'=>$orders,'locations'=>$locations,'driver'=>$driver,'patient_routes_priority'=>$patient_routes_priority,'pharmacy_routes_priority'=>$pharmacy_routes_priority,'routes_priority'=>$routes_priority,'patients_locations'=>$patients_locations,'show_ids'=>$show_ids,'show_priority'=>$show_priority,'pharmacy_locations'=>$pharmacy_locations,'title'=>'Process Detail','br1'=>'Process','br2'=>'Process Detail']);
            return self::renderPage($res_view);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    public function ready_call() {
        if(Auth::user()->hasAnyRole('superadmin', 'admin', 'dispadmin', 'logist')) {
            $user = Auth::user();
            if($user->call_ready=='1') {
                $user->call_ready='0';
            } else {
                $user->call_ready='1';
            }
            $user->save();
            return redirect()->back();
        } else {
            return abort(403, self::$err_perm);
        }
    }










    /**
     * @param int|string $driver_id
     * @param \stdClass $next_route
     */
    static function next_patient_push($driver_id,$next_route){
        return app(NotifyNextPatient::class)->handle($driver_id, $next_route);
    }


}
