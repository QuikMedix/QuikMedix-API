@extends('layouts.master')

@section('title') Add User @endsection

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
                                    <form method="post" enctype="multipart/form-data">
                                        @csrf
                                        <input type="hidden" name="save" value="1">
                                        @if($alert!='') 
                                            <div class="alert alert-danger" role="alert">{{ $alert }}</div>
                                        @endif
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Image</label>
                                            <div class="col-sm-10" style="margin-bottom: 5px;">
                                                <div style="max-width: 200px;height: 200px;overflow: hidden;border-radius: 50%;">
                                                    <img style="max-width: inherit;width: 100%;position: relative;top: 50%;transform: translate(0, -50%)" id="user_img" src="{{ $input['image'] }}">
                                                </div> 
                                                <input class="form-control" type="file" name="image" onchange='encodeImageFileAsURL(this);' accept="image/x-png,image/jpeg,image/jpg">
                                                <small>Image cannot be larger than 2 mb</small>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">First Name</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" required type="text" name="name" value="{{ $input['name'] }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Last Name</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" required type="text" name="last_name" value="{{ $input['last_name'] }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Email</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" required type="email" name="email" value="{{ $input['email'] }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Phone</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" required type="phone" name="phone" id="phone" value="{{ $input['phone'] }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Password</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" required type="text" minlength="8" onfocus="this.removeAttribute('readonly');" readonly autocomplete="off" name="password" value="{{ $input['password'] }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Driving License</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" required type="text" name="driving_license" value="{{ $input['driving_license'] }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Driving License Photo</label>
                                            <div class="col-sm-10" style="margin-bottom: 5px;">
                                                <div style="max-width: 400px;">
                                                    <img style="max-width: inherit;width: 100%;" id="driving_license_img" src="{{ $input['driving_license_img'] }}">
                                                </div> 
                                                <input class="form-control" type="file" name="driving_license_img" onchange='encodeImageFileAsURL(this);' accept="image/x-png,image/jpeg,image/jpg">
                                                <small>Image cannot be larger than 2 mb</small>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">SSN</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" required type="text" name="identification_cards" value="{{ $input['identification_cards'] }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Type transport</label>
                                            <div class="col-sm-10">
                                                <input type="radio" value="1" class="btn-check" @if($input['transport']=='1'){{"checked"}}@endif name="transport" id="success-outlined" autocomplete="off" checked>
                                                <label class="btn btn-outline-primary" for="success-outlined">
                                                    <img src="/images/delivery-truck.svg" alt="car"> Car
                                                </label>
                                                <input type="radio" value="2" class="btn-check" @if($input['transport']=='2'){{"checked"}}@endif name="transport" id="danger-outlined" autocomplete="off">
                                                <label class="btn btn-outline-primary" for="danger-outlined">
                                                    <img src="/images/bicycle.svg" alt="Bicycle"> Bicycle
                                                </label>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Car Info</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" required type="text" name="car_info" value="{{ $input['car_info'] }}">
                                                <small>Example: Mercedes Benz Vito Red</small>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Payment Card</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" required type="text" name="payment_card" value="{{ $input['payment_card'] }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Pharmacy</label>
                                            <div class="col-sm-10">
                                                <select name="pharmacy" class="form-control" disabled style="cursor:not-allowed;">
                                                    <option value="">-----</option>
                                                    @foreach($pharmacys as $pharmacy)
                                                        @if($pharmacy->id==$input['pharmacy'])
                                                            <option value="{{ $pharmacy->id }}" selected>{{ $pharmacy->name }} | {{ $pharmacy->address }}</option>
                                                        @else
                                                            <option value="{{ $pharmacy->id }}">{{ $pharmacy->name }} | {{ $pharmacy->address }}</option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Address</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" required id="searchTextField" type="text" name="address" value="{{ $input['address'] }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Apartment, STE</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" type="text" name="apartment" value="{{ $input['apartment'] }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Zip</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" required type="text" name="zip" value="{{ $input['zip'] }}">
                                            </div>
                                        </div>
                                        <button type="submit" style="margin-top:10px;" class="btn btn-primary">Save</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- end col -->
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
                            new google.maps.places.Autocomplete(input);
                        }
                    </script>
                    
@endsection