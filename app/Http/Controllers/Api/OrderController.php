<?php

namespace App\Http\Controllers\Api;

use App\Actions\RequestDispatchApproval;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Orders as the mobile apps list, show and update them.
 */
class OrderController extends Controller
{
    public function ordersList() {
        if(Auth::user()->hasAnyRole('driver', 'user')) {
            if(Auth::user()->role == 'user') {
                $orders = DB::table('orders')->join('users', 'orders.user_id', '=', 'users.id')->join('statuses', 'orders.statuse_id', '=', 'statuses.id')->leftJoin('statuses_copay', 'orders.statuse_copay', '=', 'statuses_copay.id')->join('delivery_methods', 'orders.delivery_method_id', '=', 'delivery_methods.id')->join('delivery_times', 'orders.delivery_time_id', '=', 'delivery_times.id')->join('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id')->select('orders.id', 'orders.created' , 'orders.driver_id', 'orders.statuse_id', 'orders.copay', 'orders.actual', 'orders.pharmacy_id', 'orders.signature_photo', 'users.name as username', 'users.last_name as last_name', 'delivery_methods.name as delivery_method', 'delivery_times.name as delivery_time', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end as useraddress'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end as userapartment'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end as userzip'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end as userlocation'),'users.phone as userphone', 'pharmacys.name as pharmacyname', 'pharmacys.address as pharmacyaddress','pharmacys.location as pharmacylocation','pharmacys.phone as pharmacyphone', 'statuses.name as statusename','statuses.color as statusecolor','orders.statuse_copay', 'statuses_copay.name as statuse_copay_name')->where('orders.user_id',Auth::user()->id)->groupBy('orders.id', 'orders.statuse_id', 'orders.driver_id', 'orders.created', 'orders.signature_photo', 'orders.actual', 'delivery_methods.name', 'delivery_times.name', 'orders.copay', 'orders.pharmacy_id', 'users.name', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end'), 'users.phone','pharmacys.name', 'pharmacys.address','pharmacys.location','pharmacys.phone', 'statuses.name','statuses.color','orders.statuse_copay', 'statuses_copay.name','users.last_name')->orderBy('orders.id','desc');
            } else { // driver
                $orders = DB::table('orders')->join('users', 'orders.user_id', '=', 'users.id')->join('statuses', 'orders.statuse_id', '=', 'statuses.id')->leftJoin('statuses_copay', 'orders.statuse_copay', '=', 'statuses_copay.id')->join('delivery_methods', 'orders.delivery_method_id', '=', 'delivery_methods.id')->join('delivery_times', 'orders.delivery_time_id', '=', 'delivery_times.id')->join('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id')->select('orders.id', 'orders.created' , 'orders.driver_id', 'orders.statuse_id', 'orders.copay', 'orders.actual', 'orders.pharmacy_id', 'orders.signature_photo', 'users.name as username', 'users.last_name as last_name', 'delivery_methods.name as delivery_method', 'delivery_times.name as delivery_time', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end as useraddress'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end as userapartment'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end as userzip'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end as userlocation'),'users.phone as userphone', 'pharmacys.name as pharmacyname', 'pharmacys.address as pharmacyaddress','pharmacys.location as pharmacylocation','pharmacys.phone as pharmacyphone', 'statuses.name as statusename','statuses.color as statusecolor','orders.statuse_copay', 'statuses_copay.name as statuse_copay_name')->where('orders.driver_id',Auth::user()->id)->groupBy('orders.id', 'orders.statuse_id', 'orders.driver_id', 'orders.created', 'orders.signature_photo', 'orders.actual', 'delivery_methods.name', 'delivery_times.name', 'orders.copay', 'orders.pharmacy_id', 'users.name', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end'), 'users.phone','pharmacys.name', 'pharmacys.address','pharmacys.location','pharmacys.phone', 'statuses.name','statuses.color','orders.statuse_copay', 'statuses_copay.name','users.last_name')->orderBy('orders.id','desc');
            }
            if(!empty(request()->query('statuse'))) {
                $orders = $orders->whereIn('orders.statuse_id',request()->query('statuse'));
            }
            if(!empty(request()->query('pharmacy'))) {
                $orders = $orders->where('orders.pharmacy_id',request()->query('pharmacy'));
            }
            $orders = $orders->get();
            foreach($orders as $key=>$order) {
                if($order->statuse_id==3) {
                    if($order->driver_id>0) {
                        $locations = DB::table('locations')->whereIn('id', [DB::raw("select max(`id`) from locations GROUP BY user_id")])->where('user_id',$order->driver_id)->first();
                    } else {
                        $locations="";
                    }
                    if(!empty($locations)) {
                        $location=$locations->location;
                    } else {
                        $location=NULL;
                    }
                } else {
                    $location=NULL;
                }
                $order->statusename = OrderStatus::label($order->statuse_id, $order->statusename);
                $order->order_location = $location;
                if($order->statuse_copay==6) {
                    $order->statuse_copay=4;
                    $order->statuse_copay_name='Paid by cash';
                }
                $orders[$key]=$order;
            }
            $statuses = DB::table('statuses')->get();
            foreach ($statuses as $status) {
                $status->name = OrderStatus::label($status->id, $status->name);
            }
            $pharmacys = DB::table('pharmacys')->get();
            return response()->json([
                'orders' => $orders,
                'statuses' => $statuses,
                'pharmacys' => $pharmacys
            ], 200);
        } else {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        }
    }

