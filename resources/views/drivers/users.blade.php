@extends('layouts.master')

@section('title') Users @endsection

@section('headerCss')

    <!-- Responsive Table css -->
    <link href="{{ URL::asset('/libs/rwd-table/rwd-table.min.css')}}" rel="stylesheet" type="text/css" /> <!-- Bootstrap Css -->
    <style>
        .types {
            position: absolute;
            background-color: #fff;
            padding: 5px 15px;
            border-radius: 5px;
            margin-left: -35px;
            margin-top: 15px;
            border: 2px solid #e9ecef;
            z-index:1;
        }
        .types_filter {
            cursor:pointer;
        }
        .name-column {
            overflow: hidden;
            max-width: 150px;
            width: 150px;
            white-space: break-spaces !important;
        }
        .table th, .table td {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }
    </style>
@endsection

@section('content')
 <!-- start page title -->
                    <div class="row">

                    
                    
                    </div>
                    <!-- end page title -->

                    <div class="row">
                        <div class="col-9">
                            <div class="card" style="height: 100%;">
                                <div style="margin-top: 1.25rem;position: absolute;text-align: center;width: 100%;">Pages: 
                                    @foreach ($pages as $page)
                                        <form class="filter-form" style="display: inline-block;">
                                            <input type="hidden" name="page" value="{{ $page['id'] }}">
                                            <input type="hidden" name="search" value="{{ $search }}">
                                            @if(!empty($_GET['type']))
                                                @if($_GET['type']==[1,2])
                                                <input type="hidden" name="type[]" value="1" checked>
                                                <input type="hidden" name="type[]" value="2" checked>
                                                @elseif($_GET['type']==[1])
                                                <input type="hidden" name="type[]" value="1" checked>
                                                <input type="hidden" name="type[]" value="2">
                                                @elseif($_GET['type']==[2])
                                                <input type="hidden" name="type[]" value="1">
                                                <input type="hidden" name="type[]" value="2" checked>
                                                @endif
                                            @endif
                                            <button class="btn {{$page['class']}}">{{ $page['id'] }}</button>
                                        </form> 
                                    @endforeach
                                    ...
                                </div>
                                <div class="card-body" style="margin-bottom:20px;">
                                    <div class="table-rep-plugin">
                                        <div class="table-responsive mb-0" data-pattern="priority-columns">
                                            <table id="mytable" class="table  table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Name</th>
                                                        <th data-priority="1">Phone</th>
                                                        <th data-priority="1" class="types_filter">Type <i class="fa fa-filter" aria-hidden="true"></i>
                                                            <div class="types" style="display:none;">
                                                                <form>
                                                                @if(!empty($_GET['page']))
                                                                    <input type="hidden" name="page" value="{{ $page0 }}">
                                                                @endif
                                                                @if(!empty($_GET['search']))
                                                                    <input type="hidden" name="search" value="{{ $search }}">
                                                                @endif                                                   
                                                                @if(!empty($_GET['type']))
                                                                    @if($_GET['type']==[1,2])
                                                                    <div style="margin-bottom:5px;">
                                                                        <input type="checkbox" name="type[]" value="1" checked class="col-form-label status" id="exampleinput1">
                                                                        <label for="exampleinput1" class="col-form-label">Pharmacy drivers</label>
                                                                    </div>
                                                                    <div style="margin-bottom:5px;">
                                                                        <input type="checkbox" name="type[]" value="2" checked class="col-form-label status" id="exampleinput2">
                                                                        <label for="exampleinput2" class="col-form-label">A2B Rx drivers</label>
                                                                    </div>
                                                                    @elseif($_GET['type']==[1])
                                                                    <div style="margin-bottom:5px;">
                                                                        <input type="checkbox" name="type[]" value="1" checked class="col-form-label status" id="exampleinput1">
                                                                        <label for="exampleinput1" class="col-form-label">Pharmacy drivers</label>
                                                                    </div>
                                                                    <div style="margin-bottom:5px;">
                                                                        <input type="checkbox" name="type[]" value="2" class="col-form-label status" id="exampleinput2">
                                                                        <label for="exampleinput2" class="col-form-label">A2B Rx drivers</label>
                                                                    </div>
                                                                    @elseif($_GET['type']==[2])
                                                                    <div style="margin-bottom:5px;">
                                                                        <input type="checkbox" name="type[]" value="1" class="col-form-label status" id="exampleinput1">
                                                                        <label for="exampleinput1" class="col-form-label">Pharmacy drivers</label>
                                                                    </div>
                                                                    <div style="margin-bottom:5px;">
                                                                        <input type="checkbox" name="type[]" value="2" checked class="col-form-label status" id="exampleinput2">
                                                                        <label for="exampleinput2" class="col-form-label">A2B Rx drivers</label>
                                                                    </div>
                                                                    @endif
                                                                @else
                                                                    <div style="margin-bottom:5px;">
                                                                        <input type="checkbox" name="type[]" value="1" checked class="col-form-label status" id="exampleinput1">
                                                                        <label for="exampleinput1" class="col-form-label">Pharmacy drivers</label>
                                                                    </div>
                                                                    <div style="margin-bottom:5px;">
                                                                        <input type="checkbox" name="type[]" value="2" class="col-form-label status" id="exampleinput2">
                                                                        <label for="exampleinput2" class="col-form-label">A2B Rx drivers</label>
                                                                    </div>
                                                                @endif
                                                                    <button class="btn btn-primary" type="submit">Apply</button>
                                                                </form>
                                                            </div>
                                                        </th>
                                                        <th data-priority="3">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                @foreach ($users as $user)
                                                    <tr>
                                                        <td>{{$user->id}}</td>
                                                        <td class="name-column">{{$user->name}} {{$user->last_name}}</td>
                                                        <td>{{$user->phone}}</td>
                                                        @if($user->pharmacy_id == NULL || $user->pharmacy_id == '')
                                                        <td class="name-column">A2B Rx driver</td>
                                                        @else 
                                                        <td class="name-column">Pharmacy driver</td>
                                                        @endif
                                                        <td class="action">
                                                        @if($user->id != 1 && ($user->pharmacy_id != NULL || $user->pharmacy_id != ''))
                                                            <a href="/drivers/{{ $pharmacy_id }}/users/edit/{{$user->id}}"><button class="btn btn-warning">Edit</button></a>
                                                            <form method="post" style="display: inline-block;">
                                                                    @csrf
                                                                <input type="hidden" name="user_id" value="{{$user->id}}">
                                                                <input type="hidden" name="remove" value="1">
                                                                <button class="btn btn-danger">Remove</button>
                                                            </form>
                                                        @endif
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
                                        <form class="filter-form" style="display: inline-block;">
                                            <input type="hidden" name="page" value="{{ $page['id'] }}">
                                            <input type="hidden" name="search" value="{{ $search }}">
                                            @if(!empty($_GET['type']))
                                                @if($_GET['type']==[1,2])
                                                <input type="hidden" name="type[]" value="1" checked>
                                                <input type="hidden" name="type[]" value="2" checked>
                                                @elseif($_GET['type']==[1])
                                                <input type="hidden" name="type[]" value="1" checked>
                                                <input type="hidden" name="type[]" value="2">
                                                @elseif($_GET['type']==[2])
                                                <input type="hidden" name="type[]" value="1">
                                                <input type="hidden" name="type[]" value="2" checked>
                                                @endif
                                            @endif
                                            <button class="btn {{$page['class']}}">{{ $page['id'] }}</button>
                                        </form> 
                                    @endforeach
                                    ...
                                </div>
                            </div>
                        </div>
                        <!-- end col -->
                        <div class="col-3">
                            <div id="map" style="height: 98%;width: 99%;"></div>
                        </div>
                    </div>
                    <!-- end row -->
                    <script src="https://maps.googleapis.com/maps/api/js?key={{config('app.googlemaps_apikey')}}&region=US&language=en&libraries=places&v=weekly&callback=initMap" defer></script>
                    <script>
                        var markers = [];
                        var locationPharmasy = "{{ $pharmacylocation }}".split(',');
                        var locationDrivers = [
                            @foreach ($users as $user)
                                "{{ $user->location }}",
                            @endforeach
                        ];
                        var Drivers = [
                            @foreach ($users as $user)
                                "{{ $user->id }}",
                            @endforeach
                        ];
                        function initMap() {
                            const PharmasyLatlng = { lat: Number(locationPharmasy[0]), lng: Number(locationPharmasy[1]) };
                            const map = new google.maps.Map(document.getElementById("map"), {
                                zoom: 12,
                                center: PharmasyLatlng,
                            });
                            for (let i = 0; i < locationDrivers.length; ++i) {
                                markers[i] = new google.maps.Marker({
                                    position: { lat: Number(locationDrivers[i].split(',')[0]), lng: Number(locationDrivers[i].split(',')[1]) },
                                    icon: {
                                        path: google.maps.SymbolPath.CIRCLE,
                                        scale: 15,
                                        fillColor: '#ec536c',
                                        fillOpacity: 1.0,
                                        strokeWeight: 2,
                                        strokeColor:"#000"
                                    },
                                    label: {
                                        text:Drivers[i].toString(),
                                        fontWeight: 'regular',
                                        fontSize: '14px',
                                        color: '#000'
                                    },
                                    map: map
                                });
                            }
                        }
                    </script>
@endsection

@section('footerScript')
            <!-- Responsive Table js -->
            <script src="{{ URL::asset('/libs/rwd-table/rwd-table.min.js')}}"></script>

            <!-- Init js -->
            <script src="{{ URL::asset('/js/pages/table-responsive.init.js')}}"></script>
            <script>
                $(document).ready(function(){
                    $(document).click( function(e){
                        if( $(e.target).closest('.types_filter').length ) {
                            if( $(e.target).closest('.types').length==0 ) {
                                if($('.types').is(':visible')) {
                                    $('.types').slideUp(200);
                                } else {
                                    $('.types').slideDown(200);
                                }
                            }
                            return;
                        }
                        // клик снаружи элемента 
                        $('.types').slideUp(200);
                    });
                });
            </script>
@endsection