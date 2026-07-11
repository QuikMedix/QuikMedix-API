@extends('layouts.master')

@section('title') {{$title}} @endsection

@section('headerCss')
    <!-- Responsive Table css -->
    <link href="{{ URL::asset('/libs/rwd-table/rwd-table.min.css')}}" rel="stylesheet" type="text/css" /> <!-- Bootstrap Css -->
@endsection
@section('content')
 <!-- start page title -->

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <a href="/settings/area/add" class="addorder" style="position:absolute;margin: 20px 15px;"><button class="btn btn-primary">Add Area</button></a>
                                <div style="display: inline-block;text-align: center;margin: 20px 15px;">Pages: 
                                    @foreach ($pages as $page)
                                        <form class="filter-form" style="display: inline-block;">
                                            <input type="hidden" name="page" value="{{ $page['id'] }}">
                                            <button class="btn {{$page['class']}}">{{ $page['id'] }}</button>
                                        </form> 
                                    @endforeach
                                    ...
                                </div>
                                <div class="row m-3">
                                @foreach ($areas as $area)
                                <div class="col-xl-3 col-sm-6">
                                <div class="card">
                                    <div class="card-body">
                                            <h4 class="card-title"><i class="ti-location-pin"></i> {{$area->name}}</h4>                                           

                                            <div class="btn-group btn-group-lg mt-4">
                                                <a href="/settings/area/{{$area->id}}/edit"><button class="btn btn-info btn-sm waves-effect waves-light mr-2">Edit Area</button></a>
                                                    <form method="post" style="display: inline-block;">
                                                        @csrf
                                                        <input type="hidden" name="area_id" value="{{$area->id}}">
                                                        <input type="hidden" name="remove" value="1">
                                                        <button class="btn btn-danger btn-sm waves-effect waves-light" type="button" onclick="if(confirm('Are you sure?')){$(this).parent('form').submit();}">Remove</button>
                                                    </form>
                                            </div>
                                            <br>

                                           

                                        </div>
                                    </div>
                                </div>
                                @endforeach
                                </div>

                                <!-- <div class="card-body" style="margin-bottom:30px;">
                                    <div class="table-rep-plugin">
                                        <div class="table-responsive mb-0" data-pattern="priority-columns">                                        
                                            <table id="mytable" class="table  table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Tariff</th>
                                                        <th data-priority="3">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                @foreach ($areas as $area)
                                                    <tr>
                                                        <td>{{$area->name}}</td>
                                                        <td>{{$area->tariff}}</td>
                                                        <td class="action"> 
                                                            <a href="/settings/area/{{$area->id}}/edit"><button class="btn btn-warning">Edit Area</button></a>
                                                            <form method="post" style="display: inline-block;">
                                                                @csrf
                                                                <input type="hidden" name="area_id" value="{{$area->id}}">
                                                                <input type="hidden" name="remove" value="1">
                                                                <button class="btn btn-danger" type="button" onclick="if(confirm('Are you sure?')){$(this).parent('form').submit();}">Remove</button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div> -->
                                <div style="display: inline-block;text-align: center;margin-top: 10px;">Pages: 
                                    @foreach ($pages as $page)
                                        <form class="filter-form" style="display: inline-block;">
                                            <input type="hidden" name="page" value="{{ $page['id'] }}">
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

@endsection