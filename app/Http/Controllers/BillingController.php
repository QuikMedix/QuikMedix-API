<?php

namespace App\Http\Controllers;

use App\Actions\CalculateOrderTariff;
use App\Models\Invoice;
use App\Models\Pharmacy;
use App\Models\PharmacyPayment;
use App\Models\Order;
use App\Models\InvoiceExclusion;
use App\Http\Controllers\Concerns\RendersPages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Support\AccessMessages;

/**
 * Pharmacy billing: the billing page and its actions, invoice printing, and adding and editing
 * invoices. Formerly actions of LexaAdmin, moved as they were.
 */
class BillingController extends Controller
{
    use RendersPages;


    /**
     * @param int|string $pharmacy_id
     */
    public function billing2($pharmacy_id) {
        abort_unless((Auth::user()->hasRole('medic') && Auth::user()->pharmacy_id==$pharmacy_id) || ((Auth::user()->can('admin'))) || (Auth::user()->hasRole('driver')) || (Auth::user()->hasRole('user') && Auth::user()->pharmacy_id==$pharmacy_id), 403, AccessMessages::NO_PERMISSION);
        $orders = DB::table('orders')->join('users', 'orders.user_id', '=', 'users.id')->join('statuses', 'orders.statuse_id', '=', 'statuses.id')->join('delivery_methods', 'orders.delivery_method_id', '=', 'delivery_methods.id')->join('delivery_times', 'orders.delivery_time_id', '=', 'delivery_times.id')->join('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id')->select('orders.id', 'orders.created' , 'orders.statuse_id', 'orders.pharmacy_id', 'orders.copay', 'users.name as username', 'delivery_methods.name as delivery_method', 'delivery_times.name as delivery_time', 'users.last_name as last_name', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end as useraddress'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end as userapartment'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end as userzip'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end as userlocation'), 'users.phone as userphone', DB::raw('case when orders.tariff is null then pharmacys.tariff else orders.tariff end as tariff'),'pharmacys.name as pharmacyname', 'pharmacys.address as pharmacyaddress','pharmacys.phone as pharmacyphone', 'statuses.name as statusename','statuses.color as statusecolor')->where('orders.pharmacy_id',$pharmacy_id)->where('orders.statuse_id','4')->groupBy('orders.id', 'orders.statuse_id', 'orders.created', 'delivery_methods.name', 'delivery_times.name', 'orders.copay', 'orders.pharmacy_id', 'users.name', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end'), 'users.phone','pharmacys.name', DB::raw('case when orders.tariff is null then pharmacys.tariff else orders.tariff end'), 'pharmacys.address','pharmacys.phone', 'statuses.name','statuses.color','users.last_name')->get();
        $sum_amount = 0;
        foreach($orders as $order) {
            $sum_amount = $sum_amount+$order->tariff;
        }
        return view('billing.list2',['orders'=>$orders,'sum_amount'=>$sum_amount,'pharmacy_id'=>$pharmacy_id,'title'=>'Billing','br1'=>'Billings','br2'=>'List']);
    }

