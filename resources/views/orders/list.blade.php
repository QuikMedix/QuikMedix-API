@extends('layouts.master')

@section('title') Orders @endsection

@section('headerCss')

    <!-- Responsive Table css -->
    <link href="{{ URL::asset('/libs/rwd-table/rwd-table.min.css')}}" rel="stylesheet" type="text/css" /> <!-- Bootstrap Css -->
    <link rel="stylesheet" href="{{ URL::asset('/css/bootstrap-lib.min.css')}}">
    <style>
        .statuses {
            position: absolute;
            background-color: #fff;
            color: black;
            padding: 5px 15px;
            border-radius: 5px;
            margin-left: -35px;
            margin-top: 15px;
            border: 2px solid #e9ecef;
            z-index:1;
        }
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
        .pharmacys {
            position: absolute;
            background-color: #fff;
            color: black;
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
        .created_filter {
            cursor:pointer;
        }
        .pharmacy_filter {
            cursor:pointer;
        }
        .date-column {
            overflow: hidden;
            max-width: 155px;
            min-width: 130px;
            white-space: normal !important;
            text-align: center;
            border-bottom-width: 0px !important;
        }
        .name-column {
            overflow: hidden;
            max-width: 250px;
            width: 250px;
            white-space: normal !important;
        }
        .th-100 {
            overflow: hidden;
            max-width: 100px;
            width: 140px;
            white-space: normal !important;
        }
        .time  {
            font-size: 11px;
        }
        .start-ph { 
            background-color: rgba(122, 111, 190, 1);
            padding: 11px 11px 11px 10px;
            border-top-right-radius: 10px;
            border-bottom-right-radius: 10px;
            position: fixed;
            bottom: 160px;
            left: 0;
            width: 50px;
            height: 50px;
            text-align: center;
            box-shadow: 0 -3px 31px 0 rgb(64 59 59 / 5%), 0 6px 20px 0 rgb(58 57 57 / 40%);
            cursor: pointer;
        }
        .filter-btn { 
            background-color: rgba(122, 111, 190, 1);
            padding: 11px 11px 11px 10px;
            border-top-right-radius: 10px;
            border-bottom-right-radius: 10px;
            position: fixed;
            bottom: 90px;
            left: 0;
            width: 50px;
            height: 50px;
            text-align: center;
            box-shadow: 0 -3px 31px 0 rgb(64 59 59 / 5%), 0 6px 20px 0 rgb(58 57 57 / 40%);
            cursor: pointer;
        }
        .swal2-confirm {
            margin: 0 5px;
        }
        .confirmation {
            font-size: 11px;
            display: block;
            margin: 5px 0 0 0;
            padding: 2px 0 0 0;
        }
        #dropzone {
        position: relative;
        border: 3px dotted rgba(123, 112, 191, 0.5);
        border-radius: 20px;
        color: rgba(123, 112, 191, 0.5);
        font: bold 14px/200px arial;
        height: 200px;
        margin: 30px auto;
        text-align: center;
        width: 100%;
        }

        #dropzone.hover {
        border: 10px solid rgba(123, 112, 191, 0.65);
        color: rgba(123, 112, 191, 0.65);
        }

        #dropzone.dropped {
        background: #222;
        border: 10px solid rgba(123, 112, 191, 0.65);
        }

        #dropzone div {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        }

        #dropzone img {
        border-radius: 10px;
        vertical-align: middle;
        max-width: 95%;
        max-height: 95%;
        }

        #dropzone [type="file"] {
        cursor: pointer;
        position: absolute;
        opacity: 0;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        }
        a.repeat {
            font-size: 11px;
            background: #5c50a3;
            border: solid 1px #5c50a3;
            color: white;
            padding: 1px 4px;
            border-radius: 5px;
        }
        a.repeat:hover {
            background: none;
            border: solid 1px #5c50a3;
            color: #5c50a3;
        }  

        .printstick {
            background: #7a6fbe;
            color: white;
            padding: 0px 11px 11px 7px;
            border-top-right-radius: 10px;
            border-bottom-right-radius: 10px;
            position: fixed;
            bottom: 226px;
            font-size: 34px;
            left: 0;
            width: 50px;
            height: 50px;
            text-align: center;
            box-shadow: 0 -3px 31px 0 rgb(64 59 59 / 5%), 0 6px 20px 0 rgb(58 57 57 / 40%);
        }
        .printstick:hover {
            color: white;
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
                                            <button type="button" class="btn {{$page['class']}} page-btn">{{ $page['id'] }}</button>
                                        </form> 
                                    @endforeach
                                    ...
                                </div>
                                <div class="card-body" style="margin-bottom:20px;">
                                    <div class="table-rep-plugin">
                                        <div class="table-responsive mb-0" data-pattern="priority-columns">
                                            <table id="mytable" class="table  table-striped pb-4 mb-5">
                                                <thead>
                                                    <tr style="background: linear-gradient(148deg, #2b3a4a 0%, #4d4197 55%, #8177c2 100%); color: white;">
                                                        <th class="text-center">Order</th>
                                                        <th class="created_filter text-center" data-priority="1">Date</th>
                                                        <th data-priority="1" class="statuse_filter text-center">Status</th>
                                                        <th data-priority="1">Patients</th>
                                                        <th data-priority="1" class="pharmacy_filter">Pharmacy</th>
                                                        <th class="th-100 text-center" data-priority="1">Driver</th>
                                                        <th class="text-center" data-priority="1">Rate</th>
                                                        <th data-priority="3">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                @foreach ($orders as $order)
                                                    <tr>
                                                        <td class="text-center" style="font-size: 15px;color: black;vertical-align: middle;">{{$order->id}}<br>
														    @if(($order->delivery_time_id==3))
                                                                <span style="font-size:10px;width:110px;white-space:normal;padding: 5px;color: white;" class="badge badge-pill badge-danger">{{$delivery_times[$order->delivery_time_id]->name}}</span>
                                                            @elseif($order->delivery_time_id==2)
                                                                <span style="font-size:10px;width:110px;white-space:normal;padding: 5px;color: white;" class="badge badge-pill badge-info">{{$delivery_times[$order->delivery_time_id]->name}}</span>
                                                            @elseif($order->delivery_time_id==4)
                                                                <span style="font-size:10px;width:110px;white-space:normal;padding: 5px;color: white;" class="badge badge-pill badge-danger">{{$delivery_times[$order->delivery_time_id]->name}}</span>
                                                            @else
                                                                <span style="font-size:10px;width:110px;white-space:normal;padding: 5px;color: white;" class="badge bg-primary badge-pill badge-info">{{$delivery_times[$order->delivery_time_id]->name}}</span>
                                                            @endif	
                                                            <br>
                                                            Bags: {{$order->count_bags}}
                                                            <br>
                                                            @if($order->actual=='1')
                                                            <span class="confirmation"><i class="mdi mdi-checkbox-blank-circle text-success"></i> Confirm</span>
                                                            @elseif($order->actual=='2')
                                                            <span class="confirmation"><i class="mdi mdi-checkbox-blank-circle text-danger"></i> Not confirmed</span>
                                                            @else
                                                            <span class="confirmation"><i class="mdi mdi-checkbox-blank-circle text-warning"></i> Waiting</span>
                                                            @endif
                                                            @if(!empty($order->special_instructions))
                                                            <span class="confirmation" style="border: solid 1px;max-width: 100px;margin: 5px auto;padding: 2px;background: #214848;color: #f6f4ff;"><i class="mdi mdi-alert"></i> Special Info</span>
                                                            @endif
                                                            @if(!empty($order->merchantOrder))
                                                            <img src="{{ URL::asset('/images/mms-logo-v2.png')}}" alt="mms-logo" class="mt-2 text-center" height="28">
                                                            @endif
														</td>
                                                        <td class="date-column time" style="vertical-align: middle;">Created:<br>{{date('m/d/Y g:i A', strtotime($order->created))}}
                                                            <br>Need Delivery:<br><span style="font-size:100%;" class="badge @if(empty($order->finish) && strtotime(date('Y-m-d'))==strtotime($order->delivery_date)){{'bg-success'}}@elseif(empty($order->finish) && strtotime(date('Y-m-d'))>strtotime($order->delivery_date)){{'bg-danger'}}@else{{'bg-light'}}@endif">{{date('m/d/Y', strtotime($order->delivery_date))}} @if(!empty($order->delivery_time_range) && $order->delivery_time_range!='9:00 AM;12:00 AM')<b>{{str_replace(':00','',str_replace(';',' - ',$order->delivery_time_range))}}</b>@endif</span>
                                                            @if(!empty($order->finish))
                                                            <br>Delivered:<br>{{date('m/d/Y g:i A', strtotime($order->finish))}}
                                                            @endif
                                                        </td>
                                                        <td style="vertical-align: middle;text-align: center;">
                                                            <span style="font-size: 11px;padding: 4px 5px;border-radius: 3px;box-shadow: 0 -3px 31px 0 rgb(64 59 59 / 5%), 0 6px 20px 0 rgb(58 57 57 / 20%);" class="badge badge-pill badge-{{$statuses[$order->statuse_id]->color}}">{{$statuses[$order->statuse_id]->name}}</span>
                                                            @if((Auth::user()->role == 'medic' || (Auth::user()->role == 'superadmin' || Auth::user()->role == 'admin')) && ($order->statuse_id==4))                                                            
                                                            <div style="margin: 10px 0;font-size: 20px;line-height: 20px;color: #f5b225;"><span style="margin-top: 10px;font-size: 12px;color:#5b626b;">Customer rating</span><br>
                                                            @if(empty($order->rating))
                                                            <i class="mdi mdi-star-outline"></i><i class="mdi mdi-star-outline"></i><i class="mdi mdi-star-outline"></i><i class="mdi mdi-star-outline"></i><i class="mdi mdi-star-outline"></i>
                                                            @else
                                                            @if($order->rating<=1)
                                                            <i class="mdi mdi-star"></i><i class="mdi mdi-star-outline"></i><i class="mdi mdi-star-outline"></i><i class="mdi mdi-star-outline"></i><i class="mdi mdi-star-outline"></i>
                                                            @elseif($order->rating==2)
                                                            <i class="mdi mdi-star"></i><i class="mdi mdi-star"></i><i class="mdi mdi-star-outline"></i><i class="mdi mdi-star-outline"></i><i class="mdi mdi-star-outline"></i>
                                                            @elseif($order->rating==3)
                                                            <i class="mdi mdi-star"></i><i class="mdi mdi-star"></i><i class="mdi mdi-star"></i><i class="mdi mdi-star-outline"></i><i class="mdi mdi-star-outline"></i>
                                                            @elseif($order->rating==4)
                                                            <i class="mdi mdi-star"></i><i class="mdi mdi-star"></i><i class="mdi mdi-star"></i><i class="mdi mdi-star"></i><i class="mdi mdi-star-outline"></i>
                                                            @else
                                                            <i class="mdi mdi-star"></i><i class="mdi mdi-star"></i><i class="mdi mdi-star"></i><i class="mdi mdi-star"></i><i class="mdi mdi-star"></i>
                                                            @endif                                                            
                                                            @endif
                                                            </div>
                                                            <form method="post">
                                                                @csrf
                                                                <input type="hidden" name="order_id" value="{{$order->id}}">
                                                                <input type="hidden" name="repeat" value="1">
                                                                <a class="repeat" type="button" onclick="if(confirm('Are you sure?')){$(this).parent('form').submit();}" style=""><i class="mdi mdi-repeat-once"></i> Repeat order</a>
                                                            </form>
                                                            @endif                                                     
                                                        </td>
                                                        <td style="vertical-align: middle;color: #000000;">
                                                            @if(isset($patients[$order->user_id]) && $patients[$order->user_id]->os==1)
                                                            <span class="badge badge-pill badge-dark" style="margin: 7px 0;">Android <i class="ion ion-logo-android"></i> </span> 
                                                            @elseif(isset($patients[$order->user_id]) && $patients[$order->user_id]->os==2)
                                                            <span class="badge badge-pill badge-dark" style="margin: 7px 0;">iOS <i class="ion ion-logo-apple"></i></span>
                                                            @else
                                                            <span class="badge badge-pill badge-danger" style="margin: 7px 0;">No app <i class="ion ion-md-close-circle-outline"></i></span>
                                                            @endif
                                                            @if($order->facility)
                                                            <span class="badge badge-pill bg-light" style="margin: 7px 0;">Facility</span>
                                                            @endif
                                                            <br>
                                                            @if(isset($patients[$order->user_id]))
                                                            <span style="max-width: 220px;display: block;overflow: hidden;white-space: nowrap;text-overflow: ellipsis;">{{$patients[$order->user_id]->last_name}} {{$patients[$order->user_id]->name}}</span>
                                                            <span class="badge badge-pill badge-info" style="margin: 5px 0;padding: 0.3em 0.8em;">{{$delivery_methods[$order->delivery_method_id]->name}}</span> 
                                                            <div class="button-items" style="margin-top: 5px;">
                                                                <a href="/orders/{{$order->pharmacy_id}}?search={{$patients[$order->user_id]->name}} {{$patients[$order->user_id]->last_name}}">
                                                                    <button type="button" class="btn btn-secondary btn-sm waves-effect">History <i class="mdi mdi-history"></i></button>
                                                                </a>
                                                                @if((Auth::user()->role == 'superadmin' || Auth::user()->role == 'admin') || Auth::user()->role == 'logist')
                                                                <button class="btn btn-primary btn-sm waves-effect call_patient" data-phone="{{$patients[$order->user_id]->phone}}">Call <i class="ti-headphone-alt"></i></button>
                                                                @endif                                            
                                                            </div>
                                                            @else
                                                            <span style="max-width: 220px;display: block;overflow: hidden;white-space: nowrap;text-overflow: ellipsis;color: #ec536c;font-weight: bold;">Patient is deleted! (ID #{{$order->user_id}})</span>
                                                            @endif
                                                            @if($order->statuse_id==3)
                                                            <div class="eta-orders" style="font-size: 12px;margin-top: 10px;font-weight: 100;border-top: solid 1px #afafaf;border-bottom: solid 1px #afafaf;text-align: center;">
                                                            <b>Time away:</b> {{floor($order->eta / 60)}} hr {{$order->eta % 60}} min
                                                            </div>  
                                                            @endif
                                                        </td>
                                                        <td style="vertical-align: middle;color: #000000;">
                                                            @if(isset($pharmacys[$order->pharmacy_id]))
                                                            <span style="max-width: 220px;width: auto;display: block;white-space: break-spaces;">{{$pharmacys[$order->pharmacy_id]->name}}</span>
                                                            <div class="button-items" style="margin-top: 10px;">
                                                                @if((Auth::user()->role == 'superadmin' || Auth::user()->role == 'admin') || Auth::user()->role == 'logist')
                                                                <a href="/orders/{{$order->pharmacy_id}}">
                                                                    <button type="button" class="btn btn-secondary btn-sm waves-effect">History <i class="mdi mdi-history"></i></button>
                                                                </a>
                                                                <button class="btn btn-primary btn-sm waves-effect call_patient" data-phone="{{$pharmacys[$order->pharmacy_id]->phone}}">Call <i class="ti-headphone-alt"></i></button>
                                                                @endif      
                                                            </div>                                                   
                                                            @else
                                                            <span style="max-width: 220px;width: auto;display: block;white-space: break-spaces;color: #ec536c;font-weight: bold;">Pharmacy is deleted! (ID #{{$order->pharmacy_id}})</span>
                                                            @endif
                                                        </td>
                                                        @if($order->driver_id>0)
                                                            <td style="color: black;vertical-align: middle;text-align: center;">
                                                            @if(isset($drivers[$order->driver_id]))
                                                            @if(!empty($drivers[$order->driver_id]->pharmacy_id))
                                                            <span style="font-size:10px;white-space:normal;padding: 5px;color: white;" class="badge badge-pill badge-secondary">Pharmacy driver</span>
                                                            @else
                                                            <span style="font-size:10px;white-space:normal;padding: 5px;color: white;" class="badge badge-pill badge-info">A2B Rx driver</span>
                                                            @endif
                                                            <br>
                                                            <span style="line-height: 22px;">{{$drivers[$order->driver_id]->name}} {{$drivers[$order->driver_id]->last_name}}<span> <br>
                                                            @if((Auth::user()->role == 'superadmin' || Auth::user()->role == 'admin') || Auth::user()->role == 'logist')
                                                            <a href="/routes-list/driver/{{$order->driver_id}}" target="_blank" style="border: solid 1px #000;color: black;padding: 1px 5px;font-size: 11px;">View route</a>
                                                            @endif
                                                            @else
                                                            <span style="line-height: 22px;color: #ec536c;font-weight: bold;">Driver is deleted! (ID #{{$order->driver_id}})<span> <br>
                                                            @endif
                                                            </td>
                                                        @else
                                                            <td style="color: black;vertical-align: middle;text-align: center;">No</td>
                                                        @endif
                                                        <td class="text-center" style="vertical-align: middle;">
                                                            <span title="Tariff" style="font-size: 15px;color: #000;width: 70px;display: block;margin: 0 auto;">${{number_format($order->tariff,2)}}</span>
                                                            @if($order->copay==0 || empty($order->statuse_copay))
                                                            <span title="Co-pay" style="font-size: 11px;color: black;padding: 4px 5px;border-radius: 3px;box-shadow: 0 -3px 31px 0 rgb(64 59 59 / 5%), 0 6px 20px 0 rgb(58 57 57 / 20%);" class="badge badge-pill badge-success mt-1">$0 Not required</span>
                                                            @else
                                                            <span title="Co-pay" style="font-size: 11px;color: black;padding: 4px 5px;border-radius: 3px;box-shadow: 0 -3px 31px 0 rgb(64 59 59 / 5%), 0 6px 20px 0 rgb(58 57 57 / 20%);" class="badge badge-pill badge-{{$statuses_copay[$order->statuse_copay]->color}} mt-1">${{round($order->copay,2)}} {{$statuses_copay[$order->statuse_copay]->name}}</span>
                                                            @endif
                                                        </td>
                                                        <td class="action" style="vertical-align: middle;">
                                                            <div class="btn-group">
                                                            <button class="btn btn-info btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                                                                Action
                                                                <i class="mdi mdi-chevron-down"></i>
                                                            </button>
                                                            <div class="dropdown-menu text-black">
                                                                <a class="dropdown-item" href="/orders/{{ $order->pharmacy_id }}/show/{{ $order->id }}">View order</a>
                                                                @if(((Auth::user()->role == 'medic') && ($order->statuse_id!=4 && $order->statuse_id!=5)) || ((Auth::user()->role == 'superadmin' || Auth::user()->role == 'admin')) || (Auth::user()->role == 'logist'))
                                                                @if($order->facility)
                                                                <a href="/orders/{{ $order->pharmacy_id }}/facilitys_edit/{{ $order->id }}" class="dropdown-item">Edit</a>
                                                                @else
                                                                <a href="/orders/{{ $order->pharmacy_id }}/edit/{{ $order->id }}" class="dropdown-item">Edit</a>
                                                                @endif
                                                                @endif
                                                                @if((Auth::user()->role == 'superadmin' || Auth::user()->role == 'admin') && $order->statuse_id==5)
                                                                <div class="dropdown-divider"></div>
                                                                <form method="post">
                                                                    @csrf
                                                                    <input type="hidden" name="order_id" value="{{$order->id}}">
                                                                    <input type="hidden" name="remove" value="1">
                                                                    <a class="dropdown-item" type="button" onclick="if(confirm('Are you sure?')){$(this).parent('form').submit();}" style="">Remove</a>
                                                                </form>
                                                                @endif                                                              
                                                                </div>
                                                            </div>
                                                            @if((Auth::user()->role == 'medic' || (Auth::user()->role == 'superadmin' || Auth::user()->role == 'admin')) && ($order->statuse_id==1 || $order->statuse_id==2))
                                                            <a href="#" onclick="printTicket('{{$order->id}}')"><button class="btn btn-sm btn-secondary"><i class="ti-printer"></i></button></a>
                                                            @endif                                                             

                                                            <!-- <a href="/orders/{{ $order->pharmacy_id }}/show/{{ $order->id }}"><button class="btn btn-success">View order</button></a>
                                                            @if(((Auth::user()->role == 'medic') && ($order->statuse_id!=4 && $order->statuse_id!=5)) || ((Auth::user()->role == 'superadmin' || Auth::user()->role == 'admin')) || (Auth::user()->role == 'logist'))
                                                                <a href="/orders/{{ $order->pharmacy_id }}/edit/{{ $order->id }}"><button class="btn btn-warning">Edit</button></a>
                                                            @endif
                                                            @if((Auth::user()->role == 'superadmin' || Auth::user()->role == 'admin') && $order->statuse_id==5)
                                                                <form method="post" style="display: inline-block;">
                                                                    @csrf
                                                                    <input type="hidden" name="order_id" value="{{$order->id}}">
                                                                    <input type="hidden" name="remove" value="1">
                                                                    <button class="btn btn-danger" type="button" onclick="if(confirm('Are you sure?')){$(this).parent('form').submit();}">Remove</button>
                                                                </form>
                                                            @endif
                                                            @if((Auth::user()->role == 'medic' || (Auth::user()->role == 'superadmin' || Auth::user()->role == 'admin')) && ($order->statuse_id==1 || $order->statuse_id==2))
                                                            <a href="#" onclick="printTicket('{{$order->id}}')"><button class="btn btn-secondary"><i class="ti-printer"></i></button></a>
                                                            @endif -->
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                    </div>

                                </div>
                                <div style="position: absolute;text-align: center;width: 100%;bottom: 10px;">Pages: 
                                    @foreach ($pages as $page)
                                        <form style="display: inline-block;">
                                            <input type="hidden" name="page" value="{{ $page['id'] }}">
                                            <button type="button" class="btn {{$page['class']}} page-btn">{{ $page['id'] }}</button>
                                        </form> 
                                    @endforeach
                                    ...
                                </div>
                            </div>
                        </div>
                        <!-- end col -->
                    </div>
                    <!-- end row -->
                @if(((Auth::user()->role == 'superadmin' || Auth::user()->role == 'admin') || Auth::user()->role == 'medic') && !empty($pharmacy_id))
                <div class="start-ph" id="sa-params" title="Start route automation" data-bs-original-title="Start route automation">              
                    <img src="https://cp.a2brx.com/images/start_new.svg" alt="Start">
                    @php
                    $get_pharmacy_notReady = Auth::user()->get_pharmacy_notReady($pharmacy_id);
                    @endphp
                    @if($get_pharmacy_notReady>0)
                    <span class="badge badge-danger badge-pill" style="position: absolute;top: -2px;right: -12px;">{{$get_pharmacy_notReady}}</span>
                    @endif
                </div>
                @endif
                @if(Auth::user()->role == 'medic')  
                <a href="/orders/tickets/print" target="_blank" class="printstick" title="Print all labels">
                    <i class="mdi mdi-printer"></i>
                </a>
                @endif     
                <div class="filter-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasLeft" aria-controls="offcanvasLeft" title="Filter orders" data-bs-original-title="Filter orders">              
                    <img src="https://cp.a2brx.com/images/filter.svg" alt="filter icon">
                </div>
                <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasLeft" aria-labelledby="offcanvasLeftLabel" aria-modal="true" role="dialog">
                    <div class="offcanvas-header">
                        <h5 id="offcanvasLeftLabel">Filter Form</h5>
                        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                    </div>
                    <div class="offcanvas-body">
                        <form method="GET" id="filter" enctype="multipart/form-data">
                            <input type="hidden" name="page" value="{{ $page0 }}">
                            <input type="hidden" name="search" value="{{ $search }}">
                            <div class="mb-3">
                                <label>Date Created</label>
                                <div>
                                    <div class="input-daterange input-group" data-date-format="mm/dd/yyyy" data-date-autoclose="true" data-provide="datepicker">
                                        <input type="text" class="form-control" name="create_start" value="{{$filter['create_start']}}" autocomplete="off">
                                        <input type="text" class="form-control" name="create_end" value="{{$filter['create_end']}}" autocomplete="off">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label>Date Need Delivery</label>
                                <div>
                                    <div class="input-daterange input-group" data-date-format="mm/dd/yyyy" data-date-autoclose="true" data-provide="datepicker">
                                        <input type="text" class="form-control" name="need_delivery_start" value="{{$filter['need_delivery_start']}}" autocomplete="off">
                                        <input type="text" class="form-control" name="need_delivery_end" value="{{$filter['need_delivery_end']}}" autocomplete="off">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label>Date Delivered</label>
                                <div>
                                    <div class="input-daterange input-group" data-date-format="mm/dd/yyyy" data-date-autoclose="true" data-provide="datepicker">
                                        <input type="text" class="form-control" name="delivered_start" value="{{$filter['delivered_start']}}" autocomplete="off">
                                        <input type="text" class="form-control" name="delivered_end" value="{{$filter['delivered_end']}}" autocomplete="off">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="time">Preferred delivery time</label>
                                <select class="select2 form-control select2-multiple" name="delivery_time[]" multiple="" data-placeholder="Choose ...">
                                    @foreach($delivery_times as $delivery_time)
                                        @if(!empty($filter))
                                            @if(in_array($delivery_time->id,$filter['delivery_time']))
                                                <option value="{{$delivery_time->id}}" selected>{{$delivery_time->name}}</option>
                                            @else
                                                <option value="{{$delivery_time->id}}">{{$delivery_time->name}}</option>
                                            @endif
                                        @else
                                            <option value="{{$delivery_time->id}}">{{$delivery_time->name}}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="time">Preferred delivery method</label>
                                <select class="select2 form-control select2-multiple" name="delivery_method[]" multiple="" data-placeholder="Choose ...">
                                    @foreach($delivery_methods as $delivery_method)
                                        @if(!empty($filter))
                                            @if(in_array($delivery_method->id,$filter['delivery_method']))
                                                <option value="{{$delivery_method->id}}" selected>{{$delivery_method->name}}</option>
                                            @else
                                                <option value="{{$delivery_method->id}}">{{$delivery_method->name}}</option>
                                            @endif
                                        @else
                                            <option value="{{$delivery_method->id}}">{{$delivery_method->name}}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            @if((Auth::user()->role == 'superadmin' || Auth::user()->role == 'admin' || Auth::user()->role == 'dispadmin') && isset($filter['pharmacy']))
                            <div class="form-group mb-3" data-select2-id="78">
                                <label class="form-label">Pharmacy</label>
                                <select class="select2 form-control select2-multiple" name="pharmacy[]" multiple="" data-matcher="customMatcher" data-placeholder="Choose ...">
                                    @foreach($pharmacys as $pharmacy)
                                        @if(!empty($filter))
                                            @if(in_array($pharmacy->id,$filter['pharmacy']))
                                                <option value="{{$pharmacy->id}}" selected>{{$pharmacy->name}}</option>
                                            @else
                                                <option value="{{$pharmacy->id}}">{{$pharmacy->name}}</option>
                                            @endif
                                        @else
                                            <option value="{{$pharmacy->id}}">{{$pharmacy->name}}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            @endif
                            <div class="form-group mb-3" data-select2-id="78">
                                <label class="form-label">Order Status</label>
                                <select class="select2 form-control select2-multiple" name="status[]" multiple="" data-placeholder="Choose ...">
                                    @foreach($statuses as $order_statuse)
                                        @if(!empty($filter))
                                            @if(in_array($order_statuse->id,$filter['status']))
                                                <option value="{{$order_statuse->id}}" selected>{{$order_statuse->name}}</option>
                                            @else
                                                <option value="{{$order_statuse->id}}">{{$order_statuse->name}}</option>
                                            @endif
                                        @else
                                            <option value="{{$order_statuse->id}}">{{$order_statuse->name}}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mb-3" data-select2-id="   ">
                                <label class="form-label">Client <span class="badge" style="background-color: #00b927 !important;color: white;">New</span></label>
                                <select class="select2 form-control select2-multiple" name="facility[]" multiple="" data-placeholder="Choose ...">
                                    <option value="0" @if(in_array('0',$filter['facility']) || count($filter['facility'])==0){{'selected'}}@endif>Patients</option>
                                    <option value="1" @if(in_array('1',$filter['facility']) || count($filter['facility'])==0){{'selected'}}@endif>Facilitys</option>
                                </select>
                            </div>
                            <input type="hidden" name="filter" value="1">
                            <button class="btn btn-primary w-100" onclick="$('[name=&quot;page&quot;]').val('');$('[name=&quot;search&quot;]').val('');">Filter <i class="mdi mdi-filter"></i></button>
                        </form>
                    </div>
                </div>
                <form method="GET" id="reload"></form>   
                <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasRight" aria-labelledby="offcanvasRightLabel">
                    <div class="offcanvas-header">
                        <h5 id="offcanvasRightLabel">Upload Delivery Slip</h5>
                        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                    </div>
                    <div class="offcanvas-body">
                        <div>
                            <form id="my-dropzone" method="POST" action="/import/{{$pharmacy_id}}/order" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="step2" value="1">
                                <div id="dropzone">
                                    <div>Drop PDF file here or click to upload.</div>
                                    <input name="import" type="file" accept="application/pdf" />
                                </div>
                            </form>
                        </div>
                        <div class="text-center mt-4">
                            <button type="button" onclick="$('#my-dropzone').submit()" class="btn btn-primary waves-effect waves-light">Send Files
                            </button>
                        </div>
                    </div>
                </div>               
@endsection

@section('footerScript')
<!-- Responsive Table js -->
<script src="{{ URL::asset('/libs/rwd-table/rwd-table.min.js')}}"></script>

<!-- Init js -->
<script src="{{ URL::asset('/js/pages/table-responsive.init.js')}}"></script>
<script>
    $(function() {
        $(".select2").selectize()
        $('#dropzone').on('dragover', function() {
            $(this).addClass('hover');
        });
        
        $('#dropzone').on('dragleave', function() {
            $(this).removeClass('hover');
        });
        
        $('#dropzone input').on('change', function(e) {
            var file = this.files[0];

            $('#dropzone').removeClass('hover');
            
            if (this.accept && $.inArray(file.type, this.accept.split(/, ?/)) == -1) {
            return alert('File type not allowed.');
            }
            
            $('#dropzone').addClass('dropped');
            $('#dropzone img').remove();
            
            if ((/^image\/(gif|png|jpeg)$/i).test(file.type)) {
            var reader = new FileReader(file);

            reader.readAsDataURL(file);
            
            reader.onload = function(e) {
                var data = e.target.result,
                    $img = $('<img />').attr('src', data).fadeIn();
                
                $('#dropzone div').html($img);
            };
            } else {
            var ext = file.name.split('.').pop();
            
            $('#dropzone div').html(ext);
            }
        });
    });
    $("#sa-params").click(function(){
        Swal.fire({
            title:"Are you sure?",
            text:"Route automation usually takes up to 30 minutes.",
            icon:"warning",
            showCancelButton:!0,
            confirmButtonText:"Yes, create routes!",
            cancelButtonText:"Cancel",
            confirmButtonClass:"btn btn-success mt-2",
            cancelButtonClass:"btn btn-danger ms-2 mt-2",
            buttonsStyling:!1
        }).then(function(t){
            if(t.value){
                Swal.fire({
                    title:"Success!",
                    text:"Your request has been processed.",
                    icon:"success"
                });
                $.post("/orders/{{$pharmacy_id}}/ready", { _token: $('input[name="_token"]').val() }).done(function( data ) {
                    data=JSON.parse(data);
                    if(data.message=="OK") {
                        $("#reload").submit();
                    } else {
                        alert(data.message);
                    }
                });
            } else {
                Swal.fire({
                    title:"Cancelled",
                    text:"Come back when you're ready :)",
                    icon:"error"
                });
            }
        });
    });
    var role = "{{ Auth::user()->role }}";
    var page = "{{ $page0 }}";
    $(document).ready(function(){
        @if(!empty($_GET['added']))
            printTicket('{{$_GET['added']}}');
        @endif
        if(role!='admin' && role!='medic') {
            $('.addorder').hide();
        }
        $("#search-page").val(page);
    });
    function PrintDay() {
        var date = $('#date_print').val();
        if(date!="") {
            $.get('/orders/day/print',{date:date}).done(function(response) {
                Popup(response);
            });
        } else {
            alert("Date has not be empty!");
        }
    }

    function printTicket(order_id) {
        $.get('/orders/ticket/print',{order_id:order_id}).done(function(response) {
            Popup(response);
        });
    }

    function PrintElem(elem){
        Popup($(elem).html());
    }

    function Popup(data){
        var mywindow = window.open('', 'my div', 'height=400,width=600');
        mywindow.document.write('<html><head><title>Print Check</title>');
        mywindow.document.write('<link href="https://cp.a2brx.com/css/bootstrap-lib.min.css" id="bootstrap-style" rel="stylesheet" type="text/css">');
        mywindow.document.write('</head><body >');
        mywindow.document.write(data);
        mywindow.document.write('</body></html>');
        mywindow.document.close(); // necessary for IE >= 10
        mywindow.focus(); // necessary for IE >= 10
        setTimeout(() => {mywindow.print(); }, 1500);
        //mywindow.onafterprint = function(){ mywindow.close();}
        return true;
    }

</script>
@endsection