@extends('layouts.master')

@section('title') Billing  @endsection

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
                                    <a href="/reports/billing" style="background: #7a6fbe;color: #ffffff !important;"> <i class="ti-credit-card"></i> Billing</a>
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
                                    <h4 class="card-title">Billing</h4>   
                                    <div class="table-rep-plugin">
                                        <div class="table-responsive mb-0" data-pattern="priority-columns">
                                            <table id="tech-companies-1" class="table  table-striped">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 130px;">ID</th>
                                                        <th style="width: 110px;">Date</th>
                                                        <th style="width: 200px;" data-priority="1">Pharmacy</th>
                                                        <th style="width: 110px;" data-priority="3">Time range</th>                                                      
                                                        <th data-priority="3">Orders</th>
                                                        <th data-priority="4">Status</th>
                                                        <th data-priority="5">Total</th>
                                                        <th data-priority="6">Action</th>                                                
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($payments as $payment)
                                                    <tr>
                                                        <td>Payment #{{$payment->id}}<br>
                                                        Invoice #{{$payment->invoice_id}}</th>
                                                        <td>{{date('m.d.Y g:i A', strtotime($payment->created))}}</th>
                                                        <td>{{$payment->name}}</td>
                                                        <td>{{date('m.d.Y', strtotime($payment->date_from))}} {{date('m.d.Y', strtotime($payment->date_to))}}</td>                                                       
                                                        <td>{{$payment->count}}</td>
                                                        <td>@if(!empty($payment->status)) @if($payment->status=="CANCELED" || $payment->status=="FAILED") <b style="color:red;">{{$payment->status}}</b> @elseif($payment->status=="PENDING") <b style="color:yellow;">{{$payment->status}}</b> @else <b style="color:green;">{{$payment->status}}</b> @endif @else <b style="color:green;">COMPLETED</b> @endif<br><small>({{$payment->type}})</small></td>
                                                        <td>${{number_format($payment->amount,2)}}</td>
                                                        <td>
                                                            <a href="/billing/{{$payment->pharmacy_id}}" target="_blank" rel="noopener noreferrer"><button class="btn btn-secondary btn-sm waves-effect">Pharmacy <i class="mdi mdi-history"></i></button></a>                                                            
                                                            <a href="/billing/{{$payment->pharmacy_id}}/print/{{$payment->invoice_id}}" target="_blank" rel="noopener noreferrer"><button class="btn btn-primary btn-sm waves-effect">Print <i class="mdi mdi-file-pdf"></i></button></a>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div style="display: inline-block;text-align: center;margin-top: 10px;margin-bottom: 15px;">Pages: 
                                    @foreach ($pages as $page)
                                        <form class="filter-form" style="display: inline-block;">
                                            <input type="hidden" name="page" value="{{ $page['id'] }}">
                                            <button class="btn {{$page['class']}}">{{ $page['id'] }}</button>
                                        </form> 
                                    @endforeach
                                    ...
                                </div>
                            </div>
                        </div>
                        <!-- end col -->
                    </div>
                    <!-- end row -->

@endsection

@section('footerScript')

@endsection
