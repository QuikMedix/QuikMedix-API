<?php

namespace App\Http\Controllers;

use App\Models\CashLog;
use App\Models\PackageTransition;
use App\Models\Order;
use App\Models\RouteStopLog;
use App\Models\DriverPayout;
use App\Actions\LogUserChanges;
use App\Actions\ScanPackage;
use App\Http\Controllers\Concerns\RendersPages;
use App\Support\Notifications;
use App\Support\PageLinks;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Actions\NotifyNextPatient;

/**
 * Drivers: a driver's payouts, packages and profile, and the QR scans that hand orders and bags to
 * a driver. Formerly actions of LexaAdmin, moved as they were.
 */
class DriverController extends Controller
{
    use RendersPages;


    /**
     * @param int|string $driver_id
     */
    public function driversPayouts($driver_id) {
        $user = User::find($driver_id);
        $orders = DB::table('cash_log')->where('driver_id',$driver_id)->orderBy('id','desc');
        $max_page = DB::table('cash_log')->where('driver_id',$driver_id);
        if(!empty(request()->query('created'))) {
            $orders = $orders->whereDate('created','=',request()->query('created'));
            $max_page = $max_page->whereDate('created','=',request()->query('created'));
        }
        $countOnPage=30;
        $max_pages=ceil($max_page->count()/$countOnPage);
        $page = $this->currentPage(request());
        $pages = PageLinks::around($page, $max_pages);
        $orders = $orders->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
        $duty = CashLog::query()->where('driver_id',$driver_id)->where('return','0')->sum('copay');
        $res_view = view('drivers.payouts',['user'=>$user,'orders'=>$orders,'pages'=>$pages,'duty'=>$duty,'title'=>'Drivers Payouts','br1'=>'Drivers','br2'=>'Payouts']);
        return $this->page(request(), $res_view);
    }

    public function driversQr(Request $request) {
        if(!empty($request->input('code'))) {
            $token = DB::table('tokens_driver')->where('token',$request->input('code'))->where('created', '>', DB::raw("NOW() - INTERVAL 15 MINUTE"))->first();
            if(isset($token)) {
                return json_encode([
                    'user_id' => $token->user_id,
                ]);
            } else {
                return json_encode([
                    'message' => 'Driver with such a token was not found, or the token has expired',
                    'errors' => 'Not Found'
                ]);
            }
        } else {
            return json_encode([
                'message' => 'Driver with such a token was not found, or the token has expired',
                'errors' => 'Not Found'
            ]);
        }
    }

    public function driversQrOrder(Request $request, ScanPackage $scanPackage) {
        if (empty($request->input('code')) && empty($request->input('count')) && empty($request->input('driver_id'))) {
            return json_encode([
                'message' => 'QR code or Count Bags Order are not filled.',
                'errors' => 'Not Found'
            ]);
        }
        // A code is "order_bag"; without a bag number it is the first bag.
        $code = explode('_', (string) $request->input('code'));

        return $scanPackage->handle($code[0], $code[1] ?? 1, $request->input('driver_id'));
    }

