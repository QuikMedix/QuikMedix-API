@extends('layouts.master')

@section('title') Payouts @endsection

@section('headerCss')
    <!-- Responsive Table css -->
    <style>
        .createds {
            position: absolute;
            background-color: #fff;
            padding: 5px 15px;
            border-radius: 5px;
            margin-left: -35px;
            margin-top: 15px;
            border: 2px solid #e9ecef;
            z-index:1;
            text-align: center;
        }
    </style>
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
                    <div class="card-body pb-0">
                        <div class="table-rep-plugin">
                            <table id="mytable" class="table  table-striped">
                                <thead>
                                    <tr>
                                        <th>Order</th>
                                        <th class="created_filter" style="cursor:pointer;" data-priority="1" id="mytable-col-1">Date <i class="fa fa-filter" aria-hidden="true"></i>
                                            <div class="createds" style="display:none;">
                                            <form>
                                                <input type="date" name="created" class="form-control created" placeholder="Select Date...">
                                                <button style="margin-top:10px;" class="btn btn-primary submit">Submit</button>
                                            </form>
                                            </div>
                                        </th>
                                        <th data-priority="1">Driver Name</th>
                                        <th data-priority="3">Co-pay</th>                                                        
                                        <th data-priority="3">Paid (Total Debt {{$duty}} $)</th>
                                        <th data-priority="4">Collected By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach ($orders as $key=>$order)
                                    <tr>
                                        <td><a href="/orders/0/show/{{$order->order_id}}">{{$order->order_id}}</a></td>
                                        <td>{{date('m/d/Y g:i A', strtotime($order->created))}}</td>
                                        <td>{{ $user->name }} {{ $user->last_name }}</td>
                                        <td>{{$order->copay}} $</td>
                                        <td>@if($order->return){{$order->copay}}@else{{'0'}}@endif $ ({{date('m/d/Y g:i A', strtotime($order->return_at))}})</td>
                                        <td>Admin #{{$order->admin_id}}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div style="text-align: center;width: 100%;margin-bottom: 10px;">Pages: 
                        @foreach ($pages as $page)
                            <form style="display: inline-block;">
                                <input type="hidden" name="page" value="{{ $page['id'] }}">
                                <button type="submit" class="btn {{$page['class']}} page-btn">{{ $page['id'] }}</button>
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