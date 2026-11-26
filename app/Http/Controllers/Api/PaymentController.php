<?php

namespace App\Http\Controllers\Api;

use App\Actions\SaveCardOnFile;
use App\Http\Controllers\Controller;
use App\Models\CashLog;
use App\Models\Order;
use App\Models\PaymentAccount;
use App\Models\RouteStop;
use App\Support\CopayStatus;
use App\Support\SquarePayments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

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

    public function cardAddHandler(Request $request, SaveCardOnFile $saveCard) {
        if (! Auth::user()->hasAnyRole('admin', 'user', 'driver')) {
            return response()->json(['message' => 'You cannot open this page', 'errors' => 'Forbidden'], 403);
        }
        if (empty($request->input('name')) || empty($request->input('address')) || empty($request->input('zip')) || empty($request->input('card'))) {
            return response()->json(['message' => 'All card input is required.', 'errors' => 'Bad Request'], 400);
        }
        $user = Auth::user();
        $error = $saveCard->handle(
            account: PaymentAccount::query()->firstOrNew(['user_id' => $user->id]),
            type: 'card',
            owner: ['email' => $user->email, 'phone' => $user->phone, 'reference' => (string) $user->id, 'note' => 'User #'.$user->id],
            holder: ['name' => (string) $request->input('name'), 'address' => (string) $request->input('address'), 'zip' => (string) $request->input('zip')],
            cardToken: (string) $request->input('card'),
        );

        return $error === null
            ? response()->json(['message' => 'Payment Method successfully added!'], 200)
            : response()->json(['message' => $error, 'errors' => 'Bad Request'], 400);
    }

    /**
     * @param int|string $order_id
     */
    public function payCopay($order_id, SquarePayments $payments) {
        // One attempt per order at a time, so a double tap cannot charge the card twice.
        $response = Cache::lock('pay-copay:'.$order_id, 120)->get(function () use ($order_id, $payments) {
            $order = Order::query()->where('id',$order_id)->first();
            if(!empty($order)) {
                if(!in_array($order->statuse_id,[1,4,5,8,9,10]) && (Auth::user()->hasRole('user')) && $order->user_id==Auth::user()->id && !CopayStatus::isSettled($order->statuse_copay) && $order->copay>0) {
                    $payment_account = PaymentAccount::query()->where('user_id',$order->user_id)->first();
                    if(!empty($payment_account) && $payment_account->type=="card" && !empty($payment_account->payment_profile_id)){
                        $charge = $payments->charge($payment_account->payment_profile_id, $payment_account->profile_id, floatval($order->copay), 'Pay Copay #'.$order->id);
                        if ($charge->completed()) {
                            Order::query()->where('id',$order_id)->update(['statuse_copay'=>3]);
                            DB::table('payments')->insert(['order_id'=>$order_id,'transaction_id'=>$charge->paymentId,'type'=>'copay']);
                            return response()->json([
                                'message' => 'OK'
                            ], 200);
                        }
                        return response()->json([
                            'message' => $charge->errors === null ? 'Payment not completed!' : 'Transaction Failed: '.$charge->errors,
                            'errors' => 'Error'
                        ], 400);
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
     * The driver took the co-pay in cash at the door.
     *
     * @param int|string $order_id
     */
    public function PayedCashCopay($order_id) {
        return $this->collectedAtTheDoor($order_id, CopayStatus::PAID_IN_CASH, 'cash');
    }

    /**
     * The driver took the co-pay as a check at the door. A check is handled like cash: it is logged
     * against the driver, who gets a stop to bring it back.
     *
     * @param int|string $order_id
     */
    public function PayedCheckCopay($order_id) {
        return $this->collectedAtTheDoor($order_id, CopayStatus::PAID_BY_CHECK, 'check');
    }

    /**
     * @param int|string $order_id
     */
    private function collectedAtTheDoor($order_id, int $status, string $method) {
        $order = Order::query()->where('id',$order_id)->first();
        if(!empty($order)) {
            if(!in_array($order->statuse_id,[1,4,5,8,9,10]) && (Auth::user()->hasRole('driver')) && $order->driver_id==Auth::user()->id && !CopayStatus::isSettled($order->statuse_copay) && $order->copay>0) {
                $cash_log = CashLog::query()->where("order_id",$order_id)->where("driver_id",Auth::user()->id)->first();
                if(!empty($cash_log)) {
                    CashLog::query()->where('id',$cash_log->id)->update(["copay"=>$order->copay,"method"=>$method]);
                } else {
                    CashLog::query()->insert(["order_id"=>$order_id,"driver_id"=>Auth::user()->id,"copay"=>$order->copay,"method"=>$method]);
                }
                Order::query()->where('id',$order_id)->update(['statuse_copay'=>$status]);
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
            if(!in_array($order->statuse_id,[1,4,5,8,9,10]) && (Auth::user()->hasRole('driver')) && $order->driver_id==Auth::user()->id && !CopayStatus::isSettled($order->statuse_copay) && $order->copay>0) {
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

    /**
     * The patient says how they will pay this order's co-pay: cash or a check to the driver, or by
     * card in the app. The driver sees the choice on the stop. Choosing does not pay anything.
     *
     * @param int|string $order_id
     */
    public function chooseCopayMethod(Request $request, $order_id) {
        $order = Order::query()->where('id', $order_id)->first();
        if (empty($order) || ! Auth::user()->hasRole('user') || $order->user_id != Auth::user()->id) {
            // Another patient's order is answered like one that does not exist.
            return response()->json(['message' => 'Order not found', 'errors' => 'Not Found'], 404);
        }
        $method = (string) $request->input('method');
        if (! in_array($method, CopayStatus::METHODS, true)) {
            return response()->json(['message' => 'Choose cash, check or card.', 'errors' => 'Bad Request'], 400);
        }
        if ($order->copay <= 0 || CopayStatus::isSettled($order->statuse_copay)) {
            return response()->json(['message' => 'This order has no co-pay left to pay.', 'errors' => 'Forbidden'], 403);
        }
        Order::query()->where('id', $order->id)->update(['copay_method' => $method]);

        return response()->json(['message' => 'OK', 'copay_method' => $method], 200);
    }

    /**
     * A link to the page where the patient types a card into Square's own form. The app opens it in
     * a browser sheet, so no card number passes through the app or this API. The link names the
     * account, is signed, and stops working after fifteen minutes.
     */
    public function cardPageLink() {
        if (! Auth::user()->hasRole('user')) {
            return response()->json(['message' => 'You cannot open this page', 'errors' => 'Forbidden'], 403);
        }

        return response()->json([
            // Signed without the host, so it is checked the same way behind the load balancer.
            'url' => url(URL::temporarySignedRoute('patient.card', now()->addMinutes(15), ['user' => Auth::user()->id], absolute: false)),
        ], 200);
    }
}
