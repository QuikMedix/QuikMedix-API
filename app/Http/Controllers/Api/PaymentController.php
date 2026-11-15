<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashLog;
use App\Models\Order;
use App\Models\PaymentAccount;
use App\Models\RouteStop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Saved cards and copay payment in the mobile apps.
 */
class PaymentController extends Controller
{
    public function cards() {
        if(Auth::user()->hasAnyRole('admin', 'user', 'driver')) {
            $payment_account = PaymentAccount::query()->where('user_id',Auth::user()->id)->first();
            if(!empty($payment_account)) {
                return response()->json([
                    'card' => "XXXX".substr($payment_account->card,-4)
                ], 200);
            } else {
                return response()->json([
                    'card' => ""
                ], 200);
            }
        } else {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        }
    }

    public function cardAddHandler(Request $request) {
        if(Auth::user()->hasAnyRole('admin', 'user', 'driver')) {
            if(!empty($request->input('name')) && !empty($request->input('address')) && !empty($request->input('zip'))) {
                $card_token = $request->input('card');
                $type = 'card';
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
                $payment_account = PaymentAccount::query()->where('user_id',Auth::user()->id)->first();
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
                    $body->setEmailAddress(Auth::user()->email);
                    $body->setAddress($address);
                    $body->setPhoneNumber(str_replace(" ","",str_replace("-","",str_replace(")","",str_replace("(","",Auth::user()->phone)))));
                    $body->setReferenceId(Auth::user()->id);
                    $body->setNote('User #'.Auth::user()->id);
                    $api_response = $client->getCustomersApi()->createCustomer($body);
                    if ($api_response->isSuccess()) {
                        $result = $api_response->getResult();
                        PaymentAccount::query()->insert(['user_id'=>Auth::user()->id,"type"=>$type,"profile_id"=>$result->getCustomer()->getId()]);
                        $payment_account = PaymentAccount::query()->where('user_id',Auth::user()->id)->first();
                    } else {
                        $error = json_encode($api_response->getErrors());
                        return response()->json([
                            'message' => $error,
                            'errors' => 'Bad Request'
                        ], 400);
                    }
                } else {
                    PaymentAccount::query()->where('user_id',Auth::user()->id)->update(["type"=>$type]);
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
                            PaymentAccount::query()->where('user_id',Auth::user()->id)->update(["payment_id"=>$payment_id]);
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
                                PaymentAccount::query()->where('user_id',Auth::user()->id)->update(["payment_profile_id"=>$result->getCard()->getId(),"card"=>$result->getCard()->getLast4()]);
                                $body = new \Square\Models\RefundPaymentRequest($unid, $amount_money);
                                $body->setPaymentId($payment_id);
                                $api_response = $client->getRefundsApi()->refundPayment($body);
                                if ($api_response->isSuccess()) {
                                    $success="Payment Method successfully added!";
                                    return response()->json([
                                        'message' => $success
                                    ], 200);
                                } else {
                                    $error = "Payment Method successfully added, but refund payment not completed. ".json_encode($api_response->getErrors());
                                    return response()->json([
                                        'message' => $error,
                                        'errors' => 'Bad Request'
                                    ], 400);
                                }
                            } else {
                                $error = json_encode($api_response->getErrors());
                                return response()->json([
                                    'message' => $error,
                                    'errors' => 'Bad Request'
                                ], 400);
                            }
                        } else {
                            $error = "Payment not completed!";
                            return response()->json([
                                'message' => $error,
                                'errors' => 'Bad Request'
                            ], 400);
                        }
                    } else {
                        $error = json_encode($api_response->getErrors());
                        return response()->json([
                            'message' => $error,
                            'errors' => 'Bad Request'
                        ], 400);
                    }
                }
            } else {
                return response()->json([
                    'message' => 'All card input is required.',
                    'errors' => 'Bad Request'
                ], 400);
            }
        } else {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden'
            ], 403);
        }
    }

    /**
     * @param int|string $order_id
     */
    public function payCopay($order_id) {
        // One attempt per order at a time, so a double tap cannot charge the card twice.
        $response = Cache::lock('pay-copay:'.$order_id, 120)->get(function () use ($order_id) {
            $order = Order::query()->where('id',$order_id)->first();
            if(!empty($order)) {
                if(!in_array($order->statuse_id,[1,4,5,8,9,10]) && (Auth::user()->hasRole('user')) && $order->user_id==Auth::user()->id && !in_array($order->statuse_copay,[1,3,4,6]) && $order->copay>0) {
                    $payment_account = PaymentAccount::query()->where('user_id',$order->user_id)->first();
                    if(!empty($payment_account) && $payment_account->type=="card" && !empty($payment_account->payment_profile_id)){
                        $client = new \Square\SquareClient([
                            'accessToken' => config('app.SQUARE_ACCESS_TOKEN'),
                            'environment' => config('app.SQUARE_ENVIRONMENT'),
                        ]);
                        $amount = floatval($order->copay);
                        $amount_money = new \Square\Models\Money();
                        $amount_money->setAmount(\App\Support\Money::cents($amount));
                        $amount_money->setCurrency('USD');
                        $unid = uniqid("",true).rand(0,100);
                        $body = new \Square\Models\CreatePaymentRequest(
                            $payment_account->payment_profile_id,
                            $unid
                        );
                        $body->setAmountMoney($amount_money);
                        $body->setAutocomplete(true);
                        $body->setCustomerId($payment_account->profile_id);
                        $body->setLocationId(config('app.SQUARE_LOCATION_ID'));
                        $body->setNote('Pay Copay #'.$order->id);
                        $api_response = $client->getPaymentsApi()->createPayment($body);
                        if ($api_response->isSuccess()) {
                            $result = $api_response->getResult();
                            $payment_status = $result->getPayment()->getStatus();
                            if($payment_status=="COMPLETED" || $payment_status=="APPROVED") {
                                Order::query()->where('id',$order_id)->update(['statuse_copay'=>3]);
                                DB::table('payments')->insert(['order_id'=>$order_id,'transaction_id'=>$result->getPayment()->getId(),'type'=>'copay']);
                                return response()->json([
                                    'message' => 'OK'
                                ], 200);
                            } else {
                                return response()->json([
                                    'message' => 'Payment not completed!',
                                    'errors' => 'Error'
                                ], 400);
                            }
                        } else {
                            $error = json_encode($api_response->getErrors());
                            return response()->json([
                                'message' => 'Transaction Failed: '.$error,
                                'errors' => 'Error'
                            ], 400);
                        }
                    } else {
                        return response()->json([
                            'message' => 'Card not added',
                            'errors' => 'Forbidden'
                        ], 403);
                    }
                } else {
                    return response()->json([
                        'message' => 'You cannot open this page',
                        'errors' => 'Forbidden'
                    ], 403);
                }
            } else {
                return response()->json([
                    'message' => 'Order not found',
                    'errors' => 'Not Found'
                ], 404);
            }
        });

        return $response === false ? response()->json([
            'message' => 'This copay is already being paid.',
            'errors' => 'Conflict'
        ], 409) : $response;
    }

    /**
     * @param int|string $order_id
     */
    public function PayedCashCopay($order_id) {
        $order = Order::query()->where('id',$order_id)->first();
        if(!empty($order)) {
            if(!in_array($order->statuse_id,[1,4,5,8,9,10]) && (Auth::user()->hasRole('driver')) && $order->driver_id==Auth::user()->id && !in_array($order->statuse_copay,[1,3,4,6]) && $order->copay>0) {
                $cash_log = CashLog::query()->where("order_id",$order_id)->where("driver_id",Auth::user()->id)->first();
                if(!empty($cash_log)) {
                    CashLog::query()->where('id',$cash_log->id)->update(["copay"=>$order->copay]);
                } else {
                    CashLog::query()->insert(["order_id"=>$order_id,"driver_id"=>Auth::user()->id,"copay"=>$order->copay]);
                }
                Order::query()->where('id',$order_id)->update(['statuse_copay'=>4]);
                if(empty(Auth::user()->pharmacy_id)) {
                    $next_office = RouteStop::query()->where('driver_id',Auth::user()->id)->where('type','office')->first();
                    if(!empty($next_office)) {
                        RouteStop::query()->insert(['driver_id'=>Auth::user()->id,'order_id'=>$order_id,'type'=>'office','type_id'=>$next_office->type_id,'type_pay'=>$next_office->type_pay,'pay_value'=>$next_office->pay_value,'priority'=>$next_office->priority]);
                    } else {
                        $last_route = RouteStop::query()->where('driver_id',Auth::user()->id)->max('priority');
                        $route0=RouteStop::query()->where('order_id',$order_id)->where('driver_id',Auth::user()->id)->where('type','patient')->first();
                        if(!empty($route0)) {
                            RouteStop::query()->insert(['driver_id'=>Auth::user()->id,'order_id'=>$order_id,'type'=>'office','type_id'=>1,'type_pay'=>$route0->type_pay,'pay_value'=>$route0->pay_value,'priority'=>(intval($last_route)+1)]);
                        }
                    }
                } else {
                    $next_office = RouteStop::query()->where('driver_id',Auth::user()->id)->where('type','pharmacy')->where('type_id',Auth::user()->pharmacy_id)->first();
                    if(!empty($next_office)) {
                        RouteStop::query()->insert(['driver_id'=>Auth::user()->id,'order_id'=>$order_id,'type'=>'pharmacy','type_id'=>$next_office->type_id,'type_pay'=>$next_office->type_pay,'pay_value'=>$next_office->pay_value,'priority'=>$next_office->priority]);
                    } else {
                        $last_route = RouteStop::query()->where('driver_id',Auth::user()->id)->max('priority');
                        $route0=RouteStop::query()->where('order_id',$order_id)->where('driver_id',Auth::user()->id)->where('type','patient')->first();
                        if(!empty($route0)) {
                            RouteStop::query()->insert(['driver_id'=>Auth::user()->id,'order_id'=>$order_id,'type'=>'pharmacy','type_id'=>Auth::user()->pharmacy_id,'type_pay'=>$route0->type_pay,'pay_value'=>$route0->pay_value,'priority'=>(intval($last_route)+1)]);
                        }
                    }
                }
                return response()->json([
                    'message' => 'OK'
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Order can not be payed',
                    'errors' => 'Forbidden'
                ], 403);
            }
        } else {
            return response()->json([
                'message' => 'Order not found',
                'errors' => 'Not Found'
            ], 404);
        }
    }

    /**
     * @param int|string $order_id
     */
    public function notPayedCashCopay($order_id) {
        $order = Order::query()->where('id',$order_id)->first();
        if(!empty($order)) {
            if(!in_array($order->statuse_id,[1,4,5,8,9,10]) && (Auth::user()->hasRole('driver')) && $order->driver_id==Auth::user()->id && !in_array($order->statuse_copay,[1,3,4,6]) && $order->copay>0) {
                Order::query()->where('id',$order_id)->update(['statuse_copay'=>5]);
                return response()->json([
                    'message' => 'OK'
                ], 200);
            } else {
                return response()->json([
                    'message' => 'You cannot open this page',
                    'errors' => 'Forbidden'
                ], 403);
            }
        } else {
            return response()->json([
                'message' => 'Order not found',
                'errors' => 'Not Found'
            ], 404);
        }
    }
}
