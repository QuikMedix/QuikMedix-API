<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\LoadsOrderLookups;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Printable order sheets: the day's deliveries and pickup tickets. Formerly the orders*Print actions of LexaAdmin.
 */
class OrderPrintController extends Controller
{
    use LoadsOrderLookups;

    public function __construct()
    {
        $this->middleware(['auth', '2fa', 'active']);
    }

    public function day() {
        if((Auth::user()->can('admin'))) {
            $date = date('Y-m-d', strtotime((string) request()->query('date')) ?: time());
            $orders = DB::table('orders')->join('users', 'orders.user_id', '=', 'users.id')->join('users as users2', 'orders.driver_id', '=', 'users2.id')->join('statuses', 'orders.statuse_id', '=', 'statuses.id')->join('delivery_methods', 'orders.delivery_method_id', '=', 'delivery_methods.id')->join('delivery_times', 'orders.delivery_time_id', '=', 'delivery_times.id')->join('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id')->select('orders.id', 'orders.created' , 'orders.driver_id', 'orders.count_bags', 'orders.statuse_id', 'orders.copay', 'orders.finish', 'users.apartment as userapartment', 'users2.name as driver_name', 'users2.last_name as driver_last_name', 'orders.drop_off_photo', 'orders.signature_photo', 'orders.pharmacy_id', 'users.name as username', 'users.last_name as last_name', 'delivery_methods.name as delivery_method', 'delivery_times.name as delivery_time', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end as useraddress'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end as userapartment'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end as userzip'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end as userlocation'), 'users.phone as userphone', 'pharmacys.name as pharmacyname', 'pharmacys.address as pharmacyaddress','pharmacys.phone as pharmacyphone', 'statuses.name as statusename','statuses.color as statusecolor')->where('orders.statuse_id',4)->whereDate('finish', $date)->groupBy('orders.id', 'orders.drop_off_photo', 'orders.signature_photo', 'orders.statuse_id', 'orders.driver_id', 'users.apartment', 'orders.count_bags', 'orders.finish', 'orders.created', 'users2.name', 'users2.last_name', 'delivery_methods.name', 'delivery_times.name', 'orders.copay', 'orders.pharmacy_id', 'users.name', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end'),'users.phone','pharmacys.name', 'pharmacys.address','pharmacys.phone', 'statuses.name','statuses.color','users.last_name')->orderBy('orders.id','desc')->get();
            foreach($orders as $key=>$order) {
                $rxs = DB::table('rxs')->where('order_id',$order->id)->get();
                foreach($rxs as $key0=>$rx) {
                    if(empty($rx->rx_id)) {
                        $rxs[$key0]->rx_id = 'null';
                    }
                }
                $orders[$key]->rxs=$rxs;
                if($order->driver_id>0) {
                    $driver = DB::table('users')->where('id',$order->driver_id)->first();
                } else {
                    $driver="";
                }
                $orders[$key]->driver=$driver;
            }
            return view('orders.print',['orders'=>$orders]);
        } else if (Auth::user()->role == 'medic') {
            $date = date('Y-m-d', strtotime((string) request()->query('date')) ?: time());
            $orders = DB::table('orders')->join('users', 'orders.user_id', '=', 'users.id')->join('users as users2', 'orders.driver_id', '=', 'users2.id')->join('statuses', 'orders.statuse_id', '=', 'statuses.id')->join('delivery_methods', 'orders.delivery_method_id', '=', 'delivery_methods.id')->join('delivery_times', 'orders.delivery_time_id', '=', 'delivery_times.id')->join('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id')->select('orders.id', 'orders.created' , 'orders.driver_id', 'orders.count_bags', 'orders.statuse_id', 'orders.copay', 'orders.finish', 'users.apartment as userapartment', 'users2.name as driver_name', 'users2.last_name as driver_last_name', 'orders.drop_off_photo', 'orders.signature_photo', 'orders.pharmacy_id', 'users.name as username', 'users.last_name as last_name', 'delivery_methods.name as delivery_method', 'delivery_times.name as delivery_time', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end as useraddress'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end as userapartment'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end as userzip'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end as userlocation'), 'users.phone as userphone', 'pharmacys.name as pharmacyname', 'pharmacys.address as pharmacyaddress','pharmacys.phone as pharmacyphone', 'statuses.name as statusename','statuses.color as statusecolor')->where('orders.statuse_id',4)->where('orders.pharmacy_id',Auth::user()->pharmacy_id)->whereDate('finish', $date)->groupBy('orders.id', 'orders.drop_off_photo', 'orders.signature_photo', 'orders.statuse_id', 'orders.driver_id', 'users.apartment','users2.name', 'users2.last_name', 'orders.count_bags', 'orders.finish', 'orders.created', 'delivery_methods.name', 'delivery_times.name', 'orders.copay', 'orders.pharmacy_id', 'users.name', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end'), 'users.phone','pharmacys.name', 'pharmacys.address','pharmacys.phone', 'statuses.name','statuses.color','users.last_name')->orderBy('orders.id','desc')->get();
            foreach($orders as $key=>$order) {
                $rxs = DB::table('rxs')->where('order_id',$order->id)->get();
                $orders[$key]->rxs=$rxs;
                if($order->driver_id>0) {
                    $driver = DB::table('users')->where('id',$order->driver_id)->first();
                } else {
                    $driver="";
                }
                $orders[$key]->driver=$driver;
            }
            return view('orders.print',['orders'=>$orders]);
        } else {
            return abort(403, LexaAdmin::$err_perm);
        }
    }

