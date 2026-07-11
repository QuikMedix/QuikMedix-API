@extends('layouts.master')

@section('title') Admin Zones @endsection

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
                                    @if($alert!='') 
                                        <div class="alert alert-danger" role="alert">{{ $alert }}</div>
                                    @endif
                                    <a href="/settings/admin_areas/add"><button class="btn btn-primary mb-3">Create New Zone</button></a>
                                    <div class="table-rep-plugin">
                                        <div class="table-responsive mb-0" data-pattern="priority-columns">
                                            <table id="mytable" class="table  table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th data-priority="3">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                @foreach ($admin_areas as $admin_area)
                                                    <tr>
                                                        <th>{{$admin_area->name}}</th>
                                                        <td class="action">
                                                            <a href="/settings/admin_areas/edit/{{$admin_area->id}}"><button class="btn btn-warning">Edit</button></a>
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

@endsection