@extends('layouts.master')

@section('title') Driver Detail @endsection
@section('headerCss')
<link rel="stylesheet" href="{{ URL::asset('/css/bootstrap-multiselect.min.css')}}" type="text/css"/>
<link rel="stylesheet" href="{{ URL::asset('/css/bootstrap-lib.min.css')}}">
<link href="{{ URL::asset('/css/bootstrap-datepicker.min.css')}}" rel="stylesheet">
<link href="{{ URL::asset('/css/select2.min.css')}}" rel="stylesheet" type="text/css">
<link href="{{ URL::asset('/css/jquery.bootstrap-touchspin.min.css')}}" rel="stylesheet">
<style>
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
    border: 1px solid #7a6fbe;
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
	background-color: #7a6fbe;
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
    height: 17px;
    width: 60px;
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
    line-height: 1px;
}
.btn-check:active+.btn-outline-primary, .btn-check:checked+.btn-outline-primary, .btn-outline-primary.active, .btn-outline-primary.dropdown-toggle.show, .btn-outline-primary:active {
    color: #fff;
    background-color: #7a6fbe;
    border-color: #7a6fbe;
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
                    @if(!empty($_GET['order']))
                    <div class="alert alert-warning" style="height: 59px;font-weight:bold;" role="alert">
                        Are you sure you want to assign order #{{$_GET['order']}} to this driver?
                        <div style="float:right;">
                            <button class="btn btn-success" onclick="$('#confirm_order').submit()">Yes</button>
                            <button class="btn btn-danger" onclick="location.href='https://cp.a2brx.com/routes-list/show/{{$_GET['order']}}'">No</button>
                        </div>
                        <form method="POST" id="confirm_order" style="display:none;">
                            @csrf
                            <input type="hidden" name="confirm_order_id" value="{{$_GET['order']}}">
                        </form>
                    </div>
                    @endif
                    @if(\Session::has('success'))
                        <div class="alert alert-success">
                            {!! \Session::get('success') !!}
                        </div>
                    @endif
                    @if(\Session::has('error'))
                        <div class="alert alert-danger">
                            {!! \Session::get('error') !!}
                        </div>
                    @endif
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
                                                    <option value="{{$order_statuse->id}}" selected>{{$order_statuse->name}}</option>
                                                @else
                                                    <option value="{{$order_statuse->id}}">{{$order_statuse->name}}</option>
                                                @endif
                                            @else
                                                <option value="{{$order_statuse->id}}">{{$order_statuse->name}}</option>
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
                                                    <option value="{{$order_statuse->id}}" selected>{{$order_statuse->name}}</option>
                                                @else
                                                    <option value="{{$order_statuse->id}}">{{$order_statuse->name}}</option>
                                                @endif
                                            @else
                                                <option value="{{$order_statuse->id}}">{{$order_statuse->name}}</option>
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
                                    </form>
                                    <form method="POST" id="eta_calculate" class="d-none">
                                        @csrf
                                        <input type="hidden" name="eta_calculate" value="1">
                                    </form>
                                    <div class="row" style="margin-bottom: 15px;">
                                        <form method="POST" id="auto_route">
                                            @csrf
                                            <input type="hidden" name="auto_route" value="1">
                                            <span style="background: #2b3a4a;color: #ffffff;padding: 7px 10px;margin: 1px 8px 0 0;cursor:pointer;" onclick="if(confirm('Are you sure?')){$('#auto_route').submit();}">Automate route</span> 
                                            <input type="radio" class="btn-check" name="type" value="driver" id="outlined2" autocomplete="off" checked>
                                            <label class="btn btn-outline-secondary" style="margin-right: 0px !important;border-top-right-radius: 0px;border-bottom-right-radius: 0px;" for="outlined2">Driver</label>
                                            <input type="radio" class="btn-check" name="type" value="first" id="outlined3" autocomplete="off">
                                            <label class="btn btn-outline-secondary" style="margin-left: -4px !important;border-top-left-radius: 0px;border-bottom-left-radius: 0px;" for="outlined3">First point</label>
                                        </form>
                                    </div>
                                    <form method="POST">
                                        <div class="tariff">
                                            <input type="radio" class="btn-check" value="4" name="type_pay" id="danger-outlined" required autocomplete="off" {{ ($type_pay==4)?'checked':'' }}>
                                            <label class="btn btn-outline-primary" for="danger-outlined">Half</label>
                                            <input type="radio" class="btn-check" value="5" name="type_pay" id="danger-outlined2" required autocomplete="off" {{ ($type_pay==5)?'checked':'' }}>
                                            <label class="btn btn-outline-primary" for="danger-outlined2">Full</label>
                                            <input type="radio" class="btn-check" value="1" name="type_pay" id="success-outlined" required autocomplete="off" {{ ($type_pay==1)?'checked':'' }}>
                                            <label class="btn btn-outline-primary" for="success-outlined">Flat rate</label>
                                            <div class="input-group" @if($type_pay!=1){{"style=display:none;"}}@endif >
                                                <div class="input-group-text">$</div>
                                                <input type="number" step="0.01" name="pay_value" value="{{ $pay_value }}" required class="form-control">
                                            </div>
                                            <button type="button" class="btn btn-outline-warning" onclick="if(confirm('Are you sure (after clicking, the driver will be credited with his payment)?')){$('#close_route').submit();}">Сompleted</button>
                                            <button type="button" class="btn btn-outline-secondary" onclick="if(confirm('Are you sure?')){$('#eta_calculate').submit();}">Refresh ETA</button>
                                        </div>
                                        <h4>Routes</h4>
                                        <p style="float:left;">Distance: {{$driver->distance}} miles</p>
                                        <p style="float:right;">Duration: {{floor($driver->eta / 60)}} hr {{$driver->eta % 60}} min</p>
                                        <div class="wrapper">
                                            <h4 class="drag-text">Drag an item here...</h4>
                                            @for($n=0;$n<300;$n++)
                                                @if(!empty($routes_priority[$n]))
                                                    @if($routes_priority[$n]->type=='pharmacy')
                                                        @php
                                                            if(!empty($_GET["show_route"]) && $_GET["show_route"]>0) {
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
                                                            if(!empty($_GET["show_route"]) && $_GET["show_route"]>0) {
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
                                                        <div class="plus-item active-yellow office{{$routes_priority[$n]->order_id}}">
                                                            <input type="hidden" name="order_id[]" value="{{$routes_priority[$n]->order_id}}">
                                                            <input type="hidden" name="type[]" value="{{$routes_priority[$n]->type}}">
                                                            <input type="hidden" name="type_id[]" value="{{$routes_priority[$n]->type_id}}">
                                                            <div class="eta_block">
                                                                <i class="mdi mdi-timer"></i> <span class="eta">@if(floor($routes_priority[$n]->eta / 60)>0){{floor($routes_priority[$n]->eta / 60)}} hr @endif{{$routes_priority[$n]->eta % 60}} min</span><br>
                                                                <i class="mdi mdi-clock-outline"></i> <span class="time">{{date("h:i A",strtotime('now + '.$routes_priority[$n]->eta.' minutes'))}}</span><br>
                                                                <span class="num">{{($n+1)}}</span>
                                                            </div>
                                                            <p class="order">Order:
                                                                <select class="multiple_select" required multiple="multiple">
                                                                    @foreach(explode(',',$routes_priority[$n]->order_id) as $row)
                                                                        <option selected value="{{$row}}">{{$row}}</option>
                                                                    @endforeach
                                                                @foreach($orders_id as $row)
                                                                    @if(!in_array($row->order_id,explode(',',$routes_priority[$n]->order_id)))
                                                                    <option value="{{$row->order_id}}">{{$row->order_id}}</option>
                                                                    @endif
                                                                @endforeach
                                                                </select>
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
                                                Need Delivery: <span style="font-size:100%;" class="badge @if(empty($order->finish) && strtotime(date('Y-m-d'))==strtotime($order->delivery_date)){{'bg-success'}}@elseif(empty($order->finish) && strtotime(date('Y-m-d'))>strtotime($order->delivery_date)){{'bg-danger'}}@else{{'bg-light'}}@endif">{{date('m/d/Y', strtotime($order->delivery_date))}} @if(!empty($order->delivery_time_range) && $order->delivery_time_range!='9:00 AM;12:00 AM')<b>{{str_replace(':00','',str_replace(';',' - ',$order->delivery_time_range))}}</b>@endif</span>
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
                                                Need Delivery: <span style="font-size:100%;" class="badge @if(empty($order->finish) && strtotime(date('Y-m-d'))==strtotime($order->delivery_date)){{'bg-success'}}@elseif(empty($order->finish) && strtotime(date('Y-m-d'))>strtotime($order->delivery_date)){{'bg-danger'}}@else{{'bg-light'}}@endif">{{date('m/d/Y', strtotime($order->delivery_date))}} @if(!empty($order->delivery_time_range) && $order->delivery_time_range!='9:00 AM;12:00 AM')<b>{{str_replace(':00','',str_replace(';',' - ',$order->delivery_time_range))}}</b>@endif</span>
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
                                            @if(!empty($_GET["show_route"]) && $_GET["show_route"]>0)
                                            <div class="col-sm-5 mt-0 mb-2">
                                                <button class="btn btn-outline-info" id="setup_route">Set Up Route</button>
                                                <small class="ml-2" style="display:none;">After Set Up Route you need save the route from above!</small>
                                            </div>
                                            @endif
                                        </div>
                                        <div id="map" style="height: 600px;width: 100%;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
@endsection
@section('footerScript')
<script src="{{ URL::asset('/js/bootstrap.bundle.min.js')}}"></script>
<script src="{{ URL::asset('/js/bootstrap-datepicker.min.js')}}"></script>
<script src="{{ URL::asset('/js/select2.min.js')}}"></script>
<script src="{{ URL::asset('/js/jquery.bootstrap-touchspin.min.js')}}"></script>
<script src="{{ URL::asset('/js/jquery-ui.min.js')}}" type="text/javascript"></script>
<script type="text/javascript" src="{{ URL::asset('/js/bootstrap-multiselect.min.js')}}"></script>
<script src="/js/BootstrapMenu.min.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        var menu = new BootstrapMenu('.plus-item.active-yellow,.plus-item.active-blue,.plus-item.active-green', {
            fetchElementData: function($rowElem) {
                return $rowElem;
            },
            actions: [{
                name: ' Delete',
                iconClass: 'mdi mdi-trash-can',
                onClick: function(el) {
                    if(el.find("input[name='type[]']").val()=='patient') {
                        var arr = [];
                        if($(el).find('select').is('select')) {
                            $.each($(el).find('select').val(), function( index, value ) {
                                $(el).find('select').find('option[value="'+value+'"]').remove();
                                arr.push(value.toString());
                                $(".patient_block").find('.order_id[data-id="'+value+'"][data-type="patient"]').next().next('.type_id[data-id="'+el.find("input[name='type_id[]']").val()+'"]').parent('.left').parent('.patient_block').show();
                            });
                            if($(el).find('select option').length==0) {
                                $(el).remove();
                            }
                            $(el).find('.multiple_select').multiselect('rebuild');
                            $(el).find('.multiple_select').multiselect('selectAll', false);
                            $(el).find('.multiple_select').change();
                        } else {
                            $(".patient_block").find('.order_id[data-id="'+el.find("input[name='order_id[]']").val()+'"][data-type="patient"]').next().next('.type_id[data-id="'+el.find("input[name='type_id[]']").val()+'"]').parent('.left').parent('.patient_block').show();
                            $(el).remove();
                            arr.push(el.find("input[name='order_id[]']").val().toString());
                        }
                        draggablePatient();
                        $.each(arr, function( index, value ) {
                            if(orderIds.indexOf(value.toString())>-1 && !($('body').find('.active-green').find("input[name='order_id[]'][value='"+value+"']").is('input') || $('body').find('.active-green').find('.multiple_select').find('option[value="'+value+'"]').is('option') || $('body').find('.active-blue').find("input[name='order_id[]'][value='"+value+"']").is('input') || $('body').find('.active-blue').find('.multiple_select').find('option[value="'+value+'"]').is('option'))) {
                                orderIds.splice(orderIds.indexOf(value.toString()), 1);
                            }
                        });
                        updateOfficeId(orderIds);
                    }
                    if(el.find("input[name='type[]']").val()=='pharmacy') {
                        var arr = [];
                        if($(el).find('select').is('select')) {
                            $.each($(el).find('select').val(), function( index, value ) {
                                $(el).find('select').find('option[value="'+value+'"]').remove();
                                arr.push(value.toString());
                                $(".pharmacie_block").find('.order_id[data-id="'+value+'"][data-type="pharmacy"]').next().next('.type_id[data-id="'+el.find("input[name='type_id[]']").val()+'"]').parent('.left').parent('.pharmacie_block').show();
                            });
                            if($(el).find('select option').length==0) {
                                $(el).remove();
                            }
                            $(el).find('.multiple_select').multiselect('rebuild');
                            $(el).find('.multiple_select').multiselect('selectAll', false);
                            $(el).find('.multiple_select').change();
                        } else {
                            $(".pharmacie_block").find('.order_id[data-id="'+el.find("input[name='order_id[]']").val()+'"][data-type="pharmacy"]').next().next('.type_id[data-id="'+el.find("input[name='type_id[]']").val()+'"]').parent('.left').parent('.pharmacie_block').show();
                            $(el).remove();
                            arr.push(el.find("input[name='order_id[]']").val().toString());
                        }
                        draggablePharmacy();
                        $.each(arr, function( index, value ) {
                            if(orderIds.indexOf(value.toString())>-1 && !($('body').find('.active-green').find("input[name='order_id[]'][value='"+value+"']").is('input') || $('body').find('.active-green').find('.multiple_select').find('option[value="'+value+'"]').is('option') || $('body').find('.active-blue').find("input[name='order_id[]'][value='"+value+"']").is('input') || $('body').find('.active-blue').find('.multiple_select').find('option[value="'+value+'"]').is('option'))) {
                                orderIds.splice(orderIds.indexOf(value.toString()), 1);
                            }
                        });
                        updateOfficeId(orderIds);
                    }
                    if(el.find("input[name='type[]']").val()=='office') {
                        $(el).remove();
                    }
                    reload_priority();
                }
            }]
        });
        var menu2 = new BootstrapMenu('.pharmacie_block,.patient_block,.office_block', {
            fetchElementData: function($rowElem) {
                return $rowElem;
            },
            actions: [{
                name: ' Transfer to Route',
                iconClass: 'mdi mdi-transfer-left',
                onClick: function(el2) {
                    var type = $(el2).find('.order_id').data('type');
                    $(".wrapper").addClass("activeted");
                    var el = $(".wrapper").find(".plus-item.no-active").first();
                    if(type=='patient') {
                        if($('.plus-item').is('.patient'+$(el2).find('.type_id').data('id'))) {
                            if($('.plus-item.patient'+$(el2).find('.type_id').data('id')).find('select').is('select')) {
                                $('.plus-item.patient'+$(el2).find('.type_id').data('id')).find('select').append('<option selected value="'+$(el2).find('.order_id').data('id')+'">'+$(el2).find('.order_id').data('id')+' ('+$(el2).find('.text-center.date span').first().clone().children().remove().end().text().replace(/ /g,"")+')</option>');
                                $('.plus-item.patient'+$(el2).find('.type_id').data('id')).find('.multiple_select').multiselect('rebuild');
                                $('.plus-item.patient'+$(el2).find('.type_id').data('id')).find('.multiple_select').multiselect('selectAll', false);
                                $('.plus-item.patient'+$(el2).find('.type_id').data('id')).find('.multiple_select').change();
                            } else {
                                $('.plus-item.patient'+$(el2).find('.type_id').data('id')).find('.order').html('Order: <select class="multiple_select" multiple="multiple"><option selected value="'+$('.plus-item.patient'+$(el2).find('.type_id').data('id')).find("input[name='order_id[]']").val()+'">'+$('.plus-item.patient'+$(el2).find('.type_id').data('id')).find("input[name='order_id[]']").val()+' ('+$('.plus-item.patient'+$(el2).find('.type_id').data('id')).find("span.badge").first().clone().children().remove().end().text().replace(/ /g,"")+')</option><option selected value="'+$(el2).find('.order_id').data('id')+'">'+$(el2).find('.order_id').data('id')+' ('+$(el2).find('.text-center.date span').first().clone().children().remove().end().text().replace(/ /g,"")+')</option></select>');
                                $('.plus-item.patient'+$(el2).find('.type_id').data('id')).find('.multiple_select').multiselect({
                                    includeSelectAllOption: true
                                });
                                $('.plus-item.patient'+$(el2).find('.type_id').data('id')).find('.multiple_select').multiselect('selectAll', false);
                                $('.plus-item.patient'+$(el2).find('.type_id').data('id')).find('.multiple_select').change();
                            }
                        } else {
                            el.find("p.number").remove();
                            el.removeClass("no-active");
                            el.find('.eta_block').show();
                            el.find("input[name='order_id[]']").val($(el2).find('.order_id').data('id'));
                            el.find("input[name='type[]']").val(type);
                            el.find("input[name='type_id[]']").val($(el2).find('.type_id').data('id'));
                            el.addClass("active-blue");
                            el.addClass("patient"+$(el2).find('.type_id').data('id'));
                            el.append("<p class='order'>Order: "+$(el2).find('.order_id').data('id')+"</p>");
                            el.append("<p>Customer: "+$(el2).find('.type_id').data('id')+"</p>");
                            $(el2).find('.text-center.date span').first().clone().removeAttr('style').appendTo(el);
                            el.attr('title',$(el2).find('.text-center.date span').first().text());
                        }
                        if(orderIds.indexOf($(el2).find('.order_id').data('id').toString())==-1) {
                            orderIds.push($(el2).find('.order_id').data('id').toString());
                        }
                        $(el2).hide();
                        updateOfficeId(orderIds);
                    }
                    if(type=='pharmacy') {
                        if($('.plus-item').is('.pharmacy'+$(el2).find('.type_id').data('id'))) {
                            if($('.plus-item.pharmacy'+$(el2).find('.type_id').data('id')).find('select').is('select')) {
                                $('.plus-item.pharmacy'+$(el2).find('.type_id').data('id')).find('select').append('<option selected value="'+$(el2).find('.order_id').data('id')+'">'+$(el2).find('.order_id').data('id')+' ('+$(el2).find('.text-center.date span').first().clone().children().remove().end().text().replace(/ /g,"")+')</option>');
                                $('.plus-item.pharmacy'+$(el2).find('.type_id').data('id')).find('.multiple_select').multiselect('rebuild');
                                $('.plus-item.pharmacy'+$(el2).find('.type_id').data('id')).find('.multiple_select').multiselect('selectAll', false);
                                $('.plus-item.pharmacy'+$(el2).find('.type_id').data('id')).find('.multiple_select').change();
                            } else {
                                $('.plus-item.pharmacy'+$(el2).find('.type_id').data('id')).find('.order').html('Order: <select class="multiple_select" multiple="multiple"><option selected value="'+$('.plus-item.pharmacy'+$(el2).find('.type_id').data('id')).find("input[name='order_id[]']").val()+'">'+$('.plus-item.pharmacy'+$(el2).find('.type_id').data('id')).find("input[name='order_id[]']").val()+' ('+$('.plus-item.patient'+$(el2).find('.type_id').data('id')).find("span.badge").first().clone().children().remove().end().text().replace(/ /g,"")+')</option><option selected value="'+$(el2).find('.order_id').data('id')+'">'+$(el2).find('.order_id').data('id')+' ('+$(el2).find('.text-center.date span').first().clone().children().remove().end().text().replace(/ /g,"")+')</option></select>');
                                $('.plus-item.pharmacy'+$(el2).find('.type_id').data('id')).find('.multiple_select').multiselect({
                                    includeSelectAllOption: true
                                });
                                $('.plus-item.pharmacy'+$(el2).find('.type_id').data('id')).find('.multiple_select').multiselect('selectAll', false);
                                $('.plus-item.pharmacy'+$(el2).find('.type_id').data('id')).find('.multiple_select').change();
                            }
                        } else {
                            el.find("p.number").remove();
                            el.removeClass("no-active");
                            el.find('.eta_block').show();
                            el.find("input[name='order_id[]']").val($(el2).find('.order_id').data('id'));
                            el.find("input[name='type[]']").val(type);
                            el.find("input[name='type_id[]']").val($(el2).find('.type_id').data('id'));
                            el.addClass("active-green");
                            el.addClass("pharmacy"+$(el2).find('.type_id').data('id'));
                            el.append("<p class='order'>Order: "+$(el2).find('.order_id').data('id')+"</p>");
                            el.append("<p>"+$(el2).find('.left .type_id').text()+"</p>");
                        }
                        if(orderIds.indexOf($(el2).find('.order_id').data('id').toString())==-1) {
                            orderIds.push($(el2).find('.order_id').data('id').toString());
                        }
                        $(el2).hide();
                        updateOfficeId(orderIds);
                    }
                    if(type=='office') {
                        el.find("p.number").remove();
                        el.removeClass("no-active");
                        el.find('.eta_block').show();
                        el.find("input[name='order_id[]']").val($(el2).find('.order_id').data('id'));
                        el.find("input[name='type[]']").val(type);
                        el.find("input[name='type_id[]']").val($(el2).find('.type_id').data('id'));
                        el.addClass("active-yellow");
                        el.addClass("office"+$(el2).find('.type_id').data('id'));
                        el.append("<p class='order'>Order: <select class='multiple_select' required multiple='multiple'></select></p>");
                        el.append("<p>Office #"+$(el2).find('.order_id').data('id')+"</p>");
                        $('.plus-item.office'+$(el2).find('.type_id').data('id')).find('.multiple_select').multiselect({
                            includeSelectAllOption: true
                        });
                        $('.plus-item.office'+$(el2).find('.type_id').data('id')).find('.multiple_select').multiselect('selectAll', false);
                        updateOfficeId(orderIds);
                    }
                }
            }]
        });
        $('.multiple_select').multiselect({
            includeSelectAllOption: true
        });
        $(".select2").selectize()
        $(document).on('change','.tariff [name="type_pay"]',function(){
            if($(this).val()=="1"){
                $(".tariff .input-group").show(200);
            } else {
                $(".tariff .input-group").hide(200);
            }
        });
        $(document).on('change','.multiple_select',function(){
            $(this).parent().parent().parent().find('input[name="order_id[]"]').val($(this).val().join());
        });
        $('.modal2 input[name="priority"]').TouchSpin({
            min: 1,
            max: $(".wrapper").find(".active-green,.active-yellow,.active-blue").length,
            step: 1,
        });
        $('body').on('click','.patient_block.special',function(){
            $(this).toggleClass('open');
        });
        $('body').on('click','.plus-item.active-blue.special',function(){
            $(this).toggleClass('open');
        });
    });