    /**
     * @param int|string $pharmacy_id
     */
    public function billing($pharmacy_id) {
        $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
        if(!empty($pharmacy)) {
            abort_unless(Auth::user()->can('manage-pharmacy', $pharmacy_id), 403, AccessMessages::NO_PERMISSION);
            $invoices = DB::table('invoices')->where('pharmacy_id',$pharmacy_id)->where("payed","0")->get();
            foreach($invoices as $invoice) {
                $invoice_exclusions = DB::table('invoice_exclusion')->where('invoice_id',$invoice->id)->pluck('order_id')->toArray();
                $orders = DB::table('orders')->where('pharmacy_id', $pharmacy_id)->where('invoice_payed','0')->whereIn('statuse_id',[4,8,9,10])->whereDate('finish', '>=', $invoice->date_from)->whereDate('finish', '<=', $invoice->date_to)->whereNotIn('id',$invoice_exclusions);
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
                Invoice::query()->where('pharmacy_id',$pharmacy_id)->where('id',$invoice->id)->update(['count'=>$count_orders, 'amount'=>$sum_amount, 'copay'=>$sum_copay]);
            }
            $invoices = DB::table('invoices')->where('pharmacy_id',$pharmacy_id)->where("payed","0")->get();
            $sum_amount = 0;
            foreach($invoices as $invoice) {
                if($pharmacy->copay_bill=='1') {
                    if((($invoice->amount+$invoice->corrections)-$invoice->copay)<0){
                        $sum_amount = $sum_amount+0;
                    } else {
                        $sum_amount = $sum_amount+(($invoice->amount+$invoice->corrections)-$invoice->copay);
                    }
                } else {
                    $sum_amount = $sum_amount+($invoice->amount+$invoice->corrections);
                }
            }
            if(floatval($sum_amount)>0) {
                $balance = floatval($sum_amount)*-1;
            } else {
                $balance = floatval($sum_amount);
            }
            $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
            if($pharmacy->balance<=0){
                Pharmacy::query()->where("id",$pharmacy_id)->update(['balance'=>$balance]);
            }
            $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
            $invoices = DB::table('invoices')->where('invoices.pharmacy_id',$pharmacy_id)->leftJoin("pharmacy_payments","pharmacy_payments.invoice_id","=","invoices.id")->select("invoices.id","invoices.created","invoices.pharmacy_id","invoices.date_from","invoices.date_to","invoices.count","invoices.amount","invoices.copay","invoices.corrections","invoices.payed","pharmacy_payments.amount as payed_amount")->groupBy("invoices.id","invoices.created","invoices.pharmacy_id","invoices.date_from","invoices.date_to","invoices.count","invoices.amount","invoices.copay","invoices.corrections","invoices.payed","pharmacy_payments.amount")->orderBy('id','desc')->get();
            $payment_account = DB::table('payment_pharmacy_accounts')->where('pharmacy_id',$pharmacy_id)->first();
            $error = session()->get('error');
            $pharmacy_plan = DB::table('plans')->where('id', $pharmacy->plan_id)->first();
            $res_view = view('billing.list',['invoices'=>$invoices,'pharmacy'=>$pharmacy,'pharmacy_plan'=>$pharmacy_plan,'payment_account'=>$payment_account,'error'=>$error,'pharmacy_id'=>$pharmacy_id,'title'=>'Billing','br1'=>'Billings','br2'=>'List']);
            return $this->page(request(), $res_view);
        } else {
            return abort(404, "Not found pharmacy");
        }
    }

    /**
     * @param int|string $pharmacy_id
     * @param int|string $invoice_id
     */
    public function billingOrders($pharmacy_id,$invoice_id) {
        $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
        if(!empty($pharmacy)) {
            abort_unless(Auth::user()->can('manage-pharmacy', $pharmacy_id), 403, AccessMessages::NO_PERMISSION);
            $invoice = DB::table('invoices')->where('pharmacy_id',$pharmacy_id)->where('id',$invoice_id)->first();
            if(empty($invoice)) {
                return abort(404, 'Invoice not found');
            }
            $invoice_exclusions = DB::table('invoice_exclusion')->where('invoice_id',$invoice_id)->pluck('order_id')->toArray();
            $orders = DB::table('orders')->join('delivery_times', 'orders.delivery_time_id', '=', 'delivery_times.id')->select("orders.id","delivery_times.name as delivery_time","orders.finish","orders.created","orders.copay","orders.tariff","orders.pharmacy_id")->where('pharmacy_id', $pharmacy_id)->whereIn('statuse_id',[4,8,9,10])->whereDate('finish', '>=', $invoice->date_from)->whereDate('finish', '<=', $invoice->date_to)->whereNotIn('id',$invoice_exclusions)->get();
            return view('billing.orders',['invoice'=>$invoice,'orders'=>$orders,'title'=>'Billing','br1'=>'Billings','br2'=>'List']);
        } else {
            return abort(404, "Not found pharmacy");
        }
    }

