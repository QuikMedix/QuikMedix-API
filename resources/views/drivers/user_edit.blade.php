@extends('layouts.master')

@section('title') Edit User @endsection

@section('headerCss')
<style>
.btn-check {
    position: absolute;
    clip: rect(0,0,0,0);
    pointer-events: none;
}
.btn-check:active+.btn-outline-primary, .btn-check:checked+.btn-outline-primary, .btn-outline-primary.active, .btn-outline-primary.dropdown-toggle.show, .btn-outline-primary:active {
    color: #fff;
    border-color: #7a6fbe;
    background-color: #7a6fbe;
}
.btn-check:active+.btn-outline-primary img, .btn-check:checked+.btn-outline-primary img, .btn-outline-primary.active img, .btn-outline-primary.dropdown-toggle.show img, .btn-outline-primary:active img {
    filter: invert(1);
}
.btn-outline-primary img {
    width: 36px;
    margin-right: 4px;
    filter: invert(44%) sepia(74%) saturate(341%) hue-rotate(208deg) brightness(90%) contrast(90%);
}
.btn-outline-primary:hover img {
    filter: invert(1);
}
.user-phnew  {
    text-align: center;
}
.user-phnew  {
    align-items: center;
    justify-content: center;
}
.user-phnew img {
    width: 60%;
    border-radius: 50%;
    border: solid 7px #ebebeb;
    -webkit-box-shadow: 0px 5px 10px 2px rgb(34 60 80 / 20%);
    -moz-box-shadow: 0px 5px 10px 2px rgba(34, 60, 80, 0.2);
    box-shadow: 0px 5px 10px 2px rgb(34 60 80 / 20%);
}
.user-phnew .bootstrap-filestyle {
    top: -22px;
    align-items: center;
    justify-content: center;
}

.user-phnew .btn-secondary {
    color: #000;
    background-color: #6c757d;
    border-color: #ececec;
    background: #ebebeb;
}
.user-phnew2  {
    align-items: center;
    justify-content: center;
    text-align: center;
}
.user-phnew2 .bootstrap-filestyle {
    top: -8px;
    align-items: center;
    justify-content: center;
}
</style>
@endsection

