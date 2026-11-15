<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersLegacyPages;
use App\Support\PageLinks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Support\AccessMessages;

/**
 * Tariff plans in settings: list, add and edit. Formerly actions of LexaAdmin, moved as they were.
 */
class PlanController extends Controller
{
    use RendersLegacyPages;

    public function __construct()
    {
        // Kept from LexaAdmin, whose pages load whole result sets at once.
        ini_set('memory_limit', '-1');
    }

    public function settingsPlans() {
        $countOnPage=20;
        $max_pages=ceil(DB::table('plans')->count()/$countOnPage);
        $page = $this->currentPage(request());
        $pages = PageLinks::around($page, $max_pages);
        $plans = DB::table('plans')->orderBy('id','desc')->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
        $res_view = view('plans.list',['plans'=>$plans,'pages'=>$pages,'title'=>'Tariff Plans List','br1'=>'Tariff','br2'=>'Tariff Plans']);
        return $this->page(request(), $res_view);
    }

    public function settingsPlansHandler(Request $request) {
        if($request->input('remove')>0) {
            DB::table('plans')->where('id', $request->input('plan_id'))->delete();
        }
        return redirect("settings/plans");
    }

    public function settingsPlansAdd() {
        $plan = new \stdClass();
        $plan->name="";
        $plan->order_rate="";
        $plan->tariff="";
        $plan->tariff_next_day="";
        $plan->tariff_same_day="";
        $plan->tariff_asap="";
        $plan->tariff_after_hours="";
        $plan->tariff_fridge="";
        $plan->tariff_area2="";
        $plan->tariff_area3="";
        $plan->tariff_area_more="";
        $res_view = view('plans.form',['plan'=>$plan,'alert'=>'','title'=>'Tariff Plan Add','br1'=>'Tariff','br2'=>'Tariff Plan Add']);
        return $this->page(request(), $res_view);
    }

    public function settingsPlansAddHandler(Request $request) {
        if($request->input('save')>0) {
            DB::table('plans')->insertGetId(['name' => $request->input('name'),'order_rate' => $request->input('order_rate'),'tariff' => $request->input('tariff'),'tariff_next_day' => $request->input('tariff_next_day'),'tariff_same_day' => $request->input('tariff_same_day'),'tariff_asap' => $request->input('tariff_asap'),'tariff_after_hours' => $request->input('tariff_after_hours'),'tariff_fridge' => $request->input('tariff_fridge'),'tariff_area2' => $request->input('tariff_area2'),'tariff_area3' => $request->input('tariff_area3'),'tariff_area_more' => $request->input('tariff_area_more')]);
        }
        return redirect("settings/plans");
    }

    /**
     * @param int|string $plan_id
     */
    public function settingsPlansEdit($plan_id) {
        $plan = DB::table('plans')->where('id', $plan_id)->first();
        $res_view = view('plans.form',['plan'=>$plan,'alert'=>'','title'=>'Tariff Plan Edit','br1'=>'Tariff','br2'=>'Tariff Plan Edit']);
        return $this->page(request(), $res_view);
    }

    /**
     * @param int|string $plan_id
     */
    public function settingsPlansEditHandler($plan_id, Request $request) {
        if($request->input('save')>0) {
            DB::table('plans')->where("id",$plan_id)->update(['name' => $request->input('name'),'order_rate' => $request->input('order_rate'),'tariff' => $request->input('tariff'),'tariff_next_day' => $request->input('tariff_next_day'),'tariff_same_day' => $request->input('tariff_same_day'),'tariff_asap' => $request->input('tariff_asap'),'tariff_after_hours' => $request->input('tariff_after_hours'),'tariff_fridge' => $request->input('tariff_fridge'),'tariff_area2' => $request->input('tariff_area2'),'tariff_area3' => $request->input('tariff_area3'),'tariff_area_more' => $request->input('tariff_area_more')]);
        }
        return redirect("settings/plans");
    }
}
