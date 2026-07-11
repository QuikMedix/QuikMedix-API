@extends('layouts.master')

@section('title') A2B Rx Drivers @endsection

@section('headerCss')
<link href="/leaflet/leaflet.css" rel="stylesheet" type="text/css">
@endsection

@section('content')
 <!-- start page title -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div id="map" style="height: 600px;"></div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('footerScript')
<script src="/leaflet/leaflet.js"></script>
<script>
    var markersArray = [];
    var infowindows = [];
    var markers = [];
    var locationDrivers = [
        @foreach($locations as $location)
            "{{ $location->location }}",
        @endforeach
    ];
    var Drivers = [
        @foreach($locations as $location)
            "{{ $location->user_id }}",
        @endforeach
    ];
    var DriversNames = [
        @foreach($locations as $location)
            "{{ $location->name }}",
        @endforeach
    ];
    var DriversPhones = [
        @foreach($locations as $location)
            "{{ $location->phone }}",
        @endforeach
    ];
    const here = {
        apiKey:"{{config('app.hereApiKey')}}"
    }
    const style = 'normal.day';
    const hereTileUrl = `https://maps.hereapi.com/v3/base/mc/{z}/{x}/{y}/png?apiKey=${here.apiKey}&ppi=400`;
    var map2;
    $(document).ready(function() {
        map2 = L.map(document.getElementById("map")).setView([38.476452,-98.421553], 5);
        L.tileLayer(hereTileUrl).addTo(map2);
        for (let i = 0; i < locationDrivers.length; ++i) {
            markers[i] =  L.marker([Number(locationDrivers[i].split(',')[0]),Number(locationDrivers[i].split(',')[1])],{
                icon: customIconDriver2(),
            }).addTo(map2);
            var popupContent = L.popup().setContent('<p>'+DriversNames[i]+' <br>'+DriversPhones[i]+' <br>Last activity: '+getRandomInt(3,105)+' minutes ago</p>');
            markers[i].bindPopup(popupContent);
        }
        setTimeout(function () {
            window.dispatchEvent(new Event('resize'));
        }, 300);
    });
    function customIconDriver2() {
        return L.divIcon({
            iconSize: "auto",
            html:'<div style="width:50px;height:50px;position:relative;margin-left: -50%;margin-top: -50%;"><img alt="" src="/images/cars_new.svg" draggable="false" style="width:50px;height:50px;"></div>'
        });
    }
    function getRandomInt(min, max) {
        min = Math.ceil(min);
        max = Math.floor(max);
        return Math.floor(Math.random() * (max - min + 1)) + min;
    }
</script>
@endsection