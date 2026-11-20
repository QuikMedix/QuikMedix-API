@extends('layouts.master')

@section('title') Drivers  @endsection

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
    background: #c90016;
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
                                    <a href="/reports/drivers" style="background: #c90016;color: #ffffff !important;"> <i class="ti-truck"></i> Drivers </a>
                                    <a href="/reports/pharmacies"> <i class="ti-support"></i> Pharmacies </a>
                                    <a href="/reports/customers"> <i class="ti-face-smile"></i> Customers </a>
                                    <a href="/reports/invoices"> <i class="ti-receipt"></i> Invoices </a>
                                    <a href="/reports/map"> <i class="ti-map-alt"></i> Delivery Map </a>
                                </div>                                 
                            </div>
                         </div>                                                             
                        </div>
                        <div class="col-xl-9">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Drivers</h4>
                                    <h4 class="mt-4 mb-4">Total: <mark>{{ $counts['drivers'] }}</mark>  Active: <mark>{{ $counts['drivers_active'] }}</mark></h4>
                                    <h4 class="mb-4">On shift now: <mark>{{ $counts['drivers_on_shift'] }}</mark>  With application: <mark>{{ $counts['drivers_with_app'] }}</mark></h4>
                                    <a href="/payroll" class="btn btn-primary btn-sm waves-effect waves-light my-2">Payroll</a>
                                </div>
                            </div>
                        </div>
                        <!-- end col -->
                    </div>
                    <!-- end row -->

@endsection

@section('footerScript')

@endsection
