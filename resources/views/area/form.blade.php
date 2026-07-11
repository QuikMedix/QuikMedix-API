@extends('layouts.master')

@section('title') {{$title}} @endsection

@section('headerCss')
    <!-- Responsive Table css -->
    <link href="{{ URL::asset('/libs/rwd-table/rwd-table.min.css')}}" rel="stylesheet" type="text/css" /> <!-- Bootstrap Css -->
    <style>
        #add_rx {
            display: flex;
            cursor:pointer;
        }
        .rx-field {
            margin-bottom:10px;
        }
        .remove-rx {
            color:red;
            font-size:20px;
            cursor:pointer;
            margin-top: 7px;
        }
        .gm-style-iw .gm-ui-hover-effect {display: none !important;}
    </style>
@endsection
@section('content')
 <!-- start page title -->

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <form method="post" enctype="multipart/form-data">
                                        @csrf
                                        <input type="hidden" name="save" value="1">
                                        @if($alert!='') 
                                            <div class="alert alert-danger" role="alert">{{ $alert }}</div>
                                        @endif
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Name Area</label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" required name="name" value="{{$area->name}}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">State Area</label>
                                            <div class="col-sm-10">
                                                <select class="select2 form-control" required id="state" name="state">
                                                    <option value="">Select State</option>
                                                    @foreach($states_list as $state)
                                                    @if($area->state==$state->name)
                                                    <option selected value="{{$state->name}}">{{$state->name}}</option>
                                                    @else
                                                    <option value="{{$state->name}}">{{$state->name}}</option>
                                                    @endif
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <input type="text" style="display:none;" required name="polygon" id="polygon" value="{{$polygon}}">
                                        <div id="map" style="height: 600px;width: 100%;"></div>
                                        <button type="submit" style="margin-top:10px;" class="btn btn-primary">Save</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- end col -->
                    </div>
                    <!-- end row -->

@endsection

@section('footerScript')
<script src="https://maps.googleapis.com/maps/api/js?key={{config('app.googlemaps_apikey')}}&region=US&language=en&libraries=drawing,geometry,geocoder&v=weekly&callback=initMap"></script>
<script type="text/javascript" src="{{ URL::asset('/js/maplabel.js') }}"></script>
<script>
    $(document).ready(function(){
        $('.select2').selectize();
    });
    $("#state").on("change",function(){
        var geocoder = new google.maps.Geocoder();
        geocoder.geocode({'address': $("#state").val()+", USA"}, function(results, status) {
            if (status === 'OK') {
                map.setCenter(results[0].geometry.viewport.getCenter());
            } else {
                alert('Geocode was not successful for the following reason: ' + status);
            }
        });
    });
    var map;
    var _myPolygon;
    function initMap() {
        map = new google.maps.Map(document.getElementById("map"), {
            zoom: 10,
            @if(empty($area->state))
            center: {lat: 40.743798988555,lng: -74.023925802166005},
            @else
            center: {lat: 0,lng: 0},
            @endif
            legend: 'none'
        });
        google.maps.event.addListener(map, 'click', function(event){
            if(event.placeId !== undefined) {
                event.stop();
            }
        });
        @if(empty($polygon))
        const drawingManager = new google.maps.drawing.DrawingManager({
            drawingControl: true,
            drawingControlOptions: {
                position: google.maps.ControlPosition.TOP_CENTER,
                drawingModes: [google.maps.drawing.OverlayType.POLYGON],
            },
            polygonOptions: {
                strokeColor: '#FF0000',
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: '#FF0000',
                fillOpacity: 0.35
            }
        });
        drawingManager.setMap(map);
        google.maps.event.addListener(drawingManager, 'overlaycomplete', function(e) {
            _myPolygon = e.overlay;
        });
        google.maps.event.addListener(drawingManager,'polygoncomplete',function(polygon) {
            var coord = [];
            for (var i = 0; i < polygon.getPath().getLength(); i++) {
                coord.push(polygon.getPath().getAt(i).toJSON());
            }
            $("#polygon").val(JSON.stringify(coord));
        });
        @endif
        @if(!empty($polygon))
        drawPolygon(JSON.parse('{!!$polygon!!}'),'{{$area->name}}',true);
        google.maps.event.addListener(bermudaTriangle.getPath(), 'insert_at', function(index, obj) {
            saveCoord();
        });
        google.maps.event.addListener(bermudaTriangle.getPath(), 'set_at', function(index, obj) {
            saveCoord();
        });
        function saveCoord(){
            var coord = [];
            for (var i = 0; i < bermudaTriangle.getPath().getLength(); i++) {
                coord.push(bermudaTriangle.getPath().getAt(i).toJSON());
            }
            $("#polygon").val(JSON.stringify(coord));
        }
        @endif
        @if(!empty($area->state))
        var geocoder = new google.maps.Geocoder();
        geocoder.geocode({'address': "{{$area->state}}, USA"}, function(results, status) {
            if (status === 'OK') {
                map.setCenter(results[0].geometry.location);
            } else {
                alert('Geocode was not successful for the following reason: ' + status);
            }
        });
        @endif
        @if(!empty($polygons))
        var polygons=JSON.parse('[@foreach($polygons as $key=>$pol) {"name":"{{$pol->name}}","coord":{!!$pol->polygon!!} }@if($key<count($polygons)-1){{","}}@endif @endforeach]');
        for(let i = 0; i < polygons.length; i++) {
            drawPolygon(polygons[i].coord,polygons[i].name);
        }
        @endif
        google.maps.event.addListener(map, "idle", function(){
            google.maps.event.trigger(map, 'resize');
        });
    }
    function drawPolygon(polygon,name,index=false) {
        var polygonIndex=polygon;
        var points=[];
        for(var i=0; i<polygonIndex.length; i++) { 
            points.push(polygonIndex[i]);
        }
        if(index===true){
            bermudaTriangle = new google.maps.Polygon({
                paths: polygonIndex,
                strokeColor: '#FF0000',
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: '#FF0000',
                fillOpacity: 0.35,
                editable: true
            });
            var mapLabel = new MapLabel({
                text: name,
                position: bermudaTriangle.getPath().getArray().reduce((prev, curr) => prev.extend(curr), new google.maps.LatLngBounds()).getCenter(),
                map: map,
                fontSize: 19,
                align: 'center',
                fontColor: '#fff',
                strokeColor: '#000',
                zIndex: 102
            });
            mapLabel.set('position', bermudaTriangle.getPath().getArray().reduce((prev, curr) => prev.extend(curr), new google.maps.LatLngBounds()).getCenter());
            bermudaTriangle.setMap(map);
        } else {
            var polygon2 = new google.maps.Polygon({
                paths: polygonIndex,
                strokeColor: '#000',
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: '#000',
                fillOpacity: 0.35
            });
            var mapLabel = new MapLabel({
                text: name,
                position: polygon2.getPath().getArray().reduce((prev, curr) => prev.extend(curr), new google.maps.LatLngBounds()).getCenter(),
                map: map,
                fontSize: 19,
                align: 'center',
                fontColor: '#fff',
                strokeColor: '#000',
                zIndex: 102
            });
            mapLabel.set('position', polygon2.getPath().getArray().reduce((prev, curr) => prev.extend(curr), new google.maps.LatLngBounds()).getCenter());
            polygon2.setMap(map);
        }
    }
</script>
@endsection