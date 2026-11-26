<?php

namespace App\Http\Controllers\Api;

use App\Models\RouteStop;
use App\Models\Location;
use App\Models\Pharmacy;
use App\Models\Office;
use App\Http\Controllers\Controller;
use App\Models\PackageTransition;
use App\Support\Notifications;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * A driver's route in the mobile app: the stops, their history and starting the route.
 */
class RouteController extends Controller
{
    public function routes() {
        if(Auth::user()->hasAnyRole('admin', 'driver')) {
            $routes_priority1 = DB::table('routes_priority')->select(DB::raw("min(id) as id"), "driver_id", DB::raw("GROUP_CONCAT(order_id SEPARATOR ',') as order_id"), "type", "type_id", DB::raw("min(priority) as priority"))->where('driver_id', Auth::user()->id)->where('type', '!=', 'office')->groupBy("type","type_id","driver_id")->orderBy("priority","asc")->get();
            $routes_priority0 = DB::table('routes_priority')->select(DB::raw("min(id) as id"), "driver_id", DB::raw("GROUP_CONCAT(order_id SEPARATOR ',') as order_id"), "type", "type_id", "priority")->where('driver_id', Auth::user()->id)->where('type', 'office')->groupBy("type","type_id","driver_id", "priority")->orderBy("priority","asc")->get();
            $routes = array();
            foreach ($routes_priority1 as $key => $value) {
                array_push($routes,$value);
            }
            foreach ($routes_priority0 as $key => $value) {
                array_push($routes,$value);
            }
            usort($routes, function($a, $b){
                return ($a->priority - $b->priority);
            });
            $driver= DB::table('locations')->whereIn('id', [DB::raw("select max(`id`) from locations GROUP BY user_id")])->where('user_id',Auth::user()->id)->first();
            if(empty($driver) || empty($driver->location)) {
                return response()->json([
                    'message' => 'Driver location is not available',
                    'errors' => 'Missing driver location'
                ], 409);
            }
            foreach ($routes as $key => $value) {
                if($value->type=='pharmacy') {
                    $pharmacy = DB::table('pharmacys')->where('id',$value->type_id)->first();
                    if(empty($pharmacy)) {
                        continue;
                    }
                    $routes[$key]->name_point=$pharmacy->name;
                    $routes[$key]->phone_point=$pharmacy->phone;
                    $routes[$key]->address_point=$pharmacy->address;
                    $routes[$key]->google_maps_point="https://www.google.com/maps/dir/".str_replace(' ','',$driver->location)."/".str_replace(' ','',$pharmacy->location)."/";
                    $order=DB::table('orders')->whereIn('id', explode(',', (string) $value->order_id))->first();
                    if(empty($order)) {
                        continue;
                    }
                    if($order->statuse_copay==6) {
                        $order->statuse_copay=4;
                    }
                    $routes[$key]->order=$order;
                    $routes[$key]->order->delivery_time=DB::table('delivery_times')->where('id',$order->delivery_time_id)->value('name') ?? '';
                    $routes[$key]->order->delivery_method=DB::table('delivery_methods')->where('id',$order->delivery_method_id)->value('name') ?? '';
                }
                if($value->type=='patient') {
                    $patient0 = DB::table('users')->where('id',$value->type_id)->first(['address', 'address2', 'address3', 'apartment', 'apartment2', 'apartment3', 'last_name', 'location', 'location2', 'location3', 'name', 'phone', 'primary_address', 'zip', 'zip2', 'zip3']);
                    if(empty($patient0)) {
                        continue;
                    }
                    $routes[$key]->name_point=$patient0->name.' '.$patient0->last_name;
                    $routes[$key]->phone_point=$patient0->phone;
                    $routes[$key]->address_point=$patient0->address;
                    $routes[$key]->apartment_point=$patient0->apartment;
                    $routes[$key]->zip_point=$patient0->zip;
                    if($patient0->primary_address==3){
                        $routes[$key]->address_point=$patient0->address3;
                        $routes[$key]->location_point=$patient0->location3;
                        $routes[$key]->apartment_point=$patient0->apartment3;
                        $routes[$key]->zip_point=$patient0->zip3;
                    } elseif($patient0->primary_address==2){
                        $routes[$key]->address_point=$patient0->address2;
                        $routes[$key]->location_point=$patient0->location2;
                        $routes[$key]->apartment_point=$patient0->apartment2;
                        $routes[$key]->zip_point=$patient0->zip2;
                    } else {
                        $routes[$key]->address_point=$patient0->address;
                        $routes[$key]->location_point=$patient0->location;
                        $routes[$key]->apartment_point=$patient0->apartment;
                        $routes[$key]->zip_point=$patient0->zip;
                    }
                    $routes[$key]->google_maps_point="https://www.google.com/maps/dir/".str_replace(' ','',$driver->location)."/".str_replace(' ','',$routes[$key]->location_point)."/";
                    $order=DB::table('orders')->whereIn('id', explode(',', (string) $value->order_id))->first();
                    if(empty($order)) {
                        continue;
                    }
                    if($order->statuse_copay==6) {
                        $order->statuse_copay=4;
                    }
                    $routes[$key]->order=$order;
                    $routes[$key]->order->delivery_time=DB::table('delivery_times')->where('id',$order->delivery_time_id)->value('name') ?? '';
                    $routes[$key]->order->delivery_method=DB::table('delivery_methods')->where('id',$order->delivery_method_id)->value('name') ?? '';
                    $routes[$key]->order->count_scanned=PackageTransition::query()->where('order_id',$value->order_id)->where('user_id',$value->type_id)->where('driver_id',Auth::user()->id)->count();
                    // What the patient asked of the driver, such as where to leave the order.
                    $routes[$key]->order->customer_note=\App\Support\DeliveryProgress::patientNote($order->id);
                }
                if($value->type=='office') {
                    $office = DB::table('offices')->where('id',$value->type_id)->first();
                    if(empty($office)) {
                        continue;
                    }
                    $routes[$key]->name_point=$office->name;
                    $routes[$key]->phone_point=$office->phone;
                    $routes[$key]->address_point=$office->address;
                    $routes[$key]->google_maps_point="https://www.google.com/maps/dir/".str_replace(' ','',$driver->location)."/".str_replace(' ','',$office->location)."/";
                }
            }
            return response()->json([
                'routes' => $routes,
                'status' => Auth::user()->route_status
            ], 200);
        } else {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        }
    }

