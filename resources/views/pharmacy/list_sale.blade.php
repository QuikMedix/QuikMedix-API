@extends('layouts.master')

@section('title') Pharmacys @endsection

@section('headerCss')

    <!-- Responsive Table css -->
    <link href="{{ URL::asset('/libs/rwd-table/rwd-table.min.css')}}" rel="stylesheet" type="text/css" /> <!-- Bootstrap Css -->
    <style>
    .btn-group.pull-right {
        float: right;
    }
    </style>
@endsection

@section('content')
 <!-- start page title -->
                    <div class="row">

                    
                    </div>
                    <!-- end page title -->

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body" style="margin-bottom:30px;">
                                    <div class="btn-toolbar d-block">
                                        <div class="btn-group focus-btn-group"></div>
                                        <div class="btn-group dropdown-btn-group pull-right"></div>
                                    </div>
                                    <div class="row">
                                    @foreach ($pharmacys as $pharmacy)
                                    <div class="col-xl-3 col-md-6">
                                        <div class="card directory-card">
                                            <div>
                                                <div class="directory-bg text-center" @if(!empty($pharmacy->image_front)) style="background-image:url('{{$pharmacy->image_front}}');" @endif>
                                                    <div class="directory-overlay">
                                                        @if(!empty($pharmacy->logo))
                                                        <img class="rounded-circle avatar-lg img-thumbnail" src="{{$pharmacy->logo}}" alt="Logo">
                                                        @else
                                                        <img class="rounded-circle avatar-lg img-thumbnail" src="https://test.a2brx.com/images/users/logo_ph.jpg" alt="Logo">
                                                        @endif
                                                    </div>
                                                </div>
                                                    <div class="directory-content text-center p-4">
                                                        @if($pharmacy->isblocked == 1)
                                                            <span class="badge badge-dark float-right">Blocked</span>
                                                        @elseif($pharmacy->isactive == 0)
                                                            <span class="badge badge-warning float-right">No active</span>
                                                        @else 
                                                            <span class="badge badge-info float-right">Active</span>
                                                        @endif                                     
                                                        <div style="padding: 0 10px;"> 
                                                            <p class=" mt-4" style="margin-bottom: 0.4rem;font-weight: bold;background: #2b3a4a;color: #ffffff;border-radius: 5px;">{{$pharmacy->name}}</p>                                     
                                                            <p class="text-muted" style="margin-bottom: 0.2rem;"><i class="mdi mdi-google-maps"></i> {{$pharmacy->address}}</p>
                                                            <p class="text-muted" style="margin-bottom: 0.2rem;"><i class="mdi mdi-phone-in-talk-outline"></i> {{$pharmacy->phone}}</p>
                                                            <p class="text-muted" style="margin-bottom: 0.2rem;"><i class="mdi mdi-gmail"></i> {{$pharmacy->email}}</p>
                                                        </div> 
                                                        <h4 class="text-muted mt-2 mb-2" style="margin-bottom: 0.2rem;">Balance: 0$</h4> <hr style="margin-top: 0rem;">
                                                        <ul class="social-links list-inline mb-0 mt-2">
                                                            <li class="list-inline-item">
                                                                <a title="Sale Report" data-placement="top" class="btn-primary" data-toggle="tooltip" class="tooltips" href="/pharmacy/{{ $pharmacy->id }}/sale_report" data-original-title="Sale Report"><i class="mdi mdi-finance"></i></a>
                                                            </li>
                                                            <li class="list-inline-item">
                                                                <a title="Orders" data-placement="top" class="btn-info" data-toggle="tooltip" class="tooltips" href="/orders/{{ $pharmacy->id }}" data-original-title="Orders"><i class="mdi mdi-medical-bag"></i></a>
                                                            </li>
                                                        </ul>  
                                                    </div>
                                                </div>
                                            </div>
                                    </div>
                                    @endforeach
                                </div>

                                </div>
                                <div style="position: absolute;text-align: center;width: 100%;bottom: 10px;">Pages: 
                                    @foreach ($pages as $page)
                                        <form style="display: inline-block;">
                                            <input type="hidden" name="page" value="{{ $page['id'] }}">
                                            <input type="hidden" name="search" value="{{ $search }}">
                                            <button class="btn {{$page['class']}}">{{ $page['id'] }}</button>
                                        </form> 
                                    @endforeach
                                    ...
                                </div>
                            </div>
                        </div>
                        <!-- end col -->
                    </div>
                    <!-- end row -->

@endsection

@section('footerScript')
            <!-- Responsive Table js -->
            <script src="{{ URL::asset('/libs/rwd-table/rwd-table.min.js')}}"></script>

            <!-- Init js -->
            <script src="{{ URL::asset('/js/pages/table-responsive.init.js')}}"></script>
            <script>
                $(document).ready(function(){
                    
                });
            </script>
@endsection