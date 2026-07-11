@extends('layouts.master')

@section('title') Driver Packages @endsection
<style>
.wrapper {
    display: flex;
    flex-wrap: wrap;
    max-height: 185px;overflow: auto;
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
    display: none;
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
#page-topbar {
    z-index: 1000 !important;
}
.plus-item {
    cursor:move;
    padding: 2px 0px;
    height:50px;
    width:105px;
    border: 5px solid #28c8e2;
    border-radius: 12px;
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
.plus-item .number {
    color: #28c8e2;
    font-size: 37px;
    margin: 0;
    text-align: center;
}
.plus-item p {
    line-height: 1;
    margin-bottom: 6px;
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
    border: 5px solid #0e46b7;
    background: #0e46b7;
    opacity: 1;
    padding: 0;
    padding-top: 3px;
    color:#fff;
    text-align: center;
}
.plus-item.active-yellow {
    border: 5px solid #eca40d;
    background: #eca40d;
    opacity: 1;
    padding-top: 14px;
    color:#fff;
    text-align: center;
}
.patient_block {
    z-index: 10000;
    cursor:move;
    width:100%;
    height:50px;
    border-radius: 6px;
    background-color: #c0ecff;
    color: #0b4f8a;
    padding: 5px 15px;
    font-size: 14px;
    border: 1px solid #73dff5;
    margin-bottom:15px;
    -webkit-touch-callout: none; /* iOS Safari */
    -webkit-user-select: none;   /* Chrome/Safari/Opera */
    -khtml-user-select: none;    /* Konqueror */
    -moz-user-select: none;      /* Firefox */
    -ms-user-select: none;       /* Internet Explorer/Edge */
    user-select: none;           /* Non-prefixed version, currently*/
}
.patient_block.hover{
    padding: 0px 15px;
    border: 5px solid #73dff5;
    box-shadow: 0 2px 8px rgba(0, 0, 0, .3), -23px 0 22px -23px rgba(0, 0, 0, .8), 25px 0 23px -23px rgba(0, 0, 0, .8), 0 0 40px rgba(0, 0, 0, .1) inset;
}
.patient_block .left {
    float: left;
}
.patient_block .right {
    padding-top:5px;
    float: right;
}
.pharmacie_block {
    z-index: 10000;
    width:100%;
    border-radius: 6px;
    background-color: #c8fbcd;
    color: #006311;
    padding: 5px 15px;
    font-size: 14px;
    border: 1px solid #8bf9a6;
    margin-bottom:15px;
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
}
.pharmacie_block .right {
    padding-top:5px;
    float: right;
}
.pharmacie_block p {
    margin-bottom:0px;
}
.card_height {
    height: 250px;
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
    position:absolute;
    right: 0px;
    bottom: 0px;
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

.scan-preload0 {
    text-align: center;
    background-image: url(https://cdn.dribbble.com/users/1187836/screenshots/6012802/13-qrcode.gif);
    background-position: center 65%;
    background-size: auto;
    width: 100%;
    height: 182px;
}

.scan-preload0 h4{
    bottom: 10px;
    position: absolute;
    width: 620px;
    left: 50%;
    margin-left: -310px;
}
#qr-code0 {
    width: 0px;
    opacity:0;
}
</style>
@section('content')
 <!-- start page title -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body" style="display: flex;min-height:316px;">
                                    <h3 style="position: absolute;">Driver Info</h3>
                                    <div class="col-4" style="margin-top: 35px;">
                                        <img class="rounded-circle" style="width: 160px;height: 160px;" src="{{$driver->image}}" alt="Header Avatar">
                                    </div>
                                    <div class="col-8" style="margin-top: 35px;">
                                        <input type="hidden" id="driver_id" value="{{$driver->id}}">
                                        <h4 style="display: inline-block;margin-right:15px;">Driver ID#{{$driver->id}}</h4>
                                        @if($driver->isblocked == 1)
                                            <button class="btn btn-danger" style="line-height: 0.9;vertical-align:top;">Blocked</button>
                                        @elseif($driver->isactive == 0)
                                            <button class="btn btn-warning" style="line-height: 0.9;vertical-align:top;">No active</button>
                                        @else 
                                            <button class="btn btn-success" style="line-height: 0.9;vertical-align:top;">Active</button>
                                        @endif
                                        <p>{{$driver->name}} {{$driver->last_name}}    {{$driver->phone}}    {{$driver->email}}</p>
                                        <hr>
                                        @if($need_cash>0)
                                        <p><h3>Got cash: $ {{round($need_cash,2)}}</h3></p>
                                        <form method="POST">
                                            @csrf
                                            <input type="hidden" name="confirm_receipt" value="1">
                                            <button class="btn btn-primary" type="submit">Confirm Receipt</button>
                                        </form>
                                        @endif
                                        @if(count($orders_transfer) && (Auth::user()->is_massiveBagsTransfer() || Auth::user()->role=="admin"))
                                        <form method="POST">
                                            @csrf
                                            <input type="hidden" name="massiveBagsTransfer" value="1">
                                            @php
                                            $sum_bags=0;
                                            foreach($orders_transfer as $order){
                                                $sum_bags=$sum_bags+$order->count_bags;
                                            }
                                            @endphp
                                            <button type="submit" class="btn btn-info waves-effect waves-light">I give all packages ({{$sum_bags}} Bags) <i class="ti-export"></i></button>
                                        </form>
                                        @endif
                                        <div class="office_block">
                                            <button class="btn btn-danger" onclick="window.close();">Close tab</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <h5>Required Orders Transfer (<b class="count_orders_out">{{count($orders_transfer)}}</b>)</h5>
                                    <div id="pharmacies_list" class="card_height">
                                    @foreach($orders_transfer as $order)
                                        <div class="pharmacie_block pharmacie_block_out" data-id="{{$order->id}}">
                                            <div>
                                                <b class="order_id">Order #{{$order->id}}</b>
                                                <p>Need Fridge: @if($order->fridge)<b style="color:green;">{{'Yes'}}</b>@else<b style="color:red;">{{'No'}}</b>@endif</p>
                                                <p>Special Instructions: {{$order->special_instructions}}</p>
                                                <p>Count bags: <span class="count_bags_transfer">{{$order->count_bags_transfer}}</span>/{{$order->count_bags}}</p>
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
                                    <h5>Required Orders Pick Up (<b class="count_orders_in">{{count($orders_pick_up)}}</b>)</h5>
                                    <div id="pharmacies_list" class="card_height">
                                    @foreach($orders_pick_up as $order)
                                        <div class="pharmacie_block pharmacie_block_in" data-id="{{$order->id}}">
                                            <div>
                                                <b class="order_id">Order #{{$order->id}}</b>
                                                <p>Need Fridge: @if($order->fridge)<b style="color:green;">{{'Yes'}}</b>@else<b style="color:red;">{{'No'}}</b>@endif</p>
                                                <p>Special Instructions: {{$order->special_instructions}}</p>
                                                <p>Count bags: <span class="count_bags_transfer">{{$order->count_bags_transfer}}</span>/{{$order->count_bags}}</p>
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
                                    <div class="scan-preload0">
                                        @csrf
                                        <input type="text" id="qr-code0">
                                        <h4>Scan the QR code on the order bag.</h4>
                                    </div>
                                </div> 
                            </div> 
                        </div> 
                    </div>
@endsection
@section('footerScript')
    <script>
        $(document).ready(function() {
            setInterval(() => $('#qr-code0').focus(), 1000);
        });
        $(document).on('click','.close',function() {
            $('.modal').fadeOut(300);
        });
        $(document).on('click','.close0',function() {
            $('.modal').fadeOut(300);
        });
        $(document).on('keyup','#qr-code0',function(event) {
            if(event.code=='Enter') {
                var qr = $('#qr-code0').val();
                if(qr!='') {
                    var code = qr;
                    var count = 1;
                    var driver_id = $('#driver_id').val();
                    if(code!='' && count!='') {
                        $.post('/drivers/qr_order', {code: code, count: count, driver_id: driver_id, _token: $("input[name='_token']").val()}, null, 'json')
                        .done(function(response) {
                            $('#qr-code0').val("");
                            if(response.order_id>0) {
                                $('#code').val('');
                                $('.pharmacie_block[data-id="'+response.order_id+'"]').remove();
                                $('.count_orders_out').text($('.pharmacie_block_out').length);
                                toastr["success"]('Successfully. You can transfer this bag to the driver. All bags for this order was scaned');
                                speak('Successfully. You can transfer this bag to the driver. All bags for this order was scaned');
                            } else if (response.bag_added>0) {
                                $('#code').val('');
                                $('.pharmacie_block[data-id="'+response.bag_added+'"]').find('.count_bags_transfer').text(parseInt($('.pharmacie_block[data-id="'+response.bag_added+'"]').find('.count_bags_transfer').text())+1);
                                toastr["success"]('Successfully. You can transfer this bag to the driver');
                                speak('Successfully. You can transfer this bag to the driver');
                            } else if (response.order_id_in>0) {
                                $('.pharmacie_block[data-id="'+response.order_id_in+'"]').remove();
                                $('.count_orders_in').text($('.pharmacie_block_in').length);
                                toastr["success"]('Successfully. You can transfer this bag to the storage. All bags for this order was scaned');
                                speak('Successfully. You can transfer this bag to the storage. All bags for this order was scaned');
                            } else if (response.bag_added_in>0) {
                                $('#code').val('');
                                $('.pharmacie_block[data-id="'+response.bag_added_in+'"]').find('.count_bags_transfer').text(parseInt($('.pharmacie_block[data-id="'+response.bag_added_in+'"]').find('.count_bags_transfer').text())+1);
                                toastr["success"]('Successfully. You can transfer this bag to the storage');
                                speak('Successfully. You can transfer this bag to the storage');
                            } else {
                                $('#qr-code0').focus();
                                toastr["error"](response.message);
                                speak(response.message);
                            }
                        });
                    } else {
                        toastr["error"]('Error in filling all fields');
                    }
                }
            }
        });
        $(document).on('click','#send-popup',function() {
            var code = $('#code').val();
            var count = $('#count').val();
            var driver_id = $('#driver_id').val();
            if(code!='' && count!='') {
                $.post('/drivers/qr_order', {code: code, count: count, driver_id: driver_id, _token: $("input[name='_token']").val()}, null, 'json')
                .done(function(response) {
                    $('#qr-code0').val("");
                    if(response.order_id>0) {
                        $('#code').val('');
                        $('#count').val('1');
                        $('.modal').fadeOut(300);
                        $('.pharmacie_block[data-id="'+response.order_id+'"]').remove();
                        $('.count_orders').text($('.pharmacie_block').length);
                        toastr["success"]('Successfully. You can transfer bags for this order to the driver');
                        speak('Successfully. You can transfer bags for this order to the driver');
                    } else {
                        $('#qr-code0').focus();
                        $('#count').val('1');
                        $('.modal').fadeOut(300);
                        toastr["error"](response.message);
                        speak(response.message);
                    }
                });
            } else {
                toastr["error"]('Error in filling all fields');
            }
        });
    </script>
@endsection