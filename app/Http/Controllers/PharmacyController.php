<?php

namespace App\Http\Controllers;

use App\Actions\GeocodeAddress;
use App\Http\Controllers\Concerns\RendersLegacyPages;
use App\Support\PageLinks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Pharmacies: the list with its bulk actions, adding and editing a pharmacy, its tariff map, and
 * the pharmacy software integrations page. Formerly actions of LexaAdmin, moved as they were.
 */
class PharmacyController extends Controller
{
    use RendersLegacyPages;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('2fa');
        // Kept from LexaAdmin, whose pages load whole result sets at once.
        ini_set('memory_limit', '-1');
    }

    public function pharmacysList() {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, LexaAdmin::$err_act_ban);
        }
        if((Auth::user()->can('admin'))) {
            $filter = [];
            if(!empty(request()->query('start')) && !empty(request()->query('filter'))) {
                $filter["start"] = request()->query('start');
            } else {
                $filter["start"] = "";
            }
            if(!empty(request()->query('end')) && !empty(request()->query('filter'))) {
                $filter["end"] = request()->query('end');
            } else {
                if(!empty($filter["start"])) {
                    $filter["end"] = date('m/d/Y');
                } else {
                    $filter["end"] = "";
                }
            }
            if(!empty(request()->query('advanced')) && !empty(request()->query('filter'))) {
                $filter["advanced"] = request()->query('advanced');
            } else {
                $filter["advanced"] = "";
            }
            $pharmacys = DB::table('pharmacys')->leftJoin('orders', function($query) {
                $query->on('pharmacys.id','=','orders.pharmacy_id')
                ->whereDate('orders.created', '>', date('Y-m-d', strtotime('now -7 day')));
             })->leftJoin('users','pharmacys.admin_id','=','users.id')->select("pharmacys.id","pharmacys.isactive","pharmacys.isblocked","pharmacys.email","pharmacys.phone","pharmacys.name","pharmacys.tariff","pharmacys.address","pharmacys.location","pharmacys.logo","pharmacys.image_front","pharmacys.admin_id",DB::raw("users.isactive as isactiveuser"),"pharmacys.ref_id","pharmacys.balance");
            if(!empty(request()->query('search'))) {
                $search = request()->query('search');
                $pharmacys = $pharmacys->where(function($query) use ($search) {
                    $query->where('pharmacys.name','LIKE','%'.$search.'%')
                          ->orWhere('pharmacys.email','LIKE','%'.$search.'%')
                          ->orWhere('pharmacys.phone','LIKE','%'.$search.'%')
                          ->orWhere('pharmacys.address','LIKE','%'.$search.'%');
                    });
            } else {
                $search='';
            }
            if(!empty($filter["start"]) && !empty($filter["end"])) {
                $pharmacys = $pharmacys->whereBetween('pharmacys.created',[\DateTime::createFromFormat('m/d/Y',$filter["start"])->format('Y-m-d'),\DateTime::createFromFormat('m/d/Y',$filter["end"])->format('Y-m-d')]);
            }
            if(!empty(request()->query('without_orders')) || $filter["advanced"]=='without_orders') {
                $pharmacys = $pharmacys->whereNull('orders.id');
            }
            if(!empty(request()->query('pharmacys_balance')) || $filter["advanced"]=='pharmacys_balance') {
                $pharmacys = $pharmacys->where('pharmacys.balance','<',0);
            }
            if(!empty(request()->query('pharmacys_blocked')) || $filter["advanced"]=='pharmacys_blocked') {
                $pharmacys = $pharmacys->where('pharmacys.balance_ban','1');
            }
            if($filter["advanced"]=='pharmacys_blocked_permanently') {
                $pharmacys = $pharmacys->where('pharmacys.isblocked','1');
            }
            if(!empty(Auth::user()->zone_id)){
                $pharmacys=$pharmacys->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $pharmacys = $pharmacys->groupBy("pharmacys.id","pharmacys.isactive","pharmacys.isblocked","pharmacys.email","pharmacys.phone","pharmacys.name","pharmacys.tariff","pharmacys.address","pharmacys.location","pharmacys.logo","pharmacys.image_front","pharmacys.admin_id","users.isactive","pharmacys.ref_id","pharmacys.balance")->orderBy(DB::raw("count(DISTINCT orders.id)"),"desc");
            $countOnPage=30;
            $max_pages=ceil($pharmacys->count()/$countOnPage);
            $page = $this->currentPage(request());
            $pages = PageLinks::around($page, $max_pages);
            $pharmacys = $pharmacys->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
            $res_arr = ['pages'=>$pages,'page0'=>$page,'search'=>$search,'pharmacys'=>$pharmacys,'filter'=>$filter,'title'=>'Pharmacies List','br1'=>'Pharmacy','br2'=>'List'];
            return view('pharmacy.list',$res_arr);
        } else if(Auth::user()->role == 'sale') {
            $pharmacys = DB::table('pharmacys')->where("ref_id",Auth::user()->id);
            if(!empty(request()->query('search'))) {
                $search = request()->query('search');
                $pharmacys = $pharmacys->where(function($query) use ($search) {
                    $query->where('name','LIKE','%'.$search.'%')
                          ->orWhere('email','LIKE','%'.$search.'%')
                          ->orWhere('phone','LIKE','%'.$search.'%')
                          ->orWhere('address','LIKE','%'.$search.'%');
                    });
            } else {
                $search='';
            }
            $countOnPage=30;
            $max_pages=ceil($pharmacys->count()/$countOnPage);
            $page = $this->currentPage(request());
            $pages = PageLinks::around($page, $max_pages);
            $pharmacys = $pharmacys->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
            $res_view = view('pharmacy.list_sale',['pages'=>$pages,'page0'=>$page,'search'=>$search,'pharmacys'=>$pharmacys,'title'=>'Pharmacies List','br1'=>'Pharmacy','br2'=>'List']);
            return $this->page(request(), $res_view);
        } else {
            return abort(403, LexaAdmin::$err_perm);
        }
    }

    public function pharmacysListHandler(Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, LexaAdmin::$err_act_ban);
        }
        if((Auth::user()->can('admin'))) {
            if($request->input('block')>0) {
                DB::table('pharmacys')->where('id', $request->input('user_id'))->update(['isblocked' => 1]);
            }
            if($request->input('unblock')>0) {
                DB::table('pharmacys')->where('id', $request->input('user_id'))->update(['isblocked' => 0]);
            }
            if($request->input('activate')>0) {
                DB::table('pharmacys')->where('id', $request->input('user_id'))->update(['isactive' => 1]);
            }
            if($request->input('activateuser')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['isactive' => 1]);
            }
            if($request->input('remove')>0) {
                DB::table('pharmacys')->where('id', $request->input('user_id'))->delete();
            }
            return redirect('pharmacys');
        } else {
            return abort(403, LexaAdmin::$err_perm);
        }
    }

    /**
     * @param int|string $pharmacy_id
     */
    public function pharmacysListEdit($pharmacy_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, LexaAdmin::$err_act_ban);
        }
        if((Auth::user()->can('admin'))) {
            $pharmacy = DB::table('pharmacys')->where('id', $pharmacy_id)->first();
            if(!empty($pharmacy->schedule)){
                $pharmacy->schedule=json_decode($pharmacy->schedule);
            }
            $pharmacy_areas1 = DB::table('pharmacy_areas')->where('pharmacy_id', $pharmacy_id)->where('type', 1)->pluck("area_id")->toArray();
            $pharmacy_areas2 = DB::table('pharmacy_areas')->where('pharmacy_id', $pharmacy_id)->where('type', 2)->pluck("area_id")->toArray();
            $pharmacy_areas3 = DB::table('pharmacy_areas')->where('pharmacy_id', $pharmacy_id)->where('type', 3)->pluck("area_id")->toArray();
            $plans = DB::table('plans')->orderBy('id','desc')->get();
            $pharmacy_plan = DB::table('plans')->where('id', $pharmacy->plan_id)->first();
            $areas = DB::table('area')->get();
            $admin= DB::table('users')->where('id', $pharmacy->admin_id)->first();
            $admin_areas = DB::table('admin_areas')->get();
            $res_view = view('pharmacy.pharmacy_edit',['pharmacy'=>$pharmacy,'admin_areas'=>$admin_areas,'pharmacy_areas1'=>$pharmacy_areas1,'pharmacy_areas2'=>$pharmacy_areas2,'pharmacy_areas3'=>$pharmacy_areas3,'plans'=>$plans,'pharmacy_plan'=>$pharmacy_plan,'areas'=>$areas,'admin'=>$admin, 'title'=>'Pharmacy Edit','br1'=>'Pharmacies','br2'=>'Pharmacy','br3'=>'Pharmacy Edit','alert'=>'']);
            return $this->page(request(), $res_view);
        } else {
            return abort(403, LexaAdmin::$err_perm);
        }
    }

    /**
     * @param int|string $pharmacy_id
     */
    public function pharmacysListEditHandler(Request $request,$pharmacy_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, LexaAdmin::$err_act_ban);
        }
        if((Auth::user()->can('admin'))) {
            if($request->input('save')>0) {
                $pharmacy = DB::table('pharmacys')->where('id', $pharmacy_id)->first();
                if($request->hasFile('image')) {
                    $file = $request->file('image');
                    $name_f = \App\Support\PublicUpload::name($request->file('image'));
                    $file->move(public_path() . '/images/pharmacys/',$name_f);
                    $src = '/images/pharmacys/'.$name_f;
                } else {
                    $src = $pharmacy->logo;
                }
                if($request->hasFile('image_front')) {
                    $file = $request->file('image_front');
                    $name_f = \App\Support\PublicUpload::name($request->file('image_front'));
                    $file->move(public_path() . '/images/pharmacys/',$name_f);
                    $src2 = '/images/pharmacys/'.$name_f;
                } else {
                    $src2 = $pharmacy->image_front;
                }
                if(!empty($request->input('massiveBagsTransfer')) && $request->input('massiveBagsTransfer')>0) {
                    $massiveBagsTransfer = "1";
                } else {
                    $massiveBagsTransfer = "0";
                }
                if(!empty($request->input('copay_bill')) && $request->input('copay_bill')>0) {
                    $copay_bill = "1";
                } else {
                    $copay_bill = "0";
                }
                $schedule=[];
                if(!empty($request->input('schedule_open')) && !empty($request->input('schedule_close'))) {
                    foreach($request->input('schedule_open') as $key=>$open) {
                        $close = $request->input('schedule_close')[$key];
                        if(!empty($open) && !empty($close)) {
                            $schedule[$key]=new \StdClass;
                            $schedule[$key]->open=$open;
                            $schedule[$key]->close=$close;
                        }
                    }
                }
                if($pharmacy->copay_bill!=$copay_bill) {
                    $invoices = DB::table('invoices')->where('pharmacy_id',$pharmacy_id)->where("payed","0")->get();
                    $sum_amount = 0;
                    foreach($invoices as $invoice) {
                        if($copay_bill=="1"){
                            if((($invoice->amount+$invoice->corrections)-$invoice->copay)<0){
                                $sum_amount = $sum_amount+0;
                            } else {
                                $sum_amount = $sum_amount+(($invoice->amount+$invoice->corrections)-$invoice->copay);
                            }
                        } else {
                            $sum_amount = $sum_amount+($invoice->amount+$invoice->corrections);
                        }
                    }
                    $balance = floatval($sum_amount)*-1;
                    DB::table('pharmacys')->where("id",$pharmacy_id)->update(['balance'=>$balance]);
                }
                if(!empty($request->input('npi'))){
                    $npi = $request->input('npi');
                } else {
                    $npi = $pharmacy->npi;
                }
                if(!empty($request->input('bestrx_pharmacy_id'))){
                    $bestrx_pharmacy_id = $request->input('bestrx_pharmacy_id');
                } else {
                    $bestrx_pharmacy_id = $pharmacy->bestrx_pharmacy_id;
                }
                if(!empty($request->input('ncpdp'))){
                    $ncpdp = $request->input('ncpdp');
                } else {
                    $ncpdp = $pharmacy->ncpdp;
                }
                if(!empty($request->input('dea'))){
                    $dea = $request->input('dea');
                } else {
                    $dea = $pharmacy->dea;
                }
                if(!empty($request->input('merchantFunc')) && $request->input('merchantFunc')>0) {
                    $merchantFunc = "1";
                    if(empty($pharmacy->merchantKey)){
                        $merchantKey = bin2hex(random_bytes(14));
                        $merchantSecret = bin2hex(random_bytes(20));
                    } else {
                        $merchantKey = $pharmacy->merchantKey;
                        $merchantSecret = $pharmacy->merchantSecret;
                    }
                } else {
                    $merchantFunc = "0";
                    $merchantKey = NULL;
                    $merchantSecret = NULL;
                }
                if(!empty($request->input('bestrxFunc')) && $request->input('bestrxFunc')>0) {
                    $bestrxFunc = "1";
                    if(empty($pharmacy->bestrxKey)){
                        $bestrxKey = bin2hex(random_bytes(14));
                        $bestrxSecret = bin2hex(random_bytes(20));
                    } else {
                        $bestrxKey = $pharmacy->bestrxKey;
                        $bestrxSecret = $pharmacy->bestrxSecret;
                    }
                } else {
                    $bestrxFunc = "0";
                    $bestrxKey = NULL;
                    $bestrxSecret = NULL;
                }
                DB::table('pharmacy_areas')->where('pharmacy_id',$pharmacy_id)->delete();
                $tariff_areas1 = $request->input('tariff_areas1');
                $tariff_areas2 = $request->input('tariff_areas2');
                $tariff_areas3 = $request->input('tariff_areas3');
                $data=[];
                if(!empty($tariff_areas1)) {
                    foreach($tariff_areas1 as $key=>$value){
                        $data[]=["pharmacy_id"=>$pharmacy_id,"area_id"=>$value,"type"=>1];
                    }
                }
                if(!empty($tariff_areas2)) {
                    foreach($tariff_areas2 as $key=>$value){
                        $data[]=["pharmacy_id"=>$pharmacy_id,"area_id"=>$value,"type"=>2];
                    }
                }
                if(!empty($tariff_areas3)) {
                    foreach($tariff_areas3 as $key=>$value){
                        $data[]=["pharmacy_id"=>$pharmacy_id,"area_id"=>$value,"type"=>3];
                    }
                }
                DB::table('pharmacy_areas')->insert($data);
                if($request->input('plan_id')=="2"){
                    if(!empty($request->input('date_end_trial'))) {
                        $date_end_trial = $request->input('date_end_trial');
                    } else {
                        if(empty($pharmacy->date_end_trial)){
                            $date_end_trial = date('Y-m-d', strtotime('now +3 day'));
                        } else {
                            $date_end_trial = $pharmacy->date_end_trial;
                        }
                    }
                } else {
                    $date_end_trial = NULL;
                }
                $data = app(\App\Actions\GeocodeAddress::class)->response($request->input('address'));
                $location = $data->results[0]->geometry->location->lat.','.$data->results[0]->geometry->location->lng;
                DB::table('pharmacys')->where('id', $pharmacy_id)->update(['name' => $request->input('name'),'email' => $request->input('email'),'plan_id' => $request->input('plan_id'),'date_end_trial'=>$date_end_trial,'tariff' => $request->input('tariff'),'tariff_next_day' => $request->input('tariff_next_day'),'tariff_same_day' => $request->input('tariff_same_day'),'tariff_asap' => $request->input('tariff_asap'),'tariff_after_hours' => $request->input('tariff_after_hours'),'tariff_fridge' => $request->input('tariff_fridge'),'tariff_area2' => $request->input('tariff_area2'),'tariff_area3' => $request->input('tariff_area3'),'tariff_area_more' => $request->input('tariff_area_more'),'phone' => $request->input('phone'),'logo' => $src,'image_front'=>$src2,'site' => $request->input('site'),'copay_bill'=>$copay_bill,'massiveBagsTransfer'=>$massiveBagsTransfer,'merchantFunc'=>$merchantFunc,'merchantKey'=>$merchantKey,'merchantSecret'=>$merchantSecret,'npi'=>$npi,'ncpdp'=>$ncpdp,'dea'=>$dea,'bestrxFunc'=>$bestrxFunc,'bestrx_pharmacy_id'=>$bestrx_pharmacy_id,'bestrxKey'=>$bestrxKey,'bestrxSecret'=>$bestrxSecret,'address' => $request->input('address'),'schedule'=>json_encode($schedule),'location' => $location,'zone_id'=>$request->input('zone_id')]);
            }
            return redirect("pharmacys/edit/$pharmacy_id");
        } else {
            return abort(403, LexaAdmin::$err_perm);
        }
    }

    public function pharmacysListAdd() {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, LexaAdmin::$err_act_ban);
        }
        if(Auth::user()->hasAnyRole('superadmin', 'admin', 'dispadmin', 'user')) {
            $plans = DB::table('plans')->orderBy('id','desc')->get();
            $areas = DB::table('area')->get();
            $input['name']='';
            $input['email']='';
            $input['phone']='';
            $input['image']='';
            $input['image_front']='';
            $input['site']='';
            $input['zone_id']='';
            $input['address']='';
            $input['tariff']='';
            $input['tariff_next_day']='';
            $input['tariff_same_day']='';
            $input['tariff_asap']='';
            $input['tariff_after_hours']='';
            $input['tariff_fridge']='';
            $input['plan_id']='';
            $input['tariff_areas']=[];
            $admin_areas = DB::table('admin_areas')->get();
            $res_view = view('pharmacy.pharmacy_add',['plans'=>$plans,'areas'=>$areas,'admin_areas'=>$admin_areas,'title'=>'Pharmacy Add','br1'=>'Pharmacies','br2'=>'Pharmacy','br3'=>'Pharmacy Add','alert'=>'','input'=>$input]);
            return $this->page(request(), $res_view);
        } else {
            return abort(403, LexaAdmin::$err_perm);
        }
    }

    public function pharmacysListAddHandler(Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, LexaAdmin::$err_act_ban);
        }
        if((Auth::user()->can('admin'))) {
            if($request->input('save')>0) {
                $tariffSelection = $request->validate([
                    'plan_id' => 'required|integer|exists:plans,id',
                    'tariff_areas' => 'nullable|array',
                    'tariff_areas.*' => 'integer|distinct|exists:area,id',
                ], [
                    'plan_id.required' => 'Create and select a tariff plan before saving the pharmacy.',
                    'plan_id.exists' => 'The selected tariff plan no longer exists. Please select another plan.',
                    'tariff_areas.*.exists' => 'One of the selected tariff areas no longer exists. Please select it again.',
                ]);
                if(!empty(DB::table('pharmacys')->where('email', $request->input('email'))->first())) {
                    $plans = DB::table('plans')->orderBy('id','desc')->get();
                    $areas = DB::table('area')->get();
                    $input['name']=$request->input('name');
                    $input['email']=$request->input('email');
                    $input['phone']=$request->input('phone');
                    $input['image']='';
                    $input['image_front']='';
                    $input['site']=$request->input('site');
                    $input['zone_id']=$request->input('zone_id');
                    $input['address']=$request->input('address');
                    $input['tariff']=$request->input('tariff');
                    $input['tariff_next_day']=$request->input('tariff_next_day');
                    $input['tariff_same_day']=$request->input('tariff_same_day');
                    $input['tariff_asap']=$request->input('tariff_asap');
                    $input['tariff_after_hours']=$request->input('tariff_after_hours');
                    $input['tariff_fridge']=$request->input('tariff_fridge');
                    $input['plan_id']=$request->input('plan_id');
                    $input['tariff_areas']=$request->input('tariff_areas', []);
                    $admin_areas = DB::table('admin_areas')->get();
                    return view('pharmacy.pharmacy_add',['plans'=>$plans,'areas'=>$areas,'admin_areas'=>$admin_areas,'title'=>'Pharmacy Add','br1'=>'Pharmacies','br2'=>'Pharmacy','br3'=>'Pharmacy Add','alert'=>'Pharmacy with this email already exists','input'=>$input]);
                } else {
                    $location = self::pharmacyCreationLocation($request);
                    if($request->hasFile('image')) {
                        $file = $request->file('image');
                        $name_f = \App\Support\PublicUpload::name($request->file('image'));
                        $file->move(public_path() . '/images/pharmacys/',$name_f);
                        $src = '/images/pharmacys/'.$name_f;
                    } else {
                        $src = "";
                    }
                    if($request->hasFile('image_front')) {
                        $file = $request->file('image_front');
                        $name_f = \App\Support\PublicUpload::name($request->file('image_front'));
                        $file->move(public_path() . '/images/pharmacys/',$name_f);
                        $src2 = '/images/pharmacys/'.$name_f;
                    } else {
                        $src2 = "";
                    }
                    if(!empty($request->input('massiveBagsTransfer')) && $request->input('massiveBagsTransfer')>0) {
                        $massiveBagsTransfer = "1";
                    } else {
                        $massiveBagsTransfer = "0";
                    }
                    DB::transaction(function () use ($request, $src, $src2, $massiveBagsTransfer, $location, $tariffSelection) {
                        $pharmacy_id = DB::table('pharmacys')->insertGetId(['name' => $request->input('name'),'email' => $request->input('email'),'plan_id' => $request->input('plan_id'),'tariff' => $request->input('tariff'),'tariff_next_day' => $request->input('tariff_next_day'),'tariff_same_day' => $request->input('tariff_same_day'),'tariff_asap' => $request->input('tariff_asap'),'tariff_after_hours' => $request->input('tariff_after_hours'),'tariff_fridge' => $request->input('tariff_fridge'),'phone' => $request->input('phone'),'logo' => $src,'image_front' => $src2,'site' => $request->input('site'),'massiveBagsTransfer'=>$massiveBagsTransfer,'address' => $request->input('address'),'location' => $location,'zone_id'=>$request->input('zone_id')]);
                        $areas = array_map(function ($area_id) use ($pharmacy_id) {
                            return ['pharmacy_id' => $pharmacy_id, 'area_id' => $area_id, 'type' => 1];
                        }, $tariffSelection['tariff_areas'] ?? []);
                        if ($areas) {
                            DB::table('pharmacy_areas')->insert($areas);
                        }
                    });
                }
            }
            return redirect("pharmacys");
        } else if(Auth::user()->role == 'user') {
            if($request->input('save')>0) {
                if(!empty(DB::table('pharmacys')->where('email', $request->input('email'))->first())) {
                    $input['name']=$request->input('name');
                    $input['email']=$request->input('email');
                    $input['phone']=$request->input('phone');
                    $input['image']='';
                    $input['image_front']='';
                    $input['site']='';
                    $input['address']=$request->input('address');
                    return view('pharmacy.pharmacy_add',['title'=>'Pharmacy Add','br1'=>'Pharmacies','br2'=>'Pharmacy','br3'=>'Pharmacy Add','alert'=>'Pharmacy with this email already exists','input'=>$input]);
                } else {
                    if($request->hasFile('image')) {
                        $file = $request->file('image');
                        $name_f = \App\Support\PublicUpload::name($request->file('image'));
                        $file->move(public_path() . '/images/pharmacys/',$name_f);
                        $src = '/images/pharmacys/'.$name_f;
                    } else {
                        $src = '';
                    }
                    if($request->hasFile('image_front')) {
                        $file = $request->file('image_front');
                        $name_f = \App\Support\PublicUpload::name($request->file('image_front'));
                        $file->move(public_path() . '/images/pharmacys/',$name_f);
                        $src2 = '/images/pharmacys/'.$name_f;
                    } else {
                        $src2 = '';
                    }
                    $data = app(\App\Actions\GeocodeAddress::class)->response($request->input('address'));
                    $location = $data->results[0]->geometry->location->lat.','.$data->results[0]->geometry->location->lng;
                    DB::table('pharmacys')->insert(['isactive'=>'1','name' => $request->input('name'),'email' => $request->input('email'),'phone' => $request->input('phone'),'logo' => $src,'image_front' => $src2,'site' => $request->input('site'),'address' => $request->input('address'),'location' => $location,'admin_id'=>Auth::user()->id,'ip'=>$request->ip()]);
                    DB::table('users')->where('id', Auth::user()->id)->update(['role' => 'medic','pharmacy_id' => DB::table('pharmacys')->where('admin_id', Auth::user()->id)->value('id')]);
                }
            }
            return redirect("pharmacys");
        } else {
            return abort(403, LexaAdmin::$err_perm);
        }
    }

    private function pharmacyCreationLocation(Request $request) {
        $request->validate(['address' => ['required', 'string', 'max:500']]);
        return app(GeocodeAddress::class)->handle($request->input('address'));
    }

    /**
     * @param int|string $pharmacy_id
     */
    public function pharmacysTariffMap($pharmacy_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, LexaAdmin::$err_act_ban);
        }
        if((Auth::user()->can('admin'))) {
            $pharmacy = DB::table('pharmacys')->where('id', $pharmacy_id)->first();
            $pharmacy_areas = DB::table('pharmacys')->where('id', $pharmacy_id)->get();
            $pharmacy_plan = DB::table('plans')->where('id', $pharmacy->plan_id)->first();
            $polygons = DB::table('area')->join('pharmacy_areas','pharmacy_areas.area_id',"=","area.id")->where('pharmacy_areas.pharmacy_id',$pharmacy_id)->select('area.name',DB::raw('ST_AsText(area.polygon) as polygon'),'pharmacy_areas.type')->get();
            foreach($polygons as $key=>$pol) {
                if(!empty($pol->polygon)) {
                    $polygons[$key]->polygon = \App\Support\WktPolygon::toJsonPoints($pol->polygon);
                } else {
                    $polygons[$key]->polygon = "";
                }
            }
            $res_view = view('pharmacy.tariff_map',['pharmacy'=>$pharmacy,'polygons'=>$polygons,'pharmacy_plan'=>$pharmacy_plan,'pharmacy_areas'=>$pharmacy_areas,'alert'=>'','title'=>'Tariff Map','br1'=>'Tariff','br2'=>'Tariff Map']);
            return $this->page(request(), $res_view);
        } else {
            return abort(403, LexaAdmin::$err_perm);
        }
    }

    public function pharmacysIntegrations(){
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, LexaAdmin::$err_act_ban);
        }
        if((Auth::user()->can('admin'))) {
            $pharmacys = DB::table('pharmacys')->where('pharmacys.merchantFunc','1')->where('pharmacys.isactive',1)->where('pharmacys.isblocked',0);
            if(!empty(request()->query('search'))) {
                $search = request()->query('search');
                $pharmacys = $pharmacys->where(function($query) use ($search) {
                    $query->where('pharmacys.name','LIKE','%'.$search.'%')
                          ->orWhere('pharmacys.email','LIKE','%'.$search.'%')
                          ->orWhere('pharmacys.phone','LIKE','%'.$search.'%')
                          ->orWhere('pharmacys.address','LIKE','%'.$search.'%')
                          ->orWhere('pharmacys.npi','LIKE','%'.$search.'%');
                    });
            } else {
                $search='';
            }
            $countOnPage=30;
            $max_pages=ceil($pharmacys->count()/$countOnPage);
            $page = $this->currentPage(request());
            $pages = PageLinks::around($page, $max_pages);
            if(!empty(Auth::user()->zone_id)){
                $pharmacys=$pharmacys->where('pharmacys.zone_id',Auth::user()->zone_id);
            }
            $pharmacys = $pharmacys->orderBy("pharmacys.id","desc")->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
            return view('pharmacy.integrations',['pages'=>$pages,'page0'=>$page,'search'=>$search,'pharmacys'=>$pharmacys,'title'=>'Pharmacys Integrations','br1'=>'Pharmacys','br2'=>'Integrations']);
        } else {
            return abort(403, LexaAdmin::$err_perm);
        }
    }

    public function pharmacysIntegrationsHandler(Request $request){
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, LexaAdmin::$err_act_ban);
        }
        if((Auth::user()->can('admin'))) {
            if($request->hasFile('merchant_agreement') && !empty($request->input('pharmacy_id'))) {
                $name=\App\Support\PublicUpload::name($request->file('merchant_agreement'));
                $file = $request->file('merchant_agreement');
                $file->move(public_path() . '/agreements/pharmacys/',$name);
                $src = '/agreements/pharmacys/'.$name;
                DB::table('pharmacys')->where('pharmacys.merchantFunc','1')->where('id',$request->input('pharmacy_id'))->update(['merchant_agreement'=>$src]);
            }
            return redirect("/pharmacys/integrations");
        } else {
            return abort(403, LexaAdmin::$err_perm);
        }
    }
}
