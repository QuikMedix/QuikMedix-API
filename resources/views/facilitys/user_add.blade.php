@extends('layouts.master')

@section('title') Add Facility @endsection

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
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Name</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" required type="text" name="name" value="{{ $input['name'] }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Description</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" required type="text" name="last_name" value="{{ $input['last_name'] }}">
                                            </div>
                                        </div>
                                        <div class="form-group row" style="display:none;">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Email</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" type="email" name="email" value="{{ $input['email'] }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Primary phone</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" required type="phone" name="phone" id="phone" value="{{ $input['phone'] }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Secondary phone</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" type="phone" name="home_phone" id="home_phone" value="{{ $input['home_phone'] }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Day of Birth</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" type="date" name="birth_date" id="birth_date" value="{{ $input['birth_date'] }}">
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