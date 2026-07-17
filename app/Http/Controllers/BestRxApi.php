<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Twilio\Rest\Client;
use App\Notifications;
use Illuminate\Support\Facades\Hash;
use DB;
use Response;
use Barryvdh\DomPDF\Facade\Pdf;

class BestRxApi extends Controller
{
    public function getpharmacyinfo(Request $request) {
        if($pharmacy_auth = $this->checkAuth($request)){
            $address = explode(',', (string) $pharmacy_auth->address);
            $address_line_1 = $address[0] ?? '';
            $city = '-';
            $state = 'NY';
            $zip_code = '0';
            if(isset($address[1])) {
                $city=trim($address[1]);
            }
            if(isset($address[2])) {
                $state_zip = preg_split('/\s+/', trim($address[2]));
                $state = $state_zip[0] ?? 'NY';
                $zip_code = $state_zip[1] ?? '0';
            }
            return response()->json([
                'store' => [
                    'bestrx_pharmacy_id' => $pharmacy_auth->bestrx_pharmacy_id,
                    'delivery_provider_acct_no' => $pharmacy_auth->id,
                    'name' => $pharmacy_auth->name,
                    'address' => [
                        'address_line_1'=>$address_line_1,
                        'address_line_2'=>NULL,
                        'city'=>$city,
                        'state'=>$state,
                        'zip_code'=>$zip_code
                    ],
                    'phone'=>$pharmacy_auth->phone,
                    'fax'=>$pharmacy_auth->phone,
                    'email'=>$pharmacy_auth->email,
                    'ncpdp'=>$pharmacy_auth->ncpdp,
                    'npi'=>$pharmacy_auth->npi,
                    'dea'=>$pharmacy_auth->dea
                ]
            ], 200);
        } else {
            return response()->json([
                'messages'=>[
                    'message_code'=>'DIS0000',
                    'message' => 'Your API key is wrong or pharmacy is blocked',
                    'message_type' => 2
                ],
                'request_is_valid'=>FALSE
            ], 401);
        }
    }

