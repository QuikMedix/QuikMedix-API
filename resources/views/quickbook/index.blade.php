@extends('layouts.master')

@section('title') Quickbook @endsection

@section('headerCss')

@endsection

@section('content')
 <!-- start page title -->
                    <div class="row">

                   
                    </div>
                    <!-- end page title -->

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    @if(!empty($authUrl))
									<a href="{{$authUrl}}" target="_blank"><button class="btn btn-primary" type="button">Auth Quickbook</button></a>
                                    @else
                                    <p>Token working! Account Email: {{$userInfo["email"]}}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <!-- end col -->
                    </div>
                    <!-- end row -->
@endsection

@section('footerScript')

@endsection
