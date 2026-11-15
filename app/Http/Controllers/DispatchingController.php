<?php

namespace App\Http\Controllers;

use App\Actions\RecalculateDriverEta;
use App\Actions\ReturnOrderToOffice;
use App\Http\Controllers\Concerns\RendersLegacyPages;
use App\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use App\Support\AccessMessages;

/**
 * Dispatching: the live board of drivers on shift, the dispatcher's decisions about an order's
 * delivery confirmation, and one driver's route page. Formerly the dispatching* actions of
 * LexaAdmin; the board and driver page queries were moved as they were.
 */
class DispatchingController extends Controller
{
    use RendersLegacyPages;

    /** A decision that sends an order back clears what the driver recorded at the door. */
    private const NO_PROOF_OF_DELIVERY = ['drop_off_photo' => null, 'signature_photo' => null, 'signature_type' => null];

    public function __construct()
    {
        $this->middleware(['auth', '2fa', 'active']);
    }

    /**
     * The live board: up to thirty stops of the drivers on shift, soonest arrival first.
     */
    public function index(Request $request): View
    {
        $this->authorizeDispatchStaff($request);

        $orders = User::where('users.role','driver')->where('users.work_now','1')->where('users.isblocked',0)->leftJoin("routes_priority","routes_priority.driver_id","=","users.id")->join("orders","routes_priority.order_id","=","orders.id")->leftJoin("try_call","orders.id","=","try_call.order_id")->leftJoin("notes", function($leftJoin) {$leftJoin->on('orders.id', '=', 'notes.order_id');$leftJoin->where('notes.type', '=', 1);})->leftJoin("notes as notes2", function($leftJoin) {$leftJoin->on('orders.id', '=', 'notes2.order_id');$leftJoin->where('notes2.type', '=', 2);})->join("users as users2","orders.user_id","=","users2.id")->join("pharmacys","orders.pharmacy_id","=","pharmacys.id")->where('routes_priority.type','patient')->where('orders.actual','0')->havingRaw("max(try_call.created) < (now() - interval 10 minute) OR COUNT(try_call.id) = 0")
        ->select("users.id as driver_id","routes_priority.priority",DB::raw("CONCAT(users.name, ' ', users.last_name) as driver_name"),"users.phone as driver_phone",DB::raw("CONCAT(users2.name, ' ', users2.last_name) as patient_name"),"users2.phone as patient_phone","users2.address as patient_address","users2.location as patient_location","users2.apartment as patient_apartment","orders.eta","orders.copay","orders.special_instructions",DB::raw("GROUP_CONCAT(DISTINCT CONCAT(notes.created,'!-',notes.note) SEPARATOR ';/') as notes_dispetch"),DB::raw("GROUP_CONCAT(CONCAT(notes2.created,'!-',notes2.note) SEPARATOR ';/') as notes_cust"),DB::raw("GROUP_CONCAT(DISTINCT routes_priority.order_id SEPARATOR ',') as order_id"),"routes_priority.type_id",DB::raw("GROUP_CONCAT(DISTINCT(pharmacys.name) SEPARATOR ',') as pharmacy_name"),DB::raw("GROUP_CONCAT(DISTINCT(pharmacys.phone) SEPARATOR ',') as pharmacy_phone"),DB::raw("count(DISTINCT try_call.id)/count(DISTINCT routes_priority.order_id) as count_call"),DB::raw("case when max(try_call.created) >= now() - interval 10 minute then 1 else 0 end as call_disabled"))
        ->groupBy("users.id","users.name","users.last_name","users.phone","users2.name","users2.last_name","users2.phone","users2.address","users2.apartment","users2.location","routes_priority.type_id","routes_priority.priority","orders.eta","orders.copay","orders.special_instructions")->orderBy(DB::raw("0 - orders.eta"),"desc")->limit(30);
        if(!empty(Auth::user()->zone_id)){
            $orders=$orders->where('pharmacys.zone_id',Auth::user()->zone_id);
        }
        $orders=$orders->get();
        $orders_count = User::where('users.role','driver')->where('users.work_now','1')->where('users.isblocked',0)->leftJoin("routes_priority","routes_priority.driver_id","=","users.id")->join("orders","routes_priority.order_id","=","orders.id")->join("users as users2","orders.user_id","=","users2.id")->where('routes_priority.type','patient')->where('orders.actual','0')->select(DB::raw('count(distinct users2.id) as count'))->first()->count;
        $routes_logs = DB::table('routes_priority_logs')->whereIn('driver_id',$orders->pluck('driver_id')->toArray())->whereRaw('Date(routes_priority_logs.created) = CURDATE()')->select(DB::raw('count(distinct routes_priority_logs.type,routes_priority_logs.type_id) as count_delivered'),'driver_id')->groupBy('driver_id')->get()->keyBy('driver_id');
        $routes_priority = DB::table('routes_priority')->whereIn('driver_id',$orders->pluck('driver_id')->toArray())->select(DB::raw('count(distinct routes_priority.type,routes_priority.type_id) as count_delivery'),'driver_id')->groupBy('driver_id')->get()->keyBy('driver_id');
        $locations_max = DB::table('locations')->whereIn('locations.user_id',$orders->pluck('driver_id')->toArray())->select(DB::raw('MAX(id) as id'),'user_id')->groupBy('user_id');
        $locations = DB::table('locations')->whereIn('locations.user_id',$orders->pluck('driver_id')->toArray())->joinSub($locations_max,'locations_max','locations_max.id','=','locations.id')->select('locations.user_id',DB::raw('MIN(locations.location) as location'))->groupBy('locations.user_id')->get()->keyBy('user_id');
        return view('dispatching.drivers',['orders'=>$orders,'orders_count'=>$orders_count,'routes_logs'=>$routes_logs,'routes_priority'=>$routes_priority,'locations'=>$locations,'title'=>'Dispatching','br1'=>'Dispatching','br2'=>'Index','alert'=>'']);
    }

