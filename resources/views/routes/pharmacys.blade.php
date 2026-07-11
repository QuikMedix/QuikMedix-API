@extends('layouts.master')

@section('title') Ready Orders Pharmacys @endsection

@section('headerCss')

    <!-- Responsive Table css -->
    <link href="{{ URL::asset('/libs/rwd-table/rwd-table.min.css')}}" rel="stylesheet" type="text/css" /> <!-- Bootstrap Css -->
    <style>
        .statuses {
            position: absolute;
            background-color: #fff;
            padding: 5px 15px;
            border-radius: 5px;
            margin-left: -35px;
            margin-top: 15px;
            border: 2px solid #e9ecef;
            z-index:1;
        }
        .pharmacys {
            position: absolute;
            background-color: #fff;
            padding: 5px 15px;
            border-radius: 5px;
            margin-left: -55px;
            margin-top: 15px;
            width: 400px;
            border: 2px solid #e9ecef;
        }
        .statuse_filter {
            cursor:pointer;
        }
        .pharmacy_filter {
            cursor:pointer;
        }
    </style>
@endsection

@section('content')
 <!-- start page title -->
                    <div class="row">

                    
                    
                    </div>
                    <!-- end page title -->

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body" style="margin-bottom:30px;">
                                    <div class="table-rep-plugin">
                                        <div class="table-responsive mb-0" data-pattern="priority-columns">
                                            <table id="mytable" class="table  table-striped">
                                                <thead>
                                                    <tr>
                                                        <th data-priority="1">Date</th>
                                                        <th data-priority="1">Pharmacy</th>
                                                        <th data-priority="1">Orders Ready</th>
                                                        <th data-priority="3">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                @foreach ($orders as $order)
                                                    <tr>
                                                        <td>{{date('m/d/Y g:i A', strtotime($order->created))}}</td>
                                                        <td>{{$order->pharmacyname}}</td>
                                                        <td>{{$order->count}}</td>
                                                        <td class="action">
                                                            <a href="/orders/{{$order->pharmacy_id}}?statuse%5B%5D=1"><button class="btn btn-warning">Open</button></a>
                                                        </td>
                                                    </tr>
                                                @endforeach
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
            <!-- Responsive Table js -->
            <script src="{{ URL::asset('/libs/rwd-table/rwd-table.min.js')}}"></script>

            <!-- Init js -->
            <script src="{{ URL::asset('/js/pages/table-responsive.init.js')}}"></script>
            <script>

            </script>
@endsection