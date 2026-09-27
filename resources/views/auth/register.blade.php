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
                                <a href="/" class="logo logo-admin"><img src="{{ asset('images/branding/quikmedix-logo.png?v=transparent-1') }}" width="260" height="191" style="max-width: 100%; height: auto;" alt="QuikMedix — Your Health - Our Priority"></a>
                            </h3>
                            <div class="p-3">
                                <h2 class="text-muted mb-1 text-center">Registration</h2>
                                <p class="text-muted text-center mb-0">Fields marked * are required.</p>
                                @if($errors->any())
                                <div class="alert alert-danger text-center mt-3" role="alert">Please fix the highlighted fields below.</div>
                                @endif
                                <form class="form-horizontal" id="registerForm" method="POST" action="{{ route('register') }}" enctype="multipart/form-data" novalidate>
                                @csrf
                                @if(request()->filled('ref'))
                                <input type="hidden" name="ref_id" value="{{ request('ref') }}">
                                @endif
                                <div class="row">
                                <div class="col-md-6">
                                <h5 class="qm-section-title" style="margin: 20px 0;">Pharmacy information</h5>
                                    <div class="form-group">
                                        <label for="pharmacyName">Pharmacy Name *</label>
                                        <input type="text" class="form-control @error('pharmacyName') is-invalid @enderror" name="pharmacyName" value="{{ old('pharmacyName') }}" required maxlength="255" id="pharmacyName" data-label="Pharmacy name" autocomplete="organization">
                                        <div class="invalid-feedback" role="alert">@error('pharmacyName'){{ $message }}@enderror</div>
                                    </div>

                                    <div class="form-group">
                                        <label for="pharmacyEmail">Pharmacy E-Mail Address *</label>
                                        <input type="email" class="form-control @error('pharmacyEmail') is-invalid @enderror" name="pharmacyEmail" value="{{ old('pharmacyEmail') }}" required maxlength="255" id="pharmacyEmail" data-label="Pharmacy e-mail" autocomplete="off">
                                        <div class="invalid-feedback" role="alert">@error('pharmacyEmail'){{ $message }}@enderror</div>
                                    </div>

                                    <div class="form-group">
                                        <label for="pharmacyPhone">Pharmacy Phone *</label>
                                        <input type="tel" class="form-control @error('pharmacyPhone') is-invalid @enderror" name="pharmacyPhone" value="{{ old('pharmacyPhone') }}" required maxlength="20" pattern="\(\d{3}\) \d{3}-\d{4}" data-pattern-message="Enter a 10-digit phone number." id="pharmacyPhone" data-label="Pharmacy phone" placeholder="(555) 123-4567" autocomplete="off">
                                        <div class="invalid-feedback" role="alert">@error('pharmacyPhone'){{ $message }}@enderror</div>
                                    </div>

                                    <div class="form-group">
                                        <label for="pharmacyAddress">Pharmacy Address *</label>
                                        <input type="text" class="form-control @error('pharmacyAddress') is-invalid @enderror" name="pharmacyAddress" value="{{ old('pharmacyAddress') }}" required maxlength="255" id="pharmacyAddress" data-label="Pharmacy address" placeholder="Start typing and pick a suggestion" autocomplete="off">
                                        <div class="invalid-feedback" role="alert">@error('pharmacyAddress'){{ $message }}@enderror</div>
                                    </div>

                                    <div class="form-group">
                                        <label for="pharmacyWebsite">Pharmacy Website (Optional)</label>
                                        <input type="text" class="form-control @error('pharmacyWebsite') is-invalid @enderror" name="pharmacyWebsite" value="{{ old('pharmacyWebsite') }}" maxlength="255" id="pharmacyWebsite" data-label="Pharmacy website" placeholder="example.com" autocomplete="off">
                                        <div class="invalid-feedback" role="alert">@error('pharmacyWebsite'){{ $message }}@enderror</div>
                                    </div>

                                    <div class="form-group">
                                        <label for="pharmacyLogo">Pharmacy Logo (Optional, JPG or PNG up to 6 MB)</label>
                                        <input type="file" name="pharmacyLogo" id="pharmacyLogo" accept="image/png,image/jpeg" data-label="Logo" class="filestyle @error('pharmacyLogo') is-invalid @enderror" data-buttonname="btn-secondary">
                                        <div class="invalid-feedback" role="alert">@error('pharmacyLogo'){{ $message }}@enderror</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="qm-section-title" style="margin: 20px 0;">User Information</h5>
                                    <div class="form-group">
                                        <label for="name">First Name *</label>
                                        <input type="text" name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="given-name" class="form-control @error('name') is-invalid @enderror" autofocus id="name" data-label="First name">
                                        <div class="invalid-feedback" role="alert">@error('name'){{ $message }}@enderror</div>
                                    </div>

                                    <div class="form-group">
                                        <label for="last_name">Last Name *</label>
                                        <input type="text" name="last_name" value="{{ old('last_name') }}" required maxlength="255" autocomplete="family-name" class="form-control @error('last_name') is-invalid @enderror" id="last_name" data-label="Last name">
                                        <div class="invalid-feedback" role="alert">@error('last_name'){{ $message }}@enderror</div>
                                    </div>

                                    <div class="form-group">
                                        <label for="useremail">Your E-Mail Address * <small class="text-muted">(you will log in with this)</small></label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" id="useremail" required maxlength="191" data-label="E-mail" autocomplete="email">
                                        <div class="invalid-feedback" role="alert">@error('email'){{ $message }}@enderror</div>
                                    </div>

                                    <div class="form-group">
                                        <label for="phone">Your Phone *</label>
                                        <input type="tel" name="phone" required maxlength="20" pattern="\(\d{3}\) \d{3}-\d{4}" data-pattern-message="Enter a 10-digit phone number." class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" id="phone" data-label="Phone" placeholder="(555) 123-4567" autocomplete="tel">
                                        <div class="invalid-feedback" role="alert">@error('phone'){{ $message }}@enderror</div>
                                    </div>

                                    <div class="form-group">
                                        <label for="userpassword">{{ __('Password') }} * <small class="text-muted">(at least 6 characters)</small></label>
                                        <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" required minlength="6" autocomplete="new-password" id="userpassword" data-label="Password">
                                        <div class="invalid-feedback" role="alert">@error('password'){{ $message }}@enderror</div>
                                    </div>

                                    <div class="form-group">
                                        <label for="userconfirmpassword">{{ __('Confirm Password') }} *</label>
                                        <input type="password" required name="password_confirmation" class="form-control" id="userconfirmpassword" data-label="Password confirmation" autocomplete="new-password">
                                        <div class="invalid-feedback" role="alert"></div>
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
                                            <p>Already have an account? <a href="{{ route('login') }}" class="text-primary">Login</a></p>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 text-center">

                        <p>© {{ date('Y') }} QuikMedix. All rights reserved.</p>
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
        (function () {
            var form = document.getElementById('registerForm');
            var fields = Array.prototype.slice.call(form.querySelectorAll('input[data-label]'));
            var password = document.getElementById('userpassword');
            var confirmation = document.getElementById('userconfirmpassword');
            var logo = document.getElementById('pharmacyLogo');
            var address = document.getElementById('pharmacyAddress');
            var maxLogoBytes = 6000 * 1024; // matches the server's max:6000 (KB)

            function applyCustomRules(field) {
                if (field === confirmation) {
                    confirmation.setCustomValidity(confirmation.value && confirmation.value !== password.value ? 'The passwords do not match.' : '');
                }
                if (field === logo) {
                    var file = logo.files[0];
                    logo.setCustomValidity(!file ? ''
                        : !/^image\/(png|jpe?g)$/.test(file.type) ? 'The logo must be a JPG or PNG image.'
                        : file.size > maxLogoBytes ? 'The logo must be smaller than 6 MB.' : '');
                }
            }

            function messageFor(field) {
                var validity = field.validity, label = field.dataset.label;
                if (validity.valueMissing) return label + ' is required.';
                if (validity.typeMismatch) return 'Enter a valid e-mail address, like name@example.com.';
                if (validity.patternMismatch) return field.dataset.patternMessage;
                if (validity.tooShort) return label + ' must be at least ' + field.minLength + ' characters.';
                if (validity.tooLong) return label + ' must be at most ' + field.maxLength + ' characters.';
                return field.validationMessage;
            }

            // Shows or clears the field's message; returns whether the field is valid.
            function validate(field) {
                applyCustomRules(field);
                var feedback = field.parentNode.querySelector('.invalid-feedback');
                var valid = field.checkValidity();
                field.classList.toggle('is-invalid', !valid);
                if (!valid) feedback.textContent = messageFor(field);
                return valid;
            }

            fields.forEach(function (field) {
                var recheck = function () {
                    // Re-validate once the user has seen an error on this field (server or client).
                    if (field.classList.contains('is-invalid') || form.dataset.submitted) validate(field);
                };
                field.addEventListener('input', recheck);
                field.addEventListener('change', recheck);
                field.addEventListener('blur', function () { if (field.value) validate(field); });
            });
            password.addEventListener('input', function () { if (confirmation.value) validate(confirmation); });

            form.addEventListener('submit', function (event) {
                form.dataset.submitted = '1';
                var invalid = fields.filter(function (field) { return !validate(field); });
                if (invalid.length) {
                    event.preventDefault();
                    var first = invalid[0] === logo ? logo.parentNode : invalid[0];
                    first.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    if (invalid[0] !== logo) invalid[0].focus({ preventScroll: true });
                }
            });

            if (window.google && google.maps && google.maps.places) {
                var autocomplete = new google.maps.places.Autocomplete(address);
                autocomplete.addListener('place_changed', function () {
                    var place = autocomplete.getPlace();
                    if (place && place.formatted_address) address.value = place.formatted_address;
                    validate(address);
                });
            }

            $('#phone, #pharmacyPhone').mask('(999) 999-9999').on('click', function () {
                // A click on an empty masked field can leave the caret mid-placeholder; start after "(".
                if (!/\d/.test(this.value)) this.setSelectionRange(1, 1);
            });
        })();
    </script>
@stop
