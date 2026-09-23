@extends('layouts.master')

@section('title') Patient News @endsection

@section('headerCss')

    <!-- Responsive Table css -->
    <link href="{{ URL::asset('/libs/rwd-table/rwd-table.min.css')}}" rel="stylesheet" type="text/css" /> <!-- Bootstrap Css -->
@endsection
@section('content')
 <!-- start page title -->

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                @if(Auth::user()->role=="medic")
                                <a href="/news_patient/add" class="addorder" style="position:absolute;margin-top: 10px;margin-left: 10px;"><button class="btn btn-primary">Add News</button></a>
                                @endif
                                <div style="display: inline-block;text-align: center;margin-top: 10px;">Pages: 
                                    @foreach ($pages as $page)
                                        <form class="filter-form" style="display: inline-block;">
                                            <input type="hidden" name="page" value="{{ $page['id'] }}">
                                            <button class="btn {{$page['class']}}">{{ $page['id'] }}</button>
                                        </form> 
                                    @endforeach
                                    ...
                                </div>
                                <div class="card-body">
                                    @foreach ($news as $post)
                                        <div class="alert alert-{{$post->type}} alert-block" role="alert">
                                            @if($post->link!='')
                                                <a href="{{$post->link}}">
                                            @else 
                                                <a href="#">
                                            @endif
                                            <div class="icon-block">
                                            @if($post->type=='danger' || $post->type=='warning')
                                                <i class="fas fa-info-circle"></i>
                                            @else
                                                <i class="fas fa-check-circle"></i>
                                            @endif
                                            </div>
                                            <div class="info-block">
                                                <div class="text"><b>{{$post->title}}</b></div>
                                                <div class="text">{{$post->text}}</div>
                                                @if(round((strtotime(date('now')) - strtotime($post->created ?? ''))/3600, 1)<48)
                                                    <div class="datetime">{{\App\Support\TimeAgo::format($post->created)}}</div>
                                                @else
                                                    <div class="datetime">{{date('m/d/Y g:i A', strtotime($post->created ?? ''))}}</div>
                                                @endif
                                            </div>
                                            </a>
                                        </div>
                                    @endforeach
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