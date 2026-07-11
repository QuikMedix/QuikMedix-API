@extends('layouts.master')

@section('title') Notifications @endsection

@section('headerCss')

    <!-- Responsive Table css -->
    <link href="{{ URL::asset('/libs/rwd-table/rwd-table.min.css')}}" rel="stylesheet" type="text/css" /> <!-- Bootstrap Css -->
@endsection
@php
function time_elapsed_string0($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
@endphp
@section('content')
 <!-- start page title -->

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
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
                                    @foreach ($notifications as $notification)
                                        @if($notification->viewed==1)
                                        <div class="alert alert-{{$notification->type}} alert-block viewed" role="alert">
                                        @else 
                                        <div class="alert alert-{{$notification->type}} alert-block" role="alert">
                                        @endif
                                            @if($notification->link!='')
                                                <a href="{{$notification->link}}">
                                            @else 
                                                <a href="#">
                                            @endif
                                            <div class="icon-block">
                                            @if($notification->type=='danger' || $notification->type=='warning')
                                                <i class="fas fa-info-circle"></i>
                                            @else
                                                <i class="fas fa-check-circle"></i>
                                            @endif
                                            </div>
                                            <div class="info-block">
                                                <div class="text">{{$notification->text}}</div>
                                                @if(round((strtotime(date('now')) - strtotime($notification->created))/3600, 1)<48)
                                                    <div class="datetime">{{time_elapsed_string0($notification->created)}}</div>
                                                @else
                                                    <div class="datetime">{{date('m/d/Y g:i A', strtotime($notification->created))}}</div>
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