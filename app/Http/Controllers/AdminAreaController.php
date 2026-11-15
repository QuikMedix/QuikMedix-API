<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersLegacyPages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Administrator areas in settings: list, add and edit. Formerly actions of LexaAdmin, moved as
 * they were.
 */
class AdminAreaController extends Controller
{
    use RendersLegacyPages;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('2fa');
        // Kept from LexaAdmin, whose pages load whole result sets at once.
        ini_set('memory_limit', '-1');
    }

    public function settingsAdminAreas() {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, LexaAdmin::$err_act_ban);
        }
        if((Auth::user()->role == 'superadmin')) {
            $admin_areas = DB::table('admin_areas')->get();
            return view('settings.admin_areas',['admin_areas'=>$admin_areas,'title'=>'Settings Admin Zones','br1'=>'Settings','br2'=>'Admin Zones','alert'=>'']);
        } else {
            return abort(403, LexaAdmin::$err_perm);
        }
    }

    public function settingsAdminAreasAdd() {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, LexaAdmin::$err_act_ban);
        }
        if((Auth::user()->role == 'superadmin')) {
            $admin_area= new \stdClass();
            $admin_area->id=NULL;
            $admin_area->name=NULL;
            $admin_area->office_id=NULL;
            $admin_area_zones = DB::table('admin_area_zones')->pluck("area_id")->toArray();
            $areas = DB::table('area')->whereNotIn('id',$admin_area_zones)->get();
            return view('settings.admin_areas_form',['admin_area'=>$admin_area,'admin_area_zones'=>$admin_area_zones,'areas'=>$areas,'title'=>'Settings Admin Zones Add','br1'=>'Settings','br2'=>'Admin Zones Add','alert'=>'']);
        } else {
            return abort(403, LexaAdmin::$err_perm);
        }
    }

    public function settingsAdminAreasAddHandler(Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, LexaAdmin::$err_act_ban);
        }
        if((Auth::user()->role == 'superadmin')) {
            $row = DB::table('admin_areas')->where('name',$request->input('name'))->first();
            if(empty($row)){
                $admin_area_id = DB::table('admin_areas')->insertGetId(['name'=>$request->input('name')]);
                DB::table('admin_area_zones')->where('admin_area_id',$admin_area_id)->delete();
                $tariff_areas = $request->input('tariff_areas');
                $data=[];
                if(!empty($tariff_areas)) {
                    foreach($tariff_areas as $value){
                        $data[]=["admin_area_id"=>$admin_area_id,"area_id"=>$value];
                    }
                }
                DB::table('admin_area_zones')->insert($data);
                return redirect('/settings/admin_areas');
            } else {
                $admin_area= new \stdClass();
                $admin_area->id=NULL;
                $admin_area->name=$request->input('name');
                $admin_area->office_id=$request->input('office_id');
                $admin_area_zones = DB::table('admin_area_zones')->pluck("area_id")->toArray();
                $areas = DB::table('area')->whereNotIn('id',$admin_area_zones)->get();
                $alert = 'Admin Zone with this name already exist!';
                return view('settings.admin_areas_form',['admin_area'=>$admin_area,'admin_area_zones'=>$admin_area_zones,'areas'=>$areas,'title'=>'Settings Admin Zones Add','br1'=>'Settings','br2'=>'Admin Zones Add','alert'=>$alert]);
            }
        } else {
            return abort(403, LexaAdmin::$err_perm);
        }
    }

    /**
     * @param int|string $admin_area_id
     */
    public function settingsAdminAreasEdit($admin_area_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, LexaAdmin::$err_act_ban);
        }
        if((Auth::user()->role == 'superadmin')) {
            $admin_area= DB::table('admin_areas')->where('id',$admin_area_id)->first();
            $admin_area_zones = DB::table('admin_area_zones')->where('admin_area_id',$admin_area_id)->pluck("area_id")->toArray();
            $admin_area_zones2 = DB::table('admin_area_zones')->where('admin_area_id','!=',$admin_area_id)->pluck("area_id")->toArray();
            $areas = DB::table('area')->whereNotIn('id',$admin_area_zones2)->get();
            return view('settings.admin_areas_form',['admin_area'=>$admin_area,'admin_area_zones'=>$admin_area_zones,'areas'=>$areas,'title'=>'Settings Admin Zones Add','br1'=>'Settings','br2'=>'Admin Zones Add','alert'=>'']);
        } else {
            return abort(403, LexaAdmin::$err_perm);
        }
    }

    /**
     * @param int|string $admin_area_id
     */
    public function settingsAdminAreasEditHandler(Request $request,$admin_area_id) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, LexaAdmin::$err_act_ban);
        }
        if((Auth::user()->role == 'superadmin')) {
            $row = DB::table('admin_areas')->where('name',$request->input('name'))->where('id','!=',$admin_area_id)->first();
            if(empty($row)){
                DB::table('admin_areas')->where('id',$admin_area_id)->update(['name'=>$request->input('name')]);
                DB::table('admin_area_zones')->where('admin_area_id',$admin_area_id)->delete();
                $tariff_areas = $request->input('tariff_areas');
                $data=[];
                if(!empty($tariff_areas)) {
                    foreach($tariff_areas as $value){
                        $data[]=["admin_area_id"=>$admin_area_id,"area_id"=>$value];
                    }
                }
                DB::table('admin_area_zones')->insert($data);
                return redirect('/settings/admin_areas');
            } else {
                $admin_area= DB::table('admin_areas')->where('id',$admin_area_id)->first();
                $admin_area_zones = DB::table('admin_area_zones')->where('admin_area_id',$admin_area_id)->pluck("area_id")->toArray();
                $admin_area_zones2 = DB::table('admin_area_zones')->where('admin_area_id','!=',$admin_area_id)->pluck("area_id")->toArray();
                $areas = DB::table('area')->whereNotIn('id',$admin_area_zones2)->get();
                $alert = 'Admin Zone with this name already exist!';
                return view('settings.admin_areas_form',['admin_area'=>$admin_area,'admin_area_zones'=>$admin_area_zones,'areas'=>$areas,'title'=>'Settings Admin Zones Add','br1'=>'Settings','br2'=>'Admin Zones Add','alert'=>$alert]);
            }
        } else {
            return abort(403, LexaAdmin::$err_perm);
        }
    }
}