    /**
     * @param int|string $pharmacy_id
     * @param int|string $invoice_id
     */
    public function billingPrint($pharmacy_id,$invoice_id) {
        $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
        if(!empty($pharmacy)) {
            abort_unless(Auth::user()->can('manage-pharmacy', $pharmacy_id), 403, AccessMessages::NO_PERMISSION);
            $invoice = DB::table('invoices')->where('invoices.pharmacy_id',$pharmacy_id)->where('invoices.id',$invoice_id)->leftJoin("pharmacy_payments","pharmacy_payments.invoice_id","=","invoices.id")->select("invoices.id","invoices.created","invoices.pharmacy_id","invoices.date_from","invoices.date_to","invoices.count","invoices.amount","invoices.copay","invoices.corrections","invoices.payed","pharmacy_payments.amount as payed_amount")->first();
            if(empty($invoice)) {
                return abort(404, 'Invoice not found');
            }
            $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
            $payment_account = DB::table('payment_pharmacy_accounts')->where('pharmacy_id',$pharmacy_id)->first();
            $invoice_exclusions = DB::table('invoice_exclusion')->where('invoice_id',$invoice_id)->pluck('order_id')->toArray();
            $orders = DB::table('orders')->where('pharmacy_id', $pharmacy_id)->whereIn('statuse_id',[4,8,9,10])->whereDate('finish', '>=', $invoice->date_from)->whereDate('finish', '<=', $invoice->date_to)->whereNotIn('id',$invoice_exclusions)->get();
            $pharmacy_driver = ["amount"=>0,"count"=>0];
            $next_day = ["amount"=>0,"count"=>0];
            $same_day = ["amount"=>0,"count"=>0];
            $asap = ["amount"=>0,"count"=>0];
            $after_hours = ["amount"=>0,"count"=>0];
            foreach($orders as $order) {
                if($order->type_driver==2) {
                    $pharmacy_driver["amount"]+=$order->tariff;
                    $pharmacy_driver["count"]+=1;
                } else {
                    if($order->delivery_time_id==1) {
                        $next_day["amount"]+=$order->tariff;
                        $next_day["count"]+=1;
                    }
                    if($order->delivery_time_id==2) {
                        $same_day["amount"]+=$order->tariff;
                        $same_day["count"]+=1;
                    }
                    if($order->delivery_time_id==3) {
                        $asap["amount"]+=$order->tariff;
                        $asap["count"]+=1;
                    }
                    if($order->delivery_time_id==4) {
                        $after_hours["amount"]+=$order->tariff;
                        $after_hours["count"]+=1;
                    }
                }
            }
            return view('billing.print',['invoice'=>$invoice,'orders'=>$orders,'pharmacy'=>$pharmacy,'pharmacy_driver'=>$pharmacy_driver,'next_day'=>$next_day,'same_day'=>$same_day,'asap'=>$asap,'after_hours'=>$after_hours,'payment_account'=>$payment_account,'title'=>'Billing','br1'=>'Billings','br2'=>'List']);
        } else {
            return abort(404, "Not found pharmacy");
        }
    }

    /**
     * @param int|string $pharmacy_id
     * @param int|string $invoice_id
     */
    public function billingPrintReport($pharmacy_id,$invoice_id) {
        $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
        if(!empty($pharmacy)) {
            abort_unless(Auth::user()->can('manage-pharmacy', $pharmacy_id), 403, AccessMessages::NO_PERMISSION);
            $invoice = DB::table('invoices')->where('invoices.pharmacy_id',$pharmacy_id)->where('invoices.id',$invoice_id)->leftJoin("pharmacy_payments","pharmacy_payments.invoice_id","=","invoices.id")->select("invoices.id","invoices.created","invoices.pharmacy_id","invoices.date_from","invoices.date_to","invoices.count","invoices.amount","invoices.copay","invoices.corrections","invoices.payed","pharmacy_payments.amount as payed_amount")->first();
            if(empty($invoice)) {
                return abort(404, 'Invoice not found');
            }
            $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
            $invoice_exclusions = DB::table('invoice_exclusion')->where('invoice_id',$invoice_id)->pluck('order_id')->toArray();
            $orders = DB::table('orders')->join('delivery_times', 'orders.delivery_time_id', '=', 'delivery_times.id')->join('statuses', 'orders.statuse_id', '=', 'statuses.id')->join('users', 'orders.user_id', '=', 'users.id')->select("orders.id","delivery_times.name as delivery_time","statuses.name as status",DB::raw("CONCAT(users.name, ' ', users.last_name) as username"),"orders.finish","orders.created","orders.copay","orders.statuse_copay","orders.tariff","orders.pharmacy_id")->where('orders.pharmacy_id', $pharmacy_id)->whereIn('statuse_id',[4,8,9,10])->whereDate('finish', '>=', $invoice->date_from)->whereDate('finish', '<=', $invoice->date_to)->whereNotIn('orders.id',$invoice_exclusions)->get();
            return view('billing.report',['invoice'=>$invoice,'orders'=>$orders,'pharmacy'=>$pharmacy,'title'=>'Billing','br1'=>'Billings','br2'=>'List']);
        } else {
            return abort(404, "Not found pharmacy");
        }
    }

