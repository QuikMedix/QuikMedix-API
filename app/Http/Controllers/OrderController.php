<?php

namespace App\Http\Controllers;

use App\Support\OrderListFilter;
use App\Actions\RepeatOrder;
use App\Models\DeliveryMethod;
use App\Models\DeliveryTime;
use App\Models\Status;
use App\Models\FamilyMember;
use App\Models\AdditionalRecipient;
use App\Models\Pharmacy;
use App\Models\AreaZip;
use App\Models\Area;
use App\Models\Order;
use App\Models\Rx;
use App\Models\Note;
use App\Models\CashLog;
use App\Models\Medicine;
use App\Actions\RotateSignature;
use App\Actions\ChangeOrderStatus;
use App\Http\Controllers\Concerns\LoadsOrderLookups;
use App\Support\Notifications;
use App\Support\WktPolygon;
use App\Support\OrderStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Zadarma_API\Api as Zadarma_API;
use App\Support\AccessMessages;
use App\Actions\RecalculateDriverEta;

/**
 * A pharmacy's orders: the list, create, show, edit, preview, statistics and call recordings.
 * Formerly the orders* actions of LexaAdmin.
 */
class OrderController extends Controller
{
    use LoadsOrderLookups;


    /**
     * @param int|string $pharmacy_id
     */
    public function index($pharmacy_id) {
        $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
        if(!empty($pharmacy)) {
            abort_unless((Auth::user()->hasRole('medic') && Auth::user()->pharmacy_id==$pharmacy_id) || (Auth::user()->hasRole('sale') && Auth::user()->id==$pharmacy->ref_id) || ((Auth::user()->can('admin'))), 403, AccessMessages::NO_PERMISSION);
            $orders = DB::table('orders')->where('orders.pharmacy_id',$pharmacy_id)->leftJoin('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id')->select('orders.*')->orderBy('orders.id','desc');
            $filter = OrderListFilter::fromRequest(request(), ['delivery_time', 'delivery_method', 'status', 'facility', 'create_start', 'create_end', 'delivered_start', 'delivered_end', 'need_delivery_start', 'need_delivery_end', 'micromerchant']);
            $orders = OrderListFilter::apply($orders, $filter);
            if(!empty($filter["micromerchant"])) {
                $orders = $orders->where('orders.merchantOrder','1');
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
        abort_unless(Auth::user()->can('manage-pharmacy', $pharmacy_id), 403, AccessMessages::NO_PERMISSION);
        if($request->input('remove')>0) {
            Order::query()->where('id', $request->input('order_id'))->delete();
            Rx::query()->where('order_id',$request->input('order_id'))->delete();
        }
        if($request->input('repeat')>0) {
            [$pharmacy_id, $order_id] = app(RepeatOrder::class)->handle($request->input('order_id'));
            return redirect("orders/$pharmacy_id/edit/$order_id");
        }
        return redirect("orders/$pharmacy_id");
    }

    /**
     * @param int|string $pharmacy_id
     */
    public function create($pharmacy_id) {
        if(Auth::user()->pharmacy_balance_ban()) {
            return redirect("billing/".Auth::user()->pharmacy_id, 302);
        }
        abort_unless(Auth::user()->can('manage-pharmacy', $pharmacy_id), 403, AccessMessages::NO_PERMISSION);
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
    }

    /**
     * @param int|string $pharmacy_id
     */
    public function store(Request $request,$pharmacy_id) {
        if(Auth::user()->pharmacy_balance_ban()) {
            return redirect("billing/".Auth::user()->pharmacy_id, 302);
        }
        abort_unless(Auth::user()->can('manage-pharmacy', $pharmacy_id), 403, AccessMessages::NO_PERMISSION);
        if($request->request->has('user_id')) {
            return $this->tariffAnswer(DB::table('pharmacys')->where('id',$pharmacy_id)->exists() ? $request->input('user_id') : null);
        }
        if($request->input('save')>0) {
            $this->validateNewOrder($request, $pharmacy_id);
            $id_max = $this->createOrder($request, $pharmacy_id);
            $this->announceNewOrder($request, $pharmacy_id, $id_max);
        }
        return redirect("orders/$pharmacy_id".(isset($id_max) ? "?added=$id_max" : ""));
    }

    /**
     * The delivery tariff of a patient's ZIP area. The order form asks for it when a customer is chosen.
     *
     * @param  int|string|null  $patient_id
     * @return string JSON for the form
     */
    private function tariffAnswer($patient_id): string
    {
        $patient = $patient_id === null ? null : DB::table('users')->where('id',$patient_id)->first(['zip']);
        $zip_tariff = empty($patient) ? null : DB::table('area_zip')->where('area_zip.zip',$patient->zip)->join('area', 'area_zip.area_id', '=', 'area.id')->select("area.tariff")->first();

        return json_encode(['message' => 'OK', 'tariff' => empty($zip_tariff) ? 0 : $zip_tariff->tariff]);
    }

    /**
     * @param  int|string  $pharmacy_id
     */
    private function validateNewOrder(Request $request, $pharmacy_id): void
    {
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
    }

    /**
     * Stores a new order with its prescriptions.
     *
     * @param  int|string  $pharmacy_id
     * @return int the new order's id
     */
    private function createOrder(Request $request, $pharmacy_id): int
    {
        [$copay, $statuse_copay] = $this->copayFields($request);
        $fridge = (empty($request->input('fridge')))?'0':$request->input('fridge');
        $special_instructions = (empty($request->input('special_instructions')))?NULL:addslashes($request->input('special_instructions'));
        $data = $this->rxRows($request);
        $delivery_date = $this->deliveryDate($request);
        $delivery_time_range = $this->deliveryTimeRange($request);
        $driver_id = !empty($request->input('driver')) ? $request->input('driver') : NULL;
        $order_row = !empty($request->input('facility'))
            ? ['statuse_id' => OrderStatus::READY_FOR_PICKUP, 'pharmacy_id' => $pharmacy_id, 'medic_id'=>Auth::id(), 'driver_id'=>$driver_id, 'user_id' => $request->input('facility'), 'facility'=>true, 'copay' => $copay, 'statuse_copay' => $statuse_copay, 'delivery_method_id' => $request->input('delivery_method'), 'special_instructions' => $special_instructions, 'count_bags' => $request->input('count_bags') ?? 1, 'extra_charge_driver'=>floatval($request->input('extra_charge_driver')), 'type_driver' => $request->input('type_driver'), 'delivery_time_id' => $request->input('delivery_time'), 'delivery_time_range' => $delivery_time_range, 'delivery_date'=>$delivery_date, 'fridge' => $fridge,'family_id' => $request->input('family_id')]
            : ['statuse_id' => OrderStatus::READY_FOR_PICKUP, 'pharmacy_id' => $pharmacy_id, 'medic_id'=>Auth::id(), 'driver_id'=>$driver_id, 'user_id' => $request->input('user'), 'copay' => $copay, 'statuse_copay' => $statuse_copay, 'delivery_method_id' => $request->input('delivery_method'), 'special_instructions' => $special_instructions, 'count_bags' => $request->input('count_bags') ?? 1, 'extra_charge_driver'=>floatval($request->input('extra_charge_driver')), 'type_driver' => $request->input('type_driver'), 'delivery_time_id' => $request->input('delivery_time'), 'delivery_time_range' => $delivery_time_range, 'delivery_date'=>$delivery_date, 'fridge' => $fridge,'family_id' => $request->input('family_id')];

        return DB::transaction(function () use ($order_row, $data) {
            $order_id = Order::query()->insertGetId($order_row);
            Rx::query()->insert(array_map(fn ($rx) => ['order_id' => $order_id] + $rx, $data));
            return $order_id;
        });
    }

    /**
     * Tells the patient their order was created, and dispatch when it is a rush order.
     *
     * @param  int|string  $pharmacy_id
     */
    private function announceNewOrder(Request $request, $pharmacy_id, int $id_max): void
    {
        $us = DB::table('users')->where('id', !empty($request->input('facility')) ? $request->input('facility') : $request->input('user'))->first(['primary_address', 'address', 'address2', 'address3', 'apartment', 'apartment2', 'apartment3']);
        if($us->primary_address==3){
            $user_address = !empty($us->apartment) ? $us->address3.' Apt '.$us->apartment3 : $us->address3;
        } elseif($us->primary_address==2){
            $user_address = !empty($us->apartment) ? $us->address2.' Apt '.$us->apartment2 : $us->address2;
        } else {
            $user_address = !empty($us->apartment) ? $us->address.' Apt '.$us->apartment : $us->address;
        }
        Notifications::send_push($request->input('user'),"QuikMedix","created your order #$id_max (medicines), which will be delivered to: $user_address. If the address is wrong, please contact ".\App\Support\Branding::supportContact()." as soon as possible");
        if($request->input('delivery_time')==3 || $request->input('delivery_time')==4) {
            $this->announceRushOrder($pharmacy_id, $id_max);
        }
    }

    /**
     * @param  int|string  $pharmacy_id
     * @param  int|string  $order_id
     */
    private function announceRushOrder($pharmacy_id, $order_id): void
    {
        Notifications::send_push_web(array_map('strval', User::where('role', "admin")->orWhere("role","logist")->pluck('id')->toArray()),
            "Attention!",
            "Urgent order No.".$order_id." has been created, which needs to be processed promptly.",
            url('/')."/orders/".$pharmacy_id."?statuse%5B%5D=1",
            "rush_order"
        );
    }

    /**
     * @return array{0: string|float, 1: string} the copay and its status: none, to collect, or already paid at the pharmacy
     */
    private function copayFields(Request $request): array
    {
        $copay = (empty($request->input('copay')))?'0':round($request->input('copay'),2);
        $statuse_copay = (empty($request->input('copay')))?'1':'2';
        if(!empty($request->input('copay_paid_pharm'))) {
            $statuse_copay='6';
        }

        return [$copay, $statuse_copay];
    }

    /**
     * The prescription rows typed into the order form. rxs.rx_id stores "<RX#>-<Rf#>".
     *
     * @return list<array{rx_id: string, rx_date: ?string, rx_count: int|string, rx_recipient: ?string}>
     */
    private function rxRows(Request $request): array
    {
        $rx_ids = $request->input('rx_id');
        $rf_ids = $request->input('rf_id');
        $rx_counts = $request->input('rx_count');
        $rx_dates = $request->input('rx_date');
        $rx_recipients = $request->input('rx_recipient');
        $data=[];
        foreach(array_keys($rx_ids ?? []) as $key){
            $data[]=["rx_id"=>str_replace([" ",'-',','],'',$rx_ids[$key]).'-'.($rf_ids[$key] ?? ''),"rx_date"=>$rx_dates[$key] ?? null,"rx_count"=>$rx_counts[$key] ?? 1,"rx_recipient"=>empty($rx_recipients[$key]) ? NULL : $rx_recipients[$key]];
        }

        return $data;
    }

    /** The chosen delivery date; without one, tomorrow for next-day delivery and today otherwise. */
    private function deliveryDate(Request $request): string
    {
        if(!empty($request->input('delivery_date'))){
            return date("Y-m-d",strtotime($request->input('delivery_date')));
        }

        // Use the app's timezone; the database server's CURDATE() is UTC.
        return $request->input('delivery_time')=="1" ? now()->addDay()->toDateString() : now()->toDateString();
    }

    /** The chosen delivery window as "from;to"; without one, the whole day. */
    private function deliveryTimeRange(Request $request): string
    {
        return empty($request->input('delivery_time_range')) ? "9:00 AM;12:00 AM" : $request->input('delivery_time_range');
    }

    /**
     * @param int|string $pharmacy_id
     * @param int|string $order_id
     */
    public function show($pharmacy_id,$order_id) {
        Gate::authorize('access-order', $order_id);
        if($pharmacy_id==0) {
            $order = DB::table('orders')->where('orders.id',$order_id)->first();
            abort_if($order === null, 404, 'Order not found');
            $pharmacy_id = $order->pharmacy_id;
        }
        $order = DB::table('orders')->join('users', 'orders.user_id', '=', 'users.id')->leftJoin('users as medic', 'orders.medic_id', '=', 'medic.id')->leftJoin('statuses', 'orders.statuse_id', '=', 'statuses.id')->leftJoin('statuses_copay', 'orders.statuse_copay', '=', 'statuses_copay.id')->join('delivery_methods', 'orders.delivery_method_id', '=', 'delivery_methods.id')->join('delivery_times', 'orders.delivery_time_id', '=', 'delivery_times.id')->join('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id')->select('orders.id', 'orders.pharmacy_id','orders.delivery_address','orders.delivery_location', 'orders.eta', 'orders.created', 'orders.finish', 'orders.family_id', 'orders.delivery_date', 'orders.delivery_time_range', 'orders.statuse_id', 'orders.rating', 'orders.signature', 'orders.fridge', 'orders.facility', 'orders.special_instructions', 'orders.dispatcher_notes', 'orders.user_id',  'orders.copay', 'orders.driver_id', 'orders.count_bags', 'orders.drop_off_photo', 'orders.signature_photo', 'orders.signature_type', 'orders.medic_id', 'medic.name as medicname', 'medic.last_name as mediclast_name', 'users.name as username', 'users.last_name as last_name', 'users.os as useros', 'delivery_methods.name as delivery_method', 'delivery_times.name as delivery_time', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end as useraddress'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end as userapartment'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end as userzip'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end as userlocation'), 'users.phone as userphone', 'users.home_phone as userhomephone', 'pharmacys.name as pharmacyname', 'pharmacys.address as pharmacyaddress','pharmacys.phone as pharmacyphone', 'pharmacys.location as pharmacylocation', 'statuses.name as statusename','statuses.color as statusecolor', 'orders.statuse_copay', 'statuses_copay.name as statuse_copay_name','statuses_copay.color as statuse_copay_color')->where('orders.id',$order_id)->groupBy('orders.id', 'orders.pharmacy_id', 'orders.eta', 'orders.statuse_id', 'orders.facility', 'orders.created', 'orders.finish', 'orders.delivery_date', 'orders.delivery_time_range', 'orders.driver_id', 'orders.rating', 'orders.family_id', 'orders.count_bags', 'orders.signature', 'orders.fridge', 'orders.special_instructions', 'orders.dispatcher_notes', 'orders.copay', 'orders.drop_off_photo','orders.signature_photo', 'orders.signature_type', 'orders.user_id', 'users.name', 'users.last_name', 'medic.name', 'medic.last_name','users.os', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end'), 'orders.medic_id', 'users.phone','users.home_phone','pharmacys.name', 'delivery_methods.name', 'delivery_times.name', 'pharmacys.location', 'pharmacys.address','pharmacys.phone', 'statuses.name','statuses.color', 'orders.statuse_copay', 'statuses_copay.name','statuses_copay.color','orders.delivery_address','orders.delivery_location')->first();
        abort_if($order === null, 404, 'Order not found');
        abort_unless((Auth::user()->hasRole('medic') && Auth::user()->pharmacy_id==$pharmacy_id) || ((Auth::user()->can('admin'))) || (Auth::user()->hasRole('logist')) || (Auth::user()->hasRole('driver') && Auth::user()->id==$order->driver_id) || (Auth::user()->hasRole('user') && Auth::user()->id==$order->user_id), 403, AccessMessages::NO_PERMISSION);
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
        [$pendingException, $pharmacyStaff] = Gate::allows('review-delivery-report', $order->pharmacy_id) ? $this->dispatchReview($order) : [null, collect()];
        $res_view = view('orders.show',['statuses'=>DB::table('statuses')->orderBy('id')->get(), 'pendingException'=>$pendingException, 'pharmacyStaff'=>$pharmacyStaff, 'order'=>$order,'rxs'=>$rxs,'family'=>$family,'dispatcher_notes'=>$dispatcher_notes,'customer_notes'=>$customer_notes,'additional_recipients'=>$additional_recipients,'orders_transitions'=>$orders_transitions,'medicines'=>$medicines,'driver'=>$driver,'locations'=>$locations,'locationDrivers'=>$locationDrivers,'title'=>'Order Show','br1'=>'Orders','br2'=>'Order Show']);
        if(request()->query->has('ajax')) {
            return $res_view->renderSections();
        } else {
            return $res_view;
        }
    }

    /**
     * The driver's pending unavailable/refused report and the pharmacy staff dispatch can chat with to verify it.
     *
     * @return array{0: ?\stdClass, 1: \Illuminate\Support\Collection<int, \stdClass>}
     */
    private function dispatchReview(\stdClass $order): array
    {
        $pendingException = DB::table('order_exceptions')
            ->leftJoin('users as driver', 'order_exceptions.driver_id', '=', 'driver.id')
            ->where('order_exceptions.order_id', $order->id)->where('order_exceptions.status', 'pending')
            ->select('order_exceptions.*', 'driver.name as driver_name', 'driver.last_name as driver_last_name', 'driver.phone as driver_phone')
            ->first();
        if ($pendingException === null) {
            return [null, collect()];
        }
        $pharmacyStaff = DB::table('users')->where('role', 'medic')->where('isactive', 1)->where('isblocked', 0)
            ->where('pharmacy_id', $order->pharmacy_id)->orderBy('id')->get(['id', 'name', 'last_name']);

        return [$pendingException, $pharmacyStaff];
    }

    /**
     * @param int|string $pharmacy_id
     * @param int|string $order_id
     */
    public function handleShowAction(Request $request,$pharmacy_id,$order_id, RotateSignature $rotateSignature) {
        Gate::authorize('access-order', $order_id);
        $order = DB::table('orders')->join('users', 'orders.user_id', '=', 'users.id')->leftJoin('statuses', 'orders.statuse_id', '=', 'statuses.id')->leftJoin('statuses_copay', 'orders.statuse_copay', '=', 'statuses_copay.id')->join('delivery_methods', 'orders.delivery_method_id', '=', 'delivery_methods.id')->join('delivery_times', 'orders.delivery_time_id', '=', 'delivery_times.id')->join('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id')->select('orders.id', 'orders.pharmacy_id', 'orders.eta', 'orders.created', 'orders.finish', 'orders.statuse_id', 'orders.signature', 'orders.fridge', 'orders.special_instructions', 'orders.dispatcher_notes', 'orders.user_id',  'orders.copay', 'orders.driver_id', 'orders.count_bags', 'orders.drop_off_photo', 'orders.signature_photo', 'orders.signature_type', 'users.name as username', 'users.last_name as last_name', 'users.os as useros', 'delivery_methods.name as delivery_method', 'delivery_times.name as delivery_time', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end as useraddress'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end as userapartment'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end as userzip'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end as userlocation'), 'users.phone as userphone', 'pharmacys.name as pharmacyname', 'pharmacys.address as pharmacyaddress','pharmacys.phone as pharmacyphone', 'pharmacys.location as pharmacylocation', 'statuses.name as statusename','statuses.color as statusecolor', 'orders.statuse_copay', 'statuses_copay.name as statuse_copay_name','statuses_copay.color as statuse_copay_color')->where('orders.id',$order_id)->groupBy('orders.id', 'orders.pharmacy_id', 'orders.eta', 'orders.statuse_id', 'orders.created', 'orders.finish', 'orders.driver_id', 'orders.count_bags', 'orders.signature', 'orders.fridge', 'orders.special_instructions', 'orders.dispatcher_notes', 'orders.copay', 'orders.drop_off_photo','orders.signature_photo', 'orders.signature_type', 'orders.user_id', 'users.name', 'users.last_name', 'users.os', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end'), 'users.phone','pharmacys.name', 'delivery_methods.name', 'delivery_times.name', 'pharmacys.location', 'pharmacys.address','pharmacys.phone', 'statuses.name','statuses.color', 'orders.statuse_copay', 'statuses_copay.name','statuses_copay.color')->first();
        abort_unless((Auth::user()->hasRole('medic') && Auth::user()->pharmacy_id==$pharmacy_id) || ((Auth::user()->can('admin'))) ||  (Auth::user()->hasRole('logist')) || (Auth::user()->hasRole('driver') && Auth::user()->id==$order->driver_id) || (Auth::user()->hasRole('user') && Auth::user()->id==$order->user_id), 403, AccessMessages::NO_PERMISSION);
        if(request()->request->has('dispatcher_notes')) {
            Note::query()->insert(["order_id"=>$order_id,"user_id"=>Auth::user()->id,"type"=>"1",'note'=>addslashes($request->input('dispatcher_notes'))]);
            return json_encode([
                'message' => 'OK'
            ]);
        }
        if($request->hasFile('drop_off_photo')) {
            $file = $request->file('drop_off_photo');
            $file->move(public_path() . '/images/drop_off/',\App\Support\PublicUpload::name($request->file('drop_off_photo')));
            $src = '/images/drop_off/'.\App\Support\PublicUpload::name($request->file('drop_off_photo'));
            Order::query()->where('orders.id',$order_id)->update(['drop_off_photo'=>$src]);
        }
        if($request->hasFile('signature_photo')) {
            $file = $request->file('signature_photo');
            $file->move(public_path() . '/images/signature/',\App\Support\PublicUpload::name($request->file('signature_photo')));
            $src = '/images/signature/'.\App\Support\PublicUpload::name($request->file('signature_photo'));
            Order::query()->where('orders.id',$order_id)->update(['signature_photo'=>$src]);
        }
        if($request->input('rotate_signature')>0 && !empty($order->signature_photo)) {
            $src = $rotateSignature->handle($order->signature_photo);
            Order::query()->where('orders.id',$order_id)->update(['signature_photo'=>$src]);
        }
        if($request->input('eta_calculate')>0) {
            if($order->driver_id>0) {
                app(RecalculateDriverEta::class)->handle($order->driver_id,true);
            }
            return redirect()->back()->with('success', "Successfully the route ETA was updated.");
        }
        if($request->input('paid')>0) {
            if(!empty($order->driver_id)){
                $cash_log = DB::table('cash_log')->where("order_id",$order_id)->where("driver_id",$order->driver_id)->first();
                if(!empty($cash_log)) {
                    CashLog::query()->where('id',$cash_log->id)->update(["copay"=>$order->copay]);
                } else {
                    CashLog::query()->insert(["order_id"=>$order_id,"driver_id"=>$order->driver_id,"copay"=>$order->copay]);   
                }
            }
            Order::query()->where('id',$order_id)->update(['statuse_copay'=>4]);
            return redirect()->back()->with('success', "Successfully paid co-pay.");
        }
        if($request->input('not_paid')>0) {
            Order::query()->where('id',$order_id)->update(['statuse_copay'=>5]);
            return redirect()->back()->with('success', "Successfully changed status co-pay.");
        }
        return redirect("orders/$pharmacy_id/show/$order_id");
    }

    /**
     * @param int|string $order_id
     */
    public function preview($order_id)
    {
        $order = DB::table('orders')->join('users', 'orders.user_id', '=', 'users.id')->leftJoin('users as medic', 'orders.medic_id', '=', 'medic.id')->leftJoin('statuses', 'orders.statuse_id', '=', 'statuses.id')->leftJoin('statuses_copay', 'orders.statuse_copay', '=', 'statuses_copay.id')->join('delivery_methods', 'orders.delivery_method_id', '=', 'delivery_methods.id')->join('delivery_times', 'orders.delivery_time_id', '=', 'delivery_times.id')->join('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id')->select('orders.id', 'orders.pharmacy_id', 'orders.eta', 'orders.created', 'orders.finish', 'orders.delivery_date', 'orders.delivery_time_range', 'orders.statuse_id', 'orders.rating', 'orders.signature', 'orders.fridge', 'orders.facility', 'orders.special_instructions', 'orders.dispatcher_notes', 'orders.user_id',  'orders.copay', 'orders.driver_id', 'orders.count_bags', 'orders.drop_off_photo', 'orders.signature_photo', 'orders.signature_type', 'orders.medic_id', 'medic.name as medicname', 'medic.last_name as mediclast_name', 'users.name as username', 'users.last_name as last_name', 'users.os as useros', 'delivery_methods.name as delivery_method', 'delivery_times.name as delivery_time', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end as useraddress'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end as userapartment'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end as userzip'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end as userlocation'), 'users.phone as userphone', 'users.home_phone as userhomephone', 'pharmacys.name as pharmacyname', 'pharmacys.address as pharmacyaddress','pharmacys.phone as pharmacyphone', 'pharmacys.location as pharmacylocation', 'statuses.name as statusename','statuses.color as statusecolor', 'orders.statuse_copay', 'statuses_copay.name as statuse_copay_name','statuses_copay.color as statuse_copay_color')->where('orders.id',$order_id)->groupBy('orders.id', 'orders.pharmacy_id', 'orders.eta', 'orders.statuse_id', 'orders.facility', 'orders.created', 'orders.finish', 'orders.delivery_date', 'orders.delivery_time_range', 'orders.driver_id', 'orders.rating', 'orders.count_bags', 'orders.signature', 'orders.fridge', 'orders.special_instructions', 'orders.dispatcher_notes', 'orders.copay', 'orders.drop_off_photo','orders.signature_photo', 'orders.signature_type', 'orders.user_id', 'users.name','users.last_name', 'medic.name', 'medic.last_name', 'users.os', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end'), 'orders.medic_id','users.phone','users.home_phone','pharmacys.name', 'delivery_methods.name', 'delivery_times.name', 'pharmacys.location', 'pharmacys.address','pharmacys.phone', 'statuses.name','statuses.color', 'orders.statuse_copay', 'statuses_copay.name','statuses_copay.color')->first();
        abort_if(empty($order), 404, 'Order not found');
        $pharmacy_id = $order->pharmacy_id;
        abort_unless((Auth::user()->hasRole('medic') && Auth::user()->pharmacy_id==$pharmacy_id) || ((Auth::user()->can('admin'))) || (Auth::user()->hasRole('logist')) || (Auth::user()->hasRole('driver') && Auth::user()->id==$order->driver_id) || (Auth::user()->hasRole('user') && Auth::user()->id==$order->user_id), 403, AccessMessages::NO_PERMISSION);
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
    }

    /**
     * @param int|string $pharmacy_id
     * @param int|string $order_id
     */
    public function edit($pharmacy_id,$order_id) {
        Gate::authorize('access-order', $order_id);
        abort_unless(Auth::user()->can('manage-pharmacy', $pharmacy_id) || Auth::user()->hasAnyRole('logist'), 403, AccessMessages::NO_PERMISSION);
        $order = Order::query()->where('id', $order_id)->first();
        $rxs = Rx::query()->where('order_id', $order_id)->get();
        $users = DB::table('users')->where('role', 'user')->where('pharmacy_id', $pharmacy_id)->select('users.id','users.name','users.last_name','users.phone')->get();
        $facilitys = DB::table('users')->where('role', 'facility')->where('pharmacy_id', $pharmacy_id)->select('users.id','users.name','users.last_name','users.phone')->get();
        $drivers = DB::table('users')->where('role', 'driver')->whereNull("pharmacy_id")->get();
        $drivers2 = DB::table('users')->where('role', 'driver')->where("pharmacy_id",$pharmacy_id)->get();
        $medicines = DB::table('medicines')->get();
        $delivery_methods = DeliveryMethod::query()->get();
        $delivery_times = DeliveryTime::query()->get();
        $medicine = Medicine::query()->where('order_id', $order_id)->get();
        $count = count($medicine);
        $statuses = Status::query()->get();
        $family_members = FamilyMember::query()->where('user_id', $order->user_id)->get();
        $additional_recipients = AdditionalRecipient::query()->where('user_id', $order->user_id)->get();
        $pharmacy=Pharmacy::query()->where('pharmacys.id',$pharmacy_id)->first();
        $patient=DB::table('users')->where('users.id',$order->user_id)->first();
        $time_ranges = ["9:00 AM","10:00 AM","11:00 AM","12:00 PM", "1:00 PM","2:00 PM","3:00 PM","4:00 PM","5:00 PM","6:00 PM","7:00 PM","8:00 PM","9:00 PM","10:00 PM","11:00 PM","12:00 AM"];
        $zip_tariff=AreaZip::query()->where('area_zip.zip',$patient->zip)->join('area', 'area_zip.area_id', '=', 'area.id')->select("area.tariff")->first();
        $res_view = view('orders.edit',['order'=>$order, 'rxs'=>$rxs, "time_ranges"=>$time_ranges, 'count'=>$count, 'users'=>$users, 'facilitys'=>$facilitys, 'pharmacy'=>$pharmacy, 'zip_tariff'=>$zip_tariff, 'drivers'=>$drivers, 'drivers2'=>$drivers2, 'family_members'=>$family_members, 'additional_recipients'=>$additional_recipients, 'statuses'=>$statuses, 'medicines'=>$medicines, 'medicine'=>$medicine, 'delivery_methods'=>$delivery_methods, 'delivery_times'=>$delivery_times, 'title'=>'Order Edit','br1'=>'Orders','br2'=>'Order Edit','alert'=>'']);
        if(request()->query->has('ajax')) {
            return $res_view->renderSections();
        } else {
            return $res_view;
        }
    }

    /**
     * @param int|string $pharmacy_id
     * @param int|string $order_id
     */
    public function update(Request $request,$pharmacy_id,$order_id, ChangeOrderStatus $changeOrderStatus) {
        Gate::authorize('access-order', $order_id);
        abort_unless(Auth::user()->can('manage-pharmacy', $pharmacy_id) || Auth::user()->hasAnyRole('logist'), 403, AccessMessages::NO_PERMISSION);
        if($request->request->has('user_id')) {
            return $this->tariffAnswer($request->input('user_id'));
        }
        if($request->input('save')>0) {
            $order = DB::table('orders')->where('id', $order_id)->first();
            abort_if($order === null, 404, 'Order not found');
            $validated = $request->validate([
                'statuse' => ['sometimes', 'required', 'integer', 'exists:statuses,id'],
                'count_bags' => ['sometimes', 'required', 'integer', 'min:1', 'max:10'],
            ]);
            $statusId = (int) ($validated['statuse'] ?? $order->statuse_id);
            if ($statusId !== (int) $order->statuse_id && ! Auth::user()->can('change-order-status')) {
                abort_unless((int) $order->statuse_id === 1 && $statusId === 5, 403);
            }
            $changeOrderStatus->assertCanChangeManually((int) $order->statuse_id, $statusId);

            $this->saveOrderChanges($request, $order, $validated);
            if($request->input('statuse')==1 && ($request->input('delivery_time')==3 || $request->input('delivery_time')==4)) {
                $this->announceRushOrder($pharmacy_id, $order_id);
            }
            $changeOrderStatus->handle((int) $order_id, $statusId);
            Medicine::query()->where('order_id', $order_id)->delete();
        }
        return redirect("orders/$pharmacy_id");
    }

    /**
     * Replaces an order's prescriptions and writes the form's fields to it.
     *
     * @param  array<string, mixed>  $validated
     */
    private function saveOrderChanges(Request $request, object $order, array $validated): void
    {
        $order_id = $order->id;
        [$copay, $statuse_copay] = $this->copayFields($request);
        $fridge = (empty($request->input('fridge')))?'0':$request->input('fridge');
        $special_instructions = (empty($request->input('special_instructions')))?NULL:addslashes($request->input('special_instructions'));
        $delivery_date = $this->deliveryDate($request);
        $delivery_time_range = $this->deliveryTimeRange($request);
        $driver_id = $request->input('type_driver')==2 ? $request->input('driver2') : $request->input('driver');
        DB::transaction(function () use ($request, $order, $order_id, $validated, $copay, $statuse_copay, $fridge, $special_instructions, $delivery_date, $delivery_time_range, $driver_id): void {
            Rx::query()->where('order_id',$order_id)->delete();
            Rx::query()->insert(array_map(fn ($rx) => ['order_id' => $order_id] + $rx, $this->rxRows($request)));
            if($request->input('statuse_copay')==4){
                Order::query()->where('id', $order_id)->update(['statuse_copay' => 4]);
            }
            Order::query()->where('id', $order_id)->update(['user_id' => $order->user_id, 'driver_id' => $driver_id, 'extra_charge_driver'=>floatval($request->input('extra_charge_driver')), 'copay' => $copay, 'statuse_copay' => $statuse_copay, 'special_instructions' => $special_instructions, 'delivery_method_id' => $request->input('delivery_method'), 'count_bags' => $validated['count_bags'] ?? max(1, (int) $order->count_bags), 'type_driver' => $request->input('type_driver'), 'delivery_time_id' => $request->input('delivery_time'),'delivery_time_range' => $delivery_time_range,'delivery_date'=>$delivery_date,'fridge' => $fridge, 'family_id' => $request->input('family_id')]);
        });
    }

    /**
     * @param int|string $pharmacy_id
     */
    public function statistic($pharmacy_id) {
        $pharmacy = Pharmacy::query()->where("id",$pharmacy_id)->first();
        if(!empty($pharmacy)) {
            abort_unless(Auth::user()->can('manage-pharmacy', $pharmacy_id), 403, AccessMessages::NO_PERMISSION);
            $date = date('Y-m-d');
            $statuse_id = '';
            $statuses = Status::query()->get();
            $polygons = Area::query()->select('id','name',DB::raw('ST_AsText(polygon) as polygon'))->get();
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
            return abort(404, "Not found pharmacy");
        }
    }

    /**
     * @param int|string $pharmacy_id
     */
    public function statisticForDate($pharmacy_id,Request $request) {
        $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
        if(!empty($pharmacy)) {
            abort_unless(Auth::user()->can('manage-pharmacy', $pharmacy_id), 403, AccessMessages::NO_PERMISSION);
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
            return abort(404, "Not found pharmacy");
        }
    }

    /**
     * @param int|string $pharmacy_id
     */
    public function markReady($pharmacy_id) {
        abort_unless(Auth::user()->can('manage-pharmacy', $pharmacy_id), 403, AccessMessages::NO_PERMISSION);
        Order::query()->where("pharmacy_id",$pharmacy_id)->where("orders.ready",'0')->where("orders.statuse_id",1)->update(["ready"=>'1']);
        return json_encode([
            'message' => 'OK'
        ]);
    }

    /**
     * @param int|string $order_id
     */
    public function callRecordings($order_id) {
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
