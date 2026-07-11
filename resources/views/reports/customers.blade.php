@extends('layouts.master')

@section('title') Customers  @endsection

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
                                    <a href="/reports/customers" style="background: #7a6fbe;color: #ffffff !important;"> <i class="ti-face-smile"></i> Customers </a>
                                    <a href="/reports/invoices"> <i class="ti-receipt"></i> Invoices </a>
                                    <a href="/reports/map"> <i class="ti-map-alt"></i> Delivery Map </a>
                                </div>                                 
                            </div>
                         </div>                                                             
                        </div>
                        <div class="col-xl-9">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Customers</h4> 
                                    <h4 class="mt-4 mb-4">Total: {{$count_all}} </h4> 
                                        <div class="row">
                                            @foreach($areas as $area)
                                            <div class="col-lg-4">
                                                <div class="card">
                                                    <div class="card-header text-center">
                                                        <i class="ti-location-pin"></i> {{$area->name}}
                                                    </div>
                                                    <div class="card-body">
                                                        <blockquote class="card-blockquote mb-0">
                                                            <h6 class="text-white float-left py-2 px-2">Customers: {{$area->count->count_all}}</h6>
                                                            <h6 class="text-white text-right bg-primary py-2 px-2">App: {{$area->count->count_app}}</h6>
                                                            <h4 class="text-black text-center mt-3">Orders: {{$area->count->count_order}}</h4>
                                                            <footer class="mt-3 mb-1 text-center">
                                                                <button type="button" class="btn btn-outline-dark btn-sm waves-effect waves-light my-2">View</button> <button type="button" class="btn btn-outline-dark btn-sm waves-effect waves-light my-2">Send sms/push</button> <button type="button" class="btn btn-outline-dark btn-sm waves-effect waves-light my-2">View on the map</button>
                                                            </footer>
                                                        </blockquote>
                                                    </div>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                </div>
                            </div>
                        </div>
                        <!-- end col -->
                    </div>
                    <!-- end row -->

@endsection

@section('footerScript')

@endsection
