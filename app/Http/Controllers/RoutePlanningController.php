<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersLegacyPages;
use App\Notifications;
use App\Support\PageLinks;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use App\Support\AccessMessages;
use App\Actions\RecalculateDriverEta;

/**
 * Route planning: the lists of orders ready for a route, by order, driver and pharmacy, the
 * page that assigns one order to a driver, and a driver's route page with its save.
 * Formerly the routes* actions of LexaAdmin, moved as they were.
 */
class RoutePlanningController extends Controller
{
    use RendersLegacyPages;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('2fa');
        // The route pages load every open order of a driver or pharmacy at once.
        ini_set('memory_limit', '-1');
    }

    public function orders() {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, AccessMessages::ACCOUNT_INACTIVE);
        }
        if(Auth::user()->hasAnyRole('superadmin', 'admin', 'dispadmin', 'logist', 'medic')) {
            $orders = DB::table('orders')->join('users', 'orders.user_id', '=', 'users.id')->join('statuses', 'orders.statuse_id', '=', 'statuses.id')->join('delivery_methods', 'orders.delivery_method_id', '=', 'delivery_methods.id')->join('delivery_times', 'orders.delivery_time_id', '=', 'delivery_times.id')->join('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id')->select('orders.id', 'orders.created' , 'orders.statuse_id', 'orders.pharmacy_id', 'orders.driver_id', 'users.name as username', 'users.last_name as last_name', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end as useraddress'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end as userapartment'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end as userzip'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end as userlocation'), 'delivery_methods.name as delivery_method', 'delivery_times.name as delivery_time', 'users.phone as userphone', 'pharmacys.name as pharmacyname', 'pharmacys.address as pharmacyaddress','pharmacys.phone as pharmacyphone', 'statuses.name as statusename','statuses.color as statusecolor')->groupBy('orders.id', 'orders.statuse_id', 'orders.created', 'orders.pharmacy_id', 'orders.driver_id', 'delivery_methods.name', 'delivery_times.name', 'users.name', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end'), 'users.phone','pharmacys.name', 'pharmacys.address','pharmacys.phone', 'statuses.name','statuses.color','users.last_name')->orderBy('orders.id','desc');
            $max_page=DB::table('orders')->join('users', 'orders.user_id', '=', 'users.id')->join('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id');
            if(!empty(request()->query('tariff'))) {
                if(request()->query('tariff')=='0.50') {
                    $orders = $orders->where('pharmacys.tariff','0.5');
                    $max_page = $max_page->where('pharmacys.tariff','0.5');
                }
                if(request()->query('tariff')=='1.00') {
                    $orders = $orders->where('pharmacys.tariff','1');
                    $max_page = $max_page->where('pharmacys.tariff','1');
                }
                if(request()->query('tariff')=='5.00-7.00') {
                    $orders = $orders->where('pharmacys.tariff','>=','5')->where('pharmacys.tariff','<=','7');
                    $max_page = $max_page->where('pharmacys.tariff','>=','5')->where('pharmacys.tariff','<=','7');
                }
                if(request()->query('tariff')=='10.00-12.00') {
                    $orders = $orders->where('pharmacys.tariff','>=','10')->where('pharmacys.tariff','<=','12');
                    $max_page = $max_page->where('pharmacys.tariff','>=','10')->where('pharmacys.tariff','<=','12');
                }
            }
            if(!empty(request()->query('statuse'))) {
                $orders = $orders->whereIn('orders.statuse_id',request()->query('statuse'));
                $max_page = $max_page->whereIn('orders.statuse_id',request()->query('statuse'));
            } else {
                request()->query->set('statuse', [1,2,3,6]);
                $orders = $orders->whereIn('orders.statuse_id',request()->query('statuse'));
                $max_page = $max_page->whereIn('orders.statuse_id',request()->query('statuse'));
            }
            if(!empty(request()->query('pharmacy'))) {
                $orders = $orders->where('orders.pharmacy_id',request()->query('pharmacy'));
                $max_page = $max_page->where('orders.pharmacy_id',request()->query('pharmacy'));
            }
            if(!empty(request()->query('search'))) {
                $search = request()->query('search');
                $orders = $orders->where(function($query) use ($search) {
                    $query->where(DB::raw("CONCAT(users.name, ' ', users.last_name)"),'LIKE','%'.$search.'%')
                        ->orWhere(DB::raw("CONCAT(users.last_name, ' ', users.name)"),'LIKE','%'.$search.'%')
                          ->orWhere('pharmacys.name','LIKE','%'.$search.'%')
                          ->orWhere('orders.id','LIKE','%'.$search.'%');
                    });
                $max_page = $max_page->where(function($query) use ($search) {
                    $query->where(DB::raw("CONCAT(users.name, ' ', users.last_name)"),'LIKE','%'.$search.'%')
                        ->orWhere(DB::raw("CONCAT(users.last_name, ' ', users.name)"),'LIKE','%'.$search.'%')
                            ->orWhere('pharmacys.name','LIKE','%'.$search.'%')
                            ->orWhere('orders.id','LIKE','%'.$search.'%');
                    });
            } else {
                $search='';
            }
            $countOnPage=30;
            $max_pages=ceil($max_page->count()/$countOnPage);
            $page = $this->currentPage(request());
            $pages = PageLinks::around($page, $max_pages);
            $orders = $orders->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
            $statuses = DB::table('statuses')->get();
            $pharmacys = DB::table('pharmacys')->get();
            $res_view = view('routes.list',['pages'=>$pages,'page0'=>$page,'search'=>$search,'orders'=>$orders,'statuses'=>$statuses,'pharmacys'=>$pharmacys,'title'=>'Routes','br1'=>'Routes','br2'=>'List']);
            return $this->page(request(), $res_view);
        } else {
            return abort(403, AccessMessages::NO_PERMISSION);
        }
    }

    public function drivers() {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, AccessMessages::ACCOUNT_INACTIVE);
        }
        if(Auth::user()->hasAnyRole('superadmin', 'admin', 'dispadmin', 'logist', 'medic')) {
            $routes_logs = DB::table('routes_priority_logs')->whereRaw('Date(routes_priority_logs.created) = CURDATE()')->select(DB::raw('count(distinct routes_priority_logs.type,routes_priority_logs.type_id) as count_delivered'),'driver_id')->groupBy('driver_id');
            $routes_priority = DB::table('routes_priority')->select(DB::raw('count(distinct routes_priority.type,routes_priority.type_id) as count_delivery'),'driver_id')->groupBy('driver_id');
            $users = User::where('role','driver')->where('isactive',1)->where('isblocked',0)->leftJoinSub($routes_priority,'routes_priority',function ($join) {
                $join->on('routes_priority.driver_id', '=' , 'users.id') ;
            })->leftJoinSub($routes_logs,'routes_priority_logs',function ($join) {
                $join->on('routes_priority_logs.driver_id', '=' , 'users.id') ;
            })->leftJoin('drivers_eta', function($query) {
               $query->on('users.id','=','drivers_eta.driver_id');
            })->where(function ($query) {
                $query->where('users.work_now', '1')
                    ->orWhereRaw('case when routes_priority.count_delivery is null then 0 else routes_priority.count_delivery end > 0');
            })->select("users.id","users.image","users.work_now", "users.car_img", "users.name","users.last_name","users.phone","users.isblocked","users.isactive","users.os", DB::raw("case when routes_priority.count_delivery is null then 0 else routes_priority.count_delivery end as count_delivery"), DB::raw("case when routes_priority_logs.count_delivered is null then 0 else routes_priority_logs.count_delivered end as count_delivered"), "drivers_eta.eta")->groupBy("users.id","users.image","users.work_now", "users.car_img", "users.name","users.last_name","users.phone","users.isblocked","users.isactive","users.os","drivers_eta.eta");
            if(!empty(Auth::user()->zone_id)){
                $users=$users->where(function ($query) {
                    $query->where('users.zone_id',Auth::user()->zone_id)
                        ->orWhereNull('users.zone_id');
                });
            }
            if(!empty(Auth::user()->pharmacy_id)){
                $users=$users->where(function ($query) {
                    $query->where('users.pharmacy_id',Auth::user()->pharmacy_id);
                });
            }
            $users0 = clone $users;
            $users0=$users0->select(DB::raw('count(distinct users.id) as count'));
            if(!empty(request()->query('search'))) {
                $search = request()->query('search');
                $users = $users->where(function($query) use ($search) {
                    $query->where(DB::raw("CONCAT(name, ' ', last_name)"),'LIKE','%'.$search.'%')
                          ->orWhere('email','LIKE','%'.$search.'%')
                          ->orWhere('phone','LIKE','%'.$search.'%')
                          ->orWhere('users.id','=',$search);
                    });
                $users0 = $users0->where(function($query) use ($search) {
                    $query->where(DB::raw("CONCAT(name, ' ', last_name)"),'LIKE','%'.$search.'%')
                            ->orWhere('email','LIKE','%'.$search.'%')
                            ->orWhere('phone','LIKE','%'.$search.'%')
                            ->orWhere('users.id','=',$search);
                    });
            } else {
                $search='';
            }
            $countOnPage=20;
            $page = $this->currentPage(request());
            $total_users = DB::query()->fromSub($users0, 'drivers')->count();
            $max_pages=ceil($total_users/$countOnPage);
            $users = $users->orderBy(DB::raw("(case when routes_priority.count_delivery is null then 0 else routes_priority.count_delivery end)+(case when routes_priority_logs.count_delivered is null then 0 else routes_priority_logs.count_delivered end)"),"desc")->orderBy("routes_priority.count_delivery","asc");
            $pages = PageLinks::around($page, $max_pages);
            $users = $users->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
            $res_arr = ['users'=>$users,'pages'=>$pages,'page0'=>$page,'search'=>$search,'title'=>'Active Drivers','br1'=>'Routes','br2'=>'Drivers','alert'=>''];
            return view('routes.drivers',$res_arr);
        } else {
            return abort(403, AccessMessages::NO_PERMISSION);
        }
    }

    public function pharmacies() {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, AccessMessages::ACCOUNT_INACTIVE);
        }
        if(Auth::user()->hasAnyRole('superadmin', 'admin', 'dispadmin', 'logist')) {
            $orders = DB::table("orders")->join('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id')->where("orders.ready",'1')->where("orders.statuse_id",1)->select(DB::raw("max(orders.created) as created"), "pharmacys.name as pharmacyname", 'orders.pharmacy_id', DB::raw("count(orders.id) as count"))->groupBy("orders.pharmacy_id","pharmacys.name");
            if(!empty(Auth::user()->zone_id)){
                $orders=$orders->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $orders=$orders->get();
            $res_view = view('routes.pharmacys',['orders'=>$orders, 'title'=>'Ready Orders Pharmacys','br1'=>'Routes','br2'=>'Ready Orders Pharmacys','alert'=>'']);
            return $this->page(request(), $res_view);
        } else {
            return abort(403, AccessMessages::NO_PERMISSION);
        }
    }

    /**
     * @param int|string $order_id
     */
    public function showOrder($order_id) {
        Gate::authorize('access-order', $order_id);
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, AccessMessages::ACCOUNT_INACTIVE);
        }
        if(Auth::user()->hasAnyRole('superadmin', 'admin', 'dispadmin', 'logist', 'medic')) {
            $order = DB::table('orders')->join('users', 'orders.user_id', '=', 'users.id')->join('statuses', 'orders.statuse_id', '=', 'statuses.id')->join('delivery_methods', 'orders.delivery_method_id', '=', 'delivery_methods.id')->join('delivery_times', 'orders.delivery_time_id', '=', 'delivery_times.id')->join('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id')->select('orders.id', 'orders.created', 'orders.driver_id', 'orders.statuse_id', 'users.name as username', 'users.last_name as last_name', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end as useraddress'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end as userapartment'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end as userzip'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end as userlocation'), 'users.phone as userphone', 'delivery_methods.name as delivery_method', 'delivery_times.name as delivery_time', 'pharmacys.name as pharmacyname', 'pharmacys.address as pharmacyaddress', 'pharmacys.location as pharmacylocation', 'pharmacys.phone as pharmacyphone', 'statuses.name as statusename','statuses.color as statusecolor')->where('orders.id',$order_id)->groupBy('orders.id', 'orders.statuse_id', 'orders.created', 'orders.driver_id', 'users.name', 'users.last_name', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end'), 'users.phone','pharmacys.name', 'delivery_methods.name', 'delivery_times.name', 'pharmacys.address', 'pharmacys.location','pharmacys.phone', 'statuses.name','statuses.color')->first();
            $locations = DB::table('locations')->join('users', 'locations.user_id', '=', 'users.id')->select('locations.*')->whereIn('locations.id', [DB::raw("select max(`id`) from locations GROUP BY user_id")])->get();
            if($order->driver_id>0) {
                $driver = DB::table('users')->where('id',$order->driver_id)->first();
            } else {
                $driver="";
            }
            $res_view = view('routes.show',['order'=>$order,'locations'=>$locations,'driver'=>$driver,'title'=>'Route Show','br1'=>'Routes','br2'=>'Route Show']);
            return $this->page(request(), $res_view);
        } else {
            return abort(403, AccessMessages::NO_PERMISSION);
        }
    }

    /**
     * @param int|string $order_id
     */
    public function assignOrder(Request $request,$order_id) {
        Gate::authorize('access-order', $order_id);
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, AccessMessages::ACCOUNT_INACTIVE);
        }
        if(Auth::user()->hasAnyRole('superadmin', 'admin', 'dispadmin', 'logist', 'medic')) {
            if($request->input('driver_id')>1) {
                DB::table('orders')->where('id', $order_id)->update(['driver_id'=>$request->input('driver_id'),'statuse_id'=>'2']);
            }
            $order = DB::table('orders')->join('users', 'orders.user_id', '=', 'users.id')->join('statuses', 'orders.statuse_id', '=', 'statuses.id')->join('delivery_methods', 'orders.delivery_method_id', '=', 'delivery_methods.id')->join('delivery_times', 'orders.delivery_time_id', '=', 'delivery_times.id')->join('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id')->select('orders.id', 'orders.created', 'orders.statuse_id', 'delivery_methods.name as delivery_method', 'delivery_times.name as delivery_time', 'orders.driver_id', 'users.name as username', 'users.last_name as last_name', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end as useraddress'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end as userapartment'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end as userzip'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end as userlocation'), 'users.phone as userphone', 'pharmacys.name as pharmacyname', 'pharmacys.address as pharmacyaddress', 'pharmacys.location as pharmacylocation', 'pharmacys.phone as pharmacyphone', 'statuses.name as statusename','statuses.color as statusecolor')->where('orders.id',$order_id)->groupBy('orders.id', 'orders.statuse_id', 'orders.created', 'orders.driver_id', 'users.name', 'users.last_name', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end'), 'users.phone', 'delivery_methods.name', 'delivery_times.name', 'pharmacys.name', 'pharmacys.address', 'pharmacys.location','pharmacys.phone', 'statuses.name','statuses.color')->first();
            $locations = DB::table('locations')->whereIn('id', [DB::raw("select max(`id`) from locations GROUP BY user_id")])->get();
            if($order->driver_id>0) {
                $driver = DB::table('users')->where('id',$order->driver_id)->first();
            } else {
                $driver="";
            }
            $res_view= view('routes.show',['order'=>$order,'locations'=>$locations,'driver'=>$driver,'title'=>'Route Show','br1'=>'Routes','br2'=>'Route Show']);
            return $this->page(request(), $res_view);
        } else {
            return abort(403, AccessMessages::NO_PERMISSION);
        }
    }

    /**
     * @param int|string $driver_id
     */
    public function showDriver($driver_id) {
        if(Auth::user()->role == 'medic') {
            Gate::authorize('manage-pharmacy-driver', [Auth::user()->pharmacy_id, $driver_id]);
        }
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, AccessMessages::ACCOUNT_INACTIVE);
        }
        if(Auth::user()->hasAnyRole('superadmin', 'admin', 'dispadmin', 'logist', 'medic')) {
            $filter = [];
            if(!empty(request()->query('delivery_time')) && !empty(request()->query('filter'))) {
                $filter["delivery_time"] = request()->query('delivery_time');
            } else {
                $filter["delivery_time"] = [];
            }
            if(!empty(request()->query('pharmacy')) && !empty(request()->query('filter'))) {
                $filter["pharmacy"] = request()->query('pharmacy');
            } else {
                $filter["pharmacy"] = [];
            }
            if(!empty(request()->query('status')) && !empty(request()->query('filter'))) {
                $filter["status"] = request()->query('status');
            } else {
                $filter["status"] = [];
            }
            if(!empty(request()->query('start')) && !empty(request()->query('filter'))) {
                $filter["start"] = request()->query('start');
            } else {
                $filter["start"] = "";
            }
            if(!empty(request()->query('end')) && !empty(request()->query('filter'))) {
                $filter["end"] = request()->query('end');
            } else {
                $filter["end"] = "";
            }
            $driver = DB::table('users')->where('users.id',$driver_id)->leftJoin('drivers_eta', function($query) {
                $query->on('users.id','=','drivers_eta.driver_id');
            })->select("users.*","drivers_eta.eta","drivers_eta.distance")->first();
            $orders = DB::table('orders')->join('users', 'orders.user_id', '=', 'users.id')->join('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id')->join('delivery_times', 'orders.delivery_time_id', '=', 'delivery_times.id')->select('orders.id', 'orders.delivery_date', 'orders.delivery_time_range', 'orders.pharmacy_id', 'orders.user_id', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end as useraddress'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end as userapartment'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end as userzip'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end as userlocation'), 'delivery_times.name as delivery_time', 'pharmacys.location as pharmacylocation','pharmacys.name as pharmacyname')->whereIn("statuse_id",[1,2,3,7,8,9]);
            if(!empty($driver->pharmacy_id)) {
                $orders->where('orders.pharmacy_id',$driver->pharmacy_id);
            }
            if(!empty($filter["delivery_time"])) {
                $orders->whereIn("delivery_time_id",$filter["delivery_time"]);
            }
            if(!empty($filter["pharmacy"])) {
                $orders->whereIn('orders.pharmacy_id',$filter["pharmacy"]);
            }
            if(!empty($filter["status"])) {
                $orders->whereIn('orders.statuse_id',$filter["status"]);
            }
            if(!empty($filter["start"]) && !empty($filter["end"])) {
                $orders->whereBetween('orders.delivery_date', [\DateTime::createFromFormat('m/d/Y',$filter["start"])->format('Y-m-d'), \DateTime::createFromFormat('m/d/Y',$filter["end"])->format('Y-m-d')]);
            }
            $orders = $orders->where(function($query) use ($driver_id) {
                $query->whereNull('driver_id')
                      ->orWhere('driver_id',$driver_id);
                });
            $orders = $orders->get();
            $patients_locations=array();
            $pharmacy_locations=array();
            foreach($orders as $row) {
                array_push($patients_locations,array('id'=>$row->user_id,'location'=>$row->userlocation));
                array_push($pharmacy_locations,array('id'=>$row->pharmacy_id,'location'=>$row->pharmacylocation));
            }
            $orders = DB::table('orders')->join('users', 'orders.user_id', '=', 'users.id')->join('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id')->join('delivery_times', 'orders.delivery_time_id', '=', 'delivery_times.id')->select('orders.id', 'orders.delivery_date', 'orders.delivery_time_range', 'orders.pharmacy_id', 'orders.user_id', 'orders.special_instructions', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end as useraddress'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end as userapartment'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end as userzip'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end as userlocation'), 'delivery_times.name as delivery_time', 'pharmacys.location as pharmacylocation','orders.not_delivered','pharmacys.name as pharmacyname')->whereIn("statuse_id",[1,2,3,7,8,9])->orderBy("orders.id","desc");
            if(!empty($driver->pharmacy_id)) {
                $orders->where('orders.pharmacy_id',$driver->pharmacy_id);
            }
            if(!empty($filter["delivery_time"])) {
                $orders->whereIn("delivery_time_id",$filter["delivery_time"]);
            }
            if(!empty($filter["pharmacy"])) {
                $orders->whereIn('orders.pharmacy_id',$filter["pharmacy"]);
            }
            if(!empty($filter["status"])) {
                $orders->whereIn('orders.statuse_id',$filter["status"]);
            }
            if(!empty($filter["start"]) && !empty($filter["end"])) {
                $orders->whereBetween('orders.delivery_date', [\DateTime::createFromFormat('m/d/Y',$filter["start"])->format('Y-m-d'), \DateTime::createFromFormat('m/d/Y',$filter["end"])->format('Y-m-d')]);
            }
            $orders = $orders->where(function($query) use ($driver_id) {
                $query->whereNull('driver_id')
                      ->orWhere('driver_id',$driver_id);
                });
            $orders = $orders->orderBy('users.location',"desc")->get()->keyBy('id');
            $locations = DB::table('locations')->where('user_id', $driver_id)->orderBy("id","desc")->first();
            $offices = DB::table('offices')->get();
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
            $delivery_times = DB::table('delivery_times')->get();
            $order_statuses = DB::table('statuses')->get();
            $routes_priority1 = DB::table('routes_priority')->select("routes_priority.driver_id", DB::raw("GROUP_CONCAT(order_id SEPARATOR ',') as order_id"), DB::raw("GROUP_CONCAT(orders.special_instructions SEPARATOR '. ') as special_instructions"), "routes_priority.type", "routes_priority.type_id", DB::raw("min(routes_priority.eta) as eta"), DB::raw("min(routes_priority.priority) as priority"))->join('orders','orders.id','=','routes_priority.order_id')->where('routes_priority.driver_id', $driver_id)->where('routes_priority.type', '!=', 'office')->groupBy("routes_priority.type","routes_priority.type_id","routes_priority.driver_id")->orderBy("routes_priority.priority","asc")->get();
            $routes_priority0 = DB::table('routes_priority')->select("driver_id", DB::raw("GROUP_CONCAT(order_id SEPARATOR ',') as order_id"), DB::raw("null as special_instructions"), "type", "type_id", "eta", "priority")->where('driver_id', $driver_id)->where('type', 'office')->groupBy("type","type_id","driver_id", "priority")->orderBy("priority","asc")->get();
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
            $route= DB::table('routes_priority')->where('driver_id', $driver_id)->first();
            if(!empty($route)) {
                $type_pay =$route->type_pay;
                if($type_pay==3) {
                    $pay_value = round(floatval($route->pay_value)*DB::table('routes_priority')->where('driver_id', $driver_id)->count(),2);
                } else if($type_pay==2 && !empty($driver->distance)) {
                    $pay_value = round((floatval($route->pay_value)*DB::table('routes_priority')->where('driver_id', $driver_id)->count())/$driver->distance,2);
                } else {
                    $pay_value = -1;
                }
            } else {
                $type_pay=4;
                $pay_value=-1;
            }
            if(!empty($driver->pharmacy_id)) {
                $pharmacys = DB::table('pharmacys')->where("isactive",1)->where("isblocked",0)->where("id",$driver->pharmacy_id)->get();
            } else {
                $pharmacys = DB::table('pharmacys')->where("isactive",1)->where("isblocked",0)->get();
            }
            $pharmacys_list = DB::table('pharmacys')->where("isactive",1)->where("isblocked",0)->select('id','name')->get()->keyBy('id');
            $orders_id=DB::table('routes_priority')->where('driver_id', $driver_id)->select('order_id')->groupBy('order_id')->get();
            $show_ids = [];
            $show_priority = [];
            $res_view = view('routes.driver',['orders'=>$orders,'pharmacys'=>$pharmacys,'pharmacys_list'=>$pharmacys_list,'locations'=>$locations,'driver'=>$driver,'delivery_times'=>$delivery_times,'filter'=>$filter,'offices'=>$offices,'patient_routes_priority'=>$patient_routes_priority,'pharmacy_routes_priority'=>$pharmacy_routes_priority,'pay_value'=>$pay_value,'type_pay'=>$type_pay,'routes_priority'=>$routes_priority,'patients_locations'=>$patients_locations,'order_statuses'=>$order_statuses,'show_ids'=>$show_ids,'show_priority'=>$show_priority,'orders_id'=>$orders_id,'pharmacy_locations'=>$pharmacy_locations,'title'=>'Driver Detail','br1'=>'Routes','br2'=>'Driver Detail']);
            return $this->page(request(), $res_view);
        } else {
            return abort(403, AccessMessages::NO_PERMISSION);
        }
    }

    /**
     * @param int|string $driver_id
     */
    public function updateDriver(Request $request,$driver_id) {
        if(Auth::user()->role == 'medic') {
            // Pharmacy users route only their own drivers and their own pharmacy's orders.
            Gate::authorize('manage-pharmacy-driver', [Auth::user()->pharmacy_id, $driver_id]);
            $orderIds = array_filter(array_merge([$request->input('confirm_order_id')], ...array_map(fn ($ids) => explode(',', (string) $ids), (array) $request->input('order_id'))));
            if($orderIds !== []) {
                Gate::authorize('access-order', array_values($orderIds));
            }
        }
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, AccessMessages::ACCOUNT_INACTIVE);
        }
        if(Auth::user()->hasAnyRole('superadmin', 'admin', 'dispadmin', 'logist', 'medic')) {
            if($request->input('confirm_order_id')>1) {
                DB::table('orders')->where('id', $request->input('confirm_order_id'))->update(['driver_id'=>$driver_id,'statuse_id'=>'2','ready'=>'2']);
                $order = DB::table('orders')->where('id', $request->input('confirm_order_id'))->first();
                $types = ["pharmacy","patient"];
                $count_priority = DB::table('routes_priority')->where('driver_id', $driver_id)->orderBy("priority","desc")->first();
                $data_array = [];
                for ($i=0, $c = count($types); $i<$c; ++$i) {
                    $record = [];
                    $record['driver_id'] = $driver_id;
                    $record['order_id'] = $request->input('confirm_order_id');
                    $record['type'] = $types[$i];
                    if($types[$i]=="pharmacy") {
                        $record['type_id'] = $order->pharmacy_id;
                    } else {
                        $record['type_id'] = $order->user_id;
                    }
                    if(empty($count_priority)) {
                        $count_priority = new \stdClass();
                        $record['priority'] = 1;
                        $count_priority->priority = 1;
                    } else {
                        $record['priority'] = $count_priority->priority+1;
                        $count_priority->priority = $count_priority->priority+1;
                        if (isset($count_priority->type_pay)) {
                            $record['type_pay'] = $count_priority->type_pay;
                            $record['pay_value'] = $count_priority->pay_value;
                        }
                    }
                    $data_array[] = $record;
                }
                DB::table('routes_priority')->insert($data_array);
                Notifications::send_push($driver_id,"QuikMedix","New route has added to your shift (or schedule)");
            } else if(!empty($request->input('close_route'))) {
                $count = DB::table('payouts_driver')->where('driver_id',$driver_id)->where('amount',-1)->count();
                $type_pay = $request->input('type_pay');
                if($type_pay==1) {
                    $pay_value=floatval($request->input('pay_value'));
                } else if($type_pay==4) {
                    $pay_value=90;
                } else if($type_pay==5) {
                    $pay_value=180;
                } else {
                    return redirect()->back()->with('error', 'Unknown pay type.');
                }
                if($count>0) {
                    DB::table('payouts_driver')->where('driver_id',$driver_id)->where('amount',-1)->update(["amount"=>round($pay_value/$count,2)]);
                    return redirect()->back()->with('success', "Successfully, $count orders were paid to the driver at the selected rate.");
                } else {
                    return redirect()->back()->with('error', 'Oh no, no completed orders have been found for the selected driver for the current shift.');
                }
            } else if(!empty($request->input('auto_route'))) {
                $locations = [];
                $routesL = [];
                $type_route = $request->input('type');
                $driver_location_value = DB::table('locations')->where('user_id', $driver_id)->orderBy("id","desc")->value('location');
                $driver_location = explode(",", (string) $driver_location_value);
                if(count($driver_location) < 2) {
                    return redirect()->back()->with('error', 'The driver does not have a valid current location.');
                }
                array_push($locations,[(float)$driver_location[1],(float)$driver_location[0]]);
                array_push($routesL,"driver");
                $routes_priority1 = DB::table('routes_priority')->select("driver_id", DB::raw("GROUP_CONCAT(order_id SEPARATOR ',') as order_id"), "type", "type_id", DB::raw("min(priority) as priority"))->where('driver_id', $driver_id)->where('type', '!=', 'office')->groupBy("type","type_id","driver_id")->orderBy("priority","asc")->get();
                $routes_priority0 = DB::table('routes_priority')->select("driver_id", DB::raw("GROUP_CONCAT(order_id SEPARATOR ',') as order_id"), "type", "type_id", "priority")->where('driver_id', $driver_id)->where('type', 'office')->groupBy("type","type_id","driver_id", "priority")->orderBy("priority","asc")->get();
                $routes_priority = array();
                foreach ($routes_priority1 as $key => $value) {
                    array_push($routes_priority,$value);
                }
                foreach ($routes_priority0 as $key => $value) {
                    array_push($routes_priority,$value);
                }
                usort($routes_priority, function($a, $b){
                    return ($a->priority - $b->priority);
                });
                if(!empty($routes_priority)) {
                    foreach($routes_priority as $key => $routes_priorit){
                        if($routes_priorit->type=='pharmacy') {
                            $dot = DB::table('pharmacys')->where("id",$routes_priorit->type_id)->first();
                            if(!empty($dot) && !empty($dot->location)) {
                                $location = explode(",",$dot->location);
                                if(count($location) >= 2) {
                                    array_push($locations,[(float)$location[1],(float)$location[0]]);
                                    array_push($routesL,$routes_priorit);
                                }
                            }
                        }
                        if($routes_priorit->type=='patient') {
                            $dot = DB::table('users')->where("id",$routes_priorit->type_id)->first();
                            if(!empty($dot) && !empty($dot->location)) {
                                $location = explode(",",$dot->location);
                                if(count($location) >= 2) {
                                    array_push($locations,[(float)$location[1],(float)$location[0]]);
                                    array_push($routesL,$routes_priorit);
                                }
                            }
                        }
                        if($routes_priorit->type=='office') {
                            $dot = DB::table('offices')->where("id",$routes_priorit->type_id)->first();
                            if(!empty($dot) && !empty($dot->location)) {
                                $location = explode(",",$dot->location);
                                if(count($location) >= 2) {
                                    array_push($locations,[(float)$location[1],(float)$location[0]]);
                                    array_push($routesL,$routes_priorit);
                                }
                            }
                        }
                    }
                    $driver = DB::table('users')->where('id',$driver_id)->first();
                    if(empty($driver)) {
                        return redirect()->back()->with('error', 'Driver not found.');
                    }
                    if($driver->transport=='2') {
                        $transport = "cycling-regular";
                    } else {
                        $transport = "driving-car";
                    }
                    $json = json_encode(array("locations"=>$locations,"metrics"=>["distance"]));
                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => "https://api.openrouteservice.org/v2/matrix/$transport",
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => "",
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 30,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => "POST",
                        CURLOPT_POSTFIELDS => $json,
                        CURLOPT_HTTPHEADER => array(
                            "authorization: 5b3ce3597851110001cf6248d92d4ca9f6b1425e8bd2527353e736b0",
                            "cache-control: no-cache",
                            "content-type: application/json",
                        ),
                    ));
                    $response = json_decode(curl_exec($curl));
                    $err = curl_error($curl);
                    if ($err) {
                        return redirect()->back()->with('error', 'Oh no, error automatically built');
                    } else {
                        $distances = $response->distances;
                        $routes_res = [];
                        $used_keys = [];
                        $next_key = 0;
                        while(count($used_keys)<count($routesL)) {
                            $min_val = null; $min_key = null;
                            array_push($used_keys,$next_key);
                            foreach($distances[$next_key] as $key => $val) {
                                if(($val < $min_val || $min_val===null) && !in_array($key,$used_keys)) {
                                    $min_val = $val;
                                    $min_key = $key;
                                }
                                if($type_route=="first" && $next_key==0) {
                                    $min_key = 1;
                                }
                            }
                            if($min_key!==null) {
                                array_push($routes_res,$routesL[$min_key]);
                                $next_key = $min_key;
                            } else {
                                break;
                            }
                        }
                        foreach($routes_res as $key => $routes_priorit){
                            DB::table('routes_priority')->where('driver_id', $driver_id)->where("type",$routes_priorit->type)->where("type_id",$routes_priorit->type_id)->where("priority",$routes_priorit->priority)->update(["priority"=>$key]);
                        }
                        app(RecalculateDriverEta::class)->handle($driver_id,TRUE);
                        return redirect()->back()->with('success', "Successfully the route is automatically built.");
                    }
                }
            } else if($request->input('eta_calculate')>0) {
                app(RecalculateDriverEta::class)->handle($driver_id,TRUE);
                return redirect()->back()->with('success', "Successfully the route ETA was updated.");
            } else if($request->input('region')>0) {
                $filter = [];
                if(!empty($request->input('delivery_time')) && !empty($request->input('filter'))) {
                    $filter["delivery_time"] = $request->input('delivery_time');
                } else {
                    $filter["delivery_time"] = [];
                }
                if(!empty($request->input('status')) && !empty($request->input('filter'))) {
                    $filter["status"] = $request->input('status');
                } else {
                    $filter["status"] = [];
                }
                if(!empty($request->input('start')) && !empty($request->input('filter'))) {
                    $filter["start"] = $request->input('start');
                } else {
                    $filter["start"] = "";
                }
                if(!empty($request->input('end')) && !empty($request->input('filter'))) {
                    $filter["end"] = $request->input('end');
                } else {
                    $filter["end"] = "";
                }
                $patients_id = $request->input('patients_id');
                $pharmacys_id = $request->input('pharmacys_id');
                $count_priority = DB::table('routes_priority')->where('driver_id', $driver_id)->orderBy("priority","desc")->first();
                if(empty($count_priority)) {
                    $count_priority = new \stdClass();
                    $count_priority->priority = 0;
                    $type_pay = 4;
                    $pay_value = -1;
                } else {
                    $type_pay = $count_priority->type_pay;
                    $pay_value = $count_priority->pay_value;
                }
                if(!empty($pharmacys_id)) {
                    foreach($pharmacys_id as $pharmacy_id) {
                        if(!empty($patients_id)) {
                            $orders = DB::table('orders')->where('pharmacy_id', $pharmacy_id)->whereIn('user_id', $patients_id)->whereIn("statuse_id",[1,2,3,7,8,9]);
                            if(!empty($filter["delivery_time"])) {
                                $orders->whereIn("delivery_time_id",$filter["delivery_time"]);
                            }
                            if(!empty($filter["status"])) {
                                $orders->whereIn('orders.statuse_id',$filter["status"]);
                            }
                            if(!empty($filter["start"]) && !empty($filter["end"])) {
                                $orders->whereBetween('orders.delivery_date', [\DateTime::createFromFormat('m/d/Y',$filter["start"])->format('Y-m-d'), \DateTime::createFromFormat('m/d/Y',$filter["end"])->format('Y-m-d')]);
                            }
                            $orders = $orders->where(function($query) use ($driver_id) {
                                $query->whereNull('driver_id')
                                      ->orWhere('driver_id',$driver_id);
                            });
                        } else {
                            $orders = DB::table('orders')->where('pharmacy_id', $pharmacy_id)->whereIn("statuse_id",[1,2,3,7,8,9]);
                            if(!empty($filter["delivery_time"])) {
                                $orders->whereIn("delivery_time_id",$filter["delivery_time"]);
                            }
                            if(!empty($filter["status"])) {
                                $orders->whereIn('orders.statuse_id',$filter["status"]);
                            }
                            if(!empty($filter["start"]) && !empty($filter["end"])) {
                                $orders->whereBetween('orders.delivery_date', [\DateTime::createFromFormat('m/d/Y',$filter["start"])->format('Y-m-d'), \DateTime::createFromFormat('m/d/Y',$filter["end"])->format('Y-m-d')]);
                            }
                            $orders = $orders->where(function($query) use ($driver_id) {
                                $query->whereNull('driver_id')
                                      ->orWhere('driver_id',$driver_id);
                            });
                        }
                        $orders=$orders->get();
                        foreach($orders as $order) {
                            if(empty(DB::table('routes_priority')->where('driver_id', $driver_id)->where('order_id', $order->id)->where('type', "pharmacy")->where('type_id', $order->pharmacy_id)->first())) {
                                $record = [];
                                $record['driver_id'] = $driver_id;
                                $record['order_id'] = $order->id;
                                $record['type'] = "pharmacy";
                                $record['type_id'] = $order->pharmacy_id;
                                $record['priority'] = $count_priority->priority+1;
                                $record['type_pay'] = $type_pay;
                                $record['pay_value'] = $pay_value;
                                $count_priority->priority = $count_priority->priority+1;
                                $data_array[] = $record;
                                DB::table('orders')->where('id', $order->id)->update(['driver_id'=>$driver_id,'ready'=>'2']);
                            }
                        }
                    }
                }
                if(!empty($patients_id)) {
                    foreach($patients_id as $patient_id) {
                        $orders = DB::table('orders')->where('user_id', $patient_id)->whereIn("statuse_id",[1,2,3,7,8,9]);
                        if(!empty($filter["delivery_time"])) {
                            $orders->whereIn("delivery_time_id",$filter["delivery_time"]);
                        }
                        if(!empty($filter["status"])) {
                            $orders->whereIn('orders.statuse_id',$filter["status"]);
                        }
                        if(!empty($filter["start"]) && !empty($filter["end"])) {
                            $orders->whereBetween('orders.delivery_date', [\DateTime::createFromFormat('m/d/Y',$filter["start"])->format('Y-m-d'), \DateTime::createFromFormat('m/d/Y',$filter["end"])->format('Y-m-d')]);
                        }
                        $orders = $orders->where(function($query) use ($driver_id) {
                            $query->whereNull('driver_id')
                                  ->orWhere('driver_id',$driver_id);
                        });
                        $orders=$orders->get();
                        foreach($orders as $order) {
                            if(empty(DB::table('routes_priority')->where('driver_id', $driver_id)->where('order_id', $order->id)->where('type', "patient")->where('type_id', $order->user_id)->first())) {
                                $record = [];
                                $record['driver_id'] = $driver_id;
                                $record['order_id'] = $order->id;
                                $record['type'] = "patient";
                                $record['type_id'] = $order->user_id;
                                $record['priority'] = $count_priority->priority+1;
                                $record['type_pay'] = $type_pay;
                                $record['pay_value'] = $pay_value;
                                $count_priority->priority = $count_priority->priority+1;
                                $data_array[] = $record;
                                DB::table('orders')->where('id', $order->id)->update(['driver_id'=>$driver_id,'ready'=>'2']);
                            }
                        }
                    }
                }
                if(!empty($data_array)) {
                    DB::table('routes_priority')->insert($data_array);
                }
                //DB::table('users')->where('id', $driver_id)->update(['route_status'=>'updated']);
                Notifications::send_push($driver_id,"QuikMedix","Your schedule was updated! Please check");
            } else {
                $stops = self::validateRouteStops($request);
                DB::transaction(function () use ($request, $driver_id, $stops): void {
                    DB::table('routes_priority')->where('driver_id', $driver_id)->delete();
                    DB::table('orders')->where('driver_id', $driver_id)->whereIn('statuse_id', [1, 2, 3, 7, 8, 9])->update(['driver_id' => null]);
                    $records = [];
                    foreach ($stops as $index => $stop) {
                        foreach ($stop['order_ids'] as $orderId) {
                            DB::table('orders')->where('id', $orderId)->update(['driver_id' => $driver_id, 'ready' => '2']);
                            $records[] = [
                                'driver_id' => $driver_id,
                                'order_id' => $orderId,
                                'type' => $stop['type'],
                                'type_id' => $stop['type_id'],
                                'priority' => $index + 1,
                            ];
                        }
                    }
                    if ($records !== []) {
                        DB::table('routes_priority')->insert($records);
                    }
                    $typePay = $request->input('type_pay');
                    $count = count($records);
                    $payValue = match ((int) $typePay) {
                        3 => $count === 0 ? 1 : (float) $request->input('pay_value') / $count,
                        2 => $count === 0 ? 1 : (float) $request->input('pay_value') * 100 / $count,
                        default => -1,
                    };
                    DB::table('routes_priority')->where('driver_id', $driver_id)->update(['type_pay' => $typePay, 'pay_value' => $payValue]);
                });
                app(RecalculateDriverEta::class)->handle($driver_id);
                Notifications::send_push($driver_id,"QuikMedix","Your schedule was updated! Please check");
            }
            return redirect("/routes-list/driver/$driver_id");
        } else {
            return abort(403, AccessMessages::NO_PERMISSION);
        }
    }

    /**
     * @return list<array{order_ids: list<string>, type: string, type_id: int}>
     */
    private static function validateRouteStops(Request $request): array
    {
        $validated = $request->validate([
            'order_id' => ['required', 'array'],
            'order_id.*' => ['nullable', 'regex:/^\d+(,\d+)*$/'],
            'type' => ['required', 'array'],
            'type.*' => ['nullable', 'in:pharmacy,patient,office'],
            'type_id' => ['required', 'array'],
            'type_id.*' => ['nullable', 'integer', 'min:1'],
        ]);
        if (array_keys($validated['order_id']) !== array_keys($validated['type']) || array_keys($validated['type']) !== array_keys($validated['type_id'])) {
            throw ValidationException::withMessages(['order_id' => 'The route stops are incomplete. Reload the route and try again.']);
        }

        $stops = [];
        foreach ($validated['type'] as $index => $type) {
            $orderIds = (string) ($validated['order_id'][$index] ?? '');
            $typeId = $validated['type_id'][$index];
            if (empty($type) && $orderIds === '' && empty($typeId)) {
                continue;
            }
            if ($orderIds === '' || empty($type) || empty($typeId)) {
                throw ValidationException::withMessages([
                    'order_id.'.$index => $type === 'office' ? 'Select at least one order for each office stop before saving.' : 'Select an order and destination for each route stop before saving.',
                ]);
            }
            $selectedIds = array_values(array_unique(explode(',', $orderIds)));
            if (DB::table('orders')->whereIn('id', $selectedIds)->count() !== count($selectedIds)) {
                throw ValidationException::withMessages(['order_id.'.$index => 'One of the selected orders no longer exists. Reload the route and try again.']);
            }
            if ($type === 'office' && ! DB::table('offices')->where('id', $typeId)->exists()) {
                throw ValidationException::withMessages(['type_id.'.$index => 'Choose a valid office for this stop.']);
            }
            $stops[] = ['order_ids' => $selectedIds, 'type' => $type, 'type_id' => (int) $typeId];
        }

        return $stops;
    }
}
