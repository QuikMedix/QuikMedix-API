<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersPages;

/**
 * Pages that only show a view: feedback, holiday greetings, ads and the support chat.
 * Formerly actions of LexaAdmin, moved as they were.
 */
class PageController extends Controller
{
    use RendersPages;


    public function feedback() {
        $res_view = view('feedback.index',['alert'=>'','title'=>'Feedback','br1'=>'Feedback','br2'=>'Index']);
        return $this->page(request(), $res_view);
    }

    public function happyHolidays() {
        $res_view = view('happyHolidays.index',['alert'=>'','title'=>'happyHolidays','br1'=>'happyHolidays','br2'=>'Index']);
        return $this->page(request(), $res_view);
    }

    public function ads() {
        $res_view = view('ads.index',['alert'=>'','title'=>'ads','br1'=>'ads','br2'=>'Index']);
        return $this->page(request(), $res_view);
    }

    public function supportChat() {
        return redirect('/chat');
    }
}
