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

class MerchantApi extends Controller
{
    public function getpharmacyinfo(Request $request,$pharmacy_id) {
        if($pharmacy_auth = $this->checkAuth($request,$pharmacy_id)){
            return response()->json([
                'pharmacy' => [
                    'store_id' => $pharmacy_auth->npi,
                    'service_details'=>[
                        [
                            'service_level_code'=>'1',
                            'service_name'=> 'Regular',
                            'description'=>'A2BRx Next day delivery',
                            'service_windows' => [
                                [
                                    "date"=>date("Y-m-d"),
                                    "service_level"=>"1",
                                    "number"=>1,
                                    "cutoff"=>date("Y-m-d\TH:i:s-00:00", strtotime(date("Y-m-d").' 00:00:00')),
                                    "start"=>date("Y-m-d\TH:i:s-00:00", strtotime(date("Y-m-d").' 00:00:00')),
                                    "end"=>date("Y-m-d\TH:i:s-00:00", strtotime(date("Y-m-d").' 23:59:59'))
                                ]
                            ],
                            'offset'=>1,
                            'isShipping'=>false
                        ],
                        [
                            'service_level_code'=>'2',
                            'service_name'=> 'SameDay',
                            'description'=>'A2BRx Same day delivery',
                            'service_windows' => [
                                [
                                    "date"=>date("Y-m-d"),
                                    "service_level"=>"2",
                                    "number"=>1,
                                    "cutoff"=>date("Y-m-d\TH:i:s-00:00", strtotime(date("Y-m-d").' 00:00:00')),
                                    "start"=>date("Y-m-d\TH:i:s-00:00", strtotime(date("Y-m-d").' 00:00:00')),
                                    "end"=>date("Y-m-d\TH:i:s-00:00", strtotime(date("Y-m-d").' 23:59:59'))
                                ]
                            ],
                            'offset'=>2,
                            'isShipping'=>false
                        ]
                    ],
                    'shipping_services' => [],
                    'shipping_defaults' => [
                        'default_weigth_unit'=>'pounds',
                        'default_weight_value'=>1,
                        'default_dimensions_unit'=>'inches',
                        'default_dimensions_length'=>'5',
                        'default_dimensions_width'=>'5',
                        'default_dimensions_height'=>'5',
                        'default_carrier'=>'A2B'
                    ],
                    'package_details'=>[
                        [
                            'id'=>1,
                            'size'=>'small',
                            'description'=>'3 to 5 pounds'
                        ],
                        [
                            'id'=>2,
                            'size'=>'medium',
                            'description'=>'6 to 10 pounds'
                        ]
                    ],
                    'delivery_options'=>[
                        [
                            'id'=>1,
                            'type'=>'instruction',
                            'description'=>'Next day delivery',
                            'service_name'=>'Regular',
                            'isDefault'=>true
                        ],
                        [
                            'id'=>2,
                            'type'=>'instruction',
                            'description'=>'Same day delivery',
                            'service_name'=>'SameDay',
                            'isDefault'=>false
                        ],
                        [
                            'id'=>3,
                            'type'=>'storage',
                            'description'=>'Store in refrigerated mode',
                            'service_name'=>'Regular',
                            'isDefault'=>false
                        ],
                        [
                            'id'=>4,
                            'type'=>'signature',
                            'description'=>'Online Signature Required',
                            'service_name'=>'Regular',
                            'isDefault'=>false
                        ],
                    ]
                ]
            ], 200);
        } else {
            return response()->json([
                'message' => 'Your API key is wrong or pharmacy is blocked',
                'errors' => 'Unauthorized'
            ], 401);
        }
        
    }

