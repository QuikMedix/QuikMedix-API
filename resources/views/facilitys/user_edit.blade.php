@extends('layouts.master')

@section('title') Edit Facility @endsection
@section('headerCss')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
.pac-container {
    z-index:10000 !important;

}
</style>
@endsection
@section('content')
 <!-- start page title -->
                    <div class="row">

                   
                    </div>
                    <div class="modal bs-example-modal" id="modal2" style="display:none;" tabindex="1" role="dialog">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <form method="POST" enctype="multipart/form-data" id="family-form">
                                <div class="modal-header">
                                    <h5 class="modal-title mt-0">Add Additional Recipients</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body" style="padding: 30px;">
                                    @csrf
                                    <input type="hidden" name="additional_recipients" value="1">
                                    <div class="form-group row">
                                    
                                        <div class="col-sm-12">
                                            <select id="family_type" name="family_type" required class="form-control">
                                                <option value="Additional Recipient">Additional Recipient</option>                                            
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <div class="col-sm-12">
                                            <input class="form-control" required type="text" id="family_name" name="family_name" placeholder="First name">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <div class="col-sm-12">
                                            <input class="form-control" required type="text" id="family_name2" name="family_name2" placeholder="Last Name">   <!-- добавил -->
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <div class="col-sm-12">
                                            <input type="text" class="form-control" required id="family_phone2" name="family_phone" placeholder="Phone">
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
                        <div class="col-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 style="background: #7a6fbe;color: #ffffff;padding: 5px;text-align: center;">Profile</h5>
                                    <form method="post" enctype="multipart/form-data">
                                        @csrf
                                        <input type="hidden" name="save" value="1">
                                        @if($alert!='') 
                                            <div class="alert alert-danger" role="alert">{{ $alert }}</div>
                                        @endif
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Name</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" required type="text" name="name" value="{{ $user->name }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Description</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" required type="text" name="last_name" value="{{ $user->last_name }}">
                                            </div>
                                        </div>
                                        <div class="form-group row" style="display:none;">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Email</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" type="email" name="email" value="{{ $user->email }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Primary phone</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" required type="phone" name="phone" id="phone" value="{{ $user->phone }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Secondary phone</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" type="phone" name="home_phone" id="home_phone" value="{{ $user->home_phone }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Pharmacy</label>
                                            <div class="col-sm-10">
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
                                        </div>
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
                                        <button type="submit" style="margin-top:10px;" class="btn btn-primary">Save</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card">
                                <div class="card-body" style="min-height: 200px;">
                                    <h5 style="background: #7a6fbe;color: #ffffff;padding: 5px;text-align: center;">Facility patients</h5>
                                    <div style="padding: 10px;background: #efefef;border-radius: 5px;margin: 5px;">  
                                    <h5 class="my-3 text-black">Select from database</h5>
                                    <form method="post" enctype="multipart/form-data">
                                        @csrf
                                    <select class="select2 form-control select2-multiple" multiple="multiple" name="patients_db[]" multiple data-placeholder="Choose ...">
                                        <optgroup label="Saved Patients">
                                            @foreach($patients as $patient)
                                            <option value="{{$patient->id}}">{{$patient->name}} {{$patient->last_name}} - {{$patient->phone}}</option>
                                            @endforeach
                                        </optgroup>                                                    
                                    </select>
                                    <button class="btn btn-primary form-control mt-2">Save</button>
                                    </form>
                                    </div>
                                    <div style="padding: 10px;background: #efefef;border-radius: 5px;margin: 5px;">
                                    <h5 class="my-3 text-black">Add additional recipients</h5>
                                    @foreach($additional_recipients as $family_member)
                                        <div class="row" style="padding: 0px 18px;">
                                            <div class="col-sm-12" style="margin-bottom: 7px;"><span class="badge badge-info">{{$family_member->family_type}}</span></div>
                                            <div class="col-sm-10">{{$family_member->family_name}}, {{$family_member->family_phone}} </div>
                                            <div class="col-sm-2" style="text-align: end;"><a href="#" onclick="event.preventDefault();if(confirm('Are you sure?')){$('#remove_form_recipient{{$family_member->id}}').submit();}"><i class="fas fa-trash-alt" style="font-size:18px;color:red;"></i></a></div>
                                        </div>
                                        <form method="POST" class="d-none" id="remove_form_recipient{{$family_member->id}}">
                                            @csrf
                                            <input type="hidden" name="additional_recipient_remove" value="{{$family_member->id}}">
                                        </form>
                                        <hr>
                                    @endforeach
                                    <div style="text-align: center;"><a href="#" id="add_additional_recipients">
                                        <button type="button" class="btn btn-secondary btn-sm waves-effect">Add Facility recipients <i class="fas fa-plus-circle" style="font-size:12px;"></i> </button>
                                    </a></div> 
                                    </div>                                   
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
                            var res1 = new google.maps.places.Autocomplete(input);
                            var input2 = document.getElementById('family_address');
                            var res2 = new google.maps.places.Autocomplete(input2);
                        }
                    </script>
@endsection
@section('footerScript')
<script src="{{ URL::asset('/js/select2.min.js')}}"></script>
<script src="{{ URL::asset('/js/pages/form-advanced.init.js')}}"></script>
<script>
    $(document).ready(function() {
        $('body').on('click','.close',function() {
            $('#modal').fadeOut(300);
            $('#modal2').fadeOut(300);
        });
        $('#add_additional_recipients').on('click', function(e) {
            e.preventDefault;
            $('#modal2').fadeIn(300);
        });
    });
</script>
@endsection