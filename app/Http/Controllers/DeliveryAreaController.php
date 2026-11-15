<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersLegacyPages;
use App\Support\PageLinks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Delivery areas in settings: the list, and drawing and editing an area with its tariff. Formerly
 * actions of LexaAdmin, moved as they were.
 */
class DeliveryAreaController extends Controller
{
    use RendersLegacyPages;

    public function __construct()
    {
        // Kept from LexaAdmin, whose pages load whole result sets at once.
        ini_set('memory_limit', '-1');
    }

    public function settingsStates() {
        $countOnPage=20;
        $max_pages=ceil(DB::table('area')->count()/$countOnPage);
        $page = $this->currentPage(request());
        $pages = PageLinks::around($page, $max_pages);
        $areas = DB::table('area')->orderBy('id','desc')->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
        $res_view = view('area.list',['areas'=>$areas,'pages'=>$pages,'title'=>'Area Tariff List','br1'=>'Area','br2'=>'Tariff List']);
        return $this->page(request(), $res_view);
    }

    public function settingsStatesHandler(Request $request) {
        if($request->input('remove')>0) {
            DB::table('area')->where('id', $request->input('area_id'))->delete();
            DB::table('area_zip')->where("area_id",$request->input('area_id'))->delete();
        }
        return redirect("settings/area");
    }

    public function settingsStatesAdd() {
        $area = new \stdClass();
        $area->name="";
        $area->state="";
        $polygon="";
        $states=[];
        $states_list = DB::table('states')->get();
        $polygons = DB::table('area')->select('name',DB::raw('ST_AsText(polygon) as polygon'))->get();
        foreach($polygons as $key=>$pol) {
            if(!empty($pol->polygon)) {
                $polygons[$key]->polygon = $this->encodePolygon($pol->polygon);
            } else {
                $polygons[$key]->polygon = "";
            }
        }
        $res_view = view('area.form',['area'=>$area,'polygon'=>$polygon,'polygons'=>$polygons,'states'=>$states,'states_list'=>$states_list,'alert'=>'','title'=>'Area Tariff Add','br1'=>'Area','br2'=>'Tariff Add']);
        return $this->page(request(), $res_view);
    }

    public function settingsStatesAddHandler(Request $request) {
        if($request->input('save')>0) {
            $area_id = DB::table('area')->insertGetId($this->tariffAreaData($request));
            if(!empty($request->input('zip'))){
                $zips = array_filter(explode("\n",$request->input('zip')));
                foreach($zips as $zip){
                    if(!empty($zip)){
                        DB::table('area_zip')->insert(['area_id' => $area_id,'zip' => preg_replace('/\s+/', '', $zip)]);
                    }
                }
            }
        }
        return redirect("settings/area");
    }

    /**
     * @param int|string $area_id
     */
    public function settingsStatesEdit($area_id) {
        $area = DB::table('area')->where('id', $area_id)->select('id','name','state','tariff',DB::raw('ST_AsText(polygon) as polygon'))->first();
        if(!empty($area->polygon)) {
            $polygon = $this->encodePolygon($area->polygon);
        } else {
            $polygon = "";
        }
        $polygons = DB::table('area')->where('id', '!=', $area_id)->where('state',$area->state)->select('name',DB::raw('ST_AsText(polygon) as polygon'))->get();
        foreach($polygons as $key=>$pol) {
            if(!empty($pol->polygon)) {
                $polygons[$key]->polygon = $this->encodePolygon($pol->polygon);
            } else {
                $polygons[$key]->polygon = "";
            }
        }
        $states = DB::table('area_zip')->where('area_id', $area_id)->get();
        $states_list = DB::table('states')->get();
        $res_view = view('area.form',['area'=>$area,'polygon'=>$polygon,'polygons'=>$polygons,'states'=>$states,'states_list'=>$states_list,'alert'=>'','title'=>'Area Tariff Edit','br1'=>'Area','br2'=>'Tariff Edit']);
        return $this->page(request(), $res_view);
    }

    /**
     * @param int|string $area_id
     */
    public function settingsStatesEditHandler($area_id, Request $request) {
        if($request->input('save')>0) {
            DB::table('area')->where("id",$area_id)->update($this->tariffAreaData($request));
            //DB::table('area_zip')->where("area_id",$area_id)->delete();
            if(!empty($request->input('zip'))){
                $zips = array_filter(explode("\r\n",$request->input('zip')));
                foreach($zips as $zip){
                    if(!empty($zip)){
                        DB::table('area_zip')->insert(['area_id' => $area_id,'zip' => preg_replace('/\s+/', '', $zip)]);
                    }
                }
            }
        }
        return redirect("settings/area");
    }

    private function tariffAreaData(Request $request) {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'polygon' => 'required|json',
        ], ['polygon.required' => 'Draw a tariff area on the map before saving.']);
        $points = json_decode($data['polygon'], true);
        $invalid = function () {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'polygon' => 'Draw an area with at least three distinct corners on the map.',
            ]);
        };
        if (!is_array($points) || !array_is_list($points) || count($points) < 3) {
            $invalid();
        }
        $coordinates = [];
        foreach ($points as $point) {
            if (!is_array($point) || !isset($point['lat'], $point['lng'])
                || !is_numeric($point['lat']) || !is_numeric($point['lng'])
                || !is_finite((float) $point['lat']) || !is_finite((float) $point['lng'])
                || abs((float) $point['lat']) > 90 || abs((float) $point['lng']) > 180) {
                $invalid();
            }
            // Keep the latitude/longitude order used by existing area queries.
            $coordinates[] = (float) $point['lat'].' '.(float) $point['lng'];
        }
        if (count(array_unique($coordinates)) < 3) {
            $invalid();
        }
        if (end($coordinates) !== $coordinates[0]) {
            $coordinates[] = $coordinates[0];
        }
        $data['polygon'] = DB::raw("ST_GeomFromText('POLYGON((".implode(',', $coordinates)."))')");
        return $data;
    }

    /**
     * @param string $polygon_str
     */
    public function encodePolygon($polygon_str) {
        $polygon_json = [];
        $data = explode(',',str_replace(['POLYGON((','))'],'',$polygon_str));
        foreach($data as $key=>$row) {
            if($key<count($data)-1) {
                $a = explode(' ',$row);
                if(count($a)>1) {
                    array_push($polygon_json,["lat"=>floatval($a[0]),"lng"=>floatval($a[1])]);
                }
            }
        }
        return json_encode($polygon_json);
    }
}
