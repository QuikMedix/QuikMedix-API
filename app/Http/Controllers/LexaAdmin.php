<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\LoadsOrderLookups;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Twilio\Rest\Client;
use App\User;
use App\Notifications;
use App\Support\PageLinks;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;
use Carbon\Carbon;
use Zadarma_API\Api as Zadarma_API;
use URL;
use Smalot\PdfParser\Parser;
use App\Actions\GeocodeAddress;
use App\Actions\LogUserChanges;
use App\Actions\NotifyNextPatient;
use App\Actions\RecalculateDriverEta;
use App\Actions\RotateSignature;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2LoginHelper;

class LexaAdmin extends Controller
{
    use LoadsOrderLookups;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('2fa');
        ini_set('memory_limit', '-1');
    }

    public static $err_act_ban = "We're sorry for the inconvenience, but it seems we have a little hiccup here. Our team is already on the case, working hard to get things back on track as quickly as possible.";
    public static $err_perm = 'Permision error.';

    /**
     * Pages are also loaded into the dashboard over AJAX, which wants only the sections.
     *
     * @return View|array<string, string>
     */
    private static function renderPage(View $view): View|array {
        return request()->query->has('ajax') ? $view->renderSections() : $view;
    }

    /**
     * The page number from the query string; a missing, empty or zero value is the first page.
     */
    private static function currentPage(): int {
        return empty(request()->query('page')) ? 1 : intval(request()->query('page'));
    }

    public static function driversInquiries() {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->can('admin'))) {
            $users = User::get();
            return view('drivers.inquiries',['users'=>$users,'title'=>'Drivers Inquiries','br1'=>'Drivers','br2'=>'Inquiries']);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    
    /**
     * @param int|string $pharmacy_id
     */
    public static function cardPharmacyAddHandlerOld($pharmacy_id,Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
        if(!empty($pharmacy)) {
            if(Auth::user()->can('manage-pharmacy', $pharmacy_id)) {
                $success='';
                $error='';
                if(!empty($request->input('ncard')) && !empty($request->input('name')) && !empty($request->input('expire')) && !empty($request->input('ccv')) && !empty($request->input('address')) && !empty($request->input('zip'))) {
                    $ncard=str_replace(' ','',$request->input('ncard'));
                    $name=explode(' ',$request->input('name'));
                    if(count($name)==1) {
                        $name[1]="";
                    }
                    $expire=explode('/',str_replace(' ','',$request->input('expire')));
                    $ccv=$request->input('ccv');
                    $zip=$request->input('zip');
                    $user_address = explode(',',$request->input('address'));
                    if(count($user_address)==1) {
                        $user_address[1]="";
                        $user_address[2]="";
                    }
                    if(count($user_address)==2) {
                        $user_address[2]="";
                    }
                    $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
                    $merchantAuthentication->setName(config('app.MERCHANT_LOGIN_ID'));
                    $merchantAuthentication->setTransactionKey(config('app.MERCHANT_TRANSACTION_KEY'));
                    // Set the transaction's refId
                    $refId = 'ref' . time();
                    // Set credit card information for payment profile
                    $payment_account = DB::table('payment_pharmacy_accounts')->where('pharmacy_id',$pharmacy_id)->first();
                    if(!empty($payment_account)) {
                        $request = new AnetAPI\UpdateCustomerPaymentProfileRequest();
                        $request->setMerchantAuthentication($merchantAuthentication);
                        $request->setCustomerProfileId($payment_account->profile_id);
                        $controller = new AnetController\GetCustomerProfileController($request);
                        $creditCard = new AnetAPI\CreditCardType();
                        $creditCard->setCardNumber($ncard);
                        $creditCard->setExpirationDate($expire[1].'-'.$expire[0]);
                        $creditCard->setCardCode($ccv);
                        $paymentCreditCard = new AnetAPI\PaymentType();
                        $paymentCreditCard->setCreditCard($creditCard);
                        $billTo = new AnetAPI\CustomerAddressType();
                        $billTo->setFirstName($name[0]);
                        $billTo->setLastName($name[1]);
                        $billTo->setCompany($pharmacy->name);
                        $billTo->setAddress($user_address[0]);
                        $billTo->setCity($user_address[1]);
                        $billTo->setState($user_address[2]);
                        $billTo->setZip($zip);
                        $billTo->setCountry("USA");
                        $billTo->setPhoneNumber($pharmacy->phone);
                        $billTo->setfaxNumber($pharmacy->phone);
                        $paymentprofile = new AnetAPI\CustomerPaymentProfileExType();
                        $paymentprofile->setCustomerPaymentProfileId($payment_account->payment_profile_id);
                        $paymentprofile->setBillTo($billTo);
                        $paymentprofile->setPayment($paymentCreditCard);	
                        // Submit a UpdatePaymentProfileRequest
                        $request->setPaymentProfile($paymentprofile);
                        $controller = new AnetController\UpdateCustomerPaymentProfileController($request);
                        /** @var AnetAPI\UpdateCustomerPaymentProfileResponse $response */
                        $response = $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::PRODUCTION);
                        if (($response != null) && ($response->getMessages()->getResultCode() == "Ok") )
                        {
                            $success= "New Card successfully added!";
                            DB::table('payment_pharmacy_accounts')->where('pharmacy_id',$pharmacy_id)->update(['card'=>$ncard]);
                        } else if ($response != null) {
                            $errorMessages = $response->getMessages()->getMessage();
                            $error=$errorMessages[0]->getText();
                        }
                    } else {
                        $creditCard = new AnetAPI\CreditCardType();
                        $creditCard->setCardNumber($ncard);
                        $creditCard->setExpirationDate($expire[1].'-'.$expire[0]);
                        $creditCard->setCardCode($ccv);
                        $paymentCreditCard = new AnetAPI\PaymentType();
                        $paymentCreditCard->setCreditCard($creditCard);
                        // Create the Bill To info for new payment type
                        $billTo = new AnetAPI\CustomerAddressType();
                        $billTo->setFirstName($name[0]);
                        $billTo->setLastName($name[1]);
                        $billTo->setCompany("QuikMedix");
                        $billTo->setAddress($user_address[0]);
                        $billTo->setCity($user_address[1]);
                        $billTo->setState($user_address[2]);
                        $billTo->setZip($zip);
                        $billTo->setCountry("USA");
                        $billTo->setPhoneNumber($pharmacy->phone);
                        $billTo->setfaxNumber($pharmacy->phone);
                        // Create a customer shipping address
                        $customerShippingAddress = new AnetAPI\CustomerAddressType();
                        $customerShippingAddress->setFirstName($name[0]);
                        $customerShippingAddress->setLastName($name[1]);
                        $customerShippingAddress->setCompany("QuikMedix");
                        $customerShippingAddress->setAddress($user_address[0]);
                        $customerShippingAddress->setCity($user_address[1]);
                        $customerShippingAddress->setState($user_address[2]);
                        $customerShippingAddress->setZip($zip);
                        $customerShippingAddress->setCountry("USA");
                        $customerShippingAddress->setPhoneNumber($pharmacy->phone);
                        $customerShippingAddress->setFaxNumber($pharmacy->phone);
                        // Create an array of any shipping addresses
                        $shippingProfiles[] = $customerShippingAddress;
                        // Create a new CustomerPaymentProfile object
                        $paymentProfile = new AnetAPI\CustomerPaymentProfileType();
                        $paymentProfile->setCustomerType('individual');
                        $paymentProfile->setBillTo($billTo);
                        $paymentProfile->setPayment($paymentCreditCard);
                        $paymentProfiles[] = $paymentProfile;
                        // Create a new CustomerProfileType and add the payment profile object
                        $customerProfile = new AnetAPI\CustomerProfileType();
                        $customerProfile->setDescription("Pharmacy ".$pharmacy->id);
                        $customerProfile->setMerchantCustomerId($pharmacy->id);
                        $customerProfile->setEmail($pharmacy->email);
                        $customerProfile->setpaymentProfiles($paymentProfiles);
                        $customerProfile->setShipToList($shippingProfiles);
                        // Assemble the complete transaction request
                        $request = new AnetAPI\CreateCustomerProfileRequest();
                        $request->setMerchantAuthentication($merchantAuthentication);
                        $request->setRefId($refId);
                        $request->setProfile($customerProfile);
                        // Create the controller and get the response
                        $controller = new AnetController\CreateCustomerProfileController($request);
                        /** @var AnetAPI\CreateCustomerProfileResponse $response */
                        $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::PRODUCTION);
                        if (($response != null) && ($response->getMessages()->getResultCode() == "Ok")) {
                            $paymentProfiles = $response->getCustomerPaymentProfileIdList();
                            DB::table('payment_pharmacy_accounts')->insert(['pharmacy_id'=>$pharmacy_id,'profile_id'=>$response->getCustomerProfileId(),'payment_profile_id'=>$paymentProfiles[0],'card'=>$ncard]);
                            $success="Card successfully added!";
                        } else {
                            $errorMessages = $response->getMessages()->getMessage();
                            $error=$errorMessages[0]->getText();
                        }
                    }
                } else {
                    $error='All card input is required.';
                }
                $payment_account = DB::table('payment_pharmacy_accounts')->where('pharmacy_id',$pharmacy_id)->first();
                return view('card_pharmacy.add',['payment_account'=>$payment_account,'success'=>$success,'error'=>$error,'title'=>'Cards','br1'=>'Cards','br2'=>'Card','br3'=>'Cards','alert'=>'']);
            } else {
                return abort(403, self::$err_perm);
            }
        } else {
            return abort(404, "Not found pharmacy");
        }
    }

    /**
     * @param int|string $user_id
     */
    public function chatUser($user_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        $user = DB::table('users')->where('id', $user_id)->first();
        $user_pharmacy = DB::table('pharmacys')->where('id', $user->pharmacy_id)->first();
        $user_pharmacy2 = DB::table('pharmacys')->where('id', Auth::user()->pharmacy_id)->first();
        if(empty($user_pharmacy)) {
            $user_pharmacy='';
        } else {
            $user_pharmacy=' ('.$user_pharmacy->name.')';
        }
        if(empty($user_pharmacy2)) {
            $user_pharmacy2='';
        } else {
            $user_pharmacy2=' ('.$user_pharmacy2->name.')';
        }
        $chat = DB::table('chats')->orWhere('name', $user_id.'_'.Auth::user()->id)->orWhere('name', Auth::user()->id.'_'.$user_id)->first();
        if(!empty($chat->id)) {
            $chat_name = $chat->name;
        } else {
            $chat_name = Auth::user()->id.'_'.$user_id;
            DB::table('chats')->insert(['name'=>$chat_name,'user1'=>Auth::user()->id,'user2'=>$user_id]);
            //DB::table('notifications')->insert(['user_id'=>$user_id,'type'=>'primary','link'=>url('/').'chats/user/'.Auth::user()->id,'text'=>'User '.Auth::user()->name.' '.Auth::user()->last_name.$user_pharmacy2.' started a chat with you. Open correspondence.']);
        }
        $chat = DB::table('chats')->orWhere('name', $user_id.'_'.Auth::user()->id)->orWhere('name', Auth::user()->id.'_'.$user_id)->first();
        $twilio = new Client(config('app.twilio_sid'), config('app.twilio_auth_token'));
        $channels = $twilio->chat->v2->services(config('app.twilio_chatServiceSid'))->channels->read(['uniqueName'=>$chat_name]);
        foreach ($channels as $record) {
            if($record->uniqueName==$chat_name) {
                $channel_sid = $record->sid;
            }
        }
        if(empty($channel_sid)) {
            $channel = $twilio->chat->v2->services(config('app.twilio_chatServiceSid'))->channels->create(['friendlyName'=>$chat_name,'uniqueName'=>$chat_name]);
            $channel_sid = $channel->sid;
        }
        $members = $twilio->chat->v2->services(config('app.twilio_chatServiceSid'))->channels($channel_sid)->members->read();
        foreach ($members as $record) {
            if($record->identity==Auth::user()->id) {
                $member_sid = $record->sid;
            }
            if($record->identity==$user_id) {
                $user_last_index = $record->lastConsumedMessageIndex;
            }
        }
        if(empty($member_sid)) {
            $member = $twilio->chat->v2->services(config('app.twilio_chatServiceSid'))->channels($channel_sid)->members->create(Auth::user()->id);
            $member_sid = $member->sid;
        }
        if(empty($user_last_index)) {
            $user_last_index=0;
        }
        $messages = $twilio->chat->v2->services(config('app.twilio_chatServiceSid'))->channels($channel_sid)->messages->read();
        if(!isset(end($messages)->index)) {
            $max_message_index=0;
            $last_message_date=null;
            $last_message_body=null;
        } else {
            $max_message_index=end($messages)->index;
            $last_message_date=end($messages)->dateCreated->setTimezone(new \DateTimeZone('America/New_York'))->format("Y-m-d H:i:s");
            $last_message_body=end($messages)->body;
        }
        $member = $twilio->chat->v2->services(config('app.twilio_chatServiceSid'))->channels($channel_sid)->members($member_sid)->update(["lastConsumedMessageIndex" => $max_message_index]);
        if($chat->user1==Auth::user()->id) {
            DB::table('chats')->where('name',$chat_name)->update(['unread_user1'=>0,'last_message_date'=>$last_message_date,'last_message_body'=>$last_message_body]);
        } else {
            DB::table('chats')->where('name',$chat_name)->update(['unread_user2'=>0,'last_message_date'=>$last_message_date,'last_message_body'=>$last_message_body]);
        }
        return view('chats.chat',['user'=>$user,'chat_name'=>$chat_name,'messages'=>$messages,'user_last_index'=>$user_last_index,'title'=>'Chat with '.$user->name.' '.$user->last_name.$user_pharmacy,'br1'=>'Chats','br2'=>'Chat']);
    }

    public function new_message(Request $request) {
        if(!empty($request->input('chat_name')) && !empty($request->input('created')) && !empty($request->input('body')) && !empty($request->input('user'))) {
            $user=$request->input('user');
            $chat_name=$request->input('chat_name');
            $created=date("Y-m-d H:i:s",strtotime($request->input('created')));
            $body=$request->input('body');
            $chat = DB::table('chats')->where('name',$chat_name)->first();
            if($chat->user1==$user) {
                if($request->input('not_me_author')>0) {
                    $unread_user=0;
                } else {
                    $unread_user=$chat->unread_user2+1;
                }
                $user = DB::table('users')->where('id', $chat->user1)->first();
                $user_pharmacy = DB::table('pharmacys')->where('id', $user->pharmacy_id)->first();
                if(empty($user_pharmacy)) {
                    $user_pharmacy='';
                } else {
                    $user_pharmacy=' ('.$user_pharmacy->name.')';
                }
                $user2 = DB::table('users')->where('id', $chat->user2)->first();
                Notifications::send_push($user2->id,"QuikMedix","You have a new incoming message from ".$user->name.' '.$user->last_name.$user_pharmacy);
                DB::table('chats')->where('name',$chat_name)->update(['unread_user1'=>0,'unread_user2'=>$unread_user,'last_message_date'=>$created,'last_message_body'=>$body]);
            } else {
                if($request->input('not_me_author')>0) {
                    $unread_user=0;
                } else {
                    $unread_user=$chat->unread_user1+1;
                }
                $user = DB::table('users')->where('id', $chat->user2)->first();
                $user_pharmacy = DB::table('pharmacys')->where('id', $user->pharmacy_id)->first();
                if(empty($user_pharmacy)) {
                    $user_pharmacy='';
                } else {
                    $user_pharmacy=' ('.$user_pharmacy->name.')';
                }
                $user2 = DB::table('users')->where('id', $chat->user1)->first();
                Notifications::send_push($user2->id,"QuikMedix","You have a new incoming message from ".$user->name.' '.$user->last_name.$user_pharmacy);
                DB::table('chats')->where('name',$chat_name)->update(['unread_user2'=>0,'unread_user1'=>$unread_user,'last_message_date'=>$created,'last_message_body'=>$body]);
            }
            
            return json_encode([
                'result' => 'true'
            ]);
        } else {
            return json_encode([
                'message' => 'Failed data',
                'errors' => 'Not Found'
            ]);
        }
    }

    public function chats() {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        $chats_db = DB::table('chats')->orWhere('user1',Auth::user()->id)->orWhere('user2',Auth::user()->id)->orderBy('last_message_date','desc')->get();
        $chats=array();
        foreach ($chats_db as $chat) {
            if($chat->user1==Auth::user()->id) {
                $user_id=$chat->user2;
                $count_noread=$chat->unread_user1;
            } else {
                $user_id=$chat->user1;
                $count_noread=$chat->unread_user2;
            }
            $last_message_date=$chat->last_message_date;
            $last_message_body=$chat->last_message_body;
            $user = DB::table('users')->where('id', $user_id)->first();
            $user_pharmacy = DB::table('pharmacys')->where('id', $user->pharmacy_id)->first();
            if(empty($user_pharmacy)) {
                $user_pharmacy='';
            } else {
                $user_pharmacy=' ('.$user_pharmacy->name.')';
            }
            array_push($chats,['link'=>"/chats/user/$user_id",'count_noread'=>$count_noread,'name'=>$user->name.' '.$user->last_name.$user_pharmacy,'image'=>$user->image,'last_message_date'=>$last_message_date,'last_message_body'=>$last_message_body]);
        }
        return view('chats.chats',['chats'=>$chats,'title'=>'Chats List','br1'=>'Chats','br2'=>'List']);
    }

    public function getUserInfo(Request $request) {
        $user = DB::table('users')->where('id', $request->input('identity'))->first();
        if(!empty($user->id)) {
            return json_encode([
                'name' => $user->name.' '.$user->last_name,
                'phone' => $user->phone,
                'image' => $user->image
            ]);
        } else {
            return json_encode([
                'message' => 'User not found',
                'errors' => 'Not Found'
            ]);
        }
    }

    /**
     * @param string $polygon_json
     */
    function decodePolygon($polygon_json) {
        $polygon_str = "";
        $data = json_decode($polygon_json);
        foreach($data as $key=>$row) {
            $polygon_str.=$row->lat.' ';
            $polygon_str.=$row->lng;
            if($key<count($data)-1) {
                $polygon_str.=',';
            } else {
                $polygon_str.=','.$data[0]->lat.' '.$data[0]->lng;
            }
        }
        return $polygon_str;
    }

    public function test() {
        /*$date_from = "2023-06-12";
        $date_to = "2023-06-18";
        $pharmacys = [188];
        foreach($pharmacys as $pharmacy_id) {
            $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
            $orders = DB::table('orders')->where('pharmacy_id', $pharmacy_id)->whereIn('statuse_id',[4,8,9,10])->whereDate('finish', '>=', $date_from)->whereDate('finish', '<=', $date_to);
            $count_orders=$orders->count();
            $sum_amount = 0;
            $sum_copay = 0;
            $orders = $orders->get();
            foreach($orders as $order) {
                if(!empty($order->tariff)) {
                    $sum_amount = $sum_amount+$order->tariff;
                    if($order->statuse_copay==3 || $order->statuse_copay==4){
                        $sum_copay = $sum_copay+floatval($order->copay);
                    }
                }
            }
            if($pharmacy->copay_bill=='1') {
                if((($sum_amount)-$sum_copay)<0) {
                    $amount2 = 0;
                } else {
                    $amount2 = round((($sum_amount)-$sum_copay),2);
                }
            } else {
                $amount2 = round(($sum_amount),2);
            }
            $balance = floatval($pharmacy->balance)-$amount2;
            DB::table('pharmacys')->where("id",$pharmacy_id)->update(['balance'=>$balance]);
            $invoice_id = DB::table('invoices')->insertGetId(['pharmacy_id'=>$pharmacy_id,'date_from' => $date_from,'date_to' => $date_to, 'count'=>$count_orders, 'amount'=>$sum_amount, 'copay'=>$sum_copay]);
            if(floatval($pharmacy->balance)>=$amount2) {
                DB::table('pharmacy_payments')->insert(['pharmacy_id'=>$pharmacy_id,'invoice_id'=>$invoice_id,'amount'=>$amount2,'transaction_id'=>'balance','type'=>'pay']);
                DB::table('invoices')->where('id',$invoice_id)->where('pharmacy_id',$pharmacy_id)->update(["payed"=>'1']);
                $invoice_exclusions = DB::table('invoice_exclusion')->where('invoice_id',$invoice_id)->pluck('order_id')->toArray();
                DB::table('orders')->where('pharmacy_id', $pharmacy_id)->whereIn('statuse_id',[4,8,9,10])->whereDate('finish', '>=', $date_from)->whereDate('finish', '<=', $date_to)->whereNotIn('id',$invoice_exclusions)->update(["invoice_payed"=>"1"]);
            }
        }*/
        /*$start = "2023-06-12";
        $end = "2023-06-19";
        $pharmacys = [188]; //DB::table('orders')->whereDate('finish', '>=', $start)->whereDate('finish', '<=', $end)->groupBy("pharmacy_id")->pluck('pharmacy_id')->toArray();
        foreach($pharmacys as $pharmacy_id) {
            $orders = DB::table('orders')->where("pharmacy_id",$pharmacy_id)->whereBetween('orders.finish', [$start, $end])->get();
            foreach($orders as $order) {
                $pharmacy=DB::table('pharmacys')->where('pharmacys.id',$order->pharmacy_id)->first();
                $pharmacy_plan=DB::table('plans')->where('plans.id',$pharmacy->plan_id)->first();
                $patient=DB::table('users')->where('users.id',$order->user_id)->first();
                $pharmacy_areas=DB::table('pharmacy_areas')->where('pharmacy_id',$order->pharmacy_id)->where('type',1)->pluck('area_id')->toArray();
                $pharmacy_areas2=DB::table('pharmacy_areas')->where('pharmacy_id',$order->pharmacy_id)->where('type',2)->pluck('area_id')->toArray();
                $pharmacy_areas3=DB::table('pharmacy_areas')->where('pharmacy_id',$order->pharmacy_id)->where('type',3)->pluck('area_id')->toArray();
                $zip_tariff=DB::table('area')->whereIn('area.id',$pharmacy_areas)->whereRaw('ST_CONTAINS(polygon, POINT(?, ?))', \App\Support\GeoPoint::bindings($patient->location))->select("area.id")->first();
                $zip_tariff2=DB::table('area')->whereIn('area.id',$pharmacy_areas2)->whereRaw('ST_CONTAINS(polygon, POINT(?, ?))', \App\Support\GeoPoint::bindings($patient->location))->select("area.id")->first();
                $zip_tariff3=DB::table('area')->whereIn('area.id',$pharmacy_areas3)->whereRaw('ST_CONTAINS(polygon, POINT(?, ?))', \App\Support\GeoPoint::bindings($patient->location))->select("area.id")->first();
                if(!empty($zip_tariff)){
                    if(is_numeric($pharmacy->tariff)) {
                        $tariff = $pharmacy->tariff;
                    } else {
                        $tariff = $pharmacy_plan->tariff;
                    }
                } else if(!empty($zip_tariff2)){
                    if(is_numeric($pharmacy->tariff_area2)) {
                        $tariff = $pharmacy->tariff_area2;
                    } else {
                        $tariff = $pharmacy_plan->tariff_area2;
                    }
                } else if(!empty($zip_tariff3)){
                    if(is_numeric($pharmacy->tariff_area3)) {
                        $tariff = $pharmacy->tariff_area3;
                    } else {
                        $tariff = $pharmacy_plan->tariff_area3;
                    }
                } else {
                    if(is_numeric($pharmacy->tariff_area_more)) {
                        $tariff = $pharmacy->tariff_area_more;
                    } else {
                        $tariff = $pharmacy_plan->tariff_area_more;
                    }
                }
                if(is_numeric($pharmacy->tariff_next_day)) {
                    $tariff_next_day = $pharmacy->tariff_next_day;
                } else {
                    $tariff_next_day = $pharmacy_plan->tariff_next_day;
                }
                if(is_numeric($pharmacy->tariff_same_day)) {
                    $tariff_same_day = $pharmacy->tariff_same_day;
                } else {
                    $tariff_same_day = $pharmacy_plan->tariff_same_day;
                }
                if(is_numeric($pharmacy->tariff_asap)) {
                    $tariff_asap = $pharmacy->tariff_asap;
                } else {
                    $tariff_asap = $pharmacy_plan->tariff_asap;
                }
                if(is_numeric($pharmacy->tariff_after_hours)) {
                    $tariff_after_hours = $pharmacy->tariff_after_hours;
                } else {
                    $tariff_after_hours = $pharmacy_plan->tariff_after_hours;
                }
                if(is_numeric($pharmacy->tariff_fridge)) {
                    $tariff_fridge = $pharmacy->tariff_fridge;
                } else {
                    $tariff_fridge = $pharmacy_plan->tariff_fridge;
                }
                if($order->type_driver==1) {
                    if($order->delivery_time_id==1) {
                        $tariff_res = (floatval($tariff)+floatval($tariff_next_day)+floatval($order->extra_charge_driver));
                    } elseif($order->delivery_time_id==2) {
                        $tariff_res = (floatval($tariff)+floatval($tariff_same_day)+floatval($order->extra_charge_driver));
                    } elseif($order->delivery_time_id==3) {
                        $tariff_res = (floatval($tariff)+floatval($tariff_asap)+floatval($order->extra_charge_driver));
                    } elseif($order->delivery_time_id==4) {
                        $tariff_res = (floatval($tariff)+floatval($tariff_after_hours)+floatval($order->extra_charge_driver));
                    } else {
                        throw new \UnexpectedValueException("Unknown delivery time {$order->delivery_time_id} for order {$order->id}");
                    }
                    if($order->fridge==1) {
                        $tariff_res+= floatval($tariff_fridge);
                    }
                } else {
                    $tariff_res = floatval($tariff);
                }
                DB::table('orders')->where('id', $order->id)->update(['tariff'=>$tariff_res,'invoice_payed'=>'0']);
                if($tariff_res>25) {
                    echo $order->id."<br>";
                }
            }
        }*/
        return "end";
    }

    

    /**
     * @param int|string $driver_id
     */
    public static function eta_calculate($driver_id,$primary=FALSE) {
        return app(RecalculateDriverEta::class)->handle($driver_id, (bool) $primary);
    }

    /**
     * @param int|string $driver_id
     * @param \stdClass $next_route
     */
    static function next_patient_push($driver_id,$next_route){
        return app(NotifyNextPatient::class)->handle($driver_id, $next_route);
    }


}
