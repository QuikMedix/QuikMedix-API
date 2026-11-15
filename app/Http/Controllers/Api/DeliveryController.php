<?php

namespace App\Http\Controllers\Api;

use App\Actions\CalculateOrderTariff;
use App\Actions\NotifyNextPatient;
use App\Http\Controllers\Controller;
use App\Models\DriverPayout;
use App\Models\Order;
use App\Models\PackageTransition;
use App\Models\RouteStopLog;
use App\Support\Notifications;
use App\Support\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * What a driver does at the door: scanning packages, dropping off and collecting a signature.
 */
class DeliveryController extends Controller
{
    public function qr_scanedHandler(Request $request) {
        if(Auth::user()->hasAnyRole('admin', 'driver')) {
            $code = explode("_",$request->input('order_id'));
            $order_id = $code[0];
            if(empty($order_id)) {
                return response()->json([
                    'message' => 'Something is wrong with this field!',
                    'errors' => 'Bad Request'
                ], 400);
            }
            $order=Order::query()->where('id',$order_id)->where('driver_id',Auth::user()->id)->first();
            if(!empty($order)) {
                if (in_array((int) $order->statuse_id, [OrderStatus::READY_FOR_PICKUP, 2, 6, OrderStatus::HUB], true)) {
                    Order::query()->where('id',$order_id)->update(['statuse_id'=>OrderStatus::ON_THE_WAY]);
                    Notifications::send_push($order->user_id,"QuikMedix","Your order #$order_id is on its way!");
                }
                return response()->json([
                    'message' => 'Order was changed'
                ], 200);
            } else {
                return response()->json([
                    'message' => 'You cannot changed this order',
                    'errors' => 'Forbidden'
                ], 400);
            }
        } else {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        }
    }

