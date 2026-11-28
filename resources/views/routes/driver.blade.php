@extends('layouts.master')

@section('title') Driver Detail @endsection
@section('headerCss')
<link rel="stylesheet" href="{{ URL::asset('/css/bootstrap-multiselect.min.css')}}" type="text/css"/>
<link rel="stylesheet" href="{{ URL::asset('/css/bootstrap-lib.min.css?v=quikmedix-1')}}">
<link href="{{ URL::asset('/css/bootstrap-datepicker.min.css')}}" rel="stylesheet">
<link href="{{ URL::asset('/css/select2.min.css')}}" rel="stylesheet" type="text/css">
<link href="{{ URL::asset('/css/jquery.bootstrap-touchspin.min.css')}}" rel="stylesheet">
<style>
.route-map-area-controls { margin: 10px; padding: 6px; background: #fff; border-radius: 4px; }
.route-map-area-controls [role="status"] { display: block; max-width: 300px; color: #212529; font-size: 12px; }
.office-order-picker {
    min-width: 110px;
    max-width: 170px;
    padding: 2px 6px;
    font-size: 11px;
    white-space: normal;
}
.office-order-dialog {
    width: min(480px, calc(100vw - 32px));
    max-height: 85vh;
    padding: 24px;
    border: 0;
    border-radius: 8px;
    color: #212529;
    background: #fff;
}
.office-order-dialog::backdrop {
    background: rgba(0, 0, 0, .45);
}
.office-order-options {
    max-height: 45vh;
    overflow-y: auto;
}
.office-order-options label {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    cursor: pointer;
}
.wrapper {
    display: flex;
    flex-wrap: wrap;
    max-height: 310px;
    overflow-y: auto;
    overflow-x: hidden;
    float:left;
    margin-bottom: 30px;
}
.wrapper::-webkit-scrollbar {
  width: 1px;
}
.wrapper::-webkit-scrollbar-track {
  background: #f1f1f1; 
}
/* Handle */
.wrapper::-webkit-scrollbar-thumb {
  background: #888; 
}
/* Handle on hover */
.wrapper::-webkit-scrollbar-thumb:hover {
  background: #555; 
}
.wrapper.active {
    border: 4px dashed;
    border-radius: 15px;
    opacity: 0.5;
}
.trash_block {
    position: absolute;
    right: 20px;
    font-size: 30px;
    border: 4px dashed;
    border-radius: 15px;
    opacity: 0.5;
    width: 200px;
    height: 64px;
    text-align: center;
    padding: 7px;
    /*  display: none; */
}
.trash_block.active {
    display: block;
}
.trash_block.focuse {
    opacity: 1;
}
.drag-text {
    display:none;
    position: absolute;
    text-align: center;
    width: 100%;
    top: 46%;
}
.wrapper.active .drag-text {
    display:block;
}

#patients_list,#pharmacies_list{
    overflow-x: hidden;
}
.plus-item {
    cursor: move;
    padding: 2px 0px;
    height: 70px;
    width: 240px;
    border: 2px solid #28c8e2;
    border-radius: 5px;
    opacity: 0.4;
    display: inline-table;
    margin-bottom: 10px;
    margin-right: 7px;
}
.wrapper.active .plus-item {
    opacity: 0.3 !important;
}
.plus-item:hover {
    opacity: 1;
}
.plus-item .eta_block {
    float: left;
    width:40%;
    height: 60px;
    font-size:11px;
    border-right: 1px solid #fff;
}
.plus-item .eta_block .time{
    font-weight:bold;
}
.plus-item .eta_block .num {
    font-weight:bold;
    margin-top:5px;
    font-size:16px;
}
.plus-item .number {
    color: #28c8e2;
    font-size: 37px;
    margin: 0;
    padding-top: 7px;
    text-align: center;
}
.plus-item p {
    line-height: 1;
    margin-bottom: 4.5px;
    font-size: 11px;
    font-weight: 100;
}
.plus-item.active-green {
    border: 5px solid #19b313;
    background: #19b313;
    padding: 0;
    padding-top: 3px;
    opacity: 1;
    color:#fff;
    text-align: center;
}
.plus-item.active-blue {
    border: 5px solid #464086;
    background: #464086;
    opacity: 1;
    padding: 0;
    padding-top: 3px;
    color:#fff;
    text-align: center;
    position: relative;
}
.plus-item.active-yellow {
    border: 5px solid #f3b567;
    background: #f3b567;
    opacity: 1;
    padding-top: 3px;
    color:#fff;
    text-align: center;
}
.plus-item.active-blue.special::after {
    content: attr(title);
    color: rgba(255,255,255,0);
    position: absolute;
    width: inherit;
    padding: 3px 6px;
    height: 5px;
    background-color: #f00;
    left: -5px;
    bottom: -5px;
    font-size: 10px;
    text-transform: lowercase;
    border-radius: 0px 0px 5px 5px;
    -webkit-transition: color 0.5s ease-out,height 0.6s ease-in-out;
    -moz-transition: color 0.5s ease-out,height 0.6s ease-in-out;
    -o-transition: color 0.5s ease-out,height 0.6s ease-in-out;
    transition: color 0.5s ease-out,height 0.6s ease-in-out;
}
.plus-item.active-blue.special.open::after {
    color: rgba(255,255,255,1);
    height: 60px;
}
.patient_block {
    overflow: hidden;
    position: relative;
    margin-top: 10px;
    margin-left: 5px;
    z-index: 999;
    cursor: move;
    width: 100%;
    min-height: 45px;
    border-radius: 6px;
    background-color: #eae6ff;
    color: #0b4f8a;
    padding: 5px 5px;
    font-size: 12px;
    border: 1px solid #464086;
    margin-bottom: 5px;
    -webkit-touch-callout: none; /* iOS Safari */
    -webkit-user-select: none;   /* Chrome/Safari/Opera */
    -khtml-user-select: none;    /* Konqueror */
    -moz-user-select: none;      /* Firefox */
    -ms-user-select: none;       /* Internet Explorer/Edge */
    user-select: none;           /* Non-prefixed version, currently*/
}
.patient_block.special {
    padding-right: 15px
}
.patient_block.special::after {
    content: attr(title);
    color: rgba(255,255,255,0);
    padding: 6px 3px;
    position: absolute;
    width: 5px;
    font-size: 10px;
    text-transform: lowercase;
    height: 102%;
    background-color: #f00;
    right: -1px;
    top: -1px;
    border-radius: 0px 5px 5px 0px;
    -webkit-transition: color 0.5s ease-out,width 0.6s ease-in-out;
    -moz-transition: color 0.5s ease-out,width 0.6s ease-in-out;
    -o-transition: color 0.5s ease-out,width 0.6s ease-in-out;
    transition: color 0.5s ease-out,width 0.6s ease-in-out;
}
.patient_block.special.open::after {
    color: rgba(255,255,255,1);
    width: 180px;
}
.patient_block.hover{
    padding: 0px 15px;
    border: 5px solid #73dff5;
    box-shadow: 0 2px 8px rgba(0, 0, 0, .3), -23px 0 22px -23px rgba(0, 0, 0, .8), 25px 0 23px -23px rgba(0, 0, 0, .8), 0 0 40px rgba(0, 0, 0, .1) inset;
}
.patient_block .left {
    float: left;
    width: 50%;
}
.patient_block .right {
    float: right;
    text-align: right;
    width: 50%;
}
.pharmacie_block {
    margin-top: 10px;
    margin-left: 5px;
    z-index: 10000;
    cursor: move;
    width: 100%;
    min-height: 45px;
    border-radius: 6px;
    background-color: #f4fff4;
    color: #006311;
    padding: 5px 5px;
    font-size: 12px;
    border: 1px solid #c90016;
    margin-bottom: 5px;
    -webkit-touch-callout: none; /* iOS Safari */
    -webkit-user-select: none;   /* Chrome/Safari/Opera */
    -khtml-user-select: none;    /* Konqueror */
    -moz-user-select: none;      /* Firefox */
    -ms-user-select: none;       /* Internet Explorer/Edge */
    user-select: none;           /* Non-prefixed version, currently*/
}
.pharmacie_block.hover{
    padding: 0px 15px;
    border: 5px solid #8bf9a6;
    box-shadow: 0 2px 8px rgba(0, 0, 0, .3), -23px 0 22px -23px rgba(0, 0, 0, .8), 25px 0 23px -23px rgba(0, 0, 0, .8), 0 0 40px rgba(0, 0, 0, .1) inset;
}
.pharmacie_block .left {
    float: left;
    width: 50%;
}
.pharmacie_block .right {
    float: right;
    text-align: right;
    width: 50%;
}
.pharmacie_block .date {
    margin-top:40px;
}
.card_height {
    height: 602px;
    overflow: auto;
    padding-right: 10px;
}
.card_height::-webkit-scrollbar-track
{
	-webkit-box-shadow: inset 0 0 6px rgba(0,0,0,0.3);
	border-radius: 10px;
	background-color: #F5F5F5;
}

.card_height::-webkit-scrollbar
{
	width: 4px;
	background-color: #F5F5F5;
}

.card_height::-webkit-scrollbar-thumb
{
	border-radius: 10px;
	-webkit-box-shadow: inset 0 0 6px rgba(0,0,0,.3);
	background-color: #c90016;
}
.office_block{
    cursor: move;
    position: absolute;
    width: 70px;
    right: 20px;
    bottom: 12px;
    height: 50px;
    border-radius: 6px;
    background-color: #f3b567;
    color: #ffffff;
    padding: 3px 0px;
    font-size: 11px;
    text-align: center;
    border: 1px solid #f3b567;
    -webkit-touch-callout: none; /* iOS Safari */
    -webkit-user-select: none;   /* Chrome/Safari/Opera */
    -khtml-user-select: none;    /* Konqueror */
    -moz-user-select: none;      /* Firefox */
    -ms-user-select: none;       /* Internet Explorer/Edge */
    user-select: none;           /* Non-prefixed version, currently*/
}
.office_block.hover{
    border: 5px solid #e1e27b;
    box-shadow: 0 2px 8px rgba(0, 0, 0, .3), -23px 0 22px -23px rgba(0, 0, 0, .8), 25px 0 23px -23px rgba(0, 0, 0, .8), 0 0 40px rgba(0, 0, 0, .1) inset;
}
.office_block .left {
    float: left;
    padding-top: 10px;
}
.office_block .right {
    padding-top:5px;
    float: right;
}
.office_block.focus {
    box-shadow: 0 0 5pt 4pt #58db83;
}
.pharmacie_block.focus {
    box-shadow: 0 0 5pt 4pt #58db83;
}
.patient_block.focus {
    box-shadow: 0 0 5pt 4pt #58db83;
}
.patient_block .date {
    margin-top:40px;
}
.plus-item.focus {
    box-shadow: 0 0 5pt 4pt #58db83;
}
.patient_block.disabled {
    opacity: 0.65;
    cursor: no-drop;
}
.patient_block.disabled .order_id::after {
    content: "Return";
    background-color: yellow;
    color: red;
    margin: 0 5px;
    padding: 0 5px;
    font-weight: 500;
}
.form-check-input{
    margin-top: 0px !important;
}
.multiselect-native-select>.btn-group {
    height: 22px;
    width: 110px;
}
.multiselect-native-select>.btn-group>button {
    font-size: 11px;
    padding: 0 8px 0 4px;
    margin-left: 4px;
    height: inherit;
    background-position-x: right;
    overflow: hidden;
}
.multiselect-selected-text {
    line-height: normal;
}
.btn-check:active+.btn-outline-primary, .btn-check:checked+.btn-outline-primary, .btn-outline-primary.active, .btn-outline-primary.dropdown-toggle.show, .btn-outline-primary:active {
    color: #fff;
    background-color: #c90016;
    border-color: #c90016;
}
.btn-check {
    position: absolute;
    clip: rect(0,0,0,0);
    pointer-events: none;
}
label {
    margin:0px !important;
}
.tariff {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    width: auto;
    margin-bottom: 1rem;
}
.tariff .input-group {
    width: 20%;
}
.filter_fix {
    position: fixed;
    left: 0px;
    z-index: 1000;
    bottom: 120px;
    border-top-left-radius: 0px !important;
    border-bottom-left-radius: 0px !important;
    padding: 12px !important;
    font-size: 15px !important;
    font-weight: 500 !important;
}
.multiselect-container {
    height: 300px;
    overflow: auto;

}
.multiselect-container::-webkit-scrollbar {
  width: 1px;
}
.multiselect-container::-webkit-scrollbar-track {
  background: #f1f1f1; 
}
/* Handle */
.multiselect-container::-webkit-scrollbar-thumb {
  background: #888; 
}
/* Handle on hover */
.multiselect-container::-webkit-scrollbar-thumb:hover {
  background: #555; 
}
.header-type-transport {
    width: 36px;
    margin-right: 6px;
    filter: opacity(0.67);
}
</style>
@endsection
@section('content')
 <!-- start page title -->
                    @if(!empty(request()->query('order')))
                    <div class="alert alert-warning" style="height: 59px;font-weight:bold;" role="alert">
                        Are you sure you want to assign order #{{request()->query('order')}} to this driver?
                        <div style="float:right;">
                            <button class="btn btn-success" onclick="$('#confirm_order').submit()">Yes</button>
                            <button class="btn btn-danger" onclick="location.href='{{ url('/routes-list/show') }}/{{request()->query('order')}}'">No</button>
                        </div>
                        <form method="POST" id="confirm_order" style="display:none;">
                            @csrf
                            <input type="hidden" name="confirm_order_id" value="{{request()->query('order')}}">
                        </form>
                    </div>
                    @endif
                    <x-flash-messages />
                    <div class="modal bs-example-modal modal1" style="display:none;" tabindex="1" role="dialog">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <form method="POST" enctype="multipart/form-data" id="region-form">
                                <div class="modal-header">
                                    <h5 class="modal-title mt-0">Region was created!</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    @csrf
                                    <input type="hidden" name="region" value="1">
                                    <select class="form-control d-none" name="delivery_time[]" multiple="" data-placeholder="Choose ...">
                                        @foreach($delivery_times as $delivery_time)
                                            @if(!empty($filter))
                                                @if(in_array($delivery_time->id,$filter['delivery_time']))
                                                    <option value="{{$delivery_time->id}}" selected>{{$delivery_time->name}}</option>
                                                @else
                                                    <option value="{{$delivery_time->id}}">{{$delivery_time->name}}</option>
                                                @endif
                                            @else
                                                <option value="{{$delivery_time->id}}">{{$delivery_time->name}}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    <input type="text" class="form-control d-none" name="start" value="{{$filter['start']}}" autocomplete="off">
                                    <input type="text" class="form-control d-none" name="end" value="{{$filter['end']}}" autocomplete="off">
                                    <select class="form-control d-none" name="status[]" multiple="" data-placeholder="Choose ...">
                                        @foreach($order_statuses as $order_statuse)
                                            @if(!empty($filter))
                                                @if(in_array($order_statuse->id,$filter['status']))
                                                    <option value="{{$order_statuse->id}}" selected>{{ \App\Support\OrderStatus::label($order_statuse->id, $order_statuse->name) }}</option>
                                                @else
                                                    <option value="{{$order_statuse->id}}">{{ \App\Support\OrderStatus::label($order_statuse->id, $order_statuse->name) }}</option>
                                                @endif
                                            @else
                                                <option value="{{$order_statuse->id}}">{{ \App\Support\OrderStatus::label($order_statuse->id, $order_statuse->name) }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    <h5 class="modal-title mt-0">Do you want to assign orders in the region to this driver?</h5>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-success">Assign</button>
                                    <button type="button" class="btn btn-secondary close0" data-dismiss="modal">No</button>
                                </div>
                                </form>
                            </div>
                            <!-- /.modal-content -->
                        </div>
                        <!-- /.modal-dialog -->
                    </div>
                    <div class="modal bs-example-modal modal2" style="display:none;" tabindex="1" role="dialog">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title mt-0">Set Up Route</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="id">
                                    <label class="form-label">Queue Number (Priority)</label>
                                    <input data-toggle="touchspin" type="text" name="priority" class="form-control">
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-success setup_route_yes">Yes</button>
                                    <button type="button" class="btn btn-secondary close0" data-dismiss="modal">Close</button>
                                </div>
                            </div>
                            <!-- /.modal-content -->
                        </div>
                        <!-- /.modal-dialog -->
                    </div>
                    <button class="btn btn-primary filter_fix" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasLeft" aria-controls="offcanvasLeft">Filter <i class="mdi mdi-filter"></i></button>
                    <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasLeft" aria-labelledby="offcanvasLeftLabel" aria-modal="true" role="dialog">
                        <div class="offcanvas-header">
                            <h5 id="offcanvasLeftLabel">Filter Form</h5>
                            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                        </div>
                        <div class="offcanvas-body">
                            <form method="GET" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label>Date Delivery</label>
                                    <div>
                                        <div class="input-daterange input-group" data-date-format="mm/dd/yyyy" data-date-autoclose="true" data-provide="datepicker">
                                            <input type="text" class="form-control" name="start" value="{{$filter['start']}}" autocomplete="off">
                                            <input type="text" class="form-control" name="end" value="{{$filter['end']}}" autocomplete="off">
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="time">Preferred delivery time</label>
                                    <select class="select2 form-control select2-multiple" name="delivery_time[]" multiple="" data-placeholder="Choose ...">
                                        @foreach($delivery_times as $delivery_time)
                                            @if(!empty($filter))
                                                @if(in_array($delivery_time->id,$filter['delivery_time']))
                                                    <option value="{{$delivery_time->id}}" selected>{{$delivery_time->name}}</option>
                                                @else
                                                    <option value="{{$delivery_time->id}}">{{$delivery_time->name}}</option>
                                                @endif
                                            @else
                                                <option value="{{$delivery_time->id}}">{{$delivery_time->name}}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group mb-3" data-select2-id="78">
                                    <label class="form-label">Pharmacy</label>
                                    <select class="select2 form-control select2-multiple" name="pharmacy[]" multiple="" data-matcher="customMatcher" data-placeholder="Choose ...">
                                        @foreach($pharmacys as $pharmacy)
                                            @if(!empty($filter))
                                                @if(in_array($pharmacy->id,$filter['pharmacy']))
                                                    <option value="{{$pharmacy->id}}" selected>{{$pharmacy->name}}</option>
                                                @else
                                                    <option value="{{$pharmacy->id}}">{{$pharmacy->name}}</option>
                                                @endif
                                            @else
                                                <option value="{{$pharmacy->id}}">{{$pharmacy->name}}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group mb-3" data-select2-id="78">
                                    <label class="form-label">Order Status</label>
                                    <select class="select2 form-control select2-multiple" name="status[]" multiple="" data-placeholder="Choose ...">
                                        @foreach($order_statuses as $order_statuse)
                                            @if(!empty($filter))
                                                @if(in_array($order_statuse->id,$filter['status']))
                                                    <option value="{{$order_statuse->id}}" selected>{{ \App\Support\OrderStatus::label($order_statuse->id, $order_statuse->name) }}</option>
                                                @else
                                                    <option value="{{$order_statuse->id}}">{{ \App\Support\OrderStatus::label($order_statuse->id, $order_statuse->name) }}</option>
                                                @endif
                                            @else
                                                <option value="{{$order_statuse->id}}">{{ \App\Support\OrderStatus::label($order_statuse->id, $order_statuse->name) }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <input type="hidden" name="filter" value="1">
                                <button class="btn btn-primary w-100">Filter <i class="mdi mdi-filter"></i></button>
                            </form>
                            <form method="GET">
                                <input type="hidden" name="show_route" value="1">
                                <button class="btn btn-primary w-100 mt-3" type="button" onclick="if(confirm('Are you sure? You need save route before load this function.')){$(this).parent('form').submit()}">Show driver route <i class="mdi mdi-map-marker"></i></button>
                            </form>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body" style="min-height: 550px;">
                                    <h4 style="display: inline-block;margin-right:15px;">Driver ID#{{$driver->id}}</h4>
                                    @if($driver->isblocked == 1)
                                        <button class="btn btn-danger" style="line-height: 0.9;vertical-align:top;">Blocked</button>
                                    @elseif($driver->isactive == 0)
                                        <button class="btn btn-warning" style="line-height: 0.9;vertical-align:top;">No active</button>
                                    @else 
                                        <button class="btn btn-success" style="line-height: 0.9;vertical-align:top;">Active</button>
                                    @endif                                    
                                    <button type="button" class="btn header-item waves-effect" style="float:right;height:auto;padding:0;">
                                        @if($driver->transport=='1')
                                        <img src="/images/delivery-truck.svg" class="header-type-transport" alt="car">
                                        @elseif($driver->transport=='2')
                                        <img src="/images/bicycle.svg" class="header-type-transport" alt="car">
                                        @endif
                                        <img class="rounded-circle header-profile-user" src="{{$driver->image}}" alt="Header Avatar">
                                    </button>
                                    <p>{{$driver->name}} {{$driver->last_name}}    {{$driver->phone}}    {{$driver->email}}</p>
                                    <form method="POST" id="close_route" class="d-none">
                                        @csrf
                                        <input type="hidden" name="close_route" value="1">
                                        {{-- Filled in from the choice below when Completed is pressed. --}}
                                        <input type="hidden" name="type_pay">
                                        <input type="hidden" name="pay_value">
                                    </form>
                                    <form method="POST" id="eta_calculate" class="d-none">
                                        @csrf
                                        <input type="hidden" name="eta_calculate" value="1">
                                    </form>
                                    <div class="row" style="margin-bottom: 15px;">
                                        <form method="POST" id="auto_route">
                                            @csrf
                                            <input type="hidden" name="auto_route" value="1">
                                            <span style="background: #242424;color: #ffffff;padding: 7px 10px;margin: 1px 8px 0 0;cursor:pointer;" onclick="if(confirm('Are you sure?')){$('#auto_route').submit();}">Automate route</span>
                                            <input type="radio" class="btn-check" name="type" value="driver" id="outlined2" autocomplete="off" checked>
                                            <label class="btn btn-outline-secondary" style="margin-right: 0px !important;border-top-right-radius: 0px;border-bottom-right-radius: 0px;" for="outlined2">Driver</label>
                                            <input type="radio" class="btn-check" name="type" value="first" id="outlined3" autocomplete="off">
                                            <label class="btn btn-outline-secondary" style="margin-left: -4px !important;border-top-left-radius: 0px;border-bottom-left-radius: 0px;" for="outlined3">First point</label>
                                        </form>
                                    </div>
                                    <form method="POST" id="driver-route-form">
                                        @if($errors->any())
                                            <div class="alert alert-danger" role="alert">
                                                @foreach($errors->all() as $error)
                                                    <p class="mb-0">{{ $error }}</p>
                                                @endforeach
                                            </div>
                                        @endif
                                        <div class="tariff">
                                            <input type="radio" class="btn-check" value="4" name="type_pay" id="danger-outlined" required autocomplete="off" {{ ($type_pay==4)?'checked':'' }}>
                                            <label class="btn btn-outline-primary" for="danger-outlined">Half</label>
                                            <input type="radio" class="btn-check" value="5" name="type_pay" id="danger-outlined2" required autocomplete="off" {{ ($type_pay==5)?'checked':'' }}>
                                            <label class="btn btn-outline-primary" for="danger-outlined2">Full</label>
                                            <input type="radio" class="btn-check" value="1" name="type_pay" id="success-outlined" required autocomplete="off" {{ ($type_pay==1)?'checked':'' }}>
                                            <label class="btn btn-outline-primary" for="success-outlined">Flat rate</label>
                                            <div class="input-group" @if($type_pay!=1){{"style=display:none;"}}@endif >
                                                <div class="input-group-text">$</div>
                                                {{-- A route without a rate carries the marker -1; that is not an amount to show. --}}
                                                <input type="number" step="0.01" min="0.01" name="pay_value" value="{{ $pay_value > 0 ? $pay_value : '' }}" placeholder="Amount" class="form-control">
                                            </div>
                                            <button type="button" class="btn btn-outline-warning" id="close-route-button">Completed</button>
                                            <button type="button" class="btn btn-outline-secondary" onclick="if(confirm('Are you sure?')){$('#eta_calculate').submit();}">Refresh ETA</button>
                                        </div>
                                        <p class="text-muted small mb-2">Half and Full are the shift rates; Flat rate pays the amount you type. Completed credits the driver for the stops they have completed.</p>
                                        @if(!empty($route_offer))
                                        <p class="small mb-2"><span class="badge badge-success">Route offer</span> The driver took route <strong>{{ $route_offer->code }}</strong> ({{ $route_offer->stops }} {{ $route_offer->stops == 1 ? 'stop' : 'stops' }}), which pays ${{ number_format((float) $route_offer->pay, 2) }}. That amount is filled in as the flat rate.</p>
                                        @endif
                                        <h4>Routes</h4>
                                        <p style="float:left;">Distance: {{$driver->distance}} miles</p>
                                        <p style="float:right;">Duration: {{floor($driver->eta / 60)}} hr {{$driver->eta % 60}} min</p>
                                        <div class="wrapper">
                                            <h4 class="drag-text">Drag an item here...</h4>
                                            @for($n=0;$n<300;$n++)
                                                @if(!empty($routes_priority[$n]))
                                                    @if($routes_priority[$n]->type=='pharmacy')
                                                        @php
                                                            if(!empty(request()->query('show_route')) && request()->query('show_route')>0) {
                                                                array_push($show_ids,"pharmacy".$routes_priority[$n]->type_id);
                                                                $show_priority["pharmacy".$routes_priority[$n]->type_id]=$n;
                                                            }
                                                        @endphp
                                                        <div class="plus-item active-green pharmacy{{$routes_priority[$n]->type_id}}">
                                                            <input type="hidden" name="order_id[]" value="{{$routes_priority[$n]->order_id}}">
                                                            <input type="hidden" name="type[]" value="{{$routes_priority[$n]->type}}">
                                                            <input type="hidden" name="type_id[]" value="{{$routes_priority[$n]->type_id}}">
                                                            <div class="eta_block">
                                                                <i class="mdi mdi-timer"></i> <span class="eta">@if(floor($routes_priority[$n]->eta / 60)>0){{floor($routes_priority[$n]->eta / 60)}} hr @endif{{$routes_priority[$n]->eta % 60}} min</span><br>
                                                                <i class="mdi mdi-clock-outline"></i> <span class="time">{{date("h:i A",strtotime('now + '.$routes_priority[$n]->eta.' minutes'))}}</span><br>
                                                                <span class="num">{{($n+1)}}</span>
                                                            </div>
                                                            <p class="order">Order:
                                                            @if(count(explode(',',$routes_priority[$n]->order_id))>1)
                                                                <select class="multiple_select" multiple="multiple">
                                                                @foreach(explode(',',$routes_priority[$n]->order_id) as $row)
                                                                    <option selected value="{{$row}}">{{$row}} @if(isset($orders[explode(',',$routes_priority[$n]->order_id)[0]])){{'('.date('m/d/Y', strtotime($orders[explode(',',$routes_priority[$n]->order_id)[0]]->delivery_date)).')'}}@endif</option>
                                                                @endforeach
                                                                </select>
                                                            @else
                                                            {{$routes_priority[$n]->order_id}}
                                                            @endif
                                                            </p>
                                                            <p>@if(isset($pharmacys_list[$routes_priority[$n]->type_id])){{$pharmacys_list[$routes_priority[$n]->type_id]->name}}@else{{$routes_priority[$n]->type_id}}@endif</p>
                                                        </div>
                                                    @endif
                                                    @if($routes_priority[$n]->type=='patient')
                                                        @php
                                                            if(!empty(request()->query('show_route')) && request()->query('show_route')>0) {
                                                                array_push($show_ids,"patient".$routes_priority[$n]->type_id);
                                                                $show_priority["patient".$routes_priority[$n]->type_id]=$n;
                                                            }
                                                        @endphp
                                                        <div title="@if(isset($orders[explode(',',$routes_priority[$n]->order_id)[0]])){{date('m/d/Y', strtotime($orders[explode(',',$routes_priority[$n]->order_id)[0]]->delivery_date))}} @if(!empty($orders[explode(',',$routes_priority[$n]->order_id)[0]]->delivery_time_range) && $orders[explode(',',$routes_priority[$n]->order_id)[0]]->delivery_time_range!='9:00 AM;12:00 AM')<b>{{str_replace(':00','',str_replace(';',' - ',$orders[explode(',',$routes_priority[$n]->order_id)[0]]->delivery_time_range))}}@endif @endif" class="plus-item active-blue patient{{$routes_priority[$n]->type_id}} @if(!empty($routes_priority[$n]->special_instructions)){{'special'}}@endif">
                                                            <input type="hidden" name="order_id[]" value="{{$routes_priority[$n]->order_id}}">
                                                            <input type="hidden" name="type[]" value="{{$routes_priority[$n]->type}}">
                                                            <input type="hidden" name="type_id[]" value="{{$routes_priority[$n]->type_id}}">
                                                            <div class="eta_block">
                                                                <i class="mdi mdi-timer"></i> <span class="eta">@if(floor($routes_priority[$n]->eta / 60)>0){{floor($routes_priority[$n]->eta / 60)}} hr @endif{{$routes_priority[$n]->eta % 60}} min</span><br>
                                                                <i class="mdi mdi-clock-outline"></i> <span class="time">{{date("h:i A",strtotime('now + '.$routes_priority[$n]->eta.' minutes'))}}</span><br>
                                                                <span class="num">{{($n+1)}}</span>
                                                            </div>
                                                            <p class="order">Order:
                                                            @if(count(explode(',',$routes_priority[$n]->order_id))>1)
                                                                <select class="multiple_select" multiple="multiple">
                                                                @foreach(explode(',',$routes_priority[$n]->order_id) as $row)
                                                                    <option selected value="{{$row}}">{{$row}} @if(isset($orders[explode(',',$routes_priority[$n]->order_id)[0]])){{'('.date('m/d/Y', strtotime($orders[explode(',',$routes_priority[$n]->order_id)[0]]->delivery_date)).')'}}@endif</option>
                                                                @endforeach
                                                                </select>
                                                            @else
                                                            {{$routes_priority[$n]->order_id}}
                                                            @endif
                                                            </p>
                                                            <p>Customer: {{$routes_priority[$n]->type_id}}</p>
                                                            @if(isset($orders[explode(',',$routes_priority[$n]->order_id)[0]]))<span class="badge @if(empty($orders[explode(',',$routes_priority[$n]->order_id)[0]]->finish) && strtotime(date('Y-m-d'))==strtotime($orders[explode(',',$routes_priority[$n]->order_id)[0]]->delivery_date)){{'bg-success'}}@elseif(empty($orders[explode(',',$routes_priority[$n]->order_id)[0]]->finish) && strtotime(date('Y-m-d'))>strtotime($orders[explode(',',$routes_priority[$n]->order_id)[0]]->delivery_date)){{'bg-danger'}}@else{{'bg-light'}}@endif">{{date('m/d/Y', strtotime($orders[explode(',',$routes_priority[$n]->order_id)[0]]->delivery_date))}} @if(!empty($orders[explode(',',$routes_priority[$n]->order_id)[0]]->delivery_time_range) && $orders[explode(',',$routes_priority[$n]->order_id)[0]]->delivery_time_range!='9:00 AM;12:00 AM')<b>{{str_replace(':00','',str_replace(';',' - ',$orders[explode(',',$routes_priority[$n]->order_id)[0]]->delivery_time_range))}}</b>@endif</span>@endif
                                                        </div>
                                                    @endif
                                                    @if($routes_priority[$n]->type=='office')
                                                        @php
                                                            if(request()->boolean('show_route')) {
                                                                $show_ids[] = 'office'.$routes_priority[$n]->type_id;
                                                            }
                                                        @endphp
                                                        <div class="plus-item active-yellow office{{$routes_priority[$n]->type_id}}">
                                                            <input type="hidden" name="order_id[]" value="{{$routes_priority[$n]->order_id}}">
                                                            <input type="hidden" name="type[]" value="{{$routes_priority[$n]->type}}">
                                                            <input type="hidden" name="type_id[]" value="{{$routes_priority[$n]->type_id}}">
                                                            <div class="eta_block">
                                                                <i class="mdi mdi-timer"></i> <span class="eta">@if(floor($routes_priority[$n]->eta / 60)>0){{floor($routes_priority[$n]->eta / 60)}} hr @endif{{$routes_priority[$n]->eta % 60}} min</span><br>
                                                                <i class="mdi mdi-clock-outline"></i> <span class="time">{{date("h:i A",strtotime('now + '.$routes_priority[$n]->eta.' minutes'))}}</span><br>
                                                                <span class="num">{{($n+1)}}</span>
                                                            </div>
                                                            <p class="order">Orders:
                                                                <button type="button" class="btn btn-light office-order-picker" data-office-order-picker>Choose orders</button>
                                                            </p>
                                                            <p>Office #{{$routes_priority[$n]->type_id}}</p>
                                                        </div>
                                                    @endif
                                                @else
                                                    <div class="plus-item no-active ">
                                                        <input type="hidden" name="order_id[]">
                                                        <input type="hidden" name="type[]">
                                                        <input type="hidden" name="type_id[]">
                                                        <div class="eta_block" style="display:none;">
                                                            <i class="mdi mdi-timer"></i> <span class="eta">-</span><br>
                                                            <i class="mdi mdi-clock-outline"></i> <span class="time">-</span><br>
                                                            <span class="num">{{($n+1)}}</span>
                                                        </div>
                                                        <p class="number">{{$n+1}}</p>
                                                    </div>
                                                @endif
                                            @endfor
                                        </div>
                                        @csrf
                                        <br><br>

                                        <p>Use Choose orders at each office stop to select its deliveries before saving.</p>
                                        <button type="submit" class="btn btn-primary" style="margin: auto;width: 180px;display: grid;">Save</button>
                                    </form>
                                        <p style="text-align: left;position: absolute;left: 29px;bottom: 11px;">
                                        <span class="badge" style="background:#464086;width: 10px;height: 10px;display: inline-block;" ></span> Customer 
                                        <span class="badge" style="background:#19b313;width: 10px;height: 10px;display: inline-block;margin-left: 10px;" ></span> Pharmacy 
                                        <span class="badge" style="background:#f3b567;width: 10px;height: 10px;display: inline-block;margin-left: 10px;" ></span> Office                                   
                                        </p>
                                        <h5 style="width: 200px;text-align: left;position: absolute;right: 10px;bottom: 68px;"> </h5>
                                    @foreach($offices as $office)
                                        <div class="office_block office{{$office->id}}">
                                            <div>
                                                <input type="hidden" class="type_id" data-id="{{$office->id}}">
                                                <p class="order_id mb-0" data-id="{{$office->id}}" data-type="office">Office #{{$office->id}}</p>
                                            </div>
                                            <div>
                                                <svg fill="#fff" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 30 30"><path d="M30 12h-12v-12h-5v12h-12v5h12v12h5v-12h12z"/></svg>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <h5>Pharmacies</h5>
                                    <div id="pharmacies_list" class="card_height">
                                    @foreach($orders as $order)
                                        <div class="pharmacie_block pharmacy{{$order->pharmacy_id}}" @if(in_array($order->id.','.$order->pharmacy_id,$pharmacy_routes_priority)) style="display:none;" @endif>
                                            <div class="left">
                                                <b class="order_id" ondblclick="window.open('/orders/{{$order->pharmacy_id}}/show/{{$order->id}}','_blank')" data-id="{{$order->id}}" data-type="pharmacy">Order #{{$order->id}}</b><br>
                                                <span class="type_id" data-latlng="{{$order->pharmacylocation}}" data-id="{{$order->pharmacy_id}}" style="display: block;width: auto;overflow: hidden;white-space: nowrap;text-overflow: ellipsis;">{{$order->pharmacyname}}</span>
                                            </div>
                                            <div class="right">
                                                 <b class="" data-id="" data-type="">{{$order->delivery_time}}</b><br>
                                                 <span class="type_id" ondblclick="window.open('/pharmacys/edit/{{$order->pharmacy_id}}','_blank')" data-latlng="{{$order->pharmacylocation}}" data-id="{{$order->pharmacy_id}}">Pharmacy #{{$order->pharmacy_id}}</span>
                                            </div>
                                            <div class="text-center date">
                                                Need Delivery: <span style="font-size:100%;" class="badge @if(empty($order->finish) && strtotime(date('Y-m-d'))==strtotime($order->delivery_date ?? '')){{'bg-success'}}@elseif(empty($order->finish) && strtotime(date('Y-m-d'))>strtotime($order->delivery_date ?? '')){{'bg-danger'}}@else{{'bg-light'}}@endif">{{date('m/d/Y', strtotime($order->delivery_date ?? ''))}} @if(!empty($order->delivery_time_range) && $order->delivery_time_range!='9:00 AM;12:00 AM')<b>{{str_replace(':00','',str_replace(';',' - ',$order->delivery_time_range))}}</b>@endif</span>
                                            </div>
                                        </div>
                                    @endforeach
                                    </div> 
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <h5>Patients</h5>
                                    <div id="patients_list" class="card_height">
                                    @foreach($orders as $order)
                                        <div class="patient_block patient{{$order->user_id}} @if($order->not_delivered==1) disabled @endif @if(!empty($order->special_instructions)){{'special'}}@endif" title="{{$order->special_instructions}}" @if(in_array($order->id.','.$order->user_id,$patient_routes_priority)) style="display:none;" @endif>
                                            <div class="left">
                                                <b class="order_id" ondblclick="window.open('/orders/{{$order->pharmacy_id}}/show/{{$order->id}}','_blank')" data-id="{{$order->id}}" data-type="patient">Order #{{$order->id}}</b><br>
                                                <span class="type_id"  data-latlng="{{$order->userlocation}}" data-id="{{$order->user_id}}" style="display: block;width: auto;overflow: hidden;white-space: nowrap;text-overflow: ellipsis;">{{$order->useraddress}}</span>
                                            </div>
                                            <div class="right">
                                                <b class="type_id" ondblclick="window.open('/patients/{{$order->pharmacy_id}}/edit/{{$order->user_id}}','_blank')" data-latlng="{{$order->userlocation}}" data-id="{{$order->user_id}}">Patient #{{$order->user_id}}</b><br>
                                                @if(empty($order->userlocation))
                                                <b class="text-danger">Location Empty</b>
                                                @else
                                                <b>ZIP #{{$order->userzip}}</b>
                                                @endif
                                            </div>
                                            <div class="text-center date">
                                                Need Delivery: <span style="font-size:100%;" class="badge @if(empty($order->finish) && strtotime(date('Y-m-d'))==strtotime($order->delivery_date ?? '')){{'bg-success'}}@elseif(empty($order->finish) && strtotime(date('Y-m-d'))>strtotime($order->delivery_date ?? '')){{'bg-danger'}}@else{{'bg-light'}}@endif">{{date('m/d/Y', strtotime($order->delivery_date ?? ''))}} @if(!empty($order->delivery_time_range) && $order->delivery_time_range!='9:00 AM;12:00 AM')<b>{{str_replace(':00','',str_replace(';',' - ',$order->delivery_time_range))}}</b>@endif</span>
                                            </div>
                                        </div>
                                    @endforeach
                                    </div> 
                                </div>
                            </div>
                        </div>
                        <!-- end col -->
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4>Map</h4>
                                    <button id="load_map" class="btn btn-primary" style="margin-left: 50%;transform: translateX(-50%);">Show Map</button>
                                    <div class="block_map" style="display:none;">
                                        <div class="row m-0">
                                            <div class="col-sm-2 mt-1 form-check mb-2">
                                                <input type="checkbox" value="1" name="pharmacies" class="form-check-input" id="pharmacies" checked>
                                                <label class="form-check-label" for="pharmacies">Pharmacies</label>
                                            </div>
                                            <div class="col-sm-2 mt-1 form-check mb-2">
                                                <input type="checkbox" value="1" name="patients" class="form-check-input" id="patients" checked>
                                                <label class="form-check-label" for="patients">Patients</label>
                                            </div>
                                            @if(!empty(request()->query('show_route')) && request()->query('show_route')>0)
                                            <div class="col-sm-5 mt-0 mb-2">
                                                <button class="btn btn-outline-info" id="setup_route">Set Up Route</button>
                                                <small class="ml-2" style="display:none;">After Set Up Route you need save the route from above!</small>
                                            </div>
                                            @endif
                                        </div>
                                        <p id="route-map-message" class="text-muted" role="status" hidden></p>
                                        <div id="map" style="height: 600px;width: 100%;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
@include('routes.office-order-picker')
@endsection
@section('footerScript')
<script src="{{ URL::asset('/js/bootstrap.bundle.min.js')}}"></script>
<script src="{{ URL::asset('/js/bootstrap-datepicker.min.js')}}"></script>
<script src="{{ URL::asset('/js/select2.min.js')}}"></script>
<script src="{{ URL::asset('/js/jquery.bootstrap-touchspin.min.js')}}"></script>
<script src="{{ URL::asset('/js/jquery-ui.min.js')}}" type="text/javascript"></script>
<script type="text/javascript" src="{{ URL::asset('/js/bootstrap-multiselect.min.js')}}"></script>
<script src="/js/BootstrapMenu.min.js"></script>
<script src="{{ URL::asset('/js/route-order-select.js') }}"></script>
<script src="{{ URL::asset('/js/office-order-picker.js?v=2') }}"></script>
<script src="{{ URL::asset('/js/route-map-tools.js?v=1') }}"></script>
@php($officeOrderIds = $orders->keys()->merge($orders_id->pluck('order_id'))->values()->all())
<script type="text/javascript">
    window.officeOrderPicker.init(@json($officeOrderIds));
    window.driverRoutePage = {
        orderIds: {{ Illuminate\Support\Js::from($orders_id->pluck('order_id')->map(fn ($id) => (string) $id)->values()) }},
        hasStops: {{ Illuminate\Support\Js::from(! empty($routes_priority[0])) }},
        mapsKey: {{ Illuminate\Support\Js::from(config('app.googlemaps_apikey')) }},
    };
</script>
<script src="{{ URL::asset('/js/driver-route-menu.js?v=1') }}"></script>
<script src="{{ URL::asset('/js/driver-route-pay.js?v=1') }}"></script>
<script src="{{ URL::asset('/js/driver-route-page.js?v=1') }}"></script>
<script>
    var map;
    var _myPolygon;
    var areaSelector;
    var markersArray = [];
    var locationDriver = "{{ $locations->location ?? '' }}"; // empty until the driver shares a location
    function mapCenter() { return window.routeMapTools.center([locationDriver].concat(locationPharmacy, locationPatients, locationOffice)); }
    var locationOffice = [@foreach($offices as $office)
    @if(!request()->boolean('show_route') || in_array('office'.$office->id, $show_ids))
    @if(!empty($office->location))
    @json($office->location),
    @endif
    @endif
    @endforeach];
    var locationOfficeID = [@foreach($offices as $office)
    @if(!request()->boolean('show_route') || in_array('office'.$office->id, $show_ids))
    @if(!empty($office->location))
    @json((string) $office->id),
    @endif
    @endif
    @endforeach];
    var locationPatients = [@foreach($patients_locations as $patients_location)
    @if(!empty($patients_location['id']) && !empty($patients_location['location']))
    @if(!empty(request()->query('show_route')) && request()->query('show_route')>0)
        @if((!empty($show_ids) && in_array("patient".$patients_location['id'],$show_ids)))
        "{{ $patients_location['location'] }}",
        @endif
    @else
    "{{ $patients_location['location'] }}",
    @endif
    @endif
    @endforeach];
    var locationPharmacy = [@foreach($pharmacy_locations as $pharmacy_location)
    @if(!empty($pharmacy_location['id']) && !empty($pharmacy_location['location']))
    @if(!empty(request()->query('show_route')) && request()->query('show_route')>0)
        @if((!empty($show_ids) && in_array("pharmacy".$pharmacy_location['id'],$show_ids)))
        "{{ $pharmacy_location['location'] }}",
        @endif
    @else
    "{{ $pharmacy_location['location'] }}",
    @endif
    @endif
    @endforeach];
    var locationPatientsID = [@foreach($patients_locations as $patients_location)
    @if(!empty($patients_location['id']) && !empty($patients_location['location']))
    @if(!empty(request()->query('show_route')) && request()->query('show_route')>0)
        @if(!empty($show_ids) && in_array("patient".$patients_location['id'],$show_ids))
            @if(isset($show_priority['patient'.$patients_location['id']]))
            "{{ $patients_location['id'] }};{{ $show_priority['patient'.$patients_location['id']]+1 }}",
            @else
            "0",
            @endif
        @endif
    @else
    "{{ $patients_location['id'] }}",
    @endif
    @endif
    @endforeach];
    var locationPharmacyID = [@foreach($pharmacy_locations as $pharmacy_location)
    @if(!empty($pharmacy_location['id']) && !empty($pharmacy_location['location']))
    @if(!empty(request()->query('show_route')) && request()->query('show_route')>0)
        @if(!empty($show_ids) && in_array("pharmacy".$pharmacy_location['id'],$show_ids))
            @if(isset($show_priority['pharmacy'.$pharmacy_location['id']]))
            "{{ $pharmacy_location['id'] }};{{ $show_priority['pharmacy'.$pharmacy_location['id']]+1 }}",
            @else
            "0",
            @endif
        @endif
    @else
    "{{ $pharmacy_location['id'] }}",
    @endif
    @endif
    @endforeach];
</script>
<script src="{{ URL::asset('/js/driver-route-map.js?v=1') }}"></script>
@endsection