    /**
     * @param int|string $user_id
     */
    public function driversPackages($user_id) {
        $driver = DB::table('users')->where('id', $user_id)->first();
        if((Auth::user()->can('admin'))) {
            $routes_priority0 = DB::table('routes_priority')->select("driver_id", DB::raw("GROUP_CONCAT(order_id SEPARATOR ',') as order_id"), "type", "type_id", "priority")->where('driver_id', $user_id)->where('type', 'office')->where('type_id', Auth::user()->office_id)->groupBy("type","type_id","driver_id", "priority")->orderBy("priority","asc")->first();
            if(empty($routes_priority0)) {
                $routes_priority0 = new \stdClass();
                $routes_priority0->order_id = '';
            }
            $orders_transfer = DB::table('orders')->where('driver_id', $user_id)->whereIn('statuse_id', [7])->whereIn('id', explode(',',$routes_priority0->order_id))->get();
            foreach($orders_transfer as $key=>$value) {
                $orders_transfer[$key]->count_bags_transfer = PackageTransition::query()->whereRaw('Date(created) = CURDATE()')->where('order_id',$value->id)->where('office_id',Auth::user()->office_id)->where('driver_id',$value->driver_id)->where('target','out')->count();
            }
            $orders_pick_up = DB::table('orders')->where('driver_id', $user_id)->whereIn('statuse_id', [2,3,5,6,8,9])->whereIn('id', explode(',',$routes_priority0->order_id))->get();
            foreach($orders_pick_up as $key=>$value) {
                $orders_pick_up[$key]->count_bags_transfer = PackageTransition::query()->whereRaw('Date(created) = CURDATE()')->where('order_id',$value->id)->where('office_id',Auth::user()->office_id)->where('driver_id',$value->driver_id)->where('target','in')->count();
            }
            $need_cash = CashLog::query()->where('driver_id', $user_id)->where("return","0")->sum('copay');
        } else {
            $routes_priority0 = DB::table('routes_priority')->select("driver_id", DB::raw("GROUP_CONCAT(order_id SEPARATOR ',') as order_id"), "type", "type_id", "priority")->where('driver_id', $user_id)->where('type', 'pharmacy')->where('type_id', Auth::user()->pharmacy_id)->groupBy("type","type_id","driver_id", "priority")->orderBy("priority","asc")->first();
            if(empty($routes_priority0)) {
                $routes_priority0 = new \stdClass();
                $routes_priority0->order_id = '';
            }
            $orders_transfer = DB::table('orders')->where('driver_id', $user_id)->where('pharmacy_id', Auth::user()->pharmacy_id)->whereIn('statuse_id', [1,2])->whereIn('id', explode(',',$routes_priority0->order_id))->get();
            foreach($orders_transfer as $key=>$value) {
                $orders_transfer[$key]->count_bags_transfer = PackageTransition::query()->whereRaw('Date(created) = CURDATE()')->where('order_id',$value->id)->where('pharmacy_id',Auth::user()->pharmacy_id)->where('driver_id',$value->driver_id)->where('target','out')->count();
            }
            $orders_pick_up = DB::table('orders')->where('driver_id', $user_id)->whereIn('statuse_id', [2,3,5,6,8,9])->whereIn('id', explode(',',$routes_priority0->order_id))->get();
            foreach($orders_pick_up as $key=>$value) {
                $orders_pick_up[$key]->count_bags_transfer = PackageTransition::query()->whereRaw('Date(created) = CURDATE()')->where('order_id',$value->id)->where('pharmacy_id',Auth::user()->pharmacy_id)->where('driver_id',$value->driver_id)->where('target','in')->count();
            }
            $need_cash = CashLog::query()->where('driver_id', $user_id)->where("return","0")->sum('copay');
        }
        $res_view = view('drivers.packages',['driver'=>$driver,'need_cash'=>$need_cash,'orders_transfer'=>$orders_transfer,'orders_pick_up'=>$orders_pick_up,'title'=>'Drivers Packages','br1'=>'Drivers','br2'=>'Drivers Packages','alert'=>'']);
        return $this->page(request(), $res_view);
    }