    /**
     * @param int|string $pharmacy_id
     */
    public function billingHandler(Request $request,$pharmacy_id) {
        $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
        if(!empty($pharmacy)) {
            abort_unless(Auth::user()->can('manage-pharmacy', $pharmacy_id), 403, AccessMessages::NO_PERMISSION);
            $error='';
            // One payment attempt per invoice at a time, so a double click cannot charge twice.
            $payment_lock = !empty($request->input('pay')) && !empty($request->input('invoice_id')) ? Cache::lock('pay-invoice:'.$pharmacy_id.':'.$request->input('invoice_id'), 120) : null;
            if($payment_lock && !$payment_lock->get()) {
                $error = "This invoice is already being paid. Please wait a moment and refresh.";
                $payment_lock = null;
            } else if($payment_lock) {
              try {
                $result = $this->payInvoice($request, $pharmacy, $pharmacy_id, $error);
                if (is_object($result)) {
                    return $result;
                }
                $error = (string) $result;
              } finally {
                $payment_lock->release();
              }
            }
            if(!empty($request->input('refill-amount'))) {
                $error = $this->refillBalance($request, $pharmacy, $pharmacy_id, $error);
            }
            if((Auth::user()->can('admin')) && !empty($request->input('paid')) && !empty($request->input('invoice_id'))) {
                $invoice = DB::table('invoices')->where('id',$request->input('invoice_id'))->where('pharmacy_id',$pharmacy_id)->first();
                if(!empty($invoice) && $invoice->payed=='1'){
                    $error = "This invoice is already paid.";
                } else if(!empty($invoice)){
                    if($pharmacy->copay_bill=='1') {
                        if((($invoice->amount+$invoice->corrections)-$invoice->copay)<0) {
                            $amount = 0;
                        } else {
                            $amount = round((($invoice->amount+$invoice->corrections)-$invoice->copay),2);
                        }
                    } else {
                        $amount = round(($invoice->amount+$invoice->corrections),2);
                    }
                    $balance = floatval($pharmacy->balance)+$amount;
                    PharmacyPayment::query()->insert(['pharmacy_id'=>$pharmacy_id,'invoice_id'=>$invoice->id,'amount'=>$amount,'transaction_id'=>'','type'=>$request->input('type')]);
                    Pharmacy::query()->where("id",$pharmacy_id)->update(['balance'=>$balance]);
                    Invoice::query()->where('id',$request->input('invoice_id'))->where('pharmacy_id',$pharmacy_id)->update(["payed"=>'1']);
                    $invoice_exclusions = DB::table('invoice_exclusion')->where('invoice_id',$request->input('invoice_id'))->pluck('order_id')->toArray();
                    Order::query()->where('pharmacy_id', $pharmacy_id)->whereIn('statuse_id',[4,8,9,10])->whereDate('finish', '>=', $invoice->date_from)->whereDate('finish', '<=', $invoice->date_to)->whereNotIn('id',$invoice_exclusions)->update(["invoice_payed"=>"1"]);
                }
            }
            if((Auth::user()->can('admin')) && is_numeric($request->input('balance-change'))) {
                if(floatval($request->input('balance-change'))===0){
                    Pharmacy::query()->where("id",$pharmacy_id)->update(["balance_ban"=>"0"]);
                }
                Pharmacy::query()->where("id",$pharmacy_id)->update(['balance'=>floatval($request->input('balance-change'))]);
            }
            if((Auth::user()->can('admin')) && is_numeric($request->input('corrections-change')) && !empty($invoice = DB::table('invoices')->where('id',$request->input('invoice_id'))->where('pharmacy_id',$pharmacy_id)->first())) {
                $corrections=floatval($request->input('corrections-change'));
                Invoice::query()->where('id',$request->input('invoice_id'))->where('pharmacy_id',$pharmacy_id)->update(["corrections"=>$corrections]);
                if($pharmacy->balance<0) {
                    $balance = floatval($pharmacy->balance)+$invoice->corrections-$corrections;
                } else {
                    $balance = floatval($pharmacy->balance)-$invoice->corrections+$corrections;
                }
                Pharmacy::query()->where("id",$pharmacy_id)->update(['balance'=>$balance]);
            }
            if((Auth::user()->can('admin')) && !empty($request->input('unblock'))) {
                if($request->input('unblock')>0){
                    Pharmacy::query()->where("id",$pharmacy_id)->update(["balance_ban"=>"0"]);
                }
            }
            if((Auth::user()->can('admin')) && !empty($request->input('remove')) && !empty($request->input('invoice_id'))) {
                $invoice = Invoice::query()->where('id',$request->input('invoice_id'))->where('pharmacy_id',$pharmacy_id)->delete();
                InvoiceExclusion::query()->where('invoice_id',$request->input('invoice_id'))->delete();
            }
            return redirect("/billing/$pharmacy_id")->with([ 'error' => $error ]);
        } else {
            return abort(404, "Not found pharmacy");
        }
    }

