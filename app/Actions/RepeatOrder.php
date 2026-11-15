<?php

namespace App\Actions;

use App\Models\Order;
use App\Models\Rx;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Creates a new order as a copy of an earlier one, with its prescriptions, for delivery
 * tomorrow. The copy starts as a new order by the signed-in person: no driver result, price,
 * photos, rating or payment state are carried over.
 */
class RepeatOrder
{
    /**
     * @return array{0: int|string, 1: int} the pharmacy's id and the new order's id
     */
    public function handle(int|string $orderId): array
    {
        $order = DB::table('orders')->where('id', $orderId)->first();
        $order->id = NULL;
        $order->medic_id = Auth::id();
        $order->statuse_id = 1;
        $order->finish = NULL;
        $order->rating = NULL;
        $order->statuse_copay = 1;
        $order->tariff = NULL;
        $order->drop_off_photo = NULL;
        $order->signature_photo = NULL;
        $order->signature_type = NULL;
        $order->invoice_payed = NULL;
        $order->eta = NULL;
        $order->delivery_date = date("Y-m-d",strtotime(date("Y-m-d H:i:s")." +1 day"));
        $pharmacy_id = $order->pharmacy_id;
        $order = json_encode($order);
        $order = json_decode($order,true);
        $order_id = Order::query()->insertGetId($order);
        $rxs = DB::table('rxs')->where('order_id',$orderId)->get();
        foreach($rxs as $rx) {
            $rx->id = NULL;
            $rx->order_id = $order_id;
            $rx = json_encode($rx);
            $rx = json_decode($rx,true);
            Rx::query()->insert($rx);
        }

        return [$pharmacy_id, $order_id];
    }
}
