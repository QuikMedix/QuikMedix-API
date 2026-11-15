<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersLegacyPages;
use App\Support\PageLinks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Support\AccessMessages;

/**
 * News for pharmacies and for patients: the lists and adding an item. Formerly actions of
 * LexaAdmin, moved as they were.
 */
class NewsController extends Controller
{
    use RendersLegacyPages;

    public function __construct()
    {
        // Kept from LexaAdmin, whose pages load whole result sets at once.
        ini_set('memory_limit', '-1');
    }

    public function news() {
        $countOnPage=20;
        $max_pages=ceil(DB::table('news')->where('user_id', Auth::user()->id)->count()/$countOnPage);
        $page = $this->currentPage(request());
        $pages = PageLinks::around($page, $max_pages);
        $news = DB::table('news')->orderBy('id','desc')->leftJoin('news_read',function ($join) {
            $join->on("news_read.news_id","=","news.id") ;
            $join->where("news_read.user_id",Auth::user()->id);
        })->select("news.*","news_read.id as viewed")->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
        foreach($news as $new){
            if(empty($new->viewed)) {
                DB::table('news_read')->insert(["news_id"=>$new->id,"user_id"=>Auth::user()->id]);
                Redis::del(request()->getHttpHost().':get_unread_news:'.Auth::user()->id);
            }
        }
        $res_view = view('news.list',['news'=>$news,'pages'=>$pages,'title'=>'News','br1'=>'News','br2'=>'List']);
        return $this->page(request(), $res_view);
    }

    public function newsAdd() {
        $input['link']='';
        $input['title']='';
        $input['text']='';
        $res_view = view('news.add',['title'=>'News Add','br1'=>'News list','br2'=>'News','br3'=>'News Add','alert'=>'','input'=>$input]);
        return $this->page(request(), $res_view);
    }

    public function newsAddHandler(Request $request) {
        if($request->input('save')>0 && !empty($request->input('title')) && !empty($request->input('text'))) {
            DB::table('news')->insert(["user_id"=>Auth::user()->id,"type"=>$request->input('type'),"link"=>$request->input('link'),"title"=>$request->input('title'),"text"=>$request->input('text')]);
        }
        return redirect("news");
    }

    public function news_patient() {
        $countOnPage=20;
        $max_pages=ceil(DB::table('news')->where('user_id', Auth::user()->id)->count()/$countOnPage);
        $page = $this->currentPage(request());
        $pages = PageLinks::around($page, $max_pages);
        $news = DB::table('news_patient')->where("pharmacy_id",Auth::user()->pharmacy_id)->orderBy('id','desc')->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
        $res_view = view('news.list_patient',['news'=>$news,'pages'=>$pages,'title'=>'Patients','br1'=>'News','br2'=>'List']);
        return $this->page(request(), $res_view);
    }

    public function news_patientAdd() {
        abort_unless(Auth::user()->role == 'medic' && !empty(Auth::user()->pharmacy_id), 403, AccessMessages::NO_PERMISSION);
        $input['link']='';
        $input['title']='';
        $input['text']='';
        $res_view = view('news.add',['title'=>'News Add','br1'=>'News list','br2'=>'News','br3'=>'News Add','alert'=>'','input'=>$input]);
        return $this->page(request(), $res_view);
    }

    public function news_patientAddHandler(Request $request) {
        abort_unless(Auth::user()->role == 'medic' && !empty(Auth::user()->pharmacy_id), 403, AccessMessages::NO_PERMISSION);
        if($request->input('save')>0 && !empty($request->input('title')) && !empty($request->input('text'))) {
            DB::table('news_patient')->insert(["user_id"=>Auth::user()->id,"pharmacy_id"=>Auth::user()->pharmacy_id,"type"=>$request->input('type'),"link"=>$request->input('link'),"title"=>$request->input('title'),"text"=>$request->input('text')]);
        }
        return redirect("news_patient");
    }
}
