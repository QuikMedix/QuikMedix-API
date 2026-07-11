<?php
  
namespace App\Http\Middleware;
  
use Closure;
use Illuminate\Http\Request;
use Session;
use DB;
  
class Check2FA
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Session::has('user_2fa') && (auth()->user()->role=='admin' || auth()->user()->role=='dispadmin')) {
            $white_list = DB::table('white_list_ip')->where('ip',$request->ip())->first();
            if(!empty($white_list)) {
                Session::put('user_2fa', auth()->user()->id);
                return $next($request);
            }
            return redirect()->route('2fa.index');
        }
  
        return $next($request);
    }
}