    /**
     * One decision about the orders named in order_id (comma separated): a note, a skipped
     * confirmation call, confirmed, not confirmed, or a new delivery date. A note is saved on its
     * own and answered as JSON; everything else returns to the board.
     */
    public function update(Request $request, ReturnOrderToOffice $returnToOffice): string|RedirectResponse
    {
        $this->authorizeDispatchStaff($request);
        $orderIds = explode(',', $request->input('order_id'));

        if ($request->request->has('dispatcher_notes')) {
            foreach ($orderIds as $orderId) {
                $this->note($request, $orderId, $request->input('dispatcher_notes'));
            }

            return json_encode(['message' => 'OK']);
        }

        if ($request->input('skip') > 0) {
            foreach ($orderIds as $orderId) {
                DB::table('try_call')->insert(['order_id' => $orderId]);
            }
        }
        if ($request->input('confirmed') > 0) {
            DB::table('orders')->whereIn('id', $orderIds)->update(['actual' => '1']);
        }
        if ($request->input('not_confirmed') > 0) {
            foreach ($orderIds as $orderId) {
                $order = DB::table('orders')->where('id', $orderId)->first();
                DB::table('orders')->where('id', $orderId)->update(['actual' => '2'] + self::NO_PROOF_OF_DELIVERY);
                $returnToOffice->handle($order);
                DB::table('orders')->where('id', $orderId)->update(['not_delivered' => 1]);
                $this->note($request, $orderId, 'order not confirmed');
            }
        }
        if ($request->input('reschedule') > 0) {
            DB::table('orders')->whereIn('id', $orderIds)->update(['delivery_date' => $request->input('date')]);
            foreach ($orderIds as $orderId) {
                $order = DB::table('orders')->where('id', $orderId)->first();
                DB::table('orders')->where('id', $orderId)->update(self::NO_PROOF_OF_DELIVERY);
                $returnToOffice->handle($order);
                $this->note($request, $orderId, 'reschedule to '.$request->input('date'));
            }
        }

        return redirect('dispatching');
    }