    public function drop_offHandler(Request $request) {
        if(Auth::user()->hasAnyRole('admin', 'driver')) {
            $validator = Validator::make($request->all(), [
                'image' => 'mimes:jpeg,jpg,png|max:10048',       
                'order_id' => 'max:255',    
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Something is wrong with this field!',
                    'errors' => 'Bad Request'
                ], 400);
            }
            $code = explode("_",$request->input('order_id'));
            $order_id = $code[0];
            if(empty($order_id)) {
                return response()->json([
                    'message' => 'Something is wrong with this field!',
                    'errors' => 'Bad Request'
                ], 400);
            }
            $order=DB::table('orders')->where('id',$order_id)->where('driver_id',Auth::user()->id)->first();
            if(!empty($order)) {
                if(!$request->hasFile('image')) {
                    return response()->json([
                        'message' => 'Image is required',
                        'errors' => 'Bad Request'
                    ], 400);
                }
                $file = $request->file('image');
                $file->move(public_path() . '/images/drop_off/',\App\Support\PublicUpload::name($request->file('image')));
                $src = '/images/drop_off/'.\App\Support\PublicUpload::name($request->file('image'));
                if(empty($order->signature_photo)) {
                    Order::query()->where('id',$order_id)->update(['drop_off_photo'=>$src]);
                } else {
                    $patient=DB::table('users')->where('users.id',$order->user_id)->first();
                    $tariff_res = app(CalculateOrderTariff::class)->handle($order);
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
                    $driver= DB::table('locations')->whereIn('id', [DB::raw("select max(`id`) from locations GROUP BY user_id")])->where('user_id',Auth::user()->id)->first();
                    if(!empty($driver)){
                        $driver_location = $driver->location;
                    } else {
                        $driver_location=$user_location;
                    }
                    Order::query()->where('id',$order_id)->update(['statuse_id'=>4,'finish'=>date('Y-m-d H:i:s'),'delivery_address'=>$user_address,'delivery_location'=>$driver_location,'drop_off_photo'=>$src,'tariff'=>$tariff_res]);
                    $route = DB::table('routes_priority')->where('order_id',$order_id)->where('driver_id',Auth::user()->id)->where('type','patient');
                    foreach($route->get() as $routes_priority) {
                        RouteStopLog::query()->insert(["id"=>$routes_priority->id,"driver_id"=>$routes_priority->driver_id,"order_id"=>$routes_priority->order_id,"type"=>$routes_priority->type,"type_id"=>$routes_priority->type_id,"type_pay"=>$routes_priority->type_pay,"pay_value"=>$routes_priority->pay_value]);
                        if($routes_priority->type_pay==1) {
                            DriverPayout::query()->insert(['driver_id'=>Auth::user()->id,'order_id'=>$order_id,'amount'=>$routes_priority->pay_value]);
                        }
                        if($routes_priority->type_pay==2) {
                            DriverPayout::query()->insert(['driver_id'=>Auth::user()->id,'order_id'=>$order_id,'amount'=>$routes_priority->pay_value]);
                        }
                        if($routes_priority->type_pay==3) {
                            DriverPayout::query()->insert(['driver_id'=>Auth::user()->id,'order_id'=>$order_id,'amount'=>$routes_priority->pay_value*intval($order->count_bags)]);
                        }
                    }
                    $route->delete();
                    Notifications::send_push($order->user_id,"QuikMedix","Your order #$order_id has been delivered. \nIf you have any questions please contact ".\App\Support\Branding::supportContact()." \nBest regards, QuikMedix");
                    $next_route = DB::table('routes_priority')->where('driver_id',Auth::user()->id)->orderBy("priority","asc")->first();
                    if(!empty($next_route)) {
                        if($next_route->type=='patient') {
                            app(NotifyNextPatient::class)->handle(Auth::user()->id, $next_route);
                        }
                    }
                }
                return response()->json([
                    'message' => 'Order was drop off'
                ], 200);
            } else {
                return response()->json([
                    'message' => 'You cannot drop off this order',
                    'errors' => 'Forbidden'
                ], 400);
            }
        } else {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        }
    }

    public function signatureHandler(Request $request) {
        if(Auth::user()->hasAnyRole('admin', 'driver')) {
            $validator = Validator::make($request->all(), [
                'image' => 'mimes:jpeg,jpg,png|max:2048',  
                'signature_type' => 'max:255',         
                'order_id' => 'max:255',    
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Something is wrong with this field!',
                    'errors' => 'Bad Request'
                ], 400);
            }
            $code = explode("_",$request->input('order_id'));
            $order_id = $code[0];
            if(empty($order_id)) {
                return response()->json([
                    'message' => 'Something is wrong with this field!',
                    'errors' => 'Bad Request'
                ], 400);
            }
            $order=DB::table('orders')->where('id',$order_id)->where('driver_id',Auth::user()->id)->first();
            if(!empty($order)) {
                if(!$request->hasFile('image')) {
                    return response()->json([
                        'message' => 'Image is required',
                        'errors' => 'Bad Request'
                    ], 400);
                }
                $file = $request->file('image');
                $file->move(public_path() . '/images/signature/',\App\Support\PublicUpload::name($request->file('image')));
                $src = '/images/signature/'.\App\Support\PublicUpload::name($request->file('image'));
                if($order->signature>0) {
                    if(empty($order->drop_off_photo)) {
                        Order::query()->where('id',$order_id)->update(['signature_photo'=>$src,'signature_type'=>$request->input('signature_type')]);
                    } else {
                        $patient=DB::table('users')->where('users.id',$order->user_id)->first();
                        $tariff_res = app(CalculateOrderTariff::class)->handle($order);
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
                        $driver= DB::table('locations')->whereIn('id', [DB::raw("select max(`id`) from locations GROUP BY user_id")])->where('user_id',Auth::user()->id)->first();
                        if(!empty($driver)){
                            $driver_location = $driver->location;
                        } else {
                            $driver_location=$user_location;
                        }
                        Order::query()->where('id',$order_id)->update(['statuse_id'=>4,'finish'=>date('Y-m-d H:i:s'),'delivery_address'=>$user_address,'delivery_location'=>$driver_location,'signature_photo'=>$src,'signature_type'=>$request->input('signature_type'),'tariff'=>$tariff_res]);
                        $route = DB::table('routes_priority')->where('order_id',$order_id)->where('driver_id',Auth::user()->id)->where('type','patient');
                        foreach($route->get() as $routes_priority) {
                            RouteStopLog::query()->insert(["id"=>$routes_priority->id,"driver_id"=>$routes_priority->driver_id,"order_id"=>$routes_priority->order_id,"type"=>$routes_priority->type,"type_id"=>$routes_priority->type_id,"type_pay"=>$routes_priority->type_pay,"pay_value"=>$routes_priority->pay_value]);
                            if($routes_priority->type_pay==1) {
                                DriverPayout::query()->insert(['driver_id'=>Auth::user()->id,'order_id'=>$order_id,'amount'=>$routes_priority->pay_value]);
                            }
                            if($routes_priority->type_pay==2) {
                                DriverPayout::query()->insert(['driver_id'=>Auth::user()->id,'order_id'=>$order_id,'amount'=>$routes_priority->pay_value]);
                            }
                            if($routes_priority->type_pay==3) {
                                DriverPayout::query()->insert(['driver_id'=>Auth::user()->id,'order_id'=>$order_id,'amount'=>$routes_priority->pay_value*intval($order->count_bags)]);
                            }
                        }
                        $route->delete();
                        Notifications::send_push($order->user_id,"QuikMedix","Your order #$order_id has been delivered. \nIf you have any questions please contact ".\App\Support\Branding::supportContact()." \nBest regards, QuikMedix");
                        $next_route = DB::table('routes_priority')->where('driver_id',Auth::user()->id)->orderBy("priority","asc")->first();
                        if(!empty($next_route)) {
                            if($next_route->type=='patient') {
                                app(NotifyNextPatient::class)->handle(Auth::user()->id, $next_route);
                            }
                        }
                    }
                } else {
                    Order::query()->where('id',$order_id)->update(['signature_photo'=>$src,'signature_type'=>$request->input('signature_type')]);
                }
                return response()->json([
                    'message' => 'Signature was saved'
                ], 200);
            } else {
                return response()->json([
                    'message' => 'You cannot save signature on this order',
                    'errors' => 'Forbidden'
                ], 400);
            }
        } else if(Auth::user()->hasRole('user')) {
            $validator = Validator::make($request->all(), [
                'image' => 'mimes:jpeg,jpg,png|max:2048',    
                'order_id' => 'max:255',    
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Something is wrong with this field!',
                    'errors' => 'Bad Request'
                ], 400);
            }
            $order_id=$request->input('order_id');
            $order=DB::table('orders')->where('id',$order_id)->first();
            if(!empty($order)) {
                if(!$request->hasFile('image')) {
                    return response()->json([
                        'message' => 'Image is required',
                        'errors' => 'Bad Request'
                    ], 400);
                }
                $file = $request->file('image');
                $file->move(public_path() . '/images/signature/',\App\Support\PublicUpload::name($request->file('image')));
                $src = '/images/signature/'.\App\Support\PublicUpload::name($request->file('image'));
                Order::query()->where('id',$order_id)->update(['signature_photo'=>$src,'signature_type'=>'Patient']);
                return response()->json([
                    'message' => 'Signature was saved'
                ], 200);
            } else {
                return response()->json([
                    'message' => 'You cannot save signature on this order',
                    'errors' => 'Forbidden'
                ], 400);
            }
        } else {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        }
    }

    public function orderPatientQr(Request $request) {
        if(Auth::user()->hasAnyRole('admin', 'driver')) {
            if(!empty($request->input('order_id'))) {
                $code = explode("_",$request->input('order_id'));
                $order_id = $code[0];
                if(isset($code[1])) {
                    $bag = $code[1];
                } else {
                    $bag = 1;
                }
                return $this->recordPatientScan($order_id, $bag);
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

    public function orderPatientQr2(Request $request) {
        if(Auth::user()->hasAnyRole('admin', 'driver')) {
            if(!empty($request->input('order_id')) && !empty($request->input('code'))) {
                $code = explode("_",$request->input('code'));
                $order_id_code = $code[0];
                $order_id = $request->input('order_id');
                if(isset($code[1])) {
                    $bag = $code[1];
                } else {
                    $bag = 1;
                }
                return $this->recordPatientScan($order_id, $bag, $order_id==$order_id_code);
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

    /**
     * Records that the driver scanned one bag of an order at the patient's door and answers how many
     * bags are scanned so far.
     *
     * @param  int|string  $order_id
     * @param  int|string  $bag
     * @param  bool  $codeMatchesOrder  false when the scanned code belongs to a different order than the one on screen
     */
    private function recordPatientScan($order_id, $bag, bool $codeMatchesOrder = true)
    {
        $order = Order::query()->where('id',$order_id)->where('driver_id', Auth::user()->id)->first();
        if(isset($order) && $codeMatchesOrder) {
            $count_added = PackageTransition::query()->where('order_id',$order->id)->where('user_id',$order->user_id)->where('driver_id',Auth::user()->id)->count();
            if(1+$count_added>$order->count_bags) {
                return response()->json([
                    'order_id' => $order->id,
                    'count_bags' => $order->count_bags,
                    'count_scanned' => $count_added
                ], 200);
            } else if(1+$count_added<=$order->count_bags) {
                if(PackageTransition::query()->where('order_id',$order->id)->where('user_id',$order->user_id)->where('driver_id',Auth::user()->id)->where('bag',$bag)->count()>0) {
                    return response()->json([
                        'message' => 'This bag has already been scanned, please scan the next bag for this order.',
                        'errors' => 'Not Found'
                    ], 404);
                } else {
                    PackageTransition::query()->insert(['order_id'=>$order->id,'user_id'=>$order->user_id,'driver_id'=>Auth::user()->id,'bag'=>$bag]);
                    return response()->json([
                        'order_id' => $order->id,
                        'count_bags' => $order->count_bags,
                        'count_scanned' => $count_added+1
                    ], 200);
                }
            } else {
                return response()->json([
                    'message' => 'The indicated number of bags does not match the number of bags in the order, check the quantity or change it in the order settings.',
                    'errors' => 'Not Found'
                ], 404);
            }
        } else {
            return response()->json([
                'message' => 'Order with such a QR code was not found or order was alredy picked up.',
                'errors' => 'Not Found'
            ], 404);
        }
    }
}
