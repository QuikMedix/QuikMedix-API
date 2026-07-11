@extends('layouts.master')

@section('title') Import Order @endsection
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
    color:red;
    font-size:20px;
    cursor:pointer;
    margin-top: 7px;
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
                    <form method="post" enctype="multipart/form-data">
                        @csrf
                    <div class="row">
                        <div class="col-8">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-12">
                                            <h5 style="background: #7a6fbe;color: #ffffff;padding: 5px;text-align: center;">Order Data</h5> 
                                        </div>
                                    </div>									
                                    @if($alert!='') 
                                        <div class="alert alert-danger" role="alert">{{ $alert }}</div>
                                    @endif
									<div class="row">
                                        <div class="col-5">
										    <h5 style="background: #2b3a4a;color: #ffffff;padding: 5px;text-align: center;margin-bottom: 24px;"><i class="mdi mdi-information-outline"></i> Details</h5>
									        <div class="row">
                                                <div class="col-sm-4" style="margin-bottom: 20px;">
                                                    <small>Count bags</small>
                                                    <input type="number" min="1" max="10" name="count_bags" class="form-control" value="1">
                                                </div>
                                                <div class="col-sm-4" style="margin-bottom: 20px;">
                                                    <small>Co-pay ($)</small>
                                                    <input type="number" min="0" step="0.01" class="form-control" value="{{$copay}}" name="copay" placeholder="Co-pay">
                                                    <div style="padding: 5px 20px;" class="paidph"> 
                                                        <input class="form-check-input" type="checkbox" id="formCheck2" value="1" name="copay_paid_pharm">
                                                        <label class="form-check-label" for="formCheck2">Paid at the pharmacy</label>
                                                    </div>
                                                </div>
                                                <div class="col-sm-4" style="margin-bottom: 20px;padding-top: 22px;"> 
                                                    <div style="float: right;">
                                                        <div class="form-check form-switch form-switch-md mr-4" dir="ltr">
                                                            <input type="checkbox" class="form-check-input" value="1" name="fridge" id="Fridge">
                                                            <label class="form-check-label" for="Fridge" style="padding: 3px;">Fridge</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-sm-6">
                                                    <small>Delivery options</small>
                                                    <select name="delivery_method" placeholder="Delivery options" class="form-control" required>
                                                        <option value="">Delivery options...</option>
                                                        @foreach($delivery_methods as $key=>$delivery_method)
                                                            <option value="{{ $delivery_method->id }}" @if($key==3){{'selected'}}@endif>{{ $delivery_method->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-sm-6">
                                                    <small>Preferred delivery time</small>
                                                    <select name="delivery_time" placeholder="Preferred delivery time" class="form-control" required>
                                                        <option value="">Preferred delivery time...</option>
                                                        @foreach($delivery_times as $key=>$delivery_time)
                                                            <option value="{{ $delivery_time->id }}" @if($key==0){{'selected'}}@endif>{{ $delivery_time->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-sm-6 mt-4">
                                                    <small>Delivery Time Frame (Test)</small>                                                
                                                </div>
                                                <div class="col-6 mt-4">                                         
                                                    <div style="float: right;">
                                                        <div class="form-check form-switch form-switch-md" dir="ltr">
                                                            <input type="checkbox" class="form-check-input" id="swith_time_id" checked>
                                                            <small class="form-check-label" for="swith_time_id" style="padding: 3px;">Any time delivery</small>
                                                        </div>                                               
                                                    </div>
                                                </div>
                                                <div class="col-sm-12 mt-4">                                                
                                                    <input type="text" style="display:none;" name="delivery_time_range" id="range_10">
                                                </div>                                          
                                            </div>                                          
									    </div>
										<div class="col-3">
											<h5 style="background: #2b3a4a;color: #ffffff;padding: 5px;text-align: center;margin-bottom: 24px;"><i class="mdi mdi-package-variant-closed"></i> Order Items</h5>
									        <div class="form-group row">                                            
                                                <div class="col-sm-12 rx-list">
                                                    @if(!empty($rxs))
                                                    @foreach($rxs as $key=>$rx)
                                                    @if($key==0)
                                                    <div class="rx-row col-sm-12 row" style="padding-right: 0;">
                                                        <div class="col-sm-6 rx-field" style="padding: 0">
                                                            <input type="text" maxlength="20" placeholder="Enter RX ID" name="rx_id[]" value="{{$rx['rx_id']}}" class="form-control">
                                                        </div>
                                                        <div class="col-sm-6 rx-field" style="padding: 0 0 0 5px;">
                                                            <input type="date" placeholder="Date" name="rx_date[]" value="{{$rx['rx_date']}}" class="form-control">
                                                        </div>                                                   
                                                    </div>
                                                    @else
                                                    <div class="rx-row col-sm-12 row" style="padding-right: 0;">
                                                        <div class="col-sm-6 rx-field" style="padding: 0">
                                                            <input type="text" required="" maxlength="20" placeholder="Enter RX ID" name="rx_id[]" value="{{$rx['rx_id']}}" class="form-control">
                                                        </div>
                                                        <div class="col-sm-6 rx-field" style="padding: 0">
                                                            <input type="date" required="" placeholder="Date" name="rx_date[]" value="{{$rx['rx_date']}}" class="form-control">
                                                        </div>
                                                        <div class="rx-field" style="position: absolute;right: -5px;">
                                                            <i class="fas fa-times remove-rx"></i>
                                                        </div>
                                                    </div>
                                                    @endif
                                                    @endforeach
                                                    @else
                                                    <div class="rx-row col-sm-12 row" style="padding-right: 0;">
                                                        <div class="col-sm-6 rx-field" style="padding: 0">
                                                            <input type="text" maxlength="20" placeholder="Enter RX ID" name="rx_id[]" class="form-control">
                                                        </div>
                                                        <div class="col-sm-6 rx-field" style="padding: 0 0 0 5px;">
                                                            <input type="date" placeholder="Date" name="rx_date[]" class="form-control">
                                                        </div>                                                   
                                                    </div>
                                                    @endif
                                                </div>                                            
                                                <div class="col-sm-12"  style="margin: 24px 0">
                                                    <div id="add_rx2"> 	
                                                    <button type="button" class="btn btn-secondary btn-sm btn-block waves-effect">Add RX <i class="fas fa-plus-circle" style="font-size:12px;"></i> </button>
                                                    </div>
                                                </div>
                                            </div>										
                                        </div>	
										<div class="col-4">
											<h5 style="background: #2b3a4a;color: #ffffff;padding: 5px;text-align: center;margin-bottom: 24px;"><i class="mdi mdi-alert-outline"></i> Special instructions</h5>
                                            <div class="col-sm-12" >
                                                <textarea class="form-control" name="special_instructions" rows="3"></textarea>
                                            </div>
											<div class="col-sm-12" style="margin: 20px 0;text-align: center;">
                                                <input type="radio" class="btn-check" value="1" name="type_driver" id="success-outlined" autocomplete="off" checked>
                                                <label class="btn btn-outline-primary" for="success-outlined">A2B Rx driver</label>
                                                <input type="radio" class="btn-check" value="2" name="type_driver" id="danger-outlined" autocomplete="off">
                                                <label class="btn btn-outline-primary" for="danger-outlined">Pharmacy driver</label>
                                            </div>
                                            <div class="col-sm-12 drivers-list" style="display:none;">
                                                <select class="select-state" placeholder="Select Driver..." name="driver">
                                                    <option value="">Select Driver...</option>
                                                    @foreach($drivers as $driver)
                                                        <option value="{{ $driver->id }}">{{ $driver->name }}, {{ $driver->phone }}, {{ $driver->car_info }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
										</div>
					                </div>
                                    <div class="mb-3 mt-3 float-right text-center text-black" style="background: #deeeff;width: 170px;padding: 5px 25px 5px 25px;border-radius: 8px;box-shadow: 0 -3px 31px 0 rgb(0 0 0 / 5%), 0 6px 20px 0 rgb(122 111 190 / 20%);border: solid 1px #ffffff;">
                                        <h5 class="mb-0">Cost <span class="badge bg-dark text-light"><b id="tariff">$<b>0.00</b></b></span></h5> 
                                        <div class="extra_charge" style="display:none;"> 
                                            <span class="badge bg-warning text-black">Custom tariff</span>
                                            <span class="badge bg-light">Extra charge to driver</span> 
                                            <div class="input-group">
                                                <div class="input-group-text">$</div>
                                                <input type="number" step="0.01" value="0" class="form-control" name="extra_charge_driver">
                                            </div>
                                        </div>
                                        <span class="badge bg-light">Test Mode</span> 
                                        <!-- <b id="tariff">(Teriff: $ <b>{{$pharmacy->tariff}}</b>)</b> -->
                                    </div>	
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 style="background: #7a6fbe;color: #ffffff;padding: 5px;text-align: center;">Patient Profile</h5>
                                    <input type="hidden" name="save" value="1">
                                    @if($alert!='') 
                                        <div class="alert alert-danger" role="alert">{{ $alert }}</div>
                                    @endif
                                    <div class="form-group row">
                                        <label for="example-text-input" class="col-sm-3 col-form-label">First Name</label>
                                        <div class="col-sm-9">
                                            <input class="form-control" required type="text" name="name" value="{{ $user->name }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label for="example-text-input" class="col-sm-3 col-form-label">Last Name</label>
                                        <div class="col-sm-9">
                                            <input class="form-control" required type="text" name="last_name" value="{{ $user->last_name }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label for="example-text-input" class="col-sm-3 col-form-label">Cell Phone</label>
                                        <div class="col-sm-9">
                                            <input class="form-control" required type="phone" name="phone" id="phone" value="{{ $user->phone }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label for="example-text-input" class="col-sm-3 col-form-label">Home Phone</label>
                                        <div class="col-sm-9">
                                            <input class="form-control" type="phone" name="home_phone" id="home_phone" value="{{ $user->home_phone }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label for="example-text-input" class="col-sm-3 col-form-label">Address</label>
                                        <div class="col-sm-9">
                                            <input class="form-control" required id="searchTextField" type="text" name="address" value="{{ $user->address }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label for="example-text-input" class="col-sm-3 col-form-label">Apartment, STE</label>
                                        <div class="col-sm-9">
                                            <input class="form-control" type="text" name="apartment" value="{{ $user->apartment }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label for="example-text-input" class="col-sm-3 col-form-label">Zip</label>
                                        <div class="col-sm-9">
                                            <input class="form-control" required type="text" name="zip" value="{{ $user->zip }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="text-center py-2">  <button type="submit" class="btn btn-outline-dark waves-effect waves-light" style="min-width: 350px;">Submit <i class="fas fa-arrow-alt-circle-right"></i></button></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </form>
                    <!-- end row -->
                    <script src="https://maps.googleapis.com/maps/api/js?key={{config('app.googlemaps_apikey')}}&region=US&language=en&libraries=places&v=weekly&callback=initialize" defer></script>
                    <script>
                        function initialize() {
                            var input = document.getElementById('searchTextField');
                            var res1 = new google.maps.places.Autocomplete(input);
                        }
                    </script>
@endsection
@section('footerScript')
<script src="{{ URL::asset('/js/select2.min.js')}}"></script>
<script src="{{ URL::asset('/libs/rwd-table/rwd-table.min.js')}}"></script>
<script src="{{ URL::asset('/js/ion.rangeSlider.min.js')}}"></script>
<script type='text/javascript'>
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
$(document).on('keypress', '.select2-search__field', function () {
    $(this).val($(this).val().replace(/[^\d].+/, ""));
    if ((event.which < 48 || event.which > 57)) {
      event.preventDefault();
    }
});
$('#add_rx2').on('click',function() {
    $('.rx-list').append('<div class="rx-row col-sm-12 row" style="padding-right: 0;"><div class="col-sm-6 rx-field" style="padding: 0"><input type="text" required maxlength="20" placeholder="Enter RX ID" name="rx_id[]" class="form-control"></div><div class="col-sm-6 rx-field" style="padding: 0"><input type="date" required placeholder="Date" name="rx_date[]" class="form-control"></div><div class="rx-field"  style="position: absolute;right: -5px;"><i class="fas fa-times remove-rx"></i></div></div>');
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