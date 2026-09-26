<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\LoadsOrderLookups;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Every pharmacy's orders in one list, for QuikMedix staff and couriers. Formerly ordersList of LexaAdmin.
 */
class AllOrdersController extends Controller
{
    use LoadsOrderLookups;

    public function __construct()
    {
        $this->middleware(['auth', '2fa', 'active']);
    }

    public function index() {
        if(Auth::user()->hasAnyRole('superadmin', 'admin', 'dispadmin', 'driver', 'logist')) {
            $orders = DB::table('orders')->leftJoin('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id')->select('orders.*')->orderBy('orders.id','desc');
            if(Auth::user()->role == 'driver' && Auth::user()->pharmacy_id > 0) {
                $orders = $orders->where('orders.pharmacy_id', Auth::user()->pharmacy_id);
            }
            $filter = [];
            if(!empty(request()->query('delivery_time')) && !empty(request()->query('filter'))) {
                $filter["delivery_time"] = request()->query('delivery_time');
            } else {
                $filter["delivery_time"] = [];
            }
            if(!empty(request()->query('delivery_method')) && !empty(request()->query('filter'))) {
                $filter["delivery_method"] = request()->query('delivery_method');
            } else {
                $filter["delivery_method"] = [];
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
            if(!empty(request()->query('facility')) && !empty(request()->query('filter'))) {
                $filter["facility"] = request()->query('facility');
            } else {
                $filter["facility"] = [];
            }
            if(!empty(request()->query('create_start')) && !empty(request()->query('filter'))) {
                $filter["create_start"] = request()->query('create_start');
            } else {
                $filter["create_start"] = "";
            }
            if(!empty(request()->query('create_end')) && !empty(request()->query('filter'))) {
                $filter["create_end"] = request()->query('create_end');
            } else {
                $filter["create_end"] = "";
            }
            if(!empty(request()->query('delivered_start')) && !empty(request()->query('filter'))) {
                $filter["delivered_start"] = request()->query('delivered_start');
            } else {
                $filter["delivered_start"] = "";
            }
            if(!empty(request()->query('delivered_end')) && !empty(request()->query('filter'))) {
                $filter["delivered_end"] = request()->query('delivered_end');
            } else {
                $filter["delivered_end"] = "";
            }
            if(!empty(request()->query('need_delivery_start')) && !empty(request()->query('filter'))) {
                $filter["need_delivery_start"] = request()->query('need_delivery_start');
            } else {
                $filter["need_delivery_start"] = "";
            }
            if(!empty(request()->query('need_delivery_end')) && !empty(request()->query('filter'))) {
                $filter["need_delivery_end"] = request()->query('need_delivery_end');
            } else {
                $filter["need_delivery_end"] = "";
            }
            if(!empty($filter["delivery_time"])) {
                $orders = $orders->whereIn("delivery_time_id",$filter["delivery_time"]);
            }
            if(!empty($filter["delivery_method"])) {
                $orders = $orders->whereIn("delivery_method_id",$filter["delivery_method"]);
            }
            if(!empty($filter["pharmacy"])) {
                $orders = $orders->whereIn('orders.pharmacy_id',$filter["pharmacy"]);
            }
            if(!empty($filter["status"])) {
                $orders = $orders->whereIn('orders.statuse_id',$filter["status"]);
            }
            if(count($filter["facility"])==1) {
                if($filter["facility"][0]==0) {
                    $orders = $orders->whereNull('orders.facility');
                } else {
                    $orders = $orders->whereNotNull('orders.facility');
                }
            }
            if(!empty($filter["create_start"]) && !empty($filter["create_end"])) {
                $orders = $orders->whereBetween('orders.created', [\DateTime::createFromFormat('m/d/Y',$filter["create_start"])->format('Y-m-d'), \DateTime::createFromFormat('m/d/Y',$filter["create_end"])->format('Y-m-d')]);
            }
            if(!empty($filter["delivered_start"]) && !empty($filter["delivered_end"])) {
                $orders = $orders->whereBetween('orders.finish', [\DateTime::createFromFormat('m/d/Y',$filter["delivered_start"])->format('Y-m-d'), \DateTime::createFromFormat('m/d/Y',$filter["delivered_end"])->format('Y-m-d')]);
            }
            if(!empty($filter["need_delivery_start"]) && !empty($filter["need_delivery_end"])) {
                $orders = $orders->whereBetween('orders.delivery_date', [\DateTime::createFromFormat('m/d/Y',$filter["need_delivery_start"])->format('Y-m-d'), \DateTime::createFromFormat('m/d/Y',$filter["need_delivery_end"])->format('Y-m-d')]);
            }
            if(!empty(request()->query('without_sign'))) {
                $orders = $orders->where('statuse_id','4')->where('orders.signature','1')->whereNull('orders.signature_photo')->whereDate('orders.created', '>', date('Y-m-d', strtotime('now -1 month')));
            }
            if(!empty(request()->query('same_day'))) {
                $orders = $orders->where('delivery_time_id','2')->whereDate('orders.created', '=', date('Y-m-d', strtotime('now')));
            }
            if(!empty(request()->query('asap'))) {
                $orders = $orders->whereIn('delivery_time_id',['3','4'])->whereDate('orders.created', '=', date('Y-m-d', strtotime('now')));
            }
            if(!empty(request()->query('without_photo'))) {
                $orders = $orders->where('statuse_id','4')->whereNull('orders.drop_off_photo')->whereDate('orders.created', '>', date('Y-m-d', strtotime('now -1 month')));
            }
            if(!empty(request()->query('copay_process'))) {
                $orders = $orders->where('statuse_id','4')->where('statuse_copay','2')->where('copay','>','0')->whereDate('orders.created', '>', date('Y-m-d', strtotime('now -1 month')));
            }
            if(!empty(request()->query('without_driver'))) {
                $orders = $orders->where('statuse_id','4')->whereNull('driver_id')->whereDate('orders.created', '>', date('Y-m-d', strtotime('now -1 month')));
            }
            if(!empty(request()->query('orders_without_notes'))) {
                $orders = $orders->whereIn('orders.statuse_id', ['8','9','10'])->leftJoin('notes','notes.order_id','=','orders.id')->whereNull('notes.id')->whereDate('orders.created', '>', date('Y-m-d', strtotime('now -1 month')));
            }
            if(!empty(Auth::user()->zone_id)){
                $orders=$orders->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            if(!empty(request()->query('search'))) {
                $search = request()->query('search');
                $orders = $orders->leftJoin('users', 'orders.user_id', '=', 'users.id')->where(function($query) use ($search) {
                    $query->where(DB::raw("CONCAT(users.name, ' ', users.last_name)"),'LIKE','%'.$search.'%')
                        ->orWhere(DB::raw("CONCAT(users.last_name, ' ', users.name)"),'LIKE','%'.$search.'%')
                          ->orWhere('pharmacys.name','LIKE','%'.$search.'%')
                          ->orWhere('orders.copay','LIKE','%'.$search.'%')
                          ->orWhere('orders.id','LIKE','%'.$search.'%');
                    });
                $orders0 = clone $orders;
                $orders0 = $orders0->select(DB::raw('count(orders.id) as count'))->first();
                $orders=$orders->select('orders.*');
            } else {
                $search='';
                $orders0 = clone $orders;
                $orders0 = $orders0->select(DB::raw('count(orders.id) as count'))->first();
            }
            $countOnPage=30;
            $max_pages=ceil($orders0->count/$countOnPage);
            $page=1;
            if(!empty(request()->query('page'))) {
                $page=intval(request()->query('page'));
            }
            $pages = array();
            if($page>2){
                array_push($pages,array("id"=>$page-2,"class"=>'btn-primary'));
            }
            if($page>1){
                array_push($pages,array("id"=>$page-1,"class"=>'btn-primary'));
            }
            array_push($pages,array("id"=>$page,"class"=>'btn-outline-primary'));
            if($page+1<=$max_pages){
                array_push($pages,array("id"=>$page+1,"class"=>'btn-primary'));
            }
            if($page+2<=$max_pages){
                array_push($pages,array("id"=>$page+2,"class"=>'btn-primary'));
            }
            $orders = $orders->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
            $statuses = self::get_statuses();
            $statuses_copay = self::get_statuses_copay();
            $delivery_methods = self::get_delivery_methods();
            $delivery_times = self::get_delivery_times();
            $pharmacys = DB::table('pharmacys')->get()->keyBy('id');
            $patients = self::get_patients($orders->pluck('user_id')->toArray());
            $drivers = self::get_drivers($orders->pluck('driver_id')->toArray());
            $pharmacy_id = NULL;
            $res_arr = ['pages'=>$pages,'page0'=>$page,'filter'=>$filter,'search'=>$search,'orders'=>$orders,'statuses'=>$statuses,'statuses_copay'=>$statuses_copay,'delivery_methods'=>$delivery_methods,'delivery_times'=>$delivery_times,'pharmacys'=>$pharmacys,'patients'=>$patients,'drivers'=>$drivers,'pharmacy_id'=>$pharmacy_id,'title'=>'Orders','br1'=>'Orders','br2'=>'List'];
            return view('orders.list',$res_arr);
        } else {
            return abort(403, LexaAdmin::$err_perm);
        }
    }

    public function updateStatus(Request $request) {
        if((Auth::user()->can('admin'))) {
            if($request->input('remove')>0) {
                DB::table('orders')->where('id', $request->input('order_id'))->delete();
                DB::table('rxs')->where('order_id',$request->input('order_id'))->delete();
            }
            if($request->input('repeat')>0) {
                $order = DB::table('orders')->where('id', $request->input('order_id'))->first();
                $order->id = NULL;
                $order->medic_id = Auth::id();
                $order->statuse_id = 1;
                $order->finish = NULL;
                $order->rating = NULL;
                $order->statuse_copay = 1;
                $order->tariff = NULL;
                $order->drop_off_photo = NULL;
                $order->signature_photo = NULL;
                $order->signature_type = NULL;
                $order->invoice_payed = NULL;
                $order->eta = NULL;
                $order->delivery_date = date("Y-m-d",strtotime(date("Y-m-d H:i:s")." +1 day"));
                $pharmacy_id = $order->pharmacy_id;
                $order = json_encode($order);
                $order = json_decode($order,true);
                $order_id = DB::table('orders')->insertGetId($order);
                $rxs = DB::table('rxs')->where('order_id',$request->input('order_id'))->get();
                foreach($rxs as $rx) {
                    $rx->id = NULL;
                    $rx->order_id = $order_id;
                    $rx = json_encode($rx);
                    $rx = json_decode($rx,true);
                    DB::table('rxs')->insert($rx);
                }
                return redirect("orders/$pharmacy_id/edit/$order_id");
            }
            return redirect('orders');
        } else {
            return abort(403, LexaAdmin::$err_perm);
        }
    }
}
