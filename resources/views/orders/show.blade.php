@extends('layouts.master')

@section('title') Order Show @endsection
<link href="/css/sweetalert2.min.css" rel="stylesheet" type="text/css">
<link href="/leaflet/leaflet.css" rel="stylesheet" type="text/css">
<style>
    .table th, .table td {
        width: 50%;
    }
	.card-body {  
    font-size: 16px;}
	.card-body i {
    font-size: 16px;
		
}
	.mfp-title a {display: none }
	.mfp-gallery .mfp-image-holder .mfp-figure {
    cursor: pointer;
    background: white;
}
	.mfp-title {   
    background: #c90016;
    padding: 5px;
}
	.nav-tabs > li > a, .nav-pills > li > a {
    color: #000000;
	font-size: 16px;
}	
audio {
    width: 100%;
}
.loader {
    position: relative;
    left: 50%;
    transform: translateX(-50%);
}
.printbt {
    cursor: pointer;
    padding: 0 10px 2px 10px !important;
}
.printbt i {
    color: #fff;
}
.rating{
    display: inline-block;
    vertical-align: middle;
    margin-left: 5px;
}
.rating i {
    color:#f5b225;
}
.facility {
    font-size: 10px !important;
    background: linear-gradient(148deg, #c90016 0%, #960010 55%, #e34152 100%);
}
.facility i {
    font-size: 14px;
}
#HIPAA p {margin: 0; padding: 0;}	
    .ft10{font-size:11px;font-family:Times;color:#000000;}
	.ft11{font-size:15px;font-family:Times;color:#000000;}
	.ft12{font-size:13px;font-family:Times;color:#000000;}
	.ft13{font-size:8px;font-family:Times;color:#000000;}
	.ft14{font-size:13px;font-family:Times;color:#000000;}
	.ft15{font-size:14px;font-family:Times;color:#000000;}
	.ft16{font-size:-1px;font-family:Times;color:#000000;}
	.ft17{font-size:13px;font-family:Times;color:#000000;}
	.ft18{font-size:11px;font-family:Times;color:#000000;}
	.ft19{font-size:16px;font-family:Times;color:#000000;}
	.ft110{font-size:9px;font-family:Times;color:#000000;}
	.ft111{font-size:13px;line-height:18px;font-family:Times;color:#000000;}
	.ft112{font-size:13px;line-height:18px;font-family:Times;color:#000000;}
	.ft113{font-size:13px;line-height:17px;font-family:Times;color:#000000;}
    .ft20{font-size:16px;font-family:Times;color:#000000;}
	.ft21{font-size:18px;font-family:Times;color:#000000;}
	.ft22{font-size:17px;font-family:Times;color:#000000;}
	.ft23{font-size:16px;line-height:20px;font-family:Times;color:#000000;}
	.ft24{font-size:17px;line-height:22px;font-family:Times;color:#000000;}
.marker-label {
    white-space: pre-wrap;
    margin-top: 20px;
}
</style>
@section('content')
 <!-- start page title -->
	<ul class="nav nav-tabs">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#info"><i class="mdi mdi-information-outline"></i> Information</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#history"><i class="mdi mdi-history"></i> History</a>
                </li>
				<li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#instructions"><i class="mdi mdi-alert-outline"></i> Instructions</a>
                </li>
				<li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#items"><i class="mdi mdi-package-variant-closed"></i> Order Items</a>
                </li>
		    	 <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#voice"><i class="mdi mdi-volume-high"></i> Call recording</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#video"><i class="mdi mdi-video-outline"></i> Video <span class="badge" style="background-color: #beffc7 !important;color: black;display: inline-block;margin: 0 0 0 5px;font-size: 10px;">Soon</span></a>
                </li>
            </ul>
	 <div class="tab-content">
		 <div class="tab-pane fade show active" id="info">
			 <div class="row">
				 <div class="col-8">
					 <div class="card">
                        <div class="card-body">
							<h5 class="qm-section-title">Order Details</h5>
							
						<div class="row" style="min-height: 420px;">
						<div class="col-6">							
									<b>Order:</b> {{$order->id}} - <span style="font-size: 11px;" class="badge badge-pill badge-{{$order->statusecolor}}">{{$order->statusename}}</span><br>
								    @if($driver!='')
                                    <b>Time away:</b> {{floor($order->eta / 60)}} hr {{$order->eta % 60}} min
                                    <form method="POST" id="eta_calculate" class="d-none">
                                        @csrf
                                        <input type="hidden" name="eta_calculate" value="1">
                                    </form>
                                    <button type="button" class="btn btn-outline-secondary ml-4" onclick="if(confirm('Are you sure?')){$('#eta_calculate').submit();}">Refresh ETA</button>
                                    <br>
                                    @endif
                                    <div class="mt-2">
									<b>Co-Pay:</b> {{$order->copay}} $
                                        @if(!empty($order->statuse_copay_name))
                                            <span style="font-size: 11px;" class="badge badge-pill badge-{{$order->statuse_copay_color}} mt-1">{{$order->statuse_copay_name}}</span>
                                        @endif
                                        
                                        @if(!in_array($order->statuse_id,[1,7,8,9,10]) && ((Auth::user()->hasAnyRole('superadmin', 'admin')) || Auth::user()->role == 'logist') && $order->copay>0 && !in_array($order->statuse_copay,[1,3,4,6]))
                                        <a style="cursor:pointer;" onclick="var win=window.open('/pay/copay/{{$order->id}}','Pay order Co-Pay','width=800,height=700');var timer=setInterval(function(){if(win.closed){clearInterval(timer);document.location.reload();}},600);"><button class="btn btn-success btn-sm" type="button">Pay</button></a>
                                        <form method="post" style="display: inline-block;">
                                            @csrf
                                            <input type="hidden" name="paid" value="1">
                                            <a class="btn btn-success btn-sm waves-effect" type="button" onclick="if(confirm('Are you sure?')){$(this).parent('form').submit();}" style="color: #fff;">Paid by cash</a>
                                        </form>
                                        <form method="post" style="display: inline-block;">
                                            @csrf
                                            <input type="hidden" name="not_paid" value="1">
                                            <a class="btn btn-danger btn-sm waves-effect" type="button" onclick="if(confirm('Are you sure?')){$(this).parent('form').submit();}" style="color: #fff;">Not Paid</a>
                                        </form>
                                        @endif
                                    </div>
									<b>Created:</b> {{date('m/d/Y g:i A', strtotime($order->created ?? ''))}}<br>
									<b>Need Delivery:</b> <span style="font-size:100%;" class="badge @if(empty($order->finish) && strtotime(date('Y-m-d'))==strtotime($order->delivery_date ?? '')){{'bg-success'}}@elseif(empty($order->finish) && strtotime(date('Y-m-d'))>strtotime($order->delivery_date ?? '')){{'bg-danger'}}@else{{'bg-light'}}@endif">{{date('m/d/Y', strtotime($order->delivery_date ?? ''))}} @if(!empty($order->delivery_time_range) && $order->delivery_time_range!='9:00 AM;12:00 AM')<b>{{str_replace(':00','',str_replace(';',' - ',$order->delivery_time_range))}}</b>@endif</span><br>
									 @if($order->statuse_id==4)
                                    <b>Delivered:</b> {{date('m/d/Y g:i A', strtotime($order->finish ?? ''))}}<br>
                                    @if(!empty($order->delivery_address))
                                    <b>Delivered Address:</b> {{$order->delivery_address}} <br>
                                    <b>Delivered Location:</b> {{$order->delivery_location}} <br>
                                    @else
                                    <b>Delivered Address:</b> {{$order->useraddress}}, {{$order->userzip}}, Apt {{$order->userapartment}} <br>
                                    <b>Delivered Location:</b> {{$order->userlocation}} <br>
                                    @endif
                                    @endif
									<b>Options:</b> {{$order->delivery_method}}<br>
									<b>Time:</b> {{$order->delivery_time}}<br>
									<b>Fridge:</b> {{($order->fridge>0)?'yes':'no'}}<br>
                                    @if((Auth::user()->role == 'medic' || (Auth::user()->hasAnyRole('superadmin', 'admin'))) && ($order->statuse_id==4))
                                    <b>Customer rating:</b> <div class="rating">
                                    @if(empty($order->rating))
                                    <i class="mdi mdi-star-outline"></i><i class="mdi mdi-star-outline"></i><i class="mdi mdi-star-outline"></i><i class="mdi mdi-star-outline"></i><i class="mdi mdi-star-outline"></i>
                                    @else
                                    @if($order->rating<=1)
                                    <i class="mdi mdi-star"></i><i class="mdi mdi-star-outline"></i><i class="mdi mdi-star-outline"></i><i class="mdi mdi-star-outline"></i><i class="mdi mdi-star-outline"></i>
                                    @elseif($order->rating==2)
                                    <i class="mdi mdi-star"></i><i class="mdi mdi-star"></i><i class="mdi mdi-star-outline"></i><i class="mdi mdi-star-outline"></i><i class="mdi mdi-star-outline"></i>
                                    @elseif($order->rating==3)
                                    <i class="mdi mdi-star"></i><i class="mdi mdi-star"></i><i class="mdi mdi-star"></i><i class="mdi mdi-star-outline"></i><i class="mdi mdi-star-outline"></i>
                                    @elseif($order->rating==4)
                                    <i class="mdi mdi-star"></i><i class="mdi mdi-star"></i><i class="mdi mdi-star"></i><i class="mdi mdi-star"></i><i class="mdi mdi-star-outline"></i>
                                    @else
                                    <i class="mdi mdi-star"></i><i class="mdi mdi-star"></i><i class="mdi mdi-star"></i><i class="mdi mdi-star"></i><i class="mdi mdi-star"></i>
                                    @endif
                                    @endif
                                    </div><br>
                                    @endif 
							    <div style="margin: 8px 0">
                                    <button class="btn btn-dark waves-effect waves-light printbt" onclick="PrintElem('#finish-print')" style="margin-top: 8px;">Delivery Slip <i class="mdi mdi-printer-check"></i></button>
                                    <button class="btn btn-dark waves-effect waves-light printbt" onclick="PrintElem2('#HIPAA')" style="margin-top: 8px;">HIPAA <i class="mdi mdi-printer-check"></i></button>
                                    <button class="btn btn-dark waves-effect waves-light printbt" onclick="PrintElem3('#AOB')" style="margin-top: 8px;">NYS FORM NF-AOB <i class="mdi mdi-printer-check"></i></button>
                                    <button class="btn btn-dark waves-effect waves-light printbt" onclick="PrintElem7('#all-print')" style="margin-top: 8px;">Print all <i class="mdi mdi-printer-check"></i></button>
                                    @if($order->statuse_id==4)
                                    <div class="row" style="margin: 10px 0;">
                                        <button id="show_live_tracker" type="button" class="btn btn-primary btn-lg waves-effect waves-light me-1 mt-2" data-bs-toggle="modal" data-bs-target=".bs-example-modal-lg">Live tracker at delivery time</i></button>
                                    </div>                                    
                                    @endif
                                </div>
							@if(($order->statuse_id==4 || $order->statuse_id==3))
                            <div style="margin: 10px 0;"><b>Signature owner:</b> {{$order->signature_type}}</div>	
                            <form method="POST" enctype="multipart/form-data">
                                @csrf
							@if($order->drop_off_photo!='')
							<div class="zoom-gallery">
							<a class="float-left" href="{{$order->drop_off_photo}}" title="Drop Off Photo" style="margin: 10px;"><img src="{{$order->drop_off_photo}}" alt="Drop Off Photo" height="120"></a></div>
							@else
                            @if(Auth::user()->hasAnyRole('superadmin', 'admin', 'dispadmin', 'logist'))
							<div class="form-group row" style="margin: 10px;">
                                <div>
                                    <input class="form-control" type="file" name="drop_off_photo" onchange='encodeImageFileAsURL(this);' accept="image/x-png,image/jpeg,image/jpg">
                                    <small>Image cannot be larger than 10 mb</small>
                                </div>
                            </div>     
							@endif
							@endif
							@if(!empty($order->signature_photo))
							<div class="zoom-gallery">
							    <a class="float-left" href="{{$order->signature_photo}}" title="Signature Photo" style="margin: 10px;"><img src="{{$order->signature_photo}}" alt="Signature Photo" height="120"></a>                            
                            </div> 
							@else
                            @if(Auth::user()->hasAnyRole('superadmin', 'admin', 'dispadmin', 'logist')) 
                           	<div class="form-group row" style="margin: 10px;">
                                <div>
                                    <input class="form-control" type="file" name="signature_photo" onchange='encodeImageFileAsURL(this);' accept="image/x-png,image/jpeg,image/jpg">
                                    <small>Image cannot be larger than 10 mb</small>
                                </div>
                            </div>
							@endif
                            @endif										
							@if(empty($order->drop_off_photo) || empty($order->signature_photo))
                                @if((Auth::user()->hasAnyRole('superadmin', 'admin')))  
                                    <div align="center"><button class="btn btn-primary" type="submit">Save</button></div>
                                @endif
                            @endif                            
                            </form>
                            @endif 
                            @if(!empty($order->signature_photo))
                            <form method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="rotate_signature" value="1">
                                <button class="btn btn-outline-primary btn-sm waves-effect waves-light" type="submit" role="button"><i class="mdi mdi-format-text-rotation-angle-down"></i> Rotate Signature 90&deg;</button>
                            </form>
                            @endif 
						 </div>
						<div class="col-6">
							<span style="font-size: 19px;font-weight: 900; color: #000000;border-bottom: solid #000 1px;">Pharmacy </span><br>
							<i class="mdi mdi-medical-bag"></i> {{$order->pharmacyname}}<br>
							@if(!empty($order->medic_id))<i class="mdi mdi-account-heart"></i> {{$order->medicname}} {{$order->mediclast_name}} (ID #{{$order->medic_id}})<br>@endif
							<i class="mdi mdi-cellphone-android"></i> {{$order->pharmacyphone}}
                            @if(Auth::user()->hasAnyRole('superadmin', 'admin', 'logist'))                
                            <button class="btn btn-primary btn-sm waves-effect call_patient" data-phone="{{ $order->pharmacyphone }}">Call <i class="ti-headphone-alt" style="color:#fff;font-size: inherit;"></i></button>
                            @endif 
                            <br>
							<i class="mdi mdi-map-check-outline"></i> {{$order->pharmacyaddress}}<br>                            					
							<span style="font-size: 19px;font-weight: 900; color: #000000;border-bottom: solid #000 1px;">@if($order->facility) Facility @else Customer @endif</span>
                            @if($order->facility)
                            <a href="/facilitys/{{$order->pharmacy_id}}/edit/{{$order->user_id}}" target="_blank"><span class="badge badge-pill badge-dark" style="margin: 7px 0;font-size: 11px;padding: 3px 7px;font-weight: 100;background-color: #286200;">Edit <i style="color:#fff;font-size: inherit;" class="ti-settings"></i> </span> </a>
                            @else
                            <a href="/patients/{{$order->pharmacy_id}}/edit/{{$order->user_id}}" target="_blank"><span class="badge badge-pill badge-dark" style="margin: 7px 0;font-size: 11px;padding: 3px 7px;font-weight: 100;background-color: #286200;">Edit <i style="color:#fff;font-size: inherit;" class="ti-settings"></i> </span> </a>
                            @endif
                            <br>							
							<i class="mdi mdi-emoticon-happy-outline"></i> {{$order->last_name}} {{$order->username}}
                            @if($order->useros==1)
                            <span class="badge badge-pill badge-dark" style="margin: 7px 0;">Android <i style="color:#fff;font-size: inherit;" class="ion ion-logo-android"></i> </span> 
                            @elseif($order->useros==2)
                            <span class="badge badge-pill badge-dark" style="margin: 7px 0;">iOS <i style="color:#fff;font-size: inherit;" class="ion ion-logo-apple"></i></span>
                            @else
                            <span class="badge badge-pill badge-danger" style="margin: 7px 0;">No app <i style="color:#fff;font-size: inherit;" class="ion ion-md-close-circle-outline"></i></span> <span style="cursor:pointer;" class="badge badge-pill badge-info" style="margin: 7px 0;" onclick="reSendAuthMessage('{{$order->user_id}}',this)">Resend</span>
                            @endif
                            <br>
							<i class="mdi mdi-cellphone-android"></i> {{$order->userphone}} 
                            @if(Auth::user()->hasAnyRole('superadmin', 'admin', 'logist'))
                            <button class="btn btn-primary btn-sm waves-effect call_patient" data-phone="{{ $order->userphone }}">Call <i class="ti-headphone-alt" style="color:#fff;font-size: inherit;"></i></button>
                            @endif
                            @if(!empty($order->userhomephone))
                            <br>
							<i class="mdi mdi-deskphone"></i> {{$order->userhomephone}} 
                            @if(Auth::user()->hasAnyRole('superadmin', 'admin', 'logist'))
                            <button class="btn btn-primary btn-sm waves-effect call_patient" data-phone="{{ $order->userhomephone }}">Call <i class="ti-headphone-alt" style="color:#fff;font-size: inherit;"></i></button>
                            @endif
                            @endif 
                            <br>
							<i class="mdi mdi-map-clock-outline"></i> {{$order->useraddress}}, {{$order->userzip}}<br>
							<i class="mdi mdi-office-building"></i> {{$order->userapartment}}<br>
							@if($driver!='')
							<span style="font-size: 19px;font-weight: 900; color: #000000;border-bottom: solid #000 1px;">Driver</span><br>
							<i class="mdi mdi-emoticon-cool-outline"></i> {{ $driver->name }} {{ $driver->last_name }}<br>
							<i class="mdi mdi-cellphone-android"></i> {{ $driver->phone }}<br>
							<i class="mdi mdi-car"></i> {{ $driver->car_info }}
							@else
                             Driver not selected<br>                                              
                            @endif							
						 </div>							
						</div>
					</div>
                        <div style="display:none;" id="all-print">
                            <div class="row">
                                <div class="col-3">
                                </div>
                                <div class="col-6" style="color: #000000;padding: 5px 0;text-align: center;">
                                    <h1 style="color: #000000;padding: 5px 0;text-align: center; margin: 30px 0 0 0;font-size: 20px;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">{{$order->pharmacyname}}</h1 >
                                    <h2 style="margin: 10px 0 0 0;font-size: 16px;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">Address: {{$order->pharmacyaddress}}</h2>
                                    <h2 style="margin: 10px 0 0 0;font-size: 16px;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">Phone: {{$order->pharmacyphone}}</h2>                    
                                </div>
                                <div class="col-3">
                                </div>
                                <div class="col-5">
                                </div>
                                <div class="col-2" style="color: #000000;padding: 5px 8px 0 8px;font-weight: bold;text-align: center; border: 2px solid #000; margin: 15px 0 0 0;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;"><h3>Delivery Slip</h3></div>
                                <div class="col-5"></div>
                            </div>
                            <table width="100%" border="0">
                                <tbody>
                                    <tr style="border-bottom: 2px solid #000;">
                                    <td>&nbsp;</td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="row" style="margin: 30px 20px;">
                                <div class="col-6" style="color: #000000;line-height:27px;"font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;>
                                    <span style="color: #000000; font-size: 17px; text-decoration: underline;font-weight: bold;text-transform: uppercase;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">Client:</span><br>	
                                    <span style="font-size: 15px;margin-top: 15pxfont-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">{{$order->last_name}} {{$order->username}}</span><br>
                                    <span style="font-size: 15px;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">{{$order->useraddress}} {{$order->userzip}} / {{$order->userapartment}}</span><br>
                                    <span style="font-size: 15px;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">Ph#: {{$order->userphone}}</span><br>
                                </div>
                                <div class="col-6" style="color: #000000;line-height: 30px;">
                                </div>
                                <br><br>
                                <div class="col-12" style="color: #000000;line-height: 30px;border-bottom: 1px solid #000;border-top: 1px solid #000;margin: 15px 0;padding: 5px 0 5px 0;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">
                                    <table width="100%" border="0" style="color: #000000;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">
                                        <tbody>
                                            <tr bgcolor="#E3E3E3" style="font-weight: bold">
                                            <td bgcolor="#E3E3E3" width="100" align="center">Date</td>
                                            <td bgcolor="#E3E3E3" width="150" align="center">RX#</td>
                                            <td bgcolor="#E3E3E3" width="150" align="center">RF#</td>
                                            <td bgcolor="#E3E3E3" width="250" align="center">RX Barcode</td>
                                            <td bgcolor="#E3E3E3" width="250" align="center">Qty</td>
                                            </tr>
                                            @foreach($rxs as $key=>$rx)
                                            <tr>
                                            <td align="center">{{ $rx->rx_date }}</td>
                                            <td align="center">{{explode('-',$rx->rx_id)[0]}}</td>
                                            <td align="center">@if(count(explode('-',$rx->rx_id))>1){{explode('-',$rx->rx_id)[1]}}@endif</td>
                                            <td align="center">@if(!empty($rx->rx_id))<img style="margin-bottom:10px;" src="data:image/png;base64,{{ DNS1D::getBarcodePNG($rx->rx_id, 'C128',1.5,24) }}" alt="barcode"/>@endif</td>
                                            <td align="center">{{ $rx->rx_count }}</td>    
                                            </tr>
                                            @endforeach 
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="row" style="margin: 30px 20px;">	
                                <div class="col-8" style="color: #000000;margin: 15px 0;font-size: 16px;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">		
                                Total Rx Count: {{count($rxs)}}<br>
                                Patient is requesting Counseling: &nbsp;&nbsp;&nbsp;&nbsp; Yes <span style="border-bottom: 1px solid #000;">_____ </span> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; No <span style="border-bottom: 1px solid #000;">______ </span>
                                </div>
                                <div class="col-4" style="color: #000000;margin: 15px 0;font-size: 16px;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">		
                                ${{$order->copay}}<br>                            
                                </div>
                                <div class="col-12" style="font-size: 16px;color: #000000;margin: 15px 0;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">		
                                    I certify that I requested and received my medication listed above from {{$order->pharmacyname}} located at {{$order->pharmacyaddress}} (the “Pharmacy”). I further certify that I had a patient relationship with the
                                    ordering medical provider indicated on the prescription label and that I requested that the prescriber send this
                                    prescription to the Pharmacy. The foregoing is certified as true and accurate under the penalty of perjury.
                                </div>                       

                                <div class="col-6" style="font-size: 16px;color: #000000;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;margin-top: 20px;">
                                Receiver'ce Name: <b>{{$order->last_name}} {{$order->username}}</b><br><br>
                                Receiver'ce Signature: 	@if(!empty($order->signature_photo))<img src="{{$order->signature_photo}}" alt="Signature Photo" style="width:auto;height: 120px;margin: 0px;position: absolute; left: 175px; top: 28px;">@endif
                                
                                </div>

                                <div class="col-6" style="font-size: 16px;color: #000000;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;margin-top: 20px;">
                                Date/Time: <b>{{date('m.d.Y g:i A', strtotime($order->finish ?? ''))}}</b><br><br>
                                Delivered By <b style="text-transform: capitalize;">@if(!empty($driver)){{ $driver->name }} {{ $driver->last_name }}@endif</b>
                                </div>
                            </div>
                            <br><div class="row other-pages" style="page-break-after: always;"> </div>
                            <style type="text/css">
                                p {margin: 0; padding: 0;}	.ft10{font-size:11px;font-family:Times;color:#000000;}
                                .ft11{font-size:15px;font-family:Times;color:#000000;}
                                .ft12{font-size:13px;font-family:Times;color:#000000;}
                                .ft13{font-size:8px;font-family:Times;color:#000000;}
                                .ft14{font-size:13px;font-family:Times;color:#000000;}
                                .ft15{font-size:14px;font-family:Times;color:#000000;}
                                .ft16{font-size:-1px;font-family:Times;color:#000000;}
                                .ft17{font-size:13px;font-family:Times;color:#000000;}
                                .ft18{font-size:11px;font-family:Times;color:#000000;}
                                .ft19{font-size:16px;font-family:Times;color:#000000;}
                                .ft110{font-size:9px;font-family:Times;color:#000000;}
                                .ft111{font-size:13px;line-height:18px;font-family:Times;color:#000000;}
                                .ft112{font-size:13px;line-height:18px;font-family:Times;color:#000000;}
                                .ft113{font-size:13px;line-height:17px;font-family:Times;color:#000000;}
                            </style>
                            <div id="page1-div" style="position:relative;width:918px;height:1188px;">
                                @include('layouts.partials.document-image', ['document' => 'authorization', 'description' => 'Authorization form', 'imageStyle' => 'width: 918px; height: 1188px;'])
                                <p style="position:absolute;top:36px;left:43px;white-space:nowrap" class="ft10"><b>&#160;</b></p>
                                <p style="position:absolute;top:36px;left:97px;white-space:nowrap" class="ft10"><b>&#160;</b></p>
                                <p style="position:absolute;top:36px;left:151px;white-space:nowrap" class="ft10"><b>&#160;</b></p>
                                <p style="position:absolute;top:36px;left:205px;white-space:nowrap" class="ft10"><b>&#160;</b></p>
                                <p style="position:absolute;top:36px;left:259px;white-space:nowrap" class="ft10"><b>&#160;</b></p>
                                <p style="position:absolute;top:36px;left:313px;white-space:nowrap" class="ft10"><b>&#160;</b></p>
                                <p style="position:absolute;top:36px;left:367px;white-space:nowrap" class="ft10"><b>&#160;</b></p>
                                <p style="position:absolute;top:36px;left:421px;white-space:nowrap" class="ft10"><b>&#160;</b></p>
                                <p style="position:absolute;top:36px;left:475px;white-space:nowrap" class="ft10"><b>&#160;&#160;</b></p>
                                <p style="position:absolute;top:36px;left:529px;white-space:nowrap" class="ft10"><b>&#160;</b></p>
                                <p style="position:absolute;top:36px;left:583px;white-space:nowrap" class="ft10"><b>&#160;</b></p>
                                <p style="position:absolute;top:36px;left:637px;white-space:nowrap" class="ft10"><b>&#160; &#160; &#160; &#160;&#160; &#160;&#160;&#160;&#160; &#160; &#160;&#160;&#160;OCA Official Form No.:&#160;960&#160;</b></p>
                                <p style="position:absolute;top:53px;left:107px;white-space:nowrap" class="ft11"><b>AUTHORIZATION FOR RELEASE OF HEALTH INFORMATION PURSUANT TO HIPAA</b></p>
                                <p style="position:absolute;top:55px;left:811px;white-space:nowrap" class="ft12"><b>&#160;</b></p>
                                <p style="position:absolute;top:74px;left:212px;white-space:nowrap" class="ft12"><b>[This form has been approved by the New York State Department of Health]&#160;</b></p>
                                <p style="position:absolute;top:90px;left:459px;white-space:nowrap" class="ft13"><b>&#160;</b></p>
                                <p style="position:absolute;top:104px;left:51px;white-space:nowrap;line-height: 15px;" class="ft14">Patient&#160;Name&#160; <br>
                                {{$order->last_name}} {{$order->username}}</p>
                                <p style="position:absolute;top:104px;left:503px;white-space:nowrap" class="ft14">Date of Birth&#160;</p>
                                <p style="position:absolute;top:104px;left:699px;white-space:nowrap" class="ft14">Social Security Number&#160;&#160;</p>
                                <p style="position:absolute;top:148px;left:51px;white-space:nowrap;line-height: 15px;" class="ft14">Patient Address<br>
                                {{$order->useraddress}}, {{$order->userzip}}</p>
                                <p style="position:absolute;top:147px;left:147px;white-space:nowrap" class="ft15">&#160;</p>
                                <p style="position:absolute;top:202px;left:43px;white-space:nowrap" class="ft14">I, or my authorized representative, request that health information regarding my care and treatment be released as set forth on this form:&#160;</p>
                                <p style="position:absolute;top:224px;left:43px;white-space:nowrap" class="ft112">In accordance with New York State Law and the Privacy Rule of the Health Insurance Portability and Accountability Act&#160;of 1996&#160;<br/>(HIPAA),&#160;&#160;&#160;I understand that:&#160;<br/>1.&#160;&#160;This authorization may include disclosure of information relating to&#160;&#160;<b>ALCOHOL</b>&#160;and&#160;&#160;<b>DRUG ABUSE, MENTAL HEALTH&#160;<br/>TREATMENT</b>, except psychotherapy notes, and&#160;<b>CONFIDENTIAL HIV* RELATED INFORMATION</b>&#160;only if I place my initials on&#160;<br/>the appropriate line in Item 9(a). &#160;In the event the health information described below includes any of these types of information, and I&#160;<br/>initial the line on the box in Item 9(a), I specifically authorize release of such information to the person(s) indicated&#160;in Item 8.&#160;<br/>2.&#160;&#160;If I am authorizing the release of HIV-related, alcohol or drug treatment, or mental health treatment information, the recipient is&#160;<br/>prohibited from redisclosing such information without my authorization unless permitted to do so under federal or state law. &#160;I&#160;<br/>understand that I have the right to request a list of people who may receive or use my HIV-related information without authorization. &#160;If&#160;<br/>I experience discrimination because of the release or disclosure of HIV-related information, I may contact the New York State Division&#160;<br/>of Human Rights at (212) 480-2493 or the New York City Commission of Human Rights at (212) 306-7450. &#160;These agencies are&#160;<br/>responsible for protecting my rights.&#160;<br/>3.&#160;&#160;I have the right to revoke this authorization at any time by writing to the health care provider listed below. &#160;I understand that I may&#160;<br/>revoke this authorization except to the extent that action has already been taken based on this authorization.&#160;<br/>4.&#160;&#160;I understand that signing this authorization is voluntary. My treatment, payment, enrollment in a health plan, or eligibility for&#160;<br/>benefits will not be conditioned upon my authorization of this disclosure.&#160;&#160;<br/>5.&#160;&#160;Information disclosed under this authorization might be redisclosed by the recipient (except as noted&#160;&#160;above&#160;in Item&#160;2), and this&#160;<br/>redisclosure may no longer be protected by federal or state law.&#160;<br/>6.&#160;&#160;<b>THIS AUTHORIZATION DOES NOT AUTHORIZE YOU TO DISCUSS MY HEALTH INFORMATION OR MEDICAL&#160;<br/>CARE WITH ANYONE OTHER THAN THE ATTORNEY OR GOVERNMENTAL AGENCY SPECIFIED IN&#160;ITEM 9&#160;(b).&#160;</b></p>
                                <p style="position:absolute;top:586px;left:44px;white-space:nowrap;line-height: 15px;" class="ft14">7. Name and address of health provider or entity to release this information:&#160;<br><b>{{$order->pharmacyname}}</b> - {{$order->pharmacyaddress}}</p>
                                <p style="position:absolute;top:625px;left:44px;white-space:nowrap" class="ft14">8. Name and address of person(s) or category of person to whom this information will be sent:</p>
                                <p style="position:absolute;top:634px;left:451px;white-space:nowrap" class="ft16">&#160;</p>

                                <p style="position:absolute;top:643px;left:44px;white-space:nowrap" class="ft14">&#160;</p>
                                <p style="position:absolute;top:664px;left:44px;white-space:nowrap" class="ft111">9(a). &#160;Specific information to be released:&#160;<br/>&#160; &#160; &#160; &#160;&#160;&#160; <input type="checkbox" id="chk" name="chk"> Medical Record from (insert date)&#160;<b>___________________</b>&#160;to (insert date)&#160;<b>___________________</b>&#160;&#160;<br/>&#160; &#160; &#160; &#160;&#160;&#160; <input type="checkbox" id="chk" name="chk"> Entire Medical Record, including patient histories, office notes (except psychotherapy notes), test results, radiology studies, films,&#160;<br/>&#160; &#160; &#160; &#160; &#160; &#160; &#160;referrals, consults, billing records, insurance records, and records sent to you by other health care providers.&#160;&#160;</p>
                                <p style="position:absolute;top:740px;left:44px;white-space:nowrap" class="ft14">&#160; &#160; &#160;&#160;&#160;&#160;&#160; <input type="checkbox" id="chk" name="chk"> Other: &#160;<b>__________________________________</b>&#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160;&#160; &#160;Include:&#160;(<i>Indicate by Initialing</i>)<b>&#160;</b></p>
                                <p style="position:absolute;top:763px;left:44px;white-space:nowrap" class="ft14">&#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160;&#160;&#160;&#160;&#160;&#160;<b>__________________________________</b>&#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160;&#160;&#160;&#160;&#160;&#160; &#160; &#160; &#160;&#160;&#160;&#160;<b>________</b>&#160;<b>Alcohol/Drug Treatment&#160;</b></p>
                                <p style="position:absolute;top:783px;left:44px;white-space:nowrap" class="ft14">&#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160;&#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160;&#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;<b>________</b>&#160;<b>Mental Health Information&#160;</b></p>
                                <p style="position:absolute;top:804px;left:44px;white-space:nowrap" class="ft12"><b>Authorization to Discuss Health Information</b>&#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160;&#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160;&#160; &#160; &#160;&#160;&#160;<b>________</b>&#160;<b>HIV-Related Information&#160;</b></p>
                                <p style="position:absolute;top:831px;left:50px;white-space:nowrap;line-height: 12px;" class="ft14">&#160;(b)&#160;&#160;<input type="checkbox" id="chk" name="chk" checked> By initialing here <span style="text-decoration: underline;">&nbsp;&nbsp;&nbsp;{{mb_substr($order->username,0,1,'UTF-8')}}.&nbsp; {{mb_substr($order->last_name,0,1,'UTF-8')}}.&nbsp;&nbsp;&nbsp;</span> I authorize <span style="text-decoration: underline;">{{$order->pharmacyname}}</span>_______________________________________&#160;</p>
                                <p style="position:absolute;top:849px;left:50px;white-space:nowrap" class="ft18">&#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160;&#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160;&#160; &#160; &#160; &#160; &#160; &#160;&#160;Initials&#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160;&#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160;&#160;&#160;&#160;Name of individual health care provider&#160;</p>
                                <p style="position:absolute;top:865px;left:44px;white-space:nowrap" class="ft111">&#160; &#160; &#160; &#160; &#160;to&#160;discuss my health information with my attorney, or a governmental agency, listed here:&#160;<br/>&#160;&#160;&#160; &#160; &#160;&#160;&#160;<b>______________________________________________________________________________________________________&#160;</b></p>
                                <p style="position:absolute;top:895px;left:44px;white-space:nowrap" class="ft18">&#160;&#160;&#160;&#160;&#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160;&#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160;&#160;&#160;&#160; &#160; &#160; &#160; &#160; &#160; &#160;&#160;&#160;&#160;(Attorney/Firm Name or Governmental Agency Name)&#160;</p>
                                <p style="position:absolute;top:918px;left:44px;white-space:nowrap;line-height: 14px;" class="ft111">10. &#160;Reason for release of information:&#160;<br/>&#160; &#160; &#160; &#160;&#160; <input type="checkbox" id="chk" name="chk"> At request of individual&#160;<br/>&#160; &#160; &#160; &#160;&#160; <input type="checkbox" id="chk" name="chk"> Other:&#160;</p>
                                <p style="position:absolute;top:918px;left:463px;white-space:nowrap" class="ft111">11. &#160;Date or event on which this authorization will expire:&#160;<br/>&#160;</p>
                                <p style="position:absolute;top:974px;left:44px;white-space:nowrap" class="ft14">12. &#160;If not the patient, name of person signing&#160;form:&#160;</p>
                                <p style="position:absolute;top:974px;left:463px;white-space:nowrap;line-height: 14px;" class="ft14">13. &#160;Authority to sign on behalf of patient:&#160;<br><b>{{$order->pharmacyname}}</b></p>
                                <p style="position:absolute;top:1015px;left:43px;white-space:nowrap" class="ft113">All items on this form have been&#160;completed and my questions about this&#160;form have been answered. In addition,&#160;I have been provided a&#160;<br/>copy of the&#160;form.&#160;&#160;</p>
                                <p style="position:absolute;top:1031px;left:205px;white-space:nowrap" class="ft19">&#160;</p>
                                <p style="position:absolute;top:1052px;left:43px;white-space:nowrap" class="ft19">&#160;</p>
                                <p style="position:absolute;top:1073px;left:43px;white-space:nowrap" class="ft14">&#160;&#160;_______ @if(!empty($order->signature_photo))<img src="{{$order->signature_photo}}" alt="Signature Photo" style="width:auto;height: 50px;left:83px;top:-20px;position:absolute;">@endif ______________________________&#160;</p>
                                <p style="position:absolute;top:1073px;left:421px;white-space:nowrap" class="ft14">&#160;</p>
                                <p style="position:absolute;top:1073px;left:475px;white-space:nowrap" class="ft14">Date:&#160;{{date('m/d/Y g:i A', strtotime($order->finish ?? ''))}}</p>
                                <p style="position:absolute;top:1091px;left:43px;white-space:nowrap" class="ft14">&#160; &#160;Signature of patient or&#160;representative authorized by law.&#160;&#160;</p>
                                <p style="position:absolute;top:1091px;left:421px;white-space:nowrap" class="ft14">&#160;</p>
                                <p style="position:absolute;top:1119px;left:43px;white-space:nowrap" class="ft110">&#160;</p>
                                <p style="position:absolute;top:1117px;left:46px;white-space:nowrap" class="ft10"><b>*&#160; &#160;Human Immunodeficiency Virus that causes AIDS. The New York State Public Health Law protects information which reasonably could&#160;</b></p>
                                <p style="position:absolute;top:1134px;left:63px;white-space:nowrap" class="ft10"><b>identify someone as having HIV symptoms or infection and information regarding a person’s contacts.&#160;</b></p>
                            </div>
                            <br/>
                            <style type="text/css">
                                p {margin: 0; padding: 0;}	.ft20{font-size:16px;font-family:Times;color:#000000;}
                                .ft21{font-size:18px;font-family:Times;color:#000000;}
                                .ft22{font-size:17px;font-family:Times;color:#000000;}
                                .ft23{font-size:16px;line-height:20px;font-family:Times;color:#000000;}
                                .ft24{font-size:17px;line-height:22px;font-family:Times;color:#000000;}
                            </style>
                            <div id="page2-div" style="position:relative;width:918px;height:1188px;">
                                @include('layouts.partials.document-image', ['document' => 'authorization_instructions', 'description' => 'Authorization instructions', 'imageStyle' => 'width: 918px; height: 1188px;'])
                                <p style="position:absolute;top:115px;left:108px;white-space:nowrap" class="ft20">&#160;</p>
                                <p style="position:absolute;top:113px;left:360px;white-space:nowrap" class="ft21">Instructions for the Use&#160;</p>
                                <p style="position:absolute;top:137px;left:108px;white-space:nowrap" class="ft21">&#160;</p>
                                <p style="position:absolute;top:137px;left:258px;white-space:nowrap" class="ft21">of the HIPAA-compliant Authorization Form to&#160;</p>
                                <p style="position:absolute;top:161px;left:108px;white-space:nowrap" class="ft21">&#160;</p>
                                <p style="position:absolute;top:161px;left:249px;white-space:nowrap" class="ft21">Release Health Information Needed for Litigation</p>
                                <p style="position:absolute;top:163px;left:669px;white-space:nowrap" class="ft20">&#160;</p>
                                <p style="position:absolute;top:184px;left:108px;white-space:nowrap" class="ft23">&#160;<br/>&#160;<br/>&#160;</p>
                                <p style="position:absolute;top:247px;left:108px;white-space:nowrap" class="ft22">&#160;</p>
                                <p style="position:absolute;top:247px;left:162px;white-space:nowrap" class="ft22">This form is the product of a collaborative process between the New York State&#160;</p>
                                <p style="position:absolute;top:269px;left:108px;white-space:nowrap" class="ft24">Office of Court Administration, representatives of the medical provider community in&#160;<br/>New York, and the bench and bar, designed to produce a standard official form that&#160;<br/>complies with the privacy requirements of the federal Health Insurance Portability and&#160;<br/>Accountability Act (“HIPAA”) and its implementing regulations, to be used to authorize&#160;<br/>the release of health information needed for litigation in New York State courts. &#160;It can,&#160;<br/>however, be used more broadly than this and be used before litigation has been&#160;<br/>commenced, or whenever counsel would find it useful.&#160;<br/>&#160;<br/>&#160;</p>
                                <p style="position:absolute;top:449px;left:162px;white-space:nowrap" class="ft22">The goal was to produce a standard HIPAA-compliant official form to obviate the&#160;</p>
                                <p style="position:absolute;top:471px;left:108px;white-space:nowrap" class="ft24">current disputes which often take place as to whether health information requests made in&#160;<br/>the course of litigation meet the requirements of the HIPAA&#160;Privacy Rule. &#160;It should be&#160;<br/>noted, though, that the form is optional. &#160;This form may be filled out on line and&#160;<br/>downloaded to be signed by hand, or downloaded and filled out entirely on paper.&#160;<br/>&#160;<br/>&#160;</p>
                                <p style="position:absolute;top:583px;left:162px;white-space:nowrap" class="ft22">When filing out Item 11, which requests the date or event when&#160;the authorization&#160;</p>
                                <p style="position:absolute;top:606px;left:108px;white-space:nowrap" class="ft24">will expire, the person filling out the form may designate an event such as “at the&#160;<br/>conclusion of my court case” or provide a specific date amount of time, such as “3 years&#160;<br/>from this date”.&#160;<br/>&#160;<br/>&#160;</p>
                                <p style="position:absolute;top:695px;left:162px;white-space:nowrap" class="ft22">If a patient seeks to authorize the release&#160;of his or her entire medical record, but&#160;</p>
                                <p style="position:absolute;top:718px;left:108px;white-space:nowrap" class="ft24">only from a certain date, the first two boxes in section 9(a) should both be checked, and&#160;<br/>the relevant date inserted on the first line containing the first box.&#160;</p>
                            </div>
                            <br><div class="row other-pages" style="page-break-after: always;"> </div>
                            <div class="row" style="margin-top: 70px">
                                <div class="col-2">
                                </div>
                                <div class="col-8" style="background: #fff; color: #000000;padding: 5px 0;font-weight: bold;text-align: center;">
                                    <h5 style="padding: 5px 0;font-weight: bold;text-align: center; margin: 10px 0px; font-family: Arial;">NEW YORK MOTOR VEHICLE NO-FAULT INSURANCE LAW<br>
                        ASSIGNMENT OF BENEFITS FORM</h5>
                                    <h5 style="padding: 5px 0;text-align: center; margin: 10px 0px; font-family: Arial;">(FOR ACCIDENTS OCCURRING ON AND AFTER 3/1/02)</h5>
                                </div>
                                <div class="col-2">
                                </div>
                            </div>
                            <div class="row" style="margin-top: 20px; padding: 0 80px;">
                                    <div class="col-12">
                                        <table width="100%" border="0">
                                                <tbody>
                                                    <tr height="12" style="height: 12px">
                                                    <td  height="12" style="width: 1%;white-space: nowrap;"><p align="center" style="color: #000000;line-height: 16px;font-size: 16px; font-weight: 700; font-family: Arial;">I, <span style="text-decoration: underline;width:1600px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{$order->last_name}} {{$order->username}}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><br>
                            <span style="font-size: 10px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(Print patient's name)</span></p>
                                                    </td>
                                                    <td style="width: 1%;white-space: nowrap;"><p  style="color: #000000;line-height: 16px;font-size: 16px;font-weight: 700; font-family: Arial;">, ("Assignor") hereby assign to<br>
                            <br>
                            </p></td>
                                                    <td style="width: 1%;white-space: nowrap;"><p  style="color: #000000;line-height: 16px;font-size: 16px; font-weight: 700; font-family: Arial;"><span style="text-decoration: underline;">&nbsp;{{$order->pharmacyname}}&nbsp;,</span><br>
                            <span style="font-size: 10px;">&nbsp;&nbsp;(Print hospital or health care provider name)</span> </p></td>
                                                    <td><p  style="color: #000000;line-height: 16px;font-size: 16px; font-weight: 700; font-family: Arial;"> ("Assignee")<br>
                            <br>
                            </p></td>
                                                    </tr>						
                                                </tbody>
                                                </table>

                                        <p  style="color: #000000;font-size: 16px;font-weight: 700; font-family: Arial;">
                                        all rights privileges and remedies to payment for health care services provided by assignee to which I am
                            entitled under Article 51 (the No-Fault statute) of the Insurance Law.</p>
                                        <p style="color: #000000;font-size: 15px; font-weight: 700; font-family: Arial;"> The Assignee hereby certifies that they have not received any payment from or on behalf of the Assignor and shall not pursue payment directly from the Assignor for services provided by said Assignee for injuries sustained due to
                                        the motor vehicle accident which occurred on ________________ ,not withstanding any other agreement to the contrary. <br>
                            <span style="font-size: 10px; margin-left: 270px; margin-top: -15px;">(Print accident date)</span></p>			  
                                        
                                        
                                        <p style="color: #000000;font-size: 15px; font-weight: 700; font-family: Arial;">This agreement may be revoked by the assignee when benefits are not payable based upon the assignor’s lack 
                            of coverage and/or violation of a policy condition due to the actions or conduct of the assignor. </p>
                                        <p style="color: #000000;font-size: 15px; font-weight: 700; font-family: Arial;">ANY PERSON WHO KNOWINGLY AND WITH INTENT TO DEFRAUD ANY INSURANCE COMPANY OR OTHER PERSON FILES AN APPLICATION FOR COMMERCIAL INSURANCE OR A STATEMENT OF CLAIM FOR ANY COMMERCIAL OR PERSONAL INSURANCE BENEFITS CONTAINING ANY MATERIALLY FALSE INFORMATION, OR CONCEALS FOR THE PURPOSE OF MISLEADING, INFORMATION CONCERNING ANY FACT MATERIAL THERETO, AND ANY PERSON WHO, IN CONNECTION WITH SUCH APPLICATION OR CLAIM, KNOWINGLY MAKES OR KNOWINGLY ASSISTS, ABETS, SOLICITS OR CONSPIRES WITH ANOTHER TO MAKE A FALSE REPORT OF THE THEFT, DESTRUCTION, DAMAGE OR CONVERSION OF ANY MOTOR VEHICLE TO A LAW ENFORCEMENT AGENCY, THE DEPARTMENT OF MOTOR VEHICLES OR AN INSURANCE COMPANY, COMMITS A FRAUDULENT INSURANCE ACT, WHICH IS A CRIME, AND SHALL ALSO BE SUBJECT TO A CIVIL PENALTY NOT TO EXCEED FIVE THOUSAND DOLLARS AND THE VALUE OF THE SUBJECT MOTOR VEHICLE OR STATED CLAIM FOR EACH VIOLATION.</p>		  
                                        
                                    </div>	  
                                </div>
                                <div class="row" style="margin-top: 70px">
                                    <table width="100%" border="0">
                                        <tbody>
                                            <tr>
                                            <td style="width: 10%;"><p align="center" style="color: #000000;line-height: 13px;font-size: 14px; font-weight: 700; font-family: Arial;"></p>
                                            </td>
                                            <td   style="width: 40%;text-align: center"><p align="center" style="color: #000000;line-height: 13px;font-size: 14px; font-weight: 700; font-family: Arial;">{{$order->last_name}} {{$order->username}}<br>
                                            <hr style="padding: 0 20px;margin: 0px 0 2px 0;">
                                                <span style="font-size: 11px; margin-top: -15px;">(Print patient's name)</span></p>
                                            </td>	
                                                <td style="width: 5%;"><p align="center" style="color: #000000;line-height: 13px;font-size: 14px; font-weight: 700; font-family: Arial;"></p>
                                                </td>	
                                            <td  style="width: 35%;text-align: center">
                                                @if(!empty($order->signature_photo))<img src="{{$order->signature_photo}}" alt="Signature Photo" style="max-height: 80px;width: auto;height: auto;max-width: 150px;left:755px;bottom:316px;position: absolute">@endif
                                                <p align="center" style="color: #000000;line-height: 13px;font-size: 16px; font-weight: 700; font-family: Arial;"><br>
                                                <hr style="padding: 0 20px;margin: 0px 0 2px 0;">
                                                <span style="font-size: 11px;">(Signature of Patient)</span></p>
                                            </td>
                                            <td  style="width: 10%;"><p align="center" style="color: #000000;line-height: 13px;font-size: 16px; font-weight: 700; font-family: Arial;"></p>
                                            </td>						
                                            </tr>
                                            <tr>
                                            <td><p align="center" style="color: #000000;line-height: 13px;font-size: 16px; font-weight: 700; font-family: Arial;"></p>
                                            </td>
                                            <td style="text-align: center;"><p align="center" style="color: #000000;line-height: 11px;font-size: 12px; font-weight: 700; font-family: Arial;">{{$order->useraddress}}, {{$order->userzip}}<br>
                                                <hr style="padding: 0 20px;margin: 0px 0 2px 0;">
                                                <span style="font-size: 11px;">(Address of Patient)</span></p>
                                            </td>	
                                                <td><p align="center" style="color: #000000;line-height: 13px;font-size: 16px; font-weight: 700; font-family: Arial;"></p>
                                                </td>	
                                            <td style="text-align: center">
                                                                        
                                                <p align="center" style="color: #000000;line-height: 13px;font-size: 14px; font-weight: 700; font-family: Arial;">@if(!empty($order->finish)){{date('m.d.Y', strtotime($order->finish ?? ''))}}@endif<br>
                                                <hr style="padding: 0 20px;margin: 0px 0 2px 0;"> 
                                                <span style="font-size: 11px;">(Date of signature)</span></p>
                                            </td>
                                            <td><p align="center" style="color: #000000;line-height: 13px;font-size: 16px; font-weight: 700; font-family: Arial;"></p>
                                            </td>						
                                            </tr>
                                            <tr>
                                            <td><p align="center" style="color: #000000;line-height: 13px;font-size: 16px; font-weight: 700; font-family: Arial;"></p>
                                            </td>
                                            <td style="text-align: center;">
                                            </td>	
                                                <td><p align="center" style="color: #000000;line-height: 13px;font-size: 16px; font-weight: 700; font-family: Arial;"></p>
                                                </td>	
                                            <td style="text-align: center">							 						 
                                                
                                            </td>
                                            <td><p align="center" style="color: #000000;line-height: 13px;font-size: 16px; font-weight: 700; font-family: Arial;"></p>
                                            </td>						
                                            </tr>
                                            <tr>
                                            <td><p align="center" style="color: #000000;line-height: 13px;font-size: 16px; font-weight: 700; font-family: Arial;"></p>
                                            </td>
                                            <td style="text-align: center;"><p align="center" style="color: #000000;line-height: 13px;font-size: 14px; font-weight: 700; font-family: Arial;">{{$order->pharmacyname}}<br>
                                            <hr style="padding: 0 20px;margin: 0px 0 2px 0;">
                                                <span style="font-size: 11px;font-weight: bold;">(Print name of Provider)</span></p>
                                            </td>	
                                                <td><p align="center" style="color: #000000;line-height: 13px;font-size: 16px; font-weight: 700; font-family: Arial;"></p>
                                                </td>	
                                            <td style="text-align: center">
                                                <p align="center" style="color: #000000;line-height: 14px;font-size: 14px; font-weight: 700; font-family: Arial;">&nbsp;<br>
                                                <hr style="padding: 0 20px;margin: 0px 0 2px 0;"> 
                                                <span style="font-size: 11px;">(Signature of Provider)</span></p>
                                            </td>
                                            <td><p align="center" style="color: #000000;line-height: 13px;font-size: 16px; font-weight: 700; font-family: Arial;"></p>
                                            </td>						
                                            </tr>
                                            <tr>
                                            <td><p align="center" style="color: #000000;line-height: 13px;font-size: 16px; font-weight: 700; font-family: Arial;"></p>
                                            </td>
                                            <td style="text-align: center;"><p align="center" style="color: #000000;line-height: 11px;font-size: 12px; font-weight: 700; font-family: Arial;">{{$order->pharmacyaddress}}<br>
                                            <hr style="padding: 0 20px;margin: 0px 0 2px 0;">
                                                <span style="font-size: 11px;">(Address of Provider)</span></p>
                                            </td>	
                                                <td><p align="center" style="color: #000000;line-height: 13px;font-size: 16px; font-weight: 700; font-family: Arial;"></p>
                                                </td>	
                                            <td style="text-align: center">
                                                                        
                                                <p align="center" style="color: #000000;line-height: 14px;font-size: 14px; font-weight: 700; font-family: Arial;">&nbsp;<br>
                                                <hr style="padding: 0 20px;margin: 0px 0 2px 0;"> 
                                                <span style="font-size: 11px;">(Date of signature)</span></p>
                                            </td>
                                            <td><p align="center" style="color: #000000;line-height: 13px;font-size: 16px; font-weight: 700; font-family: Arial;"></p>
                                            </td>						
                                            </tr>
                                        </tbody>
                                    </table>
                            </div>
                            <div class="row" style="margin-top: 180px; padding: 0 80px;">
                                <div class="col-12">
                                    <p  style="color: #000000;font-size: 14px;font-family: Arial;">
                                    NYS FORM NF-AOB (Rev 1/2004)</p>
                            </div>
                            </div>
						</div>
                        <div style="display:none;" id="finish-print">
                            <div class="row">
                            <div class="col-3">
                            </div>
                            <div class="col-6" style="color: #000000;padding: 5px 0;text-align: center;">
                                <h1 style="color: #000000;padding: 5px 0;text-align: center; margin: 30px 0 0 0;font-size: 20px;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">{{$order->pharmacyname}}</h1 >
                                <h2 style="margin: 10px 0 0 0;font-size: 16px;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">Address: {{$order->pharmacyaddress}}</h2>
                                <h2 style="margin: 10px 0 0 0;font-size: 16px;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">Phone: {{$order->pharmacyphone}}</h2>                    
                            </div>
                            <div class="col-3">
                            </div>
                            <div class="col-5">
                            </div>
                            <div class="col-2" style="color: #000000;padding: 5px 8px 0 8px;font-weight: bold;text-align: center; border: 2px solid #000; margin: 15px 0 0 0;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;"><h3>Delivery Slip</h3></div>
                            <div class="col-5"></div>
                            </div>
                            <table width="100%" border="0">
                                <tbody>
                                    <tr style="border-bottom: 2px solid #000;">
                                    <td>&nbsp;</td>
                                    </tr>
                            </tbody>
                            </table>
                            <div class="row" style="margin: 30px 20px;">
                                <div class="col-6" style="color: #000000;line-height:27px;"font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;>
                                    <span style="color: #000000; font-size: 17px; text-decoration: underline;font-weight: bold;text-transform: uppercase;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">Client:</span><br>	
                                    <span style="font-size: 15px;margin-top: 15pxfont-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">{{$order->last_name}} {{$order->username}}</span><br>
                                    <span style="font-size: 15px;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">{{$order->useraddress}} {{$order->userzip}} / {{$order->userapartment}}</span><br>
                                    <span style="font-size: 15px;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">Ph#: {{$order->userphone}}</span><br>
                                </div>
                                <div class="col-6" style="color: #000000;line-height: 30px;">			
                                </div>
                                <br><br>
                                <div class="col-12" style="color: #000000;line-height: 30px;border-bottom: 1px solid #000;border-top: 1px solid #000;margin: 15px 0;padding: 5px 0 5px 0;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">
                                        <table width="100%" border="0" style="color: #000000;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">
                                        <tbody>
                                            <tr bgcolor="#E3E3E3" style="font-weight: bold">
                                            <td bgcolor="#E3E3E3" width="100" align="center">Date</td>
                                            <td bgcolor="#E3E3E3" width="150" align="center">RX#</td>
                                            <td bgcolor="#E3E3E3" width="150" align="center">RF#</td>
                                            <td bgcolor="#E3E3E3" width="250" align="center">RX Barcode</td>
                                            <td bgcolor="#E3E3E3" width="250" align="center">Qty</td>
                                            </tr>
                                            @foreach($rxs as $key=>$rx)
                                            <tr>
                                            <td align="center">{{ $rx->rx_date }}</td>
                                            <td align="center">{{explode('-',$rx->rx_id)[0]}}</td>
                                            <td align="center">@if(count(explode('-',$rx->rx_id))>1){{explode('-',$rx->rx_id)[1]}}@endif</td>
                                            <td align="center">@if(!empty($rx->rx_id))<img style="margin-bottom:10px;" src="data:image/png;base64,{{ DNS1D::getBarcodePNG($rx->rx_id, 'C128',1.5,24) }}" alt="barcode"/>@endif</td>
                                            <td align="center">{{ $rx->rx_count }}</td>    
                                            </tr>
                                            @endforeach 
                                        </tbody>
                                        </table>			
                                </div>
                            </div>
                            <div class="row" style="margin: 30px 20px;">	
                                <div class="col-8" style="color: #000000;margin: 15px 0;font-size: 16px;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">		
                                Total Rx Count: {{count($rxs)}}<br>
                                Patient is requesting Counseling: &nbsp;&nbsp;&nbsp;&nbsp; Yes <span style="border-bottom: 1px solid #000;">_____ </span> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; No <span style="border-bottom: 1px solid #000;">______ </span>
                                </div>
                                <div class="col-4" style="color: #000000;margin: 15px 0;font-size: 16px;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">		
                                ${{$order->copay}}<br>                            
                                </div>
                                <div class="col-12" style="font-size: 16px;color: #000000;margin: 15px 0;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">		
                                    I certify that I requested and received my medication listed above from {{$order->pharmacyname}} located at {{$order->pharmacyaddress}} (the “Pharmacy”). I further certify that I had a patient relationship with the
                                    ordering medical provider indicated on the prescription label and that I requested that the prescriber send this
                                    prescription to the Pharmacy. The foregoing is certified as true and accurate under the penalty of perjury.
                                </div>                       

                                <div class="col-6" style="font-size: 16px;color: #000000;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;margin-top: 20px;">
                                Receiver'ce Name: <b>{{$order->last_name}} {{$order->username}}</b><br><br>
                                Receiver'ce Signature: 	@if(!empty($order->signature_photo))<img src="{{$order->signature_photo}}" alt="Signature Photo" style="width:auto;height: 120px;margin: 0px;position: absolute; left: 175px; top: 28px;">@endif
                                
                                </div>

                                <div class="col-6" style="font-size: 16px;color: #000000;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;margin-top: 20px;">
                                Date/Time: <b>{{date('m.d.Y g:i A', strtotime($order->finish ?? ''))}}</b><br><br>
                                Delivered By <b style="text-transform: capitalize;">@if(!empty($driver)){{ $driver->name }} {{ $driver->last_name }}@endif</b>
                                </div>
                                
                            </div>
                        </div>
                        @if($order->facility && $order->statuse_id==4)
                        @foreach($additional_recipients as $additional_recipient)
                        <div style="display:none;" id="finish-print{{$additional_recipient->id}}" style="padding: 10px 40px;">
                            <div class="row">
                            <div class="col-3">
                            </div>
                            <div class="col-6" style="color: #000000;padding: 5px 0;text-align: center;">
                                <h1 style="color: #000000;padding: 5px 0;text-align: center; margin: 30px 0 0 0;font-size: 20px;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">{{$order->pharmacyname}}</h1 >
                                <h2 style="margin: 10px 0 0 0;font-size: 16px;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">Address: {{$order->pharmacyaddress}}</h2>
                                <h2 style="margin: 10px 0 0 0;font-size: 16px;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">Phone: {{$order->pharmacyphone}}</h2>                    
                            </div>
                            <div class="col-3">
                            </div>
                            <div class="col-5">
                            </div>
                            <div class="col-2" style="color: #000000;padding: 5px 8px 0 8px;font-weight: bold;text-align: center; border: 2px solid #000; margin: 15px 0 0 0;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;"><h3>Delivery Slip</h3></div>
                            <div class="col-5"></div>
                            </div>
                            <table width="100%" border="0">
                                <tbody>
                                    <tr style="border-bottom: 2px solid #000;">
                                    <td>&nbsp;</td>
                                    </tr>
                            </tbody>
                            </table>
                            <div class="row" style="margin: 30px 20px;">
                                <div class="col-6" style="color: #000000;line-height:27px;"font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;>
                                    <span style="color: #000000; font-size: 17px; text-decoration: underline;font-weight: bold;text-transform: uppercase;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">Client:</span><br>	
                                    <span style="font-size: 15px;margin-top: 15pxfont-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">{{$additional_recipient->family_name}}</span><br>
                                    <span style="font-size: 15px;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">{{$order->useraddress}} {{$order->userzip}} / {{$order->userapartment}}</span><br>
                                    <span style="font-size: 15px;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">Ph#: {{$additional_recipient->family_phone}}</span><br>
                                </div>
                                <div class="col-6" style="color: #000000;line-height: 30px;">			
                                </div>
                                <br><br>
                                <div class="col-12" style="color: #000000;line-height: 30px;border-bottom: 1px solid #000;border-top: 1px solid #000;margin: 15px 0;padding: 5px 0 5px 0;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">
                                        <table width="100%" border="0" style="color: #000000;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">
                                        <tbody>
                                            <tr bgcolor="#E3E3E3" style="font-weight: bold">
                                            <td bgcolor="#E3E3E3" width="100" align="center">Date</td>
                                            <td bgcolor="#E3E3E3" width="150" align="center">RX#</td>
                                            <td bgcolor="#E3E3E3" width="150" align="center">RF#</td>
                                            <td bgcolor="#E3E3E3" width="250" align="center">RX Barcode</td>
                                            <td bgcolor="#E3E3E3" width="250" align="center">Qty</td>
                                            </tr>
                                            @php
                                            $rx_recipient_count=0;
                                            @endphp
                                            @foreach($rxs as $key=>$rx)
                                            @if($rx->rx_recipient===$additional_recipient->id)
                                            @php
                                            $rx_recipient_count++;
                                            @endphp
                                            <tr>
                                                <td align="center">{{ $rx->rx_date }}</td>
                                                <td align="center">{{explode('-',$rx->rx_id)[0]}}</td>
                                                <td align="center">@if(count(explode('-',$rx->rx_id))>1){{explode('-',$rx->rx_id)[1]}}@endif</td>
                                                <td align="center"><img style="margin-bottom:10px;" src="data:image/png;base64,{{ DNS1D::getBarcodePNG($rx->rx_id, 'C128',1.5,24) }}" alt="barcode"/></td>
                                                <td align="center">{{ $rx->rx_count }}</td>    
                                            </tr>
                                            @endif
                                            @endforeach 
                                        </tbody>
                                        </table>			
                                </div>
                            </div>
                            <div class="row" style="margin: 30px 20px;">	
                                <div class="col-8" style="color: #000000;margin: 15px 0;font-size: 16px;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">		
                                Total Rx Count: {{($rx_recipient_count)}}<br>
                                Patient is requesting Counseling: &nbsp;&nbsp;&nbsp;&nbsp; Yes <span style="border-bottom: 1px solid #000;">_____ </span> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; No <span style="border-bottom: 1px solid #000;">______ </span>
                                </div>
                                <div class="col-4" style="color: #000000;margin: 15px 0;font-size: 16px;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">		
                                ${{$order->copay}}<br>                            
                                </div>
                                <div class="col-12" style="font-size: 16px;color: #000000;margin: 15px 0;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">		
                                    I certify that I requested and received my medication listed above from {{$order->pharmacyname}} located at {{$order->pharmacyaddress}} (the “Pharmacy”). I further certify that I had a patient relationship with the
                                    ordering medical provider indicated on the prescription label and that I requested that the prescriber send this
                                    prescription to the Pharmacy. The foregoing is certified as true and accurate under the penalty of perjury.
                                </div>                       

                                <div class="col-6" style="font-size: 16px;color: #000000;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;margin-top: 20px;">
                                Receiver'ce Name: <b>{{$additional_recipient->family_name}}</b><br><br>
                                Receiver'ce Signature: 	@if(!empty($order->signature_photo))<img src="{{$order->signature_photo}}" alt="Signature Photo" style="width:auto;height: 120px;margin: 0px;position: absolute; left: 175px; top: 28px;">@endif
                                
                                </div>

                                <div class="col-6" style="font-size: 16px;color: #000000;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;margin-top: 20px;">
                                Date/Time: <b>{{date('m.d.Y g:i A', strtotime($order->finish ?? ''))}}</b><br><br>
                                Delivered By <b style="text-transform: capitalize;">{{ $driver->name }} {{ $driver->last_name }}</b>
                                </div>
                                
                            </div>
                        </div>
                        @endforeach
                        @endif


                                    <div style="display:none;" id="HIPAA">
                                    <style type="text/css">

	p {margin: 0; padding: 0;}	.ft10{font-size:11px;font-family:Times;color:#000000;}
	.ft11{font-size:15px;font-family:Times;color:#000000;}
	.ft12{font-size:13px;font-family:Times;color:#000000;}
	.ft13{font-size:8px;font-family:Times;color:#000000;}
	.ft14{font-size:13px;font-family:Times;color:#000000;}
	.ft15{font-size:14px;font-family:Times;color:#000000;}
	.ft16{font-size:-1px;font-family:Times;color:#000000;}
	.ft17{font-size:13px;font-family:Times;color:#000000;}
	.ft18{font-size:11px;font-family:Times;color:#000000;}
	.ft19{font-size:16px;font-family:Times;color:#000000;}
	.ft110{font-size:9px;font-family:Times;color:#000000;}
	.ft111{font-size:13px;line-height:18px;font-family:Times;color:#000000;}
	.ft112{font-size:13px;line-height:18px;font-family:Times;color:#000000;}
	.ft113{font-size:13px;line-height:17px;font-family:Times;color:#000000;}

</style>

<div id="page1-div" style="position:relative;width:918px;height:1188px;">
@include('layouts.partials.document-image', ['document' => 'authorization', 'description' => 'Authorization form', 'imageStyle' => 'width: 918px; height: 1188px;'])
<p style="position:absolute;top:36px;left:43px;white-space:nowrap" class="ft10"><b>&#160;</b></p>
<p style="position:absolute;top:36px;left:97px;white-space:nowrap" class="ft10"><b>&#160;</b></p>
<p style="position:absolute;top:36px;left:151px;white-space:nowrap" class="ft10"><b>&#160;</b></p>
<p style="position:absolute;top:36px;left:205px;white-space:nowrap" class="ft10"><b>&#160;</b></p>
<p style="position:absolute;top:36px;left:259px;white-space:nowrap" class="ft10"><b>&#160;</b></p>
<p style="position:absolute;top:36px;left:313px;white-space:nowrap" class="ft10"><b>&#160;</b></p>
<p style="position:absolute;top:36px;left:367px;white-space:nowrap" class="ft10"><b>&#160;</b></p>
<p style="position:absolute;top:36px;left:421px;white-space:nowrap" class="ft10"><b>&#160;</b></p>
<p style="position:absolute;top:36px;left:475px;white-space:nowrap" class="ft10"><b>&#160;&#160;</b></p>
<p style="position:absolute;top:36px;left:529px;white-space:nowrap" class="ft10"><b>&#160;</b></p>
<p style="position:absolute;top:36px;left:583px;white-space:nowrap" class="ft10"><b>&#160;</b></p>
<p style="position:absolute;top:36px;left:637px;white-space:nowrap" class="ft10"><b>&#160; &#160; &#160; &#160;&#160; &#160;&#160;&#160;&#160; &#160; &#160;&#160;&#160;OCA Official Form No.:&#160;960&#160;</b></p>
<p style="position:absolute;top:53px;left:107px;white-space:nowrap" class="ft11"><b>AUTHORIZATION FOR RELEASE OF HEALTH INFORMATION PURSUANT TO HIPAA</b></p>
<p style="position:absolute;top:55px;left:811px;white-space:nowrap" class="ft12"><b>&#160;</b></p>
<p style="position:absolute;top:74px;left:212px;white-space:nowrap" class="ft12"><b>[This form has been approved by the New York State Department of Health]&#160;</b></p>
<p style="position:absolute;top:90px;left:459px;white-space:nowrap" class="ft13"><b>&#160;</b></p>
<p style="position:absolute;top:104px;left:51px;white-space:nowrap;line-height: 15px;" class="ft14">Patient&#160;Name&#160; <br>
{{$order->last_name}} {{$order->username}}</p>
<p style="position:absolute;top:104px;left:503px;white-space:nowrap" class="ft14">Date of Birth&#160;</p>
<p style="position:absolute;top:104px;left:699px;white-space:nowrap" class="ft14">Social Security Number&#160;&#160;</p>
<p style="position:absolute;top:148px;left:51px;white-space:nowrap;line-height: 15px;" class="ft14">Patient Address<br>
{{$order->useraddress}}, {{$order->userzip}}</p>
<p style="position:absolute;top:147px;left:147px;white-space:nowrap" class="ft15">&#160;</p>
<p style="position:absolute;top:202px;left:43px;white-space:nowrap" class="ft14">I, or my authorized representative, request that health information regarding my care and treatment be released as set forth on this form:&#160;</p>
<p style="position:absolute;top:224px;left:43px;white-space:nowrap" class="ft112">In accordance with New York State Law and the Privacy Rule of the Health Insurance Portability and Accountability Act&#160;of 1996&#160;<br/>(HIPAA),&#160;&#160;&#160;I understand that:&#160;<br/>1.&#160;&#160;This authorization may include disclosure of information relating to&#160;&#160;<b>ALCOHOL</b>&#160;and&#160;&#160;<b>DRUG ABUSE, MENTAL HEALTH&#160;<br/>TREATMENT</b>, except psychotherapy notes, and&#160;<b>CONFIDENTIAL HIV* RELATED INFORMATION</b>&#160;only if I place my initials on&#160;<br/>the appropriate line in Item 9(a). &#160;In the event the health information described below includes any of these types of information, and I&#160;<br/>initial the line on the box in Item 9(a), I specifically authorize release of such information to the person(s) indicated&#160;in Item 8.&#160;<br/>2.&#160;&#160;If I am authorizing the release of HIV-related, alcohol or drug treatment, or mental health treatment information, the recipient is&#160;<br/>prohibited from redisclosing such information without my authorization unless permitted to do so under federal or state law. &#160;I&#160;<br/>understand that I have the right to request a list of people who may receive or use my HIV-related information without authorization. &#160;If&#160;<br/>I experience discrimination because of the release or disclosure of HIV-related information, I may contact the New York State Division&#160;<br/>of Human Rights at (212) 480-2493 or the New York City Commission of Human Rights at (212) 306-7450. &#160;These agencies are&#160;<br/>responsible for protecting my rights.&#160;<br/>3.&#160;&#160;I have the right to revoke this authorization at any time by writing to the health care provider listed below. &#160;I understand that I may&#160;<br/>revoke this authorization except to the extent that action has already been taken based on this authorization.&#160;<br/>4.&#160;&#160;I understand that signing this authorization is voluntary. My treatment, payment, enrollment in a health plan, or eligibility for&#160;<br/>benefits will not be conditioned upon my authorization of this disclosure.&#160;&#160;<br/>5.&#160;&#160;Information disclosed under this authorization might be redisclosed by the recipient (except as noted&#160;&#160;above&#160;in Item&#160;2), and this&#160;<br/>redisclosure may no longer be protected by federal or state law.&#160;<br/>6.&#160;&#160;<b>THIS AUTHORIZATION DOES NOT AUTHORIZE YOU TO DISCUSS MY HEALTH INFORMATION OR MEDICAL&#160;<br/>CARE WITH ANYONE OTHER THAN THE ATTORNEY OR GOVERNMENTAL AGENCY SPECIFIED IN&#160;ITEM 9&#160;(b).&#160;</b></p>
<p style="position:absolute;top:586px;left:44px;white-space:nowrap;line-height: 15px;" class="ft14">7. Name and address of health provider or entity to release this information:&#160;<br><b>{{$order->pharmacyname}}</b> - {{$order->pharmacyaddress}}</p>
<p style="position:absolute;top:625px;left:44px;white-space:nowrap" class="ft14">8. Name and address of person(s) or category of person to whom this information will be sent:</p>
<p style="position:absolute;top:634px;left:451px;white-space:nowrap" class="ft16">&#160;</p>

<p style="position:absolute;top:643px;left:44px;white-space:nowrap" class="ft14">&#160;</p>
<p style="position:absolute;top:664px;left:44px;white-space:nowrap" class="ft111">9(a). &#160;Specific information to be released:&#160;<br/>&#160; &#160; &#160; &#160;&#160;&#160; <input type="checkbox" id="chk" name="chk"> Medical Record from (insert date)&#160;<b>___________________</b>&#160;to (insert date)&#160;<b>___________________</b>&#160;&#160;<br/>&#160; &#160; &#160; &#160;&#160;&#160; <input type="checkbox" id="chk" name="chk"> Entire Medical Record, including patient histories, office notes (except psychotherapy notes), test results, radiology studies, films,&#160;<br/>&#160; &#160; &#160; &#160; &#160; &#160; &#160;referrals, consults, billing records, insurance records, and records sent to you by other health care providers.&#160;&#160;</p>
<p style="position:absolute;top:740px;left:44px;white-space:nowrap" class="ft14">&#160; &#160; &#160;&#160;&#160;&#160;&#160; <input type="checkbox" id="chk" name="chk"> Other: &#160;<b>__________________________________</b>&#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160;&#160; &#160;Include:&#160;(<i>Indicate by Initialing</i>)<b>&#160;</b></p>
<p style="position:absolute;top:763px;left:44px;white-space:nowrap" class="ft14">&#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160;&#160;&#160;&#160;&#160;&#160;<b>__________________________________</b>&#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160;&#160;&#160;&#160;&#160;&#160; &#160; &#160; &#160;&#160;&#160;&#160;<b>________</b>&#160;<b>Alcohol/Drug Treatment&#160;</b></p>
<p style="position:absolute;top:783px;left:44px;white-space:nowrap" class="ft14">&#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160;&#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160;&#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;<b>________</b>&#160;<b>Mental Health Information&#160;</b></p>
<p style="position:absolute;top:804px;left:44px;white-space:nowrap" class="ft12"><b>Authorization to Discuss Health Information</b>&#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160;&#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160;&#160; &#160; &#160;&#160;&#160;<b>________</b>&#160;<b>HIV-Related Information&#160;</b></p>
<p style="position:absolute;top:831px;left:50px;white-space:nowrap;line-height: 12px;" class="ft14">&#160;(b)&#160;&#160;<input type="checkbox" id="chk" name="chk" checked> By initialing here <span style="text-decoration: underline;">&nbsp;&nbsp;&nbsp;{{mb_substr($order->username,0,1,'UTF-8')}}.&nbsp; {{mb_substr($order->last_name,0,1,'UTF-8')}}.&nbsp;&nbsp;&nbsp;</span> I authorize <span style="text-decoration: underline;">{{$order->pharmacyname}}</span>_______________________________________&#160;</p>
<p style="position:absolute;top:849px;left:50px;white-space:nowrap" class="ft18">&#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160;&#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160;&#160; &#160; &#160; &#160; &#160; &#160;&#160;Initials&#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160;&#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160;&#160;&#160;&#160;Name of individual health care provider&#160;</p>
<p style="position:absolute;top:865px;left:44px;white-space:nowrap" class="ft111">&#160; &#160; &#160; &#160; &#160;to&#160;discuss my health information with my attorney, or a governmental agency, listed here:&#160;<br/>&#160;&#160;&#160; &#160; &#160;&#160;&#160;<b>______________________________________________________________________________________________________&#160;</b></p>
<p style="position:absolute;top:895px;left:44px;white-space:nowrap" class="ft18">&#160;&#160;&#160;&#160;&#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160;&#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160; &#160;&#160;&#160;&#160; &#160; &#160; &#160; &#160; &#160; &#160;&#160;&#160;&#160;(Attorney/Firm Name or Governmental Agency Name)&#160;</p>
<p style="position:absolute;top:918px;left:44px;white-space:nowrap;line-height: 14px;" class="ft111">10. &#160;Reason for release of information:&#160;<br/>&#160; &#160; &#160; &#160;&#160; <input type="checkbox" id="chk" name="chk"> At request of individual&#160;<br/>&#160; &#160; &#160; &#160;&#160; <input type="checkbox" id="chk" name="chk"> Other:&#160;</p>
<p style="position:absolute;top:918px;left:463px;white-space:nowrap" class="ft111">11. &#160;Date or event on which this authorization will expire:&#160;<br/>&#160;</p>
<p style="position:absolute;top:974px;left:44px;white-space:nowrap" class="ft14">12. &#160;If not the patient, name of person signing&#160;form:&#160;</p>
<p style="position:absolute;top:974px;left:463px;white-space:nowrap;line-height: 14px;" class="ft14">13. &#160;Authority to sign on behalf of patient:&#160;<br><b>{{$order->pharmacyname}}</b></p>
<p style="position:absolute;top:1015px;left:43px;white-space:nowrap" class="ft113">All items on this form have been&#160;completed and my questions about this&#160;form have been answered. In addition,&#160;I have been provided a&#160;<br/>copy of the&#160;form.&#160;&#160;</p>
<p style="position:absolute;top:1031px;left:205px;white-space:nowrap" class="ft19">&#160;</p>
<p style="position:absolute;top:1052px;left:43px;white-space:nowrap" class="ft19">&#160;</p>
<p style="position:absolute;top:1073px;left:43px;white-space:nowrap" class="ft14">&#160;&#160;_______ @if(!empty($order->signature_photo))<img src="{{$order->signature_photo}}" alt="Signature Photo" style="width:auto;height: 50px;left:83px;top:-20px;position:absolute;">@endif ______________________________&#160;</p>
<p style="position:absolute;top:1073px;left:421px;white-space:nowrap" class="ft14">&#160;</p>
<p style="position:absolute;top:1073px;left:475px;white-space:nowrap" class="ft14">Date:&#160;{{date('m/d/Y g:i A', strtotime($order->finish ?? ''))}}</p>
<p style="position:absolute;top:1091px;left:43px;white-space:nowrap" class="ft14">&#160; &#160;Signature of patient or&#160;representative authorized by law.&#160;&#160;</p>
<p style="position:absolute;top:1091px;left:421px;white-space:nowrap" class="ft14">&#160;</p>
<p style="position:absolute;top:1119px;left:43px;white-space:nowrap" class="ft110">&#160;</p>
<p style="position:absolute;top:1117px;left:46px;white-space:nowrap" class="ft10"><b>*&#160; &#160;Human Immunodeficiency Virus that causes AIDS. The New York State Public Health Law protects information which reasonably could&#160;</b></p>
<p style="position:absolute;top:1134px;left:63px;white-space:nowrap" class="ft10"><b>identify someone as having HIV symptoms or infection and information regarding a person’s contacts.&#160;</b></p>
</div>
 <br/>
<style type="text/css">

	p {margin: 0; padding: 0;}	.ft20{font-size:16px;font-family:Times;color:#000000;}
	.ft21{font-size:18px;font-family:Times;color:#000000;}
	.ft22{font-size:17px;font-family:Times;color:#000000;}
	.ft23{font-size:16px;line-height:20px;font-family:Times;color:#000000;}
	.ft24{font-size:17px;line-height:22px;font-family:Times;color:#000000;}

</style>

<div id="page2-div" style="position:relative;width:918px;height:1188px;">
@include('layouts.partials.document-image', ['document' => 'authorization_instructions', 'description' => 'Authorization instructions', 'imageStyle' => 'width: 918px; height: 1188px;'])
<p style="position:absolute;top:115px;left:108px;white-space:nowrap" class="ft20">&#160;</p>
<p style="position:absolute;top:113px;left:360px;white-space:nowrap" class="ft21">Instructions for the Use&#160;</p>
<p style="position:absolute;top:137px;left:108px;white-space:nowrap" class="ft21">&#160;</p>
<p style="position:absolute;top:137px;left:258px;white-space:nowrap" class="ft21">of the HIPAA-compliant Authorization Form to&#160;</p>
<p style="position:absolute;top:161px;left:108px;white-space:nowrap" class="ft21">&#160;</p>
<p style="position:absolute;top:161px;left:249px;white-space:nowrap" class="ft21">Release Health Information Needed for Litigation</p>
<p style="position:absolute;top:163px;left:669px;white-space:nowrap" class="ft20">&#160;</p>
<p style="position:absolute;top:184px;left:108px;white-space:nowrap" class="ft23">&#160;<br/>&#160;<br/>&#160;</p>
<p style="position:absolute;top:247px;left:108px;white-space:nowrap" class="ft22">&#160;</p>
<p style="position:absolute;top:247px;left:162px;white-space:nowrap" class="ft22">This form is the product of a collaborative process between the New York State&#160;</p>
<p style="position:absolute;top:269px;left:108px;white-space:nowrap" class="ft24">Office of Court Administration, representatives of the medical provider community in&#160;<br/>New York, and the bench and bar, designed to produce a standard official form that&#160;<br/>complies with the privacy requirements of the federal Health Insurance Portability and&#160;<br/>Accountability Act (“HIPAA”) and its implementing regulations, to be used to authorize&#160;<br/>the release of health information needed for litigation in New York State courts. &#160;It can,&#160;<br/>however, be used more broadly than this and be used before litigation has been&#160;<br/>commenced, or whenever counsel would find it useful.&#160;<br/>&#160;<br/>&#160;</p>
<p style="position:absolute;top:449px;left:162px;white-space:nowrap" class="ft22">The goal was to produce a standard HIPAA-compliant official form to obviate the&#160;</p>
<p style="position:absolute;top:471px;left:108px;white-space:nowrap" class="ft24">current disputes which often take place as to whether health information requests made in&#160;<br/>the course of litigation meet the requirements of the HIPAA&#160;Privacy Rule. &#160;It should be&#160;<br/>noted, though, that the form is optional. &#160;This form may be filled out on line and&#160;<br/>downloaded to be signed by hand, or downloaded and filled out entirely on paper.&#160;<br/>&#160;<br/>&#160;</p>
<p style="position:absolute;top:583px;left:162px;white-space:nowrap" class="ft22">When filing out Item 11, which requests the date or event when&#160;the authorization&#160;</p>
<p style="position:absolute;top:606px;left:108px;white-space:nowrap" class="ft24">will expire, the person filling out the form may designate an event such as “at the&#160;<br/>conclusion of my court case” or provide a specific date amount of time, such as “3 years&#160;<br/>from this date”.&#160;<br/>&#160;<br/>&#160;</p>
<p style="position:absolute;top:695px;left:162px;white-space:nowrap" class="ft22">If a patient seeks to authorize the release&#160;of his or her entire medical record, but&#160;</p>
<p style="position:absolute;top:718px;left:108px;white-space:nowrap" class="ft24">only from a certain date, the first two boxes in section 9(a) should both be checked, and&#160;<br/>the relevant date inserted on the first line containing the first box.&#160;</p>
</div>	</div>	 
								 
			
                                    
                                    
                                    <div style="display:none;" id="AOB">
                                    <div class="row" style="margin-top: 70px">
		  	<div class="col-2">
		  	</div>
			<div class="col-8" style="background: #fff; color: #000000;padding: 5px 0;font-weight: bold;text-align: center;">
				<h5 style="padding: 5px 0;font-weight: bold;text-align: center; margin: 10px 0px; font-family: Arial;">NEW YORK MOTOR VEHICLE NO-FAULT INSURANCE LAW<br>
	ASSIGNMENT OF BENEFITS FORM</h5>
				<h5 style="padding: 5px 0;text-align: center; margin: 10px 0px; font-family: Arial;">(FOR ACCIDENTS OCCURRING ON AND AFTER 3/1/02)</h5>
			</div>
			<div class="col-2">
			</div>
	 </div>
  <div class="row" style="margin-top: 20px; padding: 0 80px;">
		  <div class="col-12">
			<table width="100%" border="0">
					  <tbody>
						<tr height="12" style="height: 12px">
						  <td  height="12" style="width: 1%;white-space: nowrap;"><p align="center" style="color: #000000;line-height: 16px;font-size: 16px; font-weight: 700; font-family: Arial;">I, <span style="text-decoration: underline;width:1600px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{$order->last_name}} {{$order->username}}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><br>
<span style="font-size: 10px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(Print patient's name)</span></p>
						  </td>
						  <td style="width: 1%;white-space: nowrap;"><p  style="color: #000000;line-height: 16px;font-size: 16px;font-weight: 700; font-family: Arial;">, ("Assignor") hereby assign to<br>
<br>
</p></td>
						 <td style="width: 1%;white-space: nowrap;"><p  style="color: #000000;line-height: 16px;font-size: 16px; font-weight: 700; font-family: Arial;"><span style="text-decoration: underline;">&nbsp;{{$order->pharmacyname}}&nbsp;,</span><br>
<span style="font-size: 10px;">&nbsp;&nbsp;(Print hospital or health care provider name)</span> </p></td>
						<td><p  style="color: #000000;line-height: 16px;font-size: 16px; font-weight: 700; font-family: Arial;"> ("Assignee")<br>
<br>
</p></td>
						</tr>						
					  </tbody>
					</table>

		    <p  style="color: #000000;font-size: 16px;font-weight: 700; font-family: Arial;">
		      all rights privileges and remedies to payment for health care services provided by assignee to which I am
entitled under Article 51 (the No-Fault statute) of the Insurance Law.</p>
			  <p style="color: #000000;font-size: 15px; font-weight: 700; font-family: Arial;"> The Assignee hereby certifies that they have not received any payment from or on behalf of the Assignor and shall not pursue payment directly from the Assignor for services provided by said Assignee for injuries sustained due to
			  the motor vehicle accident which occurred on ________________ ,not withstanding any other agreement to the contrary. <br>
<span style="font-size: 10px; margin-left: 270px; margin-top: -15px;">(Print accident date)</span></p>			  
			  
			  
			  <p style="color: #000000;font-size: 15px; font-weight: 700; font-family: Arial;">This agreement may be revoked by the assignee when benefits are not payable based upon the assignor’s lack 
of coverage and/or violation of a policy condition due to the actions or conduct of the assignor. </p>
		    <p style="color: #000000;font-size: 15px; font-weight: 700; font-family: Arial;">ANY PERSON WHO KNOWINGLY AND WITH INTENT TO DEFRAUD ANY INSURANCE COMPANY OR OTHER PERSON FILES AN APPLICATION FOR COMMERCIAL INSURANCE OR A STATEMENT OF CLAIM FOR ANY COMMERCIAL OR PERSONAL INSURANCE BENEFITS CONTAINING ANY MATERIALLY FALSE INFORMATION, OR CONCEALS FOR THE PURPOSE OF MISLEADING, INFORMATION CONCERNING ANY FACT MATERIAL THERETO, AND ANY PERSON WHO, IN CONNECTION WITH SUCH APPLICATION OR CLAIM, KNOWINGLY MAKES OR KNOWINGLY ASSISTS, ABETS, SOLICITS OR CONSPIRES WITH ANOTHER TO MAKE A FALSE REPORT OF THE THEFT, DESTRUCTION, DAMAGE OR CONVERSION OF ANY MOTOR VEHICLE TO A LAW ENFORCEMENT AGENCY, THE DEPARTMENT OF MOTOR VEHICLES OR AN INSURANCE COMPANY, COMMITS A FRAUDULENT INSURANCE ACT, WHICH IS A CRIME, AND SHALL ALSO BE SUBJECT TO A CIVIL PENALTY NOT TO EXCEED FIVE THOUSAND DOLLARS AND THE VALUE OF THE SUBJECT MOTOR VEHICLE OR STATED CLAIM FOR EACH VIOLATION.</p>		  
			  
	  	</div>	  
	  </div>
	  
	   <div class="row" style="margin-top: 70px">
		  	
       <table width="100%" border="0">
					  <tbody>
						<tr>
						  <td style="width: 10%;"><p align="center" style="color: #000000;line-height: 13px;font-size: 14px; font-weight: 700; font-family: Arial;"></p>
						  </td>
						  <td   style="width: 40%;text-align: center"><p align="center" style="color: #000000;line-height: 13px;font-size: 14px; font-weight: 700; font-family: Arial;">{{$order->last_name}} {{$order->username}}<br>
                          <hr style="padding: 0 20px;margin: -13px 0 2px 0;">
							<span style="font-size: 11px; margin-top: -15px;">(Print patient's name)</span></p>
						  </td>	
							<td style="width: 5%;"><p align="center" style="color: #000000;line-height: 13px;font-size: 14px; font-weight: 700; font-family: Arial;"></p>
							</td>	
						 <td  style="width: 35%;text-align: center">
							 @if(!empty($order->signature_photo))<img src="{{$order->signature_photo}}" alt="Signature Photo" style="max-height: 120px;width: auto;height: auto;max-width: 200px;left:720px;top:781px;position: absolute">@endif
							 <p align="center" style="color: #000000;line-height: 13px;font-size: 16px; font-weight: 700; font-family: Arial;"><br>
                             <hr style="padding: 0 20px;margin: -13px 0 2px 0;">
							<span style="font-size: 11px;">(Signature of Patient)</span></p>
						  </td>
						  <td  style="width: 10%;"><p align="center" style="color: #000000;line-height: 13px;font-size: 16px; font-weight: 700; font-family: Arial;"></p>
						  </td>						
						</tr>
						<tr>
						  <td><p align="center" style="color: #000000;line-height: 13px;font-size: 16px; font-weight: 700; font-family: Arial;"></p>
						  </td>
						  <td style="text-align: center;"><p align="center" style="color: #000000;line-height: 11px;font-size: 12px; font-weight: 700; font-family: Arial;">{{$order->useraddress}}, {{$order->userzip}}<br>
							<hr style="padding: 0 20px;margin: -13px 0 2px 0;">
							<span style="font-size: 11px;">(Address of Patient)</span></p>
						  </td>	
							<td><p align="center" style="color: #000000;line-height: 13px;font-size: 16px; font-weight: 700; font-family: Arial;"></p>
							</td>	
						 <td style="text-align: center">
							 						 
							 <p align="center" style="color: #000000;line-height: 13px;font-size: 14px; font-weight: 700; font-family: Arial;">@if(!empty($order->finish)){{date('m.d.Y', strtotime($order->finish ?? ''))}}@endif<br>
							 <hr style="padding: 0 20px;margin: -13px 0 2px 0;"> 
							<span style="font-size: 11px;">(Date of signature)</span></p>
						  </td>
						  <td><p align="center" style="color: #000000;line-height: 13px;font-size: 16px; font-weight: 700; font-family: Arial;"></p>
						  </td>						
						</tr>
						<tr>
						  <td><p align="center" style="color: #000000;line-height: 13px;font-size: 16px; font-weight: 700; font-family: Arial;"></p>
						  </td>
						  <td style="text-align: center;">
						  </td>	
							<td><p align="center" style="color: #000000;line-height: 13px;font-size: 16px; font-weight: 700; font-family: Arial;"></p>
							</td>	
						 <td style="text-align: center">							 						 
							
						  </td>
						  <td><p align="center" style="color: #000000;line-height: 13px;font-size: 16px; font-weight: 700; font-family: Arial;"></p>
						  </td>						
						</tr>
						<tr>
						  <td><p align="center" style="color: #000000;line-height: 13px;font-size: 16px; font-weight: 700; font-family: Arial;"></p>
						  </td>
						  <td style="text-align: center;"><p align="center" style="color: #000000;line-height: 13px;font-size: 14px; font-weight: 700; font-family: Arial;">{{$order->pharmacyname}}<br>
                          <hr style="padding: 0 20px;margin: -13px 0 2px 0;">
							<span style="font-size: 11px;font-weight: bold;">(Print name of Provider)</span></p>
						  </td>	
							<td><p align="center" style="color: #000000;line-height: 13px;font-size: 16px; font-weight: 700; font-family: Arial;"></p>
							</td>	
						 <td style="text-align: center">
							 <p align="center" style="color: #000000;line-height: 14px;font-size: 14px; font-weight: 700; font-family: Arial;">&nbsp;<br>
							 <hr style="padding: 0 20px;margin: -13px 0 2px 0;"> 
							<span style="font-size: 11px;">(Signature of Provider)</span></p>
						  </td>
						  <td><p align="center" style="color: #000000;line-height: 13px;font-size: 16px; font-weight: 700; font-family: Arial;"></p>
						  </td>						
						</tr>
						<tr>
						  <td><p align="center" style="color: #000000;line-height: 13px;font-size: 16px; font-weight: 700; font-family: Arial;"></p>
						  </td>
						  <td style="text-align: center;"><p align="center" style="color: #000000;line-height: 11px;font-size: 12px; font-weight: 700; font-family: Arial;">{{$order->pharmacyaddress}}<br>
                          <hr style="padding: 0 20px;margin: -13px 0 2px 0;">
							<span style="font-size: 11px;">(Address of Provider)</span></p>
						  </td>	
							<td><p align="center" style="color: #000000;line-height: 13px;font-size: 16px; font-weight: 700; font-family: Arial;"></p>
							</td>	
						 <td style="text-align: center">
							 						 
							 <p align="center" style="color: #000000;line-height: 14px;font-size: 14px; font-weight: 700; font-family: Arial;">@if(!empty($order->finish)){{date('m.d.Y', strtotime($order->finish ?? ''))}}@endif<br>
							 <hr style="padding: 0 20px;margin: -13px 0 2px 0;"> 
							<span style="font-size: 11px;">(Date of signature)</span></p>
						  </td>
						  <td><p align="center" style="color: #000000;line-height: 13px;font-size: 16px; font-weight: 700; font-family: Arial;"></p>
						  </td>						
						</tr>
					  </tbody>
					</table>
		   
		</div>

        <div class="row" style="margin-top: 180px; padding: 0 80px;">
		  <div class="col-12">			

		    <p  style="color: #000000;font-size: 14px;font-family: Arial;">
            NYS FORM NF-AOB (Rev 1/2004)</p>
	  	</div>	  
	  </div>
    
    
    </div>





    


            </div>			 
                </div> 
                        
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="qm-section-title">Map</h5>
                        <div id="map" style="min-height: 420px;;width: 100%;"></div>
                        <div id="pano" style="min-height: 420px;;width: 100%;display:none;"></div>
                        <button id="toggle_map" class="btn btn-primary mt-2">Street View</button>
                        <!--<div style="min-height: 420px;width: 100%;display: flex;align-items: center;justify-content: center;" align="center">
                            <h5 align="center" style="margin: 50px 0;">Looking for a driver</h5>
                            <i class="ion ion-md-map mr-2" aria-hidden="true"></i>
                        </div>-->
                </div> 
            </div>				 
        </div> 
    </div>
	</div>
	 <div class="tab-pane fade" id="history">
		  <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="table-rep-plugin">
                                    <div class="table mb-0" data-pattern="priority-columns">
                                        <h1>Order History</h1>
                                        <table id="mytable2" class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Date/time</th>
                                                    <th>Transition</th>
                                                    <th>Driver ID</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($orders_transitions as $orders_transition)
                                                <tr>
                                                    <td>{{date('m/d/Y g:i A', strtotime($orders_transition->created ?? ''))}}</td>
                                                    @if(!empty($orders_transition->pharmacy_id))
                                                    <td>The package was taken/given the pharmacy (Bag {{$orders_transition->bag}})</td>
                                                    @endif
                                                    @if(!empty($orders_transition->user_id) && empty($orders_transition->target))
                                                    <td>The package was given to the patient (Bag {{$orders_transition->bag}})</td>
                                                    @elseif(!empty($orders_transition->user_id) && $orders_transition->target=="faild")
                                                    <td>The package was not given to the patient. (<a href="https://www.google.com/maps?q={{$orders_transition->location}}" target="_blank">Link to find the Driver at the time of delivery</a>)</td>
                                                    @endif
                                                    @if(!empty($orders_transition->office_id))
                                                        @if($orders_transition->target=="out")
                                                        <td>The package was taken to the office  (Bag {{$orders_transition->bag}})</td>
                                                        @else
                                                        <td>The package was given to the office (Bag {{$orders_transition->bag}})</td>
                                                        @endif
                                                    @endif
                                                    <td>{{$orders_transition->driver_id}}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
		  </div>
		<div class="tab-pane fade show" id="instructions">
			 <div class="row">
				<div class="col-4">
					 <div class="card">
                        <div class="card-body" style="min-height: 220px;">
							<h5 class="qm-section-title">Special instructions</h5>
							<div style="margin: 10px">  {{$order->special_instructions}}</div>							
						</div>
					</div>
				</div>
				<div class="col-4">
					 <div class="card">
                        <div class="card-body" style="min-height: 220px;">
							<h5 class="qm-section-title">Dispatcher Notes</h5>
							<div style="margin: 10px"> 
                                @if($order->statuse_id!=4 && $order->statuse_id!=5 && ((Auth::user()->hasAnyRole('superadmin', 'admin')) || Auth::user()->role == 'logist'))
								<div style="min-height: 30px;"><a href="#" id="ajax-alert" class="btn btn-sm btn-primary float-right">Add Note</a></div>
                                @endif
								<ol class="activity-feed mb-0">
								@foreach($dispatcher_notes as $dispatcher_note)
                                            <li class="feed-item">
                                                <div class="feed-item-list">
                                                    <span class="date">{{date('m.d.Y g:i A', strtotime($dispatcher_note->created ?? ''))}}</span>
                                                    <span class="activity-text">{{$dispatcher_note->note}}</span>
                                                </div>
                                            </li>                                          
								@endforeach
                               	</ol>								
							</div>							
						</div>
					</div>
				</div>
                <div class="col-4">
					 <div class="card">
                        <div class="card-body" style="min-height: 220px;">
							<h5 class="qm-section-title">Customer Notes</h5>
							<div style="margin: 10px">
								<ol class="activity-feed mb-0">
                                @foreach($customer_notes as $customer_note)
                                    <li class="feed-item">
                                        <div class="feed-item-list">
                                          	<span class="date">{{date('m.d.Y g:i A', strtotime($customer_note->created ?? ''))}}</span>
                                       		<span class="activity-text">{{$customer_note->note}}</span>
                                      	</div>
                                    </li> 
                                @endforeach
								</ol>	
                            </div>							
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="tab-pane fade show" id="items">
			 <div class="row">
				 <div class="col-12">
					 <div class="card">
                        <div class="card-body">
							<h5 class="qm-section-title">Order Items</h5>
							<table class="table table-striped" style="table-layout: fixed;">
                                <thead>
                                    <tr>
                                        <th data-priority="1" style="width: 20%;">RX#</th>
                                        <th data-priority="1" style="width: 10%;">Rf#</th>
                                        <th data-priority="2" style="width: 10%;">Qty</th>
                                        <th data-priority="1" style="width: 20%;">Date</th>
                                        <th data-priority="1" style="width: 30%;">Facility Patient</th>
                                        <th data-priority="1" style="width: 10%;">Delivery Slip</th>                                       
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($rxs as $key=>$rx)
                                <tr>
                                    <td>{{explode('-',$rx->rx_id)[0]}}</td>
                                    <td>@if(count(explode('-',$rx->rx_id))>1){{explode('-',$rx->rx_id)[1]}}@endif</td>
                                    <td>{{$rx->rx_count}}</td>
                                    <td>{{$rx->rx_date}}</td>
                                    <td>@if(isset($additional_recipients[$rx->rx_recipient ?? ''])) {{$additional_recipients[$rx->rx_recipient ?? '']->family_name}}, {{$additional_recipients[$rx->rx_recipient ?? '']->family_phone}} ({{$additional_recipients[$rx->rx_recipient ?? '']->family_type}}) @else{{'-'}}@endif</td>
                                    <td>@if(isset($additional_recipients[$rx->rx_recipient ?? '']) && $order->statuse_id==4) <button class="btn btn-dark waves-effect waves-light printbt facility" onclick="PrintElem('#finish-print{{$rx->rx_recipient}}')">Delivery Slip <i class="mdi mdi-printer-check"></i></button>@else{{'-'}}@endif</td>
                                </tr>
                                @endforeach
                                </tbody>
                            </table>							
						</div>
					</div>
				</div>				
			 </div>
			</div>
		 
		    <div class="tab-pane fade show" id="voice">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="qm-section-title">Call recording</h5>
                                <img src="https://www.schoolwearinc.co.uk/wp-content/uploads/2018/04/preloader.gif" alt="loader" class="loader">
                                <ol class="activity-feed mb-0">
                                </ol>							
                            </div>
                        </div>
                    </div>				
                </div> 
			</div>
            <div class="tab-pane fade show" id="video">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body" style="text-align: center;">
                                <h5 class="qm-section-title">Video</h5>
                                <img src="{{ asset('images/branding/quikmedix-logo.png?v=transparent-1') }}" alt="QuikMedix — Your Health - Our Priority" width="400" style="max-width: 100%; height: auto;">
                            </div>
                        </div>
                    </div>				
                </div> 
			</div>



</div>
<div class="modal fade bs-example-modal-lg" tabindex="-1" aria-labelledby="myLargeModalLabel" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myLargeModalLabel">Live tracker at delivery time</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
            <div class="modal-body">
                <div id="map-driver" style="min-height: 420px;;width: 100%;"></div>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>			 
							


		 <style>
            @media print {
                .noprint {
                    display: none !important;
                }
            }
        </style>
        <a href="#" class="noprint" onclick="window.print()" style="position:fixed;bottom:25px;right:25px;z-index:1;"><button class="btn btn-secondary"><i style="font-size: 25px;" class="ti-printer"></i></button></a>
@endsection
@section('footerScript')
<script src="/leaflet/leaflet.js"></script>
<script>
    function encodeImageFileAsURL(element) {
        if(element.files[0].size > 10097152){
            alert("File is too big!");element.value = "";
        }
    }
    var markersArray = [];
    var markersArray2 = [];
    var locationUser = "{{ $order->userlocation }}".split(',');
    var locationPharmasy = "{{ $order->pharmacylocation }}".split(',');
    @if($driver!='')
    var locationDriver = "{{ $locations?->location }}".split(',');
    var locationDrivers = [@foreach($locationDrivers as $locationDriver)
    "{{ $locationDriver->location }}",
    @endforeach];
    var locationDriversID = [@foreach($locationDrivers as $locationDriver)
    "{{ date('g:i A', strtotime($locationDriver->created ?? '')) }}",
    @endforeach];
    var deliverTime = "{{date('gi', strtotime($order->finish ?? ''))}}";
    var Driver = "{{ $locations?->user_id }}";
    @endif
    function addMarker(latlng, text, map) {
        if(parseInt(text.replace(/\D+/g,""))==parseInt(deliverTime) || parseInt(text.replace(/\D+/g,""))+1==parseInt(deliverTime)  || parseInt(text.replace(/\D+/g,""))+2==parseInt(deliverTime) || parseInt(text.replace(/\D+/g,""))-1==parseInt(deliverTime) || parseInt(text.replace(/\D+/g,""))-2==parseInt(deliverTime)) {
            marker2 = L.marker([latlng[0],latlng[1]],{
                icon: customIconDriverLog(text),
            }).addTo(map);
        } else {
            marker2 = L.marker([latlng[0],latlng[1]],{
                icon: customIconDriverLog(text,0.35),
            }).addTo(map);
        }
    }
    const here = {
        apiKey:"{{config('app.hereApiKey')}}"
    }
    const style = 'normal.day';
    const hereTileUrl = `https://maps.hereapi.com/v3/base/mc/{z}/{x}/{y}/png?apiKey=${here.apiKey}&ppi=400`;
    const UserLatlng = [Number(locationUser[0]), Number(locationUser[1])];
    const PharmasyLatlng = [Number(locationPharmasy[0]), Number(locationPharmasy[1])];
    @if($driver!='')
    const DriverLatlng = [Number(locationDriver[0]), Number(locationDriver[1])];
    $('#show_live_tracker').on('click',function(){
        var map2 = L.map(document.getElementById("map-driver")).setView(UserLatlng, 20);
        L.tileLayer(hereTileUrl).addTo(map2);
        var marker2 = L.marker(UserLatlng,{
            icon: customIconUser('{{$order->id}}\n({{date('g:i A', strtotime($order->finish ?? ''))}})'),
        }).addTo(map2);
        for(n=0;n<locationDrivers.length;n++) {
            addMarker(locationDrivers[n].split(','),locationDriversID[n].toString(),map2);
        }
        setTimeout(function () {
            window.dispatchEvent(new Event('resize'));
        }, 300);
    });
    @endif
    $(document).ready(function() {
        var map = L.map(document.getElementById("map")).setView(UserLatlng, 13);
        L.tileLayer(hereTileUrl).addTo(map);
        var marker = L.marker(UserLatlng,{
            icon: customIconUser("{{$order->id}}"),
        }).addTo(map);
        marker = L.marker(PharmasyLatlng,{
            icon: customPharmasyIcon(),
        }).addTo(map);
        @if($driver!='' && $locations)
        marker = L.marker(DriverLatlng,{
            icon: customIconDriver(),
        }).addTo(map);
        @endif
    });
    function customIconUser(text='') {
        return L.divIcon({
            iconSize: "auto",
            html:'<div style="width:80px;height:80px;position:relative;margin-left: -50%;margin-top: -50%;"><img alt="" src="/images/i033.svg" draggable="false" style="width:80px;height:80px;"><div style="position: absolute;top: calc(100% - 19px);"><div style="display: block;width: 80px;text-align: center;"><div class="" aria-hidden="true" style="color: rgb(0, 0, 0);line-height: 20px; font-size: 16px; font-family: Arial;">'+text+'</div></div></div></div>'
        });
    }
    function customPharmasyIcon() {
        return L.divIcon({
            iconSize: "auto",
            html:'<div style="width:50px;height:50px;position:relative;margin-left: -50%;margin-top: -50%;"><img alt="" src="/images/i011.svg" draggable="false" style="width:50px;height:50px;"></div>'
        });
    }
    function customIconDriver() {
        return L.divIcon({
            iconSize: "auto",
            html:'<div style="width:50px;height:50px;position:relative;margin-left: -50%;margin-top: -50%;"><img alt="" src="/images/i022.svg" draggable="false" style="width:50px;height:50px;"></div>'
        });
    }
    function customIconDriverLog(text='',opacity=1) {
        return L.divIcon({
            iconSize: "auto",
            html:'<div style="width:90px;height:90px;position:relative;margin-left: -50%;margin-top: -50%;opacity:'+opacity+';"><img alt="" src="/images/i055.svg" draggable="false" style="width:90px;height:90px;"><div style="position: absolute;bottom: 19px;"><div style="display: block;width: 90px;text-align: center;"><div class="" aria-hidden="true" style="color: rgb(0, 0, 0); font-size: 16px; font-family: Arial;">'+text+'</div></div></div></div>'
        });
    }
    $('#toggle_map').on('click',function () {
        if($('body').find('#google_map').length<1) {
            var s = document.createElement("script");
            s.type = "text/javascript";
            s.id = "google_map";
            s.src = "https://maps.googleapis.com/maps/api/js?key={{config('app.googlemaps_apikey')}}&region=US&language=en&v=weekly&callback=initializeMap2";
            $('#footerScript').append(s);
        }
        if($('#map').css('display')=='none'){
            $('#toggle_map').text('Street View');
            $('#map').show(300);
            $('#pano').hide(300);
        } else {
            $('#toggle_map').text('Map View');
            $('#map').hide(300);
            $('#pano').show(300);
        }
    });
    var panorama;
    function initializeMap2() {
        const fenway = { lat: UserLatlng[0], lng: UserLatlng[1] };
        var sv = new google.maps.StreetViewService();
        panorama = new google.maps.StreetViewPanorama(
            document.getElementById("pano")
        );
        sv.getPanorama({
            location: fenway,
            radius: 50,
            source: google.maps.StreetViewSource.OUTDOOR
        }, processSVData);
    }
    function processSVData(data, status) {
        console.log(data,status);
        if (status === google.maps.StreetViewStatus.OK) {
            panorama.setPano(data.location.pano);
            panorama.setPov({
                heading: 0,
                pitch: 0
            });
            panorama.setVisible(true);
        }
    }
    function PrintElem(elem){
        Popup($(elem).html());
    }
    function PrintElem2(elem){
        Popup2($(elem).html());
    }  
    function PrintElem3(elem){
        Popup3($(elem).html());
    }  
    function PrintElem7(elem){
        Popup7($(elem).html());
    }  
    
    function Popup(data){
        if (data && data.indexOf('data-document-unavailable') !== -1) {
            Swal.fire('Document unavailable', 'Please contact QuikMedix support for this form.', 'info');
            return false;
        }
        var mywindow = window.open('', 'my div', 'height=400,width=600');
        mywindow.document.write('<html><head><title>Delivery_Slip_{{$order->last_name}}_{{$order->username}}</title>');
        mywindow.document.write('<link href="{{ asset('css') }}/bootstrap.min.css?v=quikmedix-1" id="bootstrap-style" rel="stylesheet" type="text/css">');
        mywindow.document.write('</head><body >');
        mywindow.document.write(data);
        mywindow.document.write('</body></html>');
        mywindow.document.close(); // necessary for IE >= 10
        mywindow.focus(); // necessary for IE >= 10
        setTimeout(() => {mywindow.print(); }, 1500);
        mywindow.onafterprint = function(){ mywindow.close();}
        return true;
    }
    function Popup2(data){
        if (data && data.indexOf('data-document-unavailable') !== -1) {
            Swal.fire('Document unavailable', 'Please contact QuikMedix support for this form.', 'info');
            return false;
        }
        var mywindow = window.open('', 'my div', 'height=400,width=600');
        mywindow.document.write('<html><head><title>HIPAA_{{$order->last_name}}_{{$order->username}}</title>');
        mywindow.document.write('<link href="{{ asset('css') }}/bootstrap.min.css?v=quikmedix-1" id="bootstrap-style" rel="stylesheet" type="text/css">');
        mywindow.document.write('</head><body >');
        mywindow.document.write(data);
        mywindow.document.write('</body></html>');
        mywindow.document.close(); // necessary for IE >= 10
        mywindow.focus(); // necessary for IE >= 10
        setTimeout(() => {mywindow.print(); }, 1500);
        mywindow.onafterprint = function(){ mywindow.close();}
        return true;
    } 
    function Popup3(data){
        if (data && data.indexOf('data-document-unavailable') !== -1) {
            Swal.fire('Document unavailable', 'Please contact QuikMedix support for this form.', 'info');
            return false;
        }
        var mywindow = window.open('', 'my div', 'height=400,width=600');
        mywindow.document.write('<html><head><title>AOB_{{$order->last_name}}_{{$order->username}}</title>');
        mywindow.document.write('<link href="{{ asset('css') }}/bootstrap.min.css?v=quikmedix-1" id="bootstrap-style" rel="stylesheet" type="text/css">');
        mywindow.document.write('</head><body >');
        mywindow.document.write(data);
        mywindow.document.write('</body></html>');
        mywindow.document.close(); // necessary for IE >= 10
        mywindow.focus(); // necessary for IE >= 10
        setTimeout(() => {mywindow.print(); }, 1500);
        mywindow.onafterprint = function(){ mywindow.close();}
        return true;
    }   
    function Popup7(data){
        if (data && data.indexOf('data-document-unavailable') !== -1) {
            Swal.fire('Document unavailable', 'Please contact QuikMedix support for this form.', 'info');
            return false;
        }
        var mywindow = window.open('', 'my div', 'height=400,width=600');
        mywindow.document.write('<html><head><title>Print_{{$order->username}}</title>');
        mywindow.document.write('<link href="{{ asset('css') }}/bootstrap.min.css?v=quikmedix-1" id="bootstrap-style" rel="stylesheet" type="text/css">');
        mywindow.document.write('</head><body >');
        mywindow.document.write(data);
        mywindow.document.write('</body></html>');
        mywindow.document.close(); // necessary for IE >= 10
        mywindow.focus(); // necessary for IE >= 10
        setTimeout(() => {mywindow.print(); }, 1500);
        mywindow.onafterprint = function(){ mywindow.close();}
        return true;
    }
</script>
<script src="{{ URL::asset('/js/bootstrap.bundle.min.js')}}"></script>
<script src="{{ URL::asset('/js/sweetalert2.min.js')}}"></script>
<script>
    $("#ajax-alert").click(function(){
        Swal.fire({
            title:"Add Dispatcher Note",
            input:"text",
            showCancelButton:!0,
            confirmButtonText:"Submit",
            showLoaderOnConfirm:!0,
            confirmButtonColor:"#c90016",
            cancelButtonColor:"#f46a6a",
            preConfirm:function(n){
                if(n==="" || n.length<2) {
                    Swal.fire({
                        title:"Cancelled",
                        text:"Note can not be empty!",
                        icon:"error"
                    });
                } else {
                    $.post(location.href, { _token: $('input[name="_token"]').val(), dispatcher_notes: n }).done(function( data ) {
                        data=JSON.parse(data);
                        if(data.message=="OK") {
                            Swal.fire({
                                icon:"success",
                                title:"Add Dispatcher Note",
                                html:"Success added.",
                                confirmButtonColor:"#c90016"
                            });
                        } else {
                            Swal.fire({
                                title:"Cancelled",
                                text:"Your imaginary file is safe :)",
                                icon:"error"
                            });
                        }
                    });
                }
            },
            allowOutsideClick:!1
        });
    });
    var order_id = "{{$order->id}}";
    function reSendAuthMessage(user_id,_this) {
        $.post("/patients/"+user_id+"/resend", { _token: $('input[name="_token"]').val() }).done(function( data ) {
            data=JSON.parse(data);
            if(data.message=="OK") {
                $(_this).text("Sended");
                $(_this).attr("disabled","disabled");
            } else {
                alert(data.message);
            }
        });
    }
</script>
@endsection