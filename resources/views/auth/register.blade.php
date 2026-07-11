@extends('layouts.auth-master')

@section('title', 'Register')

@section('headerCss')

@endsection

@section('content')
 <div class="account-pages pt-sm-3">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-12 col-lg-12 col-xl-12">
                    <div class="card overflow-hidden">
                        <div class="card-body pt-0">
                            <h3 class="text-center mt-4">
                                <a href="/" class="logo logo-admin"><img src="{{ URL::asset('/images/logo-cp.png')}}"  height="80" alt="logo"></a>
                            </h3>
                            <div class="p-3">
                                <h2 class="text-muted mb-1 text-center">Registration</h2>                                
                                <div class="row">
                                <div class="col-md-6">
                                <h5 style="background: #7a6fbe;color: #ffffff; padding: 5px;text-align: center;margin: 20px 0;">Pharmacy information</h5>
                                    <form class="form-horizontal" method="POST" action="{{ route('register') }}" enctype="multipart/form-data">
                                    @csrf

                                    @if(isset($_GET["ref"]))
                                    <input type="hidden" name="ref_id" value="{{$_GET["ref"]}}">
                                    @endif
                                    <div class="form-group">
                                        <label for="pharmacyName">Pharmacy Name *</label>
                                          <input type="text" class="form-control @error('pharmacyName') is-invalid @enderror" name="pharmacyName" value="{{ old('pharmacyName') }}" required id="pharmacyName" placeholder="" autocomplete="pharmacyName">
                                        @error('pharmacyName')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="pharmacyEmail">Pharmacy E-Mail Address *</label>
                                          <input type="email" class="form-control @error('pharmacyEmail') is-invalid @enderror" name="pharmacyEmail" value="{{ old('pharmacyEmail') }}" required id="pharmacyEmail" placeholder="" autocomplete="pharmacyEmail">
                                        @error('pharmacyEmail')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                    

                                    <div class="form-group">
                                        <label for="pharmacyPhone">Pharmacy Phone *</label>
                                          <input type="text" class="form-control @error('pharmacyPhone') is-invalid @enderror" name="pharmacyPhone" value="{{ old('pharmacyPhone') }}" required id="pharmacyPhone" placeholder="" autocomplete="pharmacyPhone">
                                        @error('pharmacyPhone')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="pharmacyAddress">Pharmacy Address *</label>
                                          <input type="text" class="form-control @error('pharmacyAddress') is-invalid @enderror" name="pharmacyAddress" value="{{ old('pharmacyAddress') }}" required id="pharmacyAddress" placeholder="" autocomplete="off">
                                        @error('pharmacyAddress')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label for="pharmacyWebsite">Pharmacy Website (Optional)</label>
                                          <input type="text" class="form-control" name="pharmacyWebsite" value="{{ old('pharmacyWebsite') }}" id="pharmacyWebsite" placeholder="" autocomplete="off">
                                        @error('pharmacyWebsite')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="pharmacyLogo">Pharmacy Logo (Optional)</label>
                                        <input type="file" name="pharmacyLogo" id="pharmacyLogo" accept="image/x-png,image/jpeg,image/jpg" class="filestyle" data-buttonname="btn-secondary">
                                        @error('pharmacyLogo')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h5 style="background: #7a6fbe;color: #ffffff; padding: 5px;text-align: center;margin: 20px 0;">User Information</h5>
                                    <div class="form-group">
                                        <label for="name">First Name *</label>
                                        <input type="text" name="name" value="{{ old('name') }}" required autocomplete="name" class="form-control @error('name') is-invalid @enderror" autofocus id="name" placeholder="">
                                        @error('name')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="last_name">Last Name *</label>
                                        <input type="text" name="last_name" value="{{ old('last_name') }}" required autocomplete="last_name" class="form-control @error('last_name') is-invalid @enderror" autofocus id="last_name" placeholder="">
                                        @error('last_name')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="useremail">Your E-Mail Address *</label>
                                          <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" id="useremail" required placeholder="" autocomplete="email">
                                        @error('email')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="phone">Your Phone *</label>
                                          <input type="phone" name="phone" required class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" id="phone" placeholder="" autocomplete="phone">
                                        @error('phone')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div> 

                                    <div class="form-group">
                                        <label for="userpassword">{{ __('Password') }} *</label>
                                        <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password" id="userpassword" placeholder="">
                                        @error('password')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="userpassword">{{ __('Confirm Password') }} *</label>
                                    <input type="password" required name="password_confirmation" class="form-control" id="userconfirmpassword" placeholder="">
                                    </div>
			
		</div>
		
		
		 <div class="col-md-12 col-lg-12 col-xl-12">
			<div class="col-12 text-center" style="margin: 20px 0;">
			 <button class="btn btn-primary w-md waves-effect waves-light" type="submit">Register</button>
			 </div>
          </div>
          </div>
	 

                                    <div class="form-group mb-0 row">
                                        <div class="col-12 mt-4 text-center">
                                        <p>Already have an account? <a href="/login" class="text-primary"> Login </a> </p>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 text-center">
                        
                        <p>© 2025 All Rights Reserved - A2B RX Inc</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="{{ URL::asset('/libs/jquery/jquery.min.js')}}"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key={{config('app.googlemaps_apikey')}}&region=US&language=en&libraries=places"></script>
    <script src="{{ URL::asset('/js/jquery.maskedinput.min.js')}}" type="text/javascript"></script>
    <script src="{{ URL::asset('/js/bootstrap-filestyle.min.js')}}"></script>
    <script>
        var input = document.getElementById('searchTextField');
        var input2 = document.getElementById('pharmacyAddress');
        var autocomplete = new google.maps.places.Autocomplete(input);
        var autocomplete2 = new google.maps.places.Autocomplete(input2);

        input.addEventListener('input', function () {
            this.dataset.originalVal = this.value;
        });
        input.addEventListener('focus', function () {
            this.value = input.dataset.originalVal ? input.dataset.originalVal : this.value;
        });
        input2.addEventListener('input', function () {
            this.dataset.originalVal = this.value;
        });
        input2.addEventListener('focus', function () {
            this.value = input.dataset.originalVal ? input.dataset.originalVal : this.value;
        });
        $("#phone").mask("(999) 999-9999");
        $('#phone').click(function(){
            if(isNaN(Number.parseInt($(this).val()))) {
                $(this).setCursorPosition(1);
            }
        });
        $("#pharmacyPhone").mask("(999) 999-9999");
        $('#pharmacyPhone').click(function(){
            if(isNaN(Number.parseInt($(this).val()))) {
                $(this).setCursorPosition(1);
            }
        });
    </script>
@stop
