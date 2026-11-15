<?php

namespace App\Http\Controllers;

use App\Support\OrderListFilter;
use App\Actions\RepeatOrder;
use App\Models\Order;
use App\Models\Rx;
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


    public function index() {
        $orders = DB::table('orders')->leftJoin('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id')->select('orders.*')->orderBy('orders.id','desc');
        if(Auth::user()->hasRole('driver') && Auth::user()->pharmacy_id > 0) {
            $orders = $orders->where('orders.pharmacy_id', Auth::user()->pharmacy_id);
        }
        $filter = OrderListFilter::fromRequest(request(), ['delivery_time', 'delivery_method', 'pharmacy', 'status', 'facility', 'create_start', 'create_end', 'delivered_start', 'delivered_end', 'need_delivery_start', 'need_delivery_end']);
        $orders = OrderListFilter::apply($orders, $filter);
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
    }

    public function updateStatus(Request $request) {
        if($request->input('remove')>0) {
            Order::query()->where('id', $request->input('order_id'))->delete();
            Rx::query()->where('order_id',$request->input('order_id'))->delete();
        }
        if($request->input('repeat')>0) {
            [$pharmacy_id, $order_id] = app(RepeatOrder::class)->handle($request->input('order_id'));
            return redirect("orders/$pharmacy_id/edit/$order_id");
        }
        return redirect('orders');
    }
}