    /**
     * @param int|string $pharmacy_id
     */
    public function billingInvoiceAdd($pharmacy_id) {
        $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
        if(!empty($pharmacy)) {
            abort_unless((Auth::user()->can('admin')), 403, AccessMessages::NO_PERMISSION);
            $invoice = new \stdClass();
            $invoice->date_from="";
            $invoice->date_to="";
            $invoice_exclusions=[];
            $res_view = view('billing.invoice_form',['invoice'=>$invoice,'invoice_exclusions'=>$invoice_exclusions,'alert'=>'','title'=>'Billing Invoice Add','br1'=>'Billings','br2'=>'Invoice Add']);
            return $this->page(request(), $res_view);
        } else {
            return abort(404, "Not found pharmacy");
        }
    }

    /**
     * @param int|string $pharmacy_id
     */
    public function billingInvoiceAddHandler($pharmacy_id,Request $request) {
        $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
        if(!empty($pharmacy)) {
            abort_unless((Auth::user()->can('admin')), 403, AccessMessages::NO_PERMISSION);
            if($request->input('save')>0 && !empty($request->input('date_from')) && !empty($request->input('date_to'))) {
                $orders = DB::table('orders')->where('pharmacy_id', $pharmacy_id)->where('invoice_payed','0')->whereIn('statuse_id',[4,8,9,10])->whereDate('finish', '>=', date($request->input('date_from')))->whereDate('finish', '<=', date($request->input('date_to')));
                $invoice_exclusions = [];
                if(!empty($request->input('invoice_exclusions'))){
                    $invoice_exclusions = array_filter(explode("\n",$request->input('invoice_exclusions')));
                    $orders=$orders->whereNotIn('id',$invoice_exclusions);
                }
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
                    } else {
                        $pharmacy=DB::table('pharmacys')->where('pharmacys.id',$order->pharmacy_id)->first();
                        $tariff_res = app(CalculateOrderTariff::class)->handle($order);
                        Order::query()->where('id', $order->id)->update(['tariff'=>$tariff_res]);
                        $sum_amount = $sum_amount+$tariff_res;
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
                Pharmacy::query()->where("id",$pharmacy_id)->update(['balance'=>$balance]);
                $invoice_id = Invoice::query()->insertGetId(['pharmacy_id'=>$pharmacy_id,'date_from' => $request->input('date_from'),'date_to' => $request->input('date_to'), 'count'=>$count_orders, 'amount'=>$sum_amount, 'copay'=>$sum_copay]);
                if(!empty($request->input('invoice_exclusions'))){
                    foreach($invoice_exclusions as $invoice_exclusion){
                        if(!empty($invoice_exclusion)){
                            InvoiceExclusion::query()->insert(['invoice_id' => $invoice_id,'order_id' => intval(preg_replace('/\s+/', '', $invoice_exclusion))]);
                        }
                    }
                }
            }
            return redirect("billing/$pharmacy_id");
        } else {
            return abort(404, "Not found pharmacy");
        }
    }

