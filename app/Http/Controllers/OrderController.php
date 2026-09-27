<?php

namespace App\Http\Controllers;

use App\Actions\RotateSignature;
use App\Actions\SendOrderStatusToBestRx;
use App\Http\Controllers\Concerns\LoadsOrderLookups;
use App\Notifications;
use App\Support\WktPolygon;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Zadarma_API\Api as Zadarma_API;

/**
 * A pharmacy's orders: the list, create, show, edit, preview, statistics and call recordings.
 * Formerly the orders* actions of LexaAdmin.
 */
class OrderController extends Controller
{
    use LoadsOrderLookups;

    public function __construct()
    {
        $this->middleware(['auth', '2fa', 'active']);
    }

    /**
     * @param int|string $pharmacy_id
     */
    public function index($pharmacy_id) {
        $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
        if(!empty($pharmacy)) {
            if((Auth::user()->role == 'medic' && Auth::user()->pharmacy_id==$pharmacy_id) || (Auth::user()->role == 'sale' && Auth::user()->id==$pharmacy->ref_id) || ((Auth::user()->can('admin')))) {
                $orders = DB::table('orders')->where('orders.pharmacy_id',$pharmacy_id)->leftJoin('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id')->select('orders.*')->orderBy('orders.id','desc');
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
                if(!empty(request()->query('micromerchant')) && !empty(request()->query('filter'))) {
                    $filter["micromerchant"] = request()->query('micromerchant');
                } else {
                    $filter["micromerchant"] = "";
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
                if(!empty($filter["micromerchant"])) {
                    $orders = $orders->where('orders.merchantOrder','1');
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
                $res_arr = ['pages'=>$pages,'page0'=>$page,'filter'=>$filter,'search'=>$search,'orders'=>$orders,'statuses'=>$statuses,'statuses_copay'=>$statuses_copay,'delivery_methods'=>$delivery_methods,'delivery_times'=>$delivery_times,'pharmacys'=>$pharmacys,'patients'=>$patients,'drivers'=>$drivers,'pharmacy_id'=>$pharmacy_id,'title'=>'Orders','br1'=>'Orders','br2'=>'List'];
                return view('orders.list',$res_arr);
            } else {
                return abort(403, LexaAdmin::$err_perm);
            }
        } else {
            return abort(404, "Not found pharmacy");
        }
    }

    /**
     * @param int|string $pharmacy_id
     */
    public function updateStatus(Request $request,$pharmacy_id) {
        if($request->filled('order_id')) {
            Gate::authorize('access-order', $request->input('order_id'));
        }
        if(Auth::user()->can('manage-pharmacy', $pharmacy_id)) {
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
            return redirect("orders/$pharmacy_id");
        } else {
            return abort(403, LexaAdmin::$err_perm);
        }
    }

    /**
     * @param int|string $pharmacy_id
     */
    public function create($pharmacy_id) {
        if(Auth::user()->pharmacy_balance_ban()) {
            return redirect("billing/".Auth::user()->pharmacy_id, 302);
        }
        if(Auth::user()->can('manage-pharmacy', $pharmacy_id)) {
            $pharmacy = DB::table('pharmacys')->where('id', $pharmacy_id)->first();
            abort_unless($pharmacy, 404, 'Pharmacy not found.');
            $users = DB::table('users')->where('role', 'user')->where('pharmacy_id', $pharmacy_id)->select('users.id','users.name','users.last_name','users.phone')->get();
            $facilitys = DB::table('users')->where('role', 'facility')->where('pharmacy_id', $pharmacy_id)->select('users.id','users.name','users.last_name','users.phone')->get();
            $medicines = DB::table('medicines')->get();
            $drivers = DB::table('users')->where('role', 'driver')->where("pharmacy_id",$pharmacy_id)->get();
            $delivery_methods = DB::table('delivery_methods')->get();
            $delivery_times = DB::table('delivery_times')->get();
            $time_ranges = ["9:00 AM","10:00 AM","11:00 AM","12:00 PM", "1:00 PM","2:00 PM","3:00 PM","4:00 PM","5:00 PM","6:00 PM","7:00 PM","8:00 PM","9:00 PM","10:00 PM","11:00 PM","12:00 AM"];
            $res_view = view('orders.add',['users'=>$users,'facilitys'=>$facilitys,'medicines'=>$medicines,'drivers'=>$drivers,'pharmacy'=>$pharmacy,'time_ranges'=>$time_ranges,'delivery_methods'=>$delivery_methods, 'delivery_times'=>$delivery_times,'title'=>'Order Add','br1'=>'Orders','br2'=>'Order Add','alert'=>'']);
            if(request()->query->has('ajax')) {
                return $res_view->renderSections();
            } else {
                return $res_view;
            }
        } else {
            return abort(403, LexaAdmin::$err_perm);
        }
    }

    /**
     * @param int|string $pharmacy_id
     */
    public function store(Request $request,$pharmacy_id) {
        if(Auth::user()->pharmacy_balance_ban()) {
            return redirect("billing/".Auth::user()->pharmacy_id, 302);
        }
        if(Auth::user()->can('manage-pharmacy', $pharmacy_id)) {
            if(request()->request->has('user_id')) {
                $pharmacy=DB::table('pharmacys')->where('pharmacys.id',$pharmacy_id)->first();
                $patient=DB::table('users')->where('users.id',$request->input('user_id'))->first();
                if(!empty($patient) && !empty($pharmacy)) {
                    $zip_tariff=DB::table('area_zip')->where('area_zip.zip',$patient->zip)->join('area', 'area_zip.area_id', '=', 'area.id')->select("area.tariff")->first();
                    if(!empty($zip_tariff)){
                        return json_encode([
                            'message' => 'OK',
                            'tariff' => $zip_tariff->tariff
                        ]);
                    } else {
                        return json_encode([
                            'message' => 'OK',
                            'tariff' => 0
                        ]);
                    }
                } else {
                    return json_encode([
                        'message' => 'OK',
                        'tariff' => 0
                    ]);
                }
            }
            if($request->input('save')>0) {
                // rxs.rx_id is varchar(20) and stores "<RX#>-<Rf#>", so RX# (15) + "-" + Rf# (4) fits.
                $request->validate([
                    'user' => ['required_without:facility', 'nullable', 'integer', \Illuminate\Validation\Rule::exists('users', 'id')->where('pharmacy_id', $pharmacy_id)],
                    'count_bags' => ['nullable', 'integer', 'min:1', 'max:10'],
                    'copay' => ['nullable', 'numeric', 'min:0'],
                    'delivery_method' => ['required', 'integer', 'exists:delivery_methods,id'],
                    'delivery_time' => ['required', 'integer', 'exists:delivery_times,id'],
                    'delivery_date' => ['nullable', 'date'],
                    'type_driver' => ['nullable', 'in:1,2'],
                    'driver' => ['nullable', 'integer', 'exists:users,id'],
                    'special_instructions' => ['nullable', 'string', 'max:1000'],
                    'rx_id' => ['required', 'array', 'min:1'],
                    'rx_id.*' => ['required', 'string', 'max:15'],
                    'rf_id.*' => ['nullable', 'regex:/^\d{1,4}$/'],
                    'rx_count.*' => ['nullable', 'integer', 'min:1'],
                    'rx_date.*' => ['nullable', 'date'],
                ], [
                    'user.required_without' => 'Choose a customer.',
                    'user.exists' => 'The selected customer does not belong to this pharmacy.',
                    'delivery_method.required' => 'Choose a delivery option.',
                    'delivery_time.required' => 'Choose a preferred delivery time.',
                    'rx_id.required' => 'Add at least one RX#.',
                    'rx_id.*.required' => 'Every RX row needs an RX#.',
                    'rx_id.*.max' => 'RX# can be at most 15 characters.',
                    'rf_id.*.regex' => 'Rf# must be a number of up to 4 digits.',
                    'rx_count.*.min' => 'Qty must be at least 1.',
                ]);
                $copay = (empty($request->input('copay')))?'0':round($request->input('copay'),2);
                $statuse_copay = (empty($request->input('copay')))?'1':'2';
                if(!empty($request->input('copay_paid_pharm'))) {
                    $statuse_copay='6';
                }
                $fridge = (empty($request->input('fridge')))?'0':$request->input('fridge');
                $special_instructions = (empty($request->input('special_instructions')))?NULL:addslashes($request->input('special_instructions'));
                $rx_ids = $request->input('rx_id');
                $rf_ids = $request->input('rf_id');
                $rx_counts = $request->input('rx_count');
                $rx_dates = $request->input('rx_date');
                $rx_recipients = $request->input('rx_recipient');
                $data=[];
                foreach(array_keys($rx_ids) as $key){
                    if(empty($rx_recipients[$key])) {
                        $rx_recipient=NULL;
                    } else {
                        $rx_recipient=$rx_recipients[$key];
                    }
                    $data[]=["rx_id"=>str_replace([" ",'-',','],'',$rx_ids[$key]).'-'.($rf_ids[$key] ?? ''),"rx_date"=>$rx_dates[$key] ?? null,"rx_count"=>$rx_counts[$key] ?? 1,"rx_recipient"=>$rx_recipient];
                }
                if(!empty($request->input('delivery_date'))){
                    $delivery_date = date("Y-m-d",strtotime($request->input('delivery_date')));
                } else {
                    // Use the app's timezone; the database server's CURDATE() is UTC.
                    $delivery_date = $request->input('delivery_time')=="1" ? now()->addDay()->toDateString() : now()->toDateString();
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
                $order_row = !empty($request->input('facility'))
                    ? ['pharmacy_id' => $pharmacy_id, 'medic_id'=>Auth::id(), 'driver_id'=>$driver_id, 'user_id' => $request->input('facility'), 'facility'=>true, 'copay' => $copay, 'statuse_copay' => $statuse_copay, 'delivery_method_id' => $request->input('delivery_method'), 'special_instructions' => $special_instructions, 'count_bags' => $request->input('count_bags'), 'extra_charge_driver'=>floatval($request->input('extra_charge_driver')), 'type_driver' => $request->input('type_driver'), 'delivery_time_id' => $request->input('delivery_time'), 'delivery_time_range' => $delivery_time_range, 'delivery_date'=>$delivery_date, 'fridge' => $fridge,'family_id' => $request->input('family_id')]
                    : ['pharmacy_id' => $pharmacy_id, 'medic_id'=>Auth::id(), 'driver_id'=>$driver_id, 'user_id' => $request->input('user'), 'copay' => $copay, 'statuse_copay' => $statuse_copay, 'delivery_method_id' => $request->input('delivery_method'), 'special_instructions' => $special_instructions, 'count_bags' => $request->input('count_bags'), 'extra_charge_driver'=>floatval($request->input('extra_charge_driver')), 'type_driver' => $request->input('type_driver'), 'delivery_time_id' => $request->input('delivery_time'), 'delivery_time_range' => $delivery_time_range, 'delivery_date'=>$delivery_date, 'fridge' => $fridge,'family_id' => $request->input('family_id')];
                $id_max = DB::transaction(function () use ($order_row, $data) {
                    $order_id = DB::table('orders')->insertGetId($order_row);
                    DB::table('rxs')->insert(array_map(fn ($rx) => ['order_id' => $order_id] + $rx, $data));
                    return $order_id;
                });
                if(!empty($request->input('facility'))){
                    $us = DB::table('users')->where('id',$request->input('facility'))->first();
                } else {
                    $us = DB::table('users')->where('id',$request->input('user'))->first();
                }
                if($us->primary_address==3){
                    if(!empty($us->apartment)){
                        $user_address = $us->address3.' Apt '.$us->apartment3;
                    } else {
                        $user_address = $us->address3;
                    }
                } elseif($us->primary_address==2){
                    if(!empty($us->apartment)){
                        $user_address = $us->address2.' Apt '.$us->apartment2;
                    } else {
                        $user_address = $us->address2;
                    }
                } else {
                    if(!empty($us->apartment)){
                        $user_address = $us->address.' Apt '.$us->apartment;
                    } else {
                        $user_address = $us->address;
                    }
                }
                Notifications::send_push($request->input('user'),"QuikMedix","created your order #$id_max (medicines), which will be delivered to: $user_address. If the address is wrong, please contact ".\App\Support\Branding::supportContact()." as soon as possible");
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
            return redirect("orders/$pharmacy_id".(isset($id_max) ? "?added=$id_max" : ""));
        } else {
            return abort(403, LexaAdmin::$err_perm);
        }
    }

    /**
     * @param int|string $pharmacy_id
     * @param int|string $order_id
     */
    public function show($pharmacy_id,$order_id) {
        Gate::authorize('access-order', $order_id);
        if($pharmacy_id==0) {
            $order = DB::table('orders')->where('orders.id',$order_id)->first();
            $pharmacy_id = $order->pharmacy_id;
        }
        $order = DB::table('orders')->join('users', 'orders.user_id', '=', 'users.id')->leftJoin('users as medic', 'orders.medic_id', '=', 'medic.id')->join('statuses', 'orders.statuse_id', '=', 'statuses.id')->leftJoin('statuses_copay', 'orders.statuse_copay', '=', 'statuses_copay.id')->join('delivery_methods', 'orders.delivery_method_id', '=', 'delivery_methods.id')->join('delivery_times', 'orders.delivery_time_id', '=', 'delivery_times.id')->join('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id')->select('orders.id', 'orders.pharmacy_id','orders.delivery_address','orders.delivery_location', 'orders.eta', 'orders.created', 'orders.finish', 'orders.family_id', 'orders.delivery_date', 'orders.delivery_time_range', 'orders.statuse_id', 'orders.rating', 'orders.signature', 'orders.fridge', 'orders.facility', 'orders.special_instructions', 'orders.dispatcher_notes', 'orders.user_id',  'orders.copay', 'orders.driver_id', 'orders.count_bags', 'orders.drop_off_photo', 'orders.signature_photo', 'orders.signature_type', 'orders.medic_id', 'medic.name as medicname', 'medic.last_name as mediclast_name', 'users.name as username', 'users.last_name as last_name', 'users.os as useros', 'delivery_methods.name as delivery_method', 'delivery_times.name as delivery_time', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end as useraddress'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end as userapartment'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end as userzip'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end as userlocation'), 'users.phone as userphone', 'users.home_phone as userhomephone', 'pharmacys.name as pharmacyname', 'pharmacys.address as pharmacyaddress','pharmacys.phone as pharmacyphone', 'pharmacys.location as pharmacylocation', 'statuses.name as statusename','statuses.color as statusecolor', 'orders.statuse_copay', 'statuses_copay.name as statuse_copay_name','statuses_copay.color as statuse_copay_color')->where('orders.id',$order_id)->groupBy('orders.id', 'orders.pharmacy_id', 'orders.eta', 'orders.statuse_id', 'orders.facility', 'orders.created', 'orders.finish', 'orders.delivery_date', 'orders.delivery_time_range', 'orders.driver_id', 'orders.rating', 'orders.family_id', 'orders.count_bags', 'orders.signature', 'orders.fridge', 'orders.special_instructions', 'orders.dispatcher_notes', 'orders.copay', 'orders.drop_off_photo','orders.signature_photo', 'orders.signature_type', 'orders.user_id', 'users.name', 'users.last_name', 'medic.name', 'medic.last_name','users.os', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end'), 'orders.medic_id', 'users.phone','users.home_phone','pharmacys.name', 'delivery_methods.name', 'delivery_times.name', 'pharmacys.location', 'pharmacys.address','pharmacys.phone', 'statuses.name','statuses.color', 'orders.statuse_copay', 'statuses_copay.name','statuses_copay.color','orders.delivery_address','orders.delivery_location')->first();
        if((Auth::user()->role == 'medic' && Auth::user()->pharmacy_id==$pharmacy_id) || ((Auth::user()->can('admin'))) || (Auth::user()->role == 'logist') || (Auth::user()->role == 'driver' && Auth::user()->id==$order->driver_id) || (Auth::user()->role == 'user' && Auth::user()->id==$order->user_id)) {
            $medicines = DB::table('medicine')->join('medicines', 'medicine.medicine_id', '=', 'medicines.id')->select('medicine.count','medicines.name','medicine.dosage')->where('order_id',$order_id)->get();
            $rxs = DB::table('rxs')->where('order_id',$order_id)->get();
            if($order->driver_id>0) {
                $driver = DB::table('users')->where('id',$order->driver_id)->first();
            } else {
                $driver="";
            }
            $orders_transitions=DB::table('packages_transitions')->where('order_id',$order_id)->orderBy('id','ASC')->get();
            $locations = DB::table('locations')->whereIn('id', [DB::raw("select max(`id`) from locations GROUP BY user_id")])->where('user_id',$order->driver_id)->first();
            if($order->statuse_id==4 && !empty($order->finish)) {
                $locationDrivers = DB::table('locations')->where('user_id',$order->driver_id)->whereBetween("created",[date("Y-m-d H:i:s",strtotime($order->finish ?? '')-300),date("Y-m-d H:i:s",strtotime($order->finish ?? '')+300)])->get();
            } else {
                $locationDrivers = [];
            }
            $dispatcher_notes = DB::table('notes')->where('order_id',$order_id)->where("type","1")->get();
            $customer_notes = DB::table('notes')->where('order_id',$order_id)->where("type","2")->get();
            $rxs_id = DB::table('rxs')->where('order_id',$order_id)->pluck('rx_recipient')->toArray();
            $additional_recipients=DB::table('additional_recipients')->where('user_id',$order->user_id)->whereIn('id',$rxs_id)->get()->keyBy('id');;
            $family=DB::table('family_members')->where('id',$order->family_id)->first();
            $res_view = view('orders.show',['order'=>$order,'rxs'=>$rxs,'family'=>$family,'dispatcher_notes'=>$dispatcher_notes,'customer_notes'=>$customer_notes,'additional_recipients'=>$additional_recipients,'orders_transitions'=>$orders_transitions,'medicines'=>$medicines,'driver'=>$driver,'locations'=>$locations,'locationDrivers'=>$locationDrivers,'title'=>'Order Show','br1'=>'Orders','br2'=>'Order Show']);
            if(request()->query->has('ajax')) {
                return $res_view->renderSections();
            } else {
                return $res_view;
            }
        } else {
            return abort(403, LexaAdmin::$err_perm);
        }
    }

    /**
     * @param int|string $pharmacy_id
     * @param int|string $order_id
     */
    public function handleShowAction(Request $request,$pharmacy_id,$order_id, RotateSignature $rotateSignature) {
        Gate::authorize('access-order', $order_id);
        $order = DB::table('orders')->join('users', 'orders.user_id', '=', 'users.id')->join('statuses', 'orders.statuse_id', '=', 'statuses.id')->leftJoin('statuses_copay', 'orders.statuse_copay', '=', 'statuses_copay.id')->join('delivery_methods', 'orders.delivery_method_id', '=', 'delivery_methods.id')->join('delivery_times', 'orders.delivery_time_id', '=', 'delivery_times.id')->join('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id')->select('orders.id', 'orders.pharmacy_id', 'orders.eta', 'orders.created', 'orders.finish', 'orders.statuse_id', 'orders.signature', 'orders.fridge', 'orders.special_instructions', 'orders.dispatcher_notes', 'orders.user_id',  'orders.copay', 'orders.driver_id', 'orders.count_bags', 'orders.drop_off_photo', 'orders.signature_photo', 'orders.signature_type', 'users.name as username', 'users.last_name as last_name', 'users.os as useros', 'delivery_methods.name as delivery_method', 'delivery_times.name as delivery_time', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end as useraddress'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end as userapartment'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end as userzip'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end as userlocation'), 'users.phone as userphone', 'pharmacys.name as pharmacyname', 'pharmacys.address as pharmacyaddress','pharmacys.phone as pharmacyphone', 'pharmacys.location as pharmacylocation', 'statuses.name as statusename','statuses.color as statusecolor', 'orders.statuse_copay', 'statuses_copay.name as statuse_copay_name','statuses_copay.color as statuse_copay_color')->where('orders.id',$order_id)->groupBy('orders.id', 'orders.pharmacy_id', 'orders.eta', 'orders.statuse_id', 'orders.created', 'orders.finish', 'orders.driver_id', 'orders.count_bags', 'orders.signature', 'orders.fridge', 'orders.special_instructions', 'orders.dispatcher_notes', 'orders.copay', 'orders.drop_off_photo','orders.signature_photo', 'orders.signature_type', 'orders.user_id', 'users.name', 'users.last_name', 'users.os', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end'), 'users.phone','pharmacys.name', 'delivery_methods.name', 'delivery_times.name', 'pharmacys.location', 'pharmacys.address','pharmacys.phone', 'statuses.name','statuses.color', 'orders.statuse_copay', 'statuses_copay.name','statuses_copay.color')->first();
        if((Auth::user()->role == 'medic' && Auth::user()->pharmacy_id==$pharmacy_id) || ((Auth::user()->can('admin'))) ||  (Auth::user()->role == 'logist') || (Auth::user()->role == 'driver' && Auth::user()->id==$order->driver_id) || (Auth::user()->role == 'user' && Auth::user()->id==$order->user_id)) {
            if(request()->request->has('dispatcher_notes')) {
                DB::table('notes')->insert(["order_id"=>$order_id,"user_id"=>Auth::user()->id,"type"=>"1",'note'=>addslashes($request->input('dispatcher_notes'))]);
                return json_encode([
                    'message' => 'OK'
                ]);
            }
            if($request->hasFile('drop_off_photo')) {
                $file = $request->file('drop_off_photo');
                $file->move(public_path() . '/images/drop_off/',\App\Support\PublicUpload::name($request->file('drop_off_photo')));
                $src = '/images/drop_off/'.\App\Support\PublicUpload::name($request->file('drop_off_photo'));
                DB::table('orders')->where('orders.id',$order_id)->update(['drop_off_photo'=>$src]);
            }
            if($request->hasFile('signature_photo')) {
                $file = $request->file('signature_photo');
                $file->move(public_path() . '/images/signature/',\App\Support\PublicUpload::name($request->file('signature_photo')));
                $src = '/images/signature/'.\App\Support\PublicUpload::name($request->file('signature_photo'));
                DB::table('orders')->where('orders.id',$order_id)->update(['signature_photo'=>$src]);
            }
            if($request->input('rotate_signature')>0 && !empty($order->signature_photo)) {
                $src = $rotateSignature->handle($order->signature_photo);
                DB::table('orders')->where('orders.id',$order_id)->update(['signature_photo'=>$src]);
            }
            if($request->input('eta_calculate')>0) {
                if($order->driver_id>0) {
                    LexaAdmin::eta_calculate($order->driver_id,true);
                }
                return redirect()->back()->with('success', "Successfully the route ETA was updated.");
            }
            if($request->input('paid')>0) {
                if(!empty($order->driver_id)){
                    $cash_log = DB::table('cash_log')->where("order_id",$order_id)->where("driver_id",$order->driver_id)->first();
                    if(!empty($cash_log)) {
                        DB::table('cash_log')->where('id',$cash_log->id)->update(["copay"=>$order->copay]);
                    } else {
                        DB::table('cash_log')->insert(["order_id"=>$order_id,"driver_id"=>$order->driver_id,"copay"=>$order->copay]);   
                    }
                }
                DB::table('orders')->where('id',$order_id)->update(['statuse_copay'=>4]);
                return redirect()->back()->with('success', "Successfully paid co-pay.");
            }
            if($request->input('not_paid')>0) {
                DB::table('orders')->where('id',$order_id)->update(['statuse_copay'=>5]);
                return redirect()->back()->with('success', "Successfully changed status co-pay.");
            }
            return redirect("orders/$pharmacy_id/show/$order_id");
        } else {
            return abort(403, LexaAdmin::$err_perm);
        }
    }

    /**
     * @param int|string $order_id
     */
    public function preview($order_id)
    {
        $order = DB::table('orders')->join('users', 'orders.user_id', '=', 'users.id')->leftJoin('users as medic', 'orders.medic_id', '=', 'medic.id')->join('statuses', 'orders.statuse_id', '=', 'statuses.id')->leftJoin('statuses_copay', 'orders.statuse_copay', '=', 'statuses_copay.id')->join('delivery_methods', 'orders.delivery_method_id', '=', 'delivery_methods.id')->join('delivery_times', 'orders.delivery_time_id', '=', 'delivery_times.id')->join('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id')->select('orders.id', 'orders.pharmacy_id', 'orders.eta', 'orders.created', 'orders.finish', 'orders.delivery_date', 'orders.delivery_time_range', 'orders.statuse_id', 'orders.rating', 'orders.signature', 'orders.fridge', 'orders.facility', 'orders.special_instructions', 'orders.dispatcher_notes', 'orders.user_id',  'orders.copay', 'orders.driver_id', 'orders.count_bags', 'orders.drop_off_photo', 'orders.signature_photo', 'orders.signature_type', 'orders.medic_id', 'medic.name as medicname', 'medic.last_name as mediclast_name', 'users.name as username', 'users.last_name as last_name', 'users.os as useros', 'delivery_methods.name as delivery_method', 'delivery_times.name as delivery_time', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end as useraddress'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end as userapartment'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end as userzip'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end as userlocation'), 'users.phone as userphone', 'users.home_phone as userhomephone', 'pharmacys.name as pharmacyname', 'pharmacys.address as pharmacyaddress','pharmacys.phone as pharmacyphone', 'pharmacys.location as pharmacylocation', 'statuses.name as statusename','statuses.color as statusecolor', 'orders.statuse_copay', 'statuses_copay.name as statuse_copay_name','statuses_copay.color as statuse_copay_color')->where('orders.id',$order_id)->groupBy('orders.id', 'orders.pharmacy_id', 'orders.eta', 'orders.statuse_id', 'orders.facility', 'orders.created', 'orders.finish', 'orders.delivery_date', 'orders.delivery_time_range', 'orders.driver_id', 'orders.rating', 'orders.count_bags', 'orders.signature', 'orders.fridge', 'orders.special_instructions', 'orders.dispatcher_notes', 'orders.copay', 'orders.drop_off_photo','orders.signature_photo', 'orders.signature_type', 'orders.user_id', 'users.name','users.last_name', 'medic.name', 'medic.last_name', 'users.os', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end'), 'orders.medic_id','users.phone','users.home_phone','pharmacys.name', 'delivery_methods.name', 'delivery_times.name', 'pharmacys.location', 'pharmacys.address','pharmacys.phone', 'statuses.name','statuses.color', 'orders.statuse_copay', 'statuses_copay.name','statuses_copay.color')->first();
        abort_if(empty($order), 404, 'Order not found');
        $pharmacy_id = $order->pharmacy_id;
        if((Auth::user()->role == 'medic' && Auth::user()->pharmacy_id==$pharmacy_id) || ((Auth::user()->can('admin'))) || (Auth::user()->role == 'logist') || (Auth::user()->role == 'driver' && Auth::user()->id==$order->driver_id) || (Auth::user()->role == 'user' && Auth::user()->id==$order->user_id)) {
            $medicines = DB::table('medicine')->join('medicines', 'medicine.medicine_id', '=', 'medicines.id')->select('medicine.count','medicines.name','medicine.dosage')->where('order_id',$order_id)->get();
            $rxs = DB::table('rxs')->where('order_id',$order_id)->get();
            if($order->driver_id>0) {
                $driver = DB::table('users')->where('id',$order->driver_id)->first();
            } else {
                $driver="";
            }
            $orders_transitions=DB::table('packages_transitions')->where('order_id',$order_id)->orderBy('id','ASC')->get();
            $locations = DB::table('locations')->whereIn('id', [DB::raw("select max(`id`) from locations GROUP BY user_id")])->where('user_id',$order->driver_id)->first();
            if($order->statuse_id==4 && !empty($order->finish)) {
                $locationDrivers = DB::table('locations')->where('user_id',$order->driver_id)->whereBetween("created",[date("Y-m-d H:i:s",strtotime($order->finish ?? '')-300),date("Y-m-d H:i:s",strtotime($order->finish ?? '')+300)])->get();
            } else {
                $locationDrivers = [];
            }
            $dispatcher_notes = DB::table('notes')->where('order_id',$order_id)->where("type","1")->get();
            $customer_notes = DB::table('notes')->where('order_id',$order_id)->where("type","2")->get();
            $rxs_id = DB::table('rxs')->where('order_id',$order_id)->pluck('rx_recipient')->toArray();
            $additional_recipients=DB::table('additional_recipients')->where('user_id',$order->user_id)->whereIn('id',$rxs_id)->get()->keyBy('id');;
            return view('orders.preview',['order'=>$order,'rxs'=>$rxs,'dispatcher_notes'=>$dispatcher_notes,'customer_notes'=>$customer_notes,'additional_recipients'=>$additional_recipients,'orders_transitions'=>$orders_transitions,'medicines'=>$medicines,'driver'=>$driver,'locations'=>$locations,'locationDrivers'=>$locationDrivers,'title'=>'Order Preview','br1'=>'Orders','br2'=>'Order Preview']);
        } else {
            return abort(403, LexaAdmin::$err_perm);
        }
    }

    /**
     * @param int|string $pharmacy_id
     * @param int|string $order_id
     */
    public function edit($pharmacy_id,$order_id) {
        Gate::authorize('access-order', $order_id);
        if(Auth::user()->can('manage-pharmacy', $pharmacy_id) || Auth::user()->hasAnyRole('logist')) {
            $order = DB::table('orders')->where('id', $order_id)->first();
            $rxs = DB::table('rxs')->where('order_id', $order_id)->get();
            $users = DB::table('users')->where('role', 'user')->where('pharmacy_id', $pharmacy_id)->select('users.id','users.name','users.last_name','users.phone')->get();
            $facilitys = DB::table('users')->where('role', 'facility')->where('pharmacy_id', $pharmacy_id)->select('users.id','users.name','users.last_name','users.phone')->get();
            $drivers = DB::table('users')->where('role', 'driver')->whereNull("pharmacy_id")->get();
            $drivers2 = DB::table('users')->where('role', 'driver')->where("pharmacy_id",$pharmacy_id)->get();
            $medicines = DB::table('medicines')->get();
            $delivery_methods = DB::table('delivery_methods')->get();
            $delivery_times = DB::table('delivery_times')->get();
            $medicine = DB::table('medicine')->where('order_id', $order_id)->get();
            $count = count($medicine);
            $statuses = DB::table('statuses')->get();
            $family_members = DB::table('family_members')->where('user_id', $order->user_id)->get();
            $additional_recipients = DB::table('additional_recipients')->where('user_id', $order->user_id)->get();
            $pharmacy=DB::table('pharmacys')->where('pharmacys.id',$pharmacy_id)->first();
            $patient=DB::table('users')->where('users.id',$order->user_id)->first();
            $time_ranges = ["9:00 AM","10:00 AM","11:00 AM","12:00 PM", "1:00 PM","2:00 PM","3:00 PM","4:00 PM","5:00 PM","6:00 PM","7:00 PM","8:00 PM","9:00 PM","10:00 PM","11:00 PM","12:00 AM"];
            $zip_tariff=DB::table('area_zip')->where('area_zip.zip',$patient->zip)->join('area', 'area_zip.area_id', '=', 'area.id')->select("area.tariff")->first();
            $res_view = view('orders.edit',['order'=>$order, 'rxs'=>$rxs, "time_ranges"=>$time_ranges, 'count'=>$count, 'users'=>$users, 'facilitys'=>$facilitys, 'pharmacy'=>$pharmacy, 'zip_tariff'=>$zip_tariff, 'drivers'=>$drivers, 'drivers2'=>$drivers2, 'family_members'=>$family_members, 'additional_recipients'=>$additional_recipients, 'statuses'=>$statuses, 'medicines'=>$medicines, 'medicine'=>$medicine, 'delivery_methods'=>$delivery_methods, 'delivery_times'=>$delivery_times, 'title'=>'Order Edit','br1'=>'Orders','br2'=>'Order Edit','alert'=>'']);
            if(request()->query->has('ajax')) {
                return $res_view->renderSections();
            } else {
                return $res_view;
            }
        } else {
            return abort(403, LexaAdmin::$err_perm);
        }
    }

    /**
     * @param int|string $pharmacy_id
     * @param int|string $order_id
     */
    public function update(Request $request,$pharmacy_id,$order_id) {
        Gate::authorize('access-order', $order_id);
        if(Auth::user()->can('manage-pharmacy', $pharmacy_id) || Auth::user()->hasAnyRole('logist')) {
            if(request()->request->has('user_id')) {
                $pharmacy=DB::table('pharmacys')->where('pharmacys.id',$pharmacy_id)->first();
                $patient=DB::table('users')->where('users.id',$request->input('user_id'))->first();
                if(!empty($patient)){
                    $zip_tariff=DB::table('area_zip')->where('area_zip.zip',$patient->zip)->join('area', 'area_zip.area_id', '=', 'area.id')->select("area.tariff")->first();
                    if(!empty($zip_tariff)){
                        return json_encode([
                            'message' => 'OK',
                            'tariff' => $zip_tariff->tariff
                        ]);
                    } else {
                        return json_encode([
                            'message' => 'OK',
                            'tariff' => 0
                        ]);
                    }
                } else {
                    return json_encode([
                        'message' => 'OK',
                        'tariff' => 0
                    ]);
                }
            }
            if($request->input('save')>0) {
                $copay = (empty($request->input('copay')))?'0':round($request->input('copay'),2);
                $statuse_copay = (empty($request->input('copay')))?'1':'2';
                if(!empty($request->input('copay_paid_pharm'))) {
                    $statuse_copay='6';
                }
                $fridge = (empty($request->input('fridge')))?'0':$request->input('fridge');
                $order = DB::table('orders')->where('id', $order_id)->first();
                $special_instructions = (empty($request->input('special_instructions')))?NULL:addslashes($request->input('special_instructions'));
                DB::table('rxs')->where('order_id',$order_id)->delete();
                $rx_ids = $request->input('rx_id');
                $rf_ids = $request->input('rf_id');
                $rx_dates = $request->input('rx_date');
                $rx_counts = $request->input('rx_count');
                $rx_recipients = $request->input('rx_recipient');
                $data=[];
                if(!empty($rx_ids)){
                    foreach(array_keys($rx_ids) as $key){
                        if(empty($rx_recipients[$key])) {
                            $rx_recipient=NULL;
                        } else {
                            $rx_recipient=$rx_recipients[$key];
                        }
                        $data[]=["order_id"=>$order_id,"rx_id"=>str_replace([" ",'-',','],'',$rx_ids[$key]).'-'.$rf_ids[$key],"rx_date"=>$rx_dates[$key],"rx_count"=>$rx_counts[$key],"rx_recipient"=>$rx_recipient];
                    }
                }
                DB::table('rxs')->insert($data);
                if(!empty($request->input('delivery_date'))){
                    $delivery_date = date("Y-m-d",strtotime($request->input('delivery_date')));
                } else {
                    if($request->input('delivery_time')=="1"){
                        $delivery_date = DB::raw("DATE_ADD(CURDATE(), INTERVAL 1 DAY)");
                    } else {
                        $delivery_date = DB::raw("CURDATE()");
                    }
                }
                if($request->input('statuse_copay')==4){
                    DB::table('orders')->where('id', $order_id)->update(['statuse_copay' => 4]);
                }
                if($request->input('type_driver')==2) {
                    $driver_id = $request->input('driver2');
                } else {
                    $driver_id = $request->input('driver');
                }
                $time_ranges = ["9:00 AM","10:00 AM","11:00 AM","12:00 PM", "1:00 PM","2:00 PM","3:00 PM","4:00 PM","5:00 PM","6:00 PM","7:00 PM","8:00 PM","9:00 PM","10:00 PM","11:00 PM","12:00 AM"];
                if(empty($request->input('delivery_time_range'))) {
                    $delivery_time_range = $time_ranges[0].";".end($time_ranges);
                } else {
                    $delivery_time_range = $request->input('delivery_time_range');
                }
                DB::table('orders')->where('id', $order_id)->update(['user_id' => $order->user_id, 'driver_id' => $driver_id, 'statuse_id' => $request->input('statuse'), 'extra_charge_driver'=>floatval($request->input('extra_charge_driver')), 'copay' => $copay, 'statuse_copay' => $statuse_copay, 'special_instructions' => $special_instructions, 'delivery_method_id' => $request->input('delivery_method'), 'count_bags' => $request->input('count_bags'), 'type_driver' => $request->input('type_driver'), 'delivery_time_id' => $request->input('delivery_time'),'delivery_time_range' => $delivery_time_range,'delivery_date'=>$delivery_date,'fridge' => $fridge, 'family_id' => $request->input('family_id')]);
                if($request->input('statuse')==1 && ($request->input('delivery_time')==3 || $request->input('delivery_time')==4)) {
                    $pharmacy = DB::table('pharmacys')->where('id', $pharmacy_id)->first();
                    Notifications::send_push_web(array_map('strval', User::where('role', "admin")->orWhere("role","logist")->pluck('id')->toArray()),
                        "Attention!",
                        "Urgent order No.".$order_id." has been created, which needs to be processed promptly.",
                        url('/')."orders/".$pharmacy_id."?statuse%5B%5D=1",
                        "rush_order"
                    );
                }
                if($order->statuse_id!=$request->input('statuse') && ($request->input('statuse')==4 || $request->input('statuse')==8 || $request->input('statuse')==9 || $request->input('statuse')==10)) {
                    if(!empty($order->bestrx_order_id)){
                        app(SendOrderStatusToBestRx::class)->handle($order->id);
                    }
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
                    $route = DB::table('routes_priority')->where('order_id',$order_id)->delete();
                    if($patient->primary_address==3){
                        $user_address = $patient->address3.', '.$patient->zip3.', Apt '.$patient->apartment3;
                        $user_location = $patient->location3;
                    } elseif($patient->primary_address==2){
                        $user_address = $patient->address2.', '.$patient->zip2.', Apt '.$patient->apartment2;
                        $user_location = $patient->location2;
                    } else {
                        $user_address = $patient->address.', '.$patient->zip.', Apt '.$patient->apartment;
                        $user_location = $patient->location;
                    }
                    $driver_location=$user_location;
                    DB::table('orders')->where('orders.id',$order_id)->update(['statuse_id'=>$request->input('statuse'),'finish'=>date('Y-m-d H:i:s'),'delivery_address'=>$user_address,'delivery_location'=>$driver_location,'tariff'=>$tariff_res]);
                } else if($order->statuse_id!=$request->input('statuse') && $request->input('statuse')==3) {
                    Notifications::send_push($order->user_id,"QuikMedix","Your order #$order_id is on its way!");
                } else if($order->statuse_id!=$request->input('statuse') && $request->input('statuse')==4) {
                    $route = DB::table('routes_priority')->where('order_id',$order_id)->where('driver_id',$driver_id)->where('type','patient')->first();
                    $route2 = DB::table('routes_priority')->where('order_id',$order_id)->where('driver_id',$driver_id)->where('type','pharmacy')->first();
                    $route3 = DB::table('routes_priority')->where('order_id',$order_id)->where('driver_id',$driver_id)->where('type','office')->first();
                    if(!empty($route) && empty($route2) && empty($route3)){
                        $next_office = DB::table('routes_priority')->where('driver_id',$driver_id)->where('type','office')->first();
                        if(!empty($next_office)) {
                            DB::table('routes_priority')->insert(['driver_id'=>$driver_id,'order_id'=>$order_id,'type'=>'office','type_id'=>$next_office->type_id,'type_pay'=>$next_office->type_pay,'pay_value'=>$next_office->pay_value,'priority'=>$next_office->priority]);
                        } else {
                            $last_route = DB::table('routes_priority')->where('driver_id',$driver_id)->max('priority');
                            if(!empty($routeNeed)) {
                                DB::table('routes_priority')->insert(['driver_id'=>$driver_id,'order_id'=>$order_id,'type'=>'office','type_id'=>1,'type_pay'=>$routeNeed->type_pay,'pay_value'=>$routeNeed->pay_value,'priority'=>(intval($last_route)+1)]);
                            }
                        }
                    }
                    DB::table('routes_priority')->where('order_id',$order_id)->where('driver_id',$driver_id)->where('type','patient')->delete();
                }
                DB::table('medicine')->where('order_id', $order_id)->delete();
            }
            return redirect("orders/$pharmacy_id");
        } else {
            return abort(403, LexaAdmin::$err_perm);
        }
    }

    /**
     * @param int|string $pharmacy_id
     */
    public function statistic($pharmacy_id) {
        $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
        if(!empty($pharmacy)) {
            if(Auth::user()->can('manage-pharmacy', $pharmacy_id)) {
                $date = date('Y-m-d');
                $statuse_id = '';
                $statuses = DB::table('statuses')->get();
                $polygons = DB::table('area')->select('id','name',DB::raw('ST_AsText(polygon) as polygon'))->get();
                foreach($polygons as $key=>$pol) {
                    if(!empty($pol->polygon)) {
                        $polygons[$key]->polygon = WktPolygon::toJsonPoints($pol->polygon);
                        $polygons[$key]->count = DB::table('orders')->join('users', 'orders.user_id', '=', 'users.id')->join('area', function($join) use($pol) {
                            $join->on('area.polygon','!=','orders.id');
                            $join->where('area.id',$pol->id);
                        })->select('orders.id')->whereRaw("ST_CONTAINS(area.polygon, POINT(SUBSTRING_INDEX(users.location,',',1),SUBSTRING_INDEX(users.location,',',-1)))");
                        if(!empty($statuse_id)) {
                            $polygons[$key]->count=$polygons[$key]->count->where('orders.statuse_id',$statuse_id);
                        }
                        if($statuse_id==4) {
                            $polygons[$key]->count=$polygons[$key]->count->whereDate('finish', $date);
                        } else {
                            $polygons[$key]->count=$polygons[$key]->count->whereDate('created', $date);
                        }
                        $polygons[$key]->count=$polygons[$key]->count->get()->count();
                    } else {
                        $polygons[$key]->polygon = "";
                        $polygons[$key]->count = "0";
                    }   
                }
                return view('orders.statistic',['date'=>$date,'polygons'=>$polygons,'pharmacy'=>$pharmacy,'statuses'=>$statuses,'statuse_id'=>$statuse_id,'alert'=>'','title'=>'Orders Map Statistic','br1'=>'Orders','br2'=>'Map Statistic']);
            } else {
                return abort(403, LexaAdmin::$err_perm);
            }
        } else {
            return abort(404, "Not found pharmacy");
        }
    }

    /**
     * @param int|string $pharmacy_id
     */
    public function statisticForDate($pharmacy_id,Request $request) {
        $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
        if(!empty($pharmacy)) {
            if(Auth::user()->can('manage-pharmacy', $pharmacy_id)) {
                $date = date('Y-m-d',strtotime($request->input('date')));
                $statuse_id = $request->input('statuse_id');
                $statuses = DB::table('statuses')->get();
                $polygons = DB::table('area')->select('id','name',DB::raw('ST_AsText(polygon) as polygon'))->get();
                foreach($polygons as $key=>$pol) {
                    if(!empty($pol->polygon)) {
                        $polygons[$key]->polygon = WktPolygon::toJsonPoints($pol->polygon);
                        $polygons[$key]->count = DB::table('orders')->join('users', 'orders.user_id', '=', 'users.id')->join('area', function($join) use($pol) {
                            $join->on('area.polygon','!=','orders.id');
                            $join->where('area.id',$pol->id);
                        })->select('orders.id')->whereRaw("ST_CONTAINS(area.polygon, POINT(SUBSTRING_INDEX(users.location,',',1),SUBSTRING_INDEX(users.location,',',-1)))");
                        if(!empty($statuse_id)) {
                            $polygons[$key]->count=$polygons[$key]->count->where('orders.statuse_id',$statuse_id);
                        }
                        if($statuse_id==4) {
                            $polygons[$key]->count=$polygons[$key]->count->whereDate('finish', $date);
                        } else {
                            $polygons[$key]->count=$polygons[$key]->count->whereDate('created', $date);
                        }
                        $polygons[$key]->count=$polygons[$key]->count->get()->count();
                    } else {
                        $polygons[$key]->polygon = "";
                        $polygons[$key]->count = "0";
                    }   
                }
                return view('orders.statistic',['date'=>$date,'polygons'=>$polygons,'pharmacy'=>$pharmacy,'statuses'=>$statuses,'statuse_id'=>$statuse_id,'alert'=>'','title'=>'Orders Map Statistic','br1'=>'Orders','br2'=>'Map Statistic']);
            } else {
                return abort(403, LexaAdmin::$err_perm);
            }
        } else {
            return abort(404, "Not found pharmacy");
        }
    }

    /**
     * @param int|string $pharmacy_id
     */
    public function markReady($pharmacy_id) {
        if(Auth::user()->can('manage-pharmacy', $pharmacy_id)) {
            DB::table("orders")->where("pharmacy_id",$pharmacy_id)->where("orders.ready",'0')->where("orders.statuse_id",1)->update(["ready"=>'1']);
            return json_encode([
                'message' => 'OK'
            ]);
        } else {
            return abort(403, LexaAdmin::$err_perm);
        }
    }

    /**
     * @param int|string $order_id
     */
    public function callRecordings($order_id) {
        abort_unless(Auth::user()->hasAnyRole('superadmin', 'admin', 'dispadmin', 'logist', 'medic'), 403, LexaAdmin::$err_perm);
        Gate::authorize('access-order', $order_id);
        $order = DB::table('orders')->join('users', 'orders.user_id', '=', 'users.id')->select('orders.id', 'orders.created', 'users.phone as userphone')->where('orders.id',$order_id)->groupBy('orders.id', 'orders.created','users.phone')->first();
        if(!empty($order)) {
            $api = new Zadarma_API(config('services.zadarma.key'), config('services.zadarma.secret'));
            $last_calls = DB::table('calls')->where("to",preg_replace('/[^0-9]/', '', $order->userphone))->where("created",">=",$order->created)->orderBy('created','desc')->get();
            $record_links = [];
            foreach($last_calls as $last_call) {
                try {
                    $result = $api->getPbxRecord(null,$last_call->call_id,3600);
                    if(!empty($result) && !empty($result->links)) {
                        $record_link["created"] = date('m/d/Y g:i A', strtotime($last_call->created ?? ''));
                        $record_link["link"] = $result->links[0];
                        $record_links[] = $record_link;
                    }
                } catch (\Throwable) {}
            }
            return response()->json($record_links);
        } else {
            abort(404);
        }
    }
}
