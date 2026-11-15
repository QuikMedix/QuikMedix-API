<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersLegacyPages;
use App\Notifications;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Smalot\PdfParser\Parser;
use App\Support\AccessMessages;

/**
 * Imports for a pharmacy: patients from a spreadsheet and an order from a PDF. Formerly actions of
 * LexaAdmin, moved as they were.
 */
class ImportController extends Controller
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
     * @param int|string $pharmacy_id
     */
    public function patientsImport($pharmacy_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, AccessMessages::ACCOUNT_INACTIVE);
        }
        if(Auth::user()->can('manage-pharmacy', $pharmacy_id)) {
            $res_view = view('patients.import',['step'=>"1",'title'=>'Patients Import','br1'=>'Patients','br2'=>'Patient Import','alert'=>'']);
            return $this->page(request(), $res_view);
        } else {
            return abort(403, AccessMessages::NO_PERMISSION);
        }
    }

    /**
     * @param int|string $pharmacy_id
     */
    public function patientsImportHandler(Request $request,$pharmacy_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, AccessMessages::ACCOUNT_INACTIVE);
        }
        if(Auth::user()->can('manage-pharmacy', $pharmacy_id)) {
            if($request->input('step')==1) {
                $delimiter = $request->input('delimiter');
                if($request->hasFile('file')) {
                    $file = $request->file('file');
                    $file->move(public_path() . '/imports/',\App\Support\PublicUpload::name($request->file('file')));
                    $src = '/imports/'.\App\Support\PublicUpload::name($request->file('file'));
                    $csv = file_get_contents(public_path().$src);
                    $rows = explode(PHP_EOL, $csv);
                    $data = explode($delimiter, $rows[0]);
                    return view('patients.import',['step'=>"2",'column'=>$data,'src'=>$src,'delimiter'=>$delimiter,'title'=>'Patients Import','br1'=>'Patients','br2'=>'Patient Import','alert'=>'']);
                } else {
                    $src = '';
                    return view('patients.import',['step'=>"1",'title'=>'Patients Import','br1'=>'Patients','br2'=>'Patient Import','alert'=>'File was not uploaded.']);
                }
            } else if ($request->input('step')==2) {
                $src = $request->input('src');
                $delimiter = $request->input('delimiter');
                $csv = file_get_contents(public_path().$src);
                $rows = explode(PHP_EOL, $csv);
                $data = explode($delimiter, $rows[0]);
                foreach($data as $key=>$r) {
                    $data[$key] = str_replace(["\r\n","\r","\n"], '', $r);
                }
                $key_name = array_search($request->input('name'), $data);
                $key_last_name = array_search($request->input('last_name'), $data);
                if(empty($request->input('email'))) {
                    $key_email = NULL;
                } else {
                    $key_email = array_search($request->input('email'), $data);
                }
                $key_phone = array_search($request->input('phone'), $data);
                if(empty($request->input('home_phone'))) {
                    $key_home_phone = NULL;
                } else {
                    $key_home_phone = array_search($request->input('home_phone'), $data);
                }
                $key_address = array_search($request->input('address'), $data);
                if(empty($request->input('apartment'))) {
                    $key_apartment = NULL;
                } else {
                    $key_apartment = array_search($request->input('apartment'), $data);
                }
                $key_zip = array_search($request->input('zip'), $data);
                if(empty($request->input('birth_date'))) {
                    $key_birth_date = NULL;
                } else {
                    $key_birth_date = array_search($request->input('birth_date'), $data);
                }
                foreach ($rows as $key => $row) {
                    if($key>0 && $row!='') {
                        $data0 = explode($delimiter, $row);
                        if(!empty($key_email) || !empty($data0[$key_email])){
                            if(!empty(DB::table('users')->where('email', $data0[$key_email])->where('pharmacy_id', $pharmacy_id)->first())) {
                                unset($rows[$key]);
                                //return view('patients.import',['step'=>"1",'title'=>'Patients Import','br1'=>'Patients','br2'=>'Patient Import','alert'=>"User with this email (".$data0[$key_email].") already exists"]);
                            }
                        }
                        if(!empty($key_phone) || !empty($data0[$key_phone])){
                            if(preg_match( '/^\+\d(\d{3})(\d{3})(\d{4})$/', $data0[$key_phone],  $matches )) {
                                $phone = '('.$matches[1].') ' .$matches[2] . '-' . $matches[3];
                            } else {
                                if(preg_match('/^(\d{3})(\d{3})(\d{4})$/', $data0[$key_phone],  $matches )) {
                                    $phone = '('.$matches[1].') ' .$matches[2] . '-' . $matches[3];
                                } else {
                                    if(preg_match('/^(\d{3})\-(\d{3})\-(\d{4})$/', $data0[$key_phone],  $matches )) {
                                        $phone = '('.$matches[1].') ' .$matches[2] . '-' . $matches[3];
                                    } else {
                                        $phone = $data0[$key_phone];
                                    }
                                }
                            }
                            if(!empty(DB::table('users')->where('phone', $phone)->where('pharmacy_id', $pharmacy_id)->first())) {
                                unset($rows[$key]);
                                //return view('patients.import',['step'=>"1",'title'=>'Patients Import','br1'=>'Patients','br2'=>'Patient Import','alert'=>"User with this phone (".$data0[$key_phone].") already exists"]);
                            }
                        }
                    }
                }
                foreach ($rows as $key => $row) {
                    if($key>0 && $row!='') {
                        $data0 = explode($delimiter, $row);
                        if(empty($key_email) || empty($data0[$key_email])){
                            $email = 'patients'.DB::table('users')->max('id').'@'.config('branding.account_email_domain');
                        } else {
                            $email = $data0[$key_email];
                        }
                        if(empty($key_phone) || empty($data0[$key_phone])){
                            $phone = NULL;
                        } else {
                            if(preg_match( '/^\+\d(\d{3})(\d{3})(\d{4})$/', $data0[$key_phone],  $matches )) {
                                $phone = '('.$matches[1].') ' .$matches[2] . '-' . $matches[3];
                            } else {
                                if(preg_match('/^(\d{3})(\d{3})(\d{4})$/', $data0[$key_phone],  $matches )) {
                                    $phone = '('.$matches[1].') ' .$matches[2] . '-' . $matches[3];
                                } else {
                                    if(preg_match('/^(\d{3})\-(\d{3})\-(\d{4})$/', $data0[$key_phone],  $matches )) {
                                        $phone = '('.$matches[1].') ' .$matches[2] . '-' . $matches[3];
                                    } else {
                                        $phone = $data0[$key_phone];
                                    }
                                }
                            }
                        }
                        if(empty($key_home_phone) || empty($data0[$key_home_phone])){
                            $home_phone = NULL;
                        } else {
                            $home_phone = $data0[$key_home_phone];
                        }
                        if(empty($key_apartment) || empty($data0[$key_apartment])){
                            $apartment = NULL;
                        } else {
                            $apartment = $data0[$key_apartment];
                        }
                        if(empty($key_birth_date) || empty($data0[$key_birth_date])){
                            $birth_date = NULL;
                        } else {
                            $birth_date = $data0[$key_birth_date];
                        }
                        $password = bin2hex(openssl_random_pseudo_bytes(4));
                        DB::table('users')->insert(['isactive' => '1','name' => $data0[$key_name],'last_name' => $data0[$key_last_name],'email' => $email,'phone' => $phone,'home_phone' => $home_phone,'address' => $data0[$key_address],'password' => Hash::make($password),'apartment' => $apartment,'zip' => $data0[$key_zip],'birth_date' => date("Y-m-d",strtotime($birth_date ?? '')),'pharmacy_id' => $pharmacy_id]);
                    }
                }
                return redirect("patients/$pharmacy_id");
            }
        } else {
            return abort(403, AccessMessages::NO_PERMISSION);
        }
    }

    /**
     * @param int|string $pharmacy_id
     */
    public function import_order($pharmacy_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, AccessMessages::ACCOUNT_INACTIVE);
        }
        if(Auth::user()->pharmacy_balance_ban()) {
            return redirect("billing/".Auth::user()->pharmacy_id, 302);
        }
        if(Auth::user()->can('manage-pharmacy', $pharmacy_id)) {
            return view('import.step1',['alert'=>'','title'=>'Import Order','br1'=>'Import','br2'=>'Import Order']);
        } else {
            return abort(403, AccessMessages::NO_PERMISSION);
        }
    }

    /**
     * @param int|string $pharmacy_id
     */
    public function import_orderHandler($pharmacy_id,Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, AccessMessages::ACCOUNT_INACTIVE);
        }
        if(Auth::user()->pharmacy_balance_ban()) {
            return redirect("billing/".Auth::user()->pharmacy_id, 302);
        }
        if(Auth::user()->can('manage-pharmacy', $pharmacy_id)) {
            if($request->input('step2')>0) {
                $request->validate([
                    'import' => 'required|mimes:pdf|max:2048'
                ]);
                $parser = new Parser();
                $pdf = $parser->parseFile($request->file('import'));
                $text = $pdf->getPages()[0]->getDataTm();
                $user = new User;
                if(strpos($text[11][1],',')===false) {
                    $user->name=str_replace(' ','',explode(',',$text[12][1])[0]);
                    $user->last_name=str_replace(' ','',explode(',',$text[12][1])[1]);
                } else {
                    $user->name=str_replace(' ','',explode(',',$text[11][1])[0]);
                    $user->last_name=str_replace(' ','',explode(',',$text[11][1])[1]);
                }
                $user->phone=(strpos($text[2][1],'Cell#: () -')!==false)?str_replace('Ph#: ','',$text[1][1]):str_replace('Cell#: ','',$text[2][1]);
                $user->home_phone='';
                $user->address=$text[0][1];
                $user->apartment=(strpos($text[0][1],'APT')!==false)?str_replace(' ','',explode(',',explode('APT',$text[0][1])[1])[0]):'';
                $addr = explode(' ',$text[0][1]);
                $user->zip=end($addr);
                $user->pharmacy_id=$pharmacy_id;
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
                $delivery_methods = DB::table('delivery_methods')->get();
                $delivery_times = DB::table('delivery_times')->get();
                $pharmacy=DB::table('pharmacys')->where('pharmacys.id',$pharmacy_id)->first();
                $drivers = DB::table('users')->where('role', 'driver')->where("pharmacy_id",$pharmacy_id)->get();
                $time_ranges = ["9:00 AM","10:00 AM","11:00 AM","12:00 PM", "1:00 PM","2:00 PM","3:00 PM","4:00 PM","5:00 PM","6:00 PM","7:00 PM","8:00 PM","9:00 PM","10:00 PM","11:00 PM","12:00 AM"];
                return view('import.step2',['user'=>$user,'pharmacy'=>$pharmacy,'time_ranges'=>$time_ranges,'drivers'=>$drivers,'delivery_methods'=>$delivery_methods,'delivery_times'=>$delivery_times,'copay'=>$copay,'rxs'=>$rxs,'alert'=>'','title'=>'Import Order','br1'=>'Import','br2'=>'Import Order']);
            }
            if($request->input('save')>0) {
                $user = DB::table('users')->where('phone', $request->input('phone'))->where('pharmacy_id', $pharmacy_id)->first();
                if(empty($user)) {
                    $src = '';
                    $email = $request->input('email');
                    if(empty($email)){
                        $email = 'patients'.(intval(DB::table('users')->max('id'))+1).'@'.config('branding.account_email_domain');
                    }
                    $address = $request->input('address');
                    $data = app(\App\Actions\GeocodeAddress::class)->response($address);
                    $address = $data->results[0]->formatted_address;
                    $location = $data->results[0]->geometry->location->lat.','.$data->results[0]->geometry->location->lng;
                    DB::table('users')->insert(['isactive'=>1,'name' => $request->input('name'),'last_name' => $request->input('last_name'),'email' => $email,'phone' => $request->input('phone'),'image' => $src,'address' => $address,'location' => $location,'password' => Hash::make($request->input('password')),'zip' => $request->input('zip'),'apartment' => $request->input('apartment'),'pharmacy_id' => $pharmacy_id]);
                } else {
                    $address = $request->input('address');
                    $data = app(\App\Actions\GeocodeAddress::class)->response($address);
                    $address = $data->results[0]->formatted_address;
                    $location = $data->results[0]->geometry->location->lat.','.$data->results[0]->geometry->location->lng;
                    DB::table('users')->where('id',$user->id)->update(['name' => $request->input('name'),'last_name' => $request->input('last_name'),'address' => $address,'location' => $location,'zip' => $request->input('zip'),'apartment' => $request->input('apartment')]);
                }
                $user = DB::table('users')->where('phone', $request->input('phone'))->where('pharmacy_id', $pharmacy_id)->first();
                $id_max = DB::table('orders')->max('id')+1;
                $copay = (empty($request->input('copay')))?'0':round($request->input('copay'),2);
                $statuse_copay = (empty($request->input('copay')))?'1':'2';
                if(!empty($request->input('copay_paid_pharm'))) {
                    $statuse_copay='6';
                }
                $fridge = (empty($request->input('fridge')))?'0':$request->input('fridge');
                $special_instructions = (empty($request->input('special_instructions')))?NULL:addslashes($request->input('special_instructions'));
                $rx_ids = $request->input('rx_id');
                $rx_dates = $request->input('rx_date');
                $data=[];
                foreach(array_keys($rx_ids) as $key){
                    $data[]=["order_id"=>$id_max,"rx_id"=>str_replace(" ",'',$rx_ids[$key]),"rx_date"=>$rx_dates[$key]];
                }
                DB::table('rxs')->insert($data);
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
                DB::table('orders')->insert(['id'=>$id_max,'pharmacy_id' => $pharmacy_id, 'medic_id' => Auth::user()->id, 'driver_id'=>$driver_id, 'user_id' => $user->id, 'copay' => $copay, 'statuse_copay' => $statuse_copay, 'delivery_method_id' => $request->input('delivery_method'), 'special_instructions' => $special_instructions, 'count_bags' => $request->input('count_bags'), 'extra_charge_driver'=>floatval($request->input('extra_charge_driver')), 'type_driver' => $request->input('type_driver'), 'delivery_time_id' => $request->input('delivery_time'), 'delivery_time_range' => $delivery_time_range, 'delivery_date'=>$delivery_date, 'fridge' => $fridge]);
                Notifications::send_push($user->id,"QuikMedix","QuikMedix is greeting you! Your order #$id_max is ready to be shipped to this address: $user->address If the address is wrong, please contact ".\App\Support\Branding::supportContact()." as soon as possible");
                if($request->input('delivery_time')==3 || $request->input('delivery_time')==4) {
                    $pharmacy = DB::table('pharmacys')->where('id', $pharmacy_id)->first();
                    Notifications::send_push_web(array_map('strval', User::where('role', "admin")->orWhere("role","logist")->pluck('id')->toArray()),
                        "Attention!",
                        "Urgent order No.".$id_max." has been created, which needs to be processed promptly.",
                        url('/')."/orders/".$pharmacy_id."?statuse%5B%5D=1",
                        "rush_order"
                    );
                }
            }
            return redirect("orders/$pharmacy_id?added=$id_max");
        } else {
            return abort(403, AccessMessages::NO_PERMISSION);
        }
    }
}
