@extends('layouts.master')

@section('title') Dispatching Driver @endsection
@section('headerCss')
<link rel="stylesheet" href="{{ URL::asset('/css/bootstrap-multiselect.min.css')}}" type="text/css"/>
<link rel="stylesheet" href="{{ URL::asset('/css/bootstrap-lib.min.css')}}">
<link href="{{ URL::asset('/css/bootstrap-datepicker.min.css')}}" rel="stylesheet">
<link href="{{ URL::asset('/css/select2.min.css')}}" rel="stylesheet" type="text/css">
<link href="{{ URL::asset('/css/jquery.bootstrap-touchspin.min.css')}}" rel="stylesheet">
<style>
.wrapper {
    display: flex;
    flex-wrap: wrap;
    max-height: 310px;
    overflow-y: auto;
    overflow-x: hidden;
    float:left;
    margin-bottom: 30px;
}
.wrapper::-webkit-scrollbar {
  width: 1px;
}
.wrapper::-webkit-scrollbar-track {
  background: #f1f1f1; 
}
/* Handle */
.wrapper::-webkit-scrollbar-thumb {
  background: #888; 
}
/* Handle on hover */
.wrapper::-webkit-scrollbar-thumb:hover {
  background: #555; 
}
.wrapper.active {
    border: 4px dashed;
    border-radius: 15px;
    opacity: 0.5;
}
.trash_block {
    position: absolute;
    right: 20px;
    font-size: 30px;
    border: 4px dashed;
    border-radius: 15px;
    opacity: 0.5;
    width: 200px;
    height: 64px;
    text-align: center;
    padding: 7px;
   /*  display: none; */
}
.trash_block.active {
    display: block;
}
.trash_block.focuse {
    opacity: 1;
}
.drag-text {
    display:none;
    position: absolute;
    text-align: center;
    width: 100%;
    top: 46%;
}
.wrapper.active .drag-text {
    display:block;
}
#page-topbar {
    z-index: 10 !important;
}
.plus-item {
    cursor:move;
    padding: 2px 0px;
    height:60px;
    width:115px;
    border: 5px solid #28c8e2;
    border-radius: 12px;
    opacity: 0.4;
    display: inline-table;
    margin-bottom: 10px;
    margin-right: 7px;
}
.wrapper.active .plus-item {
    opacity: 0.3 !important;
}
.plus-item:hover {
    opacity: 1;
}
.plus-item .number {
    color: #28c8e2;
    font-size: 37px;
    margin: 0;
    text-align: center;
}
.plus-item p {
    line-height: 1;
    margin-bottom: 6px;
}
.plus-item.active-green {
    border: 5px solid #19b313;
    background: #19b313;
    padding: 0;
    padding-top: 3px;
    opacity: 1;
    color:#fff;
    text-align: center;
}
.plus-item.active-blue {
    border: 5px solid #0e46b7;
    background: #0e46b7;
    opacity: 1;
    padding: 0;
    padding-top: 3px;
    color:#fff;
    text-align: center;
}
.plus-item.active-yellow {
    border: 5px solid #eca40d;
    background: #eca40d;
    opacity: 1;
    padding-top: 3px;
    color:#fff;
    text-align: center;
}
.patient_block {
    margin-top: 10px;
    margin-left: 5px;
    z-index: 10000;
    cursor: move;
    width: 100%;
    height: 65px;
    border-radius: 6px;
    background-color: #c0ecff;
    color: #0b4f8a;
    padding: 5px 5px;
    font-size: 12px;
    border: 1px solid #3c9baf;
    margin-bottom: 5px;
    -webkit-touch-callout: none; /* iOS Safari */
    -webkit-user-select: none;   /* Chrome/Safari/Opera */
    -khtml-user-select: none;    /* Konqueror */
    -moz-user-select: none;      /* Firefox */
    -ms-user-select: none;       /* Internet Explorer/Edge */
    user-select: none;           /* Non-prefixed version, currently*/
}
.patient_block.hover{
    padding: 0px 15px;
    border: 5px solid #73dff5;
    box-shadow: 0 2px 8px rgba(0, 0, 0, .3), -23px 0 22px -23px rgba(0, 0, 0, .8), 25px 0 23px -23px rgba(0, 0, 0, .8), 0 0 40px rgba(0, 0, 0, .1) inset;
}
.patient_block .left {
    float: left;
    width:66%;
}
.patient_block .right {
    float: right;
    text-align: right;
}
.pharmacie_block {
    margin-top: 10px;
    margin-left: 5px;
    z-index: 10000;
    cursor: move;
    width: 100%;
    height: 45px;
    border-radius: 6px;
    background-color: #dfdaff;
    color: #006311;
    padding: 5px 5px;
    font-size: 12px;
    border: 1px solid #7a6fbe;
    margin-bottom: 5px;
    -webkit-touch-callout: none; /* iOS Safari */
    -webkit-user-select: none;   /* Chrome/Safari/Opera */
    -khtml-user-select: none;    /* Konqueror */
    -moz-user-select: none;      /* Firefox */
    -ms-user-select: none;       /* Internet Explorer/Edge */
    user-select: none;           /* Non-prefixed version, currently*/
}
.pharmacie_block.hover{
    padding: 0px 15px;
    border: 5px solid #8bf9a6;
    box-shadow: 0 2px 8px rgba(0, 0, 0, .3), -23px 0 22px -23px rgba(0, 0, 0, .8), 25px 0 23px -23px rgba(0, 0, 0, .8), 0 0 40px rgba(0, 0, 0, .1) inset;
}
.pharmacie_block .left {
    float: left;
}
.pharmacie_block .right {
    float: right;
    text-align: right;
}
.card_height {
    height: 252px;
    overflow: auto;
    padding-right: 10px;
}
.card_height::-webkit-scrollbar-track
{
	-webkit-box-shadow: inset 0 0 6px rgba(0,0,0,0.3);
	border-radius: 10px;
	background-color: #F5F5F5;
}

