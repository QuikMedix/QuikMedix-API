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
                                <a href="/settings/plans/add" class="addorder" style="position:absolute;margin-top: 10px;margin-left: 15px;"><button class="btn btn-primary">Add Tariff Plan</button></a>
                                <div style="display: inline-block;text-align: center;margin-top: 10px;">Pages: 
                                    @foreach ($pages as $page)
                                        <form class="filter-form" style="display: inline-block;">
                                            <input type="hidden" name="page" value="{{ $page['id'] }}">
                                            <button class="btn {{$page['class']}}">{{ $page['id'] }}</button>
                                        </form> 
                                    @endforeach
                                    ...
                                </div>
                                <div class="card-body" style="margin-bottom:30px;">
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
                                                @foreach ($plans as $plan)
                                                    <tr>
                                                        <td>{{$plan->name}}</td>
                                                        <td>{{$plan->tariff}}</td>
                                                        <td class="action"> 
                                                            <a href="/settings/plans/{{$plan->id}}/edit"><button class="btn btn-warning">Edit Plan</button></a>
                                                            <!--<form method="post" style="display: inline-block;">
                                                                @csrf
                                                                <input type="hidden" name="plan_id" value="{{$plan->id}}">
                                                                <input type="hidden" name="remove" value="1">
                                                                <button class="btn btn-danger" type="button" onclick="if(confirm('Are you sure?')){$(this).parent('form').submit();}">Remove</button>
                                                            </form>-->
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                    </div>

                                </div>
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