    /**
     * @param int|string $pharmacy_id
     * @param int|string $invoice_id
     */
    public function billingInvoiceEdit($pharmacy_id,$invoice_id) {
        $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
        if(!empty($pharmacy)) {
            abort_unless((Auth::user()->can('admin')), 403, AccessMessages::NO_PERMISSION);
            $invoice = DB::table('invoices')->where('id', $invoice_id)->where('pharmacy_id', $pharmacy_id)->first();
            if(empty($invoice)) {
                return abort(404, 'Invoice not found');
            }
            $invoice_exclusions = DB::table('invoice_exclusion')->where('invoice_id', $invoice_id)->get();
            $res_view = view('billing.invoice_form',['invoice'=>$invoice,'invoice_exclusions'=>$invoice_exclusions,'alert'=>'','title'=>'Billing Invoice Edit','br1'=>'Billings','br2'=>'Invoice Edit']);
            return $this->page(request(), $res_view);
        } else {
            return abort(404, "Not found pharmacy");
        }
    }

    /**
     * @param int|string $pharmacy_id
     * @param int|string $invoice_id
     */
    public function billingInvoiceEditHandler($pharmacy_id,$invoice_id, Request $request) {
        $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
        if(!empty($pharmacy)) {
            abort_unless((Auth::user()->can('admin')), 403, AccessMessages::NO_PERMISSION);
            if($request->input('save')>0 && !empty($request->input('date_from')) && !empty($request->input('date_to')) && !empty($request->input('tariff'))) {
                $count_orders = DB::table('orders')->where('pharmacy_id', $pharmacy_id)->whereIn('statuse_id',[4,8,9,10])->whereDate('created', '>=', date($request->input('date_from')))->whereDate('created', '<=', date($request->input('date_to')));
                $invoice_exclusions = [];
                if(!empty($request->input('invoice_exclusions'))){
                    $invoice_exclusions = array_filter(explode("\n",$request->input('invoice_exclusions')));
                    $count_orders=$count_orders->whereNotIn('id',$invoice_exclusions);
                }
                $count_orders=$count_orders->count();
                Invoice::query()->where('id', $invoice_id)->where('pharmacy_id', $pharmacy_id)->update(['date_from' => $request->input('date_from'),'date_to' => $request->input('date_to'), 'count'=>$count_orders, 'tariff'=>floatval($request->input('tariff'))]);
                InvoiceExclusion::query()->where('invoice_id', $invoice_id)->delete();
                if(!empty($request->input('invoice_exclusions'))){
                    foreach($invoice_exclusions as $invoice_exclusion){
                        if(!empty($invoice_exclusion)){
                            InvoiceExclusion::query()->insert(['invoice_id' => $invoice_id,'order_id' => intval(preg_replace('/\s+/', '', $invoice_exclusion))]);
                        }
                    }
                }
            }
            return redirect("billing/$pharmacy_id");
        } else {
            return abort(404, "Not found pharmacy");
        }
    }

    /**
     * Pays one invoice from the pharmacy's saved payment method.
     *
     * @param  object  $pharmacy
     * @param  int|string  $pharmacy_id
     * @return mixed the result page when a charge was attempted, otherwise the message for the billing page
     */
    private function payInvoice(Request $request, $pharmacy, $pharmacy_id, string $error)
    {
        $invoice = DB::table('invoices')->where('id',$request->input('invoice_id'))->where('pharmacy_id',$pharmacy_id)->first();
        $payment_account = DB::table('payment_pharmacy_accounts')->where('pharmacy_id',$pharmacy_id)->first();
        if(!empty($invoice) && $invoice->payed=='1'){
            $error = "This invoice is already paid.";
        } else if(!empty($invoice)){
            if($pharmacy->copay_bill=='1') {
                if((($invoice->amount+$invoice->corrections)-$invoice->copay)<0) {
                    $amount = 0;
                } else {
                    $amount = round((($invoice->amount+$invoice->corrections)-$invoice->copay),2);
                }
            } else {
                $amount = round(($invoice->amount+$invoice->corrections),2);
            }
            // Creating an invoice already debited the balance; a balance of zero or more means it is covered.
            if($amount==0 || floatval($pharmacy->balance)>=0) {
                PharmacyPayment::query()->insert(['pharmacy_id'=>$pharmacy_id,'invoice_id'=>$invoice->id,'amount'=>$amount,'transaction_id'=>'balance','type'=>'pay']);
                Invoice::query()->where('id',$request->input('invoice_id'))->where('pharmacy_id',$pharmacy_id)->update(["payed"=>'1']);
                $invoice_exclusions = DB::table('invoice_exclusion')->where('invoice_id',$request->input('invoice_id'))->pluck('order_id')->toArray();
                Order::query()->where('pharmacy_id', $pharmacy_id)->whereIn('statuse_id',[4,8,9,10])->whereDate('finish', '>=', $invoice->date_from)->whereDate('finish', '<=', $invoice->date_to)->whereNotIn('id',$invoice_exclusions)->update(["invoice_payed"=>"1"]);
            } else if(!empty($payment_account) && $payment_account->type=="card" && !empty($payment_account->payment_profile_id)){
                $client = app(\Square\SquareClient::class);
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
                $body->setNote('Invoice #'.$invoice->id);
                $api_response = $client->getPaymentsApi()->createPayment($body);
                if ($api_response->isSuccess()) {
                    $result = $api_response->getResult();
                    $payment_status = $result->getPayment()->getStatus();
                    if($payment_status=="COMPLETED" || $payment_status=="APPROVED") {
                        $balance = floatval($pharmacy->balance)+$amount;
                        PharmacyPayment::query()->insert(['pharmacy_id'=>$pharmacy_id,'invoice_id'=>$invoice->id,'amount'=>$amount,'transaction_id'=>$result->getPayment()->getId(),'type'=>'pay']);
                        Pharmacy::query()->where("id",$pharmacy_id)->update(['balance'=>$balance]);
                        Invoice::query()->where('id',$request->input('invoice_id'))->where('pharmacy_id',$pharmacy_id)->update(["payed"=>'1']);
                        $invoice_exclusions = DB::table('invoice_exclusion')->where('invoice_id',$request->input('invoice_id'))->pluck('order_id')->toArray();
                        Order::query()->where('pharmacy_id', $pharmacy_id)->whereIn('statuse_id',[4,8,9,10])->whereDate('finish', '>=', $invoice->date_from)->whereDate('finish', '<=', $invoice->date_to)->whereNotIn('id',$invoice_exclusions)->update(["invoice_payed"=>"1"]);
                    } else {
                        $error = "Payment not completed!";
                    }
                } else {
                    $error = json_encode($api_response->getErrors());
                }
            } else if(!empty($payment_account) && $payment_account->type=="bank") {
                if(!empty($request->input('card')) && !empty($request->input('name'))) {
                    $card_token = $request->input('card');
                    $name=explode(' ',$request->input('name'));
                    if(count($name)==1) {
                        $name[1]="";
                    }
                    $client = app(\Square\SquareClient::class);
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
                    $body->setNote('Invoice #'.$invoice->id);
                    $api_response = $client->getPaymentsApi()->createPayment($body);
                    if ($api_response->isSuccess()) {
                        $result = $api_response->getResult();
                        $payment_status = $result->getPayment()->getStatus();
                        if($payment_status=="COMPLETED" || $payment_status=="APPROVED" || $payment_status=="PENDING") {
                            $balance = floatval($pharmacy->balance)+$amount;
                            PharmacyPayment::query()->insert(['pharmacy_id'=>$pharmacy_id,'invoice_id'=>$invoice->id,'amount'=>$amount,'transaction_id'=>$result->getPayment()->getId(),'type'=>'pay']);
                            Pharmacy::query()->where("id",$pharmacy_id)->update(['balance'=>$balance]);
                            Invoice::query()->where('id',$request->input('invoice_id'))->where('pharmacy_id',$pharmacy_id)->update(["payed"=>'1']);
                            $invoice_exclusions = DB::table('invoice_exclusion')->where('invoice_id',$request->input('invoice_id'))->pluck('order_id')->toArray();
                            Order::query()->where('pharmacy_id', $pharmacy_id)->whereIn('statuse_id',[4,8,9,10])->whereDate('finish', '>=', $invoice->date_from)->whereDate('finish', '<=', $invoice->date_to)->whereNotIn('id',$invoice_exclusions)->update(["invoice_payed"=>"1"]);
                        } else {
                            $error = "Payment not completed!";
                        }
                    } else {
                        $error = json_encode($api_response->getErrors());
                    }
                } else {
                    $payment_account = DB::table('payment_pharmacy_accounts')->where('pharmacy_id',$pharmacy_id)->first();
                    $invoice = DB::table('invoices')->where('id',$request->input('invoice_id'))->where('pharmacy_id',$pharmacy_id)->first();
                    $success='';
                    $error='';
                    return view('card_pharmacy.pay',['payment_account'=>$payment_account,'invoice'=>$invoice,'success'=>$success,'error'=>$error,'title'=>'Cards','br1'=>'Cards','br2'=>'Card','br3'=>'Cards','alert'=>'']);
                }
            } else {
                $error = "PAYMENT METHOD IS EMPTY! PLEASE, ADD YOUR PAYMENT METHOD.";
            }
        } else {
            $error = "Invoice with this ID not found!";
        }

        return $error;
    }

