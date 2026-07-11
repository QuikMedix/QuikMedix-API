<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Twilio\Rest\Client;
use App\Notifications;
use Illuminate\Support\Facades\Hash;
use DB;

class PioneerrxApi extends Controller
{
    public function IsAuthenticated(Request $request) {
        $pharmacy_auth = $this->checkAuth($request);
        if(!is_array($pharmacy_auth)){
            return response()->json([
                TRUE
            ], 200);
        } else {
            return response()->json([
                $pharmacy_auth["error"]
            ], $pharmacy_auth["code"]);
        }
        
    }

    private function checkAuth($request) {
        if($request->hasHeader('prx-api-key') && $request->hasHeader('prx-timestamp') && $request->hasHeader('prx-signature') && !empty($request->header('prx-api-key'))) {
            $apiKey = $request->header('prx-api-key');
            $timestamp = $request->header('prx-timestamp');
            $signature = $request->header('prx-signature');
            $pharmacy = DB::table('pharmacys')->where('isactive',1)->where('isblocked',0)->where('pioneerrxKey',$apiKey)->first();
            if(!empty($pharmacy)) {
                $apiSecret = $pharmacy->pioneerrxSecret;
                $my_signature = base64_encode(hash("sha512",mb_convert_encoding($timestamp.$apiSecret, "UTF-16LE", "UTF-8")));
                if($my_signature===$signature) {
                    return $pharmacy;
                } else {
                    return ["error"=>'Unauthorized',"code"=>401];
                }
            } else {
                return ["error"=>'Unauthorized',"code"=>401];
            }
        } else {
            return ["error"=>'Invalid Header(s)',"code"=>400];
        }
    }

    public function NotFound() {
        return response()->json([
            'Not Found'
        ], 404);
    }

}
