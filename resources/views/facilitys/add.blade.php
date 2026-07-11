@extends('layouts.master')

@section('title') Add Order @endsection

@section('headerCss')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="/css/ion.rangeSlider.min.css" rel="stylesheet" />
<style>
.select2-container--default .select2-selection--multiple .select2-selection__choice__display {
    padding-left: 15px;
    padding-right: 0px;
}
.select2-container .select2-selection--multiple .select2-search__field {
    color: rgb(51, 51, 51);
    font-family: inherit;
    font-size: inherit;
    margin-top: 9px;
    margin-left: 0px;
}
.select2-container .select2-selection--multiple .select2-selection__rendered {
    padding: 2px 6px !important;
}
[type="date"]::-webkit-calendar-picker-indicator {
  color: transparent;
  opacity: 1;
  background: url(https://cdn1.iconfinder.com/data/icons/cc_mono_icon_set/blacks/16x16/calendar_2.png) no-repeat center;
  background-size: contain;
  filter: opacity(0.5) drop-shadow(0 0 0 blue);
}
#add_rx {
    display: flex;
    cursor:pointer;
}
.rx-field {
    margin-bottom:10px;
}
.remove-rx {
    color: #ff5200;
    font-size: 13px;
    cursor: pointer;
}
.rx-items {
  margin: 0;
  padding: 0;
}
.rx-items .form-control {
  font-size: 10px;
  padding-right: 5px;
}
.rx-items .rx-field {
  padding: 2px;
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
.form-check {
    cursor:pointer;
    display: block;
    min-height: 1.21875rem;
    padding-left: 1.5em;
    margin-bottom: 0;
}
.form-switch-md {
    padding-left: 2.5rem;
    min-height: 24px;
    line-height: 24px;
}
.form-check-input {
    width: 1em;
    height: 1em;
    margin-top: 0.25em;
    vertical-align: top;
    background-color: #fff;
    background-repeat: no-repeat;
    background-position: center;
    background-size: contain;
    border: 1px solid #adb5bd;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    -webkit-print-color-adjust: exact;
    color-adjust: exact;
    -webkit-transition: background-color .15s ease-in-out,background-position .15s ease-in-out,border-color .15s ease-in-out,-webkit-box-shadow .15s ease-in-out;
    transition: background-color .15s ease-in-out,background-position .15s ease-in-out,border-color .15s ease-in-out,-webkit-box-shadow .15s ease-in-out;
    transition: background-color .15s ease-in-out,background-position .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out;
    transition: background-color .15s ease-in-out,background-position .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out,-webkit-box-shadow .15s ease-in-out;
}
.form-check-input:checked[type=checkbox] {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='M6 10l3 3l6-6'/%3e%3c/svg%3e");
}
.form-switch .form-check-input:checked {
    background-position: right center;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='%23fff'/%3e%3c/svg%3e");
}
.form-check-input:checked {
    background-color: #7a6fbe;
    border-color: #7a6fbe;
}
.form-switch .form-check-input {
    width: 2em;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='rgba%280, 0, 0, 0.25%29'/%3e%3c/svg%3e");
    background-position: left center;
    border-radius: 2em;
    -webkit-transition: background-position .15s ease-in-out;
    transition: background-position .15s ease-in-out;
}
.form-switch-md .form-check-input {
    width: 40px;
    height: 20px;
    position: relative;
}
.irs-hidden-input {
    position: absolute !important;
    display: block !important;
    top: 0 !important;
    left: 0 !important;
    width: 0 !important;
    height: 0 !important;
    font-size: 0 !important;
    line-height: 0 !important;
    padding: 0 !important;
    margin: 0 !important;
    overflow: hidden;
    outline: none !important;
    z-index: -9999 !important;
    background: none !important;
    border-style: solid !important;
    border-color: transparent !important;
}
.irs--square .irs-from, .irs--square .irs-to, .irs--square .irs-single {
    font-size: 12px;
    background-color: #7a6fbe;
}
.irs--square .irs-bar {
    top: 29px;
    height: 8px;
    background-color: #7a6fbe;
}
.irs--square .irs-handle {
    border: 3px solid #7a6fbe;
    border-radius: 50%;
}
</style>
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
									<div class="row">
                                        <div class="col-12">
                                            <h5 style="background: #7a6fbe;color: #ffffff;padding: 5px;text-align: center;">New Order</h5> 
                                        </div>
                                    </div>									
                                    <form method="post" enctype="multipart/form-data" id="form">
                                        @csrf
                                        <input type="hidden" name="save" value="1">
                                        @if($alert!='') 
                                            <div class="alert alert-danger" role="alert">{{ $alert }}</div>
                                        @endif
                                        <div class="row">
                                            <div class="col-5">
                                                    <h5 style="background: #2b3a4a;color: #ffffff;padding: 5px;text-align: center;margin-bottom: 24px;"><i class="mdi mdi-information-outline"></i> Details</h5>
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <div id="facilitys-b">
                                                                <select id="facilitys" placeholder="Facility..." name="facility" required>
                                                                    <option value="">Facility...</option>
                                                                    @foreach($facilitys as $user)
                                                                        @if(isset($_GET["facility"]) && $_GET["facility"]==$user->id)
                                                                        <option value="{{ $user->id }}" selected>{{ $user->name }} {{ $user->last_name }} - {{ $user->phone }}</option>
                                                                        @else
                                                                        <option value="{{ $user->id }}">{{ $user->name }} {{ $user->last_name }} - {{ $user->phone }}</option>
                                                                        @endif
                                                                    @endforeach
                                                                </select>
                                                            </div>                                                                                         
                                                        </div>
                                                        
                                                        <div class="col-sm-4" style="margin-bottom: 20px;">
                                                                <small>Count bags</small>
                                                                <input type="number" min="1" max="10" name="count_bags" class="form-control" value="1">
                                                        </div>
                                                        <div class="col-sm-3" style="margin-bottom: 20px;">
                                                            <small>Total Co-pay ($)</small>
                                                            <input type="number" min="0" step="0.01" class="form-control" name="copay" placeholder="Co-pay">
                                                            <div style="padding: 5px 20px;" class="paidph"> 
                                                                <input class="form-check-input" type="checkbox" id="formCheck2" value="1" name="copay_paid_pharm">
                                                                <label class="form-check-label" for="formCheck2">Paid at the pharmacy</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-5 mt-4" style="margin-bottom: 20px;"> 
                                                            <div style="float: right;">
                                                                <div class="form-check form-switch form-switch-md" dir="ltr">  
                                                                    <label class="form-check-label" style="float: left;margin: 0 5px;">Fridge</label>
                                                                    <input type="checkbox" id="switch6" switch="primary" value="1" name="fridge" id="Fridge" />
                                                                    <label for="switch6" data-on-label="Yes" data-off-label="No"></label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-6">
                                                            <small>Delivery options</small>
                                                            <select name="delivery_method" placeholder="Delivery options" class="form-control" required>
                                                                <option value="">Delivery options...</option>
                                                                @foreach($delivery_methods as $delivery_method)
                                                                    <option value="{{ $delivery_method->id }}">{{ $delivery_method->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-sm-6">
                                                            <small>Preferred delivery time</small>
                                                            <select name="delivery_time" placeholder="Preferred delivery time" class="form-control" required>
                                                                <option value="">Preferred delivery time...</option>
                                                                @foreach($delivery_times as $delivery_time)
                                                                    <option value="{{ $delivery_time->id }}">{{ $delivery_time->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-sm-6 mt-4">
                                                            <small>Delivery Time Frame</small>                                                
                                                        </div>
                                                        <div class="col-6 mt-4">                                         
                                                            <div style="float: right;">
                                                                <div class="form-check form-switch form-switch-md" dir="ltr">                                                       
                                                                    <label class="form-check-label" for="swith_time_id" style="float: left;margin: 0 5px;">Any time delivery</label>
                                                                    <input type="checkbox" id="swith_time_id" switch="primary" value="1" name="swith_time_id" id="swith_time_id" checked />
                                                                    <label for="swith_time_id" data-on-label="Yes" data-off-label="No"></label>
                                                                </div>                                               
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-12 mt-4 px-4">                                                
                                                            <input type="text" style="display:none;" name="delivery_time_range" id="range_10">
                                                        </div>  
                                                        
                                                        <div class="col-sm-12 mt-5" >
                                                            <h5 style="background: #2b3a4a;color: #ffffff;padding: 5px;text-align: center;margin-bottom: 24px;"><i class="mdi mdi-alert-outline"></i> Special instructions</h5>
                                                            <textarea class="form-control" name="special_instructions" rows="3"></textarea>
                                                        </div>                                        
                                                </div>
                                            </div>
                                            <div class="col-7">
                                                <h5 style="background: #2b3a4a;color: #ffffff;padding: 5px;text-align: center;margin-bottom: 24px;"><i class="mdi mdi-package-variant-closed"></i> Recipients</h5>
                                                <div class="row">  
                                                    <div class="col-12">
                                                        <select name="rx_recipients[]" class="form-control rx_recipient float-left" style="width:290px;">
                                                            <option value="">Add Facility Patient...</option>
                                                        </select>
                                                        <label for="file-upload" class="btn btn-outline-secondary waves-effect float-right">
                                                            <i class="mdi mdi-cloud-upload"></i> Upload Delivery Slip
                                                        </label>
                                                        <input id="file-upload" type="file" multiple name="import[]" accept="application/pdf" onchange="$('#form').find('[type=&quot;submit&quot;]').trigger('click');" style="display:none;">
                                                    </div>
                                                    <div class="col-12 mt-3 recipient_blocks">
                                                        
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                      
                                        <div class="mt-5 text-center py-2">  <button type="submit" class="btn btn-outline-dark waves-effect waves-light" style="min-width: 350px;">Submit <i class="fas fa-arrow-alt-circle-right"></i></button></div> 
                                        
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- end col -->
                    </div>
                    <!-- end row -->
                    <div class="hidden" style="display:none;">
                        <div class="card recipient_block">
                            <p class="card-header">Recipient #<span class="number">1</span> - <span class="name">Last Name, First name (000) 000-0001</span> <span class="badge rounded-pill bg-dark text-light recipient_delete" style="float: right;cursor:pointer;">Delete</span></p>
                            <div class="card-body">   
                                <div class="col-sm-12 rx-list">
                                    <div class="rx-row col-sm-12 row rx-items">
                                        <div class="col-sm-3 rx-field">
                                            <small>RX#</small>
                                            <input type="text" maxlength="256" placeholder="" name="rx_id[]" class="form-control">
                                        </div>
                                        <div class="col-sm-2 rx-field">
                                            <small>Rf#</small>
                                            <input type="text" maxlength="20" placeholder="" name="rf_id[]" class="form-control">
                                        </div>
                                        <div class="col-sm-2 rx-field">
                                            <small>Qty</small>
                                            <input type="number" value="1" min="1" name="rx_count[]" class="form-control">
                                        </div>
                                        <div class="col-sm-2 rx-field">
                                            <small>Co-pay</small>
                                            <input type="number" min="0" step="0.01" placeholder="" name="rx_copay[]" class="form-control">
                                        </div>
                                        <input type="hidden" name="rx_recipient[]" value="">
                                        <div class="col-sm-3 rx-field">
                                            <small>Date</small>
                                            <input type="date" placeholder="Date" name="rx_date[]" class="form-control">
                                        </div>                                                                                                                                                           
                                    </div>
                                </div>                                            
                                <div class="col-sm-12"  style="margin: 24px 0">
                                    <button type="button" data-id="" class="btn btn-secondary btn-sm waves-effect add_rx2">Add RX <i class="fas fa-plus-circle" style="font-size:12px;"></i> </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
@endsection

@section('footerScript')
<script src="{{ URL::asset('/js/select2.min.js')}}"></script>
<script src="{{ URL::asset('/libs/rwd-table/rwd-table.min.js')}}"></script>
<script src="{{ URL::asset('/js/ion.rangeSlider.min.js')}}"></script>
<script type='text/javascript'>
    var rx_recipient = '<option value="">Add Facility Patient...</option>';
    $("#range_10").ionRangeSlider({
        type:"double",
        skin:"square",
        grid:!0,
        drag_interval:true,
        min_interval:3,
        max_interval:3,
        disable: true,
        from:0,
        to:3,
        values:[@foreach($time_ranges as $time) "{{$time}}", @endforeach]
    });
    $("#swith_time_id").on("change",function(){
        let my_range = $("#range_10").data("ionRangeSlider");
        if(this.checked) {
            my_range.update({
                disable: true
            });
        } else {
            my_range.update({
                disable: false,
                from:0,
                to:3
            });
        }
    });
    var tariff_pharmacy = parseFloat("{{$pharmacy->tariff}}");
    var tariff_next_day = parseFloat("{{$pharmacy->tariff_next_day}}");
    var tariff_same_day = parseFloat("{{$pharmacy->tariff_same_day}}");
    var tariff_asap = parseFloat("{{$pharmacy->tariff_asap}}");
    var tariff_after_hours = parseFloat("{{$pharmacy->tariff_after_hours}}");
    var tariff_patient = parseFloat("");
    var count_bags = 1;
    var type_driver = 1;
    var tariff_time;
    $('select[name="user"]').on('change',function(){
        $.post(location.href, { _token: $('input[name="_token"]').val(), user_id: this.value }).done(function( data ) {
            data=JSON.parse(data);
            if(data.message=="OK") {
                tariff_patient=parseFloat(data.tariff);
                calc_tariff();
            } else {
                console.log("Error");
            }
        });
    });
    $('select[name="delivery_time"]').on('change',function(){
        calc_tariff();
    });
    $('input[name="count_bags"]').on('change',function(){
        calc_tariff();
    });
    function calc_tariff() {
        tariff_time = $('select[name="delivery_time"] option:selected').val();
        count_bags = parseFloat($('input[name="count_bags"]').val());
        type_driver = $('input[name="type_driver"]:checked').val()
        if(type_driver=="1") {
            if(tariff_patient>0){
                $(".extra_charge").hide();
                if(tariff_time=="1"){
                    $("#tariff b").text(((tariff_patient+tariff_next_day)*1).toFixed(2));
                } else if (tariff_time=="2") {
                    $("#tariff b").text(((tariff_patient+tariff_same_day)*1).toFixed(2));
                } else if (tariff_time=="3") {
                    $("#tariff b").text(((tariff_patient+tariff_asap)*1).toFixed(2));
                } else if (tariff_time=="4") {
                    $("#tariff b").text(((tariff_patient+tariff_after_hours)*1).toFixed(2));
                } else {
                    $("#tariff b").text(((tariff_patient+tariff_next_day)*1).toFixed(2));
                }
            } else {
                if(tariff_time=="1"){
                    $(".extra_charge").hide();
                    $("#tariff b").text(((tariff_pharmacy+tariff_next_day)*1).toFixed(2));
                } else if (tariff_time=="2") {
                    $(".extra_charge").show();
                    $("#tariff b").text(((tariff_pharmacy+tariff_same_day)*1).toFixed(2));
                } else if (tariff_time=="3") {
                    $(".extra_charge").show();
                    $("#tariff b").text(((tariff_pharmacy+tariff_asap)*1).toFixed(2));
                } else if (tariff_time=="4") {
                    $(".extra_charge").show();
                    $("#tariff b").text(((tariff_pharmacy+tariff_after_hours)*1).toFixed(2));
                } else {
                    $("#tariff b").text(((tariff_pharmacy+tariff_next_day)*1).toFixed(2));
                }
            }
        } else {
            $("#tariff b").text(((tariff_pharmacy)*1).toFixed(2));
        }
    }
$('.js-example-basic-multiple').select2({
  placeholder: 'Enter RX ID',
  tags: true
});
$('body').on('click','.recipient_delete',function(){
    if(confirm('Are you sure?')) {
        $(this).parent().parent().remove();
    }
});
$(document).on('keypress', '.select2-search__field', function () {
    $(this).val($(this).val().replace(/[^\d].+/, ""));
    if ((event.which < 48 || event.which > 57)) {
      event.preventDefault();
    }
});
$('body').on('click','.add_rx2',function() {
    let id = $(this).data('id');
    $(this).parent().parent().find('.rx-list').append('<div class="rx-row col-sm-12 row rx-items"><div class="col-sm-3 rx-field"><small>RX#</small><input type="text" maxlength="256" placeholder="" name="rx_id[]" class="form-control"></div><div class="col-sm-2 rx-field"><small>Rf#</small><input type="text" maxlength="20" placeholder="" name="rf_id[]" class="form-control"></div><div class="col-sm-2 rx-field"><small>Qty</small><input type="number" value="1" min="1" name="rx_count[]" class="form-control"></div><div class="col-sm-2 rx-field"><small>Co-pay</small><input type="number" min="0" step="0.01" placeholder="" name="rx_copay[]" class="form-control"></div><div class="col-sm-3 rx-field"><small>Date</small><input type="date" placeholder="Date" name="rx_date[]" class="form-control"></div><input type="hidden" name="rx_recipient[]" value="'+id+'"><div class="rx-field"  style="position: absolute;right: 3px;top: 0px;"><i class="fas  fa-eye-slash remove-rx"></i></div></div>');
});
$('body').on('click','.remove-rx', function() {
    $(this).parent().parent('.rx-row').remove();
});
$('input[name="type_driver"]').on('change',function(){
    calc_tariff();
    if($(this).val()==2) {
        $(".drivers-list").show(100);
    } else {
        $('select[name="driver"]').selectize()[0].selectize.clear()
        $(".drivers-list").hide(100);
    }
});
$('#select-state').on("change",function(){
    var id = $(this).val();
    $.get("/patients/"+id+"/family", function( data ) {
        $("#family_id").html(data);
    });
});
$('.rx_recipient').on("change",function(){
    var id = $(this).val();
    if(id>0 && $('.recipient_blocks').find('.add_rx2[data-id="'+id+'"]').length<1){
        var el = $('.hidden').find('.recipient_block').first().clone();
        el.find('.name').text($(".rx_recipient option:selected").text());
        el.find('.number').text($('.recipient_blocks .recipient_block').length+1);
        el.find('[name="rx_recipient[]"]').val(id);
        el.find('.add_rx2').attr('data-id',id);
        $('.recipient_blocks').append(el);
    }
    $('.rx_recipient')[0].selectize.clear();
})
$('#facilitys').on("change",function(){
    var id = $(this).val();
    $.get("/patients/"+id+"/additional_recipients", function( data ) {
        rx_recipient=data;
        $(".rx_recipient").html(rx_recipient);
        $('.rx_recipient').selectize({
            sortField: 'text'
        });
    });
});
$('#facilitys').selectize({
    sortField: 'text'
});
$( document ).ready(function() {
    if($('#select-state').val()!='') {
        var id = $('#select-state').val();
        $.get("/patients/"+id+"/family", function( data ) {
            $("#family_id").html(data);
        });
    }
    if($('#facilitys').val()!='') {
        var id = $('#facilitys').val();
        $.get("/patients/"+id+"/additional_recipients", function( data ) {
            rx_recipient=data;
            $(".rx_recipient").html(rx_recipient);
            $('.rx_recipient').selectize({
                sortField: 'text'
            });
        });
    }
});
$("#swith_family_id").on("change",function(){
    if(this.checked) {
        $("#family_id").show(100);
    } else {
        $("#family_id").hide(100);
        $("#family_id").val("").change();
    }
});
</script>
@endsection
