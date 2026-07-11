<?php
  
namespace App\Http\Controllers;
  
use Illuminate\Http\Request;
use Session;
use DB;
  
class TwoFAController extends Controller
{
    /**
     * Write code on Method
     *
     * @return response()
     */
    public function index()
    {
        return view('auth.2fa');
    }
  
    /**
     * Write code on Method
     *
     * @return response()
     */
    public function store(Request $request)
    {
        $request->validate([
            'code'=>'required',
        ]);
  
        $find = DB::table('user_codes')->where('user_id', auth()->user()->id)
                        ->where('code', $request->code)
                        ->where('created_at', '>=', now()->subMinutes(15))
                        ->first();
  
        if (!is_null($find)) {
            Session::put('user_2fa', auth()->user()->id);
            return redirect()->route('home');
        }
  
        return back()->with('error', 'You entered wrong code.');
    }
    /**
     * Write code on Method
     *
     * @return response()
     */
    public function resend()
    {
        $find = DB::table('user_codes')->where('user_id', auth()->user()->id)->where('created_at', '>=', now()->subMinutes(2))->first();
        if (is_null($find)) {
            auth()->user()->generateCode();
            return back()->with('success', 'We sent you code on your mobile number.');
        }
        return back()->with('error', "It hasn't been 2 minutes since the last message was sent, please try again later.");
    }
}