.card_height::-webkit-scrollbar
{
	width: 4px;
	background-color: #F5F5F5;
}

.card_height::-webkit-scrollbar-thumb
{
	border-radius: 10px;
	-webkit-box-shadow: inset 0 0 6px rgba(0,0,0,.3);
	background-color: #7a6fbe;
}
.office_block{
    cursor: move;
    position: absolute;
    width: 150px;
    right: 20px;
    bottom: 29px;
    height: 40px;
    border-radius: 6px;
    background-color: #f8fbc8;
    color: #949602;
    padding: 0px 15px;
    font-size: 14px;
    border: 1px solid #e1e27b;
    -webkit-touch-callout: none; /* iOS Safari */
    -webkit-user-select: none;   /* Chrome/Safari/Opera */
    -khtml-user-select: none;    /* Konqueror */
    -moz-user-select: none;      /* Firefox */
    -ms-user-select: none;       /* Internet Explorer/Edge */
    user-select: none;           /* Non-prefixed version, currently*/
}
.office_block.hover{
    border: 5px solid #e1e27b;
    box-shadow: 0 2px 8px rgba(0, 0, 0, .3), -23px 0 22px -23px rgba(0, 0, 0, .8), 25px 0 23px -23px rgba(0, 0, 0, .8), 0 0 40px rgba(0, 0, 0, .1) inset;
}
.office_block .left {
    float: left;
    padding-top: 10px;
}
.office_block .right {
    padding-top:5px;
    float: right;
}
.office_block.focus {
    box-shadow: 0 0 5pt 4pt #58db83;
}
.pharmacie_block.focus {
    box-shadow: 0 0 5pt 4pt #58db83;
}
.patient_block.focus {
    box-shadow: 0 0 5pt 4pt #58db83;
}
.plus-item.focus {
    box-shadow: 0 0 5pt 4pt #58db83;
}
.patient_block.disabled {
    opacity: 0.65;
    cursor: no-drop;
}
.patient_block.disabled .order_id::after {
    content: "Return";
    background-color: yellow;
    color: red;
    margin: 0 5px;
    padding: 0 5px;
    font-weight: 500;
}
.form-check-input{
    margin-top: 0px !important;
}
.multiselect-native-select>.btn-group {
    width: 45px;
    height: 20px;
}
.multiselect-native-select>.btn-group>button {
    padding-left: 4px;
    height: inherit;
    background-position-x: right;
    overflow: hidden;
}
.multiselect-selected-text {
    line-height: 1px;
}
.btn-check:active+.btn-outline-primary, .btn-check:checked+.btn-outline-primary, .btn-outline-primary.active, .btn-outline-primary.dropdown-toggle.show, .btn-outline-primary:active {
    color: #fff;
    background-color: #7a6fbe;
    border-color: #7a6fbe;
}
.btn-check {
    position: absolute;
    clip: rect(0,0,0,0);
    pointer-events: none;
}
.btn-check+.btn {
    margin-right:15px !important;
}
label {
    margin:0px !important;
}
.tariff {
    display:flex;
    width: max-content;
    margin-bottom:1rem;
}
.tariff .input-group {
    width: 25%;
}
.filter_fix {
    position: fixed;
    left: 0px;
    z-index: 1000;
    bottom: 120px;
    border-top-left-radius: 0px !important;
    border-bottom-left-radius: 0px !important;
    padding: 12px !important;
    font-size: 15px !important;
    font-weight: 500 !important;
}
.multiselect-container {
    height: 300px;
    overflow: auto;

}
.multiselect-container::-webkit-scrollbar {
  width: 1px;
}
.multiselect-container::-webkit-scrollbar-track {
  background: #f1f1f1; 
}
/* Handle */
.multiselect-container::-webkit-scrollbar-thumb {
  background: #888; 
}
/* Handle on hover */
.multiselect-container::-webkit-scrollbar-thumb:hover {
  background: #555; 
}
</style>
@endsection
@section('content')
 <!-- start page title -->
                    @if(\Session::has('success'))
                        <div class="alert alert-success">
                            {!! \Session::get('success') !!}
                        </div>
                    @endif
                    @if(\Session::has('error'))
                        <div class="alert alert-danger">
                            {!! \Session::get('error') !!}
                        </div>
                    @endif
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body" style="min-height: 320px;">
                                    <h4 style="display: inline-block;margin-right:15px;">Driver ID#{{$driver->id}}</h4>
                                    @if($driver->isblocked == 1)
                                        <button class="btn btn-danger" style="line-height: 0.9;vertical-align:top;">Blocked</button>
                                    @elseif($driver->isactive == 0)
                                        <button class="btn btn-warning" style="line-height: 0.9;vertical-align:top;">No active</button>
                                    @else 
                                        <button class="btn btn-success" style="line-height: 0.9;vertical-align:top;">Active</button>
                                    @endif                                    
                                    <button type="button" class="btn header-item waves-effect" style="float:right;height:auto;padding:0;">
                                            <img class="rounded-circle header-profile-user" src="{{$driver->image}}" alt="Header Avatar">
                                    </button>
                                    <p>{{$driver->name}} {{$driver->last_name}}    {{$driver->phone}}    {{$driver->email}}</p>
                                    <form method="POST" id="eta_calculate" class="d-none">
                                        @csrf
                                        <input type="hidden" name="eta_calculate" value="1">
                                    </form>
                                    <button type="button" class="btn btn-outline-secondary mt-2 mb-2" onclick="if(confirm('Are you sure?')){$('#eta_calculate').submit();}">Refresh ETA</button>
                                    <div class="mt-2">
                                        <h4>Process Progress</h4>
                                        <p>Distance: {{$driver->distance}} miles</p>
                                        <p>Duration: {{floor($driver->eta / 60)}} hr {{$driver->eta % 60}} min</p>
                                        @for($n=0;$n<300;$n++)
                                            @if(!empty($routes_priority[$n]))
                                                @if($routes_priority[$n]->type=='pharmacy')
                                                    @php
                                                        array_push($show_ids,"pharmacy".$routes_priority[$n]->type_id);
                                                        $show_priority["pharmacy".$routes_priority[$n]->type_id]=$n;
                                                    @endphp
                                                @endif
                                                @if($routes_priority[$n]->type=='patient')
                                                    @php
                                                        array_push($show_ids,"patient".$routes_priority[$n]->type_id);
                                                        $show_priority["patient".$routes_priority[$n]->type_id]=$n;
                                                    @endphp
                                                @endif
                                            @endif
                                        @endfor
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5>Patients</h5>
                                    <div id="patients_list" class="card_height">
                                    @foreach($orders as $order)
                                        <div class="patient_block patient{{$order->user_id}}">
                                            <div class="left">
                                                <b class="order_id" data-id="{{$order->id}}" data-type="patient">Order #{{$order->id}}</b><br>
                                                <span class="type_id"  data-latlng="{{$order->userlocation}}" data-id="{{$order->user_id}}" style="display: block;">{{ $order->username }} {{ $order->userlast_name }}, {{ $order->userphone }}<br>{{ $order->useraddress }}</span>
                                            </div>
                                            <div class="right mt-2">
                                                <button class="btn btn-primary btn-sm waves-effect call_patient" data-phone="{{ $order->userphone }}">Call <i class="ti-headphone-alt"></i></button>
                                            </div>
                                        </div>
                                    @endforeach
                                    </div> 
                                </div>
                            </div>
                        </div>
                        <!-- end col -->
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4>Map</h4>
                                    <div id="map" style="height: 600px;width: 100%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
