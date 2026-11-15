<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersLegacyPages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;
use App\Support\AccessMessages;

/**
 * Card payments: a patient's card, a pharmacy's payment method and balance refill, and paying an
 * order's copay. Formerly actions of LexaAdmin, moved as they were.
 */
class PaymentController extends Controller
{
    use RendersLegacyPages;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('2fa');
        // Kept from LexaAdmin, whose pages load whole result sets at once.
        ini_set('memory_limit', '-1');
    }

    public function cardAdd() {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, AccessMessages::ACCOUNT_INACTIVE);
        }
        if(Auth::user()->hasAnyRole('superadmin', 'admin', 'dispadmin', 'logist')) {
            $payment_account = DB::table('payment_accounts')->where('user_id',Auth::user()->id)->first();
            $success='';
            $error='';
            return view('card.add',['payment_account'=>$payment_account,'success'=>$success,'error'=>$error,'title'=>'Cards','br1'=>'Cards','br2'=>'Card','br3'=>'Cards','alert'=>'']);
        } else {
            return abort(403, AccessMessages::NO_PERMISSION);
        }
    }

    public function cardAddHandler(Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, AccessMessages::ACCOUNT_INACTIVE);
        }
        if(Auth::user()->hasAnyRole('superadmin', 'admin', 'dispadmin', 'logist')) {
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
                $payment_account = DB::table('payment_accounts')->where('user_id',Auth::user()->id)->first();
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
                    $billTo->setCompany("QuikMedix");
                    $billTo->setAddress($user_address[0]);
                    $billTo->setCity($user_address[1]);
                    $billTo->setState($user_address[2]);
                    $billTo->setZip($zip);
                    $billTo->setCountry("USA");
                    $billTo->setPhoneNumber(Auth::user()->phone);
                    $billTo->setfaxNumber("999-999-9999");
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
                        DB::table('payment_accounts')->where('user_id',Auth::user()->id)->update(['card'=>$ncard]);
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
                    $billTo->setPhoneNumber(Auth::user()->phone);
                    $billTo->setfaxNumber("999-999-9999");
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
                    $customerShippingAddress->setPhoneNumber(Auth::user()->phone);
                    $customerShippingAddress->setFaxNumber("999-999-9999");
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
                    $customerProfile->setDescription("Customer ".Auth::user()->id);
                    $customerProfile->setMerchantCustomerId(Auth::user()->id);
                    $customerProfile->setEmail(Auth::user()->email);
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
                        DB::table('payment_accounts')->insert(['user_id'=>Auth::user()->id,'profile_id'=>$response->getCustomerProfileId(),'payment_profile_id'=>$paymentProfiles[0],'card'=>$ncard]);
                        $success="Card successfully added!";
                    } else {
                        $errorMessages = $response->getMessages()->getMessage();
                        $error=$errorMessages[0]->getText();
                    }
                }
            } else {
                $error='All card input is required.';
            }
            $payment_account = DB::table('payment_accounts')->where('user_id',Auth::user()->id)->first();
            return view('card.add',['payment_account'=>$payment_account,'success'=>$success,'error'=>$error,'title'=>'Cards','br1'=>'Cards','br2'=>'Card','br3'=>'Cards','alert'=>'']);
        } else {
            return abort(403, AccessMessages::NO_PERMISSION);
        }
    }

    /**
     * @param int|string $pharmacy_id
     */
    public function cardPharmacyAdd($pharmacy_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, AccessMessages::ACCOUNT_INACTIVE);
        }
        $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
        if(!empty($pharmacy)) {
            if(Auth::user()->can('manage-pharmacy', $pharmacy_id)) {
                $payment_account = DB::table('payment_pharmacy_accounts')->where('pharmacy_id',$pharmacy_id)->first();
                $success='';
                $error='';
                return view('card_pharmacy.add',['payment_account'=>$payment_account,'success'=>$success,'error'=>$error,'title'=>'Cards','br1'=>'Cards','br2'=>'Card','br3'=>'Cards','alert'=>'']);
            } else {
                return abort(403, AccessMessages::NO_PERMISSION);
            }
        } else {
            return abort(404, "Not found pharmacy");
        }
    }

    /**
     * @param int|string $pharmacy_id
     */
    public function cardPharmacyAddHandler($pharmacy_id,Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, AccessMessages::ACCOUNT_INACTIVE);
        }
        $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
        if(!empty($pharmacy)) {
            if(Auth::user()->can('manage-pharmacy', $pharmacy_id)) {
                $success='';
                $error='';
                if(!empty($request->input('type')) && !empty($request->input('name')) && !empty($request->input('address')) && !empty($request->input('zip'))) {
                    $card_token = $request->input('card');
                    $type = $request->input('type');
                    $name=explode(' ',$request->input('name'));
                    if(count($name)==1) {
                        $name[1]="";
                    }
                    $zip=$request->input('zip');
                    $user_address = explode(',',$request->input('address'));
                    if(count($user_address)==1) {
                        $user_address[1]="";
                        $user_address[2]="";
                    }
                    if(count($user_address)==2) {
                        $user_address[2]="";
                    }
                    if(count($user_address)==3) {
                        $user_address[3]="NY";
                    }
                    $amount = 1;
                    $payment_account = DB::table('payment_pharmacy_accounts')->where('pharmacy_id',$pharmacy_id)->first();
                    $client = new \Square\SquareClient([
                        'accessToken' => config('app.SQUARE_ACCESS_TOKEN'),
                        'environment' => config('app.SQUARE_ENVIRONMENT'),
                    ]);
                    $address = new \Square\Models\Address();
                    $address->setAddressLine1($user_address[0]);
                    $address->setAddressLine2($user_address[1]);
                    $address->setLocality($user_address[2]);
                    $address->setAdministrativeDistrictLevel1($user_address[3]);
                    $address->setPostalCode($zip);
                    $address->setCountry('US');
                    if(empty($payment_account)) {
                        $body = new \Square\Models\CreateCustomerRequest();
                        $body->setGivenName($name[0]);
                        $body->setFamilyName($name[1]);
                        $body->setEmailAddress($pharmacy->email);
                        $body->setAddress($address);
                        $body->setPhoneNumber(str_replace(" ","",str_replace("-","",str_replace(")","",str_replace("(","",$pharmacy->phone)))));
                        $body->setReferenceId($pharmacy_id);
                        $body->setNote('Pharmacy #'.$pharmacy_id);
                        $api_response = $client->getCustomersApi()->createCustomer($body);
                        if ($api_response->isSuccess()) {
                            $result = $api_response->getResult();
                            DB::table('payment_pharmacy_accounts')->insert(['pharmacy_id'=>$pharmacy_id,"type"=>$type,"profile_id"=>$result->getCustomer()->getId()]);
                            $payment_account = DB::table('payment_pharmacy_accounts')->where('pharmacy_id',$pharmacy_id)->first();
                        } else {
                            $error = json_encode($api_response->getErrors());
                            return view('card_pharmacy.add',['payment_account'=>$payment_account,'success'=>$success,'error'=>$error,'title'=>'Cards','br1'=>'Cards','br2'=>'Card','br3'=>'Cards','alert'=>'']);
                        }
                    } else {
                        DB::table('payment_pharmacy_accounts')->where('pharmacy_id',$pharmacy_id)->update(["type"=>$type]);
                    }
                    if($type=="card") {
                        $amount_money = new \Square\Models\Money();
                        $amount_money->setAmount(\App\Support\Money::cents($amount));
                        $amount_money->setCurrency('USD');
                        $unid = uniqid("",true).rand(0,100);
                        $body = new \Square\Models\CreatePaymentRequest(
                            $card_token,
                            $unid
                        );
                        $body->setAmountMoney($amount_money);
                        $body->setAutocomplete(true);
                        $body->setCustomerId($payment_account->profile_id);
                        $body->setLocationId(config('app.SQUARE_LOCATION_ID'));
                        $body->setNote('Authorization Card');
                        $api_response = $client->getPaymentsApi()->createPayment($body);
                        if ($api_response->isSuccess()) {
                            $result = $api_response->getResult();
                            $payment_id = $result->getPayment()->getId();
                            $payment_status = $result->getPayment()->getStatus();
                            if($payment_status=="COMPLETED" || $payment_status=="APPROVED" || $payment_status=="PENDING") {
                                DB::table('payment_pharmacy_accounts')->where('pharmacy_id',$pharmacy_id)->update(["payment_id"=>$payment_id]);
                                $card = new \Square\Models\Card();
                                $card->setCardholderName($name[0].' '.$name[1]);
                                $card->setBillingAddress($address);
                                $card->setCustomerId($payment_account->profile_id);
                                $unid = uniqid("",true).rand(0,100);
                                $body = new \Square\Models\CreateCardRequest(
                                    $unid,
                                    $payment_id,
                                    $card
                                );
                                $api_response = $client->getCardsApi()->createCard($body);
                                if ($api_response->isSuccess()) {
                                    $result = $api_response->getResult();
                                    $unid = uniqid("",true).rand(0,100);
                                    DB::table('payment_pharmacy_accounts')->where('pharmacy_id',$pharmacy_id)->update(["payment_profile_id"=>$result->getCard()->getId(),"card"=>$result->getCard()->getLast4()]);
                                    $body = new \Square\Models\RefundPaymentRequest($unid, $amount_money);
                                    $body->setPaymentId($payment_id);
                                    $api_response = $client->getRefundsApi()->refundPayment($body);
                                    if ($api_response->isSuccess()) {
                                        $success="Payment Method successfully added!";
                                    } else {
                                        $error = "Payment Method successfully added, but refund payment not completed. ".json_encode($api_response->getErrors());
                                        return view('card_pharmacy.add',['payment_account'=>$payment_account,'success'=>$success,'error'=>$error,'title'=>'Cards','br1'=>'Cards','br2'=>'Card','br3'=>'Cards','alert'=>'']);
                                    }
                                } else {
                                    $error = json_encode($api_response->getErrors());
                                    return view('card_pharmacy.add',['payment_account'=>$payment_account,'success'=>$success,'error'=>$error,'title'=>'Cards','br1'=>'Cards','br2'=>'Card','br3'=>'Cards','alert'=>'']);
                                }
                            } else {
                                $error = "Payment not completed!";
                                return view('card_pharmacy.add',['payment_account'=>$payment_account,'success'=>$success,'error'=>$error,'title'=>'Cards','br1'=>'Cards','br2'=>'Card','br3'=>'Cards','alert'=>'']);
                            }
                        } else {
                            $error = json_encode($api_response->getErrors());
                            return view('card_pharmacy.add',['payment_account'=>$payment_account,'success'=>$success,'error'=>$error,'title'=>'Cards','br1'=>'Cards','br2'=>'Card','br3'=>'Cards','alert'=>'']);
                        }
                    }
                } else {
                    $error='All Payment Method input is required.';
                }
                $payment_account = DB::table('payment_pharmacy_accounts')->where('pharmacy_id',$pharmacy_id)->first();
                return view('card_pharmacy.add',['payment_account'=>$payment_account,'success'=>$success,'error'=>$error,'title'=>'Cards','br1'=>'Cards','br2'=>'Card','br3'=>'Cards','alert'=>'']);
            } else {
                return abort(403, AccessMessages::NO_PERMISSION);
            }
        } else {
            return abort(404, "Not found pharmacy");
        }
    }

    /**
     * @param int|string $pharmacy_id
     */
    public function refillPharmacyBalance($pharmacy_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, AccessMessages::ACCOUNT_INACTIVE);
        }
        $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
        if(!empty($pharmacy)) {
            if(Auth::user()->can('manage-pharmacy', $pharmacy_id)) {
                $payment_account = DB::table('payment_pharmacy_accounts')->where('pharmacy_id',$pharmacy_id)->first();
                $success='';
                $error='';
                return view('card_pharmacy.pay',['payment_account'=>$payment_account,'pharmacy'=>$pharmacy,'success'=>$success,'error'=>$error,'title'=>'Cards','br1'=>'Cards','br2'=>'Card','br3'=>'Cards','alert'=>'']);
            } else {
                return abort(403, AccessMessages::NO_PERMISSION);
            }
        } else {
            return abort(403, AccessMessages::NO_PERMISSION);
        }
    }

    /**
     * @param int|string $pharmacy_id
     */
    public function refillPharmacyBalanceHandler(Request $request, $pharmacy_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, AccessMessages::ACCOUNT_INACTIVE);
        }
        $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
        if(!empty($pharmacy)) {
            if(Auth::user()->can('manage-pharmacy', $pharmacy_id)) {
                $payment_account = DB::table('payment_pharmacy_accounts')->where('pharmacy_id',$pharmacy_id)->first();
                $success='';
                $error='';
                $amount = round(floatval($request->input('amount')),2);
                if(!empty($request->input('pay')) && !empty($payment_account) && $amount>0) {
                    $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
                    $merchantAuthentication->setName(config('app.MERCHANT_LOGIN_ID'));
                    $merchantAuthentication->setTransactionKey(config('app.MERCHANT_TRANSACTION_KEY'));
                    // Set the transaction's refId
                    $refId = 'ref' . time();
                    $profileToCharge = new AnetAPI\CustomerProfilePaymentType();
                    $profileToCharge->setCustomerProfileId($payment_account->profile_id);
                    $paymentProfile = new AnetAPI\PaymentProfileType();
                    $paymentProfile->setPaymentProfileId($payment_account->payment_profile_id);
                    $profileToCharge->setPaymentProfile($paymentProfile);
                    $transactionRequestType = new AnetAPI\TransactionRequestType();
                    $transactionRequestType->setTransactionType("authCaptureTransaction");
                    $transactionRequestType->setAmount($amount);
                    $transactionRequestType->setProfile($profileToCharge);
                    $authorizationIndicatorType = new AnetAPI\AuthorizationIndicatorType();
                    $authorizationIndicatorType->setAuthorizationIndicator("final");
                    $transactionRequestType->setAuthorizationIndicatorType($authorizationIndicatorType);
                    $request0 = new AnetAPI\CreateTransactionRequest();
                    $request0->setMerchantAuthentication($merchantAuthentication);
                    $request0->setRefId($refId);
                    $request0->setTransactionRequest($transactionRequestType);
                    $controller = new AnetController\CreateTransactionController($request0);
                    /** @var AnetAPI\CreateTransactionResponse $response */
                    $response = $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::PRODUCTION);
                    if ($response != null) {
                        if($response->getMessages()->getResultCode() == "Ok") {
                            $tresponse = $response->getTransactionResponse();
                            if ($tresponse != null && $tresponse->getMessages() != null) {
                                $balance = round(floatval($amount+floatval($pharmacy->balance)),2);
                                DB::table('pharmacys')->where('id',$pharmacy_id)->update(['balance'=>$balance]);
                                DB::table('pharmacy_payments')->insert(['pharmacy_id'=>$pharmacy_id,'amount'=>$amount,'transaction_id'=>$tresponse->getTransId(),'type'=>'refill']);
                                return response('<script>window.close();</script><a href="/billing/'.$pharmacy_id.'">Go Back</a> OR Close Window');
                            } else {
                                $error= "Transaction Failed: ";
                                if($tresponse->getErrors() != null) {
                                    $error .= $tresponse->getErrors()[0]->getErrorText();
                                }
                            }
                        } else {
                            $error= "Transaction Failed: ";
                            $tresponse = $response->getTransactionResponse();
                            if($tresponse != null && $tresponse->getErrors() != null) {
                                $error .= $tresponse->getErrors()[0]->getErrorText();
                            } else {
                                $error .= $response->getMessages()->getMessage()[0]->getText();
                            }
                        }
                    } else {
                        $error= "No response returned.";
                    }
                }
                return view('card_pharmacy.pay',['payment_account'=>$payment_account,'pharmacy'=>$pharmacy,'success'=>$success,'error'=>$error,'title'=>'Billing','br1'=>'Billings','br2'=>'List']);
            } else {
                return abort(403, AccessMessages::NO_PERMISSION);
            }
        } else {
            return abort(403, AccessMessages::NO_PERMISSION);
        }
    }

    /**
     * @param int|string $order_id
     */
    public function payCopay($order_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, AccessMessages::ACCOUNT_INACTIVE);
        }
        $order = DB::table('orders')->where('id',$order_id)->first();
        if(!empty($order)) {
            if(!in_array($order->statuse_id,[1,5,8,9,10]) && ((Auth::user()->can('admin')) || Auth::user()->role == 'logist') && $order->copay>0) {
                $payment_account = DB::table('payment_accounts')->where('user_id',$order->user_id)->first();
                $success='';
                $error='';
                $res_view = view('card.pay-copay',['payment_account'=>$payment_account,'order'=>$order,'success'=>$success,'error'=>$error,'title'=>'Billing','br1'=>'Billings','br2'=>'List']);
                return $this->page(request(), $res_view);
            } else {
                return abort(403, AccessMessages::NO_PERMISSION);
            }
        } else {
            return abort(403, AccessMessages::NO_PERMISSION);
        }
    }

    /**
     * @param int|string $order_id
     */
    public function payCopayHandler(Request $request, $order_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, AccessMessages::ACCOUNT_INACTIVE);
        }
        $order = DB::table('orders')->where('id',$order_id)->first();
        if(!empty($order)) {
            $user = DB::table('users')->where('id',$order->user_id)->first();
            if(!in_array($order->statuse_id,[1,5,8,9,10]) && ((Auth::user()->can('admin')) || Auth::user()->role == 'logist') && !in_array($order->statuse_copay,[1,3,4,6]) && $order->copay>0) {
                $payment_account = DB::table('payment_accounts')->where('user_id',$order->user_id)->first();
                $success='';
                $error='';
                if(!empty($request->input('pay')) && !empty($payment_account)) {
                    $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
                    $merchantAuthentication->setName(config('app.MERCHANT_LOGIN_ID'));
                    $merchantAuthentication->setTransactionKey(config('app.MERCHANT_TRANSACTION_KEY'));
                    // Set the transaction's refId
                    $refId = 'ref' . time();
                    $profileToCharge = new AnetAPI\CustomerProfilePaymentType();
                    $profileToCharge->setCustomerProfileId($payment_account->profile_id);
                    $paymentProfile = new AnetAPI\PaymentProfileType();
                    $paymentProfile->setPaymentProfileId($payment_account->payment_profile_id);
                    $profileToCharge->setPaymentProfile($paymentProfile);
                    $transactionRequestType = new AnetAPI\TransactionRequestType();
                    $transactionRequestType->setTransactionType("authCaptureTransaction");
                    $transactionRequestType->setAmount(floatval(round($order->copay*1.04,2)));
                    $transactionRequestType->setProfile($profileToCharge);
                    $authorizationIndicatorType = new AnetAPI\AuthorizationIndicatorType();
                    $authorizationIndicatorType->setAuthorizationIndicator("final");
                    $transactionRequestType->setAuthorizationIndicatorType($authorizationIndicatorType);
                    $request0 = new AnetAPI\CreateTransactionRequest();
                    $request0->setMerchantAuthentication($merchantAuthentication);
                    $request0->setRefId($refId);
                    $request0->setTransactionRequest($transactionRequestType);
                    $controller = new AnetController\CreateTransactionController($request0);
                    /** @var AnetAPI\CreateTransactionResponse $response */
                    $response = $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::PRODUCTION);
                    if ($response != null) {
                        if($response->getMessages()->getResultCode() == "Ok") {
                            $tresponse = $response->getTransactionResponse();
                            if ($tresponse != null && $tresponse->getMessages() != null) {
                                DB::table('orders')->where('id',$order_id)->update(['statuse_copay'=>3]);
                                DB::table('payments')->insert(['order_id'=>$order_id,'transaction_id'=>$tresponse->getTransId(),'type'=>'copay']);
                                return response("<script>window.close();</script>");
                            } else {
                                $error= "Transaction Failed: ";
                                if($tresponse->getErrors() != null) {
                                    $error .= $tresponse->getErrors()[0]->getErrorText();
                                }
                            }
                        } else {
                            $error= "Transaction Failed: ";
                            $tresponse = $response->getTransactionResponse();
                            if($tresponse != null && $tresponse->getErrors() != null) {
                                $error .= $tresponse->getErrors()[0]->getErrorText();
                            } else {
                                $error .= $response->getMessages()->getMessage()[0]->getText();
                            }
                        }
                    } else {
                        $error= "No response returned.";
                    }
                }
                if(!empty($request->input('ncard')) && !empty($request->input('name')) && !empty($request->input('expire')) && !empty($request->input('ccv'))) {
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
                    if(!empty($payment_account)) {
                        $request0 = new AnetAPI\GetCustomerPaymentProfileRequest();
                        $request0->setMerchantAuthentication($merchantAuthentication);
                        $request0->setRefId($refId);
                        $request0->setCustomerProfileId($payment_account->profile_id);
                        $request0->setCustomerPaymentProfileId($payment_account->payment_profile_id);
                        $controller = new AnetController\GetCustomerPaymentProfileController($request0);
                        /** @var AnetAPI\GetCustomerPaymentProfileResponse $response */
                        $response = $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::PRODUCTION);
                        if (($response != null) && ($response->getMessages()->getResultCode() == "Ok")) {
                            $billTo = new AnetAPI\CustomerAddressType();
                            $billTo = $response->getPaymentProfile()->getbillTo();
                            $creditCard = new AnetAPI\CreditCardType();
                            $creditCard->setCardNumber($ncard);
                            $creditCard->setExpirationDate($expire[1].'-'.$expire[0]);
                            $creditCard->setCardCode($ccv);
                            $paymentCreditCard = new AnetAPI\PaymentType();
                            $paymentCreditCard->setCreditCard($creditCard);
                            $paymentprofile = new AnetAPI\CustomerPaymentProfileExType();
                            $paymentprofile->setBillTo($billTo);
                            $paymentprofile->setCustomerPaymentProfileId($payment_account->payment_profile_id);
                            $paymentprofile->setPayment($paymentCreditCard);
                            // Submit a UpdatePaymentProfileRequest
                            $request0 = new AnetAPI\UpdateCustomerPaymentProfileRequest();
                            $request0->setMerchantAuthentication($merchantAuthentication);
                            $request0->setCustomerProfileId($payment_account->profile_id);
                            $request0->setPaymentProfile($paymentprofile);
                            $controller = new AnetController\UpdateCustomerPaymentProfileController($request0);
                            /** @var AnetAPI\UpdateCustomerPaymentProfileResponse $response */
                            $response = $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::PRODUCTION);
                            if (($response != null) && ($response->getMessages()->getResultCode() == "Ok") )
                            {
                                $success= "New Card successfully added!";
                                DB::table('payment_accounts')->where('user_id',$user->id)->update(['card'=>$ncard]);
                            } else if ($response != null) {
                                $errorMessages = $response->getMessages()->getMessage();
                                $error=$errorMessages[0]->getText();
                            }
                        } else {
                            $error="Payment profile not found.";
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
                        $billTo->setPhoneNumber($user->phone);
                        $billTo->setfaxNumber("999-999-9999");
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
                        $customerShippingAddress->setPhoneNumber($user->phone);
                        $customerShippingAddress->setFaxNumber("999-999-9999");
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
                        $customerProfile->setDescription("Customer ".$user->id);
                        $customerProfile->setMerchantCustomerId($user->id);
                        $customerProfile->setEmail($user->email);
                        $customerProfile->setpaymentProfiles($paymentProfiles);
                        $customerProfile->setShipToList($shippingProfiles);
                        // Assemble the complete transaction request
                        $request0 = new AnetAPI\CreateCustomerProfileRequest();
                        $request0->setMerchantAuthentication($merchantAuthentication);
                        $request0->setRefId($refId);
                        $request0->setProfile($customerProfile);
                        // Create the controller and get the response
                        $controller = new AnetController\CreateCustomerProfileController($request0);
                        /** @var AnetAPI\CreateCustomerProfileResponse $response */
                        $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::PRODUCTION);
                        if (($response != null) && ($response->getMessages()->getResultCode() == "Ok")) {
                            $paymentProfiles = $response->getCustomerPaymentProfileIdList();
                            DB::table('payment_accounts')->insert(['user_id'=>$user->id,'profile_id'=>$response->getCustomerProfileId(),'payment_profile_id'=>$paymentProfiles[0],'card'=>$ncard]);
                            $success="Card successfully added!";
                        } else {
                            $errorMessages = $response->getMessages()->getMessage();
                            $error=$errorMessages[0]->getText();
                        }
                    }
                }
                $payment_account = DB::table('payment_accounts')->where('user_id',$order->user_id)->first();
                return view('card.pay-copay',['payment_account'=>$payment_account,'order'=>$order,'success'=>$success,'error'=>$error,'title'=>'Billing','br1'=>'Billings','br2'=>'List']);
            } else {
                return abort(403, AccessMessages::NO_PERMISSION);
            }
        } else {
            return abort(403, AccessMessages::NO_PERMISSION);
        }
    }
}
