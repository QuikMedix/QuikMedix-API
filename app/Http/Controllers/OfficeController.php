<?php

namespace App\Http\Controllers;

use App\Models\AdminArea;
use App\Models\Office;
use App\Http\Controllers\Concerns\RendersPages;
use App\Support\PageLinks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Offices: list, add and edit. Formerly actions of LexaAdmin, moved as they were.
 */
class OfficeController extends Controller
{
    use RendersPages;


    public function officesList() {
        $offices = DB::table('offices');
        if(!empty(request()->query('search'))) {
            $search = request()->query('search');
            $offices = $offices->where(function($query) use ($search) {
                $query->where('name','LIKE','%'.$search.'%')
                      ->orWhere('email','LIKE','%'.$search.'%')
                      ->orWhere('phone','LIKE','%'.$search.'%')
                      ->orWhere('address','LIKE','%'.$search.'%');
                });
        } else {
            $search='';
        }
        $countOnPage=30;
        $max_pages=ceil($offices->count()/$countOnPage);
        $page = $this->currentPage(request());
        $pages = PageLinks::around($page, $max_pages);
        $offices = $offices->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
        $res_view = view('offices.list',['pages'=>$pages,'page0'=>$page,'search'=>$search,'offices'=>$offices,'title'=>'Offices List','br1'=>'Office','br2'=>'List']);
        return $this->page(request(), $res_view);
    }

    public function officesListHandler(Request $request) {
        if($request->input('remove')>0) {
            Office::query()->where('id', $request->input('user_id'))->delete();
        }
        $offices = DB::table('offices');
        if(!empty(request()->query('search'))) {
            $search = request()->query('search');
            $offices = $offices->where(function($query) use ($search) {
                $query->where('name','LIKE','%'.$search.'%')
                      ->orWhere('email','LIKE','%'.$search.'%')
                      ->orWhere('phone','LIKE','%'.$search.'%')
                      ->orWhere('address','LIKE','%'.$search.'%');
                });
        } else {
            $search='';
        }
        $countOnPage=30;
        $max_pages=ceil($offices->count()/$countOnPage);
        $page = $this->currentPage(request());
        $pages = PageLinks::around($page, $max_pages);
        $offices = $offices->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
        $res_view = view('offices.list',['pages'=>$pages,'page0'=>$page,'search'=>$search,'offices'=>$offices,'title'=>'Offices List','br1'=>'Office','br2'=>'List']);
        return $this->page(request(), $res_view);
    }

    /**
     * @param int|string $office_id
     */
    public function officesListEdit($office_id) {
        $office = DB::table('offices')->where('id', $office_id)->first();
        if(empty($office)) {
            return abort(404, 'Office not found');
        }
        $admin_areas = DB::table('admin_areas')->get();
        $res_view = view('offices.form',['office'=>$office,'admin_areas'=>$admin_areas, 'title'=>'Office Edit','br1'=>'Offices','br2'=>'Office','br3'=>'Office Edit','alert'=>'']);
        return $this->page(request(), $res_view);
    }

    /**
     * @param int|string $office_id
     */
    public function officesListEditHandler(Request $request,$office_id) {
        if($request->input('save')>0) {
            if(!empty(DB::table('offices')->where('id','!=',$office_id)->where(function($query) use ($request) {
                    $query->where('email', $request->input('email'))->orWhere('zone_id', $request->input('zone_id'));
                })->first())) {
                $office=new \StdClass();
                $office->name=$request->input('name');
                $office->email=$request->input('email');
                $office->phone=$request->input('phone');
                $office->logo='';
                $office->address=$request->input('address');
                $office->zone_id=$request->input('zone_id');
                $admin_areas = DB::table('admin_areas')->get();
                return view('offices.form',['office'=>$office,'admin_areas'=>$admin_areas,'title'=>'Office Add','br1'=>'Offices','br2'=>'Office','br3'=>'Office Add','alert'=>'Office with this email or zone already exists']);
            } else {
                if($request->hasFile('image')) {
                    $file = $request->file('image');
                    $file->move(public_path() . '/images/offices/',\App\Support\PublicUpload::name($request->file('image')));
                    $src = '/images/offices/'.\App\Support\PublicUpload::name($request->file('image'));
                } else {
                    $office = DB::table('offices')->where('id', $office_id)->first();
                    $src = $office->logo;
                }
                $data = app(\App\Actions\GeocodeAddress::class)->response($request->input('address'));
                $location = $data->results[0]->geometry->location->lat.','.$data->results[0]->geometry->location->lng;
                Office::query()->where('id', $office_id)->update(['name' => $request->input('name'),'email' => $request->input('email'),'phone' => $request->input('phone'),'logo' => $src,'address' => $request->input('address'),'location' => $location,'zone_id' => $request->input('zone_id')]);
            }
        }
        return redirect("offices");
    }

    public function officesListAdd() {
        $office=new \StdClass();
        $office->name='';
        $office->email='';
        $office->phone='';
        $office->logo='';
        $office->address='';
        $office->zone_id='';
        $admin_areas = AdminArea::query()->get();
        $res_view = view('offices.form',['office'=>$office,'admin_areas'=>$admin_areas,'title'=>'Office Add','br1'=>'Offices','br2'=>'Office','br3'=>'Office Add','alert'=>'']);
        return $this->page(request(), $res_view);
    }

    public function officesListAddHandler(Request $request) {
        if($request->input('save')>0) {
            if(!empty(DB::table('offices')->where('email', $request->input('email'))->orWhere('zone_id', $request->input('zone_id'))->first())) {
                $office=new \StdClass();
                $office->name=$request->input('name');
                $office->email=$request->input('email');
                $office->phone=$request->input('phone');
                $office->logo='';
                $office->address=$request->input('address');
                $office->zone_id=$request->input('zone_id');
                $admin_areas = DB::table('admin_areas')->get();
                return view('offices.form',['office'=>$office,'admin_areas'=>$admin_areas,'title'=>'Office Add','br1'=>'Offices','br2'=>'Office','br3'=>'Office Add','alert'=>'Office with this email or zone already exists']);
            } else {
                if($request->hasFile('image')) {
                    $file = $request->file('image');
                    $file->move(public_path() . '/images/offices/',\App\Support\PublicUpload::name($request->file('image')));
                    $src = '/images/offices/'.\App\Support\PublicUpload::name($request->file('image'));
                } else {
                    $src = '';
                }
                $data = app(\App\Actions\GeocodeAddress::class)->response($request->input('address'));
                $location = $data->results[0]->geometry->location->lat.','.$data->results[0]->geometry->location->lng;
                Office::query()->insert(['name' => $request->input('name'),'email' => $request->input('email'),'phone' => $request->input('phone'),'logo' => $src,'address' => $request->input('address'),'location' => $location,'zone_id' => $request->input('zone_id')]);
            }
        }
        return redirect("offices");
    }
}
