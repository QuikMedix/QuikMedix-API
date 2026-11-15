<?php

namespace App\Http\Controllers\Api;

use App\Actions\LogUserChanges;
use App\Http\Controllers\Controller;
use App\Models\ActionLog;
use App\Models\FamilyMember;
use App\Support\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Twilio\Rest\Client;

/**
 * The signed-in person's own account in the mobile apps: profile, device, family members, the patient home screen and news.
 */
class ProfileController extends Controller
{
    public function profile() {
        $user = DB::table('users')->where('id', Auth::user()->id)->first();
        unset($user->password);
        unset($user->remember_token);
        unset($user->device_token);
        return response()->json([
            'user' => $user
        ], 200);
    }

    public function banner() {
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

    public function profileHandler(Request $request) {
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
                $file->move(public_path() . '/images/users/',\App\Support\PublicUpload::name($request->file('image')));
                $src = '/images/users/'.\App\Support\PublicUpload::name($request->file('image'));
                $user->image = $src;
            }
            if($request->hasFile('driving_license_img')) {
                $file = $request->file('driving_license_img');
                $file->move(public_path() . '/images/driving_license/',\App\Support\PublicUpload::name($request->file('driving_license_img')));
                $driving_license_img = '/images/driving_license/'.\App\Support\PublicUpload::name($request->file('driving_license_img'));
                $user->driving_license_img = $driving_license_img;
            }
            if($request->input('password')!='') {
                if($request->input('password')==$request->input('password2')) {
                    $user->password = Hash::make($request->input('password'));
                    ActionLog::query()->insert(['type'=>'change password','user_id'=>$user_id,'action_user_id'=>Auth::user()->id]);
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
                $data = app(\App\Actions\GeocodeAddress::class)->response($address);
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
            app(LogUserChanges::class)->handleSubmitted($request, $address, $user_id);
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

    public function patient_home() {
        if(Auth::user()->hasRole('user')) {
            $wishs = DB::table('wishes')->join('wishes_category',"wishes.category_id","=","wishes_category.id")->where("wishes_category.status",1)->pluck('wishes.text')->toArray();
            $head_text = $wishs ? $wishs[array_rand($wishs)] : '';
            $pharmacy = DB::table('pharmacys')->where("id",Auth::user()->pharmacy_id)->first();
            $last_order = DB::table('orders')->join('users', 'orders.user_id', '=', 'users.id')->join('statuses', 'orders.statuse_id', '=', 'statuses.id')->leftJoin('statuses_copay', 'orders.statuse_copay', '=', 'statuses_copay.id')->join('delivery_methods', 'orders.delivery_method_id', '=', 'delivery_methods.id')->join('delivery_times', 'orders.delivery_time_id', '=', 'delivery_times.id')->leftJoin('medicine', 'orders.id', '=', 'medicine.order_id')->join('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id')->select('orders.id', 'orders.created', 'orders.finish', 'orders.statuse_id', 'orders.signature', 'orders.fridge', 'orders.special_instructions', 'orders.user_id',  'orders.copay', 'orders.driver_id', 'orders.count_bags', 'orders.drop_off_photo', 'orders.signature_photo', 'users.name as username', 'users.last_name as last_name', 'delivery_methods.name as delivery_method', 'delivery_times.name as delivery_time', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end as useraddress'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end as userapartment'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end as userzip'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end as userlocation'), 'users.phone as userphone', DB::raw('sum(medicine.count) as count'), 'pharmacys.name as pharmacyname', 'pharmacys.address as pharmacyaddress','pharmacys.phone as pharmacyphone', 'pharmacys.location as pharmacylocation', 'statuses.name as statusename','statuses.color as statusecolor','orders.statuse_copay', 'statuses_copay.name as statuse_copay_name')->where('orders.user_id',Auth::user()->id)->groupBy('orders.id', 'orders.statuse_id', 'orders.created', 'orders.finish', 'orders.driver_id', 'orders.count_bags', 'orders.signature', 'orders.fridge', 'orders.special_instructions', 'orders.copay', 'orders.drop_off_photo','orders.signature_photo', 'orders.user_id', 'users.name', 'users.last_name', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end'), 'users.phone','pharmacys.name', 'delivery_methods.name', 'delivery_times.name', 'pharmacys.location', 'pharmacys.address','pharmacys.phone', 'statuses.name','statuses.color','orders.statuse_copay', 'statuses_copay.name')->orderBy('orders.id','desc')->first();
            if(!empty($last_order)) {
                if($last_order->statuse_id==3) {
                    if($last_order->driver_id>0) {
                        $locations = DB::table('locations')->whereIn('id', [DB::raw("select max(`id`) from locations GROUP BY user_id")])->where('user_id',$last_order->driver_id)->first();
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
                return response()->json([
                    'head_text' => $head_text,
                    'pharmacy_name' => $pharmacy->name,
                    'last_order' => [
                        'id'=>$last_order->id,
                        'status'=>OrderStatus::label($last_order->statuse_id, $last_order->statusename),
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

    public function updateDevice(Request $request) {
        $device_token = $request->input('device_token');
        if(empty($device_token)) {
            return response()->json([
                'message' => 'Something is wrong with this field!',
                'errors' => 'Bad Request'
            ], 400);
        }
        // Save the token first: FCM pushes only need this column.
        DB::table('users')->where('id',Auth::user()->id)->update(['device_token'=>$device_token]);

        // Optional legacy Twilio Notify binding. Never let it block saving the token.
        $isAndroid = Auth::user()->os==1;
        $isDriver = Auth::user()->hasRole('driver');
        $serviceSid = config('app.twilio_notify'.($isDriver ? 'Driver' : 'Client').($isAndroid ? '' : 'IOS').'ServiceSid');
        if(config('app.twilio_sid') && config('app.twilio_auth_token') && $serviceSid) {
            try {
                $twilio = new Client(config('app.twilio_sid'), config('app.twilio_auth_token'));
                $service = $twilio->notify->v1->services($serviceSid);
                foreach ($service->bindings->read(["identity"=>Auth::user()->id], 20) as $record) {
                    $service->bindings($record->sid)->delete();
                }
                $service->bindings->create(Auth::user()->id, "fcm", $device_token);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Twilio Notify binding failed', ['error' => $e->getMessage()]);
            }
        }
        return response()->json([
            'message' => 'Device Token was updated'
        ], 200);
    }

    public function profileFamily_members() {
        $family_members = FamilyMember::query()->where('user_id', Auth::user()->id)->get();
        return response()->json([
            'family_members' => $family_members
        ], 200);
    }

    public function profileFamily_membersAddHandler(Request $request) {
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
        $data = app(\App\Actions\GeocodeAddress::class)->response($request->input('family_address'));
        if(empty($data->results)) {
            return response()->json([
                'message' => 'Something is wrong with this field!',
                'errors' => 'Bad Request'
            ], 400);
        }
        $location = $data->results[0]->geometry->location->lat.','.$data->results[0]->geometry->location->lng;
        FamilyMember::query()->insert(['user_id'=>Auth::user()->id,'family_type' => $request->input('family_type'),'family_name' => $request->input('family_name'),'family_phone' => $request->input('family_phone'),'family_address' => $request->input('family_address'),'location'=>$location]);
        return response()->json([
            'message' => 'OK'
        ], 200);
    }

    public function profileFamily_membersRemoveHandler(Request $request) {
        $family_member = FamilyMember::query()->where('id',$request->input('family_member_id'))->first();
        if(!empty($family_member)) {
            if($family_member->user_id==Auth::user()->id) {
                FamilyMember::query()->where('id',$request->input('family_member_id'))->delete();
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

    public function news() {
        //$news = [["created"=>"2022-01-07 11:00:05","type"=>"news","link"=>"","title"=>"News from QuikMedix","text"=>"Welcome to our updated app"]];
        if(!empty(Auth::user()->pharmacy_id)) {
            $news = DB::table('news_patient')->where("pharmacy_id",Auth::user()->pharmacy_id)->orderBy('id','desc')->get();
            foreach($news as $key=>$new) {
                $news[$key]->created = date('m/d/Y g:i A', strtotime($new->created ?? ''));
            }
        } else {
            $news = NULL;
        }
        return response()->json([
            'news' => $news
        ], 200);
    }
}