    /**
     * @param int|string $user_id
     */
    public function driversPackagesHandler(Request $request, $user_id) {
        if($request->input('confirm_receipt')>0) {
            $driver_id = $user_id;
            $orders = DB::table('cash_log')->where('driver_id', $driver_id)->where("return","0")->get();
            foreach($orders as $order0) {
                $order =  DB::table('orders')->where('id',$order0->order_id)->first();
                if((Auth::user()->can('admin'))) {
                    $route = DB::table('routes_priority')->where('driver_id', $driver_id)->where('order_id',$order->id)->where('type','office');
                } else {
                    $route = DB::table('routes_priority')->where('driver_id', $driver_id)->where('order_id',$order->id)->where('type','pharmacy');
                }
                foreach($route->get() as $routes_priority) {
                    RouteStopLog::query()->insert(["id"=>$routes_priority->id,"driver_id"=>$routes_priority->driver_id,"order_id"=>$routes_priority->order_id,"type"=>$routes_priority->type,"type_id"=>$routes_priority->type_id,"type_pay"=>$routes_priority->type_pay,"pay_value"=>$routes_priority->pay_value]);
                    if($routes_priority->type_pay==2) {
                        DriverPayout::query()->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                    } else if($routes_priority->type_pay==3) {
                        DriverPayout::query()->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value*intval($order->count_bags)]);
                    } else {
                        DriverPayout::query()->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                    }
                }
                $route->delete();
                $next_route = DB::table('routes_priority')->where('driver_id',$driver_id)->orderBy("priority","asc")->first();
                if(!empty($next_route)) {
                    if($next_route->type=='patient') {
                        app(NotifyNextPatient::class)->handle($driver_id,$next_route);
                    }
                }
            }
            CashLog::query()->where('driver_id', $user_id)->where("return","0")->update(["return_at"=>DB::raw('NOW()'),"return"=>"1","admin_id"=>Auth::user()->id]);
        }
        if($request->input('massiveBagsTransfer')>0) {
            if((Auth::user()->can('admin'))) {
                $routes_priority0 = DB::table('routes_priority')->select("driver_id", DB::raw("GROUP_CONCAT(order_id SEPARATOR ',') as order_id"), "type", "type_id", "priority")->where('driver_id', $user_id)->where('type', 'office')->where('type_id', Auth::user()->office_id)->groupBy("type","type_id","driver_id", "priority")->orderBy("priority","asc")->first();
                if(empty($routes_priority0)) {
                    $routes_priority0 = new \stdClass();
                    $routes_priority0->order_id = '';
                }
                $orders_transfer = DB::table('orders')->where('driver_id', $user_id)->whereIn('statuse_id', [7])->whereIn('id', explode(',',$routes_priority0->order_id))->get();
            } else {
                $routes_priority0 = DB::table('routes_priority')->select("driver_id", DB::raw("GROUP_CONCAT(order_id SEPARATOR ',') as order_id"), "type", "type_id", "priority")->where('driver_id', $user_id)->where('type', 'pharmacy')->where('type_id', Auth::user()->pharmacy_id)->groupBy("type","type_id","driver_id", "priority")->orderBy("priority","asc")->first();
                if(empty($routes_priority0)) {
                    $routes_priority0 = new \stdClass();
                    $routes_priority0->order_id = '';
                }
                $orders_transfer = DB::table('orders')->where('driver_id', $user_id)->where('pharmacy_id', Auth::user()->pharmacy_id)->whereIn('statuse_id', [1,2])->whereIn('id', explode(',',$routes_priority0->order_id))->get();
            }
            foreach($orders_transfer as $order) {
                for ($i=1; $i <= $order->count_bags; $i++) {
                    app(ScanPackage::class)->handle($order->id, $i, $user_id);
                }
            }
        }
        return redirect("drivers/$user_id/packages");
    }

    /**
     * @param int|string $driver_id
     */
    public function driversProfile($driver_id) {
        $user = DB::table('users')->where('id', $driver_id)->where('role','driver')->first();
        if(!empty($user)){
            $pharmacys = DB::table('pharmacys')->get()->keyBy('id');
            $user_actions = DB::table('action_log')->where('user_id', $driver_id)->get();
            $orders_stat = DB::table('orders')->where('user_id',$driver_id)->where('statuse_id',4)->select(DB::raw('count(distinct id) as count'),DB::raw('sum(copay) as copay'))->first();
            $duty = CashLog::query()->where('driver_id',$driver_id)->where('return','0')->sum('copay');
            $last_cash = DB::table('cash_log')->where('driver_id',$driver_id)->where('return','1')->orderBy('return_at','desc')->first();
            $orders = DB::table('orders')->where('user_id',$driver_id)->join('users', 'orders.user_id', '=', 'users.id')->leftJoin('users as driver', 'orders.driver_id', '=', 'driver.id')->join('statuses', 'orders.statuse_id', '=', 'statuses.id')->leftJoin('statuses_copay', 'orders.statuse_copay', '=', 'statuses_copay.id')->join('delivery_methods', 'orders.delivery_method_id', '=', 'delivery_methods.id')->join('delivery_times', 'orders.delivery_time_id', '=', 'delivery_times.id')->join('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id')->select('orders.id', 'orders.created', 'orders.driver_id', 'orders.merchantOrder','orders.special_instructions', 'orders.rating','orders.fridge','orders.actual','orders.eta','orders.facility', 'driver.name as drivername', 'driver.last_name as driverlast_name', 'driver.pharmacy_id as driverpharmacy_id', 'orders.count_bags', 'orders.signature','orders.statuse_id', 'orders.pharmacy_id', 'orders.copay', 'users.name as username', 'delivery_methods.name as delivery_method', 'delivery_times.name as delivery_time', 'users.last_name as last_name', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end as useraddress'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end as userapartment'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end as userzip'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end as userlocation'), 'users.os as useros', 'users.phone as userphone', 'pharmacys.name as pharmacyname', 'pharmacys.address as pharmacyaddress','pharmacys.phone as pharmacyphone', 'orders.tariff', 'statuses.name as statusename','statuses.color as statusecolor','statuses_copay.name as statuse_copay_name','statuses_copay.color as statuse_copay_color')->groupBy('orders.id', 'orders.statuse_id', 'orders.merchantOrder', 'orders.actual','orders.eta', 'orders.driver_id', 'orders.rating','orders.signature','orders.fridge','driver.name', 'driver.last_name', 'driver.pharmacy_id','orders.special_instructions', 'orders.count_bags', 'orders.created', 'orders.facility','orders.copay', 'orders.pharmacy_id', 'orders.tariff', 'users.name', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end'), 'users.phone','pharmacys.name', 'pharmacys.address', 'delivery_methods.name', 'delivery_times.name', 'pharmacys.phone', 'statuses.name','statuses.color','users.last_name', 'statuses_copay.name','statuses_copay.color', 'users.os')->orderBy('orders.id','desc')->limit(8)->get();
            $res_view = view('drivers.profile',['user'=>$user,'pharmacys'=>$pharmacys,'duty'=>$duty,'last_cash'=>$last_cash,'user_actions'=>$user_actions,'orders_stat'=>$orders_stat,'orders'=>$orders,'alert'=>'','title'=>'Driver Profile','br1'=>'Drivers','br2'=>'Profile']);
            return $this->page(request(), $res_view);
        }
        return abort(404);
    }

    /**
     * @param int|string $driver_id
     */
    public function driversProfileHandler(Request $request,$driver_id) {
        $user = DB::table('users')->where('id', $driver_id)->where('role','driver')->first();
        if(!empty($user)){
            $user_id=$driver_id;
            if($request->input('save')>0) {
                if($request->hasFile('image')) {
                    $file = $request->file('image');
                    $file->move(public_path() . '/images/users/',\App\Support\PublicUpload::name($request->file('image')));
                    $src = '/images/users/'.\App\Support\PublicUpload::name($request->file('image'));
                } else {
                    $src = $user->image;
                }
                if($request->input('remove_photo')>0) {
                    $src = '/images/users/default-user-image.png';
                }
                if($request->hasFile('driving_license_img')) {
                    $file = $request->file('driving_license_img');
                    $file->move(public_path() . '/images/driving_license/',\App\Support\PublicUpload::name($request->file('driving_license_img')));
                    $driving_license_img = '/images/driving_license/'.\App\Support\PublicUpload::name($request->file('driving_license_img'));
                } else {
                    $driving_license_img = $user->driving_license_img;
                }
                if($request->hasFile('car_img')) {
                    $file = $request->file('car_img');
                    $file->move(public_path() . '/images/users/',\App\Support\PublicUpload::name($request->file('car_img')));
                    $car_img = '/images/users/'.\App\Support\PublicUpload::name($request->file('car_img'));
                } else {
                    $car_img = $user->car_img;
                }
                if(!empty($request->input('zip'))) {
                    $address = $request->input('address').' '.$request->input('zip');
                } else {
                    $address = $request->input('address');
                }
                $data = app(\App\Actions\GeocodeAddress::class)->response($address);
                $address = $data->results[0]->formatted_address;
                $location = $data->results[0]->geometry->location->lat.','.$data->results[0]->geometry->location->lng;
                app(LogUserChanges::class)->handle($request,$address,$user_id);
                DB::table('users')->where('id', $user_id)->update(['name' => $request->input('name'),'last_name' => $request->input('last_name'),'email' => $request->input('email'),'phone' => $request->input('phone'),'image' => $src,'address' => $address,'location' => $location,'zip' => $request->input('zip'),'apartment' => $request->input('apartment'),'driving_license' => $request->input('driving_license'),'driving_license_img' => $driving_license_img,'identification_cards' => $request->input('identification_cards'),'transport' => $request->input('transport'),'car_info' => $request->input('car_info'),'car_img' => $car_img]);
            }
            if($request->input('ajax_stat')>0) {
                $date=date("Y-m-d",strtotime($request->input('date_stat')));
                $routes_logs = DB::table('routes_priority_logs')->where('driver_id',$driver_id)->whereDate('created', $date)->orderBy('id','asc')->get();
                $id=1;
                $routes_logs_group = [];
                $order_ids=[];
                $copay=[];
                $orders=[];
                $copay_sum=0;
                foreach($routes_logs as $key=>$row){
                    if(!in_array($row->order_id,$order_ids)){
                        $order_ids[]=$row->order_id;
                        $order = DB::table('orders')->where('orders.id',$row->order_id)->join('users', 'orders.user_id', '=', 'users.id')->leftJoin('users as driver', 'orders.driver_id', '=', 'driver.id')->join('statuses', 'orders.statuse_id', '=', 'statuses.id')->leftJoin('statuses_copay', 'orders.statuse_copay', '=', 'statuses_copay.id')->join('delivery_methods', 'orders.delivery_method_id', '=', 'delivery_methods.id')->join('delivery_times', 'orders.delivery_time_id', '=', 'delivery_times.id')->join('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id')->select('orders.id', 'orders.statuse_copay', 'orders.created', 'orders.driver_id', 'orders.merchantOrder','orders.special_instructions', 'orders.rating','orders.fridge','orders.actual','orders.eta','orders.facility', 'driver.name as drivername', 'driver.last_name as driverlast_name', 'driver.pharmacy_id as driverpharmacy_id', 'orders.count_bags', 'orders.signature','orders.statuse_id', 'orders.pharmacy_id', 'orders.copay', 'users.name as username', 'delivery_methods.name as delivery_method', 'delivery_times.name as delivery_time', 'users.last_name as last_name', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end as useraddress'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end as userapartment'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end as userzip'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end as userlocation'), 'users.os as useros', 'users.phone as userphone', 'pharmacys.name as pharmacyname', 'pharmacys.address as pharmacyaddress','pharmacys.phone as pharmacyphone', 'orders.tariff', 'statuses.name as statusename','statuses.color as statusecolor','statuses_copay.name as statuse_copay_name','statuses_copay.color as statuse_copay_color')->groupBy('orders.id', 'orders.statuse_copay', 'orders.statuse_id', 'orders.merchantOrder', 'orders.actual','orders.eta', 'orders.driver_id', 'orders.rating','orders.signature','orders.fridge','driver.name', 'driver.last_name', 'driver.pharmacy_id','orders.special_instructions', 'orders.count_bags', 'orders.created', 'orders.facility','orders.copay', 'orders.pharmacy_id', 'orders.tariff', 'users.name', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end'), 'users.phone','pharmacys.name', 'pharmacys.address', 'delivery_methods.name', 'delivery_times.name', 'pharmacys.phone', 'statuses.name','statuses.color','users.last_name', 'statuses_copay.name','statuses_copay.color', 'users.os')->orderBy('orders.id','desc')->first();
                        if($order->statuse_copay=='4') {
                            $copay[]=$order->copay;
                            $copay_sum+=$order->copay;
                        }
                        $orders[]=$order;
                    }
                    if(isset($routes_logs[$key+1]) && $row->type.$row->type_id==$routes_logs[$key+1]->type.$routes_logs[$key+1]->type_id){} else {
                        $row->area_name=NULL;
                        if($row->type=='office'){
                            $loc = DB::table('offices')->where('id',$row->type_id)->first();
                            if(!empty($loc)){
                                $area = DB::table('area')->whereRaw('ST_CONTAINS(polygon, POINT(?, ?))', \App\Support\GeoPoint::bindings($loc->location))->select("area.name")->first();
                                if(!empty($area)){
                                    $row->area_name = $area->name;
                                }
                            }
                        }
                        if($row->type=='patient'){
                            $loc = DB::table('users')->where('id',$row->type_id)->first();
                            if(!empty($loc)){
                                $area = DB::table('area')->whereRaw('ST_CONTAINS(polygon, POINT(?, ?))', \App\Support\GeoPoint::bindings($loc->location))->select("area.name")->first();
                                if(!empty($area)){
                                    $row->area_name = $area->name;
                                }
                            }
                        }
                        if($row->type=='pharmacy'){
                            $loc = DB::table('pharmacys')->where('id',$row->type_id)->first();
                            if(!empty($loc)){
                                $area = DB::table('area')->whereRaw('ST_CONTAINS(polygon, POINT(?, ?))', \App\Support\GeoPoint::bindings($loc->location))->select("area.name")->first();
                                if(!empty($area)){
                                    $row->area_name = $area->name;
                                }
                            }
                        }
                        $row->priority=$id;
                        $row->order_id=$order_ids;
                        $row->orders=$orders;
                        $row->copay=$copay;
                        array_push($routes_logs_group,$row);
                        $order_ids=[];
                        $orders=[];
                        $copay=[];
                        $id++;
                    }
                }
                return view('drivers.ajax_stat',['routes_logs_groups'=>$routes_logs_group,'copay_sum'=>$copay_sum]);
            }
            return redirect("/drivers/$driver_id/profile");
        }
        return abort(404);
    }
}
