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
</style>
@endsection

@section('content')
 <!-- start page title -->
            <ul class="nav nav-tabs">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#info"><i class="mdi mdi-information-outline"></i> Information</a>
                </li>
                @if(isset($user_actions))
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#history"><i class="mdi mdi-history"></i> History</a>
                </li>
                @endif
            </ul>
            <div class="tab-content">
		        <div class="tab-pane fade show active" id="info">
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
                                                    <img style="max-width: inherit;width: 100%;position: relative;top: 50%;transform: translate(0, -50%)" id="user_img" src="{{ $user->image }}">
                                                </div> 
                                                <input class="form-control" type="file" name="image" onchange='encodeImageFileAsURL(this);' accept="image/x-png,image/jpeg,image/jpg">
                                                <small>Image cannot be larger than 2 mb</small>
                                                <div class="form-check">
                                                    <input type="checkbox" name="remove_photo" value="1" class="form-check-input" id="Check1">
                                                    <label class="form-check-label" for="Check1">Remove photo</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">First Name</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" required type="text" name="name" value="{{ $user->name }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Last Name</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" required type="text" name="last_name" value="{{ $user->last_name }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Email</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" required type="email" name="email" value="{{ $user->email }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Phone</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" required type="phone" name="phone" id="phone" value="{{ $user->phone }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">New Password</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" type="password" minlength="8" onfocus="this.removeAttribute('readonly');" readonly autocomplete="off" name="password" value="">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Confirm Password</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" type="password" minlength="8" onfocus="this.removeAttribute('readonly');" readonly autocomplete="off" name="password2" value="">
                                            </div>
                                        </div>
                                        @if($user->role=='driver')
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Driving License</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" type="text" name="driving_license" value="{{ $user->driving_license }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Driving License Photo</label>
                                            <div class="col-sm-10" style="margin-bottom: 5px;">
                                                <div style="max-width: 400px;">
                                                    <img style="max-width: inherit;width: 100%;" id="driving_license_img" src="{{ $user->driving_license_img }}">
                                                </div> 
                                                <input class="form-control" type="file" name="driving_license_img" onchange='encodeImageFileAsURL(this);' accept="image/x-png,image/jpeg,image/jpg">
                                                <small>Image cannot be larger than 2 mb</small>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">SSN</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" type="text" name="identification_cards" value="{{ $user->identification_cards }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Type transport</label>
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
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Car Info</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" type="text" name="car_info" value="{{ $user->car_info }}">
                                                <small>Example: Mercedes Benz Vito Red</small>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Car Photo</label>
                                            <div class="col-sm-10" style="margin-bottom: 5px;">
                                                <div style="max-width: 400px;">
                                                    <img style="max-width: inherit;width: 100%;" id="car_img" src="{{ $user->car_img }}">
                                                </div> 
                                                <input class="form-control" type="file" name="car_img" onchange='encodeImageFileAsURL(this);' accept="image/x-png,image/jpeg,image/jpg">
                                                <small>Image cannot be larger than 2 mb</small>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Payment Card</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" type="text" name="payment_card" value="{{ $user->payment_card }}">
                                            </div>
                                        </div>
                                        @endif
                                        @if($user->role!='medic')
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Pharmacy</label>
                                            <div class="col-sm-10">
                                                <select name="pharmacy" class="form-control">
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
                                        </div>
                                        @else
                                        <input type="hidden" name="pharmacy" value="{{$user->pharmacy_id}}">
                                        @endif
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Address</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" required id="searchTextField" type="text" name="address" value="{{ $user->address }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Apartment, STE</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" type="text" name="apartment" value="{{ $user->apartment }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Zip</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" required type="text" name="zip" value="{{ $user->zip }}">
                                            </div>
                                        </div>
                                        @if((Auth::user()->role == 'superadmin' || Auth::user()->role == 'admin'))
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Role</label>
                                            <div class="col-sm-10">
                                                <select name="role" class="form-control" required>
                                                    <option value="user" @if($user->role=='user') selected @endif>Patient</option>
                                                    <option value="driver" @if($user->role=='driver') selected @endif>Driver</option>
                                                    <option value="medic" @if($user->role=='medic') selected @endif>Medic</option>
                                                    <option value="logist" @if($user->role=='logist') selected @endif>Dispatcher</option>
                                                    <option value="sale" @if($user->role=='sale') selected @endif>Sale Manager</option>
                                                    @if(Auth::user()->role == 'superadmin')
                                                    <option value="dispadmin" @if($user->role=='dispadmin') selected @endif>Dispatcher Admin</option>
                                                    <option value="admin" @if($user->role=='admin') selected @endif>Admin</option>
                                                    @endif
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group row admin_zone_id" @if(!($user->role=='admin' || $user->role=='driver')){{'style=display:none;'}}@endif>
                                            <label for="zone_id" class="col-sm-2 col-form-label">Admin Zone</label>
                                            <div class="col-sm-10">
                                                <select name="zone_id" id="zone_id" class="form-control" required>
                                                    <option value="">Not Selected</option>
                                                    @foreach($admin_areas as $admin_area)
                                                    <option value="{{$admin_area->id}}" @if($admin_area->id==$user->zone_id){{'selected'}}@endif>{{$admin_area->name}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        @endif
                                        <button type="submit" style="margin-top:10px;" class="btn btn-primary">Save</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- end col -->
                    </div>
                </div>
                @if(isset($user_actions))
                <div class="tab-pane fade" id="history">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="table-rep-plugin">
                                        <div class="table mb-0" data-pattern="priority-columns">
                                            <h1>User Change History</h1>
                                            <table id="mytable2" class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Date/time</th>
                                                        <th>Event</th>
                                                        <th>User ID</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($user_actions as $user_action)
                                                    <tr>
                                                        <td>{{date('m/d/Y g:i A', strtotime($user_action->created))}}</td>
                                                        <td>{{$user_action->type}} @if(!empty($user_action->comment)) - ({{$user_action->comment}}) @endif</td>
                                                        <td>{{$user_action->action_user_id}}</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- end col -->
                    </div>
                </div>
                @endif
            </div>
@endsection
@section('footerScript')
 <!-- end row -->
 <script type='text/javascript'>
function encodeImageFileAsURL(element) {
    if(element.files[0].size > 2097152){
        alert("File is too big!");element.value = "";
    }
}
$('[name="role"]').on('change',function(){
    if($(this).val()=='admin' || $(this).val()=='driver'){
        $('.admin_zone_id').show();
    } else {
        $('.admin_zone_id').hide();
    }
});
</script>
<script src="https://maps.googleapis.com/maps/api/js?key={{config('app.googlemaps_apikey')}}&region=US&language=en&libraries=places&v=weekly&callback=initialize" defer></script>
<script>
    function initialize() {
        var input = document.getElementById('searchTextField');
        new google.maps.places.Autocomplete(input);
    }
</script>
@endsection