@extends('layouts.master')

@section('title') Order Show @endsection
<style>
    .table th, .table td {
        width: 50%;
    }
</style>
@section('content')
 <!-- start page title -->

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="table-rep-plugin">
                                        <div class="table mb-0" data-pattern="priority-columns">
                                            <table id="mytable" class="table  table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Value</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>Order</td>
                                                        <td>{{$order->id}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Order Created (datetime)</td>
                                                        <td>{{date('m/d/Y H:i:s', strtotime($order->created))}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Order Status</td>
                                                        <td><span style="font-size: 13px;" class="badge badge-pill badge-{{$order->statusecolor}}">{{$order->statusename}}</span></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Delivery options</td>
                                                        <td>{{$order->delivery_method}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Preferred delivery time</td>
                                                        <td>{{$order->delivery_time}}</td>
                                                    </tr>
                                                    @if($driver!='')
                                                        <tr>
                                                            <td>Name Driver</td>
                                                            <td>{{ $driver->name }} {{ $driver->last_name }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Phone Driver</td>
                                                            <td>{{ $driver->phone }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Car Info Driver</td>
                                                            <td>{{ $driver->car_info }}</td>
                                                        </tr>
                                                    @else
                                                        <tr>
                                                            <td>Driver Info</td>
                                                            <td style="color:red;">Not selected</td>
                                                        </tr>
                                                    @endif
                                                    <tr>
                                                        <td>Name Pharmacy</td>
                                                        <td>{{$order->pharmacyname}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Phone Pharmacy</td>
                                                        <td>{{$order->pharmacyphone}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Address Pharmacy</td>
                                                        <td>{{$order->pharmacyaddress}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Name User</td>
                                                        <td>{{$order->username}} {{$order->last_name}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Phone User</td>
                                                        <td>{{$order->userphone}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Address User</td>
                                                        <td>{{$order->useraddress}}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                    </div>
                                    <form method="POST" id="appointdriver">
                                        @csrf
                                        <input type="hidden" id="driver_id" name="driver_id" value="">
                                    </form>

                                </div>
                            </div>
                        </div>
                        <!-- end col -->
                        <div class="col-md-6">
                            <div id="map" style="height: 98%;width: 99%;"></div>
                        </div>
                    </div>
                    <script src="https://maps.googleapis.com/maps/api/js?key={{config('app.googlemaps_apikey')}}&region=US&language=en&libraries=places&v=weekly&callback=initMap" defer></script>
                    <script>
                        var markersArray = [];
                        var infowindows = [];
                        var markers = [];
                        var order_id = {{$order->id}};
                        var locationUser = "{{ $order->userlocation }}".split(',');
                        var locationPharmasy = "{{ $order->pharmacylocation }}".split(',');
                        var locationDrivers = [
                            @foreach($locations as $location)
                                @if($driver=='')
                                "{{ $location->location }}",
                                @else
                                    @if($location->user_id==$driver->id)
                                    "{{ $location->location }}",
                                    @endif
                                @endif
                            @endforeach
                        ];
                        var Drivers = [
                            @foreach($locations as $location)
                                "{{ $location->user_id }}",
                            @endforeach
                        ];
                        function initMap() {
                            var input1 = document.getElementById('location');
                            const UserLatlng = { lat: Number(locationUser[0]), lng: Number(locationUser[1]) };
                            const PharmasyLatlng = { lat: Number(locationPharmasy[0]), lng: Number(locationPharmasy[1]) };
                            const map = new google.maps.Map(document.getElementById("map"), {
                                zoom: 12,
                                center: UserLatlng,
                            });
                            const geocoder = new google.maps.Geocoder();
                            marker = new google.maps.Marker({
                                position: UserLatlng,
                                icon: customIconUser({fillColor: '#0b66d6'}),
                                map: map
                            });
                            markersArray.push(marker) ;
                            marker = new google.maps.Marker({
                                position: PharmasyLatlng,
                                icon: customIcon({fillColor: '#d60b0b'}),
                                map: map
                            });
                            markersArray.push(marker) ;
                            for (let i = 0; i < locationDrivers.length; ++i) {
                                markers[i] = new google.maps.Marker({
                                    position: { lat: Number(locationDrivers[i].split(',')[0]), lng: Number(locationDrivers[i].split(',')[1]) },
                                    icon: customIconDriver({fillColor: '#32a852'}),
                                    map: map
                                });
                                @if($driver=='')
                                attachSecretMessage(markers[i],Drivers[i]);
                                markers[i].addListener("click", () => {
                                    infowindows[i].open(markers[i].get("map"), markers[i]);
                                });
                                @endif
                            }
                        }
                        function customIcon (opts) {
                            return Object.assign({
                                path: 'M24,1C15.2,1,6.015,7.988,6,18C5.982,29.981,24,48,24,48s18.019-17.994,18-30 C41.984,8.003,32.8,1,24,1z M24,26c-4.418,0-8-3.582-8-8s3.582-8,8-8s8,3.582,8,8S28.418,26,24,26z',
                                fillColor: '#34495e',
                                fillOpacity: 1,
                                strokeColor: '#000',
                                strokeWeight: 2,
                                scale: 0.95,
                                anchor: new google.maps.Point(23,60),
                                scaledSize: new google.maps.Size(46,60)
                            }, opts);
                        }
                        function customIconDriver (opts) {
                            return Object.assign({
                                path: 'M 25 1.8007812 C 15.297 1.8007812 7.3984375 9.6954375 7.3984375 19.398438 C 7.3984375 30.625437 18.109375 41.515188 23.859375 47.367188 L 25 48.527344 L 26.136719 47.367188 C 31.890719 41.519188 42.601562 30.633437 42.601562 19.398438 C 42.601562 9.6954375 34.703 1.8007812 25 1.8007812 z M 18.925781 11.898438 L 24.392578 11.898438 C 29.000578 11.898438 31.697266 14.744094 31.697266 19.621094 C 31.697266 24.541094 29.011578 27.398438 24.392578 27.398438 L 18.925781 27.398438 L 18.925781 11.898438 z M 21.117188 13.84375 L 21.117188 25.455078 L 24.189453 25.455078 C 27.541453 25.455078 29.474609 23.328297 29.474609 19.654297 C 29.474609 15.991297 27.508453 13.84375 24.189453 13.84375 L 21.117188 13.84375 z',
                                fillColor: '#34495e',
                                fillOpacity: 1,
                                strokeColor: '#000',
                                strokeWeight: 2,
                                scale: 0.95,
                                anchor: new google.maps.Point(23,60),
                                scaledSize: new google.maps.Size(46,60)
                            }, opts);
                        }
                        function customIconUser (opts) {
                            return Object.assign({
                                path: 'M86,7.16667c-31.61217,0 -57.33333,25.72117 -57.33333,57.33333c0,23.16267 29.12892,64.17033 46.49733,86.55183c2.61942,3.37192 6.56825,5.30692 10.836,5.30692c4.26775,0 8.21658,-1.935 10.83958,-5.30692c17.36483,-22.3815 46.49375,-63.38917 46.49375,-86.55183c0,-31.61217 -25.72117,-57.33333 -57.33333,-57.33333zM86,39.41667c7.91558,0 14.33333,6.41775 14.33333,14.33333c0,7.91558 -6.41775,14.33333 -14.33333,14.33333c-7.91558,0 -14.33333,-6.41775 -14.33333,-14.33333c0,-7.91558 6.41775,-14.33333 14.33333,-14.33333zM111.08333,85.11133c0,7.96575 -10.16592,15.222 -25.08333,15.222c-14.91742,0 -25.08333,-7.25625 -25.08333,-15.222v-4.67267c0,-2.86667 2.322,-5.18867 5.18867,-5.18867h39.78933c2.86667,0 5.18867,2.322 5.18867,5.18867z',
                                fillColor: '#34495e',
                                fillOpacity: 1,
                                strokeColor: '#000',
                                strokeWeight: 2,
                                scale: 0.3,
                                anchor: new google.maps.Point(23,60),
                                scaledSize: new google.maps.Size(46,60)
                            }, opts);
                        }
                        function attachSecretMessage(marker,driver_id) {
                            $.post("/users/get", { identity: driver_id, _token: $("input[name='_token']").val() }, null, 'json').done(function(data) {
                                var infowindow0 = new google.maps.InfoWindow({
                                    content: '<p>'+data.name+' <br>ID #'+driver_id+'</p><a href="/routes-list/driver/'+driver_id+'?order='+order_id+'"><button class="btn btn-primary">Appoint as Driver</button></a>',
                                });
                                infowindows.push(infowindow0);
                            });
                        }
                        function placeMarkerAndPanTo(latLng, map) {
                            while(markersArray.length) { markersArray.pop().setMap(null); }
                            var marker = new google.maps.Marker({
                                position: latLng,
                                map: map
                            });
                            map.panTo(latLng);
                            markersArray.push(marker);
                        }
                        function geocodeLatLng(geocoder, map, infowindow) {
                            const input = document.getElementById("location").value;
                            const latlngStr = input.split(",", 2);
                            const latlng = {
                            lat: parseFloat(latlngStr[0]),
                            lng: parseFloat(latlngStr[1]),
                            };
                            geocoder.geocode({ location: latlng }, (results, status) => {
                            if (status === "OK") {
                                if (results[0]) {
                                    document.getElementById('searchTextField').value = results[0].formatted_address;
                                } else {
                                    infowindow.setContent("No info found");
                                    infowindow.open(map, marker);
                                }
                            } else {
                                window.alert("Geocoder failed due to: " + status);
                            }
                            });
                        }
                    </script>
                    
@endsection