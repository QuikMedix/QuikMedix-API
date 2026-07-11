<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 style="background: #7a6fbe;color: #ffffff;padding: 5px;text-align: center;">Order Details</h5>
                <div class="row">							
                    <div class="col-6">							
                        <b>Order:</b> {{$order->id}} - <span style="font-size: 11px;" class="badge badge-pill badge-{{$order->statusecolor}}">{{$order->statusename}}</span><br>
                        @if(!empty($driver))
                        <b>Time away:</b> {{floor($order->eta / 60)}} hr {{$order->eta % 60}} min
                        <br>
                        @endif
                        <div class="mt-2">
                        <b>Co-Pay:</b> {{$order->copay}} $
                            @if(!empty($order->statuse_copay_name))
                                <span style="font-size: 11px;" class="badge badge-pill badge-{{$order->statuse_copay_color}} mt-1">{{$order->statuse_copay_name}}</span>
                            @endif
                            
                            @if(!in_array($order->statuse_id,[1,7,8,9,10]) && ((Auth::user()->role == 'superadmin' || Auth::user()->role == 'admin') || Auth::user()->role == 'logist') && $order->copay>0 && !in_array($order->statuse_copay,[1,3,4,6]))
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
                        <b>Created:</b> {{date('m/d/Y g:i A', strtotime($order->created))}}<br>
                        <b>Need Delivery:</b> <span style="font-size:100%;" class="badge @if(empty($order->finish) && strtotime(date('Y-m-d'))==strtotime($order->delivery_date)){{'bg-success'}}@elseif(empty($order->finish) && strtotime(date('Y-m-d'))>strtotime($order->delivery_date)){{'bg-danger'}}@else{{'bg-light'}}@endif">{{date('m/d/Y', strtotime($order->delivery_date))}} @if(!empty($order->delivery_time_range) && $order->delivery_time_range!='9:00 AM;12:00 AM')<b>{{str_replace(':00','',str_replace(';',' - ',$order->delivery_time_range))}}</b>@endif</span><br>
                        @if($order->statuse_id==4)
                        <b>Delivered:</b> {{date('m/d/Y g:i A', strtotime($order->finish))}}<br>
                        @endif
                        <b>Options:</b> {{$order->delivery_method}}<br>
                        <b>Time:</b> {{$order->delivery_time}}<br>
                        <b>Fridge:</b> {{($order->fridge>0)?'yes':'no'}}<br>
                        @if((Auth::user()->role == 'medic' || (Auth::user()->role == 'superadmin' || Auth::user()->role == 'admin')) && ($order->statuse_id==4))
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
                            @if($order->statuse_id==4 && empty($order->facility))
                                <button class="btn btn-dark waves-effect waves-light printbt" onclick="PrintElem('#finish-print')" style="margin-top: 8px;">Delivery Slip <i class="mdi mdi-printer-check"></i></button>                                         
                            @endif	
                            @if($order->statuse_id==4)                                         
                                <button class="btn btn-dark waves-effect waves-light printbt" onclick="PrintElem2('#HIPAA')" style="margin-top: 8px;">HIPAA <i class="mdi mdi-printer-check"></i></button>
                                <button class="btn btn-dark waves-effect waves-light printbt" onclick="PrintElem3('#AOB')" style="margin-top: 8px;">NYS FORM NF-AOB <i class="mdi mdi-printer-check"></i></button>
                                @if($order->pharmacy_id==123) 
                                <button class="btn btn-dark waves-effect waves-light printbt" onclick="PrintElem4('#PLA')" style="margin-top: 8px;">Provider’s Lien <i class="mdi mdi-printer-check"></i></button>                                        
                                @endif
                                
                                <div class="row" style="margin: 10px 0;">
                                <button id="show_live_tracker" type="button" class="btn btn-primary btn-lg waves-effect waves-light me-1 mt-2" data-bs-toggle="modal" data-bs-target=".bs-example-modal-lg">Live tracker at delivery time</i></button>
                                </div>                                    
                            @endif	
                        </div>
                        <div style="margin: 10px 0;"><b>Signature owner:</b> {{$order->signature_type}}</div>	
                    </div>
                    <div class="col-6">
                        @if($driver!='')
                        <span style="font-size: 19px;font-weight: 900; color: #000000;border-bottom: solid #000 1px;">Driver</span><br>
                        <i class="mdi mdi-emoticon-cool-outline"></i> {{ $driver->name }} {{ $driver->last_name }} (#{{ $driver->id }})<br>
                        <i class="mdi mdi-cellphone-android"></i> {{ $driver->phone }}<br>
                        <i class="mdi mdi-car"></i> {{ $driver->car_info }}
                        @else
                        Driver not selected
                        @endif <br>
                        <span style="font-size: 19px;font-weight: 900; color: #000000;border-bottom: solid #000 1px;">Pharmacy </span><br>
                        <i class="mdi mdi-medical-bag"></i> {{$order->pharmacyname}}<br>
                        @if(!empty($order->medic_id))<i class="mdi mdi-account-heart"></i> {{$order->medicname}} {{$order->mediclast_name}} (ID #{{$order->medic_id}})<br>@endif
                        <i class="mdi mdi-cellphone-android"></i> {{$order->pharmacyphone}}
                        @if((Auth::user()->role == 'superadmin' || Auth::user()->role == 'admin') || Auth::user()->role == 'logist')                
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
                        <span class="badge badge-pill badge-danger" style="margin: 7px 0;">No app <i style="color:#fff;font-size: inherit;" class="ion ion-md-close-circle-outline"></i></span>
                        @endif
                        <br>
                        <i class="mdi mdi-cellphone-android"></i> {{$order->userphone}} 
                        @if((Auth::user()->role == 'superadmin' || Auth::user()->role == 'admin') || Auth::user()->role == 'logist')
                        <button class="btn btn-primary btn-sm waves-effect call_patient" data-phone="{{ $order->userphone }}">Call <i class="ti-headphone-alt" style="color:#fff;font-size: inherit;"></i></button>
                        @endif
                        @if(!empty($order->userhomephone))
                        <br>
                        <i class="mdi mdi-deskphone"></i> {{$order->userhomephone}} 
                        @if((Auth::user()->role == 'superadmin' || Auth::user()->role == 'admin') || Auth::user()->role == 'logist')
                        <button class="btn btn-primary btn-sm waves-effect call_patient" data-phone="{{ $order->userhomephone }}">Call <i class="ti-headphone-alt" style="color:#fff;font-size: inherit;"></i></button>
                        @endif
                        @endif 
                        <br>
                        <i class="mdi mdi-map-clock-outline"></i> {{$order->useraddress}}, {{$order->userzip}}<br>
                        <i class="mdi mdi-office-building"></i> {{$order->userapartment}}<br>
                    </div>							
                </div>
            </div>
        </div>	
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 style="background: #7a6fbe;color: #ffffff;padding: 5px;text-align: center;">Order Items</h5>
                <table class="table table-striped" style="table-layout: fixed;">
                    <thead>
                        <tr>
                            <th data-priority="1" style="width: 20%;">RX#</th>
                            <th data-priority="1" style="width: 12%;">Rf#</th>
                            <th data-priority="2" style="width: 14%;">Qty</th>
                            <th data-priority="1" style="width: 20%;">Date</th>
                            <th data-priority="1" style="width: 34%;">Facility Patient</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($rxs as $key=>$rx)
                    <tr>
                        <td>{{explode('-',$rx->rx_id)[0]}}</td>
                        <td>@if(count(explode('-',$rx->rx_id))>1){{explode('-',$rx->rx_id)[1]}}@endif</td>
                        <td>{{$rx->rx_count}}</td>
                        <td>{{$rx->rx_date}}</td>
                        <td>@if(isset($additional_recipients[$rx->rx_recipient])) {{$additional_recipients[$rx->rx_recipient]->family_name}}, {{$additional_recipients[$rx->rx_recipient]->family_phone}} ({{$additional_recipients[$rx->rx_recipient]->family_type}}) @else{{'-'}}@endif</td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h5 style="background: #7a6fbe;color: #ffffff;padding: 5px;text-align: center;">Order Instructions</h5>
                <div class="row">
                    <div class="col-4">
                        <div class="card">
                            <div class="card-body" style="min-height: 220px;">
                                <h5 style="background: #7a6fbe;color: #ffffff;padding: 5px;text-align: center;">Special instructions</h5> 	
                                <div style="margin: 10px">  {{$order->special_instructions}}</div>							
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="card">
                            <div class="card-body" style="min-height: 220px;">
                                <h5 style="background: #7a6fbe;color: #ffffff;padding: 5px;text-align: center;">Dispatcher Notes</h5> 	
                                <div style="margin: 10px"> 
                                    @if($order->statuse_id!=4 && $order->statuse_id!=5 && ((Auth::user()->role == 'superadmin' || Auth::user()->role == 'admin') || Auth::user()->role == 'logist'))
                                    <div style="min-height: 30px;"><a href="#" id="ajax-alert" class="btn btn-sm btn-primary float-right">Add Note</a></div>
                                    @endif
                                    <ol class="activity-feed mb-0">
                                    @foreach($dispatcher_notes as $dispatcher_note)
                                                <li class="feed-item">
                                                    <div class="feed-item-list">
                                                        <span class="date">{{date('m.d.Y g:i A', strtotime($dispatcher_note->created))}}</span>
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
                                <h5 style="background: #7a6fbe;color: #ffffff;padding: 5px;text-align: center;">Customer Notes</h5> 	
                                <div style="margin: 10px">
                                    <ol class="activity-feed mb-0">
                                    @foreach($customer_notes as $customer_note)
                                        <li class="feed-item">
                                            <div class="feed-item-list">
                                                <span class="date">{{date('m.d.Y g:i A', strtotime($customer_note->created))}}</span>
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
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        toastr["success"]("@if(!empty($driver)) Driver {{ $driver->name }} {{ $driver->last_name }}, ID {{ $driver->id }}@else{{'Driver not selected'}}@endif");
        speak("@if(!empty($driver)) Driver {{ $driver->name }} {{ $driver->last_name }}, ID {{ $driver->id }}@else{{'Driver not selected'}}@endif");
    });
    $("#ajax-alert").click(function(){
        Swal.fire({
            title:"Add Dispatcher Note",
            input:"text",
            showCancelButton:!0,
            confirmButtonText:"Submit",
            showLoaderOnConfirm:!0,
            confirmButtonColor:"#7a6fbe",
            cancelButtonColor:"#f46a6a",
            preConfirm:function(n){
                if(n==="" || n.length<2) {
                    Swal.fire({
                        title:"Cancelled",
                        text:"Note can not be empty!",
                        icon:"error"
                    });
                } else {
                    $.post('/orders/{{$order->pharmacy_id}}/show/{{$order->id}}', { _token: $('input[name="_token"]').val(), dispatcher_notes: n }).done(function( data ) {
                        data=JSON.parse(data);
                        if(data.message=="OK") {
                            Swal.fire({
                                icon:"success",
                                title:"Add Dispatcher Note",
                                html:"Success added.",
                                confirmButtonColor:"#7a6fbe"
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
</script>