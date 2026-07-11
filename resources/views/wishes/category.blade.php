@extends('layouts.master')

@section('title') Print Text Category @endsection

@section('headerCss')
    <!-- Responsive Table css -->
    <link href="{{ URL::asset('/libs/rwd-table/rwd-table.min.css')}}" rel="stylesheet" type="text/css" /> <!-- Bootstrap Css -->
@endsection
@section('content')
 <!-- start page title -->

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <a href="/settings/wishes/add" class="addorder" style="position:absolute;margin-top: 10px;margin-left: 10px;"><button class="btn btn-primary">Add Print Text Category</button></a>
                                <div style="display: inline-block;text-align: center;margin-top: 10px;">Pages: 
                                    @foreach ($pages as $page)
                                        <form style="display: inline-block;">
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
                                                        <th data-priority="3">Status</th>
                                                        <th data-priority="3">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                @foreach ($wishes_categorys as $wishes_category)
                                                    <tr>
                                                        <td>{{$wishes_category->name}}</td>
                                                        @if($wishes_category->status == 1)
                                                            <td><button class="btn btn-success">Active</button></td>
                                                        @else 
                                                            <td><button class="btn btn-danger">No active</button></td>
                                                        @endif
                                                        <td class="action">
                                                            <a href="/settings/wishes/{{$wishes_category->id}}/list"><button class="btn btn-primary mr-4">Print Text</button></a>
                                                            @if($wishes_category->status == 1)
                                                            <form method="post" style="display: inline-block;">
                                                                @csrf
                                                                <input type="hidden" name="wishes_category_id" value="{{$wishes_category->id}}">
                                                                <input type="hidden" name="noactivate" value="1">
                                                                <button class="btn btn-warning" type="button" onclick="if(confirm('Are you sure?')){$(this).parent('form').submit();}">Disable</button>
                                                            </form>
                                                            @else 
                                                            <form method="post" style="display: inline-block;">
                                                                @csrf
                                                                <input type="hidden" name="wishes_category_id" value="{{$wishes_category->id}}">
                                                                <input type="hidden" name="activate" value="1">
                                                                <button class="btn btn-success" type="button" onclick="if(confirm('Are you sure?')){$(this).parent('form').submit();}">Activate</button>
                                                            </form>
                                                            @endif
                                                            <form method="post" style="display: inline-block;">
                                                                @csrf
                                                                <input type="hidden" name="wishes_category_id" value="{{$wishes_category->id}}">
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

                                </div>
                                <div style="display: inline-block;text-align: center;margin-top: 10px;">Pages: 
                                    @foreach ($pages as $page)
                                        <form style="display: inline-block;">
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