@endsection
@section('footerScript')
<script src="{{ URL::asset('/js/bootstrap.bundle.min.js')}}"></script>
<script src="{{ URL::asset('/js/bootstrap-datepicker.min.js')}}"></script>
<script src="{{ URL::asset('/js/select2.min.js')}}"></script>
<script src="{{ URL::asset('/js/jquery.bootstrap-touchspin.min.js')}}"></script>
<script src="{{ URL::asset('/js/jquery-ui.min.js')}}" type="text/javascript"></script>
<script type="text/javascript" src="{{ URL::asset('/js/bootstrap-multiselect.min.js')}}"></script>
                    <script>
                        var setup_route = false;
                        var setup_marker;
                        var showPharmacy = true;
                        var showPatient = true;
                        $(".call_patient").on("click",function(){
                            var phone = $(this).data("phone").replace("(","").replace(")","").replace(" ","").replace("-","");
                            zdrmWebrtcPhone.setCallingNumber(phone);
                            $(".zdrm-webphone-call-btn").get(0).click();
                        });
                    </script>
                    <script src="https://maps.googleapis.com/maps/api/js?key={{config('app.googlemaps_apikey')}}&region=US&language=en&libraries=drawing,geometry&v=weekly&callback=initMap" defer></script>
                    <script>
                        var map;
                        var _myPolygon;
                        var markersArray = [];
                        var locationDriver = "{{ $locations->location }}";
                        var locationPatients = [@foreach($patients_locations as $patients_location)
                        @if(!empty($patients_location['location']))
                            @if((!empty($show_ids) && in_array("patient".$patients_location['id'],$show_ids)))
                            "{{ $patients_location['location'] }}",
                            @endif
                        @endif
                        @endforeach];
                        var locationPharmacy = [@foreach($pharmacy_locations as $pharmacy_location)
                        @if(!empty($pharmacy_location['location']))
                            @if((!empty($show_ids) && in_array("pharmacy".$pharmacy_location['id'],$show_ids)))
                            "{{ $pharmacy_location['location'] }}",
                            @endif
                        @endif
                        @endforeach];
                        var locationPatientsID = [@foreach($patients_locations as $patients_location)
                        @if(!empty($patients_location['id']))
                            @if(!empty($show_ids) && in_array("patient".$patients_location['id'],$show_ids))
                                @if(isset($show_priority['patient'.$patients_location['id']]))
                                "{{ $patients_location['id'] }};{{ $show_priority['patient'.$patients_location['id']]+1 }}",
                                @else
                                "0",
                                @endif
                            @endif
                        @endif
                        @endforeach];
                        var locationPharmacyID = [@foreach($pharmacy_locations as $pharmacy_location)
                        @if(!empty($pharmacy_location['id']))
                            @if(!empty($show_ids) && in_array("pharmacy".$pharmacy_location['id'],$show_ids))
                                @if(isset($show_priority['pharmacy'.$pharmacy_location['id']]))
                                "{{ $pharmacy_location['id'] }};{{ $show_priority['pharmacy'.$pharmacy_location['id']]+1 }}",
                                @else
                                "0",
                                @endif
                            @endif
                        @endif
                        @endforeach];
                        function addMarker(latlng, text, color, background, id, map, weight="bold") {
                            var latLng = new google.maps.LatLng(parseFloat(latlng.split(",")[0]), parseFloat(latlng.split(",")[1]));
                            if(markersArray.length != 0) {
                                for (i=0; i < markersArray.length; i++) {
                                    var existingMarker = markersArray[i];
                                    var pos = existingMarker.getPosition();
                                    if (latLng.equals(pos)) {
                                        var a = 360.0 / markersArray.length;
                                        var newLat = pos.lat() + -.00005 * Math.cos((+a*i) / 180 * Math.PI);  //x
                                        var newLng = pos.lng() + -.00005 * Math.sin((+a*i) / 180 * Math.PI);  //Y
                                        latLng = new google.maps.LatLng(newLat,newLng);
                                    }
                                }
                            }
                            const marker = new google.maps.Marker({
                                position: latLng,
                                icon: {
                                    url: '/images/i033.svg', // url
                                    scaledSize: new google.maps.Size(35, 35), // scaled size
                                    origin: new google.maps.Point(0,0), // origin
                                    anchor: new google.maps.Point(30, 30), // anchor
                                    labelOrigin: new google.maps.Point(18, 31)
                                },
                                label: {
                                    text:text,
                                    fontWeight: weight,
                                    fontSize: '8px',
                                    color: color
                                },
                                title: id,
                                id: id,
                                map: map,
                            });
                            markersArray.push(marker);
                            attachSecretMessage(marker);
                        }
                        function addMarkerDriver(latlng, map) {
                            const marker = new google.maps.Marker({
                                position: { lat: parseFloat(latlng.split(",")[0]), lng: parseFloat(latlng.split(",")[1]) },
                                icon: {
                                    url: '/images/i022.svg', // url
                                    scaledSize: new google.maps.Size(46, 46), // scaled size
                                    origin: new google.maps.Point(0,0), // origin
                                    anchor: new google.maps.Point(23, 23) // anchor
                                },
                                map: map,
                            });
                            attachSecretMessage(marker);
                        }
                        function addMarkerOffice(latlng, text, color, background, id, map, weight="bold") {
                            const marker = new google.maps.Marker({
                                position: { lat: parseFloat(latlng.split(",")[0]), lng: parseFloat(latlng.split(",")[1]) },
                                icon: {
                                    url: '/images/i044.svg', // url
                                    scaledSize: new google.maps.Size(30, 30), // scaled size
                                    origin: new google.maps.Point(0,0), // origin
                                    anchor: new google.maps.Point(30, 30) // anchor
                                },
                                title: id,
                                id: id,
                                map: map,
                            });
                            attachSecretMessage(marker);
                        }
                        function addMarkerPharmacy(latlng, text, color, background, id, map, weight="bold") {
                            const marker = new google.maps.Marker({
                                position: { lat: parseFloat(latlng.split(",")[0]), lng: parseFloat(latlng.split(",")[1]) },
                                icon: {
                                    url: '/images/i011.svg', // url
                                    scaledSize: new google.maps.Size(35, 35), // scaled size
                                    origin: new google.maps.Point(0,0), // origin
                                    anchor: new google.maps.Point(20, 20), // anchor
                                    labelOrigin: new google.maps.Point(18, 31)
                                },
                                label: {
                                    text:text,
                                    fontWeight: weight,
                                    fontSize: '8px',
                                    color: color
                                },
                                title: id,
                                id: id,
                                map: map,
                            });
                            markersArray.push(marker);
                            attachSecretMessage(marker);
                        }
                        function initMap() {
                            map = new google.maps.Map(document.getElementById("map"), {
                                zoom: 11,
                                center: { lat: parseFloat(locationDriver.split(",")[0]), lng: parseFloat(locationDriver.split(",")[1]) },
                                legend: 'none'
                            });

                            addMarkerDriver(locationDriver,map);
                            if(showPatient) {
                                for(n=0;n<locationPatients.length;n++) {
                                    if(locationPatientsID[n].toString().search(";")>-1) {
                                        var id2 = locationPatientsID[n].toString().split(";")[0];
                                        var prior2 = locationPatientsID[n].toString().split(";")[1];
                                        addMarker(locationPatients[n],prior2,"#000","#0e46b7","patient"+id2,map,"bold");
                                    } else {
                                        var id2 = locationPatientsID[n].toString().split(";")[0];
                                        addMarker(locationPatients[n],id2,"#000","#0e46b7","patient"+id2,map,"bold");
                                    }
                                }
                            }
                            if(showPharmacy) {
                                for(n=0;n<locationPharmacy.length;n++) {
                                    if(locationPharmacyID[n].toString().search(";")>-1) {
                                        var id2 = locationPharmacyID[n].toString().split(";")[0];
                                        var prior2 = locationPharmacyID[n].toString().split(";")[1];
                                        addMarkerPharmacy(locationPharmacy[n],prior2,"#fff","#19b313","pharmacy"+id2,map,"bold");
                                    } else {
                                        var id2 = locationPharmacyID[n].toString().split(";")[0];
                                        addMarkerPharmacy(locationPharmacy[n],id2,"#fff","#19b313","pharmacy"+id2,map,"bold");
                                    }
                                }
                            }
                        }
                        function attachSecretMessage(marker) {
                            marker.addListener("click", () => {
                                var id = marker.get('id');
                                if(id!="") {
                                    if(setup_route==true) {
                                        $('.modal2').find('input[name="id"]').val(id);
                                        if($('.wrapper .plus-item').index($('.wrapper').find("."+id))<0) {
                                            $('.modal2').find('input[name="priority"]').val(1);
                                        } else {
                                            $('.modal2').find('input[name="priority"]').val($('.wrapper .plus-item').index($('.wrapper').find("."+id))+1);
                                        }
                                        setup_marker = marker;
                                        $('.modal2').fadeIn(300);
                                    } else {
                                        $(".office_block").removeClass("focus");
                                        $(".pharmacie_block").removeClass("focus");
                                        $(".patient_block").removeClass("focus");
                                        $(".plus-item").removeClass("focus");
                                        $("."+id).addClass("focus");
                                        if(id.indexOf('patient')>-1) {
                                            if($('.wrapper').find("."+id).length) {
                                                $('.wrapper').animate({ scrollTop: $('.wrapper').find("."+id).offset().top-580 }, 500);
                                            } else {
                                                $('#patients_list').animate({ scrollTop: $("."+id).offset().top-320 }, 500);
                                            }
                                        }
                                        if(id.indexOf('pharmacy')>-1) {
                                            if($('.wrapper').find("."+id).length) {
                                                $('.wrapper').animate({ scrollTop: $('.wrapper').find("."+id).offset().top-580 }, 500);
                                            } else {
                                                $('#pharmacies_list').animate({ scrollTop: $("."+id).offset().top-320 }, 500);
                                            }
                                        }
                                        $('html, body').animate({ scrollTop: 0 }, 400);
                                    }
                                }
                            });
                        }
                    </script>           
@endsection