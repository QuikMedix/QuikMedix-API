@extends('layouts.auth-master')

@section('title', 'Lock Screen')

@section('content')
        <div class="account-pages my-5 pt-sm-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6 col-xl-5">
                    <div class="card overflow-hidden">
                        <div class="card-body pt-0">
                            <h3 class="text-center mt-4">
                                <a href="/dashboard/index" class="logo logo-admin"><img src="{{ asset('images/branding/quikmedix-logo.png?v=transparent-1') }}" width="260" height="191" style="max-width: 100%; height: auto;" alt="QuikMedix — Your Health - Our Priority"></a>
                            </h3>
                            <div class="p-3">
                                <h4 class="text-muted font-size-18 mb-1 text-center">Locked</h4>
                                <p class="text-muted text-center">Hello Smith, enter your password to unlock the screen!</p>
                                <form class="form-horizontal mt-4" action="/dashboard/index">

                                    <div class="user-thumb text-center mb-4">
                                        <img src="{{ URL::asset('/images/users/user-6.jpg')}}" class="rounded-circle avatar-md img-thumbnail" alt="thumbnail">
                                        <h6 class="font-size-16 mt-3">Robert Smith</h6>
                                    </div>

                                    <div class="form-group">
                                        <label for="userpassword">Password</label>
                                        <input type="password" class="form-control" id="userpassword" placeholder="Enter password">
                                    </div>

                                    <div class="form-group row">
                                        <div class="col-12 text-right">
                                            <button class="btn btn-primary w-md waves-effect waves-light" type="submit">Unlock</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 text-center">
                        <p>Not you ? return <a href="/pages/pages-login" class="text-primary"> Sign In </a> </p>
                        <p>© {{  date('Y', strtotime('-2 year')) }} - {{  date('Y') }} QuikMedix. All rights reserved.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop
