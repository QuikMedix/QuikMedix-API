@extends('layouts.master')

@section('title') {{$title}} @endsection

@section('headerCss')
    <link href="/leaflet/leaflet.css" rel="stylesheet" type="text/css">
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
                                    <h2>Statistic Orders Map Pharmacy #{{$pharmacy->id}} {{$pharmacy->name}}</h2>
                                    <form method="post" enctype="multipart/form-data">
                                        @csrf
                                        <input type="hidden" name="save" value="1">
                                        @if($alert!='') 
                                            <div class="alert alert-danger" role="alert">{{ $alert }}</div>
                                        @endif
                                        <div class="form-group row">
                                            <label for="statuse_id" class="col-sm-2 col-form-label">Orders Status</label>
                                            <div class="col-sm-10">
                                                <select class="form-control" name="statuse_id" id="statuse_id">
                                                    <option value="">All statuses</option>
                                                    @foreach($statuses as $status)
                                                    <option value="{{$status->id}}" @if($statuse_id==$status->id){{'selected'}}@endif>{{$status->name}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Statistic Date</label>
                                            <div class="col-sm-10">
                                                <input type="date" class="form-control" required name="date" value="{{$date}}">
                                            </div>
                                        </div>
                                        <button type="submit" style="margin-top:10px;" class="btn btn-primary">Update</button>
                                        <div id="map" style="margin-top:20px;height: 600px;width: 100%;"></div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- end col -->
                    </div>
                    <!-- end row -->

@endsection

@section('footerScript')
<script src="/leaflet/leaflet.js"></script>
<script>
    const here = {
        apiKey:"{{config('app.hereApiKey')}}"
    }
    const style = 'normal.day';
    const hereTileUrl = `https://maps.hereapi.com/v3/base/mc/{z}/{x}/{y}/png?apiKey=${here.apiKey}&ppi=400`;
    var map2;
    $(document).ready(function(){
        $('.select2').selectize();
        map2 = L.map(document.getElementById("map")).setView([40.743798988555,-74.023925802166005], 10);
        L.tileLayer(hereTileUrl).addTo(map2);
        @if(!empty($polygons))
        var polygons=JSON.parse('[@foreach($polygons as $key=>$pol) {"name":"{{$pol->count}}","coord":{!!$pol->polygon!!} }@if($key<count($polygons)-1){{","}}@endif @endforeach]');
        for(let i = 0; i < polygons.length; i++) {
            drawPolygon2(polygons[i].coord,polygons[i].name);
        }
        @endif
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
            icon: customIcon(name),
        }).addTo(map2);
    }
    function customIcon(text='') {
        return L.divIcon({
            iconSize: "auto",
            html:'<div style="width:80px;height:80px;position:relative;margin-left: -50%;margin-top: -50%;"><div style="position: absolute;top: calc(50% - 10px);"><div style="display: block;width: 80px;text-align: center;"><div class="" aria-hidden="true" style="color: #fff;line-height: 20px;font-size: 19px;font-family: Arial;text-shadow: -1px -2px 0 #000, 1px -2px 0 #000, -1px 2px 0 #000, 2px 2px 0 #000;">'+text+'</div></div></div></div>'
        });
    }
</script>
@endsection