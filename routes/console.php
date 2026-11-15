<?php

use App\Actions\RecalculateDriverEta;
use App\Support\Notifications;
use App\Support\HereMaps;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('push:test {user_id? : Send a test notification to this user\'s device}', function () {
    $error = \App\Support\FirebaseMessaging::verifyCredentials();
    if ($error !== null) {
        $this->error($error);

        return 1;
    }
    $this->info('Firebase credentials work. The server can send push notifications.');

    $userId = $this->argument('user_id');
    if ($userId === null) {
        return 0;
    }
    $user = \Illuminate\Support\Facades\DB::table('users')->where('id', $userId)->first();
    if (empty($user?->device_token)) {
        $this->warn("User {$userId} has no device token yet. Sign in on the phone and allow notifications first.");

        return 1;
    }
    $result = \App\Support\Notifications::send_push($userId, 'QuikMedix', 'Test notification');
    $this->line("Result: {$result}");

    return $result === \App\Support\FirebaseMessaging::RESULT_SENT ? 0 : 1;
})->describe('Check Firebase push setup and optionally send a test notification');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->describe('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
|
| Moved as they were from App\Console\Kernel::schedule(). They run in the
| application's timezone.
|
*/

//check pharmacy create 5 order
Schedule::call(function () {
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
})->everyThreeMinutes();
//end check pharmacy create 5 order
//eta calculate
Schedule::call(function () {
    $drivers = User::where('role','driver')->where('work_now','1')->where('isblocked',0)->leftJoin("routes_priority","routes_priority.driver_id","=","users.id")->whereNotNull('routes_priority.id')->select('users.id')->groupBy('users.id')->get();
    foreach($drivers as $driver){
        try {
            app(RecalculateDriverEta::class)->handle($driver->id);
        } catch (\Throwable $exception) {
            // The work around it continues, but the failure is no longer lost.
            report($exception);
        }
    }
})->everyFifteenMinutes();
//end eta calculate
//update eta num minus one every minutes
Schedule::call(function () {
    $drivers = User::where('role','driver')->where('work_now','1')->where('isblocked',0)->leftJoin("routes_priority","routes_priority.driver_id","=","users.id")->whereNotNull('routes_priority.id')->select('users.id')->groupBy('users.id')->get();
    foreach($drivers as $driver){
        try {
            $driver_location = DB::table('locations')->whereIn('id', [DB::raw("select max(`id`) from locations GROUP BY user_id")])->where('user_id',$driver->id)->value('location');
            if(empty($driver_location)) {
                continue;
            }
            $driver_loc = HereMaps::roundLocation($driver_location);
            $eta = DB::table('drivers_eta')->where('driver_id',$driver->id)->first();
            $access=TRUE;
            if(!empty($eta)) {
                if($eta->last_location==$driver_loc) {
                    $access=FALSE;
                }
            }
            if($access){
                DB::table('orders')->where('driver_id',$driver->id)->whereNotNull('eta')->where('eta','>','0')->update(['eta'=>DB::raw('eta - 1')]);
                DB::table('drivers_eta')->where('driver_id',$driver->id)->whereNotNull('eta')->where('eta','>','0')->update(['eta'=>DB::raw('eta - 1')]);
                DB::table('routes_priority')->where('driver_id',$driver->id)->whereNotNull('eta')->where('eta','>','0')->update(['eta'=>DB::raw('eta - 1')]);
            }
        } catch (\Throwable $exception) {
            // The work around it continues, but the failure is no longer lost.
            report($exception);
        }
    }
})->everyMinute();
//end update eta num minus one
//pharmacys ban balance
Schedule::call(function () {
    DB::table('pharmacys')->where('isactive','1')->where('isblocked','0')->where('balance','<',0)->update(['balance_ban'=>'1']);
})->weeklyOn(4, '15:00');
//invoice created
Schedule::call(function () {
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
})->weeklyOn(1, '0:00');
//check end trial tariff
Schedule::call(function () {
    $pharmacys = DB::table('pharmacys')->where('isactive','1')->where('isblocked','0')->where('plan_id',2)->get();
    foreach($pharmacys as $pharmacy) {
        if(strtotime($pharmacy->date_end_trial ?? '')<=strtotime('now')) {
            DB::table('pharmacys')->where("id",$pharmacy->id)->update(["date_end_trial"=>NULL,"plan_id"=>1]);
        }
    }
})->dailyAt('0:00');
//cancel order ready pick up when created more 7 days
Schedule::call(function () {
    DB::table('orders')->where('statuse_id',1)->where('created', '<', Carbon::now()->subDays(7)->startOfDay())->update(['statuse_id'=>5]);
})->dailyAt('12:00');
