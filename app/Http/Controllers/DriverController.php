<?php

namespace App\Http\Controllers;

use App\Actions\LogUserChanges;
use App\Http\Controllers\Concerns\RendersLegacyPages;
use App\Notifications;
use App\Support\PageLinks;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Support\AccessMessages;
use App\Actions\NotifyNextPatient;

/**
 * Drivers: a driver's payouts, packages and profile, and the QR scans that hand orders and bags to
 * a driver. Formerly actions of LexaAdmin, moved as they were.
 */
class DriverController extends Controller
{
    use RendersLegacyPages;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('2fa');
        // Kept from LexaAdmin, whose pages load whole result sets at once.
        ini_set('memory_limit', '-1');
    }

    /**
     * @param int|string $driver_id
     */
    public function driversPayouts($driver_id) {
        $user = User::find($driver_id);
        $orders = DB::table('cash_log')->where('driver_id',$driver_id)->orderBy('id','desc');
        $max_page = DB::table('cash_log')->where('driver_id',$driver_id);
        if(!empty(request()->query('created'))) {
            $orders = $orders->whereDate('created','=',request()->query('created'));
            $max_page = $max_page->whereDate('created','=',request()->query('created'));
        }
        $countOnPage=30;
        $max_pages=ceil($max_page->count()/$countOnPage);
        $page = $this->currentPage(request());
        $pages = PageLinks::around($page, $max_pages);
        $orders = $orders->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
        $duty = DB::table('cash_log')->where('driver_id',$driver_id)->where('return','0')->sum('copay');
        $res_view = view('drivers.payouts',['user'=>$user,'orders'=>$orders,'pages'=>$pages,'duty'=>$duty,'title'=>'Drivers Payouts','br1'=>'Drivers','br2'=>'Payouts']);
        return $this->page(request(), $res_view);
    }

    public function driversQr(Request $request) {
        if(!empty($request->input('code'))) {
            $token = DB::table('tokens_driver')->where('token',$request->input('code'))->where('created', '>', DB::raw("NOW() - INTERVAL 15 MINUTE"))->first();
            if(isset($token)) {
                return json_encode([
                    'user_id' => $token->user_id,
                ]);
            } else {
                return json_encode([
                    'message' => 'Driver with such a token was not found, or the token has expired',
                    'errors' => 'Not Found'
                ]);
            }
        } else {
            return json_encode([
                'message' => 'Driver with such a token was not found, or the token has expired',
                'errors' => 'Not Found'
            ]);
        }
    }

    public function driversQrOrder(Request $request) {
        if(!empty($request->input('code')) || !empty($request->input('count')) || !empty($request->input('driver_id'))) {
            $driver_id = $request->input('driver_id');
            $count = 1;
            $code = explode("_",$request->input('code'));
            $order_id = $code[0];
            if(isset($code[1])) {
                $bag = $code[1];
            } else {
                $bag = 1;
            }
            if((Auth::user()->can('admin'))) {
                $order = DB::table('orders')->where('id',$order_id)->where('driver_id', $driver_id)->whereIn('statuse_id', [1,2,3,5,6,7,8,9])->first();
                if(isset($order)) {
                    $count_added = DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('driver_id', $driver_id)->where('office_id',Auth::user()->office_id)->where('target','in')->count();
                    $count_gived = DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('driver_id', $driver_id)->where('office_id',Auth::user()->office_id)->where('target','out')->count();
                    $order_transfer = DB::table('orders')->where('driver_id', $driver_id)->whereIn('statuse_id', [7])->where('id',$order->id)->first();
                    if(!empty($order_transfer)) {
                        if($count+$count_gived>=$order->count_bags) {
                            if(DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('office_id',Auth::user()->office_id)->where('driver_id', $driver_id)->where('target','out')->where('bag',$bag)->count()>0) {
                                return json_encode([
                                    'message' => 'This bag has already been scanned, please scan the next bag for this order.',
                                    'errors' => 'Not Found'
                                ]);
                            } else {
                                DB::table('packages_transitions')->insert(['order_id'=>$order->id,'office_id'=>Auth::user()->office_id,'driver_id'=>$order->driver_id,'target'=>'out','bag'=>$bag]);
                                DB::table('orders')->where('id',$order_id)->update(['statuse_id'=>3]);
                                $route = DB::table('routes_priority')->where('driver_id', $driver_id)->where('type','office')->where('order_id',$order->id);
                                foreach($route->get() as $routes_priority) {
                                    DB::table('routes_priority_logs')->insert(["id"=>$routes_priority->id,"driver_id"=>$routes_priority->driver_id,"order_id"=>$routes_priority->order_id,"type"=>$routes_priority->type,"type_id"=>$routes_priority->type_id,"type_pay"=>$routes_priority->type_pay,"pay_value"=>$routes_priority->pay_value]);
                                    if($routes_priority->type_pay==2) {
                                        DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                                    } else if($routes_priority->type_pay==3) {
                                        DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value*intval($order->count_bags)]);
                                    } else {
                                        DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                                    }
                                }
                                $route->delete();
                                if(empty($order->eta) || $order->eta>60) {
                                    //Notifications::send_push($order->user_id,"QuikMedix","Your order #$order_id will be delivered by 1 p.m. \nFor more information about your order (medication) please contact ".\App\Support\Branding::supportContact()." \nBest regards, QuikMedix");
                                } else {
                                    //Notifications::send_push($order->user_id,"QuikMedix","Your order #$order_id will be delivered from 1 p.m. until 9 p.m. \nFor more information about your order (medication) please contact ".\App\Support\Branding::supportContact()." \nBest regards, QuikMedix");
                                }
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
                            if(DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('office_id',Auth::user()->office_id)->where('driver_id', $driver_id)->where('target','out')->where('bag',$bag)->count()>0) {
                                return json_encode([
                                    'message' => 'This bag has already been scanned, please scan the next bag for this order.',
                                    'errors' => 'Not Found'
                                ]);
                            } else {
                                DB::table('packages_transitions')->insert(['order_id'=>$order->id,'office_id'=>Auth::user()->office_id,'driver_id'=>$order->driver_id,'target'=>'out','bag'=>$bag]);
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
                            DB::table('packages_transitions')->insert(['order_id'=>$order->id,'office_id'=>Auth::user()->office_id,'driver_id'=>$order->driver_id,'target'=>'in','bag'=>$bag]);
                            DB::table('orders')->where('id',$order_id)->update(['statuse_id'=>7,'driver_id'=>null]);
                            $route = DB::table('routes_priority')->where('driver_id', $driver_id)->where('order_id',$order->id)->where('type','office');
                            foreach($route->get() as $routes_priority) {
                                DB::table('routes_priority_logs')->insert(["id"=>$routes_priority->id,"driver_id"=>$routes_priority->driver_id,"order_id"=>$routes_priority->order_id,"type"=>$routes_priority->type,"type_id"=>$routes_priority->type_id,"type_pay"=>$routes_priority->type_pay,"pay_value"=>$routes_priority->pay_value]);
                                if($routes_priority->type_pay==2) {
                                    DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                                } else if($routes_priority->type_pay==3) {
                                    DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value*intval($order->count_bags)]);
                                } else {
                                    DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
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
                            if(DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('office_id',Auth::user()->office_id)->where('driver_id', $driver_id)->where('target','in')->where('bag',$bag)->count()>0) {
                                return json_encode([
                                    'message' => 'This bag has already been scanned, please scan the next bag for this order.',
                                    'errors' => 'Not Found'
                                ]);
                            } else {
                                DB::table('packages_transitions')->insert(['order_id'=>$order->id,'office_id'=>Auth::user()->office_id,'driver_id'=>$order->driver_id,'target'=>'in','bag'=>$bag]);
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
                    $count_added = DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('pharmacy_id', Auth::user()->pharmacy_id)->where('driver_id', $driver_id)->where('target','in')->count();
                    $count_gived = DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('pharmacy_id', Auth::user()->pharmacy_id)->where('driver_id', $driver_id)->where('target','out')->count();
                    $order_transfer = DB::table('orders')->where('driver_id', $driver_id)->where('pharmacy_id', Auth::user()->pharmacy_id)->whereIn('statuse_id', [1,2])->where('id', $order->id)->first();
                    if(!empty($order_transfer)) {
                        if($count+$count_gived>=$order->count_bags) {
                            if(DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('pharmacy_id',Auth::user()->pharmacy_id)->where('driver_id', $driver_id)->where('target','out')->where('bag',$bag)->count()>0) {
                                return json_encode([
                                    'message' => 'This bag has already been scanned, please scan the next bag for this order.',
                                    'errors' => 'Not Found'
                                ]);
                            } else {
                                DB::table('packages_transitions')->insert(['order_id'=>$order->id,'pharmacy_id'=>Auth::user()->pharmacy_id,'driver_id'=>$order->driver_id,'target'=>'out','bag'=>$bag]);
                                DB::table('orders')->where('id',$order_id)->update(['statuse_id'=>3]);
                                $route = DB::table('routes_priority')->where('driver_id', $driver_id)->where('type','pharmacy')->where('type_id',Auth::user()->pharmacy_id)->where('order_id',$order->id);
                                foreach($route->get() as $routes_priority) {
                                    DB::table('routes_priority_logs')->insert(["id"=>$routes_priority->id,"driver_id"=>$routes_priority->driver_id,"order_id"=>$routes_priority->order_id,"type"=>$routes_priority->type,"type_id"=>$routes_priority->type_id,"type_pay"=>$routes_priority->type_pay,"pay_value"=>$routes_priority->pay_value]);
                                    if($routes_priority->type_pay==2) {
                                        DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                                    } else if($routes_priority->type_pay==3) {
                                        DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value*intval($order->count_bags)]);
                                    } else {
                                        DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
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
                            if(DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('pharmacy_id',Auth::user()->pharmacy_id)->where('driver_id', $driver_id)->where('target','out')->where('bag',$bag)->count()>0) {
                                return json_encode([
                                    'message' => 'This bag has already been scanned, please scan the next bag for this order.',
                                    'errors' => 'Not Found'
                                ]);
                            } else {
                                DB::table('packages_transitions')->insert(['order_id'=>$order->id,'pharmacy_id'=>Auth::user()->pharmacy_id,'driver_id'=>$order->driver_id,'target'=>'out','bag'=>$bag]);
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
                            DB::table('packages_transitions')->insert(['order_id'=>$order->id,'pharmacy_id'=>Auth::user()->pharmacy_id,'driver_id'=>$order->driver_id,'target'=>'in','bag'=>$bag]);
                            DB::table('orders')->where('id',$order_id)->update(['statuse_id'=>10,'driver_id'=>null]);
                            $pharmacy=DB::table('pharmacys')->where('pharmacys.id',$order->pharmacy_id)->first();
                            $pharmacy_plan=DB::table('plans')->where('plans.id',$pharmacy->plan_id)->first();
                            $patient=DB::table('users')->where('users.id',$order->user_id)->first();
                            $pharmacy_areas=DB::table('pharmacy_areas')->where('pharmacy_id',$order->pharmacy_id)->where('type',1)->pluck('area_id')->toArray();
                            $pharmacy_areas2=DB::table('pharmacy_areas')->where('pharmacy_id',$order->pharmacy_id)->where('type',2)->pluck('area_id')->toArray();
                            $pharmacy_areas3=DB::table('pharmacy_areas')->where('pharmacy_id',$order->pharmacy_id)->where('type',3)->pluck('area_id')->toArray();
                            $zip_tariff=DB::table('area')->whereIn('area.id',$pharmacy_areas)->whereRaw('ST_CONTAINS(polygon, POINT(?, ?))', \App\Support\GeoPoint::bindings($patient->location))->select("area.id")->first();
                            $zip_tariff2=DB::table('area')->whereIn('area.id',$pharmacy_areas2)->whereRaw('ST_CONTAINS(polygon, POINT(?, ?))', \App\Support\GeoPoint::bindings($patient->location))->select("area.id")->first();
                            $zip_tariff3=DB::table('area')->whereIn('area.id',$pharmacy_areas3)->whereRaw('ST_CONTAINS(polygon, POINT(?, ?))', \App\Support\GeoPoint::bindings($patient->location))->select("area.id")->first();
                            if(!empty($zip_tariff)){
                                if(is_numeric($pharmacy->tariff)) {
                                    $tariff = $pharmacy->tariff;
                                } else {
                                    $tariff = $pharmacy_plan->tariff;
                                }
                            } else if(!empty($zip_tariff2)){
                                if(is_numeric($pharmacy->tariff_area2)) {
                                    $tariff = $pharmacy->tariff_area2;
                                } else {
                                    $tariff = $pharmacy_plan->tariff_area2;
                                }
                            } else if(!empty($zip_tariff3)){
                                if(is_numeric($pharmacy->tariff_area3)) {
                                    $tariff = $pharmacy->tariff_area3;
                                } else {
                                    $tariff = $pharmacy_plan->tariff_area3;
                                }
                            } else {
                                if(is_numeric($pharmacy->tariff_area_more)) {
                                    $tariff = $pharmacy->tariff_area_more;
                                } else {
                                    $tariff = $pharmacy_plan->tariff_area_more;
                                }
                            }
                            if(is_numeric($pharmacy->tariff_next_day)) {
                                $tariff_next_day = $pharmacy->tariff_next_day;
                            } else {
                                $tariff_next_day = $pharmacy_plan->tariff_next_day;
                            }
                            if(is_numeric($pharmacy->tariff_same_day)) {
                                $tariff_same_day = $pharmacy->tariff_same_day;
                            } else {
                                $tariff_same_day = $pharmacy_plan->tariff_same_day;
                            }
                            if(is_numeric($pharmacy->tariff_asap)) {
                                $tariff_asap = $pharmacy->tariff_asap;
                            } else {
                                $tariff_asap = $pharmacy_plan->tariff_asap;
                            }
                            if(is_numeric($pharmacy->tariff_after_hours)) {
                                $tariff_after_hours = $pharmacy->tariff_after_hours;
                            } else {
                                $tariff_after_hours = $pharmacy_plan->tariff_after_hours;
                            }
                            if(is_numeric($pharmacy->tariff_fridge)) {
                                $tariff_fridge = $pharmacy->tariff_fridge;
                            } else {
                                $tariff_fridge = $pharmacy_plan->tariff_fridge;
                            }
                            if($order->type_driver==1) {
                                if($order->delivery_time_id==1) {
                                    $tariff_res = (floatval($tariff)+floatval($tariff_next_day)+floatval($order->extra_charge_driver));
                                } elseif($order->delivery_time_id==2) {
                                    $tariff_res = (floatval($tariff)+floatval($tariff_same_day)+floatval($order->extra_charge_driver));
                                } elseif($order->delivery_time_id==3) {
                                    $tariff_res = (floatval($tariff)+floatval($tariff_asap)+floatval($order->extra_charge_driver));
                                } elseif($order->delivery_time_id==4) {
                                    $tariff_res = (floatval($tariff)+floatval($tariff_after_hours)+floatval($order->extra_charge_driver));
                                } else {
                                    throw new \UnexpectedValueException("Unknown delivery time {$order->delivery_time_id} for order {$order->id}");
                                }
                                if($order->fridge==1) {
                                    $tariff_res+= floatval($tariff_fridge);
                                }
                            } else {
                                $tariff_res = floatval($tariff);
                            }
                            DB::table('orders')->where('orders.id',$order_id)->update(['finish'=>date('Y-m-d H:i:s'),'tariff'=>$tariff_res]);
                            $route = DB::table('routes_priority')->where('driver_id', $driver_id)->where('order_id',$order->id)->where('type','pharmacy')->where('type_id',Auth::user()->pharmacy_id);
                            foreach($route->get() as $routes_priority) {
                                DB::table('routes_priority_logs')->insert(["id"=>$routes_priority->id,"driver_id"=>$routes_priority->driver_id,"order_id"=>$routes_priority->order_id,"type"=>$routes_priority->type,"type_id"=>$routes_priority->type_id,"type_pay"=>$routes_priority->type_pay,"pay_value"=>$routes_priority->pay_value]);
                                if($routes_priority->type_pay==2) {
                                    DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                                } else if($routes_priority->type_pay==3) {
                                    DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value*intval($order->count_bags)]);
                                } else {
                                    DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
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
                            if(DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('pharmacy_id', Auth::user()->pharmacy_id)->where('driver_id', $driver_id)->where('target','in')->where('bag',$bag)->count()>0) {
                                return json_encode([
                                    'message' => 'This bag has already been scanned, please scan the next bag for this order.',
                                    'errors' => 'Not Found'
                                ]);
                            } else {
                                DB::table('packages_transitions')->insert(['order_id'=>$order->id,'pharmacy_id'=>Auth::user()->pharmacy_id,'driver_id'=>$order->driver_id,'target'=>'in','bag'=>$bag]);
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
        } else {
            return json_encode([
                'message' => 'QR code or Count Bags Order are not filled.',
                'errors' => 'Not Found'
            ]);
        }
    }

    /**
     * @param int|string $order_id
     * @param int|string $bag
     * @param int|string $driver_id
     */
    public function driversBagsOrder($order_id, $bag, $driver_id, $count=1) {
        if((Auth::user()->can('admin'))) {
            $order = DB::table('orders')->where('id',$order_id)->where('driver_id', $driver_id)->whereIn('statuse_id', [1,2,3,5,6,7,8,9])->first();
            if(isset($order)) {
                $count_added = DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('driver_id', $driver_id)->where('office_id',Auth::user()->office_id)->where('target','in')->count();
                $count_gived = DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('driver_id', $driver_id)->where('office_id',Auth::user()->office_id)->where('target','out')->count();
                $order_transfer = DB::table('orders')->where('driver_id', $driver_id)->whereIn('statuse_id', [7])->where('id',$order->id)->first();
                if(!empty($order_transfer)) {
                    if($count+$count_gived>=$order->count_bags) {
                        if(DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('driver_id', $driver_id)->where('order_id',$order->id)->where('office_id',Auth::user()->office_id)->where('target','out')->where('bag',$bag)->count()>0) {
                            return json_encode([
                                'message' => 'This bag has already been scanned, please scan the next bag for this order.',
                                'errors' => 'Not Found'
                            ]);
                        } else {
                            DB::table('packages_transitions')->insert(['order_id'=>$order->id,'office_id'=>Auth::user()->office_id,'driver_id'=>$order->driver_id,'target'=>'out','bag'=>$bag]);
                            DB::table('orders')->where('id',$order_id)->update(['statuse_id'=>3]);
                            $route = DB::table('routes_priority')->where('driver_id', $driver_id)->where('type','office')->where('order_id',$order->id);
                            foreach($route->get() as $routes_priority) {
                                DB::table('routes_priority_logs')->insert(["id"=>$routes_priority->id,"driver_id"=>$routes_priority->driver_id,"order_id"=>$routes_priority->order_id,"type"=>$routes_priority->type,"type_id"=>$routes_priority->type_id,"type_pay"=>$routes_priority->type_pay,"pay_value"=>$routes_priority->pay_value]);
                                if($routes_priority->type_pay==2) {
                                    DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                                } else if($routes_priority->type_pay==3) {
                                    DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value*intval($order->count_bags)]);
                                } else {
                                    DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
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
                        if(DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('office_id',Auth::user()->office_id)->where('driver_id', $driver_id)->where('target','out')->where('bag',$bag)->count()>0) {
                            return json_encode([
                                'message' => 'This bag has already been scanned, please scan the next bag for this order.',
                                'errors' => 'Not Found'
                            ]);
                        } else {
                            DB::table('packages_transitions')->insert(['order_id'=>$order->id,'office_id'=>Auth::user()->office_id,'driver_id'=>$order->driver_id,'target'=>'out','bag'=>$bag]);
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
                        DB::table('packages_transitions')->insert(['order_id'=>$order->id,'office_id'=>Auth::user()->office_id,'driver_id'=>$order->driver_id,'target'=>'in','bag'=>$bag]);
                        DB::table('orders')->where('id',$order_id)->update(['statuse_id'=>7,'driver_id'=>null]);
                        $route = DB::table('routes_priority')->where('driver_id', $driver_id)->where('order_id',$order->id)->where('type','office');
                        foreach($route->get() as $routes_priority) {
                            DB::table('routes_priority_logs')->insert(["id"=>$routes_priority->id,"driver_id"=>$routes_priority->driver_id,"order_id"=>$routes_priority->order_id,"type"=>$routes_priority->type,"type_id"=>$routes_priority->type_id,"type_pay"=>$routes_priority->type_pay,"pay_value"=>$routes_priority->pay_value]);
                            if($routes_priority->type_pay==2) {
                                DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                            } else if($routes_priority->type_pay==3) {
                                DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value*intval($order->count_bags)]);
                            } else {
                                DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
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
                        if(DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('office_id',Auth::user()->office_id)->where('driver_id', $driver_id)->where('target','in')->where('bag',$bag)->count()>0) {
                            return json_encode([
                                'message' => 'This bag has already been scanned, please scan the next bag for this order.',
                                'errors' => 'Not Found'
                            ]);
                        } else {
                            DB::table('packages_transitions')->insert(['order_id'=>$order->id,'office_id'=>Auth::user()->office_id,'driver_id'=>$order->driver_id,'target'=>'in','bag'=>$bag]);
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
                $count_added = DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('pharmacy_id', Auth::user()->pharmacy_id)->where('driver_id', $driver_id)->where('target','in')->count();
                $count_gived = DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('pharmacy_id', Auth::user()->pharmacy_id)->where('driver_id', $driver_id)->where('target','out')->count();
                $order_transfer = DB::table('orders')->where('driver_id', $driver_id)->where('pharmacy_id', Auth::user()->pharmacy_id)->whereIn('statuse_id', [1,2])->where('id', $order->id)->first();
                if(!empty($order_transfer)) {
                    if($count+$count_gived>=$order->count_bags) {
                        if(DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('pharmacy_id',Auth::user()->pharmacy_id)->where('driver_id', $driver_id)->where('target','out')->where('bag',$bag)->count()>0) {
                            return json_encode([
                                'message' => 'This bag has already been scanned, please scan the next bag for this order.',
                                'errors' => 'Not Found'
                            ]);
                        } else {
                            DB::table('packages_transitions')->insert(['order_id'=>$order->id,'pharmacy_id'=>Auth::user()->pharmacy_id,'driver_id'=>$order->driver_id,'target'=>'out','bag'=>$bag]);
                            DB::table('orders')->where('id',$order_id)->update(['statuse_id'=>3]);
                            $route = DB::table('routes_priority')->where('driver_id', $driver_id)->where('type','pharmacy')->where('type_id',Auth::user()->pharmacy_id)->where('order_id',$order->id);
                            foreach($route->get() as $routes_priority) {
                                DB::table('routes_priority_logs')->insert(["id"=>$routes_priority->id,"driver_id"=>$routes_priority->driver_id,"order_id"=>$routes_priority->order_id,"type"=>$routes_priority->type,"type_id"=>$routes_priority->type_id,"type_pay"=>$routes_priority->type_pay,"pay_value"=>$routes_priority->pay_value]);
                                if($routes_priority->type_pay==2) {
                                    DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                                } else if($routes_priority->type_pay==3) {
                                    DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value*intval($order->count_bags)]);
                                } else {
                                    DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
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
                        if(DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('pharmacy_id',Auth::user()->pharmacy_id)->where('driver_id', $driver_id)->where('target','out')->where('bag',$bag)->count()>0) {
                            return json_encode([
                                'message' => 'This bag has already been scanned, please scan the next bag for this order.',
                                'errors' => 'Not Found'
                            ]);
                        } else {
                            DB::table('packages_transitions')->insert(['order_id'=>$order->id,'pharmacy_id'=>Auth::user()->pharmacy_id,'driver_id'=>$order->driver_id,'target'=>'out','bag'=>$bag]);
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
                        DB::table('packages_transitions')->insert(['order_id'=>$order->id,'pharmacy_id'=>Auth::user()->pharmacy_id,'driver_id'=>$order->driver_id,'target'=>'in','bag'=>$bag]);
                        DB::table('orders')->where('id',$order_id)->update(['statuse_id'=>10,'driver_id'=>null]);
                        $pharmacy=DB::table('pharmacys')->where('pharmacys.id',$order->pharmacy_id)->first();
                        $pharmacy_plan=DB::table('plans')->where('plans.id',$pharmacy->plan_id)->first();
                        $patient=DB::table('users')->where('users.id',$order->user_id)->first();
                        $pharmacy_areas=DB::table('pharmacy_areas')->where('pharmacy_id',$order->pharmacy_id)->where('type',1)->pluck('area_id')->toArray();
                        $pharmacy_areas2=DB::table('pharmacy_areas')->where('pharmacy_id',$order->pharmacy_id)->where('type',2)->pluck('area_id')->toArray();
                        $pharmacy_areas3=DB::table('pharmacy_areas')->where('pharmacy_id',$order->pharmacy_id)->where('type',3)->pluck('area_id')->toArray();
                        $zip_tariff=DB::table('area')->whereIn('area.id',$pharmacy_areas)->whereRaw('ST_CONTAINS(polygon, POINT(?, ?))', \App\Support\GeoPoint::bindings($patient->location))->select("area.id")->first();
                        $zip_tariff2=DB::table('area')->whereIn('area.id',$pharmacy_areas2)->whereRaw('ST_CONTAINS(polygon, POINT(?, ?))', \App\Support\GeoPoint::bindings($patient->location))->select("area.id")->first();
                        $zip_tariff3=DB::table('area')->whereIn('area.id',$pharmacy_areas3)->whereRaw('ST_CONTAINS(polygon, POINT(?, ?))', \App\Support\GeoPoint::bindings($patient->location))->select("area.id")->first();
                        if(!empty($zip_tariff)){
                            if(is_numeric($pharmacy->tariff)) {
                                $tariff = $pharmacy->tariff;
                            } else {
                                $tariff = $pharmacy_plan->tariff;
                            }
                        } else if(!empty($zip_tariff2)){
                            if(is_numeric($pharmacy->tariff_area2)) {
                                $tariff = $pharmacy->tariff_area2;
                            } else {
                                $tariff = $pharmacy_plan->tariff_area2;
                            }
                        } else if(!empty($zip_tariff3)){
                            if(is_numeric($pharmacy->tariff_area3)) {
                                $tariff = $pharmacy->tariff_area3;
                            } else {
                                $tariff = $pharmacy_plan->tariff_area3;
                            }
                        } else {
                            if(is_numeric($pharmacy->tariff_area_more)) {
                                $tariff = $pharmacy->tariff_area_more;
                            } else {
                                $tariff = $pharmacy_plan->tariff_area_more;
                            }
                        }
                        if(is_numeric($pharmacy->tariff_next_day)) {
                            $tariff_next_day = $pharmacy->tariff_next_day;
                        } else {
                            $tariff_next_day = $pharmacy_plan->tariff_next_day;
                        }
                        if(is_numeric($pharmacy->tariff_same_day)) {
                            $tariff_same_day = $pharmacy->tariff_same_day;
                        } else {
                            $tariff_same_day = $pharmacy_plan->tariff_same_day;
                        }
                        if(is_numeric($pharmacy->tariff_asap)) {
                            $tariff_asap = $pharmacy->tariff_asap;
                        } else {
                            $tariff_asap = $pharmacy_plan->tariff_asap;
                        }
                        if(is_numeric($pharmacy->tariff_after_hours)) {
                            $tariff_after_hours = $pharmacy->tariff_after_hours;
                        } else {
                            $tariff_after_hours = $pharmacy_plan->tariff_after_hours;
                        }
                        if(is_numeric($pharmacy->tariff_fridge)) {
                            $tariff_fridge = $pharmacy->tariff_fridge;
                        } else {
                            $tariff_fridge = $pharmacy_plan->tariff_fridge;
                        }
                        if($order->type_driver==1) {
                            if($order->delivery_time_id==1) {
                                $tariff_res = (floatval($tariff)+floatval($tariff_next_day)+floatval($order->extra_charge_driver));
                            } elseif($order->delivery_time_id==2) {
                                $tariff_res = (floatval($tariff)+floatval($tariff_same_day)+floatval($order->extra_charge_driver));
                            } elseif($order->delivery_time_id==3) {
                                $tariff_res = (floatval($tariff)+floatval($tariff_asap)+floatval($order->extra_charge_driver));
                            } elseif($order->delivery_time_id==4) {
                                $tariff_res = (floatval($tariff)+floatval($tariff_after_hours)+floatval($order->extra_charge_driver));
                            } else {
                                throw new \UnexpectedValueException("Unknown delivery time {$order->delivery_time_id} for order {$order->id}");
                            }
                            if($order->fridge==1) {
                                $tariff_res+= floatval($tariff_fridge);
                            }
                        } else {
                            $tariff_res = floatval($tariff);
                        }
                        DB::table('orders')->where('orders.id',$order_id)->update(['finish'=>date('Y-m-d H:i:s'),'tariff'=>$tariff_res]);
                        $route = DB::table('routes_priority')->where('driver_id', $driver_id)->where('order_id',$order->id)->where('type','pharmacy')->where('type_id',Auth::user()->pharmacy_id);
                        foreach($route->get() as $routes_priority) {
                            DB::table('routes_priority_logs')->insert(["id"=>$routes_priority->id,"driver_id"=>$routes_priority->driver_id,"order_id"=>$routes_priority->order_id,"type"=>$routes_priority->type,"type_id"=>$routes_priority->type_id,"type_pay"=>$routes_priority->type_pay,"pay_value"=>$routes_priority->pay_value]);
                            if($routes_priority->type_pay==2) {
                                DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                            } else if($routes_priority->type_pay==3) {
                                DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value*intval($order->count_bags)]);
                            } else {
                                DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
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
                        if(DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$order->id)->where('pharmacy_id', Auth::user()->pharmacy_id)->where('driver_id', $driver_id)->where('target','in')->where('bag',$bag)->count()>0) {
                            return json_encode([
                                'message' => 'This bag has already been scanned, please scan the next bag for this order.',
                                'errors' => 'Not Found'
                            ]);
                        } else {
                            DB::table('packages_transitions')->insert(['order_id'=>$order->id,'pharmacy_id'=>Auth::user()->pharmacy_id,'driver_id'=>$order->driver_id,'target'=>'in','bag'=>$bag]);
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

    /**
     * @param int|string $user_id
     */
    public function driversPackages($user_id) {
        $driver = DB::table('users')->where('id', $user_id)->first();
        if((Auth::user()->can('admin'))) {
            $routes_priority0 = DB::table('routes_priority')->select("driver_id", DB::raw("GROUP_CONCAT(order_id SEPARATOR ',') as order_id"), "type", "type_id", "priority")->where('driver_id', $user_id)->where('type', 'office')->where('type_id', Auth::user()->office_id)->groupBy("type","type_id","driver_id", "priority")->orderBy("priority","asc")->first();
            if(empty($routes_priority0)) {
                $routes_priority0 = new \stdClass();
                $routes_priority0->order_id = '';
            }
            $orders_transfer = DB::table('orders')->where('driver_id', $user_id)->whereIn('statuse_id', [7])->whereIn('id', explode(',',$routes_priority0->order_id))->get();
            foreach($orders_transfer as $key=>$value) {
                $orders_transfer[$key]->count_bags_transfer = DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$value->id)->where('office_id',Auth::user()->office_id)->where('driver_id',$value->driver_id)->where('target','out')->count();
            }
            $orders_pick_up = DB::table('orders')->where('driver_id', $user_id)->whereIn('statuse_id', [2,3,5,6,8,9])->whereIn('id', explode(',',$routes_priority0->order_id))->get();
            foreach($orders_pick_up as $key=>$value) {
                $orders_pick_up[$key]->count_bags_transfer = DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$value->id)->where('office_id',Auth::user()->office_id)->where('driver_id',$value->driver_id)->where('target','in')->count();
            }
            $need_cash = DB::table('cash_log')->where('driver_id', $user_id)->where("return","0")->sum('copay');
        } else {
            $routes_priority0 = DB::table('routes_priority')->select("driver_id", DB::raw("GROUP_CONCAT(order_id SEPARATOR ',') as order_id"), "type", "type_id", "priority")->where('driver_id', $user_id)->where('type', 'pharmacy')->where('type_id', Auth::user()->pharmacy_id)->groupBy("type","type_id","driver_id", "priority")->orderBy("priority","asc")->first();
            if(empty($routes_priority0)) {
                $routes_priority0 = new \stdClass();
                $routes_priority0->order_id = '';
            }
            $orders_transfer = DB::table('orders')->where('driver_id', $user_id)->where('pharmacy_id', Auth::user()->pharmacy_id)->whereIn('statuse_id', [1,2])->whereIn('id', explode(',',$routes_priority0->order_id))->get();
            foreach($orders_transfer as $key=>$value) {
                $orders_transfer[$key]->count_bags_transfer = DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$value->id)->where('pharmacy_id',Auth::user()->pharmacy_id)->where('driver_id',$value->driver_id)->where('target','out')->count();
            }
            $orders_pick_up = DB::table('orders')->where('driver_id', $user_id)->whereIn('statuse_id', [2,3,5,6,8,9])->whereIn('id', explode(',',$routes_priority0->order_id))->get();
            foreach($orders_pick_up as $key=>$value) {
                $orders_pick_up[$key]->count_bags_transfer = DB::table('packages_transitions')->whereRaw('Date(created) = CURDATE()')->where('order_id',$value->id)->where('pharmacy_id',Auth::user()->pharmacy_id)->where('driver_id',$value->driver_id)->where('target','in')->count();
            }
            $need_cash = DB::table('cash_log')->where('driver_id', $user_id)->where("return","0")->sum('copay');
        }
        $res_view = view('drivers.packages',['driver'=>$driver,'need_cash'=>$need_cash,'orders_transfer'=>$orders_transfer,'orders_pick_up'=>$orders_pick_up,'title'=>'Drivers Packages','br1'=>'Drivers','br2'=>'Drivers Packages','alert'=>'']);
        return $this->page(request(), $res_view);
    }

    /**
     * @param int|string $user_id
     */
    public function driversPackagesHandler(Request $request, $user_id) {
        if($request->input('confirm_receipt')>0) {
            $driver_id = $user_id;
            $orders = DB::table('cash_log')->where('driver_id', $driver_id)->where("return","0")->get();
            foreach($orders as $order0) {
                $order =  DB::table('orders')->where('id',$order0->order_id)->first();
                if((Auth::user()->can('admin'))) {
                    $route = DB::table('routes_priority')->where('driver_id', $driver_id)->where('order_id',$order->id)->where('type','office');
                } else {
                    $route = DB::table('routes_priority')->where('driver_id', $driver_id)->where('order_id',$order->id)->where('type','pharmacy');
                }
                foreach($route->get() as $routes_priority) {
                    DB::table('routes_priority_logs')->insert(["id"=>$routes_priority->id,"driver_id"=>$routes_priority->driver_id,"order_id"=>$routes_priority->order_id,"type"=>$routes_priority->type,"type_id"=>$routes_priority->type_id,"type_pay"=>$routes_priority->type_pay,"pay_value"=>$routes_priority->pay_value]);
                    if($routes_priority->type_pay==2) {
                        DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                    } else if($routes_priority->type_pay==3) {
                        DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value*intval($order->count_bags)]);
                    } else {
                        DB::table('payouts_driver')->insert(['driver_id'=>$routes_priority->driver_id,'order_id'=>$routes_priority->order_id,'amount'=>$routes_priority->pay_value]);
                    }
                }
                $route->delete();
                $next_route = DB::table('routes_priority')->where('driver_id',$driver_id)->orderBy("priority","asc")->first();
                if(!empty($next_route)) {
                    if($next_route->type=='patient') {
                        app(NotifyNextPatient::class)->handle($driver_id,$next_route);
                    }
                }
            }
            DB::table('cash_log')->where('driver_id', $user_id)->where("return","0")->update(["return_at"=>DB::raw('NOW()'),"return"=>"1","admin_id"=>Auth::user()->id]);
        }
        if($request->input('massiveBagsTransfer')>0) {
            if((Auth::user()->can('admin'))) {
                $routes_priority0 = DB::table('routes_priority')->select("driver_id", DB::raw("GROUP_CONCAT(order_id SEPARATOR ',') as order_id"), "type", "type_id", "priority")->where('driver_id', $user_id)->where('type', 'office')->where('type_id', Auth::user()->office_id)->groupBy("type","type_id","driver_id", "priority")->orderBy("priority","asc")->first();
                if(empty($routes_priority0)) {
                    $routes_priority0 = new \stdClass();
                    $routes_priority0->order_id = '';
                }
                $orders_transfer = DB::table('orders')->where('driver_id', $user_id)->whereIn('statuse_id', [7])->whereIn('id', explode(',',$routes_priority0->order_id))->get();
            } else {
                $routes_priority0 = DB::table('routes_priority')->select("driver_id", DB::raw("GROUP_CONCAT(order_id SEPARATOR ',') as order_id"), "type", "type_id", "priority")->where('driver_id', $user_id)->where('type', 'pharmacy')->where('type_id', Auth::user()->pharmacy_id)->groupBy("type","type_id","driver_id", "priority")->orderBy("priority","asc")->first();
                if(empty($routes_priority0)) {
                    $routes_priority0 = new \stdClass();
                    $routes_priority0->order_id = '';
                }
                $orders_transfer = DB::table('orders')->where('driver_id', $user_id)->where('pharmacy_id', Auth::user()->pharmacy_id)->whereIn('statuse_id', [1,2])->whereIn('id', explode(',',$routes_priority0->order_id))->get();
            }
            foreach($orders_transfer as $order) {
                for ($i=1; $i <= $order->count_bags; $i++) {
                    $this->driversBagsOrder($order->id, $i, $user_id);
                }
            }
        }
        return redirect("drivers/$user_id/packages");
    }

    /**
     * @param int|string $driver_id
     */
    public function driversProfile($driver_id) {
        $user = DB::table('users')->where('id', $driver_id)->where('role','driver')->first();
        if(!empty($user)){
            $pharmacys = DB::table('pharmacys')->get()->keyBy('id');
            $user_actions = DB::table('action_log')->where('user_id', $driver_id)->get();
            $orders_stat = DB::table('orders')->where('user_id',$driver_id)->where('statuse_id',4)->select(DB::raw('count(distinct id) as count'),DB::raw('sum(copay) as copay'))->first();
            $duty = DB::table('cash_log')->where('driver_id',$driver_id)->where('return','0')->sum('copay');
            $last_cash = DB::table('cash_log')->where('driver_id',$driver_id)->where('return','1')->orderBy('return_at','desc')->first();
            $orders = DB::table('orders')->where('user_id',$driver_id)->join('users', 'orders.user_id', '=', 'users.id')->leftJoin('users as driver', 'orders.driver_id', '=', 'driver.id')->join('statuses', 'orders.statuse_id', '=', 'statuses.id')->leftJoin('statuses_copay', 'orders.statuse_copay', '=', 'statuses_copay.id')->join('delivery_methods', 'orders.delivery_method_id', '=', 'delivery_methods.id')->join('delivery_times', 'orders.delivery_time_id', '=', 'delivery_times.id')->join('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id')->select('orders.id', 'orders.created', 'orders.driver_id', 'orders.merchantOrder','orders.special_instructions', 'orders.rating','orders.fridge','orders.actual','orders.eta','orders.facility', 'driver.name as drivername', 'driver.last_name as driverlast_name', 'driver.pharmacy_id as driverpharmacy_id', 'orders.count_bags', 'orders.signature','orders.statuse_id', 'orders.pharmacy_id', 'orders.copay', 'users.name as username', 'delivery_methods.name as delivery_method', 'delivery_times.name as delivery_time', 'users.last_name as last_name', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end as useraddress'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end as userapartment'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end as userzip'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end as userlocation'), 'users.os as useros', 'users.phone as userphone', 'pharmacys.name as pharmacyname', 'pharmacys.address as pharmacyaddress','pharmacys.phone as pharmacyphone', 'orders.tariff', 'statuses.name as statusename','statuses.color as statusecolor','statuses_copay.name as statuse_copay_name','statuses_copay.color as statuse_copay_color')->groupBy('orders.id', 'orders.statuse_id', 'orders.merchantOrder', 'orders.actual','orders.eta', 'orders.driver_id', 'orders.rating','orders.signature','orders.fridge','driver.name', 'driver.last_name', 'driver.pharmacy_id','orders.special_instructions', 'orders.count_bags', 'orders.created', 'orders.facility','orders.copay', 'orders.pharmacy_id', 'orders.tariff', 'users.name', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end'), 'users.phone','pharmacys.name', 'pharmacys.address', 'delivery_methods.name', 'delivery_times.name', 'pharmacys.phone', 'statuses.name','statuses.color','users.last_name', 'statuses_copay.name','statuses_copay.color', 'users.os')->orderBy('orders.id','desc')->limit(8)->get();
            $res_view = view('drivers.profile',['user'=>$user,'pharmacys'=>$pharmacys,'duty'=>$duty,'last_cash'=>$last_cash,'user_actions'=>$user_actions,'orders_stat'=>$orders_stat,'orders'=>$orders,'alert'=>'','title'=>'Driver Profile','br1'=>'Drivers','br2'=>'Profile']);
            return $this->page(request(), $res_view);
        }
        return abort(404);
    }

    /**
     * @param int|string $driver_id
     */
    public function driversProfileHandler(Request $request,$driver_id) {
        $user = DB::table('users')->where('id', $driver_id)->where('role','driver')->first();
        if(!empty($user)){
            $user_id=$driver_id;
            if($request->input('save')>0) {
                if($request->hasFile('image')) {
                    $file = $request->file('image');
                    $file->move(public_path() . '/images/users/',\App\Support\PublicUpload::name($request->file('image')));
                    $src = '/images/users/'.\App\Support\PublicUpload::name($request->file('image'));
                } else {
                    $src = $user->image;
                }
                if($request->input('remove_photo')>0) {
                    $src = '/images/users/default-user-image.png';
                }
                if($request->hasFile('driving_license_img')) {
                    $file = $request->file('driving_license_img');
                    $file->move(public_path() . '/images/driving_license/',\App\Support\PublicUpload::name($request->file('driving_license_img')));
                    $driving_license_img = '/images/driving_license/'.\App\Support\PublicUpload::name($request->file('driving_license_img'));
                } else {
                    $driving_license_img = $user->driving_license_img;
                }
                if($request->hasFile('car_img')) {
                    $file = $request->file('car_img');
                    $file->move(public_path() . '/images/users/',\App\Support\PublicUpload::name($request->file('car_img')));
                    $car_img = '/images/users/'.\App\Support\PublicUpload::name($request->file('car_img'));
                } else {
                    $car_img = $user->car_img;
                }
                if(!empty($request->input('zip'))) {
                    $address = $request->input('address').' '.$request->input('zip');
                } else {
                    $address = $request->input('address');
                }
                $data = app(\App\Actions\GeocodeAddress::class)->response($address);
                $address = $data->results[0]->formatted_address;
                $location = $data->results[0]->geometry->location->lat.','.$data->results[0]->geometry->location->lng;
                app(LogUserChanges::class)->handle($request,$address,$user_id);
                DB::table('users')->where('id', $user_id)->update(['name' => $request->input('name'),'last_name' => $request->input('last_name'),'email' => $request->input('email'),'phone' => $request->input('phone'),'image' => $src,'address' => $address,'location' => $location,'zip' => $request->input('zip'),'apartment' => $request->input('apartment'),'driving_license' => $request->input('driving_license'),'driving_license_img' => $driving_license_img,'identification_cards' => $request->input('identification_cards'),'transport' => $request->input('transport'),'car_info' => $request->input('car_info'),'car_img' => $car_img]);
            }
            if($request->input('ajax_stat')>0) {
                $date=date("Y-m-d",strtotime($request->input('date_stat')));
                $routes_logs = DB::table('routes_priority_logs')->where('driver_id',$driver_id)->whereDate('created', $date)->orderBy('id','asc')->get();
                $id=1;
                $routes_logs_group = [];
                $order_ids=[];
                $copay=[];
                $orders=[];
                $copay_sum=0;
                foreach($routes_logs as $key=>$row){
                    if(!in_array($row->order_id,$order_ids)){
                        $order_ids[]=$row->order_id;
                        $order = DB::table('orders')->where('orders.id',$row->order_id)->join('users', 'orders.user_id', '=', 'users.id')->leftJoin('users as driver', 'orders.driver_id', '=', 'driver.id')->join('statuses', 'orders.statuse_id', '=', 'statuses.id')->leftJoin('statuses_copay', 'orders.statuse_copay', '=', 'statuses_copay.id')->join('delivery_methods', 'orders.delivery_method_id', '=', 'delivery_methods.id')->join('delivery_times', 'orders.delivery_time_id', '=', 'delivery_times.id')->join('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id')->select('orders.id', 'orders.statuse_copay', 'orders.created', 'orders.driver_id', 'orders.merchantOrder','orders.special_instructions', 'orders.rating','orders.fridge','orders.actual','orders.eta','orders.facility', 'driver.name as drivername', 'driver.last_name as driverlast_name', 'driver.pharmacy_id as driverpharmacy_id', 'orders.count_bags', 'orders.signature','orders.statuse_id', 'orders.pharmacy_id', 'orders.copay', 'users.name as username', 'delivery_methods.name as delivery_method', 'delivery_times.name as delivery_time', 'users.last_name as last_name', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end as useraddress'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end as userapartment'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end as userzip'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end as userlocation'), 'users.os as useros', 'users.phone as userphone', 'pharmacys.name as pharmacyname', 'pharmacys.address as pharmacyaddress','pharmacys.phone as pharmacyphone', 'orders.tariff', 'statuses.name as statusename','statuses.color as statusecolor','statuses_copay.name as statuse_copay_name','statuses_copay.color as statuse_copay_color')->groupBy('orders.id', 'orders.statuse_copay', 'orders.statuse_id', 'orders.merchantOrder', 'orders.actual','orders.eta', 'orders.driver_id', 'orders.rating','orders.signature','orders.fridge','driver.name', 'driver.last_name', 'driver.pharmacy_id','orders.special_instructions', 'orders.count_bags', 'orders.created', 'orders.facility','orders.copay', 'orders.pharmacy_id', 'orders.tariff', 'users.name', DB::raw('case when users.primary_address=2 then users.address2 when users.primary_address=3 then users.address3 else users.address end'), DB::raw('case when users.primary_address=2 then users.apartment2 when users.primary_address=3 then users.apartment3 else users.apartment end'), DB::raw('case when users.primary_address=2 then users.zip2 when users.primary_address=3 then users.zip3 else users.zip end'), DB::raw('case when users.primary_address=2 then users.location2 when users.primary_address=3 then users.location3 else users.location end'), 'users.phone','pharmacys.name', 'pharmacys.address', 'delivery_methods.name', 'delivery_times.name', 'pharmacys.phone', 'statuses.name','statuses.color','users.last_name', 'statuses_copay.name','statuses_copay.color', 'users.os')->orderBy('orders.id','desc')->first();
                        if($order->statuse_copay=='4') {
                            $copay[]=$order->copay;
                            $copay_sum+=$order->copay;
                        }
                        $orders[]=$order;
                    }
                    if(isset($routes_logs[$key+1]) && $row->type.$row->type_id==$routes_logs[$key+1]->type.$routes_logs[$key+1]->type_id){} else {
                        $row->area_name=NULL;
                        if($row->type=='office'){
                            $loc = DB::table('offices')->where('id',$row->type_id)->first();
                            if(!empty($loc)){
                                $area = DB::table('area')->whereRaw('ST_CONTAINS(polygon, POINT(?, ?))', \App\Support\GeoPoint::bindings($loc->location))->select("area.name")->first();
                                if(!empty($area)){
                                    $row->area_name = $area->name;
                                }
                            }
                        }
                        if($row->type=='patient'){
                            $loc = DB::table('users')->where('id',$row->type_id)->first();
                            if(!empty($loc)){
                                $area = DB::table('area')->whereRaw('ST_CONTAINS(polygon, POINT(?, ?))', \App\Support\GeoPoint::bindings($loc->location))->select("area.name")->first();
                                if(!empty($area)){
                                    $row->area_name = $area->name;
                                }
                            }
                        }
                        if($row->type=='pharmacy'){
                            $loc = DB::table('pharmacys')->where('id',$row->type_id)->first();
                            if(!empty($loc)){
                                $area = DB::table('area')->whereRaw('ST_CONTAINS(polygon, POINT(?, ?))', \App\Support\GeoPoint::bindings($loc->location))->select("area.name")->first();
                                if(!empty($area)){
                                    $row->area_name = $area->name;
                                }
                            }
                        }
                        $row->priority=$id;
                        $row->order_id=$order_ids;
                        $row->orders=$orders;
                        $row->copay=$copay;
                        array_push($routes_logs_group,$row);
                        $order_ids=[];
                        $orders=[];
                        $copay=[];
                        $id++;
                    }
                }
                return view('drivers.ajax_stat',['routes_logs_groups'=>$routes_logs_group,'copay_sum'=>$copay_sum]);
            }
            return redirect("/drivers/$driver_id/profile");
        }
        return abort(404);
    }
}
