@extends('layouts.master')

@section('title') Routes @endsection

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
                                <div style="margin-top: 1.25rem;position: absolute;text-align: center;width: 100%;">Pages: 
                                    @foreach ($pages as $page)
                                        <form style="display: inline-block;">
                                            <input type="hidden" name="page" value="{{ $page['id'] }}">
                                            <input type="hidden" name="search" value="{{ $search }}">
                                            <button class="btn {{$page['class']}}">{{ $page['id'] }}</button>
                                        </form> 
                                    @endforeach
                                    ...
                                </div>
                                <div class="card-body" style="margin-bottom:30px;">
                                    <div class="table-rep-plugin">
                                        <div class="table-responsive mb-0" data-pattern="priority-columns">
                                            <table id="mytable" class="table  table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Order</th>
                                                        <th data-priority="1">Date</th>
                                                        <th data-priority="1" class="statuse_filter">Status <i class="fa fa-filter" aria-hidden="true"></i>
                                                            <div class="statuses" style="display:none;">
                                                            @foreach($statuses as $n=>$statuse)
                                                                @if(!empty($_GET['statuse']))
                                                                    @if(in_array($statuse->id,$_GET['statuse']))
                                                                        <div style="margin-bottom:5px;">
                                                                            <input type="checkbox" checked class="col-form-label status" id="exampleinput{{$n}}">
                                                                            <label for="exampleinput{{$n}}" class="col-form-label">{{ $statuse->name }}</label>
                                                                        </div>
                                                                    @else
                                                                        <div style="margin-bottom:5px;">
                                                                            <input type="checkbox" class="col-form-label status" id="exampleinput{{$n}}">
                                                                            <label for="exampleinput{{$n}}" class="col-form-label">{{ $statuse->name }}</label>
                                                                        </div>
                                                                    @endif
                                                                @else
                                                                    <div style="margin-bottom:5px;">
                                                                        <input type="checkbox" class="col-form-label status" id="exampleinput{{$n}}">
                                                                        <label for="exampleinput{{$n}}" class="col-form-label">{{ $statuse->name }}</label>
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                            </div>
                                                        </th>
                                                        <th data-priority="1">Patients Name</th>
                                                        <th data-priority="1" class="pharmacy_filter">Pharmacy <i class="fa fa-filter" aria-hidden="true"></i>
                                                            <div class="pharmacys" style="display:none;">
                                                                <select id="select-state" placeholder="Select Pharmacy..." name="user" required>
                                                                    <option value="">Select Pharmacy...</option>
                                                                    @foreach($pharmacys as $pharmacy)
                                                                        @if(!empty($_GET['pharmacy']))
                                                                            @if($_GET['pharmacy']==$pharmacy->id)
                                                                                <option value="{{ $pharmacy->id }}" selected>{{ $pharmacy->name }}, {{ $pharmacy->phone }}, {{ $pharmacy->address }}</option>
                                                                            @else
                                                                                <option value="{{ $pharmacy->id }}">{{ $pharmacy->name }}, {{ $pharmacy->phone }}, {{ $pharmacy->address }}</option>
                                                                            @endif
                                                                        @else
                                                                            <option value="{{ $pharmacy->id }}">{{ $pharmacy->name }}, {{ $pharmacy->phone }}, {{ $pharmacy->address }}</option>
                                                                        @endif
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        </th>
                                                        <th data-priority="1">Driver Selected</th>
                                                        <th data-priority="3">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                @foreach ($orders as $order)
                                                    <tr>
                                                        <td>{{$order->id}}</td>
                                                        <td>{{date('m/d/Y g:i A', strtotime($order->created))}}</td>
                                                        <td><span style="font-size: 13px;" class="badge badge-pill badge-{{$order->statusecolor}}">{{$order->statusename}}</span></td>
                                                        <td>{{$order->username}} {{$order->last_name}}</td>
                                                        <td>{{$order->pharmacyname}}</td>
                                                        @if($order->driver_id>0)
                                                            <td style="color:green;">Yes</td>
                                                        @else
                                                            <td style="color:red;">No</td>
                                                        @endif
                                                        <td class="action">
                                                        @if((Auth::user()->role == 'logist' || (Auth::user()->role == 'superadmin' || Auth::user()->role == 'admin' || Auth::user()->role == 'dispadmin')) && ($order->statuse_id!=4 && $order->statuse_id!=5))
                                                            <a href="/routes-list/show/{{ $order->id }}"><button class="btn btn-warning">Open</button></a>
                                                        @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                            <form id="filter" style="display:none;">
                                                <input name="page" value="{{ $page0 }}">
                                                <input name="search" value="{{ $search }}">
                                                <input type="checkbox" name="tariff" id="tariff">
                                                <input type="checkbox" name="pharmacy" id="pharmacy">
                                                @foreach($statuses as $n=>$statuse)
                                                    @if(!empty($_GET['statuse']))
                                                        @if(in_array($statuse->id,$_GET['statuse']))
                                                            <input type="checkbox" name="statuse[]" checked value="{{ $statuse->id }}" class="col-form-label exampleinput{{$n}}">
                                                        @else
                                                            <input type="checkbox" name="statuse[]" value="{{ $statuse->id }}" class="col-form-label exampleinput{{$n}}">
                                                        @endif
                                                    @else
                                                        <input type="checkbox" name="statuse[]" value="{{ $statuse->id }}" class="col-form-label exampleinput{{$n}}">
                                                    @endif
                                                @endforeach
                                            </form>
                                        </div>

                                    </div>

                                </div>
                                <div style="position: absolute;text-align: center;width: 100%;bottom: 10px;">Pages: 
                                    @foreach ($pages as $page)
                                        <form style="display: inline-block;">
                                            <input type="hidden" name="page" value="{{ $page['id'] }}">
                                            <input type="hidden" name="search" value="{{ $search }}">
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
            <!-- Responsive Table js -->
            <script src="{{ URL::asset('/libs/rwd-table/rwd-table.min.js')}}"></script>

            <!-- Init js -->
            <script src="{{ URL::asset('/js/pages/table-responsive.init.js')}}"></script>
            <script>
                var role = "{{ Auth::user()->role }}";
                $(document).ready(function(){
                    @if(empty($_GET['tariff']))
                        $('.focus-btn-group').append('<button class="btn btn-primary tariff" data-value="all">All</button>');
                        $('.focus-btn-group').append('<button class="btn btn-secondary tariff" data-value="0.50">0.50$</button>');
                        $('.focus-btn-group').append('<button class="btn btn-secondary tariff" data-value="1.00">1.00$</button>');
                        $('.focus-btn-group').append('<button class="btn btn-secondary tariff" data-value="5.00-7.00">5.00-7.00$</button>');
                        $('.focus-btn-group').append('<button class="btn btn-secondary tariff" data-value="10.00-12.00">10.00-12.00$</button>');
                    @else
                        @if($_GET['tariff']=='all')
                            $('.focus-btn-group').append('<button class="btn btn-primary tariff" data-value="all">All</button>');
                        @else
                            $('.focus-btn-group').append('<button class="btn btn-secondary tariff" data-value="all">All</button>');
                        @endif
                        @if($_GET['tariff']=='0.50')
                            $('.focus-btn-group').append('<button class="btn btn-primary tariff" data-value="0.50">0.50$</button>');
                        @else
                            $('.focus-btn-group').append('<button class="btn btn-secondary tariff" data-value="0.50">0.50$</button>');
                        @endif
                        @if($_GET['tariff']=='1.00')
                            $('.focus-btn-group').append('<button class="btn btn-primary tariff" data-value="1.00">1.00$</button>');
                        @else
                            $('.focus-btn-group').append('<button class="btn btn-secondary tariff" data-value="1.00">1.00$</button>');
                        @endif
                        @if($_GET['tariff']=='5.00-7.00')
                            $('.focus-btn-group').append('<button class="btn btn-primary tariff" data-value="5.00-7.00">5.00-7.00$</button>');
                        @else
                            $('.focus-btn-group').append('<button class="btn btn-secondary tariff" data-value="5.00-7.00">5.00-7.00$</button>');
                        @endif
                        @if($_GET['tariff']=='10.00-12.00')
                            $('.focus-btn-group').append('<button class="btn btn-primary tariff" data-value="10.00-12.00">10.00-12.00$</button>');
                        @else
                            $('.focus-btn-group').append('<button class="btn btn-secondary tariff" data-value="10.00-12.00">10.00-12.00$</button>');
                        @endif
                    @endif
                    
                    $('body').on('click','.tariff',function() {
                        $("#tariff").val($(this).data("value"));
                        $("#tariff").prop('checked', true);
                        $("#filter").submit();
                    });
                    $('body').on('click','.status',function() {
                        if($("."+$(this).attr('id')).prop('checked')) {
                            $("."+$(this).attr('id')).prop('checked', false);
                        } else {
                            $("."+$(this).attr('id')).prop('checked', true);
                        }
                        $("#filter").submit();
                    });
                    $('#select-state').on('change',function() {
                        $("#pharmacy").val($(this).val());
                        $("#pharmacy").prop('checked', true);
                        $("#filter").submit();
                    });
                    $(document).click( function(e){
                        if( $(e.target).closest('.statuse_filter').length ) {
                            if( $(e.target).closest('.statuses').length==0 ) {
                                if($('.statuses').is(':visible')) {
                                    $('.statuses').slideUp(200);
                                } else {
                                    $('.statuses').slideDown(200);
                                }
                            }
                            return;
                        }
                        if( $(e.target).closest('.pharmacy_filter').length ) {
                            if( $(e.target).closest('.pharmacys').length==0 ) {
                                if($('.pharmacys').is(':visible')) {
                                    $('.pharmacys').slideUp(200);
                                } else {
                                    $('.pharmacys').slideDown(200);
                                }
                            }
                            return;
                        }
                        // клик снаружи элемента 
                        $('.statuses').slideUp(200);
                        $('.pharmacys').slideUp(200);
                    });
                });
            </script>
@endsection