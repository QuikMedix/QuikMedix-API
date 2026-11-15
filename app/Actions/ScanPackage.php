<?php

namespace App\Actions;

use App\Actions\NotifyNextPatient;
use App\Models\DriverPayout;
use App\Models\Order;
use App\Models\PackageTransition;
use App\Models\RouteStopLog;
use App\Support\Notifications;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Records the scan of one bag of an order by office staff, checking it in at the hub or handing
 * it out to a driver, and moves the order and the driver's route along with it.
 *
 * Used by the QR scanner page and by the page that hands a driver all bags of an order at once.
 */
class ScanPackage
{
    /**
     * @param  int|string  $order_id
     * @param  int|string  $bag
     * @param  int|string|null  $driver_id
     * @param  int  $count
     * @return mixed the JSON text the scanner page shows
     */
    public function handle($order_id, $bag, $driver_id, $count = 1)
    {
        if((Auth::user()->can('admin'))) {
            $order = DB::table('orders')->where('id',$order_id)->where('driver_id', $driver_id)->whereIn('statuse_id', [1,2,3,5,6,7,8,9])->first();
            if(isset($order)) {
                $count_added = PackageTransition::query()->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('driver_id', $driver_id)->where('office_id',Auth::user()->office_id)->where('target','in')->count();
                $count_gived = PackageTransition::query()->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('driver_id', $driver_id)->where('office_id',Auth::user()->office_id)->where('target','out')->count();
                $order_transfer = DB::table('orders')->where('driver_id', $driver_id)->whereIn('statuse_id', [7])->where('id',$order->id)->first();
                if(!empty($order_transfer)) {
                    if($count+$count_gived>=$order->count_bags) {
                        if(PackageTransition::query()->whereRaw('Date(created) = CURDATE()')->where('driver_id', $driver_id)->where('order_id',$order->id)->where('office_id',Auth::user()->office_id)->where('target','out')->where('bag',$bag)->count()>0) {
                            return json_encode([
                                'message' => 'This bag has already been scanned, please scan the next bag for this order.',
                                'errors' => 'Not Found'
                            ]);
                        } else {
                            PackageTransition::query()->insert(['order_id'=>$order->id,'office_id'=>Auth::user()->office_id,'driver_id'=>$order->driver_id,'target'=>'out','bag'=>$bag]);
                            Order::query()->where('id',$order_id)->update(['statuse_id'=>3]);
                            $route = DB::table('routes_priority')->where('driver_id', $driver_id)->where('type','office')->where('order_id',$order->id);
                            foreach($route->get() as $routes_priority) {
                                RouteStopLog::query()->insert(["id"=>$routes_priority->id,"driver_id"=>$routes_priority->driver_id,"order_id"=>$routes_priority->order_id,"type"=>$routes_priority->type,"type_id"=>$routes_priority->type_id,"type_pay"=>$routes_priority->type_pay,"pay_value"=>$routes_priority->pay_value]);
                                if($routes_priority->type_pay==2) {
                                    DriverPayout::query()->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                                } else if($routes_priority->type_pay==3) {
                                    DriverPayout::query()->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value*intval($order->count_bags)]);
                                } else {
                                    DriverPayout::query()->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                                }
                            }
                            $route->delete();
                            $next_route = DB::table('routes_priority')->where('driver_id',$driver_id)->orderBy("priority","asc")->first();
                            if(!empty($next_route)) {
                                if($next_route->type=='patient') {
                                    app(NotifyNextPatient::class)->handle($driver_id,$next_route);
                                }
                            }
                            return json_encode([
                                'order_id' => $order->id,
                            ]);
                        }
                    } else if($count+$count_gived<$order->count_bags) {
                        if(PackageTransition::query()->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('office_id',Auth::user()->office_id)->where('driver_id', $driver_id)->where('target','out')->where('bag',$bag)->count()>0) {
                            return json_encode([
                                'message' => 'This bag has already been scanned, please scan the next bag for this order.',
                                'errors' => 'Not Found'
                            ]);
                        } else {
                            PackageTransition::query()->insert(['order_id'=>$order->id,'office_id'=>Auth::user()->office_id,'driver_id'=>$order->driver_id,'target'=>'out','bag'=>$bag]);
                            return json_encode([
                                'bag_added' => $order->id,
                            ]);
                        }
                    } else {
                        return json_encode([
                            'message' => 'The indicated number of bags does not match the number of bags in the order, check the quantity or change it in the order settings.',
                            'errors' => 'Not Found'
                        ]);
                    }
                } else {
                    if($count+$count_added>=$order->count_bags) {
                        PackageTransition::query()->insert(['order_id'=>$order->id,'office_id'=>Auth::user()->office_id,'driver_id'=>$order->driver_id,'target'=>'in','bag'=>$bag]);
                        Order::query()->where('id',$order_id)->update(['statuse_id'=>7,'driver_id'=>null]);
                        $route = DB::table('routes_priority')->where('driver_id', $driver_id)->where('order_id',$order->id)->where('type','office');
                        foreach($route->get() as $routes_priority) {
                            RouteStopLog::query()->insert(["id"=>$routes_priority->id,"driver_id"=>$routes_priority->driver_id,"order_id"=>$routes_priority->order_id,"type"=>$routes_priority->type,"type_id"=>$routes_priority->type_id,"type_pay"=>$routes_priority->type_pay,"pay_value"=>$routes_priority->pay_value]);
                            if($routes_priority->type_pay==2) {
                                DriverPayout::query()->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                            } else if($routes_priority->type_pay==3) {
                                DriverPayout::query()->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value*intval($order->count_bags)]);
                            } else {
                                DriverPayout::query()->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                            }
                        }
                        $route->delete();
                        $next_route = DB::table('routes_priority')->where('driver_id',$driver_id)->orderBy("priority","asc")->first();
                        if(!empty($next_route)) {
                            if($next_route->type=='patient') {
                                app(NotifyNextPatient::class)->handle($driver_id,$next_route);
                            }
                        }
                        return json_encode([
                            'order_id_in' => $order->id,
                        ]);
                    } else if($count+$count_added<$order->count_bags) {
                        if(PackageTransition::query()->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('office_id',Auth::user()->office_id)->where('driver_id', $driver_id)->where('target','in')->where('bag',$bag)->count()>0) {
                            return json_encode([
                                'message' => 'This bag has already been scanned, please scan the next bag for this order.',
                                'errors' => 'Not Found'
                            ]);
                        } else {
                            PackageTransition::query()->insert(['order_id'=>$order->id,'office_id'=>Auth::user()->office_id,'driver_id'=>$order->driver_id,'target'=>'in','bag'=>$bag]);
                            return json_encode([
                                'bag_added_in' => $order->id,
                            ]);
                        }
                    } else {
                        return json_encode([
                            'message' => 'The indicated number of bags does not match the number of bags in the order, check the quantity or change it in the order settings.',
                            'errors' => 'Not Found'
                        ]);
                    }
                }
            } else {
                return json_encode([
                    'message' => 'Order with such a QR code was not found.',
                    'errors' => 'Not Found'
                ]);
            }
        } else {
            $order = DB::table('orders')->where('id',$order_id)->where('driver_id', $driver_id)->where('pharmacy_id', Auth::user()->pharmacy_id)->whereIn('statuse_id', [1,2,3,5,6,7,8,9])->first();
            if(isset($order)) {
                $count_added = PackageTransition::query()->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('pharmacy_id', Auth::user()->pharmacy_id)->where('driver_id', $driver_id)->where('target','in')->count();
                $count_gived = PackageTransition::query()->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('pharmacy_id', Auth::user()->pharmacy_id)->where('driver_id', $driver_id)->where('target','out')->count();
                $order_transfer = DB::table('orders')->where('driver_id', $driver_id)->where('pharmacy_id', Auth::user()->pharmacy_id)->whereIn('statuse_id', [1,2])->where('id', $order->id)->first();
                if(!empty($order_transfer)) {
                    if($count+$count_gived>=$order->count_bags) {
                        if(PackageTransition::query()->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('pharmacy_id',Auth::user()->pharmacy_id)->where('driver_id', $driver_id)->where('target','out')->where('bag',$bag)->count()>0) {
                            return json_encode([
                                'message' => 'This bag has already been scanned, please scan the next bag for this order.',
                                'errors' => 'Not Found'
                            ]);
                        } else {
                            PackageTransition::query()->insert(['order_id'=>$order->id,'pharmacy_id'=>Auth::user()->pharmacy_id,'driver_id'=>$order->driver_id,'target'=>'out','bag'=>$bag]);
                            Order::query()->where('id',$order_id)->update(['statuse_id'=>3]);
                            $route = DB::table('routes_priority')->where('driver_id', $driver_id)->where('type','pharmacy')->where('type_id',Auth::user()->pharmacy_id)->where('order_id',$order->id);
                            foreach($route->get() as $routes_priority) {
                                RouteStopLog::query()->insert(["id"=>$routes_priority->id,"driver_id"=>$routes_priority->driver_id,"order_id"=>$routes_priority->order_id,"type"=>$routes_priority->type,"type_id"=>$routes_priority->type_id,"type_pay"=>$routes_priority->type_pay,"pay_value"=>$routes_priority->pay_value]);
                                if($routes_priority->type_pay==2) {
                                    DriverPayout::query()->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                                } else if($routes_priority->type_pay==3) {
                                    DriverPayout::query()->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value*intval($order->count_bags)]);
                                } else {
                                    DriverPayout::query()->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                                }
                            }
                            $route->delete();
                            Notifications::send_push($order->user_id,"QuikMedix","Your order #$order_id has been confirmed. \nFor more information about your order (medication) please contact ".\App\Support\Branding::supportContact()." \nBest regards, QuikMedix");
                            $next_route = DB::table('routes_priority')->where('driver_id',$driver_id)->orderBy("priority","asc")->first();
                            if(!empty($next_route)) {
                                if($next_route->type=='patient') {
                                    app(NotifyNextPatient::class)->handle($driver_id,$next_route);
                                }
                            }
                            return json_encode([
                                'order_id' => $order->id,
                            ]);
                        }
                    } else if($count+$count_gived<$order->count_bags) {
                        if(PackageTransition::query()->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('pharmacy_id',Auth::user()->pharmacy_id)->where('driver_id', $driver_id)->where('target','out')->where('bag',$bag)->count()>0) {
                            return json_encode([
                                'message' => 'This bag has already been scanned, please scan the next bag for this order.',
                                'errors' => 'Not Found'
                            ]);
                        } else {
                            PackageTransition::query()->insert(['order_id'=>$order->id,'pharmacy_id'=>Auth::user()->pharmacy_id,'driver_id'=>$order->driver_id,'target'=>'out','bag'=>$bag]);
                            return json_encode([
                                'bag_added' => $order->id,
                            ]);
                        }
                    } else {
                        return json_encode([
                            'message' => 'The indicated number of bags does not match the number of bags in the order, check the quantity or change it in the order settings.',
                            'errors' => 'Not Found'
                        ]);
                    }
                } else {
                    if($count+$count_added>=$order->count_bags) {
                        PackageTransition::query()->insert(['order_id'=>$order->id,'pharmacy_id'=>Auth::user()->pharmacy_id,'driver_id'=>$order->driver_id,'target'=>'in','bag'=>$bag]);
                        Order::query()->where('id',$order_id)->update(['statuse_id'=>10,'driver_id'=>null]);
                        $tariff_res = app(CalculateOrderTariff::class)->handle($order);
                        Order::query()->where('orders.id',$order_id)->update(['finish'=>date('Y-m-d H:i:s'),'tariff'=>$tariff_res]);
                        $route = DB::table('routes_priority')->where('driver_id', $driver_id)->where('order_id',$order->id)->where('type','pharmacy')->where('type_id',Auth::user()->pharmacy_id);
                        foreach($route->get() as $routes_priority) {
                            RouteStopLog::query()->insert(["id"=>$routes_priority->id,"driver_id"=>$routes_priority->driver_id,"order_id"=>$routes_priority->order_id,"type"=>$routes_priority->type,"type_id"=>$routes_priority->type_id,"type_pay"=>$routes_priority->type_pay,"pay_value"=>$routes_priority->pay_value]);
                            if($routes_priority->type_pay==2) {
                                DriverPayout::query()->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                            } else if($routes_priority->type_pay==3) {
                                DriverPayout::query()->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value*intval($order->count_bags)]);
                            } else {
                                DriverPayout::query()->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                            }
                        }
                        $route->delete();
                        $next_route = DB::table('routes_priority')->where('driver_id',$driver_id)->orderBy("priority","asc")->first();
                        if(!empty($next_route)) {
                            if($next_route->type=='patient') {
                                app(NotifyNextPatient::class)->handle($driver_id,$next_route);
                            }
                        }
                        return json_encode([
                            'order_id_in' => $order->id,
                        ]);
                    } else if($count+$count_added<$order->count_bags) {
                        if(PackageTransition::query()->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('pharmacy_id', Auth::user()->pharmacy_id)->where('driver_id', $driver_id)->where('target','in')->where('bag',$bag)->count()>0) {
                            return json_encode([
                                'message' => 'This bag has already been scanned, please scan the next bag for this order.',
                                'errors' => 'Not Found'
                            ]);
                        } else {
                            PackageTransition::query()->insert(['order_id'=>$order->id,'pharmacy_id'=>Auth::user()->pharmacy_id,'driver_id'=>$order->driver_id,'target'=>'in','bag'=>$bag]);
                            return json_encode([
                                'bag_added_in' => $order->id,
                            ]);
                        }
                    } else {
                        return json_encode([
                            'message' => 'The indicated number of bags does not match the number of bags in the order, check the quantity or change it in the order settings.',
                            'errors' => 'Not Found'
                        ]);
                    }
                }
            } else {
                return json_encode([
                    'message' => 'Order with such a QR code was not found.',
                    'errors' => 'Not Found'
                ]);
            }
        }
    }
}