</script>
<script>
    var setup_route = false;
    var setup_marker;
    var showPharmacy = true;
    var showPatient = true;
    $("#pharmacies").on("change",function(){
        if(this.checked) {
            showPharmacy = true;
        } else {
            showPharmacy = false;
        }
        initMap();
    });
    $("#patients").on("change",function(){
        if(this.checked) {
            showPatient = true;
        } else {
            showPatient = false;
        }
        initMap();
    });
    $("#setup_route").on("click",function(){
        if($(this).hasClass("active")==true) {
            $(this).removeClass("active");
            $(this).next("small").hide(200);
            setup_route=false;
        } else {
            $(this).addClass("active");
            $(this).next("small").show(200);
            setup_route=true;
        }
    });
    $(".setup_route_yes").on("click",function(){
        var id0 = $('.modal2 input[name="id"]').val();
        var prior = $('.modal2 input[name="priority"]').val();
        if(id0!="" && prior!="") {
            if((prior-2)<0) {
                $(".wrapper .drag-text").after($(".wrapper").find("."+id0));
            } else {
                if($('.wrapper .plus-item').index($('.wrapper').find("."+id0))<prior) {
                    $(".wrapper").find(".plus-item").eq((prior-1)).after($(".wrapper").find("."+id0));
                } else {
                    $(".wrapper").find(".plus-item").eq((prior-2)).after($(".wrapper").find("."+id0));
                }
            }
            for (let marker0 of markersArray) {
                var label = marker0.getLabel();
                label.text = String($('.wrapper .plus-item').index($('.wrapper').find("."+marker0.id))+1);
                marker0.setLabel(label);
            };
        }
        $('.modal2').fadeOut(300);
    });
    function reload_priority() {
        var nmax = 300-$('.plus-item').length;
        for(n=0;n<nmax;n++) {
            $('.wrapper').append(element.replaceAll("$num",$('.plus-item').length+1));
        }
    }
    function draggablePharmacy() {
        $(".pharmacie_block").draggable({ cursor: "move",helper: "clone", cursorAt: { top: 20, left: 120 },revert:"invalid", revertDuration: 100,
            start: function() {
                $(".wrapper").addClass("active");
            }, 
            stop: function() {
                $(".wrapper").removeClass("active");
                $(this).removeClass("focus");
            } 
        });
    }
    function draggablePatient() {
        $(".patient_block").draggable({ cursor: "move",helper: "clone", cursorAt: { top: 20, left: 120 },revert:"invalid", revertDuration: 100,
            start: function() {
                $(".wrapper").addClass("active");
            }, 
            stop: function() {
                $(".wrapper").removeClass("active");
                $(this).removeClass("focus");
            } 
        });
    }
    var element = '<div class="plus-item no-active"><input type="hidden" name="order_id[]"><input type="hidden" name="type[]"><input type="hidden" name="type_id[]"><div class="eta_block" style="display:none;"><i class="mdi mdi-timer"></i> <span class="eta"></span><br><i class="mdi mdi-clock-outline"></i> <span class="time"></span><br><span class="num">$num</span></div><p class="number">$num</p></div>';
    function updateOfficeId(ids) {
        $(".plus-item.active-yellow").each(function(index) {
            var el0 =  $(this);
            var arr = [];
            el0.find('.multiple_select').find('option').each(function(index) {
                if(ids.indexOf($(this).val().toString())==-1) {
                    $(this).remove();
                } else {
                    arr.push($(this).val().toString());
                }
            });
            ids.forEach(function(currentValue, index, array) {
                if(arr.indexOf(currentValue)==-1) {
                    el0.find('.multiple_select').append('<option value="'+currentValue+'">'+currentValue+'</option>');
                }
            });
            el0.find('.multiple_select').multiselect('rebuild');
            el0.find('.multiple_select').parent().parent().parent().find('input[name="order_id[]"]').val(el0.find('.multiple_select').val().join());
        });
    }
    var orderIds = [@foreach($orders_id as $order_id)
    "{{ $order_id->order_id }}",
    @endforeach];
    $(document).ready(function() {
        $(".plus-item.active-yellow").eq(0).find('.multiple_select').find('option').each(function(index) {
            //orderIds.push($(this).val().toString());
        });
        $('body').on('click','.close',function() {
            $('.modal2').fadeOut(300);
            $('.modal1').fadeOut(300);
            $('.order_ids').remove();
            _myPolygon.setMap(null);
        });
        $('body').on('click','.close0',function() {
            $('.modal1').fadeOut(300);
            $('.modal2').fadeOut(300);
            $('.order_ids').remove();
            _myPolygon.setMap(null);
        });
        draggablePharmacy();
        draggablePatient();
        $(".office_block").draggable({ cursor: "move",helper: "clone", cursorAt: { top: 20, left: 120 },revert:"invalid", revertDuration: 100,
            start: function() {
                $(".wrapper").addClass("active");
            }, 
            stop: function() {
                $(".wrapper").removeClass("active");
                $(this).removeClass("focus");
            }
        });
        $(".patient_block.disabled").draggable("disable");
        $(".patient_block.disabled").on('click',function(){
            alert("You cannot deliver this order to the patient, since it has been returned to the office more than 3 times, you can return this order to the pharmacy");
        });
        @if(!empty($routes_priority[0]))
            $(".wrapper").sortable({cursor: "move", cursorAt: { top: 30, left: 55 },revert:"invalid", revertDuration: 100,
            start: function() {
                $(".trash_block").addClass("active");
            }, 
            stop: function() {
                $(".trash_block").removeClass("active");
                $(".trash_block").removeClass("focuse");
                var last = 0;
                $('.plus-item').each(function( index ) {
                    if($(this).find("input[name='type[]']").val()=='') {
                        $(this).remove();
                    }
                });
                reload_priority();
            }});
            $(".wrapper").disableSelection();
        @endif
        $(".trash_block").droppable({
            over:function(){
                $(".trash_block").addClass("focuse");
            },
            drop:function(event, ui){
                $(".trash_block").removeClass("focuse");
                var el = $(ui.draggable);
                if(el.find("input[name='type[]']").val()=='patient') {
                    var arr = [];
                    if($(ui.draggable).find('select').is('select')) {
                        $.each($(ui.draggable).find('select').val(), function( index, value ) {
                            $(ui.draggable).find('select').find('option[value="'+value+'"]').remove();
                            arr.push(value.toString());
                            $(".patient_block").find('.order_id[data-id="'+value+'"][data-type="patient"]').next().next('.type_id[data-id="'+el.find("input[name='type_id[]']").val()+'"]').parent('.left').parent('.patient_block').show();
                        });
                        if($(ui.draggable).find('select option').length==0) {
                            $(ui.draggable).remove();
                        }
                        $(ui.draggable).find('.multiple_select').multiselect('rebuild');
                        $(ui.draggable).find('.multiple_select').multiselect('selectAll', false);
                        $(ui.draggable).find('.multiple_select').change();
                    } else {
                        $(".patient_block").find('.order_id[data-id="'+el.find("input[name='order_id[]']").val()+'"][data-type="patient"]').next().next('.type_id[data-id="'+el.find("input[name='type_id[]']").val()+'"]').parent('.left').parent('.patient_block').show();
                        $(ui.draggable).remove();
                        arr.push(el.find("input[name='order_id[]']").val().toString());
                    }
                    draggablePatient();
                    $.each(arr, function( index, value ) {
                        if(orderIds.indexOf(value.toString())>-1 && !($('body').find('.active-green').find("input[name='order_id[]'][value='"+value+"']").is('input') || $('body').find('.active-green').find('.multiple_select').find('option[value="'+value+'"]').is('option') || $('body').find('.active-blue').find("input[name='order_id[]'][value='"+value+"']").is('input') || $('body').find('.active-blue').find('.multiple_select').find('option[value="'+value+'"]').is('option'))) {
                            orderIds.splice(orderIds.indexOf(value.toString()), 1);
                        }
                    });
                    updateOfficeId(orderIds);
                }
                if(el.find("input[name='type[]']").val()=='pharmacy') {
                    var arr = [];
                    if($(ui.draggable).find('select').is('select')) {
                        $.each($(ui.draggable).find('select').val(), function( index, value ) {
                            $(ui.draggable).find('select').find('option[value="'+value+'"]').remove();
                            arr.push(value.toString());
                            $(".pharmacie_block").find('.order_id[data-id="'+value+'"][data-type="pharmacy"]').next().next('.type_id[data-id="'+el.find("input[name='type_id[]']").val()+'"]').parent('.left').parent('.pharmacie_block').show();
                        });
                        if($(ui.draggable).find('select option').length==0) {
                            $(ui.draggable).remove();
                        }
                        $(ui.draggable).find('.multiple_select').multiselect('rebuild');
                        $(ui.draggable).find('.multiple_select').multiselect('selectAll', false);
                        $(ui.draggable).find('.multiple_select').change();
                    } else {
                        $(".pharmacie_block").find('.order_id[data-id="'+el.find("input[name='order_id[]']").val()+'"][data-type="pharmacy"]').next().next('.type_id[data-id="'+el.find("input[name='type_id[]']").val()+'"]').parent('.left').parent('.pharmacie_block').show();
                        $(ui.draggable).remove();
                        arr.push(el.find("input[name='order_id[]']").val().toString());
                    }
                    draggablePharmacy();
                    $.each(arr, function( index, value ) {
                        if(orderIds.indexOf(value.toString())>-1 && !($('body').find('.active-green').find("input[name='order_id[]'][value='"+value+"']").is('input') || $('body').find('.active-green').find('.multiple_select').find('option[value="'+value+'"]').is('option') || $('body').find('.active-blue').find("input[name='order_id[]'][value='"+value+"']").is('input') || $('body').find('.active-blue').find('.multiple_select').find('option[value="'+value+'"]').is('option'))) {
                            orderIds.splice(orderIds.indexOf(value.toString()), 1);
                        }
                    });
                    updateOfficeId(orderIds);
                }
                if(el.find("input[name='type[]']").val()=='office') {
                    $(ui.draggable).remove();
                }
                reload_priority();
            }
        });
        $(".wrapper").droppable({
            drop:function(event, ui){
                $(".wrapper").sortable({cursor: "move", cursorAt: { top: 30, left: 55 },revert:"valid", revertDuration: 100,
                start: function() {
                    $(".trash_block").addClass("active");
                }, 
                stop: function() {
                    $(".trash_block").removeClass("active");
                    $(".trash_block").removeClass("focuse");
                    var last = 0;
                    $('.plus-item').each(function( index ) {
                        if($(this).find("input[name='type[]']").val()=='') {
                            $(this).remove();
                        }
                    });
                    reload_priority();
                }});
                $(".wrapper").disableSelection();
                var type = $(ui.draggable).find('.order_id').data('type');
                $(".wrapper").addClass("activeted");
                var el = $(this).find(".plus-item.no-active").first();
                if(type=='patient') {
                    if($('.plus-item').is('.patient'+$(ui.draggable).find('.type_id').data('id'))) {
                        if($('.plus-item.patient'+$(ui.draggable).find('.type_id').data('id')).find('select').is('select')) {
                            $('.plus-item.patient'+$(ui.draggable).find('.type_id').data('id')).find('select').append('<option selected value="'+$(ui.draggable).find('.order_id').data('id')+'">'+$(ui.draggable).find('.order_id').data('id')+' ('+$(ui.draggable).find('.text-center.date span').first().clone().children().remove().end().text().replace(/ /g,"")+')</option>');
                            $('.plus-item.patient'+$(ui.draggable).find('.type_id').data('id')).find('.multiple_select').multiselect('rebuild');
                            $('.plus-item.patient'+$(ui.draggable).find('.type_id').data('id')).find('.multiple_select').multiselect('selectAll', false);
                            $('.plus-item.patient'+$(ui.draggable).find('.type_id').data('id')).find('.multiple_select').change();
                        } else {
                            $('.plus-item.patient'+$(ui.draggable).find('.type_id').data('id')).find('.order').html('Order: <select class="multiple_select" multiple="multiple"><option selected value="'+$('.plus-item.patient'+$(ui.draggable).find('.type_id').data('id')).find("input[name='order_id[]']").val()+'">'+$('.plus-item.patient'+$(ui.draggable).find('.type_id').data('id')).find("input[name='order_id[]']").val()+' ('+$('.plus-item.patient'+$(ui.draggable).find('.type_id').data('id')).find("span.badge").first().clone().children().remove().end().text().replace(/ /g,"")+')</option><option selected value="'+$(ui.draggable).find('.order_id').data('id')+'">'+$(ui.draggable).find('.order_id').data('id')+' ('+$(ui.draggable).find('.text-center.date span').first().clone().children().remove().end().text().replace(/ /g,"")+')</option></select>');
                            $('.plus-item.patient'+$(ui.draggable).find('.type_id').data('id')).find('.multiple_select').multiselect({
                                includeSelectAllOption: true
                            });
                            $('.plus-item.patient'+$(ui.draggable).find('.type_id').data('id')).find('.multiple_select').multiselect('selectAll', false);
                            $('.plus-item.patient'+$(ui.draggable).find('.type_id').data('id')).find('.multiple_select').change();
                        }
                    } else {
                        el.find("p").remove();
                        el.removeClass("no-active");
                        el.find('.eta_block').show();
                        el.find("input[name='order_id[]']").val($(ui.draggable).find('.order_id').data('id'));
                        el.find("input[name='type[]']").val(type);
                        el.find("input[name='type_id[]']").val($(ui.draggable).find('.type_id').data('id'));
                        el.addClass("active-blue");
                        el.addClass("patient"+$(ui.draggable).find('.type_id').data('id'));
                        el.append("<p class='order'>Order: "+$(ui.draggable).find('.order_id').data('id')+"</p>");
                        el.append("<p>Customer: "+$(ui.draggable).find('.type_id').data('id')+"</p>");
                        $(ui.draggable).find('.text-center.date span').first().clone().removeAttr('style').appendTo(el);
                        el.attr('title',$(ui.draggable).find('.text-center.date span').first().text());
                    }
                    if(orderIds.indexOf($(ui.draggable).find('.order_id').data('id').toString())==-1) {
                        orderIds.push($(ui.draggable).find('.order_id').data('id').toString());
                    }
                    $(ui.draggable).hide();
                    updateOfficeId(orderIds);
                }
                if(type=='pharmacy') {
                    if($('.plus-item').is('.pharmacy'+$(ui.draggable).find('.type_id').data('id'))) {
                        if($('.plus-item.pharmacy'+$(ui.draggable).find('.type_id').data('id')).find('select').is('select')) {
                            $('.plus-item.pharmacy'+$(ui.draggable).find('.type_id').data('id')).find('select').append('<option selected value="'+$(ui.draggable).find('.order_id').data('id')+'">'+$(ui.draggable).find('.order_id').data('id')+' ('+$(ui.draggable).find('.text-center.date span').first().clone().children().remove().end().text().replace(/ /g,"")+')</option>');
                            $('.plus-item.pharmacy'+$(ui.draggable).find('.type_id').data('id')).find('.multiple_select').multiselect('rebuild');
                            $('.plus-item.pharmacy'+$(ui.draggable).find('.type_id').data('id')).find('.multiple_select').multiselect('selectAll', false);
                            $('.plus-item.pharmacy'+$(ui.draggable).find('.type_id').data('id')).find('.multiple_select').change();
                        } else {
                            $('.plus-item.pharmacy'+$(ui.draggable).find('.type_id').data('id')).find('.order').html('Order: <select class="multiple_select" multiple="multiple"><option selected value="'+$('.plus-item.pharmacy'+$(ui.draggable).find('.type_id').data('id')).find("input[name='order_id[]']").val()+'">'+$('.plus-item.pharmacy'+$(ui.draggable).find('.type_id').data('id')).find("input[name='order_id[]']").val()+' ('+$('.plus-item.patient'+$(ui.draggable).find('.type_id').data('id')).find("span.badge").first().clone().children().remove().end().text().replace(/ /g,"")+')</option><option selected value="'+$(ui.draggable).find('.order_id').data('id')+'">'+$(ui.draggable).find('.order_id').data('id')+' ('+$(ui.draggable).find('.text-center.date span').first().clone().children().remove().end().text().replace(/ /g,"")+')</option></select>');
                            $('.plus-item.pharmacy'+$(ui.draggable).find('.type_id').data('id')).find('.multiple_select').multiselect({
                                includeSelectAllOption: true
                            });
                            $('.plus-item.pharmacy'+$(ui.draggable).find('.type_id').data('id')).find('.multiple_select').multiselect('selectAll', false);
                            $('.plus-item.pharmacy'+$(ui.draggable).find('.type_id').data('id')).find('.multiple_select').change();
                        }
                    } else {
                        el.find("p").remove();
                        el.removeClass("no-active");
                        el.find('.eta_block').show();
                        el.find("input[name='order_id[]']").val($(ui.draggable).find('.order_id').data('id'));
                        el.find("input[name='type[]']").val(type);
                        el.find("input[name='type_id[]']").val($(ui.draggable).find('.type_id').data('id'));
                        el.addClass("active-green");
                        el.addClass("pharmacy"+$(ui.draggable).find('.type_id').data('id'));
                        el.append("<p class='order'>Order: "+$(ui.draggable).find('.order_id').data('id')+"</p>");
                        el.append("<p>"+$(ui.draggable).find('.left .type_id').text()+"</p>");
                    }
                    if(orderIds.indexOf($(ui.draggable).find('.order_id').data('id').toString())==-1) {
                        orderIds.push($(ui.draggable).find('.order_id').data('id').toString());
                    }
                    $(ui.draggable).hide();
                    updateOfficeId(orderIds);
                }
                if(type=='office') {
                    el.find("p").remove();
                    el.removeClass("no-active");
                    el.find('.eta_block').show();
                    el.find("input[name='order_id[]']").val($(ui.draggable).find('.order_id').data('id'));
                    el.find("input[name='type[]']").val(type);
                    el.find("input[name='type_id[]']").val($(ui.draggable).find('.type_id').data('id'));
                    el.addClass("active-yellow");
                    el.addClass("office"+$(ui.draggable).find('.type_id').data('id'));
                    el.append("<p class='order'>Order: <select class='multiple_select' required multiple='multiple'></select></p>");
                    el.append("<p>Office #"+$(ui.draggable).find('.order_id').data('id')+"</p>");
                    $('.plus-item.office'+$(ui.draggable).find('.type_id').data('id')).find('.multiple_select').multiselect({
                        includeSelectAllOption: true
                    });
                    $('.plus-item.office'+$(ui.draggable).find('.type_id').data('id')).find('.multiple_select').multiselect('selectAll', false);
                    updateOfficeId(orderIds);
                }
            }
        });
        reload_priority();
    });
    $(window).resize(function() {
        reload_priority();
    });
    $('#load_map').on('click',function(){
        var s = document.createElement("script");
        s.type = "text/javascript";
        s.src = "https://maps.googleapis.com/maps/api/js?key={{config('app.googlemaps_apikey')}}&region=US&language=en&libraries=drawing,geometry&v=weekly&callback=initMap";
        $('#footerScript').append(s);
        $(this).hide(300);
        setTimeout(() => {
            $('.block_map').show(300);
        }, 200);
    });
