<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersLegacyPages;
use App\Support\PageLinks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Support\AccessMessages;

/**
 * Reports: billing, apps, invoices, drivers, pharmacies, customers and the delivery map. Formerly
 * actions of LexaAdmin, moved as they were.
 */
class ReportController extends Controller
{
    use RendersLegacyPages;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('2fa');
        // Kept from LexaAdmin, whose pages load whole result sets at once.
        ini_set('memory_limit', '-1');
    }

    public function reports() {
        $res_view = view('reports.index',['alert'=>'','title'=>'Reports','br1'=>'Reports','br2'=>'Index']);
        return $this->page(request(), $res_view);
    }

    public function reportsBilling() {
        $payments = DB::table('pharmacy_payments')->join("pharmacys","pharmacy_payments.pharmacy_id","=","pharmacys.id")->leftJoin("invoices","pharmacy_payments.invoice_id","=","invoices.id")->select("pharmacy_payments.id","invoices.id as invoice_id","pharmacy_payments.type","pharmacy_payments.created","pharmacys.name","pharmacy_payments.pharmacy_id","pharmacy_payments.transaction_id","invoices.date_from","invoices.date_to","pharmacy_payments.amount","invoices.count");
        $countOnPage=30;
        $max_pages=ceil($payments->count()/$countOnPage);
        $page = $this->currentPage(request());
        $pages = PageLinks::around($page, $max_pages);
        $payments = $payments->orderBy('pharmacy_payments.id','desc')->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
        $client = null;
        foreach($payments as $key=>$payment) {
            if(!empty($payment->transaction_id)){
                try {
                    if($client === null) {
                        $client = new \Square\SquareClient([
                            'accessToken' => config('app.SQUARE_ACCESS_TOKEN'),
                            'environment' => config('app.SQUARE_ENVIRONMENT'),
                        ]);
                    }
                    $api_response = $client->getPaymentsApi()->getPayment($payment->transaction_id);
                    if($api_response->isSuccess()) {
                        $result = $api_response->getResult();
                        if($result->getPayment() !== null) {
                            $payments[$key]->status = $result->getPayment()->getStatus();
                        }
                    }
                } catch (\Throwable $exception) {
                    Log::warning('Unable to retrieve Square payment status.', [
                        'payment_id' => $payment->id,
                        'exception' => $exception,
                    ]);
                }
            }
        }
        $res_view = view('reports.billing',['payments'=>$payments,'pages'=>$pages,'alert'=>'','title'=>'Reports Billing','br1'=>'Reports','br2'=>'Billing']);
        return $this->page(request(), $res_view);
    }

    public function reportsApps() {
        $res_view = view('reports.apps',['alert'=>'','title'=>'Reports Apps','br1'=>'Reports','br2'=>'Apps']);
        return $this->page(request(), $res_view);
    }

    public function reportsInvoices() {
        $chartData = DB::table('invoices')->select(DB::raw('CONCAT(date_from," ",date_to) as y'),DB::raw('ROUND(sum((amount+corrections)),2) as a'),DB::raw('count(id) as b'),DB::raw('ROUND(sum(count),2) as c'))->groupBy(DB::raw('CONCAT(date_from," ",date_to)'))->orderBy(DB::raw('CONCAT(date_from," ",date_to)'),'desc')->limit(46)->get();
        $res_view = view('reports.invoices',['chartData'=>$chartData,'alert'=>'','title'=>'Reports Invoices','br1'=>'Reports','br2'=>'Invoices']);
        return $this->page(request(), $res_view);
    }

    public function reportsDrivers() {
        $res_view = view('reports.drivers',['alert'=>'','title'=>'Reports Drivers','br1'=>'Reports','br2'=>'Drivers']);
        return $this->page(request(), $res_view);
    }

    public function reportsPharmacies() {
        $res_view = view('reports.pharmacies',['alert'=>'','title'=>'Reports Pharmacies','br1'=>'Reports','br2'=>'Pharmacies']);
        return $this->page(request(), $res_view);
    }

    public function reportsMap() {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, AccessMessages::ACCOUNT_INACTIVE);
        }
        if((Auth::user()->can('admin'))) {
            $date = date('Y-m-d');
            $polygons = DB::table('area')->select('id','name',DB::raw('ST_AsText(polygon) as polygon'))->get();
            foreach($polygons as $key=>$pol) {
                if(!empty($pol->polygon)) {
                    $polygons[$key]->polygon = \App\Support\WktPolygon::toJsonPoints($pol->polygon);
                    $polygons[$key]->count = DB::table('orders')->join('users', 'orders.user_id', '=', 'users.id')->join('area', function($join) use($pol) {
                        $join->on('area.polygon','!=','orders.id');
                        $join->where('area.id',$pol->id);
                    })->select('orders.id')->whereRaw("ST_CONTAINS(area.polygon, POINT(SUBSTRING_INDEX(users.location,',',1),SUBSTRING_INDEX(users.location,',',-1)))");
                    $polygons[$key]->count=$polygons[$key]->count->whereDate('created', $date);
                    $polygons[$key]->count=$polygons[$key]->count->get()->count();
                } else {
                    $polygons[$key]->polygon = "";
                    $polygons[$key]->count = "0";
                }
            }
            return view('reports.map',['date'=>$date,'polygons'=>$polygons,'alert'=>'','title'=>'Reports Map','br1'=>'Reports','br2'=>'Map']);
        } else {
            return abort(403, AccessMessages::NO_PERMISSION);
        }
    }

    public function reportsMapHandler(Request $request) {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, AccessMessages::ACCOUNT_INACTIVE);
        }
        if((Auth::user()->can('admin'))) {
            $date = date('Y-m-d',strtotime($request->input('date')));
            $polygons = DB::table('area')->select('id','name',DB::raw('ST_AsText(polygon) as polygon'))->get();
            foreach($polygons as $key=>$pol) {
                if(!empty($pol->polygon)) {
                    $polygons[$key]->polygon = \App\Support\WktPolygon::toJsonPoints($pol->polygon);
                    $polygons[$key]->count = DB::table('orders')->join('users', 'orders.user_id', '=', 'users.id')->join('area', function($join) use($pol) {
                        $join->on('area.polygon','!=','orders.id');
                        $join->where('area.id',$pol->id);
                    })->select('orders.id')->whereRaw("ST_CONTAINS(area.polygon, POINT(SUBSTRING_INDEX(users.location,',',1),SUBSTRING_INDEX(users.location,',',-1)))");
                    $polygons[$key]->count=$polygons[$key]->count->whereDate('created', $date);
                    $polygons[$key]->count=$polygons[$key]->count->get()->count();
                } else {
                    $polygons[$key]->polygon = "";
                    $polygons[$key]->count = "0";
                }
            }
            return view('reports.map',['date'=>$date,'polygons'=>$polygons,'alert'=>'','title'=>'Reports Map','br1'=>'Reports','br2'=>'Map']);
        } else {
            return abort(403, AccessMessages::NO_PERMISSION);
        }
    }

    public function reportsCustomers() {
        $areas = DB::table('area')->select('id','name',DB::raw('ST_AsText(polygon) as polygon'))->get();
        $count_all = 0;
        foreach($areas as $key=>$pol) {
            if(!empty($pol->polygon)) {
                $areas[$key]->count = DB::table('users')->leftJoin('orders', 'orders.user_id', '=', 'users.id')->join('area', function($join) use($pol) {
                    $join->on('area.polygon','!=','orders.id');
                    $join->where('area.id',$pol->id);
                })->where('users.role','user')->where('users.isactive','1')->where('isblocked','0')->select(DB::raw('count(distinct orders.id) as count_order'),DB::raw('count(distinct users.id) as count_all'),DB::raw('count(distinct case when users.os is not null then users.id end) as count_app'))->whereRaw("ST_CONTAINS(area.polygon, POINT(SUBSTRING_INDEX(users.location,',',1),SUBSTRING_INDEX(users.location,',',-1)))")->first();
                $count_all+=$areas[$key]->count->count_all;
            } else {
                $areas[$key]->count = new \StdClass;
                $areas[$key]->count->count_all=0;
                $areas[$key]->count->count_app=0;
                $areas[$key]->count->count_order=0;
            }
        }
        $res_view = view('reports.customers',['areas'=>$areas,'count_all'=>$count_all,'alert'=>'','title'=>'Reports Customers','br1'=>'Reports','br2'=>'Customers']);
        return $this->page(request(), $res_view);
    }
}
