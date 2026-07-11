<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\Notifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Stripe\Stripe;
use Carbon\Carbon;
use Twilio\Rest\Client;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;
use Illuminate\Support\Facades\Redis;


class LexaAdminApi extends Controller
{
    public function __construct() {
        $this->middleware('auth:api');
    }

    public static function profile() {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        $user = DB::table('users')->where('id', Auth::user()->id)->first();
        unset($user->password);
        unset($user->remember_token);
        unset($user->device_token);
        return response()->json([
            'user' => $user
        ], 200);
    }

    public static function banner() {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        $banner = DB::table('banners')->first();
        if(!empty($banner)) {
            return response()->json([
                'type_id' => $banner->type_id,
                'image' => $banner->image,
                'href' => $banner->href,
                'title' => $banner->title,
                'body' => $banner->body
            ], 200);
        }
        return response()->json([
            
        ], 200);
    }

    public static function profileHandler(Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        $validator = Validator::make($request->all(), [
            'image' => 'mimes:jpeg,jpg,png|max:5048',
            'password' => 'min:8',
            'password2' => 'min:8',
            'name' => 'max:255',
            'last_name' => 'max:255',
            'email' => 'max:155',
            'phone' => 'max:155',
            'address' => 'max:255',
            'city' => 'max:155',
            'state' => 'max:155',
            'zip' => 'max:155',
            'apartment' => 'max:155',
            'driving_license' => 'max:155',
            'identification_cards' => 'max:155',
            'car_info' => 'max:255',
            'payment_card' => 'max:155',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Something is wrong with this field!',
                'errors' => 'Bad Request'
            ], 400);
        }
        $user_id = Auth::user()->id;
        if($user_id) {
            $user = Auth::user();
            $address="";
            if($request->hasFile('image')) {
                $file = $request->file('image');
                $file->move(public_path() . '/images/users/',date('mdHis').$request->file('image')->getClientOriginalName());
                $src = '/images/users/'.date('mdHis').$request->file('image')->getClientOriginalName();
                $user->image = $src;
            }
            if($request->hasFile('driving_license_img')) {
                $file = $request->file('driving_license_img');
                $file->move(public_path() . '/images/driving_license/',date('mdHis').$request->file('driving_license_img')->getClientOriginalName());
                $driving_license_img = '/images/driving_license/'.date('mdHis').$request->file('driving_license_img')->getClientOriginalName();
                $user->driving_license_img = $driving_license_img;
            }
            if($request->input('password')!='') {
                if($request->input('password')==$request->input('password2')) {
                    $user->password = Hash::make($request->input('password'));
                    DB::table('action_log')->insert(['type'=>'change password','user_id'=>$user_id,'action_user_id'=>Auth::user()->id]);
                } else {
                    return response()->json([
                        'message' => 'Passwords must match',
                        'errors' => 'Bad Request'
                    ], 400);
                }
            }
            if($request->input('name')!='') {
                $user->name = $request->input('name');
            }
            if($request->input('last_name')!='') {
                $user->last_name = $request->input('last_name');
            }
            if($request->input('email')!='') {
                $user->email = $request->input('email');
            }
            if($request->input('phone')!='') {
                $user->phone = $request->input('phone');
            }
            if($request->input('address')!='') {
                if(!empty($request->input('zip'))) {
                    $address = $request->input('address').' '.$request->input('zip');
                } else {
                    $address = $request->input('address');
                }
                $data = json_decode(file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?address=".urlencode($address)."&key=".config('app.googlemaps_apikey')));
                $address = $data->results[0]->formatted_address;
                $location = $data->results[0]->geometry->location->lat.','.$data->results[0]->geometry->location->lng;
                $user->address = $address;
                $user->location = $location;
            }
            if($request->input('city')!='') {
                $user->city = $request->input('city');
            }
            if($request->input('state')!='') {
                $user->state = $request->input('state');
            }
            if($request->input('zip')!='') {
                $user->zip = $request->input('zip');
            }
            if($request->input('apartment')!='') {
                $user->apartment = $request->input('apartment');
            }
            if($request->input('driving_license')!='') {
                $user->driving_license = $request->input('driving_license');
            }
            if($request->input('identification_cards')!='') {
                $user->identification_cards = $request->input('identification_cards');
            }
            if($request->input('car_info')!='') {
                $user->car_info = $request->input('car_info');
            }
            if($request->input('payment_card')!='') {
                $user->payment_card = $request->input('payment_card');
            }
            self::action_log_user_check($request,$address,$user_id);
            $user->save();
            return response()->json([
                'message' => 'Profile successfully updated'
            ], 200);
        } else {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        }
    }

    private static function action_log_user_check($request,$address,$user_id) {
        $user = DB::table('users')->where('id', $user_id)->first();
        if($request->input('name')!=$user->name) {
            DB::table('action_log')->insert(['type'=>'change name','comment'=>'from '.$user->name.' to '.$request->input('name'),'user_id'=>$user_id,'action_user_id'=>Auth::user()->id]);
        }
        if($request->input('last_name')!=$user->last_name) {
            DB::table('action_log')->insert(['type'=>'change last_name','comment'=>'from '.$user->last_name.' to '.$request->input('last_name'),'user_id'=>$user_id,'action_user_id'=>Auth::user()->id]);
        }
        if($request->input('email')!=$user->email) {
            DB::table('action_log')->insert(['type'=>'change email','comment'=>'from '.$user->email.' to '.$request->input('email'),'user_id'=>$user_id,'action_user_id'=>Auth::user()->id]);
        }
        if($request->input('phone')!=$user->phone) {
            DB::table('action_log')->insert(['type'=>'change phone','comment'=>'from '.$user->phone.' to '.$request->input('phone'),'user_id'=>$user_id,'action_user_id'=>Auth::user()->id]);
        }
        if(!empty($address) && $address!=$user->address) {
            DB::table('action_log')->insert(['type'=>'change address','comment'=>'from '.$user->address.' to '.$request->input('address'),'user_id'=>$user_id,'action_user_id'=>Auth::user()->id]);
        }
        if($request->input('zip')!=$user->zip) {
            DB::table('action_log')->insert(['type'=>'change zip','comment'=>'from '.$user->zip.' to '.$request->input('zip'),'user_id'=>$user_id,'action_user_id'=>Auth::user()->id]);
        }
        if($request->input('apartment')!=$user->apartment) {
            DB::table('action_log')->insert(['type'=>'change apartment','comment'=>'from '.$user->apartment.' to '.$request->input('apartment'),'user_id'=>$user_id,'action_user_id'=>Auth::user()->id]);
        }
        if(!empty($request->input('car_info')) && $request->input('car_info')!=$user->car_info) {
            DB::table('action_log')->insert(['type'=>'change car_info','comment'=>'from '.$user->car_info.' to '.$request->input('car_info'),'user_id'=>$user_id,'action_user_id'=>Auth::user()->id]);
        }
        if(!empty($request->input('pharmacy_id')) && $request->input('pharmacy_id')!=$user->pharmacy_id) {
            DB::table('action_log')->insert(['type'=>'change pharmacy_id','comment'=>'from '.$user->pharmacy_id.' to '.$request->input('pharmacy_id'),'user_id'=>$user_id,'action_user_id'=>Auth::user()->id]);
        }
        return true;
    }
    
