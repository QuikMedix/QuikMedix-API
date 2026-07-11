@extends('layouts.master')

@section('title') Delivery Calendar @endsection

@section('headerCss')
<style>
    .fc-event-container {
        padding:5px !important;
        font-size: 12px !important;
    }
</style>
<link href="{{ URL::asset('/css/@fullcalendar/core/main.min.css')}}" rel="stylesheet">
<link href="{{ URL::asset('/css/@fullcalendar/daygrid/main.min.css')}}" rel="stylesheet">
<link href="{{ URL::asset('/css/@fullcalendar/bootstrap/main.min.css')}}" rel="stylesheet">
<link href="{{ URL::asset('/css/@fullcalendar/timegrid/main.min.css')}}" rel="stylesheet">
@endsection

@section('content')
<!-- start page title -->
<div class="row">
</div>
<!-- end page title -->

<div class="row mb-4">
    @if(Auth::user()->role=='admin')
    <div class="col-xl-3">
        <div class="card">
            <div class="card-body">
                <form>
                    <input type="hidden" name="month" value="{{$month}}">
                    <div class="form-group">
                        <label class="control-label">State</label>
                        <select class="form-control select2" name="state">
                            <option value="">All states</option>
                            @foreach($states as $state)
                            <option value="{{$state->name}}" @if(isset($_GET['state']) && $_GET['state']==$state->name){{'selected'}}@endif>{{$state->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="control-label">Zone</label>
                        <select class="form-control select2" name="zone">
                            <option value="">All zones</option>
                            @if(isset($_GET['state']) && !empty($_GET['state']))
                            @foreach($zones as $zone)
                            @if($zone->state==$_GET['state'])
                            <option value="{{$zone->id}}" @if(isset($_GET['zone']) && $_GET['zone']==$zone->id){{'selected'}}@endif>{{$zone->name}}</option>
                            @endif
                            @endforeach
                            @endif
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>
    </div> <!-- end col-->
    <div class="col-xl-9">
    @else
    <div class="col-xl-12">
    @endif
        <div class="card mt-4 mt-xl-0 mb-0">
            <div class="card-body">
                <div id="calendar" class="fc fc-ltr fc-bootstrap" style="">
                    <div class="fc-toolbar fc-header-toolbar">
                        <div class="fc-left">
                            <div class="btn-group">
                                <a href="?month={{date("Y-m-d",strtotime($month.'- 1 month'))}}"><button type="button" class="fc-prev-button btn btn-primary"aria-label="prev"><span class="fa fa-chevron-left"></span></button></a>
                                <a href="?month={{date("Y-m-d",strtotime($month.'+ 1 month'))}}"><button type="button" class="fc-next-button btn btn-primary" aria-label="next"><span class="fa fa-chevron-right"></span></button></a>
                            </div>
                        </div>
                        <div class="fc-center">
                            <h2>{{date("F Y",strtotime($month))}}</h2>
                        </div>                       
                    </div>
                    <div class="fc-view-container">
                        <div class="fc-view fc-dayGridMonth-view fc-dayGrid-view" style="">
                            <table class="table-bordered">
                                <thead class="fc-head">
                                    <tr>
                                        <td class="fc-head-container ">
                                            <div class="fc-row table-bordered">
                                                <table class="table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th class="fc-day-header  fc-sun"><span>Sun</span></th>
                                                            <th class="fc-day-header  fc-mon"><span>Mon</span></th>
                                                            <th class="fc-day-header  fc-tue"><span>Tue</span></th>
                                                            <th class="fc-day-header  fc-wed"><span>Wed</span></th>
                                                            <th class="fc-day-header  fc-thu"><span>Thu</span></th>
                                                            <th class="fc-day-header  fc-fri"><span>Fri</span></th>
                                                            <th class="fc-day-header  fc-sat"><span>Sat</span></th>
                                                        </tr>
                                                    </thead>
                                                </table>
                                            </div>
                                        </td>
                                    </tr>
                                </thead>
                                <tbody class="fc-body">
                                    <tr>
                                        <td class="">
                                            <div class="fc-scroller fc-day-grid-container"
                                                style="overflow: hidden;">
                                                <div class="fc-day-grid">
                                                    @foreach($weeks as $week)
                                                    <div class="fc-row fc-week table-bordered" style="height: 130px;">
                                                        <div class="fc-bg">
                                                            <table class="table-bordered">
                                                                <tbody>
                                                                    <tr>
                                                                        @foreach([0,1,2,3,4,5,6] as $day)
                                                                        @if(isset($week[$day]))
                                                                        <td class="fc-day @if($day==0){{'fc-sun'}}@elseif($day==1){{'fc-mon'}}@elseif($day==2){{'fc-tue'}}@elseif($day==3){{'fc-wed'}}@elseif($day==4){{'fc-thu'}}@elseif($day==5){{'fc-fri'}}@else{{'fc-sat'}}@endif @if(date('Y-m-d')>$week[$day]){{'fc-past'}}@elseif(date('Y-m-d')<$week[$day]){{'fc-future'}}@else{{'fc-today alert alert-info'}}@endif" data-date="{{$week[$day]}}"></td>
                                                                        @else
                                                                        <td class="fc-day @if($day==0){{'fc-sun'}}@elseif($day==1){{'fc-mon'}}@elseif($day==2){{'fc-tue'}}@elseif($day==3){{'fc-wed'}}@elseif($day==4){{'fc-thu'}}@elseif($day==5){{'fc-fri'}}@else{{'fc-sat'}}@endif fc-other-month fc-past" data-date="-"></td>
                                                                        @endif
                                                                        @endforeach
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                        <div class="fc-content-skeleton">
                                                            <table>
                                                                <thead>
                                                                    <tr>
                                                                        @foreach([0,1,2,3,4,5,6] as $day)
                                                                        @if(isset($week[$day]))
                                                                        <td class="fc-day-top @if($day==0){{'fc-sun'}}@elseif($day==1){{'fc-mon'}}@elseif($day==2){{'fc-tue'}}@elseif($day==3){{'fc-wed'}}@elseif($day==4){{'fc-thu'}}@elseif($day==5){{'fc-fri'}}@else{{'fc-sat'}}@endif @if(date('Y-m-d')>$week[$day]){{'fc-past'}}@elseif(date('Y-m-d')<$week[$day]){{'fc-future'}}@else{{'fc-today alert alert-info'}}@endif" data-date="{{$week[$day]}}"><span class="fc-day-number">{{date('d',strtotime($week[$day]))}}</span></td>
                                                                        @else
                                                                        <td class="fc-day-top @if($day==0){{'fc-sun'}}@elseif($day==1){{'fc-mon'}}@elseif($day==2){{'fc-tue'}}@elseif($day==3){{'fc-wed'}}@elseif($day==4){{'fc-thu'}}@elseif($day==5){{'fc-fri'}}@else{{'fc-sat'}}@endif fc-other-month fc-past" data-date="-"><span class="fc-day-number">-</span></td>
                                                                        @endif
                                                                        @endforeach
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <tr>
                                                                        @foreach([0,1,2,3,4,5,6] as $day)
                                                                        @if(isset($week[$day]))
                                                                        <td class="fc-event-container">
                                                                            @if(isset($stats[$week[$day]]))
                                                                            @foreach($stats[$week[$day]] as $status)
                                                                            <a href="/orders/{{$pharmacy_id}}?filter=1&status%5B%5D={{$status->id}}&need_delivery_start={{date('m/d/Y',strtotime($week[$day]))}}&need_delivery_end={{date('m/d/Y',strtotime($week[$day]))}}" class="">{{$status->name}}: {{$status->count}}</a><br>
                                                                            @endforeach
                                                                            @endif
                                                                        </td>
                                                                        @else
                                                                        <td class="fc-event-container"></td>
                                                                        @endif
                                                                        @endforeach
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- end col -->

</div>

@endsection

@section('footerScript')
<script>
    $('select[name="state"]').selectize({
        sortField: 'text'
    });
    $('select[name="state"]').on('change',function(){
        $('select[name="zone"]').html('');
        $('select[name="zone"]').append('<option value="">All zones</option>');
        $.get(location.href, { get_zones: 1, state: $(this).val() }).done(function( data ) {
            data=JSON.parse(data);
            if(data.length>0){
                data.forEach(function(zone){
                    $('select[name="zone"]').append('<option value="'+zone.id+'">'+zone.name+'</option>');
                });
            }
        });
    });
</script>
@endsection