    public function orderAdd(Request $request) {
        if($pharmacy_auth = $this->checkAuth($request)){
            $response = json_decode($request->getContent());
            if(isset($response->service_level) && isset($response->patient) && isset($response->pharmacy) && isset($response->recipient)) {
                if(isset($response->service_date) && ($response->service_level=="1" || $response->service_level=="3")) {
                    $delivery_date = date('Y-m-d', strtotime($response->service_date));
                } else {
                    $delivery_date = date('Y-m-d', strtotime(' +1 day'));
                }
                if(isset($response->instruction) && !empty($response->instruction)){
                    if($response->instruction=='Same day delivery'){
                        $delivery_time_id = 2;
                    } else {
                        $delivery_time_id = 1;
                    }
                } else {
                    $delivery_time_id = 1;
                }
                if(isset($response->no_of_packages) && !empty($response->no_of_packages)){
                    $count_bags=intval($response->no_of_packages);
                } else {
                    $count_bags=1;
                }
                if(isset($response->storage) && !empty($response->storage)){
                    $fridge=1;
                } else {
                    $fridge=0;
                }
                if(isset($response->signature) && !empty($response->signature)){
                    $signature=1;
                } else {
                    $signature=0;
                }
                if(isset($response->delivery_notes)) {
                    $special_instructions = $response->delivery_notes;
                } else {
                    $special_instructions = "";
                }
                if(empty($response->patient->mobile_phone_number)) {
                    return response()->json([
                        'message' => 'Your request is invalid (Patient mobile phone number is required)',
                        'errors' => 'Bad Request'
                    ], 400);
                }
                $patient_phone=$response->patient->mobile_phone_number;
                if(preg_match( '/^(\d{3})(\d{3})(\d{4})$/', $patient_phone,  $matches)) {
                    $patient_phone = '('.$matches[1].') '.$matches[2].'-'.$matches[3];
                    $patient = DB::table("users")->where("phone",$patient_phone)->where("pharmacy_id",$pharmacy_auth->id)->first();
                    if(empty($patient)) {
                        $password = bin2hex(openssl_random_pseudo_bytes(4));
                        $address = $response->recipient->address->street_1.' '.$response->recipient->address->street_2.', '.$response->recipient->address->city.', '.$response->recipient->address->state.' '.$response->recipient->address->zipcode.', USA';
                        $data = json_decode(file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?address=".urlencode($address)."&key=".config('app.googlemaps_apikey')));
                        $address = $data->results[0]->formatted_address;
                        $location = $data->results[0]->geometry->location->lat.','.$data->results[0]->geometry->location->lng;
                        $home_phone=NULL;
                        if(preg_match( '/^(\d{3})(\d{3})(\d{4})$/', $response->patient->home_phone_number,  $matches)) {
                            $home_phone = '('.$matches[1].') '.$matches[2].'-'.$matches[3];
                        }
                        $email = 'patients'.DB::table('users')->max('id').'@cp.a2brx.com';
                        DB::table('users')->insert(['isactive' => '1','name' => $response->patient->first_name,'last_name' => $response->patient->last_name,'email' => $email,'phone' => $patient_phone,'home_phone'=> $home_phone,'address' => $address,'location' => $location,'password' => Hash::make($password),'zip' => $response->recipient->address->zipcode,'pharmacy_id' => $pharmacy_auth->id]);
                        try {
                            $twilio = new Client(config('app.twilio_sid'), config('app.twilio_auth_token'));
                            if($twilio->messages->create("+1".str_replace(" ","",str_replace("-","",str_replace(")","",str_replace("(","",$patient_phone)))), ["body" => "Hello, ".$response->patient->first_name.". Account was created. \nLogin: ".$patient_phone."\nPassword: ".$password."\nDownload the app https://a2brx.com/app \nBest regards, A2B Rx Inc.", "from" => config('app.twilio_from_phone')])){
                                
                            }
                        } catch (\Throwable $th) {
                            //throw $th;
                        }
                        $patient = DB::table("users")->where("phone",$patient_phone)->where("pharmacy_id",$pharmacy_auth->id)->first();
                    }
                    $id_max = DB::table('orders')->max('id')+1;
                    $copay=0;
                    $fridge=0;
                    $data=[];
                    foreach($response->prescriptions as $rx) {
                        if(isset($rx->patient_pay)){
                            $copay=$copay+$rx->patient_pay;
                        }
                        if(isset($rx->is_refrigeration_needed)){
                            if($rx->is_refrigeration_needed==TRUE) {
                                $fridge=1;
                            }
                        }
                        $data[]=["order_id"=>$id_max,"rx_id"=>str_replace(" ",'',$rx->number).'-'.$rx->fill_number,"rx_date"=>$rx->date_of_service,"rx_count"=>$rx->fill_quantity];
                    }
                    $statuse_copay = (empty($copay))?'1':'2';
                    DB::table('rxs')->insert($data);
                    DB::table('orders')->insert(['id'=>$id_max,'pharmacy_id' => $pharmacy_auth->id, 'user_id' => $patient->id, 'count_bags'=>$count_bags, 'copay' => $copay, 'statuse_copay' => $statuse_copay, 'delivery_method_id' => 4, 'special_instructions' => $special_instructions, 'type_driver' => 1, 'delivery_time_id' => $delivery_time_id, 'delivery_date'=>$delivery_date, 'fridge' => $fridge, 'signature'=>$signature, 'merchantOrder'=>'1']);
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
                    return response()->json([
                        'order' => [
                            'id'=>$id_max,
                            'status'=>'new',
                            'pickup'=>NULL,
                            'delivery'=>NULL,
                            'return'=>NULL,
                            'shipping'=>NULL,
                            'labels'=>[
                                ['label_pdf'=>url('/').'/api/merchant/orders/ticket/print?order_id='.$id_max]
                            ],
                            'payment_collected'=>null,
                            'proof_of_delivery'=>null
                        ]
                    ], 200);
                } else {
                    return response()->json([
                        'message' => 'Your request is invalid (Patient mobile phone number is invalid)',
                        'errors' => 'Bad Request'
                    ], 400);
                }
            } else {
                return response()->json([
                    'message' => 'Your request is invalid (Any mandatory field is missing)',
                    'errors' => 'Bad Request'
                ], 400);
            }
        } else {
            return response()->json([
                'message' => 'Your API key is wrong or pharmacy is blocked',
                'errors' => 'Unauthorized'
            ], 401);
        }
    }

    public function orderRxAdd(Request $request,$order_id){
        if($pharmacy_auth = $this->checkAuth($request)){
            $order=DB::table('orders')->where('id',$order_id)->where('pharmacy_id',$pharmacy_auth->id)->where('merchantOrder','1')->first();
            if(!empty($order)){
                $copay=$order->copay;
                $fridge=$order->fridge;
                $data=[];
                foreach($response->prescriptions as $rx) {
                    if(isset($rx->patient_pay)){
                        $copay=$copay+$rx->patient_pay;
                    }
                    if(isset($rx->is_refrigeration_needed)){
                        if($rx->is_refrigeration_needed==TRUE) {
                            $fridge=1;
                        }
                    }
                    $data[]=["order_id"=>$order_id,"rx_id"=>str_replace(" ",'',$rx->number).'-'.$rx->fill_number,"rx_date"=>$rx->date_of_service,"rx_count"=>$rx->fill_quantity];
                }
                $statuse_copay = (empty($copay))?'1':'2';
                DB::table('rxs')->insert($data);
                DB::table('orders')->where('id',$order_id)->update(['copay'=>$copay,'statuse_copay'=>$statuse_copay,'fridge'=>$fridge]);
                return response()->json([
                    'order' => [
                        'id'=>$order_id,
                        'status'=>'new',
                        'pickup'=>NULL,
                        'delivery'=>NULL,
                        'return'=>NULL,
                        'shipping'=>NULL,
                        'labels'=>[
                            ['label_pdf'=>url('/').'/api/merchant/orders/ticket/print?order_id='.$order_id]
                        ],
                        'payment_collected'=>null,
                        'proof_of_delivery'=>null
                    ]
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Your request is invalid',
                    'errors' => 'Bad Request'
                ], 400);
            }
        } else {
            return response()->json([
                'message' => 'Your API key is wrong or pharmacy is blocked',
                'errors' => 'Unauthorized'
            ], 401);
        }
    }

    public function orderRxDelete(Request $request,$order_id,$rx_number) {
        if($pharmacy_auth = $this->checkAuth($request)){
            $order=DB::table('orders')->where('id',$order_id)->where('pharmacy_id',$pharmacy_auth->id)->where('merchantOrder','1')->first();
            if(!empty($order)){
                DB::table('rxs')->where('order_id',$order_id)->where("rx_id",$rx_number)->delete();
                return response()->json(NULL, 200);
            } else {
                return response()->json([
                    'message' => 'Your request is invalid',
                    'errors' => 'Bad Request'
                ], 400);
            }
        } else {
            return response()->json([
                'message' => 'Your API key is wrong or pharmacy is blocked',
                'errors' => 'Unauthorized'
            ], 401);
        }
    }

    public function orderDelete(Request $request,$order_id) {
        if($pharmacy_auth = $this->checkAuth($request)){
            $order=DB::table('orders')->where('id',$order_id)->where('pharmacy_id',$pharmacy_auth->id)->where('merchantOrder','1')->first();
            if(!empty($order)){
                if($order->statuse_id==1) {
                    DB::table('orders')->where('id',$order_id)->update(["statuse_id"=>5]);
                    DB::table('routes_priority')->where('order_id',$order_id)->delete();
                    return response()->json(NULL, 204);
                } else {
                    return response()->json([
                        'message' => 'Your request is invalid',
                        'errors' => 'Bad Request'
                    ], 400);
                }
            } else {
                return response()->json([
                    'message' => 'Your request is invalid',
                    'errors' => 'Bad Request'
                ], 400);
            }
        } else {
            return response()->json([
                'message' => 'Your API key is wrong or pharmacy is blocked',
                'errors' => 'Unauthorized'
            ], 401);
        }
    }

    public function orderStatus(Request $request) {
        if($pharmacy_auth = $this->checkAuth($request)){
            $response = json_decode($request->getContent());
            if(isset($response->orders)) {
                $orders=[];
                foreach($response->orders as $order_id){
                    $order=DB::table('orders')->where('id',$order_id)->where('pharmacy_id',$pharmacy_auth->id)->where('merchantOrder','1')->first();
                    if(!empty($order)){
                        $record=[];
                        $record["id"]=$order_id;
                        $record["external_reference_number"]=NULL;
                        if($order->statuse_id==1){
                            $record["status"]="new";
                        } else if($order->statuse_id==2 || $order->statuse_id==3 || $order->statuse_id==6 || $order->statuse_id==7){
                            $record["status"]="picked_up";
                        } else if($order->statuse_id==4){
                            $record["status"]="delivered";
                        } else if($order->statuse_id==10){
                            $record["status"]="returned_to_pharmacy";
                        } else if($order->statuse_id==5 || $order->statuse_id==9){
                            $record["status"]="cancelled";
                        } else if($order->statuse_id==8){
                            $record["status"]="retry";
                        }
                        if($order->statuse_id==4){
                            if(stripos($order->signature_type,"patient")!=FALSE) {
                                $relationship="1";
                            } else if(stripos($order->signature_type,"brother")!=FALSE || stripos($order->signature_type,"sister")!=FALSE) {
                                $relationship="2";
                            } else if(stripos($order->signature_type,"mother")!=FALSE || stripos($order->signature_type,"father")!=FALSE) {
                                $relationship="3";
                            } else {
                                $relationship="6";
                            }
                            if($order->statuse_id==10 || $order->statuse_id==5 || $order->statuse_id==9) {
                                $failure_reason = DB::table('notes')->where('order_id',$order_id)->where("user_id",$order->driver_id)->orderBy('id','desc')->first();
                            } else {
                                $failure_reason = NULL;
                            }
                            if(!empty($order->driver_id)) {
                                $driver = DB::table('users')->where('id',$order->driver_id)->first();
                            }
                            $record["delivery"]=[
                                "occurred_at"=>date("Y-m-d\TH:i:s-00:00", strtotime($order->finish)),
                                "signature_url"=>"https://cp.a2brx.com".$order->signature_photo,
                                "signed_by"=>$order->signature_type,
                                "relationship"=>$relationship,
                                "failure_reason"=>$failure_reason,
                                "recipient_identification"=>[
                                    "number"=>$driver->driving_license,
                                    "qualifier"=>"06"   
                                ]
                            ];
                        } elseif($order->statuse_id==10) {
                            $packages_transition = DB::table("packages_transitions")->where('order_id',$order->id)->where('user_id',$order->user_id)->orderBy('id','desc')->first();
                            $return = DB::table('packages_transitions')->where('order_id',$order_id)->where('pharmacy_id',$pharmacy_auth->id)->where('driver_id',$order->driver_id)->where("target","in")->first();
                            if(!empty($packages_transition)) {
                                $record["delivery"]=[
                                    "failure_reason"=>"Patient Not Home",
                                    "occurred_at"=>date("Y-m-d\TH:i:s-00:00", strtotime($packages_transition->created)),
                                    "signature_url"=>NULL,
                                    "other_relationship_description"=>NULL,
                                    "signature_url"=>NULL,
                                    "recipient_identification"=>NULL,
                                    "signed_by"=>NULL
                                ];
                            } else {
                                $record["delivery"]=[
                                    "failure_reason"=>"Patient Not Home",
                                    "occurred_at"=>date("Y-m-d\TH:i:s-00:00", strtotime($order->finish)),
                                    "signature_url"=>NULL,
                                    "other_relationship_description"=>NULL,
                                    "signature_url"=>NULL,
                                    "recipient_identification"=>NULL,
                                    "signed_by"=>NULL
                                ];
                            }
                            if(empty($return)) {
                                $record["return"]=[
                                    "occurred_at"=>date("Y-m-d\TH:i:s-00:00", strtotime($order->finish))
                                ];
                            } else {
                                $record["return"]=[
                                    "occurred_at"=>date("Y-m-d\TH:i:s-00:00", strtotime($return->created))
                                ];
                            }
                        } elseif($order->statuse_id==2 || $order->statuse_id==3 || $order->statuse_id==6 || $order->statuse_id==7){
                            $pickup = DB::table('packages_transitions')->where('order_id',$order_id)->where('pharmacy_id',$pharmacy_auth->id)->where('driver_id',$order->driver_id)->where("target","out")->first();
                            if(!empty($order->driver_id) && !empty($pickup)) {
                                $record["pickup"]=[
                                    "occurred_at"=>date("Y-m-d\TH:i:s-00:00", strtotime($pickup->created))
                                ];
                            } else {
                                $record["pickup"]=[
                                    "occurred_at"=>date("Y-m-d\TH:i:s-00:00", strtotime($order->created))
                                ];
                            }
                        } else {
                            $record["delivery"]=NULL;
                            $record["return"]=NULL;
                            $record["pickup"]=NULL;
                        }
                        if($order->copay>0 && in_array($order->statuse_copay,[3,4,6])) {
                            $record["payment_collected"]=strval($order->copay);
                        } else {
                            $record["payment_collected"]=NULL;
                        }
                        if(!empty($order->drop_off_photo)) {
                            $r = explode('.',$order->drop_off_photo);
                            $type_img =$r[(count($r)-1)];
                            $record["proof_of_delivery"]=[
                                "image_url"=>"https://cp.a2brx.com".$order->drop_off_photo,
                                "type_of_image"=>strtoupper($type_img)
                            ];
                        } else {
                            $record["proof_of_delivery"]=NULL;
                        }
                        $record["payment_collected"]=NULL;
                        $record["shipping"]=NULL;
                        $record["labels"]=[
                            ['label_pdf'=>url('/').'/api/merchant/orders/ticket/print?order_id='.$order->id]
                        ];
                        $orders[]=$record;
                    }
                }
                return response()->json([
                    'orders' => $orders
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Your request is invalid',
                    'errors' => 'Bad Request'
                ], 400);
            }
        } else {
            return response()->json([
                'message' => 'Your API key is wrong or pharmacy is blocked',
                'errors' => 'Unauthorized'
            ], 401);
        }
    }

    public function ordersTicket(Request $request) {
        if($pharmacy_auth = $this->checkAuth($request)){
            $order_id = $_GET['order_id'];
            $order=DB::table('orders')->where('id',$order_id)->where('pharmacy_id',$pharmacy_auth->id)->where('merchantOrder','1')->first();
            if(!empty($order)){
                $rxs = DB::table('rxs')->where('order_id',$order->id)->get();
                foreach($rxs as $key0=>$rx) {
                    if(empty($rx->rx_id)) {
                        $rxs[$key0]->rx_id = 'null';
                    }
                }
                $order->rxs=$rxs;
                $pharmacy = $pharmacy_auth;
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
        } else {
            return response()->json([
                'message' => 'Your API key is wrong or pharmacy is blocked',
                'errors' => 'Unauthorized'
            ], 401);
        }
    }

    public function ordersTicketPrintPdf(Request $request) {
        if($pharmacy_auth = $this->checkAuth($request)){
            $order_id = $_GET['order_id'];
            $order=DB::table('orders')->where('id',$order_id)->where('pharmacy_id',$pharmacy_auth->id)->where('merchantOrder','1')->first();
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
        } else {
            return response()->json([
                'message' => 'Your API key is wrong or pharmacy is blocked',
                'errors' => 'Unauthorized'
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
                if(!empty($pharmacy_id)){
                    $pharmacy = DB::table('pharmacys')->where('isactive',1)->where('isblocked',0)->where('balance_ban','0')->where('npi',$pharmacy_id)->where('merchantKey',$apiKey)->where('merchantSecret',$apiSecret)->first();
                } else {
                    $pharmacy = DB::table('pharmacys')->where('isactive',1)->where('isblocked',0)->where('balance_ban','0')->where('merchantKey',$apiKey)->where('merchantSecret',$apiSecret)->first();
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
            'message' => 'Sorry, url or method not found',
            'errors' => 'Not Found'
        ], 404);
    }

}
