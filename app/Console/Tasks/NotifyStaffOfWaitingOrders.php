<?php

namespace App\Console\Tasks;

use App\Models\User;
use App\Support\Notifications;
use Illuminate\Support\Facades\DB;

/**
 * Tells admins and logists when a pharmacy has five or more orders waiting for pickup. Scheduled in routes/console.php.
 */
class NotifyStaffOfWaitingOrders
{
    public function __invoke(): void
    {
        $orders = DB::table('orders')->join('users', 'orders.user_id', '=', 'users.id')->join('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id')->select(DB::raw('count(distinct orders.id) as count'),"orders.pharmacy_id","pharmacys.name")->where('orders.statuse_id', '1')->groupBy("orders.pharmacy_id","pharmacys.name")->get();
        foreach($orders as $order) {
            if($order->count>=5){
                $notify = DB::table('notifications')->where('type_text', 'five_orders')->where('link',url("/orders/".$order->pharmacy_id)."?statuse%5B%5D=1")->whereBetween("created",[DB::raw('DATE_SUB(NOW(), INTERVAL 2 HOUR)'),DB::raw('NOW()')])->first();
                if(empty($notify)) {
                    Notifications::send_push_web(array_map('strval', User::where('role', "admin")->orWhere("role","logist")->pluck('id')->toArray()),
                        "Attention!",
                        "The pharmacy '".$order->name."' has already accumulated ".$order->count." orders, it's time to send drivers for them",
                        url("/orders/".$order->pharmacy_id)."?statuse%5B%5D=1",
                        "five_orders"
                    );
                }
            }
        }
    }
}
