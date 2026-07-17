@extends('layouts.master')

@section('title') Dispatching @endsection

@section('headerCss')
<link href="/leaflet/leaflet.css" rel="stylesheet" type="text/css">
    <!-- Responsive Table css -->
    <link rel="stylesheet" href="{{ URL::asset('/css/bootstrap-multiselect.min.css')}}" type="text/css"/>
    <link rel="stylesheet" href="{{ URL::asset('/css/bootstrap-lib.min.css')}}">
    <link href="{{ URL::asset('/libs/rwd-table/rwd-table.min.css')}}" rel="stylesheet" type="text/css" /> <!-- Bootstrap Css -->
    <link href="{{ URL::asset('/css/sweetalert2.min.css')}}" rel="stylesheet" type="text/css">
    <style>
        .card-dispatch {
            padding: 30px 10px 0 10px;
            color: black;
            background: #ffffff;
        }
        .card-dispatch img {
            width: 140px;
            background: #dcefe3;
            padding: 10px;
            border-radius: 20%;
            float: left;
            margin: 0 15px 0 0;
        }
        .card.disabled {
            pointer-events: none;
            opacity: 0.4;
        }
        .button-items {
            display: inline-flex;
        }
        .list-dispatch{
            scroll-snap-type: y mandatory;
            overflow-x: auto;
            height: 470px;
        }
        .row-dispatch {
            margin-bottom: 110px !important;
            scroll-snap-align: start;
            scroll-snap-stop: normal;
        }
        .loader {
            opacity: 0;
            position: absolute;
            top: 0px;
            left: 0px;
            width: 100%;
            height: 490px;
            background: #fff;
            transition: all 0.3s ease-out;
            z-index: -1;
        }
        .loader.show {
            z-index: 1000;
            opacity: 1;
        }
        .loader img {
            width: 150px;
            height: 450px;
            object-fit: contain;
            margin-left: 50%;
            transform: translate(-50%, 0px);
        }
        body.swal2-shown .offcanvas-bottom {
            visibility: hidden !important;
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
                <div class="card-body pb-1">
                <h4 class="float-left pb-1">Dispatcher: Shahzod </h4> 
                <h6 class="float-right mt-2">Total Tasks <span class="badge bg-info text-light">{{$orders_count}}</span> Done <span class="badge bg-success text-light">12</span> Urgently <span class="badge bg-danger text-light">4</span> Missed <span class="badge bg-dark text-light">4</span></h5>
                
                </div> 
            </div> 
        </div>
    </div>


    <div class="row">
        <div class="col-8 list-dispatch">
            @foreach($orders as $order)
            <div class="card row-dispatch @if($order->call_disabled){{'disabled'}}@endif" data-id="{{str_replace(',','',$order->order_id)}}">
                <div class="card-body">
                    <div class="card-dispatch row">
                        <div class="col-7">
                        <h5 style="position: absolute;top: -35px;left: 0;"><span class="badge bg-dark text-light"># {{ $loop->iteration }}</span></h5>
                        
                            <img src="/images/dispatch_icon.svg" alt="dispatching">                         
                            <h6>Order: {{$order->order_id}} @if(!empty($order->special_instructions))
                                                            <span class="сonfirmation" style="border: solid 1px;max-width: 100px;border-radius: 5px;padding: 2px 5px;background: #e90000;color: #f6f4ff; margin: 0 6px;"><i class="mdi mdi-alert"></i> Special Info</span>
                                                            @endif</h6>
                            <h6>Customer: {{$order->patient_name}} <button class="btn btn-primary btn-sm waves-effect call_patient" data-phone="{{$order->patient_phone}}">Call <i class="ti-headphone-alt"></i></button></h6>
                            <h6>Driver: {{$order->driver_name}} <button class="btn btn-primary btn-sm waves-effect call_patient" data-phone="{{$order->driver_phone}}">Call <i class="ti-headphone-alt"></i></button> <a href="/routes-list/driver/{{$order->driver_id}}" target="_blank"><button class="btn btn-outline-dark btn-sm waves-effect">Route <i class="ti-map-alt"></i></button></a> </h6> 
                            <h6>Pharmacy: @foreach(explode(",",$order->pharmacy_name) as $key=>$pharmacy_name)
                                @if(isset(explode(",",$order->pharmacy_phone)[$key]))
                                {{$pharmacy_name}} <button class="btn btn-primary btn-sm waves-effect call_patient" data-phone="{{explode(",",$order->pharmacy_phone)[$key]}}">Call <i class="ti-headphone-alt"></i></button>
                                @endif
                                @endforeach
                            </h6>
                            <h6>Delivery address: {{$order->patient_address}} apt. {{$order->patient_apartment}}</h6>
                            <div>                                                                                                          
                            </div>  
                        </div>
                        <div class="col-5">
                            <div class="button-items float-right mb-2">
                                <form method="POST">
                                    @csrf
                                    <input type="hidden" name="confirmed" value="1">
                                    <input type="hidden" name="order_id" value="{{$order->order_id}}">
                                    <button class="btn btn-primary waves-effect waves-light" role="submit">Confirmed <i class="mdi mdi-account-check"></i></button>
                                </form>
                                <form method="POST">
                                    @csrf
                                    <input type="hidden" name="not_confirmed" value="1">
                                    <input type="hidden" name="order_id" value="{{$order->order_id}}">
                                    <button class="btn btn-warning waves-effect waves-light" role="submit">Not confirmed <i class="mdi mdi-cellphone-off"></i></button>
                                </form>
                                <form method="POST" class="date-form ml-2" style="display:none;">
                                    @csrf
                                    <input type="hidden" name="reschedule" value="1">
                                    <input type="hidden" name="order_id" value="{{$order->order_id}}">
                                    <input type="date" name="date" class="form-control" style="width:120px;">
                                    <button type="submit" class="btn btn-success">Save</button>
                                </form>
                                <button class="btn btn-info waves-effect waves-light reschedule" onclick="$('.reschedule').hide();$('.date-form').show();" role="button">Reschedule <i class="mdi mdi-calendar-clock"></i></button>
                            </div>
                            <p class="card-title-desc mb-1">&nbsp;</p>
                            <div class="button-items mb-2" style="display: flex;justify-content: flex-end;width: 100%;">
                                <button class="btn btn-outline-secondary" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasLeft" aria-controls="offcanvasLeft">Customer Chat <i class="mdi mdi-chat-processing"></i></button>
                                <button class="btn btn-outline-secondary" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasLeft2" aria-controls="offcanvasLeft2">Driver Chat <i class="mdi mdi-chat-processing"></i></button> 
                                <button class="btn btn-outline-secondary" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvas{{str_replace(',','',$order->order_id)}}" aria-controls="offcanvasRight3">Notes <i class="mdi mdi-book-information-variant"></i></button>                                                   
                            </div>  
                            <div class="float-right mb-2" style="display: flex;width: 1%;justify-content: flex-end;flex-wrap: wrap;">
                                @php 
                                if(!isset($routes_priority[$order->driver_id])){
                                    $routes_priority[$order->driver_id]=new \stdClass();
                                    $routes_priority[$order->driver_id]->count_delivery=0;
                                }
                                if(!isset($routes_logs[$order->driver_id])){
                                    $routes_logs[$order->driver_id]=new \stdClass();
                                    $routes_logs[$order->driver_id]->count_delivered=0;
                                }
                                @endphp
                                <h4><span class="badge bg-light">Driver Stage #{{$order->priority}}/{{($routes_priority[$order->driver_id]->count_delivery+$routes_logs[$order->driver_id]->count_delivered)}}</span></h4>
                                <h4><span class="badge bg-dark">Co-Pay: ${{$order->copay}}</span></h4>      
                        
                            </div>      
                            <!-- right offcanvas -->
                            <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasLeft" aria-labelledby="offcanvasLeftLabel" aria-modal="true" role="dialog">
                                <div class="offcanvas-header">
                                    <h5 id="offcanvasLeftLabel">Chat - {{$order->patient_name}} </h5>
                                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                                </div>
                                <div class="offcanvas-body"> 
                                    <ul class="list-unstyled activity-feed ms-1">
                                        <li class="feed-item">
                                            <div class="feed-item-list">
                                                <div>
                                                    <div class="date">10 Apr</div>
                                                    <p class="activity-text mb-0">Test #1</p>
                                                </div>
                                            </div> 
                                        </li>

                                        <li class="feed-item">
                                            <div class="feed-item-list">
                                                <div>
                                                    <div class="date">10 Apr</div>
                                                    <p class="activity-text mb-0">Test #2</p>
                                                </div>
                                            </div> 
                                        </li>
                                        <li class="feed-item">
                                            <div class="feed-item-list">
                                                <div>
                                                    <div class="date">10 Apr</div>
                                                    <p class="activity-text mb-0">Test #3</p>
                                                </div>
                                            </div> 
                                        </li>
                                        <li class="feed-item">
                                            <div class="feed-item-list">
                                                <div>
                                                    <div class="date">10 Apr</div>
                                                    <p class="activity-text mb-0">Test #4</p>
                                                </div>
                                            </div> 
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <!-- right offcanvas -->
                            <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasLeft2" aria-labelledby="offcanvasLeftLabel" aria-modal="true" role="dialog">
                                <div class="offcanvas-header">
                                    <h5 id="offcanvasLeftLabel">Driver Chat - {{$order->driver_name}} </h5>
                                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                                </div>
                                <div class="offcanvas-body"> 
                                    <ul class="list-unstyled activity-feed ms-1">
                                        <li class="feed-item">
                                            <div class="feed-item-list">
                                                <div>
                                                    <div class="date">10 Apr</div>
                                                    <p class="activity-text mb-0">Test #1</p>
                                                </div>
                                            </div> 
                                        </li>

                                        <li class="feed-item">
                                            <div class="feed-item-list">
                                                <div>
                                                    <div class="date">10 Apr</div>
                                                    <p class="activity-text mb-0">Test #2</p>
                                                </div>
                                            </div> 
                                        </li>
                                        <li class="feed-item">
                                            <div class="feed-item-list">
                                                <div>
                                                    <div class="date">10 Apr</div>
                                                    <p class="activity-text mb-0">Test #3</p>
                                                </div>
                                            </div> 
                                        </li>                                                                  
                                    </ul>
                                </div>
                            </div>  
                            <!-- bottom offcanvas -->
                            <div class="offcanvas offcanvas-bottom" tabindex="-1" id="offcanvas{{str_replace(',','',$order->order_id)}}" aria-labelledby="offcanvasLeftLabel" aria-modal="true" role="dialog">
                                <div class="offcanvas-header">
                                    <h5 id="offcanvasLeftLabel">Notes</h5>
                                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                                </div>
                                <div class="offcanvas-body"> 
                                <div class="row"> 
                                        <div class="col-6">Pharmacy
                                            <ul class="list-unstyled activity-feed ms-1">
                                                @if(!empty($order->special_instructions))
                                                <li class="feed-item">
                                                    <div class="feed-item-list">
                                                        <div>
                                                            <div class="date">-</div>
                                                            <p class="activity-text mb-0">{{$order->special_instructions}}</p>
                                                        </div>
                                                    </div> 
                                                </li>
                                                @endif
                                            </ul>
                                        </div>
                                        <div class="col-6">Dispatcher <a href="#" data-id="{{$order->order_id}}" class="btn btn-sm btn-primary ml-4 ajax-alert">Add Note</a>
                                            <ul class="list-unstyled activity-feed ms-1">
                                                @foreach(explode(';/',$order->notes_dispetch) as $note)
                                                @if(!empty($note) && strpos($note,'!-')!==FALSE)
                                                <li class="feed-item">
                                                    <div class="feed-item-list">
                                                        <div>
                                                            <div class="date">{{date('m/d/Y g:i A', strtotime(explode('!-',$note)[0]))}}</div>
                                                            <p class="activity-text mb-0">{{explode('!-',$note)[1]}}</p>
                                                        </div>
                                                    </div> 
                                                </li>
                                                @endif
                                                @endforeach                                                                
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 mt-4 mb-4 p-1" style="background: #dcefe3;">
                            <h4 class="text-center mt-2"><i class="mdi mdi-send-clock"></i> Time away:  <span class="badge bg-light">{{floor($order->eta / 60)}} hr {{$order->eta % 60}} min</span> <i class="mdi mdi-av-timer"></i> Delay <span class="badge bg-light">- 0 min</span> <i class="mdi mdi-cellphone-iphone"></i> Total calls <span class="badge bg-light">{{intval($order->count_call)}}</span></h4>                                                                             
                        </div>
                        <div class="col-6" style="text-align: left;">
                            <h6 class="float-left text-center mt-4">State:  <span class="badge bg-light">New York</span>  Local Time: <span class="badge bg-light">{{date('g:i A')}}</span></h6>                                    
                        </div>
                        <div class="col-6 float-right pt-3" style="text-align: right;">    
                            <form method="POST">
                                @csrf
                                <input type="hidden" name="skip" value="1">
                                <input type="hidden" name="order_id" value="{{$order->order_id}}">
                                <button type="submit" class="btn btn-danger waves-effect waves-light">Skip <i class="mdi mdi-skip-next-outline"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <div class="col-4">
            <div class="card">
                <div class="card-body" style="margin-bottom:10px;">
                    <div id="map" style="height: 450px;width: 100%;"></div>
                    <div class="loader">
                        <img src="https://i.pinimg.com/originals/67/2b/62/672b62d967f8d00d608d22f36c1831db.gif" alt="load">
                    </div>
                </div>
            </div>
        </div>                        
    </div>

    <div class="rightbar-overlay"></div> 
@endsection

@section('footerScript')
@php
    $initialOrder = $orders->first();
    $initialOrderId = $initialOrder ? str_replace(',', '', $initialOrder->order_id) : null;
@endphp
<script src="{{ URL::asset('/js/bootstrap.bundle.min.js')}}"></script>
<script src="{{ URL::asset('/js/bootstrap-datepicker.min.js')}}"></script>
<script src="{{ URL::asset('/js/select2.min.js')}}"></script>
<script src="{{ URL::asset('/js/jquery.bootstrap-touchspin.min.js')}}"></script>
<script src="{{ URL::asset('/js/jquery-ui.min.js')}}" type="text/javascript"></script>
<!-- Responsive Table js -->
<script src="{{ URL::asset('/libs/rwd-table/rwd-table.min.js')}}"></script>
<!-- Init js -->
<script src="{{ URL::asset('/js/pages/table-responsive.init.js')}}"></script>
<script src="/js/sweetalert2.min.js"></script>
<script src="/leaflet/leaflet.js"></script>
<script>
    var locationDrivers = {@foreach($orders as $order)
    @if(isset($locations[$order->driver_id]))
    "{{str_replace(',','',$order->order_id)}}":"{{ $locations[$order->driver_id]->location }}",
    @endif
    @endforeach};
    var locationPatients = {@foreach($orders as $order)
    "{{str_replace(',','',$order->order_id)}}":"{{ $order->patient_location }}",
    @endforeach};
    var markersArray = [];
    function initAll() {    
        var timer;
        $('.list-dispatch').bind('scroll',function () {
            if($(".loader").hasClass("show")===false){
                $(".loader").addClass("show");
            }
            clearTimeout(timer);
            timer = setTimeout( refresh , 50 );
        });
        var refresh = function () {
            $(".row-dispatch").each(function( index ) {
                var blockPosition = $(this).offset().top, 
                    windowScrollPosition = $('.list-dispatch').scrollTop();
                if( blockPosition>0 ) {
                    var order_id = $(this).data("id");
                    if (!locationPatients[order_id] || !locationDrivers[order_id]) {
                        $(".loader").removeClass("show");
                        return false;
                    }
                    if (marker) map.removeLayer(marker);
                    if (marker2) map.removeLayer(marker2);
                    marker = L.marker([parseFloat(locationPatients[order_id].split(",")[0]),parseFloat(locationPatients[order_id].split(",")[1])],{
                        icon: customIconUser("Patient"),
                    }).addTo(map);
                    marker2 = L.marker([parseFloat(locationDrivers[order_id].split(",")[0]),parseFloat(locationDrivers[order_id].split(",")[1])],{
                        icon: customIconDriver(),
                    }).addTo(map);
                    map.setView([parseFloat(locationDrivers[order_id].split(",")[0]),parseFloat(locationDrivers[order_id].split(",")[1])], 12);
                    setTimeout(function () {
                        window.dispatchEvent(new Event('resize'));
                    }, 300);
                    $(".loader").removeClass("show");
                    return false;
                }
            });
        };
    };
    const initialOrderId = @json($initialOrderId);
    $(document).ready(function() {
        if (initialOrderId && locationDrivers[initialOrderId] && locationPatients[initialOrderId]) {
            initMap(initialOrderId);
        }
    });
    const here = {
        apiKey:"{{config('app.hereApiKey')}}"
    }
    const style = 'normal.day';
    const hereTileUrl = `https://maps.hereapi.com/v3/base/mc/{z}/{x}/{y}/png?apiKey=${here.apiKey}&ppi=400`;
    var map,marker,marker2;
    function initMap(order_id) {
        if (!order_id || !locationDrivers[order_id] || !locationPatients[order_id]) {
            return;
        }
        if(map!==undefined){
            map.remove();
        }
        map = L.map(document.getElementById("map")).setView([parseFloat(locationDrivers[order_id].split(",")[0]),parseFloat(locationDrivers[order_id].split(",")[1])], 12);
        L.tileLayer(hereTileUrl).addTo(map);
        marker = L.marker([parseFloat(locationPatients[order_id].split(",")[0]),parseFloat(locationPatients[order_id].split(",")[1])],{
            icon: customIconUser("Patient"),
        }).addTo(map);
        marker2 = L.marker([parseFloat(locationDrivers[order_id].split(",")[0]),parseFloat(locationDrivers[order_id].split(",")[1])],{
            icon: customIconDriver(),
        }).addTo(map);
        setTimeout(function () {
            window.dispatchEvent(new Event('resize'));
        }, 300);
    }
    function customIconUser(text='') {
        return L.divIcon({
            iconSize: "auto",
            html:'<div style="width:80px;height:80px;position:relative;margin-left: -50%;margin-top: -50%;"><img alt="" src="/images/i033.svg" draggable="false" style="width:80px;height:80px;"><div style="position: absolute;top: calc(100% - 19px);"><div style="display: block;width: 80px;text-align: center;"><div class="" aria-hidden="true" style="color: rgb(0, 0, 0);line-height: 20px; font-size: 16px; font-family: Arial;">'+text+'</div></div></div></div>'
        });
    }
    function customIconDriver() {
        return L.divIcon({
            iconSize: "auto",
            html:'<div style="width:50px;height:50px;position:relative;margin-left: -50%;margin-top: -50%;"><img alt="" src="/images/i022.svg" draggable="false" style="width:50px;height:50px;"></div>'
        });
    }
</script>
@endsection
