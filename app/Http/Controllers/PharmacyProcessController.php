<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersLegacyPages;
use App\Support\PageLinks;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Support\AccessMessages;

/**
 * A pharmacy's process board: its drivers on route and one driver's stops. Formerly actions of
 * LexaAdmin, moved as they were.
 */
class PharmacyProcessController extends Controller
{
    use RendersLegacyPages;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('2fa');
        // Kept from LexaAdmin, whose pages load whole result sets at once.
        ini_set('memory_limit', '-1');
    }

    /**
     * @param int|string $pharmacy_id
     */
    public function process($pharmacy_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, AccessMessages::ACCOUNT_INACTIVE);
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
            $page = $this->currentPage(request());
            $max_pages=ceil(1/$countOnPage);
            $users = $users->orderBy(DB::raw("count(distinct routes_priority.id)+count(distinct routes_priority_logs.id)"),"desc")->orderBy(DB::raw("count(distinct routes_priority.id)"),"asc");
            $pages = PageLinks::around($page, $max_pages);
            $users = $users->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
            $res_view = view('process.drivers',['users'=>$users,'pharmacy_id'=>$pharmacy_id,'pages'=>$pages,'page0'=>$page,'search'=>$search,'title'=>'Process','br1'=>'Drivers','br2'=>'Process','alert'=>'']);
            return $this->page(request(), $res_view);
        } else {
            return abort(403, AccessMessages::NO_PERMISSION);
        }
    }

    /**
     * @param int|string $pharmacy_id
     * @param int|string $driver_id
     */
    public function processShow($pharmacy_id,$driver_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, AccessMessages::ACCOUNT_INACTIVE);
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
            return $this->page(request(), $res_view);
        } else {
            return abort(403, AccessMessages::NO_PERMISSION);
        }
    }
}
