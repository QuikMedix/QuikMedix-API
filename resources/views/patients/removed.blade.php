@extends('layouts.master')

@section('title') Users @endsection

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
                                <div style="margin-top: 1.25rem;position: absolute;text-align: center;width: 100%;">Pages: 
                                    @foreach ($pages as $page)
                                        <form class="filter-form" style="display: inline-block;">
                                            <input type="hidden" name="page" value="{{ $page['id'] }}">
                                            <input type="hidden" name="search" value="{{ $search }}">
                                            <button class="btn {{$page['class']}}">{{ $page['id'] }}</button>
                                        </form> 
                                    @endforeach
                                    ...
                                </div>
                                <div class="card-body" style="margin-bottom:10px;">
                                    <div class="table-rep-plugin">
                                        <div class="table-responsive mb-0" data-pattern="priority-columns">
                                            <table id="mytable" class="table  table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th data-priority="1">Cell Phone</th>
                                                        <th data-priority="1">Role</th>
                                                        <th data-priority="3">Removed Medic ID</th>
                                                        <th data-priority="3">APP</th>
                                                        <th data-priority="3">Reason</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                @foreach ($users as $user)
                                                    <tr>
                                                        <td>{{$user->name}} {{$user->last_name}}</td>
                                                        <td>{{$user->phone}}</td>
                                                        <td>{{$user->role}}</td>
                                                        <td>{{$user->medic_id}}</td>
                                                        <td>
                                                            @if($user->os==1)
                                                                <i class="ion ion-logo-android" style="font-size: 34px;color:#58db83;"></i> 
                                                                <i style="font-size: 34px;margin-left:5px;" class="ion ion-logo-apple"></i>
                                                            @elseif($user->os==2)
                                                                <i class="ion ion-logo-android" style="font-size: 34px;"></i> 
                                                                <i style="font-size: 34px;margin-left:5px; color:#58db83;" class="ion ion-logo-apple"></i>
                                                            @else
                                                                <i class="ion ion-logo-android" style="font-size: 34px;"></i> 
                                                                <i style="font-size: 34px;margin-left:5px;" class="ion ion-logo-apple"></i>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            {{$user->reason}}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                    </div>

                                </div>
                                <div style="position: absolute;text-align: center;width: 100%;bottom: 10px;">Pages: 
                                    @foreach ($pages as $page)
                                        <form class="filter-form" style="display: inline-block;">
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
                    $('body').on('click','.close',function() {
                        $('.modal').fadeOut(300);
                    });
                    $('body').on('click','.close0',function() {
                        $('.modal').fadeOut(300);
                    });
                });
            </script>
@endsection