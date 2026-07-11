@extends('layouts.master')

@section('title') Pharmacies  @endsection

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
                                    <a href="/reports/pharmacies" style="background: #7a6fbe;color: #ffffff !important;"> <i class="ti-support"></i> Pharmacies </a>
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
                                    <img src="{{ URL::asset('/images/c-soon.jpg')}}" alt="icon-d4" class="mr-0 pl-0 float-left" width="100%">
                                </div>
                            </div>
                        </div>
                        <!-- end col -->
                    </div>
                    <!-- end row -->

@endsection

@section('footerScript')

@endsection
