<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersLegacyPages;
use App\Support\PageLinks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Greeting wishes in settings: categories and the wishes of a category. Formerly actions of
 * LexaAdmin, moved as they were.
 */
class WishController extends Controller
{
    use RendersLegacyPages;

    public function __construct()
    {
        // Kept from LexaAdmin, whose pages load whole result sets at once.
        ini_set('memory_limit', '-1');
    }

    public function settingsWishesCategory() {
        $countOnPage=20;
        $max_pages=ceil(DB::table('wishes_category')->count()/$countOnPage);
        $page = $this->currentPage(request());
        $pages = PageLinks::around($page, $max_pages);
        $wishes_categorys = DB::table('wishes_category')->orderBy('id','desc')->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
        $res_view = view('wishes.category',['wishes_categorys'=>$wishes_categorys,'pages'=>$pages,'title'=>'Print Text Category','br1'=>'Print Text','br2'=>'Category']);
        return $this->page(request(), $res_view);
    }

    public function settingsWishesCategoryHandler(Request $request) {
        if($request->input('remove')>0) {
            DB::table('wishes')->where('category_id', $request->input('wishes_category_id'))->delete();
            DB::table('wishes_category')->where('id', $request->input('wishes_category_id'))->delete();
        }
        if($request->input('activate')>0) {
            DB::table('wishes_category')->where('id', $request->input('wishes_category_id'))->update(["status"=>1]);
        }
        if($request->input('noactivate')>0) {
            DB::table('wishes_category')->where('id', $request->input('wishes_category_id'))->update(["status"=>0]);
        }
        return redirect("settings/wishes");
    }

    public function settingsWishesCategoryAdd() {
        $input['name']='';
        $res_view = view('wishes.category_add',['input'=>$input,'alert'=>'','title'=>'Print Text Category Add','br1'=>'Print Text','br2'=>'Category Add']);
        return $this->page(request(), $res_view);
    }

    public function settingsWishesCategoryAddHandler(Request $request) {
        if($request->input('save')>0) {
            if(!empty(DB::table('wishes_category')->where('name', $request->input('name'))->first())) {
                $input['name']=$request->input('name');
                return view('wishes.category_add',['input'=>$input,'alert'=>'Print Text Category with this name is alredy exist.','title'=>'Print Text Category Add','br1'=>'Print Text','br2'=>'Category Add']);
            } else {
                DB::table('wishes_category')->insert(['name' => $request->input('name')]);
            }
        }
        return redirect("settings/wishes");
    }

    /**
     * @param int|string $wish_id
     */
    public function settingsWishes($wish_id) {
        $countOnPage=20;
        $max_pages=ceil(DB::table('wishes')->where("category_id",$wish_id)->count()/$countOnPage);
        $page = $this->currentPage(request());
        $pages = PageLinks::around($page, $max_pages);
        $wishes = DB::table('wishes')->where("category_id",$wish_id)->orderBy('id','desc')->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
        $res_view = view('wishes.wishes',['wishes'=>$wishes,'wish_id'=>$wish_id,'pages'=>$pages,'title'=>'Print Text','br1'=>'Print Text','br2'=>'List']);
        return $this->page(request(), $res_view);
    }

    /**
     * @param int|string $wish_id
     */
    public function settingsWishesHandler($wish_id,Request $request) {
        if($request->input('remove')>0) {
            DB::table('wishes')->where('id', $request->input('wishes_id'))->delete();
        }
        return redirect("settings/wishes/$wish_id/list");
    }

    public function settingsWishesAdd() {
        $input['text']='';
        $res_view = view('wishes.add',['input'=>$input,'alert'=>'','title'=>'Print Text Add','br1'=>'Print Text','br2'=>'Add']);
        return $this->page(request(), $res_view);
    }

    /**
     * @param int|string $wish_id
     */
    public function settingsWishesAddHandler($wish_id,Request $request) {
        if($request->input('save')>0) {
            DB::table('wishes')->insert(['category_id'=>$wish_id,'text' => $request->input('text')]);
        }
        return redirect("settings/wishes/$wish_id/list");
    }
}