    /**
     * One driver's open orders, stops and last location.
     *
     * @param  int|string  $driver_id
     */
    public function show(Request $request, $driver_id): View|array
    {
        $this->authorizeDispatchStaff($request);

        $driver = DB::table('users')->where('users.id',$driver_id)->leftJoin('drivers_eta', function($query) {
            $query->on('users.id','=','drivers_eta.driver_id')
            ->whereRaw('drivers_eta.id IN (select MAX(a2.id) from drivers_eta as a2 join users as u2 on u2.id = a2.driver_id group by u2.id)');
        })->select("users.*","drivers_eta.eta","drivers_eta.distance")->first();
        $orders = DB::table('orders')->join('users', 'orders.user_id', '=', 'users.id')->join('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id')->select(DB::raw("GROUP_CONCAT(orders.id) as id"), 'orders.user_id', 'users.name as username', 'users.last_name as userlast_name', 'users.phone as userphone', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end as useraddress'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end as userapartment'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end as userzip'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end as userlocation'))->whereIn("statuse_id",[1,2,3,7,8,9])->where('driver_id',$driver_id)->groupBy('orders.user_id','users.name','users.last_name','users.phone',DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end'))->orderBy("orders.id","desc");
        $orders = $orders->get();
        $patients_locations=array();
        $pharmacy_locations=array();
        foreach($orders as $row) {
            array_push($patients_locations,array('id'=>$row->user_id,'order_id'=>$row->id,'location'=>$row->userlocation));
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
        $routes_priority1 = DB::table('routes_priority')->select("driver_id", DB::raw("GROUP_CONCAT(order_id) as order_id"), "type", "type_id", DB::raw("min(priority) as priority"))->where('driver_id', $driver_id)->where('type', '!=', 'office')->groupBy("type","type_id","driver_id")->orderBy("priority","asc")->get();
        $routes_priority0 = DB::table('routes_priority')->select("driver_id", DB::raw("GROUP_CONCAT(order_id) as order_id"), "type", "type_id", "priority")->where('driver_id', $driver_id)->where('type', 'office')->groupBy("type","type_id","driver_id", "priority")->orderBy("priority","asc")->get();
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
        $res_view = view('dispatching.show',['orders'=>$orders,'locations'=>$locations,'driver'=>$driver,'patient_routes_priority'=>$patient_routes_priority,'pharmacy_routes_priority'=>$pharmacy_routes_priority,'routes_priority'=>$routes_priority,'patients_locations'=>$patients_locations,'show_ids'=>$show_ids,'show_priority'=>$show_priority,'pharmacy_locations'=>$pharmacy_locations,'title'=>'Process Detail','br1'=>'Process','br2'=>'Process Detail']);
        return $this->page($request, $res_view);
    }

    /**
     * The driver page's only action: recalculate the driver's ETAs. Any other post does nothing.
     *
     * @param  int|string  $driver_id
     */
    public function recalculateEta(Request $request, $driver_id, RecalculateDriverEta $recalculateEta): ?RedirectResponse
    {
        $this->authorizeDispatchStaff($request);

        if ($request->input('eta_calculate') > 0) {
            $recalculateEta->handle($driver_id);

            return redirect()->back()->with('success', 'Successfully the route ETA was updated.');
        }

        return null;
    }

    private function authorizeDispatchStaff(Request $request): void
    {
        abort_unless($request->user()->hasAnyRole('superadmin', 'admin', 'dispadmin', 'logist'), 403, AccessMessages::NO_PERMISSION);
    }

    private function note(Request $request, string $orderId, string $text): void
    {
        DB::table('notes')->insert(['order_id' => $orderId, 'user_id' => $request->user()->id, 'type' => '1', 'note' => addslashes($text)]);
    }
}