    /**
     * @param int|string $order_id
     */
    public function ordersShow($order_id) {
        $order = DB::table('orders')->join('users', 'orders.user_id', '=', 'users.id')->join('statuses', 'orders.statuse_id', '=', 'statuses.id')->leftJoin('statuses_copay', 'orders.statuse_copay', '=', 'statuses_copay.id')->join('delivery_methods', 'orders.delivery_method_id', '=', 'delivery_methods.id')->join('delivery_times', 'orders.delivery_time_id', '=', 'delivery_times.id')->leftJoin('medicine', 'orders.id', '=', 'medicine.order_id')->join('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id')->select('orders.id', 'orders.created', 'orders.finish', 'orders.statuse_id', 'orders.signature', 'orders.fridge', 'orders.special_instructions', 'orders.user_id', 'orders.delivery_address','orders.delivery_location', 'orders.copay', 'orders.driver_id', 'orders.count_bags', 'orders.actual','orders.drop_off_photo', 'orders.signature_photo', 'users.name as username', 'users.last_name as last_name', 'delivery_methods.name as delivery_method', 'delivery_times.name as delivery_time', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end as useraddress'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end as userapartment'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end as userzip'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end as userlocation'), 'users.phone as userphone', DB::raw('sum(medicine.count) as count'), 'pharmacys.name as pharmacyname', 'pharmacys.address as pharmacyaddress','pharmacys.phone as pharmacyphone', 'pharmacys.location as pharmacylocation', 'statuses.name as statusename','statuses.color as statusecolor','orders.statuse_copay', 'statuses_copay.name as statuse_copay_name')->where('orders.id',$order_id)->groupBy('orders.id', 'orders.statuse_id', 'orders.created', 'orders.finish', 'orders.driver_id', 'orders.count_bags', 'orders.signature', 'orders.fridge','orders.actual', 'orders.special_instructions', 'orders.copay', 'orders.drop_off_photo','orders.signature_photo', 'orders.user_id', 'users.name', 'users.last_name', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end'), 'users.phone','pharmacys.name', 'delivery_methods.name', 'delivery_times.name', 'pharmacys.location', 'pharmacys.address','pharmacys.phone', 'statuses.name','statuses.color','orders.statuse_copay', 'statuses_copay.name','orders.delivery_address','orders.delivery_location')->first();
        if(!empty($order)) {
            if((Auth::user()->role == 'driver' && Auth::user()->id==$order->driver_id) || (Auth::user()->role == 'user' && Auth::user()->id==$order->user_id)) {
                if($order->driver_id>0) {
                    $driver = DB::table('users')->where('id',$order->driver_id)->first();
                    $locations = DB::table('locations')->whereIn('id', [DB::raw("select max(`id`) from locations GROUP BY user_id")])->where('user_id',$order->driver_id)->first();
                } else {
                    $driver="";
                    $locations="";
                }
                $location=$order->pharmacylocation;
                if($order->statuse_id==4) {
                    if(empty($order->delivery_location)) {
                        $location=$order->userlocation;
                    } else {
                        $location=$order->delivery_location;
                    }
                }
                if($order->statuse_id==3 && $order->driver_id>0) {
                    if(!empty($locations)) {
                        $location=$locations->location;
                    } else {
                        $location="";
                    }
                }
                if($order->statuse_copay==6) {
                    $order->statuse_copay=4;
                    $order->statuse_copay_name='Paid by cash';
                }
                $order->statusename = OrderStatus::label($order->statuse_id, $order->statusename);
                $order->order_location = $location;
                $rxs = DB::table('rxs')->where('order_id',$order_id)->get();
                return response()->json([
                    'order' => $order,
                    'rxs' => $rxs,
                    'driver' => $driver,
                    "order_location" => $location
                ], 200);
            } else {
                return response()->json([
                    'message' => 'You cannot open this page',
                    'errors' => 'Forbidden'
                ], 403);
            }
        } else {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        }
    }

    /**
     * @param int|string $order_id
     */
    public function ordersShowHandler($order_id) {
        $order = DB::table('orders')->where('orders.id',$order_id)->first();
        if(!empty($order)) {
            if((Auth::user()->role == 'driver' && Auth::user()->id==$order->driver_id) || (Auth::user()->role == 'user' && Auth::user()->id==$order->user_id)) {
                
            } else {
                return response()->json([
                    'message' => 'You cannot open this page',
                    'errors' => 'Forbidden'
                ], 403);
            }
        } else {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        }
    }

    public function update_status(Request $request) {
        if(Auth::user()->hasAnyRole('admin', 'driver')) {
            if(!empty($request->input('order_id')) && !empty($request->input('status_id'))) {
                $validated = $request->validate([
                    'order_id' => ['required', 'regex:/^\d+(?:_\d+)?$/'],
                    'status_id' => ['required', 'integer'],
                    'note' => ['nullable', 'string', 'max:1000'],
                ]);
                $order_id = explode('_', (string) $validated['order_id'])[0];
                $status_id = (int) $validated['status_id'];
                if(in_array($status_id, OrderStatus::NEEDS_DISPATCH_APPROVAL, true)) {
                    if(Auth::user()->role == 'driver') {
                        // Unavailable / refused stops stay open until dispatch verifies them with the pharmacy.
                        $exception = app(RequestDispatchApproval::class)->handle((int) $order_id, $status_id, Auth::user(), $request->input('note'));
                        return response()->json([
                            'message' => 'OK',
                            'pending_approval' => true,
                            'exception_id' => $exception['exception_id'],
                        ], 200);
                    }
                    return response()->json([
                        'message' => 'Use dispatch review after obtaining pharmacy approval in live chat.',
                        'errors' => 'Forbidden',
                    ], 403);
                } else {
                    Order::query()->where('id', $order_id)->update(['statuse_id'=>$status_id]);
                }
                return response()->json([
                    'message' => 'OK'
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Fields is not be empty.',
                    'errors' => 'Forbidden'
                ], 403);
            }
        } else {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        }
    }
}
