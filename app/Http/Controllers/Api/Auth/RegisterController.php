<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\RegisterFormRequest;
use App\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use DB;

class RegisterController extends Controller
{
    public function __invoke(RegisterFormRequest $request)
    {
        if(empty($request->input('email'))){
            $email = 'patients'.DB::table('users')->max('id').'@cp.a2brx.com';
        } else {
            $email = $request->input('email');
        }
        $user = User::create([
            'name' => $request->input('name'),
            'last_name' => $request->input('last_name'),
            'phone' => $request->input('phone'),
            'address' => $request->input('address'),
            'apartment' => $request->input('apartment'),
            'zip' => $request->input('zip'),
            'birth_date' => $request->input('birth_date'),
            'email' => $email,
            'password' => Hash::make($request->input('password')),
            'isactive'=>1
        ]);

        return response()->json([
            'message' => 'You were successfully registered. Use your phone and password to sign in.'
        ], 200);
    }
}