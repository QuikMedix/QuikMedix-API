<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'pharmacyName' => ['required', 'string', 'max:255'],
            'pharmacyPhone' => ['required', 'string', 'max:255'],
            'pharmacyLogo' => ['mimes:jpeg,jpg,png', 'max:6000'],
            'pharmacyEmail' => ['required', 'string', 'email', 'max:255'],
            'pharmacyWebsite' => ['required', 'string', 'max:255'],
            'pharmacyAddress' => ['required', 'string', 'max:255'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function create(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => "medic",
            'isactive' => 1
        ]);
        $user->isactive = 1;
        $user->save();
        $data0 = json_decode(file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?address=".urlencode($data['pharmacyAddress'])."&key=".config('app.googlemaps_apikey')));
        $location = $data0->results[0]->geometry->location->lat.','.$data0->results[0]->geometry->location->lng;
        if(isset($data['ref_id'])) {
            if(!empty(User::find(base64_decode($data['ref_id'])))) {
                $ref_id = base64_decode($data['ref_id']);
            } else {
                $ref_id = NULL;
            }
        } else {
            $ref_id = NULL;
        }
        $request = request();
        if($request->hasFile('pharmacyLogo')) {
            $file = $request->file('pharmacyLogo');
            $name_f = date('mdHis').$request->file('pharmacyLogo')->getClientOriginalName();
            $file->move(public_path() . '/images/pharmacys/',$name_f);
            $src = '/images/pharmacys/'.$name_f;
        } else {
            $src = NULL;
        }
        $pharmacy_id = DB::table('pharmacys')->insertGetId(['name' => $data['pharmacyName'],'email' => $data['pharmacyEmail'],'phone' => $data['pharmacyPhone'],'address' => $data['pharmacyAddress'],'location' => $location,'logo'=>$src,'site'=>$data['pharmacyWebsite'],'admin_id' => $user->id, 'ref_id' => $ref_id,'ip'=>\Request::ip()]);
        $user->pharmacy_id = $pharmacy_id;
        $user->save();
        return $user;
    }
}
