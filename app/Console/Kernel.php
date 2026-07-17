<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;
use App\Notifications;
use App\User;
use Zadarma_API\Api as Zadarma_API;
use Carbon\Carbon;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //check pharmacy create 5 order
        $schedule->call(function () {
            $orders = DB::table('orders')->join('users', 'orders.user_id', '=', 'users.id')->join('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id')->select(DB::raw('count(distinct orders.id) as count'),"orders.pharmacy_id","pharmacys.name")->where('orders.statuse_id', '1')->groupBy("orders.pharmacy_id","pharmacys.name")->get();
            foreach($orders as $order) {
                if($order->count>=5){
                    $notify = DB::table('notifications')->where('type_text', 'five_orders')->where('link',"https://cp.a2brx.com/orders/".$order->pharmacy_id."?statuse%5B%5D=1")->whereBetween("created",[DB::raw('DATE_SUB(NOW(), INTERVAL 2 HOUR)'),DB::raw('NOW()')])->first(); 
                    if(empty($notify)) {
                        Notifications::send_push_web(array_map('strval', User::where('role', "admin")->orWhere("role","logist")->pluck('id')->toArray()),
                            "Attention!",
                            "The pharmacy '".$order->name."' has already accumulated ".$order->count." orders, it's time to send drivers for them",
                            "https://cp.a2brx.com/orders/".$order->pharmacy_id."?statuse%5B%5D=1",
                            "five_orders"
                        );
                    }
                }
            }
        })->everyThreeMinutes();
        //end check pharmacy create 5 order
        //eta calculate
        $schedule->call(function () {
            $drivers = User::where('role','driver')->where('work_now','1')->where('isblocked',0)->leftJoin("routes_priority","routes_priority.driver_id","=","users.id")->whereNotNull('routes_priority.id')->select('users.id')->groupBy('users.id')->get();
            foreach($drivers as $driver){
                try {
                    $driver_location = DB::table('locations')->whereIn('id', [DB::raw("select max(`id`) from locations GROUP BY user_id")])->where('user_id',$driver->id)->value('location');
                    if(empty($driver_location)) {
                        continue;
                    }
                    $driver_loc = self::slice_location($driver_location);
                    $eta = DB::table('drivers_eta')->where('driver_id',$driver->id)->first();
                    $access=TRUE;
                    if(!empty($eta)) {
                        if($eta->last_location==$driver_loc) {
                            $access=FALSE;
                        }
                    }
                    if($access){
                        $routes_priority1 = DB::table('routes_priority')->select("driver_id", DB::raw("GROUP_CONCAT(order_id SEPARATOR ',') as order_id"), "type", "type_id", DB::raw("min(priority) as priority"))->where('driver_id', $driver->id)->where('type', '!=', 'office')->groupBy("type","type_id","driver_id")->orderBy("priority","asc")->get();
                        $routes_priority0 = DB::table('routes_priority')->select("driver_id", DB::raw("GROUP_CONCAT(order_id SEPARATOR ',') as order_id"), "type", "type_id", "priority")->where('driver_id', $driver->id)->where('type', 'office')->groupBy("type","type_id","driver_id", "priority")->orderBy("priority","asc")->get();
                        $routes = array();
                        foreach ($routes_priority1 as $key => $value) {
                            array_push($routes,$value);
                        }
                        foreach ($routes_priority0 as $key => $value) {
                            array_push($routes,$value);
                        }
                        usort($routes, function($a, $b){
                            return ($a->priority - $b->priority);
                        });
                        $distance=0;
                        $duration=0;
                        if(count($routes)>0) {
                            if($driver->transport=='2') {
                                $transport = "bicycle";
                            } else {
                                $transport = "car";
                            }
                            $loc_arr = [];
                            $orders_arr = [];
                            foreach ($routes as $key => $value) {
                                $location = null;
                                if($value->type=='pharmacy') {
                                    $location = DB::table('pharmacys')->where('id',$value->type_id)->value('location');
                                }
                                if($value->type=='patient') {
                                    $location = DB::table('users')->where('id',$value->type_id)->value('location');
                                }
                                if($value->type=='office') {
                                    $location = DB::table('offices')->where('id',$value->type_id)->value('location');
                                }
                                if(empty($location)) {
                                    continue;
                                }
                                array_push($loc_arr,self::slice_location(str_replace(' ','',$location)));
                                array_push($orders_arr,["order_id"=>$value->order_id,"type"=>$value->type,"type_id"=>$value->type_id,"priority"=>$value->priority]);
                            }
                            $access_token = Redis::get('here_access_token');
                            if(empty($access_token)) {
                                $access_token = self::update_here_access_token();
                            }
                            $options = array(
                                'http' => array(
                                    'method'  => 'GET',
                                    'ignore_errors' => true,
                                    'header' => 'Authorization: Bearer '.$access_token
                                )
                            );
                            $context  = stream_context_create($options);
                            $legs_arr = [];
                            for($i=0; $i < count($loc_arr); $i+=50) {
                                $location_arr = array_slice($loc_arr,$i,50);
                                $last_loc = $location_arr[count($location_arr)-1];
                                unset($location_arr[count($location_arr)-1]);
                                if(empty($location_arr)) {
                                    $directions = json_decode(file_get_contents("https://router.hereapi.com/v8/routes?transportMode=$transport&origin=$driver_loc&destination=$last_loc&return=summary",false,$context));
                                } else {
                                    $directions = json_decode(file_get_contents("https://router.hereapi.com/v8/routes?transportMode=$transport&origin=$driver_loc&destination=$last_loc&return=summary&via=".implode('&via=',$location_arr),false,$context));
                                }
                                if(!empty($directions->routes)) {
                                    foreach($directions->routes[0]->sections as $key => $legs) {
                                        array_push($legs_arr,$legs);
                                    }
                                }
                            }
                            if(!empty($legs_arr)) {
                                foreach($legs_arr as $key => $legs) {
                                    $distance=$distance+$legs->summary->length;
                                    $duration=$duration+$legs->summary->duration+240;
                                    if(isset($orders_arr[$key])) {
                                        DB::table('routes_priority')->where('driver_id', $driver->id)->where('type',$orders_arr[$key]["type"])->where('type_id',$orders_arr[$key]["type_id"])->where('priority',$orders_arr[$key]["priority"])->update(["eta"=>ceil($duration / 60)]);
                                        if($orders_arr[$key]["type"]=='patient') {
                                            DB::table('orders')->whereIn('id',explode(",",$orders_arr[$key]["order_id"]))->update(["eta"=>ceil($duration / 60)]);
                                        }
                                    }
                                }
                            }
                        }
                        $min = ceil($duration / 60);
                        $distance=round($distance*0.000621371192,1);
                        if(!empty($eta)) {
                            DB::table('drivers_eta')->where('driver_id',$driver->id)->update(["distance"=>$distance,"eta"=>$min,"last_location"=>$driver_loc]);
                        } else {
                            DB::table('drivers_eta')->insert(["driver_id"=>$driver->id,"distance"=>$distance,"eta"=>$min,"last_location"=>$driver_loc]);
                        }
                    }
                } catch (\Throwable $th) {
                    //throw $th;
                }
            }
        })->everyFifteenMinutes();
        //end eta calculate
        //update eta num minus one every minutes
        $schedule->call(function () {
            $drivers = User::where('role','driver')->where('work_now','1')->where('isblocked',0)->leftJoin("routes_priority","routes_priority.driver_id","=","users.id")->whereNotNull('routes_priority.id')->select('users.id')->groupBy('users.id')->get();
            foreach($drivers as $driver){
                try {
                    $driver_location = DB::table('locations')->whereIn('id', [DB::raw("select max(`id`) from locations GROUP BY user_id")])->where('user_id',$driver->id)->value('location');
                    if(empty($driver_location)) {
                        continue;
                    }
                    $driver_loc = self::slice_location($driver_location);
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
                } catch (\Throwable $th) {
                    //throw $th;
                }
            }
        })->everyMinute();
        //end update eta num minus one
        //pharmacys ban balance
        $schedule->call(function () {
            DB::table('pharmacys')->where('isactive','1')->where('isblocked','0')->where('balance','<',0)->update(['balance_ban'=>'1']);
        })->weeklyOn(4, '15:00');
        //invoice created
        $schedule->call(function () {
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
                    if(in_array($pharmacy_id,[123])===false) {
                        DB::table('pharmacys')->where("id",$pharmacy_id)->update(['balance'=>$balance]);
                        if(floatval($pharmacy->balance)>=$amount2) {
                            DB::table('pharmacy_payments')->insert(['pharmacy_id'=>$pharmacy_id,'invoice_id'=>$invoice_id,'amount'=>$amount2,'transaction_id'=>'balance','type'=>'pay']);
                            DB::table('invoices')->where('id',$invoice_id)->where('pharmacy_id',$pharmacy_id)->update(["payed"=>'1']);
                            $invoice_exclusions = DB::table('invoice_exclusion')->where('invoice_id',$invoice_id)->pluck('order_id')->toArray();
                            DB::table('orders')->where('pharmacy_id', $pharmacy_id)->whereIn('statuse_id',[4,8,9,10])->whereDate('finish', '>=', $date_from)->whereDate('finish', '<=', $date_to)->whereNotIn('id',$invoice_exclusions)->update(["invoice_payed"=>"1"]);
                        }
                    }
                } catch (\Throwable $th) {
                    //throw $th;
                }
            }
        })->weeklyOn(1, '0:00');
        //check end trial tariff
        $schedule->call(function () {
            $pharmacys = DB::table('pharmacys')->where('isactive','1')->where('isblocked','0')->where('plan_id',2)->get();
            foreach($pharmacys as $pharmacy) {
                if(strtotime($pharmacy->date_end_trial)<=strtotime('now')) {
                    DB::table('pharmacys')->where("id",$pharmacy->id)->update(["date_end_trial"=>NULL,"plan_id"=>1]);
                }
            }
        })->dailyAt('0:00');
        //cancel order ready pick up when created more 7 days
        $schedule->call(function () {
            DB::table('orders')->where('statuse_id',1)->where('created', '<', Carbon::now()->subDays(7)->startOfDay())->update(['statuse_id'=>5]);
        })->dailyAt('12:00');
    }

    public static function slice_location($location) {
        if(!is_string($location) || $location === '') {
            return '';
        }
        $arr = explode(',',$location);
        if(count($arr)>1) {
            $arr[0]=round(floatval($arr[0]),5);
            $arr[1]=round(floatval($arr[1]),5);
        }
        $location = implode(',',$arr);
        return $location;
    }

    public static function update_here_access_token() {
        $nonce     = bin2hex(random_bytes(4));
        $timestamp = time();
        $string='POST&'.urlencode('https://account.api.here.com/oauth2/token').'&'.urlencode('grant_type=client_credentials&'.'oauth_consumer_key='.config('app.hereAccessId').'&oauth_nonce='.$nonce.'&oauth_signature_method=HMAC-SHA256&oauth_timestamp='.$timestamp.'&oauth_version=1.0');
        $signature = urlencode(base64_encode(hash_hmac('sha256', $string, config('app.hereAccessSecret').'&',TRUE)));
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://account.api.here.com/oauth2/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded',
                'Authorization: OAuth oauth_consumer_key="'.config('app.hereAccessId').'",oauth_nonce="'.$nonce.'",oauth_signature_method="HMAC-SHA256",oauth_timestamp="'.$timestamp.'",oauth_version="1.0",oauth_signature="'.$signature.'"'
            ),
        ));
        $response = json_decode(curl_exec($curl));
        curl_close($curl);
        if(isset($response->access_token) && isset($response->expires_in)) {
            Redis::set('here_access_token',$response->access_token, 'EX', intval($response->expires_in));
            return $response->access_token;
        } else {
            dd('Error when update access token HERE!');
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
