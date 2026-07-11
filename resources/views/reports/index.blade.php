@extends('layouts.master')

@section('title') Reports @endsection

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
                                    <a href="/reports" style="background: #7a6fbe;color: #ffffff !important;"> <i class="ti-stats-up"></i> Overview</a>
                                    <a href="/reports/billing"> <i class="ti-credit-card"></i> Billing</a>
                                    
                                    <a href="/reports/apps"> <i class="ti-mobile"></i> Apps analytics </a>
                                    <a href="/reports/drivers"> <i class="ti-truck"></i> Drivers </a>
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
                                <h4 class="card-title">Overview</h4>
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="card">
                                                    <div class="card-header">
                                                    <i class="ti-location-pin"></i> Pharmacies
                                                    </div>
                                                    <div class="card-body">
                                                        <blockquote class="card-blockquote mb-0">
                                                            <h4>Total: <mark>XXX</mark></h4>
                                                            <h4>Active: <mark>XXX</mark></h4>
                                                            <footer class="mt-4 mb-4">
                                                                <button type="button" class="btn btn-primary btn-sm waves-effect waves-light my-2">View</button> <button type="button" class="btn btn-primary btn-sm waves-effect waves-light my-2">Notification</button> <button type="button" class="btn btn-primary btn-sm waves-effect waves-light my-2">View on the map</button>
                                                            </footer>
                                                        </blockquote>
                                                    </div>
                                                </div>
                                            </div><!-- end col --> 
                                            <div class="col-lg-12">
                                                <div class="card">
                                                    <div class="card-header">
                                                    <i class="ti-location-pin"></i> Customers
                                                    </div>
                                                    <div class="card-body">
                                                        <blockquote class="card-blockquote mb-0">
                                                            <h4>Total: <mark>XXX</mark></h4>
                                                            <h4>With application: <mark>XXX</mark></h4>
                                                            <footer class="mt-4 mb-4">
                                                            <button type="button" class="btn btn-primary btn-sm waves-effect waves-light my-2">View</button> <button type="button" class="btn btn-primary btn-sm waves-effect waves-light my-2">Send sms/push</button> <button type="button" class="btn btn-primary btn-sm waves-effect waves-light my-2">View on the map</button>
                                                            </footer>
                                                        </blockquote>
                                                    </div>
                                                </div>
                                            </div><!-- end col -->  
                                            <div class="col-lg-12">
                                                <div class="card">
                                                    <div class="card-header">
                                                    <i class="ti-location-pin"></i> Drivers
                                                    </div>
                                                    <div class="card-body">
                                                        <blockquote class="card-blockquote mb-0">
                                                            <h4>Total: <mark>XXX</mark></h4>
                                                            <h4>With application: <mark>XXX</mark></h4>
                                                            <footer class="mt-4 mb-4">
                                                            <button type="button" class="btn btn-primary btn-sm waves-effect waves-light my-2">View</button> <button type="button" class="btn btn-primary btn-sm waves-effect waves-light my-2">Send sms/push</button> <button type="button" class="btn btn-primary btn-sm waves-effect waves-light my-2">View on the map</button>
                                                            </footer>
                                                        </blockquote>
                                                    </div>
                                                </div>
                                            </div><!-- end col -->     
                                            <div class="col-lg-12">
                                                <div class="card">
                                                    <div class="card-header">
                                                    <i class="ti-location-pin"></i> Billing
                                                    </div>
                                                    <div class="card-body">
                                                        <blockquote class="card-blockquote mb-0">
                                                            <h4>Total: <mark>XXX</mark></h4>
                                                            <h4>With application: <mark>XXX</mark></h4>
                                                            <footer class="mt-4 mb-4">
                                                            <button type="button" class="btn btn-primary btn-sm waves-effect waves-light my-2">View</button> <button type="button" class="btn btn-primary btn-sm waves-effect waves-light my-2">Send sms/push</button> <button type="button" class="btn btn-primary btn-sm waves-effect waves-light my-2">View on the map</button>
                                                            </footer>
                                                        </blockquote>
                                                    </div>
                                                </div>
                                            </div><!-- end col -->      
                                            <div class="col-lg-12">
                                                <div class="card">
                                                    <div class="card-header">
                                                    <i class="ti-location-pin"></i> Apps analytics
                                                    </div>
                                                    <div class="card-body">
                                                        <blockquote class="card-blockquote mb-0">
                                                            <h4>Total: <mark>XXX</mark></h4>
                                                            <h4>With application: <mark>XXX</mark></h4>
                                                            <footer class="mt-4 mb-4">
                                                            <button type="button" class="btn btn-primary btn-sm waves-effect waves-light my-2">View</button> <button type="button" class="btn btn-primary btn-sm waves-effect waves-light my-2">Send sms/push</button> <button type="button" class="btn btn-primary btn-sm waves-effect waves-light my-2">View on the map</button>
                                                            </footer>
                                                        </blockquote>
                                                    </div>
                                                </div>
                                            </div><!-- end col -->     
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