    /**
     * @param int|string $route_id
     */
    public function routesShow($route_id) {
        if(Auth::user()->hasAnyRole('admin', 'user', 'driver')) {
            $route = RouteStop::query()->select(DB::raw("min(id) as id"), "driver_id", DB::raw("GROUP_CONCAT(order_id SEPARATOR ',') as order_id"), "type", "type_id", DB::raw("min(priority) as priority"))->where('driver_id', Auth::user()->id)->groupBy("type","type_id","driver_id")->havingRaw('min(id) = ?', [$route_id])->orderBy("priority","asc")->first();
            if(empty($route) || empty($route->id)) {
                return response()->json([
                    'message' => 'Route not found',
                    'errors' => 'Not Found'
                ], 404);
            }
            $driver= Location::query()->whereIn('id', [DB::raw("select max(`id`) from locations GROUP BY user_id")])->where('user_id',Auth::user()->id)->first();
            if($route->type=='pharmacy') {
                $pharmacy = Pharmacy::query()->where('id',$route->type_id)->first();
                $pharmacy->google_maps_route = "https://www.google.com/maps/dir/".str_replace(' ','',$driver->location)."/".str_replace(' ','',$pharmacy->location)."/";
                return response()->json([
                    'route' => $route,
                    'point' => $pharmacy
                ], 200);
            }
            if($route->type=='patient') {
                $patient0 = DB::table('users')->where('id',$route->type_id)->first(['address', 'address2', 'address3', 'apartment', 'apartment2', 'apartment3', 'city', 'email', 'id', 'image', 'last_name', 'location', 'location2', 'location3', 'name', 'primary_address', 'state', 'zip', 'zip2', 'zip3']);
                $patient=[];
                $patient['id']=$patient0->id;
                $patient['name']=$patient0->name.' '.$patient0->last_name;
                $patient['email']=$patient0->email;
                $patient['image']=$patient0->image;
                if($patient0->primary_address==3){
                    $patient['address']=$patient0->address3;
                    $patient['location']=$patient0->location3;
                    $patient['apartment']=$patient0->apartment3;
                    $patient['zip']=$patient0->zip3;
                } elseif($patient0->primary_address==2){
                    $patient['address']=$patient0->address2;
                    $patient['location']=$patient0->location2;
                    $patient['apartment']=$patient0->apartment2;
                    $patient['zip']=$patient0->zip2;
                } else {
                    $patient['address']=$patient0->address;
                    $patient['location']=$patient0->location;
                    $patient['apartment']=$patient0->apartment;
                    $patient['zip']=$patient0->zip;
                }
                $patient['city']=$patient0->city;
                $patient['state']=$patient0->state;
                $patient['google_maps_route'] = "https://www.google.com/maps/dir/".str_replace(' ','',$driver->location)."/".str_replace(' ','',$patient['location'])."/";
                return response()->json([
                    'route' => $route,
                    'point' => $patient
                ], 200);
            }
            if($route->type=='office') {
                $route = RouteStop::query()->select(DB::raw("min(id) as id"), "driver_id", DB::raw("GROUP_CONCAT(order_id SEPARATOR ',') as order_id"), "type", "type_id", "priority")->where('driver_id', Auth::user()->id)->groupBy("type","type_id","driver_id", "priority")->havingRaw('min(id) = ?', [$route_id])->orderBy("priority","asc")->get();
                $office = Office::query()->where('id',$route->type_id)->first();
                $office->google_maps_route = "https://www.google.com/maps/dir/".str_replace(' ','',$driver->location)."/".str_replace(' ','',$office->location)."/";
                return response()->json([
                    'route' => $route,
                    'point' => $office
                ], 200);
            }
        } else {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        }
    }