    public function orderAdd(Request $request) {
        if($pharmacy_auth = $this->checkAuth($request)){
            $response = json_decode($request->getContent());
            if(isset($response->bestrx_order_id) && isset($response->amount_to_collect) && isset($response->refrigeration_needed) && isset($response->signature_required) && isset($response->store) && isset($response->recipient) && isset($response->prescriptions)) {
                if(isset($response->requested_delivery_date)) {
                    $delivery_date = date('Y-m-d', strtotime($response->requested_delivery_date));
                } else {
                    $delivery_date = date('Y-m-d', strtotime(' +1 day'));
                }
                if(date('Y-m-d')==$delivery_date){
                    $delivery_time_id = 2;
                } else {
                    $delivery_time_id = 1;
                }
                $count_bags=1;
                if(isset($response->refrigeration_needed) && !empty($response->refrigeration_needed)){
                    $fridge=1;
                } else {
                    $fridge=0;
                }
                if(isset($response->signature_required) && !empty($response->signature_required)){
                    $signature=1;
                } else {
                    $signature=0;
                }
                if(isset($response->delivery_notes)) {
                    $special_instructions = $response->delivery_notes;
                } else {
                    $special_instructions = "";
                }
                if(empty($response->recipient->mobile_phone)) {
                    return response()->json([
                        'messages'=>[
                            'message_code'=>'DIS0001',
                            'message' => 'Your request is invalid (Patient mobile phone number is required)',
                            'message_type' => 2
                        ],
                        'request_is_valid'=>FALSE
                    ], 400);
                }
                $patient_phone=$response->recipient->mobile_phone;
                if(preg_match( '/^(\d{3})(\d{3})(\d{4})$/', $patient_phone,  $matches)) {
                    $patient_phone = '('.$matches[1].') '.$matches[2].'-'.$matches[3];
                    $patient = DB::table("users")->where("phone",$patient_phone)->where("pharmacy_id",$pharmacy_auth->id)->first();
                    if(empty($patient)) {
                        $password = bin2hex(openssl_random_pseudo_bytes(4));
                        $address = $response->recipient->address->address_line_1.' '.$response->recipient->address->address_line_2.', '.$response->recipient->address->city.', '.$response->recipient->address->state.' '.$response->recipient->address->zip_code.', USA';
                        $data = json_decode(file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?address=".urlencode($address)."&key=".config('app.googlemaps_apikey')));
                        $address = $data->results[0]->formatted_address;
                        $location = $data->results[0]->geometry->location->lat.','.$data->results[0]->geometry->location->lng;
                        $home_phone=NULL;
                        if(preg_match( '/^(\d{3})(\d{3})(\d{4})$/', $response->recipient->home_phone,  $matches)) {
                            $home_phone = '('.$matches[1].') '.$matches[2].'-'.$matches[3];
                        }
                        if(empty($response->recipient->email)){
                            $email = 'patients'.DB::table('users')->max('id').'@cp.a2brx.com';
                        } else {
                            $email = $response->recipient->email;
                        }
                        DB::table('users')->insert(['isactive' => '1','name' => $response->recipient->first_name,'last_name' => $response->recipient->last_name,'email' => $email,'phone' => $patient_phone,'home_phone'=> $home_phone,'address' => $address,'location' => $location,'password' => Hash::make($password),'zip' => $response->recipient->address->zip_code,'pharmacy_id' => $pharmacy_auth->id]);
                        try {
                            $twilio = new Client(config('app.twilio_sid'), config('app.twilio_auth_token'));
                            if($twilio->messages->create("+1".str_replace(" ","",str_replace("-","",str_replace(")","",str_replace("(","",$patient_phone)))), ["body" => "Hello, ".$response->recipient->first_name.". Account was created. \nLogin: ".$patient_phone."\nPassword: ".$password."\nDownload the app https://a2brx.com/app \nBest regards, A2B Rx Inc.", "from" => config('app.twilio_from_phone')])){
                                
                            }
                        } catch (\Throwable $th) {
                            //throw $th;
                        }
                        $patient = DB::table("users")->where("phone",$patient_phone)->where("pharmacy_id",$pharmacy_auth->id)->first();
                    }
                    $id_max = DB::table('orders')->max('id')+1;
                    $copay=$response->amount_to_collect;
                    $data=[];
                    foreach($response->prescriptions as $rx) {
                        $data[]=["order_id"=>$id_max,"rx_id"=>str_replace(" ",'',$rx->rx_number).'-'.$rx->partial_fill_seqno,"rx_date"=>$rx->fill_date,"rx_count"=>1];
                    }
                    if(isset($response->otc_items)){
                        foreach($response->otc_items as $rx) {
                            $data[]=["order_id"=>$id_max,"rx_id"=>str_replace(" ",'',$rx->upc),"rx_date"=>date("Y-m-d"),"rx_count"=>$rx->quantity];
                        }
                    }
                    $statuse_copay = (empty($copay))?'1':'2';
                    DB::table('rxs')->insert($data);
                    DB::table('orders')->insert(['id'=>$id_max,'pharmacy_id' => $pharmacy_auth->id, 'bestrx_order_id'=>$response->bestrx_order_id, 'user_id' => $patient->id, 'count_bags'=>$count_bags, 'copay' => $copay, 'statuse_copay' => $statuse_copay, 'delivery_method_id' => 4, 'special_instructions' => $special_instructions, 'type_driver' => 1, 'delivery_time_id' => $delivery_time_id, 'delivery_date'=>$delivery_date, 'fridge' => $fridge, 'signature'=>$signature]);
                    if($patient->primary_address==3){
                        if(!empty($patient->apartment3)){
                            $user_address = $patient->address3.' Apt '.$patient->apartment3;
                        } else {
                            $user_address = $patient->address3;
                        }
                    } elseif($patient->primary_address==2){
                        if(!empty($patient->apartment2)){
                            $user_address = $patient->address2.' Apt '.$patient->apartment2;
                        } else {
                            $user_address = $patient->address2;
                        }
                    } else {
                        if(!empty($patient->apartment)){
                            $user_address = $patient->address.' Apt '.$patient->apartment;
                        } else {
                            $user_address = $patient->address;
                        }
                    }
                    Notifications::send_push($request->input('user'),"A2BRx","A2B Rx is greeting you! Your order #$id_max is ready to be shipped to this address: $user_address If the address is wrong, please contact us phone number (855) 657-9595 or your pharmacy ASAP");
                    $dt = new \DateTime();
                    $dt->setTimeZone(new \DateTimeZone('UTC'));
                    return response()->json([
                        'data' => [
                            'bestrx_order_id'=>$response->bestrx_order_id,
                            'bestrx_unique_order_id'=>$response->bestrx_unique_order_id,
                            'provider_order_id'=>strval($id_max),
                            'tracking_id'=>strval($id_max),
                            'bestrx_pharmacy_id'=>$pharmacy_auth->bestrx_pharmacy_id,
                            'order_status'=>[
                                'date'=>$dt->format('Y-m-d\TH:i:s.\0\0\0\0\0\0\0\Z'),
                                'status_code'=>1,
                                'status_code_description'=>'Ready for pick up',
                                'status_notes'=>''
                            ],
                            'label_url'=>url('/').'/api/bestrx/orders/ticket/print?order_id='.$id_max,
                            'label_image'=>'',
                            'label_file_type'=>4
                        ],
                        'request_is_valid'=>TRUE,
                        'messages'=>[]
                    ], 200);
                } else {
                    return response()->json([
                        'messages'=>[
                            'message_code'=>'DIS0001',
                            'message' => 'Your request is invalid (Patient mobile phone number is invalid)',
                            'message_type' => 2
                        ],
                        'request_is_valid'=>FALSE
                    ], 400);
                }
            } else {
                return response()->json([
                    'messages'=>[
                        'message_code'=>'DIS0001',
                        'message' => 'Your request is invalid (Any mandatory field is missing)',
                        'message_type' => 2
                    ],
                    'request_is_valid'=>FALSE
                ], 400);
            }
        } else {
            return response()->json([
                'messages'=>[
                    'message_code'=>'DIS0000',
                    'message' => 'Your API key is wrong or pharmacy is blocked',
                    'message_type' => 2
                ],
                'request_is_valid'=>FALSE
            ], 401);
        }
    }

    public function ordersTicket(Request $request) {
        $order_id = $request->query('order_id');
        $order=DB::table('orders')->where('id',$order_id)->whereNotNull('bestrx_order_id')->first();
        if(!empty($order)){
            $rxs = DB::table('rxs')->where('order_id',$order->id)->get();
            foreach($rxs as $key0=>$rx) {
                if(empty($rx->rx_id)) {
                    $rxs[$key0]->rx_id = 'null';
                }
            }
            $order->rxs=$rxs;
            $pharmacy = DB::table('pharmacys')->where('id',$order->pharmacy_id)->first();
            $patient = DB::table('users')->where('id',$order->user_id)->first();
            $wish = DB::table('wishes')->join('wishes_category',"wishes.category_id","=","wishes_category.id")->where("wishes_category.status",1)->inRandomOrder()->first();
            $pharmacy_id = NULL;
            $res_arr = ['order'=>$order,'pharmacy'=>$pharmacy,'patient'=>$patient,'wish'=>$wish];
            return view('orders.ticket_pdf',$res_arr);
        } else {
            return response()->json([
                'message' => 'Your request is invalid',
                'errors' => 'Bad Request'
            ], 400);
        }
    }

    public function ordersTicketPrintPdf(Request $request) {
        $order_id = $request->query('order_id');
        $order=DB::table('orders')->where('id',$order_id)->whereNotNull('bestrx_order_id')->first();
        if(!empty($order)){
            $rxs = DB::table('rxs')->where('order_id',$order->id)->get();
            foreach($rxs as $key0=>$rx) {
                if(empty($rx->rx_id)) {
                    $rxs[$key0]->rx_id = 'null';
                }
            }
            $order->rxs=$rxs;
            $pharmacy = DB::table('pharmacys')->where('id',$order->pharmacy_id)->first();
            $patient = DB::table('users')->where('id',$order->user_id)->first();
            $wish = DB::table('wishes')->join('wishes_category',"wishes.category_id","=","wishes_category.id")->where("wishes_category.status",1)->inRandomOrder()->first();
            $pharmacy_id = NULL;
            $res_arr = ['order'=>$order,'pharmacy'=>$pharmacy,'patient'=>$patient,'wish'=>$wish];
            $filename='ticket_'.$order_id.'.pdf';
            $pdf = PDF::loadView('orders.ticket_pdf',$res_arr);
            return $pdf->stream($filename);
        } else {
            return response()->json([
                'message' => 'Your request is invalid',
                'errors' => 'Bad Request'
            ], 400);
        }
    }

    public function validateAddress(Request $request) {
        if($pharmacy_auth = $this->checkAuth($request)){
            $response = json_decode($request->getContent());
            if(isset($response->store) && isset($response->address_to_validate)) {
                if(!empty($response->address_to_validate->address_line_1)){
                    return response()->json([
                        'data' => [
                            'suggested_address'=>[
                                [
                                    'address_line_1'=>$response->address_to_validate->address_line_1,
                                    'address_line_2'=>$response->address_to_validate->address_line_2,
                                    'city'=>$response->address_to_validate->city,
                                    'state'=>$response->address_to_validate->state,
                                    'zip_code'=>$response->address_to_validate->zip_code
                                ]
                            ]
                        ],
                        'messages'=>[],
                        'request_is_valid'=>TRUE
                    ], 200);
                } else {
                    return response()->json([
                        'data' => [
                            'suggested_address'=>[]
                        ],
                        'messages'=>[
                            [
                                'message'=>'No matching addresses found.',
                                'message_type'=>2
                            ]
                        ],
                        'request_is_valid'=>TRUE
                    ], 200);
                }
            } else {
                return response()->json([
                    'messages'=>[
                        'message_code'=>'DIS0001',
                        'message' => 'Your request is invalid (Any mandatory field is missing)',
                        'message_type' => 2
                    ],
                    'request_is_valid'=>FALSE
                ], 400);
            }
        } else {
            return response()->json([
                'messages'=>[
                    'message_code'=>'DIS0000',
                    'message' => 'Your API key is wrong or pharmacy is blocked',
                    'message_type' => 2
                ],
                'request_is_valid'=>FALSE
            ], 401);
        }
    }

    public function orderDelete(Request $request) {
        if($pharmacy_auth = $this->checkAuth($request)){
            $response = json_decode($request->getContent());
            if(isset($response->bestrx_order_id)) {
                $order=DB::table('orders')->where('pharmacy_id',$pharmacy_auth->id)->where('bestrx_order_id',$response->bestrx_order_id)->first();
                if(!empty($order)){
                    if($order->statuse_id==1) {
                        DB::table('orders')->where('bestrx_order_id',$response->bestrx_order_id)->update(["statuse_id"=>5]);
                        DB::table('routes_priority')->where('order_id',$order->id)->delete();
                        return response()->json([
                            'data'=>[
                                'bestrx_order_id'=>$response->bestrx_order_id,
                                'provider_order_id'=>strval($order->id),
                                'tracking_id'=>strval($order->id),
                                'status'=>TRUE,
                                'status_reason'=>'Success',
                            ],
                            'request_is_valid'=>TRUE,
                            'messages'=>[]
                        ], 200);
                    } else {
                        return response()->json([
                            'messages'=>[
                                'message_code'=>'DIS0001',
                                'message' => 'Your request is invalid',
                                'message_type' => 2
                            ],
                            'request_is_valid'=>FALSE
                        ], 400);
                    }
                } else {
                    return response()->json([
                        'messages'=>[
                            'message_code'=>'DIS0003',
                            'message' => 'Order not fount',
                            'message_type' => 2
                        ],
                        'request_is_valid'=>FALSE
                    ], 404);
                }
            } else {
                return response()->json([
                    'messages'=>[
                        'message_code'=>'DIS0001',
                        'message' => 'Your request is invalid (Any mandatory field is missing)',
                        'message_type' => 2
                    ],
                    'request_is_valid'=>FALSE
                ], 400);
            }
        } else {
            return response()->json([
                'messages'=>[
                    'message_code'=>'DIS0000',
                    'message' => 'Your API key is wrong or pharmacy is blocked',
                    'message_type' => 2
                ],
                'request_is_valid'=>FALSE
            ], 401);
        }
    }

    private function checkAuth($request,$pharmacy_id=NULL) {
        if($request->hasHeader('Authorization')) {
            $header = explode(" ",$request->header('Authorization'));
            $header = base64_decode(end($header));
            if(strpos($header,":")!==FALSE) {
                $header = explode(":",$header);
                $apiKey = $header[0];
                $apiSecret = $header[1];
                if(empty($pharmacy_id)) {
                    $response = json_decode($request->getContent());
                    if(isset($response->store) && isset($response->store->bestrx_pharmacy_id)) {
                        $pharmacy_id = $response->store->bestrx_pharmacy_id;
                    }
                }
                if(!empty($pharmacy_id)){
                    $pharmacy = DB::table('pharmacys')->where('isactive',1)->where('isblocked',0)->where('bestrx_pharmacy_id',$pharmacy_id)->where('bestrxKey',$apiKey)->where('bestrxSecret',$apiSecret)->first();
                } else {
                    $pharmacy = DB::table('pharmacys')->where('isactive',1)->where('isblocked',0)->where('bestrxKey',$apiKey)->where('bestrxSecret',$apiSecret)->first();
                }
                return $pharmacy;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function NotFound() {
        return response()->json([
            'messages'=>[
                'message_code'=>'DIS0000',
                'message' => 'Sorry, url or method not found',
                'message_type' => 2
            ],
            'request_is_valid'=>FALSE
        ], 400);
    }

}
