@extends('layouts.auth-master')

@section('title', 'Login')

@section('content')
 <div class="account-pages my-5 pt-sm-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6 col-xl-5">
                    <div class="card overflow-hidden">
                        <div class="card-body pt-0">                            
                            <div class="text-center mt-1">
                                <a href="/" class="logo logo-admin"><img src="{{ asset('images/branding/quikmedix-logo.png?v=transparent-1') }}" width="260" height="191" style="max-width: 100%; height: auto;" alt="QuikMedix — Your Health - Our Priority"></a>
                            </div>                           
                            <h4 class="text-center text-dark font-size-18 mb-1">Welcome!</h4>
                            <p class="text-center text-dark">Sign in to continue.</p>
                           
                            <div class="pl-2 pr-2">                                
                                <form method="POST" class="form-horizontal mt-4" action="{{ route('login') }}">
                                       @csrf
                                    <div class="form-group">
                                        <label for="username">E-Mail Address</label>
                                         <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus placeholder="Email">
                                        @error('email')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror

                                    </div>
                                    <div class="form-group">
                                        <label for="userpassword">Password</label>
                                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" placeholder="Password">
                                        @error('password')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                    <div class="form-group row mt-4">
                                        <div class="col-6">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" name="remember" id="customControlInline" {{ old('remember') ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="customControlInline">{{ __('Remember Me') }}</label>
                                            </div>
                                        </div>
                                        <div class="col-6 text-right">
                                            <button class="btn btn-primary w-md waves-effect waves-light" type="submit">Log In</button>
                                        </div>
                                    </div>
                                    <div class="form-group mb-0 row">
                                        <div class="col-12 mt-4">
                                            <a href="{{ route('password.request') }}" class="text-muted"><i class="mdi mdi-lock"></i> Forgot your password?</a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 text-center">
                        <!--<p>Don't have an account ? <a href="/register" class="text-primary"> Signup Now </a></p>-->
                        <p>© {{ date('Y') }} QuikMedix. All rights reserved.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop
