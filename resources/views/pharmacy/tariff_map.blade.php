@extends('layouts.master')

@section('title') Tariff Map @endsection

@section('headerCss')
<link href="/leaflet/leaflet.css" rel="stylesheet" type="text/css">
<style>
    .bg_tariff {
        transform: none !important;
        z-index: 0 !important;
    }
    .leaflet-map-pane svg {
        z-index: 1 !important;
    }
</style>
@endsection

@section('content')
 <!-- start page title -->
                    
                    <div class="row mb-4">
                        <div class="col-12">
                            <div  class="card-title-5">
                                <div class="row">
                                    <div class="col-8">
                                        <h4 class="mt-2"><i class="mdi mdi-medical-bag"></i> {{$pharmacy->name}} - <i class="mdi mdi-google-maps"></i> {{$pharmacy->address}}</h4> 
                                    </div> 
                                    <div class="col-4 text-right">
                                    @if((Auth::user()->role == 'superadmin' || Auth::user()->role == 'admin' || Auth::user()->role == 'dispadmin'))
                                        <a href="/billing/{{ $pharmacy->id }}"><button type="button" class="btn btn-outline-dark waves-effect waves-light">Billing</button></a>
                                        <a href="/pharmacys/edit/{{ $pharmacy->id }}"><button type="button" class="btn btn-outline-dark waves-effect waves-light">Edit</button></a>                    
                                    @endif   
                                    </div>  
                                </div>                                            
                            </div>
                        </div>    
                    </div>
                  
                    
                    <!-- end page title -->

                    <div class="row">
                        <div class="col-xl-12 col-sm-12">
                            <div class="card">
                                <div class="card-body">
                                    <form method="GET" class="mb-2">
                                        <label for="time_delivery">Time Delivery</label>
                                        <select name="time_delivery" id="time_delivery" class="form-control">
                                            <option value="1" @if(isset($_GET["time_delivery"]) && $_GET["time_delivery"]=='1'){{"selected"}}@endif>Next day delivery</option>
                                            <option value="2" @if(isset($_GET["time_delivery"]) && $_GET["time_delivery"]=='2'){{"selected"}}@endif>Same day delivery</option>
                                            <option value="3" @if(isset($_GET["time_delivery"]) && $_GET["time_delivery"]=='3'){{"selected"}}@endif>ASAP Delivery</option>
                                            <option value="4" @if(isset($_GET["time_delivery"]) && $_GET["time_delivery"]=='4'){{"selected"}}@endif>After Hours</option>
                                        </select>
                                    </form>
                                    <div id="map" style="height: 600px;width: 100%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- end row -->  
@endsection

