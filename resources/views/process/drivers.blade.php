@extends('layouts.master')

@section('title') Process @endsection

@section('headerCss')

    <!-- Responsive Table css -->
    <link href="{{ URL::asset('/libs/rwd-table/rwd-table.min.css')}}" rel="stylesheet" type="text/css" /> <!-- Bootstrap Css -->
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
                                    @if($alert!='') 
                                        <div class="alert alert-danger" role="alert">{{ $alert }}</div>
                                    @endif
                                    <div class="btn-toolbar d-block">
                                        <div class="btn-group focus-btn-group"></div>
                                        <div class="btn-group dropdown-btn-group pull-right"></div>
                                    </div>                                   
                                    <div class="row">
                                        @if(count($users)==0)
                                            <div class="col-xl-12 col-md-12" align="center">
                                                <h2>You don't have your own drivers in the works</h2>
                                                <img class="img-fluid mt-5" src="https://cp.a2brx.com/images/empty.jpg" alt="empty">
                                            </div>
                                        @else
                                            @foreach($users as $user)
                                            <div class="col-xl-3 col-md-6">
                                                <div class="card directory-card drive">
                                                    <div>
                                                        <div class="directory-bg text-center" @if(!empty($user->car_img)) style="background-image:url('{{$user->car_img}}');" @endif>
                                                            <div class="directory-overlay">
                                                            <h4 style="margin-top: -8px;position: absolute;right: 4px;top: 15px;"> <span class="badge bg-light float-left">Driver ID {{$user->id}}</span></h4>
                                                                @if(!empty($user->image))
                                                                <img class="rounded-circle avatar-lg img-thumbnail" src="{{ $user->image }}" alt="Logo">
                                                                @else
                                                                <img class="rounded-circle avatar-lg img-thumbnail" src="/images/users/default-user-image.png" alt="Logo">
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="directory-content text-center p-4">
                                                            @if($user->os==1)
                                                                    <img class="float-left" src="https://cp.a2brx.com/images/android.png" alt="android" style="width: 30px;position: initial;margin-top: -14px;">
                                                            @elseif($user->os==2)
                                                                <img class="float-left" src="https://cp.a2brx.com/images/ios.png" alt="ios" style="width: 30px;position: initial;margin-top: -14px;s;">
                                                            @else
                                                                <i class="ion ion-logo-android float-left" style="font-size: 34px;"></i>                                                            
                                                            @endif
                                                            @if($user->isblocked == 1)
                                                                                <span class="badge badge-dark float-right">Blocked</span>
                                                                            @elseif($user->isactive == 0)
                                                                                <span class="badge badge-warning float-right">No active</span>
                                                                            @else 
                                                                                <span class="badge badge-info float-right">Active</span>
                                                            @endif                                     
                                                            <div style="padding: 0 10px;"> 
                                                                <p class=" mt-4" style="margin-bottom: 0.4rem;font-weight: bold;background: #2b3a4a;color: #ffffff;border-radius: 5px;">{{$user->name}} {{$user->last_name}}</p>
                                                                <p class="text-muted" style="margin-bottom: 0.2rem;"><i class="mdi mdi-phone-in-talk-outline"></i> {{$user->phone}}</p>
                                                            </div> 
                                                            <p class="text-muted mt-2" style="margin-bottom: 0.2rem;">Progress</p> <hr style="margin-top: 0rem;">
                                                            <div class="progress" style="height: 24px;min-width:250px;position:relative;">
                                                                    <div class="progress-bar" role="progressbar" @if(($user->count_delivery+$user->count_delivered)>0) style="width:{{ceil($user->count_delivered/($user->count_delivery+$user->count_delivered)*100)}}%;" @endif aria-valuenow="{{$user->count_delivery}}" aria-valuemin="0" aria-valuemax="{{($user->count_delivery+$user->count_delivered)}}"></div>
                                                                    @if($user->count_delivery==0)
                                                                    <div style="position: absolute;display: flex;width: 100%;height: 100%;justify-content: center;align-items: center;font-size: 13px;color: #000;">{{$user->count_delivered}} / {{($user->count_delivery+$user->count_delivered)}} - 0 hr 0 min left</div>
                                                                    @else
                                                                    <div style="position: absolute;display: flex;width: 100%;height: 100%;justify-content: center;align-items: center;font-size: 13px;color: #000;">{{$user->count_delivered}} / {{($user->count_delivery+$user->count_delivered)}} - {{floor($user->eta / 60)}} hr {{$user->eta % 60}} min left</div>
                                                                    @endif 
                                                            </div>
                                                            <hr style="margin-top: 0rem;">                                                        
                                                            <a href="/process/{{$pharmacy_id}}/show/{{$user->id}}"><button class="btn btn-primary btn-sm waves-effect waves-light">Show Map</button></a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            @endforeach	
                                        @endif
                                    </div>
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
                var page = "{{ $page0 }}";
                $(document).ready(function(){
                    $("#search-page").val(page);
                });
            </script>
@endsection