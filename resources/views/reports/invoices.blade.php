@extends('layouts.master')

@section('title') Invoices analytics  @endsection

@section('headerCss')
<style>
.rep-menu a {
    font-size: 14px;
    line-height: 30px;
    color: black;
    display: block;
    margin: 10px 0;
    padding: 5px 0 5px 20px;

}
.rep-menu a:hover {
    background: #7a6fbe;
    color: #ffffff !important;
}
.rep-menu i {
    font-size: 16px;
    margin: 0px 7px;
}

</style>
        

@endsection

@section('content')
 <!-- start page title -->
                    <div class="row">

                   
                    </div>
                    <!-- end page title -->                    
                    <div class="row">
                        <div class="col-xl-3"> 
                        <div class="card">
                            <div class="card-body">                                
                                <div class="rep-menu">  
                                    <a href="/reports"> <i class="ti-stats-up"></i> Overview</a>
                                    <a href="/reports/billing"> <i class="ti-credit-card"></i> Billing</a>
                                    <a href="/reports/apps"> <i class="ti-mobile"></i> Apps analytics </a>
                                    <a href="/reports/drivers"> <i class="ti-truck"></i> Drivers </a>
                                    <a href="/reports/pharmacies"> <i class="ti-support"></i> Pharmacies </a>
                                    <a href="/reports/customers"> <i class="ti-face-smile"></i> Customers </a>
                                    <a href="/reports/invoices" style="background: #7a6fbe;color: #ffffff !important;"> <i class="ti-receipt"></i> Invoices </a>
                                    <a href="/reports/map"> <i class="ti-map-alt"></i> Delivery Map </a>
                                </div>                                 
                            </div>
                         </div>                                                             
                        </div>
                        <div class="col-xl-9">
                            <div class="card">
                                <div class="card-body">
                                    <div id="morris-bar-stacked" class="morris-charts morris-charts-height" dir="ltr"></div>
                                </div>
                            </div>
                        </div>
                        <!-- end col -->
                    </div>
                    <!-- end row -->

@endsection

@section('footerScript')
<script src="{{ URL::asset('/libs/morris.js/morris.js.min.js')}}"></script>
<script src="{{ URL::asset('/libs/raphael/raphael.min.js')}}"></script>
<script>
    var chartData = JSON.parse('{!!json_encode($chartData)!!}');
    !function(e) {
    "use strict";
    function a() {}
    a.prototype.createStackedChart = function(e, a, r, t, i, o) {
        Morris.Bar({
            element: e,
            data: a,
            xkey: r,
            ykeys: t,
            stacked: 0,
            labels: i,
            hideHover: "auto",
            barSizeRatio: .4,
            resize: !0,
            gridLineColor: "rgba(108, 120, 151, 0.1)",
            barColors: o,
        })
    },
    a.prototype.init = function() {
        this.createStackedChart("morris-bar-stacked", chartData.reverse(), "y", ["a", "b", "c"], ["Total sum invoice", "Total count invoice", "Total count orders"], ["#7a6fbe", "#ffef8c", "#8cb3ff"])
    }
    ,
    e.MorrisCharts = new a,
    e.MorrisCharts.Constructor = a
}(window.jQuery),
function() {
    "use strict";
    window.jQuery.MorrisCharts.init()
}();
</script>
@endsection
