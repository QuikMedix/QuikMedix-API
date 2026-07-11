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
                                <div class="card-body">
                                    <div class="table-rep-plugin">
                                        <div class="table-responsive mb-0" data-pattern="priority-columns">
                                            <table id="mytable" class="table  table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th data-priority="1">Email</th>
                                                        <th data-priority="1">Role</th>
                                                        <th data-priority="3">Is Active</th>
                                                        <th data-priority="3">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                @foreach ($users as $user)
                                                    <tr>
                                                        <th>{{$user->name}}</th>
                                                        <td>{{$user->email}}</td>
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
                                                                <button class="btn btn-danger">Remove</button>
                                                            </form>
                                                            @if($user->role != 'admin')
                                                                @if($user->role == 'medic')
                                                                <form method="post" style="display: inline-block;">
                                                                    @csrf
                                                                    <input type="hidden" name="user_id" value="{{$user->id}}">
                                                                    <input type="hidden" name="todeliveryman" value="1">
                                                                    <button class="btn btn-secondary">Switch To DeliveryMan</button>
                                                                </form>
                                                                <form method="post" style="display: inline-block;">
                                                                    @csrf
                                                                    <input type="hidden" name="user_id" value="{{$user->id}}">
                                                                    <input type="hidden" name="touser" value="1">
                                                                    <button class="btn btn-secondary">Switch To User</button>
                                                                </form>
                                                                <form method="post" style="display: inline-block;">
                                                                    @csrf
                                                                    <input type="hidden" name="user_id" value="{{$user->id}}">
                                                                    <input type="hidden" name="touseradmin" value="1">
                                                                    <button class="btn btn-secondary">Switch To Admin</button>
                                                                </form>
                                                                @elseif($user->role == 'deliveryman')
                                                                <form method="post" style="display: inline-block;">
                                                                    @csrf
                                                                    <input type="hidden" name="user_id" value="{{$user->id}}">
                                                                    <input type="hidden" name="tomedic" value="1">
                                                                    <button class="btn btn-secondary">Switch To Medic</button>
                                                                </form>
                                                                <form method="post" style="display: inline-block;">
                                                                    @csrf
                                                                    <input type="hidden" name="user_id" value="{{$user->id}}">
                                                                    <input type="hidden" name="touser" value="1">
                                                                    <button class="btn btn-secondary">Switch To User</button>
                                                                </form>
                                                                <form method="post" style="display: inline-block;">
                                                                    @csrf
                                                                    <input type="hidden" name="user_id" value="{{$user->id}}">
                                                                    <input type="hidden" name="touseradmin" value="1">
                                                                    <button class="btn btn-secondary">Switch To Admin</button>
                                                                </form>
                                                                @else
                                                                <form method="post" style="display: inline-block;">
                                                                    @csrf
                                                                    <input type="hidden" name="user_id" value="{{$user->id}}">
                                                                    <input type="hidden" name="tomedic" value="1">
                                                                    <button class="btn btn-secondary">Switch To Medic</button>
                                                                </form>
                                                                <form method="post" style="display: inline-block;">
                                                                    @csrf
                                                                    <input type="hidden" name="user_id" value="{{$user->id}}">
                                                                    <input type="hidden" name="todeliveryman" value="1">
                                                                    <button class="btn btn-secondary">Switch To DeliveryMan</button>
                                                                </form>
                                                                <form method="post" style="display: inline-block;">
                                                                    @csrf
                                                                    <input type="hidden" name="user_id" value="{{$user->id}}">
                                                                    <input type="hidden" name="touseradmin" value="1">
                                                                    <button class="btn btn-secondary">Switch To Admin</button>
                                                                </form>
                                                                @endif
                                                            @endif
                                                        @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>

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
                $(document).ready(function(){
                    $("#search").keyup(function(){
                    _this = this;
                        $.each($("#mytable tbody tr"), function() {
                            if($(this).find("td").filter(":not(.action)").text().toLowerCase().indexOf($(_this).val().toLowerCase()) === -1) {
                                $(this).hide();
                            } else {
                                $(this).show();                
                            };
                        });
                    });
                });
            </script>
@endsection