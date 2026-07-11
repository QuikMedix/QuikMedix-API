@extends('layouts.master')

@section('title') Payroll @endsection

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
                        <div class="col-xl-6 col-sm-6">
                                <div class="card mini-stat bg-primary">
                                    <div class="card-body mini-stat-img">
                                        <div class="mini-stat-icon">
                                            <i class="mdi mdi-car-multiple float-right"></i>
                                        </div>
                                        <div class="text-light">
                                            <h6 class="text-uppercase mb-3 font-size-16">Working drivers</h6>
                                            <h4 class="mb-2">17</h4>
                                            <span>This week</span>
                                        </div>
                                    </div>
                                </div>
                        </div>
                        <div class="col-xl-6 col-sm-6">
                                <div class="card mini-stat bg-primary" style="background-color: #2b3a4a !important;">
                                    <div class="card-body mini-stat-img">
                                        <div class="mini-stat-icon">
                                            <i class="mdi mdi-map-marker-multiple float-right"></i>
                                        </div>
                                        <div class="text-light">
                                            <h6 class="text-uppercase mb-3 font-size-16">Total points in routes</h6>
                                            <h4 class="mb-2">186</h4>
                                            <span>This week</span>
                                        </div>
                                    </div>
                                </div>
                        </div>
                    </div>
                    <!-- end page title -->                    
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">10.02.2023 - 10.08.2023</h4>
                                        <div class="row">
                                            <div class="table-responsive">
                                                <table class="table table-striped mb-0">
            
                                                    <thead>
                                                        <tr>
                                                            <th class="text-center">#</th>
                                                            <th>Driver</th>
                                                            <th>Points</th>
                                                            <th>Total</th>
                                                            <th>Correction</th>
                                                            <th>Status</th>
                                                            <th class="text-right">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <th scope="row text-center">1</th>
                                                            <td>Rustem Mambetov</td>
                                                            <td>395</td>
                                                            <td>$1311.00</td>
                                                            <td>$0.00 </td>
                                                            <td><i class="mdi mdi-checkbox-blank-circle text-success"></i> Paid</td>
                                                            <td class="text-right"><a href="#" target="_blank" rel="noopener noreferrer"><button class="btn btn-primary btn-sm waves-effect">Report <i class="mdi mdi-file-pdf"></i></button></a> </td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row text-center">2</th>
                                                            <td>Rakhim Makhazhiev</td>
                                                            <td>233</td>
                                                            <td>$1311.00</td>
                                                            <td>$0.00 <i style="color:#000;" class="mdi mdi-circle-edit-outline"></i></td>
                                                            <td><i class="mdi mdi-checkbox-blank-circle text-warning"></i> Not paid</td>
                                                            <td class="text-right">
                                                                <a href="#"><button class="btn btn-warning btn-sm waves-effect">Change tariff <i class="mdi mdi-briefcase-edit"></i></button></a>
                                                                <a href="#"><button class="btn btn-warning btn-sm waves-effect">Paid <i class="mdi mdi-briefcase-edit"></i></button></a>
                                                                <a href="#"><button class="btn btn-success btn-sm waves-effect">Pay Driver <i class="mdi mdi-credit-card-plus-outline"></i></button></a>
                                                                <a href="#" target="_blank" rel="noopener noreferrer"><button class="btn btn-primary btn-sm waves-effect">Report  <i class="mdi mdi-file-pdf"></i></button></a>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row text-center">3</th>
                                                            <td>Jamshid Egamberdiyev</td>
                                                            <td>176</td>
                                                            <td>$1311.00</td>
                                                            <td>$0.00 </td>
                                                            <td><i class="mdi mdi-checkbox-blank-circle text-success"></i> Paid</td>
                                                            <td class="text-right"><a href="#" target="_blank" rel="noopener noreferrer"><button class="btn btn-primary btn-sm waves-effect">Report <i class="mdi mdi-file-pdf"></i></button></a></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
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