</script>
<script>
    var map;
    var _myPolygon;
    var markersArray = [];
    var locationDriver = "{{ $locations->location }}";
    var locationOffice = [@foreach($offices as $office)
    "{{ $office->location }}",
    @endforeach];
    var locationOfficeID = [@foreach($offices as $office)
    "{{ $office->id }}",
    @endforeach];
    var locationPatients = [@foreach($patients_locations as $patients_location)
    @if(!empty($patients_location['id']) && !empty($patients_location['location']))
    @if(!empty($_GET["show_route"]) && $_GET["show_route"]>0)
        @if((!empty($show_ids) && in_array("patient".$patients_location['id'],$show_ids)))
        "{{ $patients_location['location'] }}",
        @endif
    @else
    "{{ $patients_location['location'] }}",
    @endif
    @endif
    @endforeach];
    var locationPharmacy = [@foreach($pharmacy_locations as $pharmacy_location)
    @if(!empty($pharmacy_location['location']))
    @if(!empty($_GET["show_route"]) && $_GET["show_route"]>0)
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
    @if(!empty($_GET["show_route"]) && $_GET["show_route"]>0)
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
    @if(!empty($pharmacy_location['id']))
    @if(!empty($_GET["show_route"]) && $_GET["show_route"]>0)
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
    function addMarker(latlng, text, color, background, id, map, weight="bold") {
        var latLng = new google.maps.LatLng(parseFloat(latlng.split(",")[0]), parseFloat(latlng.split(",")[1]));
        if(markersArray.length != 0) {
            for (i=0; i < markersArray.length; i++) {
                var existingMarker = markersArray[i];
                var pos = existingMarker.getPosition();
                if (latLng.equals(pos)) {
                    var a = 360.0 / markersArray.length;
                    var newLat = pos.lat() + -.00005 * Math.cos((+a*i) / 180 * Math.PI);  //x
                    var newLng = pos.lng() + -.00005 * Math.sin((+a*i) / 180 * Math.PI);  //Y
                    latLng = new google.maps.LatLng(newLat,newLng);
                }
            }
        }
        const marker = new google.maps.Marker({
            position: latLng,
            icon: {
                url: '/images/i033.svg', // url
                scaledSize: new google.maps.Size(35, 35), // scaled size
                origin: new google.maps.Point(0,0), // origin
                anchor: new google.maps.Point(30, 30), // anchor
                labelOrigin: new google.maps.Point(18, 31)
            },
            label: {
                text:text,
                fontWeight: weight,
                fontSize: '8px',
                color: color
            },
            title: id,
            id: id,
            map: map,
        });
        markersArray.push(marker);
        attachSecretMessage(marker);
    }
    function addMarkerDriver(latlng, map) {
        const marker = new google.maps.Marker({
            position: { lat: parseFloat(latlng.split(",")[0]), lng: parseFloat(latlng.split(",")[1]) },
            icon: {
                @if($driver->transport=='1')
                url: '/images/i022.svg', // url
                @elseif($driver->transport=='2')
                url: '/images/bicycle-map.svg', // url
                @endif
                scaledSize: new google.maps.Size(46, 46), // scaled size
                origin: new google.maps.Point(0,0), // origin
                anchor: new google.maps.Point(23, 23) // anchor
            },
            map: map,
        });
        attachSecretMessage(marker);
    }
    function addMarkerOffice(latlng, text, color, background, id, map, weight="bold") {
        const marker = new google.maps.Marker({
            position: { lat: parseFloat(latlng.split(",")[0]), lng: parseFloat(latlng.split(",")[1]) },
            icon: {
                url: '/images/i044.svg', // url
                scaledSize: new google.maps.Size(30, 30), // scaled size
                origin: new google.maps.Point(0,0), // origin
                anchor: new google.maps.Point(30, 30) // anchor
            },
            title: id,
            id: id,
            map: map,
        });
        attachSecretMessage(marker);
    }
    function addMarkerPharmacy(latlng, text, color, background, id, map, weight="bold") {
        const marker = new google.maps.Marker({
            position: { lat: parseFloat(latlng.split(",")[0]), lng: parseFloat(latlng.split(",")[1]) },
            icon: {
                url: '/images/i011.svg', // url
                scaledSize: new google.maps.Size(35, 35), // scaled size
                origin: new google.maps.Point(0,0), // origin
                anchor: new google.maps.Point(20, 20), // anchor
                labelOrigin: new google.maps.Point(18, 31)
            },
            label: {
                text:text,
                fontWeight: weight,
                fontSize: '8px',
                color: color
            },
            title: id,
            id: id,
            map: map,
        });
        markersArray.push(marker);
        attachSecretMessage(marker);
    }
    function initMap() {
        map = new google.maps.Map(document.getElementById("map"), {
            zoom: 11,
            center: { lat: parseFloat(locationDriver.split(",")[0]), lng: parseFloat(locationDriver.split(",")[1]) },
            legend: 'none'
        });

        const drawingManager = new google.maps.drawing.DrawingManager({
            drawingControl: true,
            drawingControlOptions: {
                position: google.maps.ControlPosition.TOP_CENTER,
                drawingModes: [google.maps.drawing.OverlayType.POLYGON],
            },                               
        });
        drawingManager.setMap(map);
        google.maps.event.addListener(drawingManager, 'overlaycomplete', function(e) {
            _myPolygon = e.overlay;
        });
        google.maps.event.addListener(drawingManager,'polygoncomplete',function(polygon) {
            for(n=0;n<locationPatients.length;n++) {
                if(google.maps.geometry.poly.containsLocation(new google.maps.LatLng(parseFloat(locationPatients[n].split(',')[0]), parseFloat(locationPatients[n].split(',')[1])),polygon) && showPatient) {
                    $("#region-form").append('<input class="order_ids" type="hidden" name="patients_id[]" value="'+locationPatientsID[n]+'">');
                }
            }
            for(n1=0;n1<locationPharmacy.length;n1++) {
                if(google.maps.geometry.poly.containsLocation(new google.maps.LatLng(parseFloat(locationPharmacy[n1].split(',')[0]), parseFloat(locationPharmacy[n1].split(',')[1])),polygon) && showPharmacy) {
                    $("#region-form").append('<input class="order_ids" type="hidden" name="pharmacys_id[]" value="'+locationPharmacyID[n1]+'">');
                }
            }
            for(n2=0;n2<locationOffice.length;n2++) {
                if(google.maps.geometry.poly.containsLocation(new google.maps.LatLng(parseFloat(locationOffice[n2].split(',')[0]), parseFloat(locationOffice[n2].split(',')[1])),polygon)) {
                    $("#region-form").append('<input class="order_ids" type="hidden" name="office_id[]" value="'+locationOfficeID[n2]+'">');
                }
            }
            if(n>=locationPatients.length && n1>=locationPharmacy.length && n2>=locationOffice.length) {
                $('.modal1').fadeIn(300);
            }
        });
        addMarkerDriver(locationDriver,map);
        for(n=0;n<locationOffice.length;n++) {
            addMarkerOffice(locationOffice[n],locationOfficeID[n].toString(),"#000","#eca40d","office"+locationOfficeID[n].toString(),map);
        }
        if(showPatient) {
            for(n=0;n<locationPatients.length;n++) {
                if(locationPatientsID[n].toString().search(";")>-1) {
                    var id2 = locationPatientsID[n].toString().split(";")[0];
                    var prior2 = locationPatientsID[n].toString().split(";")[1];
                    addMarker(locationPatients[n],prior2,"#000","#0e46b7","patient"+id2,map,"bold");
                } else {
                    var id2 = locationPatientsID[n].toString().split(";")[0];
                    addMarker(locationPatients[n],id2,"#000","#0e46b7","patient"+id2,map,"bold");
                }
            }
        }
        if(showPharmacy) {
            for(n=0;n<locationPharmacy.length;n++) {
                if(locationPharmacyID[n].toString().search(";")>-1) {
                    var id2 = locationPharmacyID[n].toString().split(";")[0];
                    var prior2 = locationPharmacyID[n].toString().split(";")[1];
                    addMarkerPharmacy(locationPharmacy[n],prior2,"#fff","#19b313","pharmacy"+id2,map,"bold");
                } else {
                    var id2 = locationPharmacyID[n].toString().split(";")[0];
                    addMarkerPharmacy(locationPharmacy[n],id2,"#fff","#19b313","pharmacy"+id2,map,"bold");
                }
            }
        }
    }
    function attachSecretMessage(marker) {
        marker.addListener("click", () => {
            var id = marker.get('id');
            if(id!="") {
                if(setup_route==true) {
                    $('.modal2').find('input[name="id"]').val(id);
                    if($('.wrapper .plus-item').index($('.wrapper').find("."+id))<0) {
                        $('.modal2').find('input[name="priority"]').val(1);
                    } else {
                        $('.modal2').find('input[name="priority"]').val($('.wrapper .plus-item').index($('.wrapper').find("."+id))+1);
                    }
                    setup_marker = marker;
                    $('.modal2').fadeIn(300);
                } else {
                    $(".office_block").removeClass("focus");
                    $(".pharmacie_block").removeClass("focus");
                    $(".patient_block").removeClass("focus");
                    $(".plus-item").removeClass("focus");
                    $("."+id).addClass("focus");
                    if(id.indexOf('patient')>-1) {
                        if($('.wrapper').find("."+id).length) {
                            $('.wrapper').animate({ scrollTop: $('.wrapper').find("."+id).offset().top-580 }, 500);
                        } else {
                            $('#patients_list').animate({ scrollTop: $("."+id).offset().top-320 }, 500);
                        }
                    }
                    if(id.indexOf('pharmacy')>-1) {
                        if($('.wrapper').find("."+id).length) {
                            $('.wrapper').animate({ scrollTop: $('.wrapper').find("."+id).offset().top-580 }, 500);
                        } else {
                            $('#pharmacies_list').animate({ scrollTop: $("."+id).offset().top-320 }, 500);
                        }
                    }
                    $('html, body').animate({ scrollTop: 0 }, 400);
                }
            }
        });
    }
</script>           
@endsection
