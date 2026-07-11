@extends('layouts.master')

@section('title') Delivery Map  @endsection

@section('headerCss')
<link href="/leaflet/leaflet.css" rel="stylesheet" type="text/css">
<style>
.rep-menu a {
    font-size: 14px;
    line-height: 30px;
    color: black;
    display: block;
    margin: 10px 0;
    padding: 5px 0 5px 20px;

}
.rep-menu a:hover {
    background: #7a6fbe;
    color: #ffffff !important;
}
.rep-menu i {
    font-size: 16px;
    margin: 0px 7px;
}
</style>
@endsection

@section('content')
 <!-- start page title -->
                    <div class="row">

                   
                    </div>
                    <!-- end page title -->                  
                    <div class="row">
                        <div class="col-xl-3"> 
                        <div class="card">
                            <div class="card-body">                                
                                <div class="rep-menu">  
                                    <a href="/reports"> <i class="ti-stats-up"></i> Overview</a>
                                    <a href="/reports/billing"> <i class="ti-credit-card"></i> Billing</a>
                                    <a href="/reports/apps"> <i class="ti-mobile"></i> Apps analytics </a>
                                    <a href="/reports/drivers"> <i class="ti-truck"></i> Drivers </a>
                                    <a href="/reports/pharmacies"> <i class="ti-support"></i> Pharmacies </a>
                                    <a href="/reports/customers"> <i class="ti-face-smile"></i> Customers </a>
                                    <a href="/reports/invoices"> <i class="ti-receipt"></i> Invoices </a>
                                    <a href="/reports/map" style="background: #7a6fbe;color: #ffffff !important;"> <i class="ti-map-alt"></i> Delivery Map </a>
                                </div>                                 
                            </div>
                         </div>                                                             
                        </div>
                        <form method="POST" class="col-xl-9">
                            @csrf
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-4 pt-4">
                                            <h4>Total orders created: {{array_sum(array_column($polygons->toArray(), 'count'))}}</h4>
                                        </div>
                                        <div class="col-3">
                                            <div class="form-group">
                                                <label class="control-label">Date</label>
                                                <input type="date" class="form-control" required name="date" value="{{$date}}">                                       
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="form-group">
                                                <label class="control-label">Zone</label>
                                                <select class="form-control select2">
                                                    <option>Northeast</option>
                                                    <option>Southeast</option>
                                                    <option>Midwest</option>
                                                    <option>Southwest</option>
                                                    <option>West</option>                                                
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-2">
                                            <div class="form-group">
                                                <label class="control-label">&nbsp;</label>
                                                <button type="submit" class="btn btn-primary form-control">Update</button>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div id="map" style="margin-top:20px;height: 600px;width: 100%;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
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