    public function routesLogs(Request $request) {
        if(Auth::user()->hasAnyRole('admin', 'driver')) {
            if(empty($request->input('from')) && empty($request->input('to'))) {
                $routes = DB::table('routes_priority_logs')->select(DB::raw("min(id) as id"), "driver_id", DB::raw("GROUP_CONCAT(order_id SEPARATOR ',') as order_id"), "type", "type_id")->where('driver_id', Auth::user()->id)->groupBy("type","type_id","driver_id")->orderBy("created","desc")->limit(10)->get();
            } else {
                $from = date("Y-m-d H:i:s",strtotime($request->input('from')));
                $to = date("Y-m-d H:i:s",strtotime($request->input('to')));
                $routes = DB::table('routes_priority_logs')->select(DB::raw("min(id) as id"), "driver_id", DB::raw("GROUP_CONCAT(order_id SEPARATOR ',') as order_id"), "type", "type_id")->where('driver_id', Auth::user()->id)->whereBetween('created', [$from, $to])->groupBy("type","type_id","driver_id")->orderBy("created","desc")->get();
            }
            foreach ($routes as $key => $value) {
                if($value->type=='pharmacy') {
                    $pharmacy = DB::table('pharmacys')->where('id',$value->type_id)->first();
                    if(empty($pharmacy)) {
                        continue;
                    }
                    $routes[$key]->name_point=$pharmacy->name;
                    $routes[$key]->phone_point=$pharmacy->phone;
                    $routes[$key]->address_point=$pharmacy->address;
                    $order=DB::table('orders')->whereIn('id', explode(',', (string) $value->order_id))->first();
                    if(empty($order)) {
                        continue;
                    }
                    $routes[$key]->order=$order;
                    $routes[$key]->order->delivery_time=DB::table('delivery_times')->where('id',$order->delivery_time_id)->value('name') ?? '';
                    $routes[$key]->order->delivery_method=DB::table('delivery_methods')->where('id',$order->delivery_method_id)->value('name') ?? '';
                }
                if($value->type=='patient') {
                    $patient0 = DB::table('users')->where('id',$value->type_id)->first(['address', 'address2', 'address3', 'last_name', 'name', 'phone', 'primary_address']);
                    if(empty($patient0)) {
                        continue;
                    }
                    $routes[$key]->name_point=$patient0->name.' '.$patient0->last_name;
                    $routes[$key]->phone_point=$patient0->phone;
                    if($patient0->primary_address==3){
                        $routes[$key]->address_point=$patient0->address3;
                    } elseif($patient0->primary_address==2){
                        $routes[$key]->address_point=$patient0->address2;
                    } else {
                        $routes[$key]->address_point=$patient0->address;
                    }
                    $order=DB::table('orders')->whereIn('id', explode(',', (string) $value->order_id))->first();
                    if(empty($order)) {
                        continue;
                    }
                    $routes[$key]->order=$order;
                    $routes[$key]->order->delivery_time=DB::table('delivery_times')->where('id',$order->delivery_time_id)->value('name') ?? '';
                    $routes[$key]->order->delivery_method=DB::table('delivery_methods')->where('id',$order->delivery_method_id)->value('name') ?? '';
                }
                if($value->type=='office') {
                    $office = DB::table('offices')->where('id',$value->type_id)->first();
                    if(empty($office)) {
                        continue;
                    }
                    $routes[$key]->name_point=$office->name;
                    $routes[$key]->phone_point=$office->phone;
                    $routes[$key]->address_point=$office->address;
                }
            }
            return response()->json([
                'routes_logs' => $routes
            ], 200);
        } else {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        }
    }

    public function startRoute() {
        if(Auth::user()->hasAnyRole('admin', 'driver')) {
            DB::table('users')->where('id', Auth::user()->id)->update(['route_status'=>'started']);
            $routes = RouteStop::query()->where('driver_id', Auth::user()->id)->orderBy("priority","asc")->get();
            foreach($routes as $value) {
                if($value->type=='patient') {
                    //Notifications::send_push(DB::table('orders')->where('id', $value->order_id)->first()->user_id,"QuikMedix","Your orders are out for delivery today");
                }
            }
            return response()->json([
                'message' => 'OK'
            ], 200);
        }
    }
}
