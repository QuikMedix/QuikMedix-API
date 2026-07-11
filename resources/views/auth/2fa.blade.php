@extends('layouts.auth-master')

@section('title', '2FA Verification')

@section('content')
 <div class="account-pages my-5 pt-sm-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6 col-xl-5">
                    <div class="card overflow-hidden">
                        <div class="card-header">2FA Verification</div> 
                        <div class="card-body">
                            <form method="POST" action="{{ route('2fa.post') }}">
                                @csrf
                                <p class="text-center">We sent code to your phone : {{ substr(auth()->user()->phone, 0, 5) . '******' . substr(auth()->user()->phone,  -2) }}</p>
                                @if ($message = Session::get('success'))
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="alert alert-success alert-block">
                                            <button type="button" class="close" data-dismiss="alert">×</button> 
                                                <strong>{{ $message }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                @if ($message = Session::get('error'))
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="alert alert-danger alert-block">
                                            <button type="button" class="close" data-dismiss="alert">×</button> 
                                                <strong>{{ $message }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                <div class="form-group row">
                                    <label for="code" class="col-md-4 col-form-label text-md-right">Code</label>

                                    <div class="col-md-6">
                                        <input id="code" type="number" class="form-control @error('code') is-invalid @enderror" name="code" value="{{ old('code') }}" required autocomplete="code" autofocus>

                                        @error('code')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="form-group row mb-0">
                                    <div class="col-md-10 offset-md-2">
                                        <a class="btn btn-link" href="{{ route('2fa.resend') }}">Resend Code?</a>
                                        Or
                                        <a class="btn btn-link" target="_blank" href="https://t.me/auth_a2brx_bot">Add Telegram Auth</a>
                                    </div>
                                </div>
                                <div class="form-group row mb-0">
                                    <div class="col-md-12 text-center">
                                        <button type="submit" class="btn btn-primary">
                                            Submit
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="mt-5 text-center">
                        <!--<p>Don't have an account ? <a href="/register" class="text-primary"> Signup Now </a></p>-->
                        <p>© 2025 All Rights Reserved - A2B RX Inc</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop