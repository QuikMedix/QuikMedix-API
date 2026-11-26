<?php

namespace App\Console\Tasks;

use Illuminate\Support\Facades\DB;

/**
 * Creates last week's invoice for every pharmacy with finished orders and pays it from the balance when that covers it. Scheduled in routes/console.php.
 */
class CreateWeeklyInvoices
{
    public function __invoke(): void
    {
        $date_from = date('Y-m-d', strtotime('now -7 day'));
        $date_to = date('Y-m-d', strtotime('now -1 day'));
        $pharmacys = DB::table('orders')->whereDate('finish', '>=', $date_from)->whereDate('finish', '<=', $date_to)->groupBy("pharmacy_id")->pluck('pharmacy_id')->toArray();
        foreach($pharmacys as $pharmacy_id) {
            try {
                $pharmacy = DB::table('pharmacys')->where("id",$pharmacy_id)->first();
                $orders = DB::table('orders')->where('pharmacy_id', $pharmacy_id)->where('invoice_payed','0')->whereIn('statuse_id',[4,8,9,10])->whereDate('finish', '>=', $date_from)->whereDate('finish', '<=', $date_to);
                $count_orders=$orders->count();
                $sum_amount = 0;
                $sum_copay = 0;
                $orders = $orders->get();
                foreach($orders as $order) {
                    if(!empty($order->tariff)) {
                        $sum_amount = $sum_amount+$order->tariff;
                        if(\App\Support\CopayStatus::isCollected($order->statuse_copay)){
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
                $invoice_id = DB::table('invoices')->insertGetId(['pharmacy_id'=>$pharmacy_id,'date_from' => $date_from,'date_to' => $date_to, 'count'=>$count_orders, 'amount'=>$sum_amount, 'copay'=>$sum_copay]);
                DB::table('pharmacys')->where("id",$pharmacy_id)->update(['balance'=>$balance]);
                if(floatval($pharmacy->balance)>=$amount2) {
                    DB::table('pharmacy_payments')->insert(['pharmacy_id'=>$pharmacy_id,'invoice_id'=>$invoice_id,'amount'=>$amount2,'transaction_id'=>'balance','type'=>'pay']);
                    DB::table('invoices')->where('id',$invoice_id)->where('pharmacy_id',$pharmacy_id)->update(["payed"=>'1']);
                    $invoice_exclusions = DB::table('invoice_exclusion')->where('invoice_id',$invoice_id)->pluck('order_id')->toArray();
                    DB::table('orders')->where('pharmacy_id', $pharmacy_id)->whereIn('statuse_id',[4,8,9,10])->whereDate('finish', '>=', $date_from)->whereDate('finish', '<=', $date_to)->whereNotIn('id',$invoice_exclusions)->update(["invoice_payed"=>"1"]);
                }
            } catch (\Throwable $exception) {
                // The work around it continues, but the failure is no longer lost.
                report($exception);
            }
        }
    }
}
