@extends('layouts.master')

@section('title') Edit User @endsection
@section('headerCss')
<style>
.pac-container {
    z-index:10000 !important;

}
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
.deladd {
    padding: 10px;
    border: solid 2px #9b9a9f;
    border-radius: 10px;
    margin: 30px 10px 10px 10px;
    box-shadow: 0 -3px 31px 0 rgba(0, 0, 0, 0.05), 0 6px 20px 0 rgba(0, 0, 0, 0.02);
}
.primary {
    float: right;
    margin-top: -30px;
}
</style>
@endsection
@section('content')
 <!-- start page title -->
                    <div class="row">

                   
                    </div>
                    <!-- end page title -->
                    <div class="modal bs-example-modal" id="modal" style="display:none;" tabindex="1" role="dialog">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <form method="POST" enctype="multipart/form-data" id="family-form">
                                <div class="modal-header">
                                    <h5 class="modal-title mt-0">Add Family member</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body" style="padding: 30px;">
                                    @csrf
                                    <input type="hidden" name="family_members" value="1">
                                    <div class="form-group row">
                                    
                                        <div class="col-sm-12">
                                            <select id="family_type" name="family_type" required class="form-control">
                                                <option value="">Choose an option...</option>
                                                <option value="Husband">Husband</option>
                                                <option value="Wife">Wife</option>
                                                <option value="Brother">Brother</option>
                                                <option value="Sister">Sister</option>
                                                <option value="Father">Father</option>
                                                <option value="Mother">Mother</option>
                                                <option value="Son">Son</option>
                                                <option value="Daughter">Daughter</option>
                                                <option value="Grandfather">Grandfather</option>
                                                <option value="Grandmother">Grandmother</option>
                                                <option value="Home Attendant">Home Attendant</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <div class="col-sm-12">
                                            <input class="form-control" required type="text" id="family_name" name="family_name" placeholder="Name">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <div class="col-sm-12">
                                            <input type="text" class="form-control" required id="family_phone" name="family_phone" placeholder="Phone">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <div class="col-sm-12">
                                            <input type="text" class="form-control" required id="family_address" name="family_address" placeholder="Address">
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary btn-lg btn-block waves-effect waves-light">Save</button>
                                </div>
                                </form>
                            </div>
                            <!-- /.modal-content -->
                        </div>
                        <!-- /.modal-dialog -->
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
                                        @if(!empty($user->pharmacy_id))
                                        <div class="col-xl-3 col-sm-6 bor text-center">     
                                            <h6 class="mb-1 font-size-16 mt-4">{{$pharmacys[$user->pharmacy_id]->name}}</h6> 
                                            @if(!empty($user_zone))<p class="text-muted mb-0">Zone: {{$user_zone->name}}</p>@endif
                                        </div>
                                        @endif
                                        <div class="col-xl-3 col-sm-6 bor text-center rating">    
                                            <h6 class="mb-1 font-size-16 mt-4">Customer rating</h6>    
                                            <i class="mdi mdi-star-outline"></i><i class="mdi mdi-star-outline"></i><i class="mdi mdi-star-outline"></i><i class="mdi mdi-star-outline"></i><i class="mdi mdi-star-outline"></i>                                    
                                        </div>
                                        <div class="col-xl-3 col-sm-6 text-center">
                                            <h6 class="mb-1 font-size-16 mt-2">Total orders: {{$orders_stat->count}}</h6>
                                            <h6 class="mb-3 font-size-16 mt-2">Co-pay: ${{number_format($orders_stat->copay,2)}}</h6>
                                            <a href="/orders/{{$user->pharmacy_id}}/add?patient={{$user->id}}"><button type="button" class="btn btn-outline-primary btn-sm waves-effect waves-light">New Order <i class="mdi mdi-car"></i></button></a>
                                            <a href="/orders/{{$user->pharmacy_id}}?search={{$user->name}} {{$user->last_name}}">
                                                <button type="button" class="btn btn-outline-primary btn-sm waves-effect waves-light">History <i class="mdi mdi-history"></i></button>
                                            </a>
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
                                        <div class="col-xl-3 col-sm-6 bor"> 
                                            <h5 class="mb-4 pb-2">Profile</h5>
                                            <form method="post" enctype="multipart/form-data">
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
                                                    <label for="home-phone-text">Home Phone</label>
                                                    <input class="form-control" type="phone" name="home_phone" id="home_phone" value="{{ $user->home_phone }}">                                                  
                                                </div>
                                                <div class="mb-2" style="display:none;">
                                                    <label for="pharmacy-text">Pharmacy</label>                                                   
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
                                                <div class="deladd"> 
                                                    <h5 style="border:0;">Delivery address #1</h5>
                                                    <div class="primary">
                                                        <input class="form-check-input" type="radio" name="primary_address" value="1" id="formRadiosRight1" @if($user->primary_address==1){{'checked'}}@endif>
                                                        <label class="form-check-label" for="formRadiosRight1">Primary</label>
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
                                                </div>
                                                <div class="deladd"> 
                                                    <h5 style="border:0;">Delivery address #2</h5>
                                                    <div class="primary">
                                                        <input class="form-check-input" type="radio" name="primary_address" value="2" id="formRadiosRight2" @if($user->primary_address==2){{'checked'}}@endif>
                                                        <label class="form-check-label" for="formRadiosRight2">Primary</label>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label for="Address-text">Address</label>                                                   
                                                        <input class="form-control" id="searchTextField2" type="text" name="address2" value="{{ $user->address2 }}">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label for="apt-text">Apartment, STE</label>                                                    
                                                        <input class="form-control" type="text" name="apartment2" value="{{ $user->apartment2 }}">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label for="zip-text">Zip</label>                                                   
                                                        <input class="form-control" type="text" name="zip2" value="{{ $user->zip2 }}">                                                    
                                                    </div>
                                                </div>
                                                <div class="deladd"> 
                                                    <h5 style="border:0;">Delivery address #3</h5>
                                                    <div class="primary">
                                                        <input class="form-check-input" type="radio" name="primary_address" value="3" id="formRadiosRight3" @if($user->primary_address==3){{'checked'}}@endif>
                                                        <label class="form-check-label" for="formRadiosRight3">Primary</label>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label for="Address-text">Address</label>                                                   
                                                        <input class="form-control" id="searchTextField3" type="text" name="address3" value="{{ $user->address3 }}">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label for="apt-text">Apartment, STE</label>                                                    
                                                        <input class="form-control" type="text" name="apartment3" value="{{ $user->apartment3 }}">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label for="zip-text">Zip</label>                                                   
                                                        <input class="form-control" type="text" name="zip3" value="{{ $user->zip3 }}">                                                    
                                                    </div>
                                                </div>
                                                <div class="mt-3 text-center"><button type="submit" style="margin-top:10px;" class="btn btn-primary">Save</button></div>
                                            </form>
                                        </div>
                                        <div class="col-xl-3 col-sm-6 bor text-left"> 
                                            <h5 class="mb-4 pb-2 text-center">Family members</h5>
                                            @foreach($family_members as $family_member)
                                                <div class="row" style="padding: 0px 18px;">
                                                    <div class="col-sm-12" style="margin-bottom: 7px;"><span class="badge badge-info">{{$family_member->family_type}}</span></div>
                                                    <div class="col-sm-10">{{$family_member->family_name}}, {{$family_member->family_phone}} </div>
                                                    <div class="col-sm-2" style="text-align: end;"><a href="#" onclick="event.preventDefault();if(confirm('Are you sure?')){$('#remove_form{{$family_member->id}}').submit();}"><i class="fas fa-trash-alt" style="font-size:18px;color:red;"></i></a></div>
                                                    <div class="col-sm-12 mt-1">{{$family_member->family_address}}</div>
                                                </div>
                                                <form method="POST" class="d-none" id="remove_form{{$family_member->id}}">
                                                    @csrf
                                                    <input type="hidden" name="family_member_remove" value="{{$family_member->id}}">
                                                </form>
                                                <hr>
                                            @endforeach
                                            <div style="margin-top: 50px;"><a href="#" id="add_family_members">
                                                <button type="button" class="btn btn-secondary btn-sm btn-block waves-effect">Add Family member <i class="fas fa-plus-circle" style="font-size:12px;"></i> </button>
                                            </a></div>

                                        </div>
                                        <div class="col-xl-6 col-sm-6 text-center"> 
                                            <ul class="nav nav-tabs nav-tabs-custom" role="tablist" style="margin-top: -12px;">
                                                <li class="nav-item">
                                                    <a class="nav-link font-size-16 active" data-toggle="tab" href="#orders" role="tab">
                                                        <span class="d-block d-sm-none"><i class="fas fa-home"></i></span>
                                                        <span class="d-none d-sm-block">Last Orders</span>
                                                    </a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link font-size-16" data-toggle="tab" href="#changes" role="tab">
                                                        <span class="d-block d-sm-none"><i class="far fa-user"></i></span>
                                                        <span class="d-none d-sm-block">Profile changes</span>
                                                    </a>
                                                </li>
                                            </ul>

                                            <!-- Tab panes -->
                                            <div class="tab-content">
                                                <div class="tab-pane p-2 fade show active" id="orders" role="tabpanel">
                                                    <div class="table-responsive">
                                                        <table class="table mb-0">
                                                            <thead>
                                                                <tr>
                                                                    <th>Order #</th>
                                                                    <th>Date</th>
                                                                    <th>Status</th>
                                                                    <th>Driver</th>
                                                                    <th>Action</th>                                                     
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach($orders as $order)
                                                                <tr>
                                                                    <th scope="row">{{$order->id}}</th>
                                                                    <td>{{date('m/d/Y g:i A', strtotime($order->created))}}</td>
                                                                    <td><span style="font-size: 11px;padding: 4px 5px;border-radius: 3px;box-shadow: 0 -3px 31px 0 rgb(64 59 59 / 5%), 0 6px 20px 0 rgb(58 57 57 / 20%);" class="badge badge-pill badge-{{$order->statusecolor}}">{{$order->statusename}}</span></td>
                                                                    <td>{{$order->drivername}} {{$order->driverlast_name}}</td>
                                                                    <td><a href="/orders/{{$order->pharmacy_id}}/show/{{$order->id}}"><button type="button" class="btn btn-outline-secondary waves-effect">View order</button></a></td>                                                    
                                                                </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                                <div class="tab-pane p-2" id="changes" role="tabpanel">
                                                    <div class="table-responsive">
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
                            var input = document.getElementById('searchTextField2');
                            var res1 = new google.maps.places.Autocomplete(input);
                            var input = document.getElementById('searchTextField3');
                            var res1 = new google.maps.places.Autocomplete(input);
                            var input2 = document.getElementById('family_address');
                            var res2 = new google.maps.places.Autocomplete(input2);
                        }
                    </script>
@endsection
@section('footerScript')
<script>
    $(document).ready(function() {
        $('body').on('click','.close',function() {
            $('#modal').fadeOut(300);
            $('#modal2').fadeOut(300);
        });
        $('#add_family_members').on('click', function(e) {
            e.preventDefault;
            $('#modal').fadeIn(300);
        });
    });
</script>
@endsection