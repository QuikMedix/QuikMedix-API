@extends('layouts.master')

@section('title') Users @endsection

@section('headerCss')

    <!-- Responsive Table css -->
    <link href="{{ URL::asset('/libs/rwd-table/rwd-table.min.css')}}" rel="stylesheet" type="text/css" /> <!-- Bootstrap Css -->
    <link rel="stylesheet" href="{{ URL::asset('/css/bootstrap-multiselect.min.css')}}" type="text/css"/>
    <link rel="stylesheet" href="{{ URL::asset('/css/bootstrap-lib.min.css')}}">
    <link href="{{ URL::asset('/css/sweetalert2.min.css')}}" rel="stylesheet" type="text/css">
    <style>
        .pac-container {
            z-index: 10000 !important;
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
                            @if($error!='')
                                <div class="alert alert-danger" role="alert">
                                    {{$error}}
                                </div>
                            @endif
                                <div style="margin-top: 1.25rem;position: absolute;text-align: center;width: 100%;">Pages: 
                                    @foreach ($pages as $page)
                                        <form class="filter-form" style="display: inline-block;">
                                            <input type="hidden" name="page" value="{{ $page['id'] }}">
                                            <input type="hidden" name="search" value="{{ $search }}">
                                            <button class="btn {{$page['class']}}">{{ $page['id'] }}</button>
                                        </form> 
                                    @endforeach
                                    ...                                    
                                </div>
                                <div class="card-body" style="margin-bottom:30px;">
                                    <div class="table-rep-plugin">
                                        <div class="table-responsive mb-0" data-pattern="priority-columns">
                                            <table id="mytable" class="table  table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th data-priority="3">Status</th>
                                                        <th data-priority="3">Action</th>
                                                        <th data-priority="3">APP</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($users as $user)
                                                        <tr>
                                                            <td><img style="max-width: 40px;height: auto;float: left;margin-right: 15px;" id="user_img" @if(empty($user->image)) src="/images/users/default-user-image.png" @else src="{{ $user->image }}" @endif>  {{$user->name}} {{$user->last_name}}  <br> <span style="font-weight: bold;color: black;">{{$user->phone}} </span></td>
                                                            @if($user->isblocked == 1)
                                                                <td><button class="btn btn-danger">Blocked</button></td>
                                                            @elseif($user->isactive == 0)
                                                                <td><button class="btn btn-warning">No active</button></td>
                                                            @else 
                                                                <td><button class="btn btn-success">Active</button></td>
                                                            @endif
                                                            <td class="action">
                                                            @if($user->id != 1)
                                                                <a href="/facilitys/{{ $pharmacy_id }}/edit/{{$user->id}}"><button class="btn btn-warning">Edit</button></a>
                                                                <form method="post" style="display: inline-block;">
                                                                        @csrf
                                                                    <input type="hidden" name="user_id" value="{{$user->id}}">
                                                                    <input type="hidden" name="remove" value="1">
                                                                    <button class="btn btn-danger">Remove</button>
                                                                </form>
                                                                <button type="button" class="btn btn-outline-secondary waves-effect" onclick="reSendAuthMessage('{{$user->id}}',this)">Resend</button>
                                                            @endif
                                                            </td>
                                                            <td>
                                                                @if($user->os==1)
                                                                    <i class="ion ion-logo-android" style="font-size: 34px;color:#58db83;"></i> 
                                                                    <i style="font-size: 34px;margin-left:5px;" class="ion ion-logo-apple"></i>
                                                                @elseif($user->os==2)
                                                                    <i class="ion ion-logo-android" style="font-size: 34px;"></i> 
                                                                    <i style="font-size: 34px;margin-left:5px; color:#58db83;" class="ion ion-logo-apple"></i>
                                                                @else
                                                                    <i class="ion ion-logo-android" style="font-size: 34px;"></i> 
                                                                    <i style="font-size: 34px;margin-left:5px;" class="ion ion-logo-apple"></i>
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
                                            <button class="btn {{$page['class']}}">{{ $page['id'] }}</button>
                                        </form> 
                                    @endforeach
                                    ...
                                </div>   
                                </div>
                            </div>
                        </div>
                        <!-- end col -->
                    </div>
                    <!-- end row -->
                    <div class="offcanvas offcanvas-end" tabindex="-1" id="addPatientModal" aria-labelledby="addPatientModalLabel" style="width: 40%;">
                        <div class="offcanvas-header card-title-4">                                               
                            <h5 class="mb-0 float-left">Add New Facility</h5>                                                
                            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                        </div>
                        <div class="offcanvas-body">
                            <form action="/facilitys/{{$pharmacy_id}}/add" method="POST" id="form_add">
                                @csrf
                                <input type="hidden" name="save" value="1">
                                <div class="row">                                                            
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Name</label>
                                            <input type="text" class="form-control" required name="name" value="">
                                        </div>
                                    </div> 
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <input type="text" class="form-control" name="last_name" value="">
                                        </div>
                                    </div>
                                    <!-- <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Day of Birth</label>
                                            <input type="date" class="form-control" name="birth_date" id="" value="">  желательно чтоб маска была как в телефоне, чтоб лишнего не заполнили  - можно это вообще удалить?
                                        </div>
                                    </div>   --> 
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Primary phone</label>
                                            <input type="phone" class="form-control" required name="phone" id="phone" value="">                                                                    
                                        </div>
                                    </div> 
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Secondary phone</label>
                                            <input type="phone" class="form-control" name="home_phone" id="home_phone" value="">                                                                   
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Address</label>
                                            <input type="text" class="form-control" required id="searchTextField" type="text" name="address" value="">                                                                   
                                        </div>
                                    </div> 
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Apartment, STE</label>
                                            <input type="text" class="form-control" type="text" name="apartment" value="">                                                                   
                                        </div>
                                    </div> 
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Zip</label>
                                            <input type="text" class="form-control" required type="text" name="zip" value="">                                                                   
                                        </div>
                                    </div>
                                    <input style="display:none;" type="checkbox" id="order_add" name="order_add" value="1">
                                    <div class="col-md-12 mt-3">                                                                
                                        <div class="mt-4 text-center">
                                            <button type="submit" class="btn btn-secondary waves-effect mr-3">Save <i class="ti-save"></i></button>
                                            <button type="button" onclick="$('#order_add').prop('checked', true);$('#form_add').submit();" class="btn btn-primary waves-effect">Create and add patients<i class="ti-arrow-circle-right"></i></button>
                                        </div>                                      
                                    </div>                                                          
                                </div> 
                            </form>                                            
                        </div> 
                    </div> 

@endsection

@section('footerScript')
            <!-- Responsive Table js -->
            <script src="{{ URL::asset('/libs/rwd-table/rwd-table.min.js')}}"></script>

            <script src="{{ URL::asset('/js/bootstrap.bundle.min.js')}}"></script>
            <script src="{{ URL::asset('/js/bootstrap-datepicker.min.js')}}"></script>
            <script src="{{ URL::asset('/js/select2.min.js')}}"></script>
            <script src="{{ URL::asset('/js/jquery.bootstrap-touchspin.min.js')}}"></script>
            <script src="{{ URL::asset('/js/jquery-ui.min.js')}}" type="text/javascript"></script>

            <!-- Init js -->
            <script src="{{ URL::asset('/js/pages/table-responsive.init.js')}}"></script>
            <script>
                function initialize() {
                    var input = document.getElementById('searchTextField');
                    new google.maps.places.Autocomplete(input);
                }
            </script>
            <script src="https://maps.googleapis.com/maps/api/js?key={{config('app.googlemaps_apikey')}}&region=US&language=en&libraries=places&v=weekly&callback=initialize"></script>
            <script>
                $(document).ready(function(){
                    
                });
                function reSendAuthMessage(user_id,_this) {
                    $.post("/facilitys/"+user_id+"/resend", { _token: $('input[name="_token"]').val() }).done(function( data ) {
                        data=JSON.parse(data);
                        if(data.message=="OK") {
                            $(_this).text("Sended");
                            $(_this).attr("disabled","disabled");
                        } else {
                            alert(data.message);
                        }
                    });
                }
            </script>
@endsection