    public function ticket() {
        if(Auth::user()->hasAnyRole('medic', 'superadmin', 'admin', 'dispadmin', 'driver', 'logist')) {
            $order_id = request()->query('order_id');
            $order = DB::table('orders')->where('id',$order_id)->first();
            if(empty($order)) {
                return abort(404, 'Order not found');
            }
            Gate::authorize('access-order', $order->id);
            $rxs = DB::table('rxs')->where('order_id',$order->id)->get();
            foreach($rxs as $key0=>$rx) {
                if(empty($rx->rx_id)) {
                    $rxs[$key0]->rx_id = 'null';
                }
            }
            $order->rxs=$rxs;
            $pharmacys = self::get_pharmacys([$order->pharmacy_id]);
            $patients = self::get_patients([$order->user_id]);
            $wishs = self::get_wishs();
            $res_arr = ['order'=>$order,'pharmacys'=>$pharmacys,'patients'=>$patients,'wishs'=>$wishs];
            return view('orders.ticket',$res_arr);
        } else {
            return abort(403, LexaAdmin::$err_perm);
        }
    }

    public function tickets() {
        if(Auth::user()->hasAnyRole('medic', 'superadmin', 'admin', 'dispadmin', 'driver', 'logist')) {
            if(Auth::user()->pharmacy_id>0) {
                $orders = DB::table('orders')->where('orders.pharmacy_id',Auth::user()->pharmacy_id)->where('orders.statuse_id',1);
            } else {
                $orders = DB::table('orders')->where('orders.statuse_id',1);
            }
            if(!empty(Auth::user()->zone_id)){
                $orders=$orders->join('pharmacys','pharmacys.id','=','orders.pharmacy_id')->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $orders=$orders->get();
            foreach($orders as $key=>$order){
                $rxs = DB::table('rxs')->where('order_id',$order->id)->count();
                $orders[$key]->rxs_count=$rxs;
            }
            $pharmacys = self::get_pharmacys($orders->pluck('pharmacy_id')->all());
            $patients = self::get_patients($orders->pluck('user_id')->all());
            $wishs = self::get_wishs();
            $res_arr = ['orders'=>$orders,'pharmacys'=>$pharmacys,'patients'=>$patients,'wishs'=>$wishs];
            return view('orders.tickets',$res_arr);
        } else {
            return abort(403, LexaAdmin::$err_perm);
        }
    }
}
