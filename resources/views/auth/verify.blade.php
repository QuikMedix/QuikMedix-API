@extends('layouts.auth-master')

@section('title', 'Verify')

@section('content')
 <div class="account-pages my-5 pt-sm-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6 col-xl-5">
                    <div class="card overflow-hidden">
                        <div class="card-body pt-0">
                            <h3 class="text-center mt-4">
                                <a href="/" class="logo logo-admin"><img src="{{ asset('images/branding/quikmedix-logo.png?v=transparent-1') }}" width="260" height="191" style="max-width: 100%; height: auto;" alt="QuikMedix — Your Health - Our Priority"></a>
                            </h3>
                            <div class="p-3">
                                <h4 class="text-muted font-size-18 mb-1 text-center">Security !</h4>
                                <p class="text-muted text-center">{{ __('Verify Your Email Address') }}.</p>
                             @if (session('resent'))
                        <div class="alert alert-success" role="alert">
                            {{ __('A fresh verification link has been sent to your email address.') }}
                        </div>
                    @endif

                    {{ __('Before proceeding, please check your email for a verification link.') }}
                    {{ __('If you did not receive the email') }},
                    <form class="d-inline" method="POST" action="{{ route('verification.resend') }}">
                        @csrf
                        <button type="submit" class="btn btn-link p-0 m-0 align-baseline">{{ __('click here to request another') }}</button>.
                    </form>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 text-center">
                        <p>Already have an account ? <a href="/login" class="text-primary"> Login </a> </p>
                        <p>© {{ date('Y') }} QuikMedix. All rights reserved.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

@stop