@section('footerScript')
<script src="/leaflet/leaflet.js"></script>
<script>
    var map;
    var tariff = {
        "tariff_default":@if(!empty($pharmacy->tariff)){{number_format($pharmacy->tariff,2)}}@else{{number_format($pharmacy_plan->tariff,2)}}@endif,
        "tariff_next_day":@if(!empty($pharmacy->tariff_next_day)){{number_format($pharmacy->tariff_next_day,2)}}@else{{number_format($pharmacy_plan->tariff_next_day,2)}}@endif,
        "tariff_same_day":@if(!empty($pharmacy->tariff_same_day)){{number_format($pharmacy->tariff_same_day,2)}}@else{{number_format($pharmacy_plan->tariff_same_day,2)}}@endif,
        "tariff_asap":@if(!empty($pharmacy->tariff_asap)){{number_format($pharmacy->tariff_asap,2)}}@else{{number_format($pharmacy_plan->tariff_asap,2)}}@endif,
        "tariff_after_hours":@if(!empty($pharmacy->tariff_after_hours)){{number_format($pharmacy->tariff_after_hours,2)}}@else{{number_format($pharmacy_plan->tariff_after_hours,2)}}@endif,
        "tariff_area2":@if(!empty($pharmacy->tariff_area2)){{number_format($pharmacy->tariff_area2,2)}}@else{{number_format($pharmacy_plan->tariff_area2,2)}}@endif,
        "tariff_area3":@if(!empty($pharmacy->tariff_area3)){{number_format($pharmacy->tariff_area3,2)}}@else{{number_format($pharmacy_plan->tariff_area3,2)}}@endif,
        "tariff_area_more":@if(!empty($pharmacy->tariff_area_more)){{number_format($pharmacy->tariff_area_more,2)}}@else{{number_format($pharmacy_plan->tariff_area_more,2)}}@endif
    };
    var time_delivery = @if(isset($_GET["time_delivery"])) '{{$_GET["time_delivery"]}}' @else '1' @endif;
    var locationPharmasy = "{{ $pharmacy->location }}".split(',');
    var _myPolygon;
    var polygonsList=[];
    var labelsLoc=[];
    var kof = 0.05;
    @if(!empty($polygons))
    var polygons=JSON.parse('[@foreach($polygons as $key=>$pol) {"name":"{{$pol->name}}","type":"{{$pol->type}}","coord":{!!$pol->polygon!!} }@if($key<count($polygons)-1){{","}}@endif @endforeach]');
    @endif
    const here = {
        apiKey:"{{config('app.hereApiKey')}}"
    }
    const style = 'normal.day';
    const hereTileUrl = `https://maps.hereapi.com/v3/base/mc/{z}/{x}/{y}/png?apiKey=${here.apiKey}&ppi=400`;
    var map2;
    $(document).ready(function(){
        $("#time_delivery").on("change",function(){
            $("#time_delivery").parent("form").submit();
        });
        var PharmasyLatlng = [Number(locationPharmasy[0]),Number(locationPharmasy[1])];
        map2 = L.map(document.getElementById("map")).setView(PharmasyLatlng, 10);
        L.tileLayer(hereTileUrl).addTo(map2);
        var marker = L.marker(PharmasyLatlng,{
            icon: customBG(),
        }).addTo(map2);
        marker = L.marker(PharmasyLatlng,{
            icon: customPharmasyIcon(),
        }).addTo(map2);
        for(let i = 0; i < polygons.length; i++) {
            drawPolygon2(polygons[i].coord,polygons[i].name,polygons[i].type);
        }
        $('.leaflet-pane.leaflet-overlay-pane svg').first().css('opacity',0);
        var blc = $('.leaflet-pane.leaflet-overlay-pane svg').first().clone();
        blc.css('opacity',1);
        $('.leaflet-pane.leaflet-marker-pane>svg').first().remove();
        $('.leaflet-pane.leaflet-marker-pane').append(blc);
        map2.on('zoom',function() {
            var blc = $('.leaflet-pane.leaflet-overlay-pane svg').first().clone();
            blc.css('opacity',1);
            $('.leaflet-pane.leaflet-marker-pane>svg').first().remove();
            $('.leaflet-pane.leaflet-marker-pane').append(blc);
            window.dispatchEvent(new Event('resize'));
        });
        map2.on('move',function() {
            var blc = $('.leaflet-pane.leaflet-overlay-pane svg').first().clone();
            blc.css('opacity',1);
            $('.leaflet-pane.leaflet-marker-pane>svg').first().remove();
            $('.leaflet-pane.leaflet-marker-pane').append(blc);
        });
    });
    function drawPolygon2(polygon,name,index=false) {
        var polygon2 = L.polygon(polygon, {
            fillColor: '#000',
            fillOpacity: 0.35,
            stroke: true,
            color: '#000',
            opacity: 0.8,
            weight: 2
        }).addTo(map2);
        var marker = L.marker(polygon2.getCenter(),{
            icon: customIcon(name,index),
        }).addTo(map2);
    }
    function customPharmasyIcon() {
        return L.divIcon({
            zIndexOffset: 100,
            iconSize: "auto",
            html:'<div style="width:50px;height:50px;position:relative;margin-left: -50%;margin-top: -50%;"><img alt="" src="/images/i011.svg" draggable="false" style="width:50px;height:50px;"></div>'
        });
    }
    function customIcon(text='',indexX) {
        if(time_delivery==='2') {
            var plus = tariff.tariff_same_day;
        } else if(time_delivery==='3') {
            var plus = tariff.tariff_asap;
        } else if(time_delivery==='4') {
            var plus = tariff.tariff_after_hours;
        } else {
            var plus = tariff.tariff_next_day;
        }
        if(indexX==='1') {
            var zindex = 101;
            var color = '#7a6fbe';
            name = '$'+(tariff.tariff_default+plus);
            kof = 0.1;
        } else if(indexX==='2') {
            var zindex = 101;
            var color = '#29bbe3';
            name = '$'+(tariff.tariff_area2+plus);
            kof = 0.1;
        } else if(indexX==='3') {
            var zindex = 101;
            var color = '#ec536c';
            name = '$'+(tariff.tariff_area3+plus);
            kof = 0.1;
        } else {
            var zindex = 101;
            var color = '#5d656d';
            name = '$'+(tariff.tariff_area_more+plus);
            kof = 0.13;
        }
        return L.divIcon({
            zIndexOffset: 100,
            iconSize: "auto",
            html:'<div style="width:80px;height:80px;position:relative;margin-left: -50%;margin-top: -50%;"><div style="position: absolute;top: calc(50% - 10px);"><div style="display: block;width: 80px;text-align: center;"><div class="" aria-hidden="true" style="color: #fff;line-height: 20px;font-size: 19px;font-family: Arial;text-shadow: -1px -2px 0 #000, 1px -2px 0 #000, -1px 2px 0 #000, 2px 2px 0 #000;">'+name+'</div></div></div></div>'
        });
    }
    function customBG(){
        if(time_delivery==='2') {
            var plus = tariff.tariff_same_day;
        } else if(time_delivery==='3') {
            var plus = tariff.tariff_asap;
        } else if(time_delivery==='4') {
            var plus = tariff.tariff_after_hours;
        } else {
            var plus = tariff.tariff_next_day;
        }
        var name = '$'+(tariff.tariff_area_more+plus)
        var canvas = document.createElement("canvas");
        var fontSize = 67;
        canvas.setAttribute('width', 400);
        canvas.setAttribute('height', 400);
        var context = canvas.getContext('2d');
        context.fillStyle = '#fff';
        context.strokeStyle = '#000';
        context.lineWidth = 4
        context.font = 'bold '+fontSize + 'px Arial';
        context.fillText(name, 0, fontSize);
        context.strokeText(name, 0, fontSize);
        return L.divIcon({
            zIndexOffset: 0,
            iconSize: "auto",
            className: "bg_tariff",
            html:'<div style="width:1800px;height:1800px;border-radius: 900px;outline: 2px solid #000;position:relative;margin-left:-25%;margin-top:-30%;background-size: 125px;background-image:url('+canvas.toDataURL("image/png")+');"></div>'
        });
    }
</script>
@endsection