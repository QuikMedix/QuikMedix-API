@extends('layouts.master')

@section('title') Feedback | QuikMedix @endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body p-4">
                <img src="{{ asset('images/branding/quikmedix-wordmark.png?v=transparent-1') }}" alt="QuikMedix" width="220" class="mb-4">
                <h4>How can we help?</h4>
                <p>Report a problem or share an idea with the QuikMedix support team.</p>
                <div class="row mt-4">
                    <div class="col-md-6 mb-3">
                        <h5><i class="mdi mdi-message-alert-outline mr-2" aria-hidden="true"></i>Report a problem</h5>
                        <p>Tell us what happened and include the order reference when relevant.</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <h5><i class="mdi mdi-lightbulb-outline mr-2" aria-hidden="true"></i>Suggest an improvement</h5>
                        <p>Let us know how QuikMedix could work better for you.</p>
                    </div>
                </div>
                <a href="{{ url('/chat') }}" class="btn btn-primary">Open support chat</a>
                @if(config('branding.support_email'))
                    <a href="mailto:{{ config('branding.support_email') }}" class="btn btn-outline-primary ml-2">Email support</a>
                @endif
                @if(config('branding.support_phone'))
                    <p class="mt-3 mb-0">You can also call {{ config('branding.support_phone') }}.</p>
                @endif
                @if(config('branding.address'))
                    <address class="mt-3 mb-0">{!! nl2br(e(config('branding.address'))) !!}</address>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
