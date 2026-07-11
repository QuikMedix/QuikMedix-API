@extends('layouts.master')
@section('title') Billing @endsection
@section('headerCss')
    <!-- Responsive Table css -->
    <link href="{{ URL::asset('/libs/rwd-table/rwd-table.min.css')}}" rel="stylesheet" type="text/css" /> <!-- Bootstrap Css -->
@endsection
@section('content')
 <!-- start page title -->
                    <div class="row">
                    
                    
                    </div>
                    <!-- end page title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="table-rep-plugin">
                                        <div class="table-responsive mb-0" data-pattern="priority-columns">
                                            <table id="mytable" class="table  table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Order</th>
                                                        <th data-priority="1">Date</th>
                                                        <th data-priority="1">Status</th>
                                                        <th data-priority="1">Status</th>
                                                        <th data-priority="1">Patient Name</th>
                                                        <th data-priority="1">Co-pay</th>
                                                        <th data-priority="3">Cost</th>
                                                    </tr>
                                                </thead>
                                                <tbody>                                              
                                                @foreach ($orders as $key=>$order)
                                                    <tr>
                                                        <td>{{ $key + 1 }}</td>
                                                        <td><a href="/orders/{{ $order->pharmacy_id }}/show/{{ $order->id }}" target="_blank">{{$order->id}} <i class="mdi mdi-vector-link"></i></a></td>
                                                        <td>{{date('m.d.Y g:i A', strtotime($order->created))}}</td>
                                                        <td>@if(($order->delivery_time=='ASAP Delivery'))
                                                                <span style="font-size:10px;width:110px;white-space:normal;padding: 5px;color: white;" class="badge badge-pill badge-danger">{{$order->delivery_time}}</span>
                                                            @elseif($order->delivery_time=='Same day delivery')
                                                                <span style="font-size:10px;width:110px;white-space:normal;padding: 5px;color: white;" class="badge badge-pill badge-info">{{$order->delivery_time}}</span>
                                                            @elseif($order->delivery_time=='After Hours Delivery')
                                                                <span style="font-size:10px;width:110px;white-space:normal;padding: 5px;color: white;" class="badge badge-pill badge-danger">{{$order->delivery_time}}</span>
                                                            @else
                                                                <span style="font-size:10px;width:110px;white-space:normal;padding: 5px;color: white;" class="badge bg-primary badge-pill badge-info">{{$order->delivery_time}}</span>
                                                            @endif</td>
                                                        <td><span style="font-size: 13px;" class="badge badge-pill badge-{{$order->statusecolor}}">{{$order->statusename}}</span></td>
                                                        <td>{{ $order->username }} {{ $order->last_name }}</td>
                                                        <td>${{$order->copay}}</td>
                                                        <td>${{$order->tariff}}</td>
                                                    </tr>
                                                @endforeach                                                
                                                    <tr>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td>Total:</td>
                                                        <td>${{$sum_amount}}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            	</ol>
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
            <!-- Responsive Table js -->
            <script src="{{ URL::asset('/libs/rwd-table/rwd-table.min.js')}}"></script>
            <!-- Init js -->
            <script src="{{ URL::asset('/js/pages/table-responsive.init.js')}}"></script>
            <script>
                $(document).ready(function(){
                    $("#search").keyup(function(){
                    _this = this;
                        $.each($("#mytable tbody tr"), function() {
                            if($(this).find("td").filter(":not(.action)").text().toLowerCase().indexOf($(_this).val().toLowerCase()) === -1) {
                                $(this).hide();
                            } else {
                                $(this).show();                
                            };
                        });
                    });
                });
            </script>
@endsection