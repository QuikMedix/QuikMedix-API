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
                                <div class="card-body" style="margin-bottom:30px;">
                                        @if($alert!='') 
                                            <div class="alert alert-danger" role="alert">{{ $alert }}</div>
                                        @endif
                                    <div class="table-rep-plugin">
                                        <div class="table-responsive mb-0" data-pattern="priority-columns">
                                            <table id="mytable" class="table  table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th data-priority="1">Email</th>
                                                        <th data-priority="1">Phone</th>
                                                        <th data-priority="1">Role</th>
                                                        <th data-priority="3">Status</th>
                                                        <th data-priority="3">Action</th>
                                                        <th data-priority="3">APP</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                @foreach ($users as $user)
                                                    <tr>
                                                        <th>{{$user->name}} {{$user->last_name}}</th>
                                                        <td>{{$user->email}}</td>
                                                        <td>{{$user->phone}}</td>
                                                        <td>{{$user->role}}</td>
                                                        @if($user->isblocked == 1)
                                                            <td><button class="btn btn-danger">Blocked</button></td>
                                                        @elseif($user->isactive == 0)
                                                            <td><button class="btn btn-warning">No active</button></td>
                                                        @else 
                                                            <td><button class="btn btn-success">Active</button></td>
                                                        @endif
                                                        <td class="action">
                                                        @if($user->id != 1)
                                                            <a href="users/edit/{{$user->id}}"><button class="btn btn-warning">Edit</button></a>
                                                            <a href="/drivers/{{ $user->id }}/payouts/"><button class="btn btn-success">Payouts</button></a>
                                                            @if($user->isblocked == 1)
                                                                <form method="post" style="display: inline-block;">
                                                                    @csrf
                                                                    <input type="hidden" name="user_id" value="{{$user->id}}">
                                                                    <input type="hidden" name="unblock" value="1">
                                                                    <button class="btn btn-success">Unblock</button>
                                                                </form>
                                                            @else 
                                                                <form method="post" style="display: inline-block;">
                                                                    @csrf
                                                                    <input type="hidden" name="user_id" value="{{$user->id}}">
                                                                    <input type="hidden" name="block" value="1">
                                                                    <button class="btn btn-danger">Block</button>
                                                                </form>
                                                            @endif
                                                            @if($user->isactive == 0)
                                                                <form method="post" style="display: inline-block;">
                                                                    @csrf
                                                                    <input type="hidden" name="user_id" value="{{$user->id}}">
                                                                    <input type="hidden" name="activate" value="1">
                                                                    <button class="btn btn-success">Activate</button>
                                                                </form>
                                                            @endif
                                                            <form method="post" style="display: inline-block;">
                                                                    @csrf
                                                                <input type="hidden" name="user_id" value="{{$user->id}}">
                                                                <input type="hidden" name="remove" value="1">
                                                                <button class="btn btn-danger" type="button" onclick="if(confirm('Are you sure?')){$(this).parent('form').submit();}">Remove</button>
                                                            </form>
                                                        @endif
                                                        </td>
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
                var page = "{{ $page0 }}";
                $(document).ready(function(){
                    $("#search-page").val(page);
                });
            </script>
@endsection