@section('content')
 <!-- start page title -->
                    <div class="row">

                   
                    </div>
                    <!-- end page title -->

                    <form method="post" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="save" value="1">
                    @if($alert!='') 
                        <div class="alert alert-danger" role="alert">{{ $alert }}</div>
                    @endif
				    <div class="row">
                        <div class="col-3">
                            <div class="card">
                                <div class="card-body" style="min-height: 200px;"><h5 style="background: #7a6fbe;color: #ffffff;padding: 5px;text-align: center;">Photo </h5>
                                    <div style="text-align: center;"  class="user-phnew">
                                        <img style="width: 60%;" class="user-phnew" id="user_img" src="{{ $user->image }}">
                                        <input type="file" class="filestyle form-control" data-input="false" data-buttonname="btn-secondary" name="image" onchange='encodeImageFileAsURL(this);' accept="image/x-png,image/jpeg,image/jpg">
                                    </div>
                                    <div class="form-group row">
                                        <span class="badge bg-primary" style="color: white;font-size: 100%;margin: 10px;">Vehicle type</span>
                                        <div class="col-sm-10">
                                            <input type="radio" value="1" class="btn-check" @if($user->transport=='1'){{"checked"}}@endif name="transport" id="success-outlined" autocomplete="off" checked>
                                            <label class="btn btn-outline-primary" for="success-outlined">
                                                <img src="/images/delivery-truck.svg" alt="car"> Car
                                            </label>
                                            <input type="radio" value="2" class="btn-check" @if($user->transport=='2'){{"checked"}}@endif name="transport" id="danger-outlined" autocomplete="off">
                                            <label class="btn btn-outline-primary" for="danger-outlined">
                                                <img src="/images/bicycle.svg" alt="Bicycle"> Bicycle
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
						<div class="col-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 style="background: #7a6fbe;color: #ffffff;padding: 5px;text-align: center;">Profile</h5>

                                        <div class="form-group row">                                            
                                            <div class="col-sm-6">
                                                <label for="example-text-input" class="col-form-label">First Name</label>
                                                <input class="form-control" required type="text" name="name" value="{{ $user->name }}">
                                            </div>
                                            <div class="col-sm-6">
                                                <label for="example-text-input" class="col-form-label">Last Name</label>                                            
                                                <input class="form-control" required type="text" name="last_name" value="{{ $user->last_name }}">
                                            </div>
                                        </div>                                    
                                        <div class="form-group row">                                            
                                            <div class="col-sm-6">
                                                <label for="example-text-input" class="col-form-label">Phone</label>
                                                <input class="form-control" required type="phone" name="phone" id="phone" value="{{ $user->phone }}">                                                    
                                            </div>
                                            <div class="col-sm-6">
                                                <label for="example-text-input" class="col-form-label">Email</label>
                                                <input class="form-control" required type="email" name="email" value="{{ $user->email }}">                                                
                                            </div>                                                                                
                                        </div>
                                        <div class="form-group row">                                            
                                            <div class="col-sm-6">
                                                <label for="example-text-input" class="col-form-label">Driving License</label>                                           
                                                <input class="form-control" required type="text" name="driving_license" value="{{ $user->driving_license }}">
                                            </div> 
                                            <div class="col-sm-6">
                                                <label for="example-text-input" class="col-form-label">SSN</label>
                                                <input class="form-control" required type="text" name="identification_cards" value="{{ $user->identification_cards }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">                                            
                                            <div class="col-sm-6">
                                                <label for="example-text-input" class="col-form-label">Vehicle</label>                                     
                                                <input class="form-control" required type="text" name="car_info" value="{{ $user->car_info }}">
                                            </div> 
                                            <div class="col-sm-6">
                                                <label for="example-text-input" class="col-form-label">Payment Card</label>                                          
                                                <input class="form-control" required type="text" name="payment_card" value="{{ $user->payment_card }}">
                                            </div>
                                        </div>  
                                        <div class="form-group row">                                            
                                            <div class="col-sm-6">
                                                <label for="example-text-input" class="col-form-label">Address</label>                                            
                                                <input class="form-control" required id="searchTextField" type="text" name="address" value="{{ $user->address }}">                                               
                                            </div> 
                                            <div class="col-sm-3">
                                                <label for="example-text-input" class="col-form-label">Apartment, STE</label>                                            
                                                <input class="form-control" type="text" name="apartment" value="{{ $user->apartment }}">                                               
                                            </div>
                                            <div class="col-sm-3">
                                                <label for="example-text-input" class="col-form-label">Zip</label>                                            
                                                <input class="form-control" required type="text" name="zip" value="{{ $user->zip }}">                                               
                                            </div>
                                        </div> 
                                     
                                        <div class="form-group">
                                            <label for="example-text-input" class="col-form-label">Pharmacy</label>
                                            
                                                <select name="pharmacy" class="form-control" disabled style="cursor:not-allowed;">
                                                    <option value="">-----</option>
                                                    @foreach($pharmacys as $pharmacy)
                                                        @if($pharmacy->id==$user->pharmacy_id)
                                                            <option value="{{ $pharmacy->id }}" selected>{{ $pharmacy->name }} | {{ $pharmacy->address }}</option>
                                                        @else
                                                            <option value="{{ $pharmacy->id }}">{{ $pharmacy->name }} | {{ $pharmacy->address }}</option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            
                                        </div>
                                        <div class="col-12">
								            <button type="submit" style="margin-top:10px;" class="btn btn-primary">Save</button>
						                </div>                                        
								</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="card">
                                <div class="card-body" style="min-height: 200px;"><h5 style="background: #7a6fbe;color: #ffffff;padding: 5px;text-align: center;">Additional info</h5>  
                                        
                                <div class="row user-phnew2">
                                        <div class="col-12">
                                            <span class="badge bg-primary" style="color: white;font-size: 100%;margin: 10px;">Driving License Photo</span>                                                
                                            <a class="image-popup-no-margins" href="{{ $user->driving_license_img }}">
                                            <img style="max-width: inherit;max-width: 200px;height: auto;display: block;margin: 10px auto;" class="img-fluid" id="driving_license_img" src="{{ $user->driving_license_img }}"> 
                                            </a>                                       
                                            <input type="file" class="filestyle form-control" data-input="false" data-buttonname="btn-secondary" name="driving_license_img" onchange='encodeImageFileAsURL(this);' accept="image/x-png,image/jpeg,image/jpg">
                                        </div>
                                        <div class="col-12">
                                            <span class="badge bg-primary" style="color: white;font-size: 100%;margin: 10px;">Vehicle Photo</span>
                                            <a class="image-popup-no-margins" href="{{ $user->car_img }}">
                                            <img style="max-width: inherit;max-width: 200px;height: auto;display: block;margin: 10px auto;" class="img-fluid"  id="car_img" src="{{ $user->car_img }}"> 
                                            </a>                                                                                       
                                            <input type="file" class="filestyle form-control" data-input="false" data-buttonname="btn-secondary" name="car_img" onchange='encodeImageFileAsURL(this);' accept="image/x-png,image/jpeg,image/jpg">
                                        </div>
                                </div>
									
                                </div>
                            </div>
                        </div>
						
				</div>               
                </form>
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
                            new google.maps.places.Autocomplete(input);
                        }
                    </script>
                    <script src="{{ URL::asset('/libs/jquery/jquery.min.js')}}"></script>  
                    <script src="{{ URL::asset('/js/bootstrap-filestyle.min.js')}}"></script>
                    
@endsection