    public static function patient_home() {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        if(Auth::user()->role == 'user') {
            $wishs = DB::table('wishes')->join('wishes_category',"wishes.category_id","=","wishes_category.id")->where("wishes_category.status",1)->pluck('wishes.text')->toArray();
            $head_text = $wishs[array_rand($wishs)];
            $pharmacy = DB::table('pharmacys')->where("id",Auth::user()->pharmacy_id)->first();
            $last_order = DB::table('orders')->join('users', 'orders.user_id', '=', 'users.id')->join('statuses', 'orders.statuse_id', '=', 'statuses.id')->leftJoin('statuses_copay', 'orders.statuse_copay', '=', 'statuses_copay.id')->join('delivery_methods', 'orders.delivery_method_id', '=', 'delivery_methods.id')->join('delivery_times', 'orders.delivery_time_id', '=', 'delivery_times.id')->leftJoin('medicine', 'orders.id', '=', 'medicine.order_id')->join('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id')->select('orders.id', 'orders.created', 'orders.finish', 'orders.statuse_id', 'orders.signature', 'orders.fridge', 'orders.special_instructions', 'orders.user_id',  'orders.copay', 'orders.driver_id', 'orders.count_bags', 'orders.drop_off_photo', 'orders.signature_photo', 'users.name as username', 'users.last_name as last_name', 'delivery_methods.name as delivery_method', 'delivery_times.name as delivery_time', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end as useraddress'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end as userapartment'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end as userzip'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end as userlocation'), 'users.phone as userphone', DB::raw('sum(medicine.count) as count'), 'pharmacys.name as pharmacyname', 'pharmacys.address as pharmacyaddress','pharmacys.phone as pharmacyphone', 'pharmacys.location as pharmacylocation', 'statuses.name as statusename','statuses.color as statusecolor','orders.statuse_copay', 'statuses_copay.name as statuse_copay_name')->where('orders.user_id',Auth::user()->id)->groupBy('orders.id', 'orders.statuse_id', 'orders.created', 'orders.finish', 'orders.driver_id', 'orders.count_bags', 'orders.signature', 'orders.fridge', 'orders.special_instructions', 'orders.copay', 'orders.drop_off_photo','orders.signature_photo', 'orders.user_id', 'users.name', 'users.last_name', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end'), 'users.phone','pharmacys.name', 'delivery_methods.name', 'delivery_times.name', 'pharmacys.location', 'pharmacys.address','pharmacys.phone', 'statuses.name','statuses.color','orders.statuse_copay', 'statuses_copay.name')->orderBy('orders.id','desc')->first();
            if(!empty($last_order)) {
                if($last_order->statuse_id==3) {
                    if($last_order->driver_id>0) {
                        $driver = DB::table('users')->where('id',$last_order->driver_id)->first();
                        $locations = DB::table('locations')->whereIn('id', [DB::raw("select max(`id`) from locations GROUP BY user_id")])->where('user_id',$last_order->driver_id)->first();
                    } else {
                        $driver="";
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
                return response()->json([
                    'head_text' => $head_text,
                    'pharmacy_name' => $pharmacy->name,
                    'last_order' => [
                        'id'=>$last_order->id,
                        'status'=>$last_order->statusename,
                        'eta'=>(!empty($last_order->eta))?$last_order->eta:"Processing",
                        'pharmacyaddress'=>$last_order->pharmacyaddress,
                        'pharmacylocation'=>$last_order->pharmacylocation,
                        'useraddress'=>$last_order->useraddress,
                        'userlocation'=>$last_order->userlocation,
                        'copay'=>$last_order->copay,
                        'copay_status'=>$last_order->statuse_copay_name,
                        'order_location'=>$location
                    ]
                ], 200);
            } else if(!empty($pharmacy)) {
                return response()->json([
                    'head_text' => $head_text,
                    'pharmacy_name' => $pharmacy->name,
                    'last_order' => NULL
                ], 200);
            } else {
                return response()->json([
                    'head_text' => $head_text,
                    'pharmacy_name' => NULL,
                    'last_order' => NULL
                ], 200);
            }
        } else {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        }
    }

    public static function ordersList() {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        if(Auth::user()->role == 'driver' || Auth::user()->role == 'user') {
            if(Auth::user()->role == 'user') {
                $orders = DB::table('orders')->join('users', 'orders.user_id', '=', 'users.id')->join('statuses', 'orders.statuse_id', '=', 'statuses.id')->leftJoin('statuses_copay', 'orders.statuse_copay', '=', 'statuses_copay.id')->join('delivery_methods', 'orders.delivery_method_id', '=', 'delivery_methods.id')->join('delivery_times', 'orders.delivery_time_id', '=', 'delivery_times.id')->join('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id')->select('orders.id', 'orders.created' , 'orders.driver_id', 'orders.statuse_id', 'orders.copay', 'orders.actual', 'orders.pharmacy_id', 'orders.signature_photo', 'users.name as username', 'users.last_name as last_name', 'delivery_methods.name as delivery_method', 'delivery_times.name as delivery_time', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end as useraddress'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end as userapartment'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end as userzip'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end as userlocation'),'users.phone as userphone', 'pharmacys.name as pharmacyname', 'pharmacys.address as pharmacyaddress','pharmacys.location as pharmacylocation','pharmacys.phone as pharmacyphone', 'statuses.name as statusename','statuses.color as statusecolor','orders.statuse_copay', 'statuses_copay.name as statuse_copay_name')->where('orders.user_id',Auth::user()->id)->groupBy('orders.id', 'orders.statuse_id', 'orders.driver_id', 'orders.created', 'orders.signature_photo', 'orders.actual', 'delivery_methods.name', 'delivery_times.name', 'orders.copay', 'orders.pharmacy_id', 'users.name', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end'), 'users.phone','pharmacys.name', 'pharmacys.address','pharmacys.location','pharmacys.phone', 'statuses.name','statuses.color','orders.statuse_copay', 'statuses_copay.name','users.last_name')->orderBy('orders.id','desc');
            } else if (Auth::user()->role == 'driver') {
                $orders = DB::table('orders')->join('users', 'orders.user_id', '=', 'users.id')->join('statuses', 'orders.statuse_id', '=', 'statuses.id')->leftJoin('statuses_copay', 'orders.statuse_copay', '=', 'statuses_copay.id')->join('delivery_methods', 'orders.delivery_method_id', '=', 'delivery_methods.id')->join('delivery_times', 'orders.delivery_time_id', '=', 'delivery_times.id')->join('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id')->select('orders.id', 'orders.created' , 'orders.driver_id', 'orders.statuse_id', 'orders.copay', 'orders.actual', 'orders.pharmacy_id', 'orders.signature_photo', 'users.name as username', 'users.last_name as last_name', 'delivery_methods.name as delivery_method', 'delivery_times.name as delivery_time', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end as useraddress'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end as userapartment'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end as userzip'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end as userlocation'),'users.phone as userphone', 'pharmacys.name as pharmacyname', 'pharmacys.address as pharmacyaddress','pharmacys.location as pharmacylocation','pharmacys.phone as pharmacyphone', 'statuses.name as statusename','statuses.color as statusecolor','orders.statuse_copay', 'statuses_copay.name as statuse_copay_name')->where('orders.driver_id',Auth::user()->id)->groupBy('orders.id', 'orders.statuse_id', 'orders.driver_id', 'orders.created', 'orders.signature_photo', 'orders.actual', 'delivery_methods.name', 'delivery_times.name', 'orders.copay', 'orders.pharmacy_id', 'users.name', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end'), 'users.phone','pharmacys.name', 'pharmacys.address','pharmacys.location','pharmacys.phone', 'statuses.name','statuses.color','orders.statuse_copay', 'statuses_copay.name','users.last_name')->orderBy('orders.id','desc');
            }
            if(!empty($_GET['statuse'])) {
                $orders = $orders->whereIn('orders.statuse_id',$_GET['statuse']);
            }
            if(!empty($_GET['pharmacy'])) {
                $orders = $orders->where('orders.pharmacy_id',$_GET['pharmacy']);
            }
            $orders = $orders->get();
            foreach($orders as $key=>$order) {
                if($order->statuse_id==3) {
                    if($order->driver_id>0) {
                        $driver = DB::table('users')->where('id',$order->driver_id)->first();
                        $locations = DB::table('locations')->whereIn('id', [DB::raw("select max(`id`) from locations GROUP BY user_id")])->where('user_id',$order->driver_id)->first();
                    } else {
                        $driver="";
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
                $order->order_location = $location;
                if($order->statuse_copay==6) {
                    $order->statuse_copay=4;
                    $order->statuse_copay_name='Paid by cash';
                }
                $orders[$key]=$order;
            }
            $statuses = DB::table('statuses')->get();
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

    public static function ordersShow($order_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
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


    public static function ordersShowHandler(Request $request,$order_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
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

    public static function routes() {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        if(Auth::user()->role == 'admin' || Auth::user()->role == 'driver') {
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
            foreach ($routes as $key => $value) {
                if($value->type=='pharmacy') {
                    $pharmacy = DB::table('pharmacys')->where('id',$value->type_id)->first();
                    $routes[$key]->name_point=$pharmacy->name;
                    $routes[$key]->phone_point=$pharmacy->phone;
                    $routes[$key]->address_point=$pharmacy->address;
                    $routes[$key]->google_maps_point="https://www.google.com/maps/dir/".str_replace(' ','',$driver->location)."/".str_replace(' ','',$pharmacy->location)."/";
                    $order=DB::table('orders')->whereRaw('id in ('.$value->order_id.')')->first();
                    if($order->statuse_copay==6) {
                        $order->statuse_copay=4;
                    }
                    $routes[$key]->order=$order;
                    $routes[$key]->order->delivery_time=DB::table('delivery_times')->where('id',$order->delivery_time_id)->first()->name;
                    $routes[$key]->order->delivery_method=DB::table('delivery_methods')->where('id',$order->delivery_method_id)->first()->name;
                }
                if($value->type=='patient') {
                    $patient0 = DB::table('users')->where('id',$value->type_id)->first();
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
                    $order=DB::table('orders')->whereRaw('id in ('.$value->order_id.')')->first();
                    if($order->statuse_copay==6) {
                        $order->statuse_copay=4;
                    }
                    $routes[$key]->order=$order;
                    $routes[$key]->order->delivery_time=DB::table('delivery_times')->where('id',$order->delivery_time_id)->first()->name;
                    $routes[$key]->order->delivery_method=DB::table('delivery_methods')->where('id',$order->delivery_method_id)->first()->name;
                    $routes[$key]->order->count_scanned=DB::table('packages_transitions')->where('order_id',$value->order_id)->where('user_id',$value->type_id)->where('driver_id',Auth::user()->id)->count();
                }
                if($value->type=='office') {
                    $office = DB::table('offices')->where('id',$value->type_id)->first();
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

    public static function routesShow($route_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        if(Auth::user()->role == 'admin' || Auth::user()->role == 'user' || Auth::user()->role == 'driver') {
            $route = DB::table('routes_priority')->select(DB::raw("min(id) as id"), "driver_id", DB::raw("GROUP_CONCAT(order_id SEPARATOR ',') as order_id"), "type", "type_id", DB::raw("min(priority) as priority"))->where('driver_id', Auth::user()->id)->groupBy("type","type_id","driver_id")->havingRaw('min(id) = ?', [$route_id])->orderBy("priority","asc")->first();
            $driver= DB::table('locations')->whereIn('id', [DB::raw("select max(`id`) from locations GROUP BY user_id")])->where('user_id',Auth::user()->id)->first();
            if($route->type=='pharmacy') {
                $pharmacy = DB::table('pharmacys')->where('id',$route->type_id)->first();
                $pharmacy->google_maps_route = "https://www.google.com/maps/dir/".str_replace(' ','',$driver->location)."/".str_replace(' ','',$pharmacy->location)."/";
                return response()->json([
                    'route' => $route,
                    'point' => $pharmacy
                ], 200);
            }
            if($route->type=='patient') {
                $patient0 = DB::table('users')->where('id',$route->type_id)->first();
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
                $route = DB::table('routes_priority')->select(DB::raw("min(id) as id"), "driver_id", DB::raw("GROUP_CONCAT(order_id SEPARATOR ',') as order_id"), "type", "type_id", "priority")->where('driver_id', Auth::user()->id)->groupBy("type","type_id","driver_id", "priority")->havingRaw('min(id) = ?', [$route_id])->orderBy("priority","asc")->get();
                $office = DB::table('offices')->where('id',$route->type_id)->first();
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

    public static function cards() {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        if(Auth::user()->role == 'admin' || Auth::user()->role == 'user' || Auth::user()->role == 'driver') {
            $payment_account = DB::table('payment_accounts')->where('user_id',Auth::user()->id)->first();
            if(!empty($payment_account)) {
                return response()->json([
                    'card' => "XXXX".substr($payment_account->card,-4)
                ], 200);
            } else {
                return response()->json([
                    'card' => ""
                ], 200);
            }
        } else {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        }
    }

    public static function cardAddHandler(Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        if(Auth::user()->role == 'admin' || Auth::user()->role == 'user' || Auth::user()->role == 'driver') {
            if(!empty($request->input('name')) && !empty($request->input('address')) && !empty($request->input('zip'))) {
                $card_token = $request->input('card');
                $type = 'card';
                $name=explode(' ',$request->input('name'));
                if(count($name)==1) {
                    $name[1]="";
                }
                $zip=$request->input('zip');
                $user_address = explode(',',$request->input('address'));
                if(count($user_address)==1) {
                    $user_address[1]="";
                    $user_address[2]="";
                }
                if(count($user_address)==2) {
                    $user_address[2]="";
                }
                if(count($user_address)==3) {
                    $user_address[3]="NY";
                }
                $amount = 1;
                $payment_account = DB::table('payment_accounts')->where('user_id',Auth::user()->id)->first();
                $client = new \Square\SquareClient([
                    'accessToken' => config('app.SQUARE_ACCESS_TOKEN'),
                    'environment' => config('app.SQUARE_ENVIRONMENT'),
                ]);
                $address = new \Square\Models\Address();
                $address->setAddressLine1($user_address[0]);
                $address->setAddressLine2($user_address[1]);
                $address->setLocality($user_address[2]);
                $address->setAdministrativeDistrictLevel1($user_address[3]);
                $address->setPostalCode($zip);
                $address->setCountry('US');
                if(empty($payment_account)) {
                    $body = new \Square\Models\CreateCustomerRequest();
                    $body->setGivenName($name[0]);
                    $body->setFamilyName($name[1]);
                    $body->setEmailAddress(Auth::user()->email);
                    $body->setAddress($address);
                    $body->setPhoneNumber(str_replace(" ","",str_replace("-","",str_replace(")","",str_replace("(","",Auth::user()->phone)))));
                    $body->setReferenceId(Auth::user()->id);
                    $body->setNote('User #'.Auth::user()->id);
                    $api_response = $client->getCustomersApi()->createCustomer($body);
                    if ($api_response->isSuccess()) {
                        $result = $api_response->getResult();
                        DB::table('payment_accounts')->insert(['user_id'=>Auth::user()->id,"type"=>$type,"profile_id"=>$result->getCustomer()->getId()]);
                        $payment_account = DB::table('payment_accounts')->where('user_id',Auth::user()->id)->first();
                    } else {
                        $error = json_encode($api_response->getErrors());
                        return response()->json([
                            'message' => $error,
                            'errors' => 'Bad Request'
                        ], 400);
                    }
                } else {
                    DB::table('payment_accounts')->where('user_id',Auth::user()->id)->update(["type"=>$type]);
                }
                if($type=="card") {
                    $amount_money = new \Square\Models\Money();
                    $amount_money->setAmount(($amount*100));
                    $amount_money->setCurrency('USD');
                    $unid = uniqid("",true).rand(0,100);
                    $body = new \Square\Models\CreatePaymentRequest(
                        $card_token,
                        $unid,
                        $amount_money
                    );
                    $body->setAutocomplete(true);
                    $body->setCustomerId($payment_account->profile_id);
                    $body->setLocationId(config('app.SQUARE_LOCATION_ID'));
                    $body->setNote('Authorization Card');
                    $api_response = $client->getPaymentsApi()->createPayment($body);
                    if ($api_response->isSuccess()) {
                        $result = $api_response->getResult();
                        $payment_id = $result->getPayment()->getId();
                        $payment_status = $result->getPayment()->getStatus();
                        if($payment_status=="COMPLETED" || $payment_status=="APPROVED" || $payment_status=="PENDING") {
                            DB::table('payment_accounts')->where('user_id',Auth::user()->id)->update(["payment_id"=>$payment_id]);
                            $card = new \Square\Models\Card();
                            $card->setCardholderName($name[0].' '.$name[1]);
                            $card->setBillingAddress($address);
                            $card->setCustomerId($payment_account->profile_id);
                            $unid = uniqid("",true).rand(0,100);
                            $body = new \Square\Models\CreateCardRequest(
                                $unid,
                                $payment_id,
                                $card
                            );
                            $api_response = $client->getCardsApi()->createCard($body);
                            if ($api_response->isSuccess()) {
                                $result = $api_response->getResult();
                                $unid = uniqid("",true).rand(0,100);
                                DB::table('payment_accounts')->where('user_id',Auth::user()->id)->update(["payment_profile_id"=>$result->getCard()->getId(),"card"=>$result->getCard()->getLast4()]);
                                $body = new \Square\Models\RefundPaymentRequest($unid, $amount_money);
                                $body->setPaymentId($payment_id);
                                $api_response = $client->getRefundsApi()->refundPayment($body);
                                if ($api_response->isSuccess()) {
                                    $success="Payment Method successfully added!";
                                    return response()->json([
                                        'message' => $success
                                    ], 200);
                                } else {
                                    $error = "Payment Method successfully added, but refund payment not completed. ".json_encode($api_response->getErrors());
                                    return response()->json([
                                        'message' => $error,
                                        'errors' => 'Bad Request'
                                    ], 400);
                                }
                            } else {
                                $error = json_encode($api_response->getErrors());
                                return response()->json([
                                    'message' => $error,
                                    'errors' => 'Bad Request'
                                ], 400);
                            }
                        } else {
                            $error = "Payment not completed!";
                            return response()->json([
                                'message' => $error,
                                'errors' => 'Bad Request'
                            ], 400);
                        }
                    } else {
                        $error = json_encode($api_response->getErrors());
                        return response()->json([
                            'message' => $error,
                            'errors' => 'Bad Request'
                        ], 400);
                    }
                }
            } else {
                return response()->json([
                    'message' => 'All card input is required.',
                    'errors' => 'Bad Request'
                ], 400);
            }
        } else {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        }
    }

    public static function qr_scanedHandler(Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        if(Auth::user()->role == 'admin' || Auth::user()->role == 'driver') {
            $code = explode("_",$request->input('order_id'));
            $order_id = $code[0];
            if(isset($code[1])) {
                $bag = $code[1];
            } else {
                $bag = 1;
            }
            if(empty($order_id)) {
                return response()->json([
                    'message' => 'Something is wrong with this field!',
                    'errors' => 'Bad Request'
                ], 400);
            }
            $order=DB::table('orders')->where('id',$order_id)->where('driver_id',Auth::user()->id)->first();
            if(!empty($order)) {
                if($order->statuse_id==1 || $order->statuse_id==2 || $order->statuse_id==6) {
                    DB::table('orders')->where('id',$order_id)->update(['statuse_id'=>3]);
                    Notifications::send_push($order->user_id,"A2BRx","Your order #$order_id is on its way!");
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

    public static function drop_offHandler(Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        if(Auth::user()->role == 'admin' || Auth::user()->role == 'driver') {
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
            if(isset($code[1])) {
                $bag = $code[1];
            } else {
                $bag = 1;
            }
            if(empty($order_id)) {
                return response()->json([
                    'message' => 'Something is wrong with this field!',
                    'errors' => 'Bad Request'
                ], 400);
            }
            $order=DB::table('orders')->where('id',$order_id)->where('driver_id',Auth::user()->id)->first();
            if(!empty($order)) {
                if($request->hasFile('image')) {
                    $file = $request->file('image');
                    $file->move(public_path() . '/images/drop_off/',date('mdHis').$request->file('image')->getClientOriginalName());
                    $src = '/images/drop_off/'.date('mdHis').$request->file('image')->getClientOriginalName();
                }
                if(empty($order->signature_photo)) {
                    DB::table('orders')->where('id',$order_id)->update(['drop_off_photo'=>$src]);
                } else {
                    $pharmacy=DB::table('pharmacys')->where('pharmacys.id',$order->pharmacy_id)->first();
                    $pharmacy_plan=DB::table('plans')->where('plans.id',$pharmacy->plan_id)->first();
                    $patient=DB::table('users')->where('users.id',$order->user_id)->first();
                    $pharmacy_areas=DB::table('pharmacy_areas')->where('pharmacy_id',$order->pharmacy_id)->where('type',1)->pluck('area_id')->toArray();
                    $pharmacy_areas2=DB::table('pharmacy_areas')->where('pharmacy_id',$order->pharmacy_id)->where('type',2)->pluck('area_id')->toArray();
                    $pharmacy_areas3=DB::table('pharmacy_areas')->where('pharmacy_id',$order->pharmacy_id)->where('type',3)->pluck('area_id')->toArray();
                    $zip_tariff=DB::table('area')->whereIn('area.id',$pharmacy_areas)->whereRaw('ST_CONTAINS(polygon, POINT('.$patient->location.'))')->select("area.id")->first();
                    $zip_tariff2=DB::table('area')->whereIn('area.id',$pharmacy_areas2)->whereRaw('ST_CONTAINS(polygon, POINT('.$patient->location.'))')->select("area.id")->first();
                    $zip_tariff3=DB::table('area')->whereIn('area.id',$pharmacy_areas3)->whereRaw('ST_CONTAINS(polygon, POINT('.$patient->location.'))')->select("area.id")->first();
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
                        }
                        if($order->delivery_time_id==2) {
                            $tariff_res = (floatval($tariff)+floatval($tariff_same_day)+floatval($order->extra_charge_driver));
                        }
                        if($order->delivery_time_id==3) {
                            $tariff_res = (floatval($tariff)+floatval($tariff_asap)+floatval($order->extra_charge_driver));
                        }
                        if($order->delivery_time_id==4) {
                            $tariff_res = (floatval($tariff)+floatval($tariff_after_hours)+floatval($order->extra_charge_driver));
                        }
                        if($order->fridge==1) {
                            $tariff_res+= floatval($tariff_fridge);
                        }
                    } else {
                        $tariff_res = floatval($tariff);
                    }
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
                    DB::table('orders')->where('id',$order_id)->update(['statuse_id'=>4,'finish'=>date('Y-m-d H:i:s'),'delivery_address'=>$user_address,'delivery_location'=>$driver_location,'drop_off_photo'=>$src,'tariff'=>$tariff_res]);
                    $route = DB::table('routes_priority')->where('order_id',$order_id)->where('driver_id',Auth::user()->id)->where('type','patient');
                    foreach($route->get() as $routes_priority) {
                        DB::table('routes_priority_logs')->insert(["id"=>$routes_priority->id,"driver_id"=>$routes_priority->driver_id,"order_id"=>$routes_priority->order_id,"type"=>$routes_priority->type,"type_id"=>$routes_priority->type_id,"type_pay"=>$routes_priority->type_pay,"pay_value"=>$routes_priority->pay_value]);
                        if($routes_priority->type_pay==1) {
                            DB::table('payouts_driver')->insert(['driver_id'=>Auth::user()->id,'order_id'=>$order_id,'amount'=>$routes_priority->pay_value]);
                        }
                        if($routes_priority->type_pay==2) {
                            DB::table('payouts_driver')->insert(['driver_id'=>Auth::user()->id,'order_id'=>$order_id,'amount'=>$routes_priority->pay_value]);
                        }
                        if($routes_priority->type_pay==3) {
                            DB::table('payouts_driver')->insert(['driver_id'=>Auth::user()->id,'order_id'=>$order_id,'amount'=>$routes_priority->pay_value*intval($order->count_bags)]);
                        }
                    }
                    $route->delete();
                    Notifications::send_push($order->user_id,"A2BRx","Your order #$order_id has been delivered. \nIf you have any questions please contact us (855) 657-9595 \nBest regards, A2B Rx Inc.");
                    $next_route = DB::table('routes_priority')->where('driver_id',Auth::user()->id)->orderBy("priority","asc")->first();
                    if(!empty($next_route)) {
                        if($next_route->type=='patient') {
                            self::next_patient_push(Auth::user()->id,$next_route);
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

    public static function signatureHandler(Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        if(Auth::user()->role == 'admin' || Auth::user()->role == 'driver') {
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
            if(isset($code[1])) {
                $bag = $code[1];
            } else {
                $bag = 1;
            }
            if(empty($order_id)) {
                return response()->json([
                    'message' => 'Something is wrong with this field!',
                    'errors' => 'Bad Request'
                ], 400);
            }
            $order=DB::table('orders')->where('id',$order_id)->where('driver_id',Auth::user()->id)->first();
            if(!empty($order)) {
                if($request->hasFile('image')) {
                    $file = $request->file('image');
                    $file->move(public_path() . '/images/signature/',date('mdHis').$request->file('image')->getClientOriginalName());
                    $src = '/images/signature/'.date('mdHis').$request->file('image')->getClientOriginalName();
                }
                if($order->signature>0) {
                    if(empty($order->drop_off_photo)) {
                        DB::table('orders')->where('id',$order_id)->update(['signature_photo'=>$src,'signature_type'=>$request->input('signature_type')]);
                    } else {
                        $pharmacy=DB::table('pharmacys')->where('pharmacys.id',$order->pharmacy_id)->first();
                        $pharmacy_plan=DB::table('plans')->where('plans.id',$pharmacy->plan_id)->first();
                        $patient=DB::table('users')->where('users.id',$order->user_id)->first();
                        $pharmacy_areas=DB::table('pharmacy_areas')->where('pharmacy_id',$order->pharmacy_id)->where('type',1)->pluck('area_id')->toArray();
                        $pharmacy_areas2=DB::table('pharmacy_areas')->where('pharmacy_id',$order->pharmacy_id)->where('type',2)->pluck('area_id')->toArray();
                        $pharmacy_areas3=DB::table('pharmacy_areas')->where('pharmacy_id',$order->pharmacy_id)->where('type',3)->pluck('area_id')->toArray();
                        $zip_tariff=DB::table('area')->whereIn('area.id',$pharmacy_areas)->whereRaw('ST_CONTAINS(polygon, POINT('.$patient->location.'))')->select("area.id")->first();
                        $zip_tariff2=DB::table('area')->whereIn('area.id',$pharmacy_areas2)->whereRaw('ST_CONTAINS(polygon, POINT('.$patient->location.'))')->select("area.id")->first();
                        $zip_tariff3=DB::table('area')->whereIn('area.id',$pharmacy_areas3)->whereRaw('ST_CONTAINS(polygon, POINT('.$patient->location.'))')->select("area.id")->first();
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
                            }
                            if($order->delivery_time_id==2) {
                                $tariff_res = (floatval($tariff)+floatval($tariff_same_day)+floatval($order->extra_charge_driver));
                            }
                            if($order->delivery_time_id==3) {
                                $tariff_res = (floatval($tariff)+floatval($tariff_asap)+floatval($order->extra_charge_driver));
                            }
                            if($order->delivery_time_id==4) {
                                $tariff_res = (floatval($tariff)+floatval($tariff_after_hours)+floatval($order->extra_charge_driver));
                            }
                            if($order->fridge==1) {
                                $tariff_res+= floatval($tariff_fridge);
                            }
                        } else {
                            $tariff_res = floatval($tariff);
                        }
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
                        DB::table('orders')->where('id',$order_id)->update(['statuse_id'=>4,'finish'=>date('Y-m-d H:i:s'),'delivery_address'=>$user_address,'delivery_location'=>$driver_location,'signature_photo'=>$src,'signature_type'=>$request->input('signature_type'),'tariff'=>$tariff_res]);
                        $route = DB::table('routes_priority')->where('order_id',$order_id)->where('driver_id',Auth::user()->id)->where('type','patient');
                        foreach($route->get() as $routes_priority) {
                            DB::table('routes_priority_logs')->insert(["id"=>$routes_priority->id,"driver_id"=>$routes_priority->driver_id,"order_id"=>$routes_priority->order_id,"type"=>$routes_priority->type,"type_id"=>$routes_priority->type_id,"type_pay"=>$routes_priority->type_pay,"pay_value"=>$routes_priority->pay_value]);
                            if($routes_priority->type_pay==1) {
                                DB::table('payouts_driver')->insert(['driver_id'=>Auth::user()->id,'order_id'=>$order_id,'amount'=>$routes_priority->pay_value]);
                            }
                            if($routes_priority->type_pay==2) {
                                DB::table('payouts_driver')->insert(['driver_id'=>Auth::user()->id,'order_id'=>$order_id,'amount'=>$routes_priority->pay_value]);
                            }
                            if($routes_priority->type_pay==3) {
                                DB::table('payouts_driver')->insert(['driver_id'=>Auth::user()->id,'order_id'=>$order_id,'amount'=>$routes_priority->pay_value*intval($order->count_bags)]);
                            }
                        }
                        $route->delete();
                        Notifications::send_push($order->user_id,"A2BRx","Your order #$order_id has been delivered. \nIf you have any questions please contact us (855) 657-9595 \nBest regards, A2B Rx Inc.");
                        $next_route = DB::table('routes_priority')->where('driver_id',Auth::user()->id)->orderBy("priority","asc")->first();
                        if(!empty($next_route)) {
                            if($next_route->type=='patient') {
                                self::next_patient_push(Auth::user()->id,$next_route);
                            }
                        }
                    }
                } else {
                    DB::table('orders')->where('id',$order_id)->update(['signature_photo'=>$src,'signature_type'=>$request->input('signature_type')]);
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
        } else if(Auth::user()->role == 'user') {
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
                if($request->hasFile('image')) {
                    $file = $request->file('image');
                    $file->move(public_path() . '/images/signature/',date('mdHis').$request->file('image')->getClientOriginalName());
                    $src = '/images/signature/'.date('mdHis').$request->file('image')->getClientOriginalName();
                }
                DB::table('orders')->where('id',$order_id)->update(['signature_photo'=>$src,'signature_type'=>'Patient']);
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

    public static function locationHandler(Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        if(Auth::user()->role == 'admin' || Auth::user()->role == 'driver') {
            $location = $request->input('location');
            if(empty($location)) {
                return response()->json([
                    'message' => 'Something is wrong with this field!',
                    'errors' => 'Bad Request'
                ], 400);
            }
            DB::table('locations')->insert(['user_id'=>Auth::user()->id,'location'=>$location]);
            return response()->json([
                'message' => 'Location was added'
            ], 200);
        } else {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        }
    }

    public static function driverQr() {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        if(Auth::user()->role == 'driver') {
            $token=bin2hex(random_bytes(24));
            DB::table('tokens_driver')->insert(['user_id'=>Auth::user()->id,'token'=>$token]);
            return response()->json([
                'qr-code' => "https://quickchart.io/qr?text=$token"
            ], 200);
        } else {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        }
    }

    public static function updateDevice(Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        $device_token = $request->input('device_token');
        if(empty($device_token)) {
            return response()->json([
                'message' => 'Something is wrong with this field!',
                'errors' => 'Bad Request'
            ], 400);
        }
        $twilio = new Client(config('app.twilio_sid'), config('app.twilio_auth_token'));
        if(Auth::user()->os==1) {
            if(Auth::user()->role=='driver') {
                $bindings = $twilio->notify->v1->services(config('app.twilio_notifyDriverServiceSid'))->bindings->read(["identity"=>Auth::user()->id], 20);
                foreach ($bindings as $record) {
                    $twilio->notify->v1->services(config('app.twilio_notifyDriverServiceSid'))->bindings($record->sid)->delete();
                }
                $binding = $twilio->notify->v1->services(config('app.twilio_notifyDriverServiceSid'))->bindings->create(Auth::user()->id, "fcm", $device_token);
                DB::table('users')->where('id',Auth::user()->id)->update(['device_token'=>$device_token]);
                // Create a notification Notifications::send_push(Auth::user()->id,"A2BRx","Hello Test");
            } else {
                $bindings = $twilio->notify->v1->services(config('app.twilio_notifyClientServiceSid'))->bindings->read(["identity"=>Auth::user()->id], 20);
                foreach ($bindings as $record) {
                    $twilio->notify->v1->services(config('app.twilio_notifyClientServiceSid'))->bindings($record->sid)->delete();
                }
                $binding = $twilio->notify->v1->services(config('app.twilio_notifyClientServiceSid'))->bindings->create(Auth::user()->id, "fcm", $device_token);
                DB::table('users')->where('id',Auth::user()->id)->update(['device_token'=>$device_token]);
                // Create a notification Notifications::send_push(Auth::user()->id,"A2BRx","Hello Test");
            }
        } else {
            if(Auth::user()->role=='driver') {
                $bindings = $twilio->notify->v1->services(config('app.twilio_notifyDriverIOSServiceSid'))->bindings->read(["identity"=>Auth::user()->id], 20);
                foreach ($bindings as $record) {
                    $twilio->notify->v1->services(config('app.twilio_notifyDriverIOSServiceSid'))->bindings($record->sid)->delete();
                }
                $binding = $twilio->notify->v1->services(config('app.twilio_notifyDriverIOSServiceSid'))->bindings->create(Auth::user()->id, "fcm", $device_token);
                DB::table('users')->where('id',Auth::user()->id)->update(['device_token'=>$device_token]);
                // Create a notification Notifications::send_push(Auth::user()->id,"A2BRx","Hello Test");
            } else {
                $bindings = $twilio->notify->v1->services(config('app.twilio_notifyClientIOSServiceSid'))->bindings->read(["identity"=>Auth::user()->id], 20);
                foreach ($bindings as $record) {
                    $twilio->notify->v1->services(config('app.twilio_notifyClientIOSServiceSid'))->bindings($record->sid)->delete();
                }
                $binding = $twilio->notify->v1->services(config('app.twilio_notifyClientIOSServiceSid'))->bindings->create(Auth::user()->id, "fcm", $device_token);
                DB::table('users')->where('id',Auth::user()->id)->update(['device_token'=>$device_token]);
                // Create a notification Notifications::send_push(Auth::user()->id,"A2BRx","Hello Test");
            }
        }
        return response()->json([
            'message' => 'Device Token was updated'
        ], 200);
    }

    public static function driverStatus() {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        return response()->json([
            'work_now' => Auth::user()->work_now
        ], 200);
    }

    public static function driverStatusStart() {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        DB::table('users')->where('id',Auth::user()->id)->update(['work_now'=>1]);
        return response()->json([
            'message' => 'Driver Status was updated'
        ], 200);
    }

    public static function driverStatusFinish() {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        DB::table('users')->where('id',Auth::user()->id)->update(['work_now'=>0]);
        return response()->json([
            'message' => 'Driver Status was updated'
        ], 200);
    }

    public static function testNotification($user_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        $bing = Notifications::send_push($user_id,"A2BRx","Test Notification");
        return response()->json([
            'message' => $bing
        ], 200);
    }

    public static function userCountunread() {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        $count_noread=Auth::user()->get_unread_mess();
        return response()->json([
            'count_unread_message' => $count_noread
        ], 200);
    }

    public static function chats() {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        $chats_db = DB::table('chats')->orWhere('user1',Auth::user()->id)->orWhere('user2',Auth::user()->id)->orderBy('last_message_date','desc')->get();
        $chats=array();
        foreach ($chats_db as $chat) {
            $chat_name=$chat->name;
            if($chat->user1==Auth::user()->id) {
                $user_id=$chat->user2;
                $count_noread=$chat->unread_user1;
            } else {
                $user_id=$chat->user1;
                $count_noread=$chat->unread_user2;
            }
            $last_message_date=$chat->last_message_date;
            $last_message_body=$chat->last_message_body;
            $user = DB::table('users')->where('id', $user_id)->first();
            $user_pharmacy = DB::table('pharmacys')->where('id', $user->pharmacy_id)->first();
            if(empty($user_pharmacy)) {
                $user_pharmacy='';
            } else {
                $user_pharmacy=' ('.$user_pharmacy->name.')';
            }
            array_push($chats,['link'=>"/api/chats/user/$user_id",'count_noread'=>$count_noread,'name'=>$user->name.' '.$user->last_name.$user_pharmacy,'image'=>$user->image,'last_message_date'=>$last_message_date,'last_message_body'=>$last_message_body]);
        }
        return response()->json([
            'chats' => $chats
        ], 200);
    }

    public function chatUser($user_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        $user = DB::table('users')->where('id', $user_id)->first();
        $user_pharmacy = DB::table('pharmacys')->where('id', $user->pharmacy_id)->first();
        $user_pharmacy2 = DB::table('pharmacys')->where('id', Auth::user()->pharmacy_id)->first();
        if(empty($user_pharmacy)) {
            $user_pharmacy='';
        } else {
            $user_pharmacy=' ('.$user_pharmacy->name.')';
        }
        if(empty($user_pharmacy2)) {
            $user_pharmacy2='';
        } else {
            $user_pharmacy2=' ('.$user_pharmacy2->name.')';
        }
        $chat = DB::table('chats')->orWhere('name', $user_id.'_'.Auth::user()->id)->orWhere('name', Auth::user()->id.'_'.$user_id)->first();
        if(!empty($chat->id)) {
            $chat_name = $chat->name;
        } else {
            $chat_name = Auth::user()->id.'_'.$user_id;
            DB::table('chats')->insert(['name'=>$chat_name,'user1'=>Auth::user()->id,'user2'=>$user_id]);
        }
        $chat = DB::table('chats')->orWhere('name', $user_id.'_'.Auth::user()->id)->orWhere('name', Auth::user()->id.'_'.$user_id)->first();
        $twilio = new Client(config('app.twilio_sid'), config('app.twilio_auth_token'));
        $channels = $twilio->chat->v2->services(config('app.twilio_chatServiceSid'))->channels->read(['uniqueName'=>$chat_name]);
        foreach ($channels as $record) {
            if($record->uniqueName==$chat_name) {
                $channel_sid = $record->sid;
            }
        }
        if(empty($channel_sid)) {
            $channel = $twilio->chat->v2->services(config('app.twilio_chatServiceSid'))->channels->create(['friendlyName'=>$chat_name,'uniqueName'=>$chat_name]);
            $channel_sid = $channel->sid;
        }
        $members = $twilio->chat->v2->services(config('app.twilio_chatServiceSid'))->channels($channel_sid)->members->read();
        foreach ($members as $record) {
            if($record->identity==Auth::user()->id) {
                $member_sid = $record->sid;
            }
            if($record->identity==$user_id) {
                $user_last_index = $record->lastConsumedMessageIndex;
            }
        }
        if(empty($member_sid)) {
            $member = $twilio->chat->v2->services(config('app.twilio_chatServiceSid'))->channels($channel_sid)->members->create(Auth::user()->id);
            $member_sid = $member->sid;
        }
        if(empty($user_last_index)) {
            $user_last_index=0;
        }
        $messages = $twilio->chat->v2->services(config('app.twilio_chatServiceSid'))->channels($channel_sid)->messages->read();
        if(!isset(end($messages)->index)) {
            $max_message_index=0;
            $last_message_date=null;
            $last_message_body=null;
        } else {
            $max_message_index=end($messages)->index;
            $last_message_date=end($messages)->dateCreated->setTimezone(new \DateTimeZone('America/New_York'))->format("Y-m-d H:i:s");
            $last_message_body=end($messages)->body;
        }
        $member = $twilio->chat->v2->services(config('app.twilio_chatServiceSid'))->channels($channel_sid)->members($member_sid)->update(["lastConsumedMessageIndex" => $max_message_index]);
        if($chat->user1==Auth::user()->id) {
            DB::table('chats')->where('name',$chat_name)->update(['unread_user1'=>0,'last_message_date'=>$last_message_date,'last_message_body'=>$last_message_body]);
        } else {
            DB::table('chats')->where('name',$chat_name)->update(['unread_user2'=>0,'last_message_date'=>$last_message_date,'last_message_body'=>$last_message_body]);
        }
        return view('chats.chat_api',['user'=>$user,'chat_name'=>$chat_name,'messages'=>$messages,'user_last_index'=>$user_last_index]);
    }

    public static function routesLogs(Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        if(Auth::user()->role == 'admin' || Auth::user()->role == 'driver') {
            if(empty($request->input('from')) && empty($request->input('to'))) {
                $routes = DB::table('routes_priority_logs')->select(DB::raw("min(id) as id"), "driver_id", DB::raw("GROUP_CONCAT(order_id SEPARATOR ',') as order_id"), "type", "type_id")->where('driver_id', Auth::user()->id)->groupBy("type","type_id","driver_id")->orderBy("created","desc")->limit(10)->get();
            } else {
                $from = date("Y-m-d H:i:s",strtotime($request->input('from')));
                $to = date("Y-m-d H:i:s",strtotime($request->input('to')));
                $routes = DB::table('routes_priority_logs')->select(DB::raw("min(id) as id"), "driver_id", DB::raw("GROUP_CONCAT(order_id SEPARATOR ',') as order_id"), "type", "type_id")->where('driver_id', Auth::user()->id)->whereRaw("created between '$from' and '$to'")->groupBy("type","type_id","driver_id")->orderBy("created","desc")->get();
            }
            foreach ($routes as $key => $value) {
                if($value->type=='pharmacy') {
                    $pharmacy = DB::table('pharmacys')->where('id',$value->type_id)->first();
                    $routes[$key]->name_point=$pharmacy->name;
                    $routes[$key]->phone_point=$pharmacy->phone;
                    $routes[$key]->address_point=$pharmacy->address;
                    $order=DB::table('orders')->whereRaw('id in ('.$value->order_id.')')->first();
                    $routes[$key]->order=$order;
                    $routes[$key]->order->delivery_time=DB::table('delivery_times')->where('id',$order->delivery_time_id)->first()->name;
                    $routes[$key]->order->delivery_method=DB::table('delivery_methods')->where('id',$order->delivery_method_id)->first()->name;
                }
                if($value->type=='patient') {
                    $patient0 = DB::table('users')->where('id',$value->type_id)->first();
                    $routes[$key]->name_point=$patient0->name.' '.$patient0->last_name;
                    $routes[$key]->phone_point=$patient0->phone;
                    if($patient0->primary_address==3){
                        $routes[$key]->address_point=$patient0->address3;
                    } elseif($patient0->primary_address==2){
                        $routes[$key]->address_point=$patient0->address2;
                    } else {
                        $routes[$key]->address_point=$patient0->address;
                    }
                    $order=DB::table('orders')->whereRaw('id in ('.$value->order_id.')')->first();
                    $routes[$key]->order=$order;
                    $routes[$key]->order->delivery_time=DB::table('delivery_times')->where('id',$order->delivery_time_id)->first()->name;
                    $routes[$key]->order->delivery_method=DB::table('delivery_methods')->where('id',$order->delivery_method_id)->first()->name;
                }
                if($value->type=='office') {
                    $office = DB::table('offices')->where('id',$value->type_id)->first();
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

    public static function startRoute() {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        if(Auth::user()->role == 'admin' || Auth::user()->role == 'driver') {
            DB::table('users')->where('id', Auth::user()->id)->update(['route_status'=>'started']);
            $routes = DB::table('routes_priority')->where('driver_id', Auth::user()->id)->orderBy("priority","asc")->get();
            foreach($routes as $key => $value) {
                if($value->type=='patient') {
                    //Notifications::send_push(DB::table('orders')->where('id', $value->order_id)->first()->user_id,"A2BRx","Your orders are out for delivery today");
                }
            }
            return response()->json([
                'message' => 'OK'
            ], 200);
        }
    }

    public static function update_status(Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        if(Auth::user()->role == 'admin' || Auth::user()->role == 'driver') {
            if(!empty($request->input('order_id')) && !empty($request->input('status_id'))) {
                $order_id = explode("_",$request->input('order_id'))[0];
                $order = DB::table('orders')->where('id', $order_id)->first();
                DB::table('orders')->where('id', $order_id)->update(['statuse_id'=>$request->input('status_id')]);
                if($request->input('status_id')==8 || $request->input('status_id')==9) {
                    $pharmacy=DB::table('pharmacys')->where('pharmacys.id',$order->pharmacy_id)->first();
                    $pharmacy_plan=DB::table('plans')->where('plans.id',$pharmacy->plan_id)->first();
                    $patient=DB::table('users')->where('users.id',$order->user_id)->first();
                    $pharmacy_areas=DB::table('pharmacy_areas')->where('pharmacy_id',$order->pharmacy_id)->where('type',1)->pluck('area_id')->toArray();
                    $pharmacy_areas2=DB::table('pharmacy_areas')->where('pharmacy_id',$order->pharmacy_id)->where('type',2)->pluck('area_id')->toArray();
                    $pharmacy_areas3=DB::table('pharmacy_areas')->where('pharmacy_id',$order->pharmacy_id)->where('type',3)->pluck('area_id')->toArray();
                    $zip_tariff=DB::table('area')->whereIn('area.id',$pharmacy_areas)->whereRaw('ST_CONTAINS(polygon, POINT('.$patient->location.'))')->select("area.id")->first();
                    $zip_tariff2=DB::table('area')->whereIn('area.id',$pharmacy_areas2)->whereRaw('ST_CONTAINS(polygon, POINT('.$patient->location.'))')->select("area.id")->first();
                    $zip_tariff3=DB::table('area')->whereIn('area.id',$pharmacy_areas3)->whereRaw('ST_CONTAINS(polygon, POINT('.$patient->location.'))')->select("area.id")->first();
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
                        }
                        if($order->delivery_time_id==2) {
                            $tariff_res = (floatval($tariff)+floatval($tariff_same_day)+floatval($order->extra_charge_driver));
                        }
                        if($order->delivery_time_id==3) {
                            $tariff_res = (floatval($tariff)+floatval($tariff_asap)+floatval($order->extra_charge_driver));
                        }
                        if($order->delivery_time_id==4) {
                            $tariff_res = (floatval($tariff)+floatval($tariff_after_hours)+floatval($order->extra_charge_driver));
                        }
                        if($order->fridge==1) {
                            $tariff_res+= floatval($tariff_fridge);
                        }
                    } else {
                        $tariff_res = floatval($tariff);
                    }
                    DB::table('orders')->where('id', $order_id)->update(['finish'=>date('Y-m-d H:i:s'),'drop_off_photo'=>NULL,'signature_photo'=>NULL,'signature_type'=>NULL,'tariff'=>$tariff_res]);
                    $routes = DB::table('routes_priority')->where('order_id',$order_id)->where('driver_id',Auth::user()->id)->where('type','patient')->get();
                    if(!empty($routes) && isset($routes[0])){
                        $routeNeed = $routes[0];
                        foreach($routes as $routes_priority) {
                            DB::table('routes_priority_logs')->insert(["id"=>$routes_priority->id,"driver_id"=>$routes_priority->driver_id,"order_id"=>$routes_priority->order_id,"type"=>$routes_priority->type,"type_id"=>$routes_priority->type_id,"type_pay"=>$routes_priority->type_pay,"pay_value"=>$routes_priority->pay_value]);
                            if($routes_priority->type_pay==1) {
                                DB::table('payouts_driver')->insert(['driver_id'=>Auth::user()->id,'order_id'=>$order_id,'amount'=>$routes_priority->pay_value]);
                            }
                            if($routes_priority->type_pay==2) {
                                DB::table('payouts_driver')->insert(['driver_id'=>Auth::user()->id,'order_id'=>$order_id,'amount'=>$routes_priority->pay_value]);
                            }
                            if($routes_priority->type_pay==3) {
                                DB::table('payouts_driver')->insert(['driver_id'=>Auth::user()->id,'order_id'=>$order_id,'amount'=>$routes_priority->pay_value*intval($order->count_bags)]);
                            }
                        }
                        if(empty(Auth::user()->pharmacy_id)) {
                            $next_office = DB::table('routes_priority')->where('driver_id',Auth::user()->id)->where('type','office')->first();
                            if(!empty($next_office)) {
                                DB::table('routes_priority')->insert(['driver_id'=>Auth::user()->id,'order_id'=>$order_id,'type'=>'office','type_id'=>$next_office->type_id,'type_pay'=>$next_office->type_pay,'pay_value'=>$next_office->pay_value,'priority'=>$next_office->priority]);
                            } else {
                                $last_route = DB::table('routes_priority')->where('driver_id',Auth::user()->id)->max('priority');
                                if(!empty($routeNeed)) {
                                    DB::table('routes_priority')->insert(['driver_id'=>Auth::user()->id,'order_id'=>$order_id,'type'=>'office','type_id'=>1,'type_pay'=>$routeNeed->type_pay,'pay_value'=>$routeNeed->pay_value,'priority'=>(intval($last_route)+1)]);
                                }
                            }
                        } else {
                            $next_office = DB::table('routes_priority')->where('driver_id',Auth::user()->id)->where('type','pharmacy')->where('type_id',Auth::user()->pharmacy_id)->first();
                            if(!empty($next_office)) {
                                DB::table('routes_priority')->insert(['driver_id'=>Auth::user()->id,'order_id'=>$order_id,'type'=>'pharmacy','type_id'=>$next_office->type_id,'type_pay'=>$next_office->type_pay,'pay_value'=>$next_office->pay_value,'priority'=>$next_office->priority]);
                            } else {
                                $last_route = DB::table('routes_priority')->where('driver_id',Auth::user()->id)->max('priority');
                                if(!empty($routeNeed)) {
                                    DB::table('routes_priority')->insert(['driver_id'=>Auth::user()->id,'order_id'=>$order_id,'type'=>'pharmacy','type_id'=>Auth::user()->pharmacy_id,'type_pay'=>$routeNeed->type_pay,'pay_value'=>$routeNeed->pay_value,'priority'=>(intval($last_route)+1)]);
                                }
                            } 
                        }
                        if(!empty($routeNeed)) {
                            $driver= DB::table('locations')->whereIn('id', [DB::raw("select max(`id`) from locations GROUP BY user_id")])->where('user_id',Auth::user()->id)->first();
                            if(!empty($driver)){
                                DB::table('packages_transitions')->insert(['order_id'=>$order_id,'user_id'=>$routeNeed->type_id,'driver_id'=>Auth::user()->id,'target'=>"faild",'location'=>$driver->location]);
                            }
                            $count_faild = DB::table('packages_transitions')->where('order_id',$order_id)->where('user_id',$routeNeed->type_id)->where('target',"faild")->count();
                            if($count_faild>=3){
                                DB::table('orders')->where('id', $order_id)->update(['not_delivered'=>1]);
                            }
                        }                 
                        DB::table('routes_priority')->where('order_id',$order_id)->where('driver_id',Auth::user()->id)->where('type','patient')->delete();
                    }
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

    public static function orderPatientQr(Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        if(Auth::user()->role == 'admin' || Auth::user()->role == 'driver') {
            if(!empty($request->input('order_id'))) {
                $code = explode("_",$request->input('order_id'));
                $order_id = $code[0];
                if(isset($code[1])) {
                    $bag = $code[1];
                } else {
                    $bag = 1;
                }
                $order = DB::table('orders')->where('id',$order_id)->where('driver_id', Auth::user()->id)->first();
                if(isset($order)) {
                    $count_added = DB::table('packages_transitions')->where('order_id',$order->id)->where('user_id',$order->user_id)->where('driver_id',Auth::user()->id)->count();
                    if(1+$count_added>$order->count_bags) {
                        return response()->json([
                            'order_id' => $order->id,
                            'count_bags' => $order->count_bags,
                            'count_scanned' => $count_added
                        ], 200);
                    } else if(1+$count_added<=$order->count_bags) {
                        if(DB::table('packages_transitions')->where('order_id',$order->id)->where('user_id',$order->user_id)->where('driver_id',Auth::user()->id)->where('bag',$bag)->count()>0) {
                            return response()->json([
                                'message' => 'This bag has already been scanned, please scan the next bag for this order.',
                                'errors' => 'Not Found'
                            ], 404);
                        } else {
                            DB::table('packages_transitions')->insert(['order_id'=>$order->id,'user_id'=>$order->user_id,'driver_id'=>Auth::user()->id,'bag'=>$bag]);
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

    public static function orderPatientQr2(Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        if(Auth::user()->role == 'admin' || Auth::user()->role == 'driver') {
            if(!empty($request->input('order_id')) && !empty($request->input('code'))) {
                $code = explode("_",$request->input('code'));
                $order_id_code = $code[0];
                $order_id = $request->input('order_id');
                if(isset($code[1])) {
                    $bag = $code[1];
                } else {
                    $bag = 1;
                }
                $order = DB::table('orders')->where('id',$order_id)->where('driver_id', Auth::user()->id)->first();
                if(isset($order) && $order_id==$order_id_code) {
                    $count_added = DB::table('packages_transitions')->where('order_id',$order->id)->where('user_id',$order->user_id)->where('driver_id',Auth::user()->id)->count();
                    if(1+$count_added>$order->count_bags) {
                        return response()->json([
                            'order_id' => $order->id,
                            'count_bags' => $order->count_bags,
                            'count_scanned' => $count_added
                        ], 200);
                    } else if(1+$count_added<=$order->count_bags) {
                        if(DB::table('packages_transitions')->where('order_id',$order->id)->where('user_id',$order->user_id)->where('driver_id',Auth::user()->id)->where('bag',$bag)->count()>0) {
                            return response()->json([
                                'message' => 'This bag has already been scanned, please scan the next bag for this order.',
                                'errors' => 'Not Found'
                            ], 404);
                        } else {
                            DB::table('packages_transitions')->insert(['order_id'=>$order->id,'user_id'=>$order->user_id,'driver_id'=>Auth::user()->id,'bag'=>$bag]);
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

    public static function get_unread_mess() {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        $count_noread=Auth::user()->get_unread_mess();
        return response()->json([
            'count_noread' => $count_noread
        ], 200);
    }

    public static function get_payouts_driver() {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        if(Auth::user()->role == 'admin' || Auth::user()->role == 'driver') {
            $amount = round(DB::table('payouts_driver')->where('driver_id', Auth::user()->id)->where('withdraw',0)->sum('amount'),1);
            return response()->json([
                'amount' => $amount
            ], 200);
        } else {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        }
    }

    public static function payCopay($order_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        $order = DB::table('orders')->where('id',$order_id)->first();
        if(!empty($order)) {
            if(!in_array($order->statuse_id,[1,4,5,8,9,10]) && (Auth::user()->role == 'user') && !in_array($order->statuse_copay,[1,3,4,6]) && $order->copay>0) {
                $payment_account = DB::table('payment_accounts')->where('user_id',$order->user_id)->first();
                if(!empty($payment_account) && $payment_account->type=="card" && !empty($payment_account->payment_profile_id)){
                    $client = new \Square\SquareClient([
                        'accessToken' => config('app.SQUARE_ACCESS_TOKEN'),
                        'environment' => config('app.SQUARE_ENVIRONMENT'),
                    ]);
                    $amount = floatval($order->copay);
                    $amount_money = new \Square\Models\Money();
                    $amount_money->setAmount(($amount*100));
                    $amount_money->setCurrency('USD');
                    $unid = uniqid("",true).rand(0,100);
                    $body = new \Square\Models\CreatePaymentRequest(
                        $payment_account->payment_profile_id,
                        $unid,
                        $amount_money
                    );
                    $body->setAutocomplete(true);
                    $body->setCustomerId($payment_account->profile_id);
                    $body->setLocationId(config('app.SQUARE_LOCATION_ID'));
                    $body->setNote('Pay Copay #'.$order->id);
                    $api_response = $client->getPaymentsApi()->createPayment($body);
                    if ($api_response->isSuccess()) {
                        $result = $api_response->getResult();
                        $payment_status = $result->getPayment()->getStatus();
                        if($payment_status=="COMPLETED" || $payment_status=="APPROVED") {
                            DB::table('orders')->where('id',$order_id)->update(['statuse_copay'=>3]);
                            DB::table('payments')->insert(['order_id'=>$order_id,'transaction_id'=>$result->getPayment()->getId(),'type'=>'copay']);
                            return response()->json([
                                'message' => 'OK'
                            ], 200);
                        } else {
                            return response()->json([
                                'message' => 'Payment not completed!',
                                'errors' => 'Error'
                            ], 400);
                        }
                    } else {
                        $error = json_encode($api_response->getErrors());
                        return response()->json([
                            'message' => 'Transaction Failed: '.$error,
                            'errors' => 'Error'
                        ], 400);
                    }
                } else {
                    return response()->json([
                        'message' => 'Card not added',
                        'errors' => 'Forbidden'
                    ], 403);
                }
            } else {
                return response()->json([
                    'message' => 'You cannot open this page',
                    'errors' => 'Forbidden'
                ], 403);
            }
        } else {
            return response()->json([
                'message' => 'Order not found',
                'errors' => 'Not Found'
            ], 404);
        }
    }

    public static function PayedCashCopay($order_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        $order = DB::table('orders')->where('id',$order_id)->first();
        if(!empty($order)) {
            if(!in_array($order->statuse_id,[1,4,5,8,9,10]) && (Auth::user()->role == 'driver') && $order->driver_id==Auth::user()->id && !in_array($order->statuse_copay,[1,3,4,6]) && $order->copay>0) {
                $cash_log = DB::table('cash_log')->where("order_id",$order_id)->where("driver_id",Auth::user()->id)->first();
                if(!empty($cash_log)) {
                    DB::table('cash_log')->where('id',$cash_log->id)->update(["copay"=>$order->copay]);
                } else {
                    DB::table('cash_log')->insert(["order_id"=>$order_id,"driver_id"=>Auth::user()->id,"copay"=>$order->copay]);
                }
                DB::table('orders')->where('id',$order_id)->update(['statuse_copay'=>4]);
                if(empty(Auth::user()->pharmacy_id)) {
                    $next_office = DB::table('routes_priority')->where('driver_id',Auth::user()->id)->where('type','office')->first();
                    if(!empty($next_office)) {
                        DB::table('routes_priority')->insert(['driver_id'=>Auth::user()->id,'order_id'=>$order_id,'type'=>'office','type_id'=>$next_office->type_id,'type_pay'=>$next_office->type_pay,'pay_value'=>$next_office->pay_value,'priority'=>$next_office->priority]);
                    } else {
                        $last_route = DB::table('routes_priority')->where('driver_id',Auth::user()->id)->max('priority');
                        $route0=DB::table('routes_priority')->where('order_id',$order_id)->where('driver_id',Auth::user()->id)->where('type','patient')->first();
                        if(!empty($route0)) {
                            DB::table('routes_priority')->insert(['driver_id'=>Auth::user()->id,'order_id'=>$order_id,'type'=>'office','type_id'=>1,'type_pay'=>$route0->type_pay,'pay_value'=>$route0->pay_value,'priority'=>(intval($last_route)+1)]);
                        }
                    }
                } else {
                    $next_office = DB::table('routes_priority')->where('driver_id',Auth::user()->id)->where('type','pharmacy')->where('type_id',Auth::user()->pharmacy_id)->first();
                    if(!empty($next_office)) {
                        DB::table('routes_priority')->insert(['driver_id'=>Auth::user()->id,'order_id'=>$order_id,'type'=>'pharmacy','type_id'=>$next_office->type_id,'type_pay'=>$next_office->type_pay,'pay_value'=>$next_office->pay_value,'priority'=>$next_office->priority]);
                    } else {
                        $last_route = DB::table('routes_priority')->where('driver_id',Auth::user()->id)->max('priority');
                        $route0=DB::table('routes_priority')->where('order_id',$order_id)->where('driver_id',Auth::user()->id)->where('type','patient')->first();
                        if(!empty($route0)) {
                            DB::table('routes_priority')->insert(['driver_id'=>Auth::user()->id,'order_id'=>$order_id,'type'=>'pharmacy','type_id'=>Auth::user()->pharmacy_id,'type_pay'=>$route0->type_pay,'pay_value'=>$route0->pay_value,'priority'=>(intval($last_route)+1)]);
                        }
                    }
                }
                return response()->json([
                    'message' => 'OK'
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Order can not be payed',
                    'errors' => 'Forbidden'
                ], 403);
            }
        } else {
            return response()->json([
                'message' => 'Order not found',
                'errors' => 'Not Found'
            ], 404);
        }
    }
    
    public static function notPayedCashCopay($order_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        $order = DB::table('orders')->where('id',$order_id)->first();
        if(!empty($order)) {
            if(!in_array($order->statuse_id,[1,4,5,8,9,10]) && (Auth::user()->role == 'driver') && $order->driver_id==Auth::user()->id && !in_array($order->statuse_copay,[1,3,4,6]) && $order->copay>0) {
                DB::table('orders')->where('id',$order_id)->update(['statuse_copay'=>5]);
                return response()->json([
                    'message' => 'OK'
                ], 200);
            } else {
                return response()->json([
                    'message' => 'You cannot open this page',
                    'errors' => 'Forbidden'
                ], 403);
            }
        } else {
            return response()->json([
                'message' => 'Order not found',
                'errors' => 'Not Found'
            ], 404);
        }
    }

    public static function profileFamily_members() {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        $family_members = DB::table('family_members')->where('user_id', Auth::user()->id)->get();
        return response()->json([
            'family_members' => $family_members
        ], 200);
    }

    public static function profileFamily_membersAddHandler(Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        $validator = Validator::make($request->all(), [
            'family_type' => 'required|max:155',
            'family_name' => 'required|max:155',
            'family_phone' => 'required|max:155',
            'family_address' => 'required|max:155',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Something is wrong with this field!',
                'errors' => 'Bad Request'
            ], 400);
        }
        $data = json_decode(file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?address=".urlencode($request->input('family_address'))."&key=".config('app.googlemaps_apikey')));
        if(empty($data->results)) {
            return response()->json([
                'message' => 'Something is wrong with this field!',
                'errors' => 'Bad Request'
            ], 400);
        }
        $location = $data->results[0]->geometry->location->lat.','.$data->results[0]->geometry->location->lng;
        DB::table('family_members')->insert(['user_id'=>Auth::user()->id,'family_type' => $request->input('family_type'),'family_name' => $request->input('family_name'),'family_phone' => $request->input('family_phone'),'family_address' => $request->input('family_address'),'location'=>$location]);
        return response()->json([
            'message' => 'OK'
        ], 200);
    }

    public static function profileFamily_membersRemoveHandler(Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        $family_member = DB::table('family_members')->where('id',$request->input('family_member_id'))->first();
        if(!empty($family_member)) {
            if($family_member->user_id==Auth::user()->id) {
                DB::table('family_members')->where('id',$request->input('family_member_id'))->delete();
                return response()->json([
                    'message' => 'OK'
                ], 200);
            } else {
                return response()->json([
                    'message' => 'You cannot open this page',
                    'errors' => 'Forbidden'
                ], 403);
            }
        } else {
            return response()->json([
                'message' => 'Family member not found',
                'errors' => 'Not Found'
            ], 404);
        }
    }

    public static function customer_notesAddHandler($order_id, Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        $order = DB::table('orders')->where('orders.id',$order_id)->first();
        if((Auth::user()->role == 'user' && Auth::user()->id==$order->user_id)) {
            $validator = Validator::make($request->all(), [
                'note' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Something is wrong with this field!',
                    'errors' => 'Bad Request'
                ], 400);
            }
            DB::table('notes')->insert(["order_id"=>$order_id,"user_id"=>Auth::user()->id,"type"=>"2",'note'=>addslashes($request->input('note'))]);
            return response()->json([
                'message' => 'OK'
            ], 200);
        } else {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        }   
    }

    public static function ratingHandler($order_id, Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        $order = DB::table('orders')->where('orders.id',$order_id)->first();
        if((Auth::user()->role == 'user' && Auth::user()->id==$order->user_id) && $order->statuse_id==4 && empty($order->rating)) {
            $validator = Validator::make($request->all(), [
                'rating' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Something is wrong with this field!',
                    'errors' => 'Bad Request'
                ], 400);
            }
            $rating = intval($request->input('rating'));
            if($rating<0) {
                $rating = 1;
            }
            if($rating>5) {
                $rating = 5;
            }
            DB::table('orders')->where('id',$order_id)->update(['rating'=>$rating]);
            return response()->json([
                'message' => 'OK'
            ], 200);
        } else {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        }   
    }

    public static function ordersDelivery_times() {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        $delivery_times = DB::table('delivery_times')->get();
        return response()->json([
            'delivery_times' => $delivery_times
        ], 200);
    }

    public static function ordersDelivery_timesHandler(Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        if(Auth::user()->role == 'admin' || Auth::user()->role == 'user') {
            if(!empty($request->input('order_id')) && !empty($request->input('time_id'))) {
                $order = DB::table('orders')->where('id',$request->input('order_id'))->first();
                if(!empty($order) && ($order->user_id==Auth::user()->id)) {
                    DB::table('orders')->where('id',$request->input('order_id'))->update(["delivery_time_id"=>intval($request->input('time_id'))]);
                    return response()->json([
                        'message' => 'OK'
                    ], 200);
                } else {
                    return response()->json([
                        'message' => 'Order not found',
                        'errors' => 'Not Found'
                    ], 404);
                }
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

    public static function news() {
        if(Auth::user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        } else {
            $token = Auth::user()->token();
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();
        }
        //$news = [["created"=>"2022-01-07 11:00:05","type"=>"news","link"=>"","title"=>"News from A2B Rx","text"=>"Welcome to our updated app"]];
        if(!empty(Auth::user()->pharmacy_id)) {
            $news = DB::table('news_patient')->where("pharmacy_id",Auth::user()->pharmacy_id)->orderBy('id','desc')->get();
            foreach($news as $key=>$new) {
                $news[$key]->created = date('m/d/Y g:i A', strtotime($new->created));
            }
        } else {
            $news = NULL;
        }
        return response()->json([
            'news' => $news
        ], 200);
    }

    static function next_patient_push($driver_id,$next_route){
        $distance=0;
        $duration=0;
        $driver_loc= DB::table('locations')->whereIn('id', [DB::raw("select max(`id`) from locations GROUP BY user_id")])->where('user_id',$driver_id)->first()->location;
        $driver = DB::table('users')->where('id',$driver_id)->first();
        if($driver->transport=='2') {
            $transport = "bicycle";
        } else {
            $transport = "car";
        }
        $patient0 = DB::table('users')->where('id',$next_route->type_id)->first();
        $patient=[];
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
        $last_loc=str_replace(' ','',$patient['location']);
        $access_token = Redis::get('here_access_token');
        if(empty($access_token)) {
            $access_token = self::update_here_access_token();
        }
        $options = array(
            'http' => array(
                'method'  => 'GET',
                'ignore_errors' => true,
                'header' => 'Authorization: Bearer '.$access_token
            )
        );
        $context  = stream_context_create($options);
        $directions = json_decode(file_get_contents("https://router.hereapi.com/v8/routes?transportMode=$transport&origin=$driver_loc&destination=$last_loc&return=summary", false, $context));
        if(!empty($directions->routes)) {
            foreach($directions->routes[0]->sections as $legs) {
                $distance=$distance+$legs->summary->length;
                $duration=$duration+$legs->summary->duration;
            }
        }
        $duration = ceil($duration / 60);
        $distance=round($distance*0.000621371192,1);
        DB::table('orders')->where('id',$next_route->order_id)->update(["eta"=>ceil($duration / 60)]);
        $min = $duration % 60;
        $duration = floor($duration / 60);
        $duration= $duration." hours ".$min." minutes";
        $distance= $distance.' miles';
        Notifications::send_push($next_route->type_id,"A2BRx","Your delivery is next. \nPlease track your order #".$next_route->order_id." via our app. ETA: $duration \nThank you for using our service.");
        return true;
    }

    public static function update_here_access_token() {
        $nonce     = bin2hex(random_bytes(4));
        $timestamp = time();
        $string='POST&'.urlencode('https://account.api.here.com/oauth2/token').'&'.urlencode('grant_type=client_credentials&'.'oauth_consumer_key='.config('app.hereAccessId').'&oauth_nonce='.$nonce.'&oauth_signature_method=HMAC-SHA256&oauth_timestamp='.$timestamp.'&oauth_version=1.0');
        $signature = urlencode(base64_encode(hash_hmac('sha256', $string, config('app.hereAccessSecret').'&',TRUE)));
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://account.api.here.com/oauth2/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded',
                'Authorization: OAuth oauth_consumer_key="'.config('app.hereAccessId').'",oauth_nonce="'.$nonce.'",oauth_signature_method="HMAC-SHA256",oauth_timestamp="'.$timestamp.'",oauth_version="1.0",oauth_signature="'.$signature.'"'
            ),
        )); 
        $response = json_decode(curl_exec($curl));
        curl_close($curl);
        if(isset($response->access_token) && isset($response->expires_in)) {
            Redis::set('here_access_token',$response->access_token, 'EX', intval($response->expires_in));
            return $response->access_token;
        } else {
            dd('Error when update access token HERE!');
        }
    } 
}