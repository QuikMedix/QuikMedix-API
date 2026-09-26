<?php

namespace App\Http\Controllers\Auth;

use App\Actions\GeocodeAddress;
use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\User;
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
        // Limits mirror the columns: users.phone is varchar(20), pharmacys.phone matches it.
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'email' => ['required', 'string', 'email', 'max:191', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'pharmacyName' => ['required', 'string', 'max:255'],
            'pharmacyPhone' => ['required', 'string', 'max:20'],
            'pharmacyLogo' => ['nullable', 'file', 'mimes:jpeg,jpg,png', 'max:6000'],
            'pharmacyEmail' => ['required', 'string', 'email', 'max:255'],
            'pharmacyWebsite' => ['nullable', 'string', 'max:255'],
            'pharmacyAddress' => ['required', 'string', 'max:255'],
        ], [
            'phone.unique' => 'An account with this phone number already exists. Try logging in instead.',
            'email.unique' => 'An account with this e-mail already exists. Try logging in instead.',
            'password.confirmed' => 'The passwords do not match.',
            'pharmacyLogo.mimes' => 'The logo must be a JPG or PNG image.',
            'pharmacyLogo.max' => 'The logo must be smaller than 6 MB.',
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
        // Resolve the address before writing anything; a failure is shown on the address field.
        $location = app(GeocodeAddress::class)->handle($data['pharmacyAddress'], 'pharmacyAddress');

        return DB::transaction(fn () => $this->createAdminWithPharmacy($data, $location));
    }

    private function createAdminWithPharmacy(array $data, string $location): User
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
            $name_f = \App\Support\PublicUpload::name($request->file('pharmacyLogo'));
            $file->move(public_path() . '/images/pharmacys/',$name_f);
            $src = '/images/pharmacys/'.$name_f;
        } else {
            $src = NULL;
        }
        $pharmacy_id = DB::table('pharmacys')->insertGetId(['name' => $data['pharmacyName'],'email' => $data['pharmacyEmail'],'phone' => $data['pharmacyPhone'],'address' => $data['pharmacyAddress'],'location' => $location,'logo'=>$src,'site'=>$data['pharmacyWebsite'] ?? null,'admin_id' => $user->id, 'ref_id' => $ref_id,'ip'=>\Request::ip()]);
        $user->pharmacy_id = $pharmacy_id;
        $user->save();
        return $user;
    }
}
