@extends('layouts.master')

@section('title') Driver Profile @endsection

@section('headerCss')
    <!-- Responsive Table css -->
    <style>
    .bor {
    border-right: solid 1px #e7e7e7;
    }
    .rating .mdi {
        font-size: 20px;
    }
    .profilenew h5 {
        border-bottom: solid 1px #e7e7e7;
    }
    .profilenew label {
        margin-bottom: 0;
        font-size: 11px;
        color: #000000;
        font-weight: 300;
        background: #efefef;
        padding: 2px 5px;
        border-radius: 2px;
        margin-left: 5px;
    }
    .profilenew input[type=radio] {
        margin: 0px -5px 0 7px;
    }
    .modal-map {
    width: 800px;
    max-width: 800px;
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
                    <div class="card-body">
                        <div class="row">
                            <div class="col-3">
                                <h5 class="pt-2">Quick Report</h5>
                            </div>
                            <div class="col-9">
                            <div style="display:flex;float: right;">                            
                                <input value="{{date('Y-m-d')}}" style="max-width: 150px;margin-left:10px;font-size:12px;min-height:35px;padding:0 5px" class="form-control" type="date" id="date_stat">
                                <button type="button" class="btn btn-primary waves-effect waves-light mx-1" id="exampleModalScrollableOpen" style="min-width: 150px;">View statistics</button>
                                <button type="button" class="btn btn-primary waves-effect waves-light mx-1" data-bs-toggle="modal" data-bs-target=".bs-example-modal-center" style="min-width: 150px;">On Map</button>
                                <div class="modal bs-example-modal-xl fade" id="exampleModalScrollable" tabindex="-1" role="dialog" aria-labelledby="exampleModalScrollableTitle" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-scrollable modal-xl">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title mt-0" id="exampleModalScrollableTitle">Driver routes statistics <span></span></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                    aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light waves-effect" data-bs-dismiss="modal">Close</button>                                          
                                            </div>
                                        </div><!-- /.modal-content -->
                                    </div><!-- /.modal-dialog -->
                                </div><!-- /.modal -->
                                <div class="modal fade bs-example-modal-center" tabindex="-1" role="dialog"
                                    aria-labelledby="mySmallModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-map modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title mt-0">Map</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d387191.33750346623!2d-73.97968099999999!3d40.6974881!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c24fa5d33f083b%3A0xc80b8f06e177fe62!2sNew%20York%2C%20NY%2C%20USA!5e0!3m2!1sen!2sua!4v1682951290399!5m2!1sen!2sua" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                                            </div>
                                        </div><!-- /.modal-content -->
                                    </div><!-- /.modal-dialog -->
                                </div><!-- /.modal -->
                            </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>   

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">  
                        <div class="row">
                            <div class="col-xl-3 col-sm-6 bor">
                                <div class="p-1">
                                    <div class="float-left mr-3 mb-4">
                                        <img @if(empty($user->image)) src="/images/users/default-user-image.png" @else src="{{ $user->image }}" @endif id="user_img"  alt="user_img" class="avatar-lg rounded">
                                    </div>
                                    <h6 class="mb-1 font-size-16 mt-2">{{ $user->name }} {{ $user->last_name }}</h6>
                                    <p class="text-muted mb-0">{{ $user->phone }}</p>
                                    
                                    <p class="text-muted mb-0">Created: {{date('m.d.Y g:i A', strtotime($user->created_at))}}</p>
                                    <p class="text-muted mb-0">App: 
                                        @if($user->os==1)
                                            Android
                                        @elseif($user->os==2)
                                            IOS
                                        @else
                                            No app
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="col-xl-3 col-sm-6 bor text-center rating">    
                                <h6 class="mb-1 font-size-16 mt-4">Driver rating</h6>    
                                <i class="mdi mdi-star-outline"></i><i class="mdi mdi-star-outline"></i><i class="mdi mdi-star-outline"></i><i class="mdi mdi-star-outline"></i><i class="mdi mdi-star-outline"></i>                                    
                            </div>
                            <div class="col-xl-3 col-sm-6 bor text-center">     
                                <h6 class="mb-1 font-size-16 mt-1">Copay debt: ${{number_format($duty,2)}}</h6> 
                                @if(!empty($last_cash))
                                <p class="text-muted mb-0">Last transfer: ${{number_format($last_cash->copay,2)}}</p>
                                <p class="text-muted mb-3">Collected by admin #{{$last_cash->admin_id}} - {{date('m.d.Y g:i A', strtotime($last_cash->return_at))}}</p>
                                @endif
                                <a href="/drivers/{{$user->id}}/payouts"><button type="button" class="btn btn-outline-primary btn-sm waves-effect waves-light">History <i class="mdi mdi-history"></i></button></a>                              
                            </div>                            
                            <div class="col-xl-3 col-sm-6 text-center">
                                <h6 class="mb-1 font-size-16 mt-1">Total routes: 0</h6>                            
                                <p class="text-muted mb-0">Unpaid routes: 0</p>
                                <h6 class="mb-3 font-size-18 mt-1">Salary: $0.00</h6>
                                <a href="/routes-list/driver/{{$user->id}}"><button type="button" class="btn btn-outline-primary btn-sm waves-effect waves-light">View route <i class="mdi mdi-car"></i></button></a>
                                <a href="#"><button type="button" class="btn btn-outline-primary btn-sm waves-effect waves-light">Route history <i class="mdi mdi-history"></i></button></a>
                                <a href="#"><button type="button" class="btn btn-outline-primary btn-sm waves-effect waves-light">Chat <i class="mdi mdi-message-processing-outline"></i></button></a>
                            </div>
                        </div>
                    </div>                               
                </div>
            </div>
            <div class="col-12">
            <div class="card">
                <div class="card-body">  
                    <div class="row profilenew">
                        <form method="post" class="col-xl-6 col-sm-12 p-0 row" enctype="multipart/form-data">
                            <div class="col-xl-6 col-sm-6 bor"> 
                                <h5 class="mb-4 pb-2 text-center">Profile</h5>
                                @csrf
                                <input type="hidden" name="save" value="1">
                                @if($alert!='') 
                                    <div class="alert alert-danger" role="alert">{{ $alert }}</div>
                                @endif
                                <div class="mb-2">
                                    <label for="first-name-text">First Name</label>
                                    <input class="form-control" required type="text" name="name" value="{{ $user->name }}">
                                    
                                </div>
                                <div class="mb-2">
                                    <label for="last-name-text">Last Name</label>
                                    <input class="form-control" required type="text" name="last_name" value="{{ $user->last_name }}">
                                </div>
                                <div class="mb-2" style="display:none;">
                                    <label for="email-text">Email</label>
                                    <input class="form-control" type="email" name="email" value="{{ $user->email }}">                                                  
                                </div>
                                <div class="mb-2">
                                    <label for="phone-text" >Cell Phone</label>                                               
                                    <input class="form-control" required type="phone" name="phone" id="phone" value="{{ $user->phone }}">                                                   
                                </div>
                                <div class="mb-2">
                                    <label for="home-phone-text">SSN</label>
                                    <input class="form-control" type="text" name="identification_cards" id="SSN" value="{{ $user->identification_cards }}">                                                  
                                </div>                                
                                <div class="mb-2">
                                    <label for="Address-text">Address</label>                                                   
                                    <input class="form-control" required id="searchTextField" type="text" name="address" value="{{ $user->address }}">
                                    
                                </div>
                                <div class="mb-2">
                                    <label for="apt-text">Apartment, STE</label>                                                    
                                    <input class="form-control" type="text" name="apartment" value="{{ $user->apartment }}">
                                </div>
                                <div class="mb-2">
                                    <label for="zip-text">Zip</label>                                                   
                                    <input class="form-control" required type="text" name="zip" value="{{ $user->zip }}">                                                    
                                </div>                                
                                <div class="mt-3 text-center"><button type="submit" style="margin-top:10px;" class="btn btn-primary">Save</button></div>
                            </div>
                            <div class="col-xl-6 col-sm-6 bor text-left"> 
                                <h5 class="mb-4 pb-2 text-center">Vehicle</h5>
                                <div class="text-center mb-4">
                                    <img src="@if(!empty($user->car_img)){{$user->car_img}}@else{{'https://cp.a2brx.com/images/drivers-bg.jpg'}}@endif" id="car"  alt="car" class="rounded" style="max-height: 250px;max-width: 100%;">
                                </div>
                                <div class="mb-4 text-center">
                                    <input type="radio" value="1" class="btn-check" @if($user->transport=='1'){{"checked"}}@endif name="transport" id="formRadios1" autocomplete="off">
                                    <label class="" for="formRadios1">Car <i class="mdi mdi-car-back"></i></label>
                                    <input type="radio" value="2" class="btn-check" @if($user->transport=='2'){{"checked"}}@endif name="transport" id="formRadios2" autocomplete="off">
                                    <label class="" for="formRadios2">Bicycle <i class="mdi mdi-bicycle"></i></label>                             
                                </div>                                  
                                <div class="mb-2">
                                    <label for="first-name-text">Make/Model</label>
                                    <input class="form-control" required type="text" name="car_info" value="{{ $user->car_info }}">
                                </div>                                
                                <div class="mb-2">
                                    <label for="driving-license">Driving License</label>
                                    <input class="form-control" required type="text" name="driving_license" value="{{ $user->driving_license }}">                                                
                                </div> 
                                <div class="mb-2"> 
                                    <label for="first-name-text">Upload a new car photo</label>                                  
                                    <input type="file" class="filestyle form-control" data-input="false" data-buttonname="btn-secondary" name="car_img" onchange='encodeImageFileAsURL(this);' accept="image/x-png,image/jpeg,image/jpg">                                   
                                </div>
                            </div>
                        </form>
                        <div class="col-xl-6 col-sm-6 text-center"> 
                            <h5 class="mb-4 pb-2">Statistics</h5>
                            <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Route#</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Waypoints</th>                                        
                                        <th>Completed</th> 
                                        <th>Salary</th>                                        
                                        <th>Action</th>                                                     
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <th scope="row">51</th>
                                        <td>04.28.2023</td>
                                        <td>Complete</td>
                                        <td>38</td>
                                        <td>34</td>
                                        <td>$180.00</td>
                                        <td><button type="button" class="btn btn-outline-secondary btn-sm waves-effect">View route</button></td>                                                    
                                    </tr>
                                    
                                </tbody>
                            </table>
                        </div>
                        </div>                       
                    </div>
                </div>                               
            </div>
            </div>
        </div>
        <!-- end row -->
        <script type='text/javascript'>
            function encodeImageFileAsURL(element) {
                if(element.files[0].size > 2097152){
                    alert("File is too big!");element.value = "";
                }
            }
        </script>
        <script src="https://maps.googleapis.com/maps/api/js?key={{config('app.googlemaps_apikey')}}&region=US&language=en&libraries=places&v=weekly&callback=initialize" defer></script>
        <script>
            function initialize() {
                var input = document.getElementById('searchTextField');
                var res1 = new google.maps.places.Autocomplete(input);
                var input2 = document.getElementById('family_address');
                var res2 = new google.maps.places.Autocomplete(input2);
            }
        </script>
@endsection

@section('footerScript')
<script src="{{ URL::asset('/js/bootstrap.bundle.min.js')}}"></script>
<script>
    $('#exampleModalScrollableOpen').on('click',function(){
        if($('#date_stat').val()!=''){
            var _this = $(this)
            var text = _this.text();
            _this.prop( "disabled", true );
            _this.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...');
            $('#exampleModalScrollableTitle span').text('('+$('#date_stat').val()+')');
            $.post(location.href, { _token: $('input[name="_token"]').val(), ajax_stat: 1, date_stat: $('#date_stat').val() }).done(function( data ) {
                $('#exampleModalScrollable').find('.modal-body').html(data);
                $('#exampleModalScrollable').modal('show');
                _this.prop( "disabled", false );
                _this.html(text);
            }).fail(function() {
                _this.prop( "disabled", false );
                _this.html(text);
                alert( "error" );
            });
        } else {
            alert("Date is required!");
        }
    });
</script>
            
@endsection