<?php

namespace App\Http\Controllers;

use App\Actions\LogUserChanges;
use App\Http\Controllers\Concerns\RendersLegacyPages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Support\AccessMessages;

/**
 * The signed-in user's own profile. Formerly actions of LexaAdmin, moved as they were.
 */
class ProfileController extends Controller
{
    use RendersLegacyPages;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('2fa');
        // Kept from LexaAdmin, whose pages load whole result sets at once.
        ini_set('memory_limit', '-1');
    }

    public function profile() {
        $pharmacys = DB::table('pharmacys')->get();
        $user = Auth::user();
        $admin_areas = DB::table('admin_areas')->get();
        $res_view = view('settings.user_edit',['user'=> $user,'admin_areas'=>$admin_areas,'pharmacys'=>$pharmacys,'title'=>'Profile','br1'=>'Profile','alert'=>'']);
        return $this->page(request(), $res_view);
    }

    public function profileHandler(Request $request) {
        $user_id = Auth::user()->id;
        if($user_id) {
            if($request->input('save')>0) {
                if($request->hasFile('image')) {
                    $file = $request->file('image');
                    $file->move(public_path() . '/images/users/',\App\Support\PublicUpload::name($request->file('image')));
                    $src = '/images/users/'.\App\Support\PublicUpload::name($request->file('image'));
                } else {
                    $src = Auth::user()->image;
                }
                if($request->input('remove_photo')>0) {
                    $src = '/images/users/default-user-image.png';
                }
                if($request->hasFile('driving_license_img')) {
                    $file = $request->file('driving_license_img');
                    $file->move(public_path() . '/images/driving_license/',\App\Support\PublicUpload::name($request->file('driving_license_img')));
                    $driving_license_img = '/images/driving_license/'.\App\Support\PublicUpload::name($request->file('driving_license_img'));
                } else {
                    $driving_license_img = Auth::user()->driving_license_img;
                }
                if($request->input('password')!='') {
                    if($request->input('password')==$request->input('password2')) {
                        if(!empty($request->input('zip'))) {
                            $address = $request->input('address').' '.$request->input('zip');
                        } else {
                            $address = $request->input('address');
                        }
                        $data = app(\App\Actions\GeocodeAddress::class)->response($address);
                        $address = $data->results[0]->formatted_address;
                        $location = $data->results[0]->geometry->location->lat.','.$data->results[0]->geometry->location->lng;
                        app(LogUserChanges::class)->handle($request,$address,$user_id);
                        DB::table('users')->where('id', $user_id)->update(['name' => $request->input('name'),'last_name' => $request->input('last_name'),'email' => $request->input('email'),'phone' => $request->input('phone'),'image' => $src,'address' => $address,'location' => $location,'password' => Hash::make($request->input('password')),'zip' => $request->input('zip'),'apartment' => $request->input('apartment'),'driving_license' => $request->input('driving_license'),'driving_license_img' => $driving_license_img,'identification_cards' => $request->input('identification_cards'),'car_info' => $request->input('car_info'),'payment_card' => $request->input('payment_card'),'pharmacy_id' => $request->input('pharmacy')]);
                        DB::table('action_log')->insert(['type'=>'change password','user_id'=>$user_id,'action_user_id'=>Auth::user()->id]);
                    } else {
                        $pharmacys = DB::table('pharmacys')->get();
                        $admin_areas = DB::table('admin_areas')->get();
                        $user = Auth::user();
                        return view('settings.user_edit',['user'=> $user,'admin_areas'=>$admin_areas,'pharmacys'=>$pharmacys,'title'=>'Profile','br1'=>'Profile','alert'=>'Passwords must match']);
                    }
                } else {
                    if(!empty($request->input('zip'))) {
                        $address = $request->input('address').' '.$request->input('zip');
                    } else {
                        $address = $request->input('address');
                    }
                    $data = app(\App\Actions\GeocodeAddress::class)->response($address);
                    $address = $data->results[0]->formatted_address;
                    $location = $data->results[0]->geometry->location->lat.','.$data->results[0]->geometry->location->lng;
                    app(LogUserChanges::class)->handle($request,$address,$user_id);
                    DB::table('users')->where('id', $user_id)->update(['name' => $request->input('name'),'last_name' => $request->input('last_name'),'email' => $request->input('email'),'phone' => $request->input('phone'),'image' => $src,'address' => $address,'location' => $location,'zip' => $request->input('zip'),'apartment' => $request->input('apartment'),'driving_license' => $request->input('driving_license'),'driving_license_img' => $driving_license_img,'identification_cards' => $request->input('identification_cards'),'car_info' => $request->input('car_info'),'payment_card' => $request->input('payment_card'),'pharmacy_id' => $request->input('pharmacy')]);
                }
                return redirect("profile");
            }
        } else {
            return abort(403, AccessMessages::NO_PERMISSION);
        }
    }
}