    /**
     * Adds money to the pharmacy's balance from its saved payment method.
     *
     * @param  object  $pharmacy
     * @param  int|string  $pharmacy_id
     * @return string the message for the billing page, unchanged when the refill went through
     */
    private function refillBalance(Request $request, $pharmacy, $pharmacy_id, string $error): string
    {
        $payment_account = DB::table('payment_pharmacy_accounts')->where('pharmacy_id',$pharmacy_id)->first();
        $amount = round(floatval($request->input('refill-amount')),2);
        if($amount < 1) {
            $error = "Enter a refill amount of at least $1.00.";
        } else if(!empty($payment_account)){
            if(!empty($payment_account) && $payment_account->type=="card" && !empty($payment_account->payment_profile_id)){
                $client = app(\Square\SquareClient::class);
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
                $body->setNote('Refill #'.$pharmacy_id);
                $api_response = $client->getPaymentsApi()->createPayment($body);
                if ($api_response->isSuccess()) {
                    $result = $api_response->getResult();
                    $payment_status = $result->getPayment()->getStatus();
                    if($payment_status=="COMPLETED" || $payment_status=="APPROVED") {
                        $balance = floatval($pharmacy->balance)+$amount;
                        PharmacyPayment::query()->insert(['pharmacy_id'=>$pharmacy_id,'amount'=>$amount,'transaction_id'=>$result->getPayment()->getId(),'type'=>'refill']);
                        Pharmacy::query()->where("id",$pharmacy_id)->update(['balance'=>$balance]);
                    } else {
                        $error = "Payment not completed!";
                    }
                } else {
                    $error = json_encode($api_response->getErrors());
                }
            } else if(!empty($payment_account) && $payment_account->type=="bank") {
                $error = "PAYMENT METHOD BANK ACCOUNT CAN`T REFILL BALANCE.";
            } else {
                $error = "PAYMENT METHOD IS EMPTY! PLEASE, ADD YOUR PAYMENT METHOD.";
            }
        } else {
            $error = "PAYMENT METHOD IS EMPTY! PLEASE, ADD YOUR PAYMENT METHOD.";
        }

        return $error;
    }
}
