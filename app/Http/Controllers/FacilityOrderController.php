<?php

namespace App\Http\Controllers;

use App\Notifications;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Smalot\PdfParser\Parser;

/**
 * Orders placed for a facility (several recipients per order). Formerly the ordersFacilitys* actions of LexaAdmin.
 */
class FacilityOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa', 'active']);
    }

    /**
     * @param int|string $pharmacy_id
     */
    public function create($pharmacy_id) {
        if(Auth::user()->pharmacy_balance_ban()) {
            return redirect("billing/".Auth::user()->pharmacy_id, 302);
        }
        if(Auth::user()->can('manage-pharmacy', $pharmacy_id)) {
            $pharmacy = DB::table('pharmacys')->where('id', $pharmacy_id)->first();
            abort_unless($pharmacy, 404, 'Pharmacy not found.');
            $facilitys = DB::table('users')->where('role', 'facility')->where('pharmacy_id', $pharmacy_id)->get();
            $delivery_methods = DB::table('delivery_methods')->get();
            $delivery_times = DB::table('delivery_times')->get();
            $time_ranges = ["9:00 AM","10:00 AM","11:00 AM","12:00 PM", "1:00 PM","2:00 PM","3:00 PM","4:00 PM","5:00 PM","6:00 PM","7:00 PM","8:00 PM","9:00 PM","10:00 PM","11:00 PM","12:00 AM"];
            $res_view = view('facilitys.add',['facilitys'=>$facilitys,'pharmacy'=>$pharmacy,'time_ranges'=>$time_ranges,'delivery_methods'=>$delivery_methods, 'delivery_times'=>$delivery_times,'title'=>'Order Facilitys Add','br1'=>'Orders','br2'=>'Order Facilitys Add','alert'=>'']);
            if(request()->query->has('ajax')) {
                return $res_view->renderSections();
            } else {
                return $res_view;
            }
        } else {
            return abort(403, LexaAdmin::$err_perm);
        }
    }

    /**
     * @param int|string $pharmacy_id
     */
    public function store(Request $request,$pharmacy_id) {
        if(Auth::user()->pharmacy_balance_ban()) {
            return redirect("billing/".Auth::user()->pharmacy_id, 302);
        }
        if(Auth::user()->can('manage-pharmacy', $pharmacy_id)) {
            if(request()->request->has('user_id')) {
                $patient=DB::table('users')->where('users.id',$request->input('user_id'))->first();
                $zip_tariff=DB::table('area_zip')->where('area_zip.zip',$patient->zip)->join('area', 'area_zip.area_id', '=', 'area.id')->select("area.tariff")->first();
                if(!empty($zip_tariff)){
                    return json_encode([
                        'message' => 'OK',
                        'tariff' => $zip_tariff->tariff
                    ]);
                } else {
                    return json_encode([
                        'message' => 'OK',
                        'tariff' => 0
                    ]);
                }
            }
            if($request->input('save')>0) {
                $id_max = DB::table('orders')->max('id')+1;
                $copay = (empty($request->input('copay')))?'0':round($request->input('copay'),2);
                $statuse_copay = (empty($request->input('copay')))?'1':'2';
                if(!empty($request->input('copay_paid_pharm'))) {
                    $statuse_copay='6';
                }
                $fridge = (empty($request->input('fridge')))?'0':$request->input('fridge');
                $special_instructions = (empty($request->input('special_instructions')))?NULL:addslashes($request->input('special_instructions'));
                $rx_ids = $request->input('rx_id');
                $rf_ids = $request->input('rf_id');
                $rx_counts = $request->input('rx_count');
                $rx_dates = $request->input('rx_date');
                $rx_copays = $request->input('rx_copay');
                $rx_recipients = $request->input('rx_recipient');
                $data=[];
                if(!empty($rx_ids)){
                    foreach(array_keys($rx_ids) as $key){
                        if(empty($rx_recipients[$key])) {
                            $rx_recipient=NULL;
                        } else {
                            $rx_recipient=$rx_recipients[$key];
                        }
                        if(empty($rx_copays[$key])) {
                            $rx_copay=0;
                        } else {
                            $rx_copay=$rx_copays[$key];
                        }
                        $data[]=["order_id"=>$id_max,"rx_id"=>str_replace([" ",'-',','],'',$rx_ids[$key]).'-'.$rf_ids[$key],"rx_date"=>$rx_dates[$key],"rx_count"=>intval($rx_counts[$key]),"rx_copay"=>$rx_copay,"rx_recipient"=>$rx_recipient];
                    }
                    DB::table('rxs')->insert($data);
                }
                if($request->input('delivery_time')=="1"){
                    $delivery_date = DB::raw("DATE_ADD(CURDATE(), INTERVAL 1 DAY)");
                } else {
                    $delivery_date = DB::raw("CURDATE()");
                }
                $time_ranges = ["9:00 AM","10:00 AM","11:00 AM","12:00 PM", "1:00 PM","2:00 PM","3:00 PM","4:00 PM","5:00 PM","6:00 PM","7:00 PM","8:00 PM","9:00 PM","10:00 PM","11:00 PM","12:00 AM"];
                if(empty($request->input('delivery_time_range'))) {
                    $delivery_time_range = $time_ranges[0].";".end($time_ranges);
                } else {
                    $delivery_time_range = $request->input('delivery_time_range');
                }
                if(!empty($request->input('driver'))) {
                    $driver_id = $request->input('driver');
                } else {
                    $driver_id = NULL;
                }
                DB::table('orders')->insert(['id'=>$id_max,'pharmacy_id' => $pharmacy_id, 'medic_id'=>Auth::id(), 'driver_id'=>$driver_id, 'user_id' => $request->input('facility'), 'facility'=>true, 'copay' => $copay, 'statuse_copay' => $statuse_copay, 'delivery_method_id' => $request->input('delivery_method'), 'special_instructions' => $special_instructions, 'count_bags' => $request->input('count_bags'), 'extra_charge_driver'=>floatval($request->input('extra_charge_driver')), 'type_driver' => 1, 'delivery_time_id' => $request->input('delivery_time'), 'delivery_time_range' => $delivery_time_range, 'delivery_date'=>$delivery_date, 'fridge' => $fridge,'family_id' => $request->input('family_id')]);
                if(!empty($request->input('facility'))){
                    $us = DB::table('users')->where('id',$request->input('facility'))->first();
                } else {
                    $us = DB::table('users')->where('id',$request->input('user'))->first();
                }
                if($us->primary_address==3){
                    if(!empty($us->apartment)){
                        $user_address = $us->address3.' Apt '.$us->apartment3;
                    } else {
                        $user_address = $us->address3;
                    }
                } elseif($us->primary_address==2){
                    if(!empty($us->apartment)){
                        $user_address = $us->address2.' Apt '.$us->apartment2;
                    } else {
                        $user_address = $us->address2;
                    }
                } else {
                    if(!empty($us->apartment)){
                        $user_address = $us->address.' Apt '.$us->apartment;
                    } else {
                        $user_address = $us->address;
                    }
                }
                Notifications::send_push($request->input('user'),"QuikMedix","QuikMedix is greeting you! Your order #$id_max is ready to be shipped to this address: $user_address If the address is wrong, please contact ".\App\Support\Branding::supportContact()." as soon as possible");
                if($request->input('delivery_time')==3 || $request->input('delivery_time')==4) {
                    Notifications::send_push_web(array_map('strval', User::where('role', "admin")->orWhere("role","logist")->pluck('id')->toArray()),
                        "Attention!",
                        "Urgent order No.".$id_max." has been created, which needs to be processed promptly.",
                        url('/')."/orders/".$pharmacy_id."?statuse%5B%5D=1",
                        "rush_order"
                    );
                }
            }
            if($request->hasFile('import')) {
                $request->validate([
                    'import.*' => 'required|mimes:pdf|max:2048'
                ]);
                $order_id = $id_max;
                foreach($request->file('import') as $file) {
                    $order = DB::table('orders')->where('id', $order_id)->first();
                    $parser = new Parser();
                    $pdf = $parser->parseFile($file);
                    $text = $pdf->getPages()[0]->getDataTm();
                    $user = new User;
                    if(strpos($text[11][1],',')===false) {
                        $user->name=str_replace(' ','',explode(',',$text[12][1])[0]);
                        $user->last_name=str_replace(' ','',explode(',',$text[12][1])[1]);
                    } else {
                        $user->name=str_replace(' ','',explode(',',$text[11][1])[0]);
                        $user->last_name=str_replace(' ','',explode(',',$text[11][1])[1]);
                    }
                    $user->phone=str_replace('Ph#: ','',(strpos($text[2][1],'Cell#: () -')!==false)?str_replace('Ph#: ','',$text[1][1]):str_replace('Cell#: ','',$text[2][1]));
                    $facility = DB::table('additional_recipients')->where('family_phone',$user->phone)->where('user_id',$order->user_id)->where('family_name',$user->name.' '.$user->last_name)->first();
                    if(empty($facility)){
                        $facility_id = DB::table('additional_recipients')->insertGetId(['user_id'=>$order->user_id,'family_type' => 'Additional Recipient','family_name' => $user->name.' '.$user->last_name,'family_phone' => $user->phone]);
                    } else {
                        $facility_id = $facility->id;
                    }
                    $copay=floatval(preg_replace("/[^-0-9\.]/","",$text[(array_search('Total Rx Count:', array_column($text, 1))+1)][1]));
                    $rxs = [];
                    for ($i=(array_search('Rf#', array_column($text, 1))+1); $i < (array_search('Total Rx Count:', array_column($text, 1))-1); $i++) {
                        if(floatval($text[$i][0][4])>9 && floatval($text[$i][0][4])<20) {
                            $rx['rx_date']=date("Y-m-d",strtotime($text[$i][1]));
                            $rx['rx_id']='';
                            for ($i2=($i+1); $i2 < (array_search('Total Rx Count:', array_column($text, 1))-1); $i2++) {
                                if(floatval($text[$i2][0][4])>47 && floatval($text[$i2][0][4])<59) {
                                    $rx['rx_id']=$text[$i2][1];
                                    for ($i3=($i2+1); $i3 < (array_search('Total Rx Count:', array_column($text, 1))-1); $i3++) {
                                        if(floatval($text[$i3][0][4])>106 && floatval($text[$i3][0][4])<120) {
                                            $rx['rx_id'].='-'.$text[$i3][1];
                                            break;
                                        }
                                    }
                                    break;
                                }
                            }
                            $rxs[]=$rx;
                        }
                    }
                    $data=[];
                    foreach($rxs as $rx) {
                        $data[]=["order_id"=>$order_id,"rx_id"=>$rx['rx_id'],"rx_date"=>$rx['rx_date'],"rx_count"=>1,"rx_copay"=>0,"rx_recipient"=>$facility_id];
                    }
                    DB::table('rxs')->insert($data);
                    DB::table('orders')->where('id', $order_id)->update(['copay'=>($order->copay+$copay)]);
                }
            }
            return redirect("orders/$pharmacy_id/facilitys_edit/$id_max");
        } else {
            return abort(403, LexaAdmin::$err_perm);
        }
    }

    /**
     * @param int|string $pharmacy_id
     * @param int|string $order_id
     */
    public function edit($pharmacy_id,$order_id) {
        Gate::authorize('access-order', $order_id);
        if(Auth::user()->can('manage-pharmacy', $pharmacy_id) || Auth::user()->hasAnyRole('logist')) {
            $order = DB::table('orders')->where('id', $order_id)->first();
            $rxs = DB::table('rxs')->where('order_id', $order_id)->get();
            $users = DB::table('users')->where('role', 'user')->where('pharmacy_id', $pharmacy_id)->select('users.id','users.name','users.last_name','users.phone')->get();
            $facilitys = DB::table('users')->where('role', 'facility')->where('pharmacy_id', $pharmacy_id)->select('users.id','users.name','users.last_name','users.phone')->get();
            $drivers = DB::table('users')->where('role', 'driver')->whereNull("pharmacy_id")->get();
            $drivers2 = DB::table('users')->where('role', 'driver')->where("pharmacy_id",$pharmacy_id)->get();
            $delivery_methods = DB::table('delivery_methods')->get();
            $delivery_times = DB::table('delivery_times')->get();
            $statuses = DB::table('statuses')->get();
            $family_members = DB::table('family_members')->where('user_id', $order->user_id)->get();
            $additional_recipients = DB::table('additional_recipients')->where('user_id', $order->user_id)->join('rxs','rxs.rx_recipient','=','additional_recipients.id')->where('rxs.order_id', $order_id)->select('additional_recipients.id','additional_recipients.family_type','additional_recipients.family_name','additional_recipients.family_phone')->groupBy('additional_recipients.id','additional_recipients.family_type','additional_recipients.family_name','additional_recipients.family_phone')->get();
            $pharmacy=DB::table('pharmacys')->where('pharmacys.id',$pharmacy_id)->first();
            $patient=DB::table('users')->where('users.id',$order->user_id)->first();
            $time_ranges = ["9:00 AM","10:00 AM","11:00 AM","12:00 PM", "1:00 PM","2:00 PM","3:00 PM","4:00 PM","5:00 PM","6:00 PM","7:00 PM","8:00 PM","9:00 PM","10:00 PM","11:00 PM","12:00 AM"];
            $zip_tariff=DB::table('area_zip')->where('area_zip.zip',$patient->zip)->join('area', 'area_zip.area_id', '=', 'area.id')->select("area.tariff")->first();
            $res_view = view('facilitys.edit',['order'=>$order, 'rxs'=>$rxs, "time_ranges"=>$time_ranges, 'users'=>$users, 'facilitys'=>$facilitys, 'pharmacy'=>$pharmacy, 'zip_tariff'=>$zip_tariff, 'drivers'=>$drivers, 'drivers2'=>$drivers2, 'family_members'=>$family_members, 'additional_recipients'=>$additional_recipients, 'statuses'=>$statuses, 'delivery_methods'=>$delivery_methods, 'delivery_times'=>$delivery_times, 'title'=>'Order Edit','br1'=>'Orders','br2'=>'Order Edit','alert'=>'']);
            if(request()->query->has('ajax')) {
                return $res_view->renderSections();
            } else {
                return $res_view;
            }
        } else {
            return abort(403, LexaAdmin::$err_perm);
        }
    }

    /**
     * @param int|string $pharmacy_id
     * @param int|string $order_id
     */
    public function update(Request $request,$pharmacy_id,$order_id) {
        Gate::authorize('access-order', $order_id);
        if(Auth::user()->can('manage-pharmacy', $pharmacy_id) || Auth::user()->hasAnyRole('logist')) {
            if(request()->request->has('user_id')) {
                $pharmacy=DB::table('pharmacys')->where('pharmacys.id',$pharmacy_id)->first();
                $patient=DB::table('users')->where('users.id',$request->input('user_id'))->first();
                $zip_tariff=DB::table('area_zip')->where('area_zip.zip',$patient->zip)->join('area', 'area_zip.area_id', '=', 'area.id')->select("area.tariff")->first();
                if(!empty($zip_tariff)){
                    return json_encode([
                        'message' => 'OK',
                        'tariff' => $zip_tariff->tariff
                    ]);
                } else {
                    return json_encode([
                        'message' => 'OK',
                        'tariff' => 0
                    ]);
                }
            }
            if($request->hasFile('import')) {
                $request->validate([
                    'import.*' => 'required|mimes:pdf|max:2048'
                ]);
                foreach($request->file('import') as $file) {
                    $order = DB::table('orders')->where('id', $order_id)->first();
                    $parser = new Parser();
                    $pdf = $parser->parseFile($file);
                    $text = $pdf->getPages()[0]->getDataTm();
                    $user = new User;
                    if(strpos($text[11][1],',')===false) {
                        $user->name=str_replace(' ','',explode(',',$text[12][1])[0]);
                        $user->last_name=str_replace(' ','',explode(',',$text[12][1])[1]);
                    } else {
                        $user->name=str_replace(' ','',explode(',',$text[11][1])[0]);
                        $user->last_name=str_replace(' ','',explode(',',$text[11][1])[1]);
                    }
                    $user->phone=str_replace('Ph#: ','',(strpos($text[2][1],'Cell#: () -')!==false)?str_replace('Ph#: ','',$text[1][1]):str_replace('Cell#: ','',$text[2][1]));
                    $facility = DB::table('additional_recipients')->where('family_phone',$user->phone)->where('user_id',$order->user_id)->where('family_name',$user->name.' '.$user->last_name)->first();
                    if(empty($facility)){
                        $facility_id = DB::table('additional_recipients')->insertGetId(['user_id'=>$order->user_id,'family_type' => 'Additional Recipient','family_name' => $user->name.' '.$user->last_name,'family_phone' => $user->phone]);
                    } else {
                        $facility_id = $facility->id;
                    }
                    $copay=floatval(preg_replace("/[^-0-9\.]/","",$text[(array_search('Total Rx Count:', array_column($text, 1))+1)][1]));
                    $rxs = [];
                    for ($i=(array_search('Rf#', array_column($text, 1))+1); $i < (array_search('Total Rx Count:', array_column($text, 1))-1); $i++) {
                        if(floatval($text[$i][0][4])>9 && floatval($text[$i][0][4])<20) {
                            $rx['rx_date']=date("Y-m-d",strtotime($text[$i][1]));
                            $rx['rx_id']='';
                            for ($i2=($i+1); $i2 < (array_search('Total Rx Count:', array_column($text, 1))-1); $i2++) {
                                if(floatval($text[$i2][0][4])>47 && floatval($text[$i2][0][4])<59) {
                                    $rx['rx_id']=$text[$i2][1];
                                    for ($i3=($i2+1); $i3 < (array_search('Total Rx Count:', array_column($text, 1))-1); $i3++) {
                                        if(floatval($text[$i3][0][4])>106 && floatval($text[$i3][0][4])<120) {
                                            $rx['rx_id'].='-'.$text[$i3][1];
                                            break;
                                        }
                                    }
                                    break;
                                }
                            }
                            $rxs[]=$rx;
                        }
                    }
                    $data=[];
                    foreach($rxs as $rx) {
                        $data[]=["order_id"=>$order_id,"rx_id"=>$rx['rx_id'],"rx_date"=>$rx['rx_date'],"rx_count"=>1,"rx_copay"=>0,"rx_recipient"=>$facility_id];
                    }
                    DB::table('rxs')->insert($data);
                    DB::table('orders')->where('id', $order_id)->update(['copay'=>($order->copay+$copay)]);
                }
                return redirect("orders/$pharmacy_id/facilitys_edit/$order_id");
            }
            if($request->input('save')>0) {
                $copay = (empty($request->input('copay')))?'0':round($request->input('copay'),2);
                $statuse_copay = (empty($request->input('copay')))?'1':'2';
                if(!empty($request->input('copay_paid_pharm'))) {
                    $statuse_copay='6';
                }
                $fridge = (empty($request->input('fridge')))?'0':$request->input('fridge');
                $order = DB::table('orders')->where('id', $order_id)->first();
                $special_instructions = (empty($request->input('special_instructions')))?NULL:addslashes($request->input('special_instructions'));
                DB::table('rxs')->where('order_id',$order_id)->delete();
                $rx_ids = $request->input('rx_id');
                $rf_ids = $request->input('rf_id');
                $rx_dates = $request->input('rx_date');
                $rx_counts = $request->input('rx_count');
                $rx_recipients = $request->input('rx_recipient');
                $rx_copays = $request->input('rx_copay');
                $data=[];
                if(!empty($rx_ids)){
                    foreach(array_keys($rx_ids) as $key){
                        if(empty($rx_recipients[$key])) {
                            $rx_recipient=NULL;
                        } else {
                            $rx_recipient=$rx_recipients[$key];
                        }
                        if(empty($rx_copays[$key])) {
                            $rx_copay=0;
                        } else {
                            $rx_copay=$rx_copays[$key];
                        }
                        $data[]=["order_id"=>$order_id,"rx_id"=>str_replace([" ",'-',','],'',$rx_ids[$key]).'-'.$rf_ids[$key],"rx_date"=>$rx_dates[$key],"rx_count"=>intval($rx_counts[$key]),"rx_copay"=>$rx_copay,"rx_recipient"=>$rx_recipient];
                    }
                }
                DB::table('rxs')->insert($data);
                if($request->input('delivery_time')=="1"){
                    $delivery_date = DB::raw("DATE_ADD(CURDATE(), INTERVAL 1 DAY)");
                } else {
                    $delivery_date = DB::raw("CURDATE()");
                }
                if($request->input('statuse_copay')==4){
                    DB::table('orders')->where('id', $order_id)->update(['statuse_copay' => 4]);
                }
                $driver_id = $request->input('driver');
                $time_ranges = ["9:00 AM","10:00 AM","11:00 AM","12:00 PM", "1:00 PM","2:00 PM","3:00 PM","4:00 PM","5:00 PM","6:00 PM","7:00 PM","8:00 PM","9:00 PM","10:00 PM","11:00 PM","12:00 AM"];
                if(empty($request->input('delivery_time_range'))) {
                    $delivery_time_range = $time_ranges[0].";".end($time_ranges);
                } else {
                    $delivery_time_range = $request->input('delivery_time_range');
                }
                DB::table('orders')->where('id', $order_id)->update(['user_id' => $order->user_id, 'driver_id' => $driver_id, 'statuse_id' => $request->input('statuse'), 'extra_charge_driver'=>floatval($request->input('extra_charge_driver')), 'copay' => $copay, 'statuse_copay' => $statuse_copay, 'special_instructions' => $special_instructions, 'delivery_method_id' => $request->input('delivery_method'), 'count_bags' => $request->input('count_bags'), 'type_driver' => 1, 'delivery_time_id' => $request->input('delivery_time'),'delivery_time_range' => $delivery_time_range,'delivery_date'=>$delivery_date,'fridge' => $fridge]);
                if($request->input('statuse')==1 && ($request->input('delivery_time')==3 || $request->input('delivery_time')==4)) {
                    $pharmacy = DB::table('pharmacys')->where('id', $pharmacy_id)->first();
                    Notifications::send_push_web(array_map('strval', User::where('role', "admin")->orWhere("role","logist")->pluck('id')->toArray()),
                        "Attention!",
                        "Urgent order No.".$order_id." has been created, which needs to be processed promptly.",
                        url('/')."orders/".$pharmacy_id."?statuse%5B%5D=1",
                        "rush_order"
                    );
                }
                if($order->statuse_id!=$request->input('statuse') && ($request->input('statuse')==4 || $request->input('statuse')==8 || $request->input('statuse')==9 || $request->input('statuse')==10)) {
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
                    $route = DB::table('routes_priority')->where('order_id',$order_id);
                    $route->delete();
                    if($patient->primary_address==3){
                        $user_address = $patient->address3.', '.$patient->zip3.', Apt '.$patient->apartment3;
                        $user_location = $patient->location3;
                    } elseif($patient->primary_address==2){
                        $user_address = $patient->address2.', '.$patient->zip2.', Apt '.$patient->apartment2;
                        $user_location = $patient->location2;
                    } else {
                        $user_address = $patient->address.', '.$patient->zip.', Apt '.$patient->apartment;
                        $user_location = $patient->location;
                    }
                    $driver_location=$user_location;
                    DB::table('orders')->where('orders.id',$order_id)->update(['statuse_id'=>$request->input('statuse'),'finish'=>date('Y-m-d H:i:s'),'delivery_address'=>$user_address,'delivery_location'=>$driver_location,'tariff'=>$tariff_res]);
                } else if($order->statuse_id!=$request->input('statuse') && $request->input('statuse')==3) {
                    Notifications::send_push($order->user_id,"QuikMedix","Your order #$order_id is on its way!");
                }
                DB::table('medicine')->where('order_id', $order_id)->delete();
            }
            return redirect("orders/$pharmacy_id");
        } else {
            return abort(403, LexaAdmin::$err_perm);
        }
    }
}
