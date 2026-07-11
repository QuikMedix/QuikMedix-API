@extends('layouts.master')

@section('title') Edit User @endsection

@section('headerCss')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
    padding: 0 !important;
}
[type="date"]::-webkit-calendar-picker-indicator {
  color: transparent;
  opacity: 1;
  background: url(https://cdn1.iconfinder.com/data/icons/cc_mono_icon_set/blacks/16x16/calendar_2.png) no-repeat center;
  background-size: contain;
  filter: opacity(0.5) drop-shadow(0 0 0 blue);
}
.select2-container .select2-selection--multiple .select2-selection__choice {
    padding: 0 20px !important;
}
</style>
@endsection

@section('content')
 <!-- start page title -->
                    <div class="row">

                   
                    </div>
                    <!-- end page title -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div  class="card-title-5">
                                <div class="row">
                                    <div class="col-8">
                                        <h4 class="mt-2"><i class="mdi mdi-medical-bag"></i> {{$pharmacy->name}} - <i class="mdi mdi-google-maps"></i> {{$pharmacy->address}}</h4> 
                                    </div> 
                                    <div class="col-4 text-right">

                                    @if((Auth::user()->role == 'superadmin' || Auth::user()->role == 'admin' || Auth::user()->role == 'dispadmin'))
                                        <a href="/billing/{{ $pharmacy->id }}"><button type="button" class="btn btn-outline-dark waves-effect waves-light">Billing</button></a>
                                        <a href="/pharmacys/edit/{{ $pharmacy->id }}"><button type="button" class="btn btn-dark waves-effect waves-light">Edit</button></a>                    
                                    @endif
                                       
                                    </div>  
                                </div>                                            
                            </div>
                        </div>    
                    </div>

                    <div class="row">
                        <div class="col-xl-4 col-sm-6">
                            <div class="card">
                                <div class="card-body"><h5 style="background: #7a6fbe;color: #ffffff;padding: 5px;text-align: center;">Logo </h5>
                                    <form method="post" enctype="multipart/form-data">
                                        @csrf
                                        <input type="hidden" name="save" value="1">
                                        @if($alert!='') 
                                            <div class="alert alert-danger" role="alert">{{ $alert }}</div>
                                        @endif									
                                        <div class="form-group row" align="center">                                       
                                                <div class="col-sm-12" style="margin-bottom: 5px;">
                                                    <div style="margin: 10px; min-height: 200px;">
                                                        <img style="width: 100%;" id="user_img" src="{{ $pharmacy->logo }}">
                                                    </div> 
                                                    <input class="form-control" type="file" name="image" onchange='encodeImageFileAsURL(this);' accept="image/x-png,image/jpeg,image/jpg">                                                   
                                                </div>
                                        </div>                                    										
                                </div>
                                <div class="card-body"><h5 style="background: #7a6fbe;color: #ffffff;padding: 5px;text-align: center;">Exterior</h5>  
                                        <div class="form-group row" align="center">                                        
                                            <div class="col-sm-12" style="margin-bottom: 5px;">
                                                <div style="margin: 10px; min-height: 200px;">
                                                    <img style="width: 100%;" id="user_img" src="{{ $pharmacy->image_front }}">
                                                </div> 
                                                <input class="form-control" type="file" name="image_front" onchange='encodeImageFileAsURL(this);' accept="image/x-png,image/jpeg,image/jpg">                                                
                                            </div>
                                    </div>									
                                </div>
                            </div>
                        </div>
                       
                        <div class="col-xl-4 col-sm-6">
                            <div class="card">
                            <div class="card-body" style="min-height: 430px;">
                                    <h5 style="background: #7a6fbe;color: #ffffff;padding: 5px;text-align: center;">Profile</h5>
                                        <div class="form-group" style="margin-bottom: 0;">
                                            <label for="example-text-input" class="col-sm-12 col-form-label">Pharmacy name</label>
                                            <div class="col-sm-12">
                                                <input class="form-control" required type="text" name="name" value="{{ $pharmacy->name }}">
                                            </div>
                                        </div>
                                        <div class="form-group" style="margin-bottom: 0;">
                                            <label for="example-text-input" class="col-sm-12 col-form-label">Email</label>
                                            <div class="col-sm-12 ">
                                                <input class="form-control" required type="email" name="email" value="{{ $pharmacy->email }}">
                                            </div>
                                        </div>
                                        <div class="form-group" style="margin-bottom: 0;">
                                            <label for="example-text-input" class="col-sm-12 col-form-label">Phone</label>
                                            <div class="col-sm-12">
                                                <input class="form-control" required type="text" name="phone" id="phone" value="{{ $pharmacy->phone }}">
                                            </div>
                                        </div>
                                        <div class="form-group" style="margin-bottom: 0;">
                                            <label for="example-text-input" class="col-sm-12 col-form-label">Address</label>
                                            <div class="col-sm-12">
                                                <input class="form-control" required id="searchTextField" type="text" name="address" value="{{ $pharmacy->address }}">
                                            </div>
                                        </div>
                                        <div class="form-group" style="margin-bottom: 0;">
                                            <label for="example-text-input" class="col-sm-12 col-form-label">Website</label>
                                            <div class="col-sm-12 ">
                                                <input class="form-control" required type="text" name="site" value="{{ $pharmacy->site }}">
                                            </div>
                                        </div>
                                        <div class="form-group" style="margin-bottom: 0;">
                                            <label for="zone_id" class="col-sm-12 col-form-label">Admin Zone</label>
                                            <div class="col-sm-12">
                                                <select name="zone_id" id="zone_id" class="form-control" required>
                                                    <option value="">Not Selected</option>
                                                    @foreach($admin_areas as $admin_area)
                                                    <option value="{{$admin_area->id}}" @if($admin_area->id==$pharmacy->zone_id){{'selected'}}@endif>{{$admin_area->name}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group" style="margin-bottom: 0;">
                                            <label for="example-text-input" class="col-sm-12 col-form-label">Default Areas</label>
                                            <div class="col-sm-12">
                                                <select class="form-control" name="tariff_areas1[]" multiple id="areas">
                                                    @foreach($areas as $area)
                                                    @if(in_array($area->id,$pharmacy_areas1))
                                                    <option selected value="{{$area->id}}">{{$area->state}}, {{$area->name}}</option>
                                                    @else
                                                    <option value="{{$area->id}}">{{$area->state}}, {{$area->name}}</option>
                                                    @endif
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group" style="margin-bottom: 0;">
                                            <label for="example-text-input" class="col-sm-12 col-form-label">Second Areas</label>
                                            <div class="col-sm-12">
                                                <select class="form-control" name="tariff_areas2[]" multiple id="areas2">
                                                    @foreach($areas as $area)
                                                    @if(in_array($area->id,$pharmacy_areas2))
                                                    <option selected value="{{$area->id}}">{{$area->state}}, {{$area->name}}</option>
                                                    @else
                                                    <option value="{{$area->id}}">{{$area->state}}, {{$area->name}}</option>
                                                    @endif
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group" style="margin-bottom: 0;">
                                            <label for="example-text-input" class="col-sm-12 col-form-label">Third Areas</label>
                                            <div class="col-sm-12">
                                                <select class="form-control" name="tariff_areas3[]" multiple id="areas3">
                                                    @foreach($areas as $area)
                                                    @if(in_array($area->id,$pharmacy_areas3))
                                                    <option selected value="{{$area->id}}">{{$area->state}}, {{$area->name}}</option>
                                                    @else
                                                    <option value="{{$area->id}}">{{$area->state}}, {{$area->name}}</option>
                                                    @endif
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        @if(!empty($admin))
                                        <div class="form-group" style="margin-bottom: 0;">
                                            <label for="example-text-input" class="col-sm-12 col-form-label">Admin User</label>
                                            <div class="col-sm-12">
                                                {{ $admin->name }} {{ $admin->last_name }}, {{ $admin->phone }} (#{{ $admin->id }})
                                            </div>
                                        </div>
                                        @endif
                                        <hr>
                                        <div class="form-group" style="margin-bottom: 0;">
                                            <label for="example-text-input" class="col-sm-12 col-form-label">NPI number</label>
                                            <div class="col-sm-12 ">
                                                <input class="form-control" type="text" name="npi" maxlength="10" minlength="8" value="{{ $pharmacy->npi }}">
                                            </div>
                                        </div>
                                        <div class="form-group" style="margin-bottom: 0;">
                                            <label for="example-text-input" class="col-sm-12 col-form-label">NCPDP number</label>
                                            <div class="col-sm-12 ">
                                                <input class="form-control" type="text" name="ncpdp" maxlength="10" minlength="8" value="{{ $pharmacy->ncpdp }}">
                                            </div>
                                        </div>
                                        <div class="form-group" style="margin-bottom: 0;">
                                            <label for="example-text-input" class="col-sm-12 col-form-label">DEA number</label>
                                            <div class="col-sm-12 ">
                                                <input class="form-control" type="text" name="dea" maxlength="10" minlength="8" value="{{ $pharmacy->dea }}">
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="form-check mt-3 ml-3">
                                            <input class="form-check-input" type="checkbox" @if($pharmacy->copay_bill) checked @endif value="1" name="copay_bill" id="copay_bill">
                                            <label class="form-check-label" for="copay_bill">On/Off Copay as bill credit</label>
                                        </div>
                                        <div class="form-check mt-3 ml-3">
                                            <input class="form-check-input" type="checkbox" @if($pharmacy->massiveBagsTransfer) checked @endif value="1" name="massiveBagsTransfer" id="massiveBagsTransfer">
                                            <label class="form-check-label" for="massiveBagsTransfer">On/Off Function massive bags transfer</label>
                                        </div>
                                        <div class="form-check mt-3 ml-3">
                                            <input class="form-check-input" type="checkbox" @if($pharmacy->merchantFunc) checked @endif value="1" name="merchantFunc" id="merchantFunc">
                                            <label class="form-check-label" for="merchantFunc">On/Off Function Micro Merchant Systems</label>
                                        </div>
                                        <div class="form-check mt-3 ml-3">
                                            <input class="form-check-input" type="checkbox" @if($pharmacy->bestrxFunc) checked @endif value="1" name="bestrxFunc" id="bestrxFunc">
                                            <label class="form-check-label" for="bestrxFunc">On/Off Function BestRx Dispensing Service</label>
                                        </div>
                                        <hr>
                                        @if($pharmacy->merchantFunc)
                                        <div class="form-group" style="margin-bottom: 0;">
                                            <label for="example-text-input" class="col-sm-12 col-form-label">Merchant Api Key</label>
                                            <div class="col-sm-12 ">
                                                <input class="form-control" type="text" onclick="copyToClipboard(this)" readonly value="{{ $pharmacy->merchantKey }}">
                                            </div>
                                        </div>
                                        <div class="form-group" style="margin-bottom: 0;">
                                            <label for="example-text-input" class="col-sm-12 col-form-label">Merchant Api Secret</label>
                                            <div class="col-sm-12 ">
                                                <input class="form-control" type="text" onclick="copyToClipboard(this)" readonly value="{{ $pharmacy->merchantSecret }}">
                                            </div>
                                        </div>
                                        @endif
                                        @if($pharmacy->bestrxFunc)
                                        <div class="form-group" style="margin-bottom: 0;">
                                            <label for="example-text-input" class="col-sm-12 col-form-label">BestRx Pharmacy ID</label>
                                            <div class="col-sm-12 ">
                                                <input class="form-control" type="text" name="bestrx_pharmacy_id" maxlength="40" minlength="20" value="{{ $pharmacy->bestrx_pharmacy_id }}">
                                            </div>
                                        </div>
                                        <div class="form-group" style="margin-bottom: 0;">
                                            <label for="example-text-input" class="col-sm-12 col-form-label">BestRx Api Key</label>
                                            <div class="col-sm-12 ">
                                                <input class="form-control" type="text" onclick="copyToClipboard(this)" readonly value="{{ $pharmacy->bestrxKey }}">
                                            </div>
                                        </div>
                                        <div class="form-group" style="margin-bottom: 0;">
                                            <label for="example-text-input" class="col-sm-12 col-form-label">BestRx Api Secret</label>
                                            <div class="col-sm-12 ">
                                                <input class="form-control" type="text" onclick="copyToClipboard(this)" readonly value="{{ $pharmacy->bestrxSecret }}">
                                            </div>
                                        </div>
                                        @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-sm-6">
                            <div class="card">
                                <div class="card-body" style="min-height: 430px;">
                                    <h5 style="background: #7a6fbe;color: #ffffff;padding: 5px;text-align: center;">Tariff </h5><br>
                                    <div class="row"> 
                                        <div class="col-xl-12 col-sm-12" style="margin: 0px 0;">                         
                                            <div class="col-sm-12">
                                                <select class="form-control" required name="plan_id" id="plan_id">
                                                    <option value="">----</option>
                                                    @foreach($plans as $plan)
                                                    @if($pharmacy->plan_id==$plan->id)
                                                    <option selected value="{{$plan->id}}">{{$plan->name}}, Monthly Order Rate: {{$plan->order_rate}}, Default Tariff: {{$plan->tariff}} $</option>
                                                    @else
                                                    <option value="{{$plan->id}}">{{$plan->name}}, Monthly Order Rate: {{$plan->order_rate}}, Default Tariff: {{$plan->tariff}} $</option>
                                                    @endif
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-xl-12 col-sm-12" id="date_end_trial" @if($pharmacy->plan_id!="2") style="display:none;margin: 10px 0;" @else style="margin: 10px 0;" @endif>
                                            <div class="form-group" style="margin-bottom: 0;">
                                                <label for="example-text-input" class="col-sm-12 col-form-label">Date End Free trial</label>
                                                <div class="col-sm-12 ">
                                                    <input class="form-control" name="date_end_trial" type="date" value="{{ $pharmacy->date_end_trial }}">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xl-6 col-sm-6" style="margin: 10px 0;">
                                            <span style="font-size:10px;width:100%;height: 25px;align-items: center;display: grid;white-space:normal;color: white;margin: 10px 0px;" class="badge bg-primary badge-pill">Default Tariff @if(!empty($pharmacy_plan)){{'('.$pharmacy_plan->tariff.'$)'}}@endif</span>
                                            <div class="col-sm-12">
                                                <input class="form-control" type="number" step="0.1" min="0" name="tariff" value="{{ $pharmacy->tariff }}">
                                            </div>
                                        </div>
                                        <div class="col-xl-6 col-sm-6" style="margin: 10px 0;">
                                            <span style="font-size:10px;width:100%;height: 25px;align-items: center;display: grid;white-space:normal;color: white;margin: 10px 0px;" class="badge badge-pill bg-info">Second Area @if(!empty($pharmacy_plan)){{'('.$pharmacy_plan->tariff_area2.'$)'}}@endif</span>
                                            <div class="col-sm-12">
                                                <input class="form-control" type="number" step="0.1" min="0" name="tariff_area2" value="{{ $pharmacy->tariff_area2 }}">
                                            </div>
                                        </div>
                                        <div class="col-xl-6 col-sm-6" style="margin: 10px 0;">                                          
                                            <span style="font-size:10px;width:100%;height: 25px;align-items: center;display: grid;white-space:normal;color: white;margin: 10px 0px;" class="badge badge-pill bg-danger">Third Area @if(!empty($pharmacy_plan)){{'('.$pharmacy_plan->tariff_area3.'$)'}}@endif</span>                                           
                                            <div class="col-sm-12">
                                                <input class="form-control" type="number" step="0.1" min="0" name="tariff_area3" value="{{ $pharmacy->tariff_area3 }}">
                                            </div>
                                        </div>
                                        <div class="col-xl-6 col-sm-6" style="margin: 10px 0;">                                          
                                            <span style="font-size:10px;width:100%;height: 25px;align-items: center;display: grid;white-space:normal;color: white;margin: 10px 0px;background-color: #808c98 !important;" class="badge badge-pill bg-light">Far Area @if(!empty($pharmacy_plan)){{'('.$pharmacy_plan->tariff_area_more.'$)'}}@endif</span>                                           
                                            <div class="col-sm-12">
                                                <input class="form-control" type="number" step="0.1" min="0" name="tariff_area_more" value="{{ $pharmacy->tariff_area_more }}">
                                            </div>
                                        </div>
                                        <hr class="w-100 mt-1 mb-1">
                                        <div class="col-xl-6 col-sm-6" style="margin: 10px 0;">
                                            <span style="font-size:10px;width:100%;height: 25px;align-items: center;display: grid;white-space:normal;color: white;margin: 10px 0px;" class="badge badge-pill badge-success">Markup Next day delivery @if(!empty($pharmacy_plan)){{'('.$pharmacy_plan->tariff_next_day.'$)'}}@endif</span>
                                            <div class="col-sm-12">
                                                <input class="form-control" type="number" step="0.1" min="0" name="tariff_next_day" value="{{ $pharmacy->tariff_next_day }}">
                                            </div>
                                        </div>
                                        <div class="col-xl-6 col-sm-6" style="margin: 10px 0;">
                                            <span style="font-size:10px;width:100%;height: 25px;align-items: center;display: grid;white-space:normal;color: white;margin: 10px 0px;" class="badge badge-pill badge-warning">Markup Same day delivery @if(!empty($pharmacy_plan)){{'('.$pharmacy_plan->tariff_same_day.'$)'}}@endif</span>
                                            <div class="col-sm-12">
                                                <input class="form-control" type="number" step="0.1" min="0" name="tariff_same_day" value="{{ $pharmacy->tariff_same_day }}">
                                            </div>
                                        </div>
                                        <div class="col-xl-6 col-sm-6" style="margin: 10px 0;">
                                            <span style="font-size:10px;width:100%;height: 25px;align-items: center;display: grid;white-space:normal;color: white;margin: 10px 0px;" class="badge badge-pill badge-danger">Markup ASAP Delivery @if(!empty($pharmacy_plan)){{'('.$pharmacy_plan->tariff_asap.'$)'}}@endif</span>
                                            <div class="col-sm-12">
                                                <input class="form-control" type="number" step="0.1" min="0" name="tariff_asap" value="{{ $pharmacy->tariff_asap }}">
                                            </div>
                                        </div>
                                        <div class="col-xl-6 col-sm-6" style="margin: 10px 0;">                                          
                                            <span style="font-size:10px;width:100%;height: 25px;align-items: center;display: grid;white-space:normal;color: white;margin: 10px 0px;" class="badge badge-pill bg-danger">Markup After Hours @if(!empty($pharmacy_plan)){{'('.$pharmacy_plan->tariff_after_hours.'$)'}}@endif</span>                                           
                                            <div class="col-sm-12">
                                                <input class="form-control" type="number" step="0.1" min="0" name="tariff_after_hours" value="{{ $pharmacy->tariff_after_hours }}">
                                            </div>
                                        </div>
                                        <div class="col-xl-6 col-sm-6" style="margin: 10px 0;">                                          
                                            <span style="font-size:10px;width:100%;height: 25px;align-items: center;display: grid;white-space:normal;color: white;margin: 10px 0px;" class="badge badge-pill bg-dark">Markup Delivery With Fridge @if(!empty($pharmacy_plan)){{'('.$pharmacy_plan->tariff_fridge.'$)'}}@endif</span>                                           
                                            <div class="col-sm-12">
                                                <input class="form-control" type="number" step="0.1" min="0" name="tariff_fridge" value="{{ $pharmacy->tariff_fridge }}">
                                            </div>
                                        </div>
                                        <div class="col-xl-6 col-sm-6" style="margin: 10px 0;">                                          
                                            <span style="opacity:0;font-size:10px;width:100%;height: 25px;align-items: center;display: grid;white-space:normal;color: white;margin: 10px 0px;" class="badge badge-pill bg-light">1</span>                                           
                                            <div class="col-sm-12">
                                                <a href="#" onclick="if(confirm('Are you sure? You need save pharmacy info before load this page.')){window.location.href='/pharmacys/tariff_map/{{$pharmacy->id}}'}"><button type="button" class="btn btn-primary w-100">Show map tariff</button></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h5 style="background: #7a6fbe;color: #ffffff;padding: 5px;text-align: center;">Hours:</h5>
                                    <label class="col-form-label p-0" style="text-align: center;font-weight:bold;width: 100%;">Monday</label> 
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="example-time-input" class="col-form-label">Opens at</label> 
                                            <input class="form-control" type="time" @if(isset($pharmacy->schedule[0]) && !empty($pharmacy->schedule[0]->open)){{'value='.$pharmacy->schedule[0]->open}}@endif id="example-time-input" name="schedule_open[0]">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="example-time-input2" class="col-form-label">Closes at</label> 
                                            <input class="form-control" type="time" @if(isset($pharmacy->schedule[0]) && !empty($pharmacy->schedule[0]->close)){{'value='.$pharmacy->schedule[0]->close}}@endif id="example-time-input2" name="schedule_close[0]">    
                                        </div>
                                    </div>
                                    <hr>
                                    <label class="col-form-label p-0" style="text-align: center;font-weight:bold;width: 100%;">Tuesday</label> 
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="example-time-input3" class="col-form-label">Opens at</label> 
                                            <input class="form-control" type="time" @if(isset($pharmacy->schedule[1]) && !empty($pharmacy->schedule[1]->open)){{'value='.$pharmacy->schedule[1]->open}}@endif id="example-time-input3" name="schedule_open[1]">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="example-time-input4" class="col-form-label">Closes at</label> 
                                            <input class="form-control" type="time" @if(isset($pharmacy->schedule[1]) && !empty($pharmacy->schedule[1]->close)){{'value='.$pharmacy->schedule[1]->close}}@endif id="example-time-input4" name="schedule_close[1]">    
                                        </div>
                                    </div>
                                    <hr>
                                    <label class="col-form-label p-0" style="text-align: center;font-weight:bold;width: 100%;">Wednesday</label> 
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="example-time-input5" class="col-form-label">Opens at</label> 
                                            <input class="form-control" type="time" @if(isset($pharmacy->schedule[2]) && !empty($pharmacy->schedule[2]->open)){{'value='.$pharmacy->schedule[2]->open}}@endif id="example-time-input5" name="schedule_open[2]">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="example-time-input6" class="col-form-label">Closes at</label> 
                                            <input class="form-control" type="time" @if(isset($pharmacy->schedule[2]) && !empty($pharmacy->schedule[2]->close)){{'value='.$pharmacy->schedule[2]->close}}@endif id="example-time-input6" name="schedule_close[2]">    
                                        </div>
                                    </div>
                                    <hr>
                                    <label class="col-form-label p-0" style="text-align: center;font-weight:bold;width: 100%;">Thursday</label> 
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="example-time-input7" class="col-form-label">Opens at</label> 
                                            <input class="form-control" type="time" @if(isset($pharmacy->schedule[3]) && !empty($pharmacy->schedule[3]->open)){{'value='.$pharmacy->schedule[3]->open}}@endif id="example-time-input7" name="schedule_open[3]">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="example-time-input8" class="col-form-label">Closes at</label> 
                                            <input class="form-control" type="time" @if(isset($pharmacy->schedule[3]) && !empty($pharmacy->schedule[3]->close)){{'value='.$pharmacy->schedule[3]->close}}@endif id="example-time-input8" name="schedule_close[3]">    
                                        </div>
                                    </div>
                                    <hr>
                                    <label class="col-form-label p-0" style="text-align: center;font-weight:bold;width: 100%;">Friday</label> 
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="example-time-input9" class="col-form-label">Opens at</label> 
                                            <input class="form-control" type="time" @if(isset($pharmacy->schedule[4]) && !empty($pharmacy->schedule[4]->open)){{'value='.$pharmacy->schedule[4]->open}}@endif id="example-time-input9" name="schedule_open[4]">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="example-time-input10" class="col-form-label">Closes at</label> 
                                            <input class="form-control" type="time" @if(isset($pharmacy->schedule[4]) && !empty($pharmacy->schedule[4]->close)){{'value='.$pharmacy->schedule[4]->close}}@endif id="example-time-input10" name="schedule_close[4]">    
                                        </div>
                                    </div>
                                    <hr>
                                    <label class="col-form-label p-0" style="text-align: center;font-weight:bold;width: 100%;">Saturday</label> 
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="example-time-input11" class="col-form-label">Opens at</label> 
                                            <input class="form-control" type="time" @if(isset($pharmacy->schedule[5]) && !empty($pharmacy->schedule[5]->open)){{'value='.$pharmacy->schedule[5]->open}}@endif id="example-time-input11" name="schedule_open[5]">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="example-time-input12" class="col-form-label">Closes at</label> 
                                            <input class="form-control" type="time" @if(isset($pharmacy->schedule[5]) && !empty($pharmacy->schedule[5]->close)){{'value='.$pharmacy->schedule[5]->close}}@endif id="example-time-input12" name="schedule_close[5]">    
                                        </div>
                                    </div>
                                    <hr>
                                    <label class="col-form-label p-0" style="text-align: center;font-weight:bold;width: 100%;">Sunday</label> 
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="example-time-input13" class="col-form-label">Opens at</label> 
                                            <input class="form-control" type="time" @if(isset($pharmacy->schedule[6]) && !empty($pharmacy->schedule[6]->open)){{'value='.$pharmacy->schedule[6]->open}}@endif id="example-time-input13" name="schedule_open[6]">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="example-time-input14" class="col-form-label">Closes at</label> 
                                            <input class="form-control" type="time" @if(isset($pharmacy->schedule[6]) && !empty($pharmacy->schedule[6]->close)){{'value='.$pharmacy->schedule[6]->close}}@endif id="example-time-input14" name="schedule_close[6]">    
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-12 col-sm-12">
                            <div class="card">
                                <div class="card-body" align="center">
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary btn-lg waves-effect waves-light" style="width: 30%;">Save</button>
                                     </div>                                  
                                </div>
                            </div>
                        </div>

                        <!-- end col -->
                    </div>
                        <!-- end col -->
                    </div>
                    <!-- end row -->
                    <script type='text/javascript'>
                    function encodeImageFileAsURL(element) {
                        if(element.files[0].size > 2097152){
                            alert("File is too big!");element.value = "";
                        }
                    }
                    </script>
                    <script src="https://maps.googleapis.com/maps/api/js?key={{config('app.googlemaps_apikey')}}&region=US&language=en&libraries=places"></script>
                    <script>
                        var input = document.getElementById('searchTextField');
                        var autocomplete = new google.maps.places.Autocomplete(input);

                        input.addEventListener('input', function () {
                        this.dataset.originalVal = this.value;
                        });
                        input.addEventListener('focus', function () {
                        this.value = input.dataset.originalVal ? input.dataset.originalVal : this.value;
                        });
                        function copyToClipboard(element) {
                            $(element).select();
                            document.execCommand("copy");
                        }
                    </script>
                    
@endsection

@section('footerScript')
<script src="{{ URL::asset('/js/select2.min.js')}}"></script>
<script>
    $("#plan_id").on("change",function(){
        if($("#plan_id").val()=='2'){
            $("#date_end_trial").show();
        } else {
            $("#date_end_trial").hide();
        }
    });
    var selectedArea = [@foreach($areas as $area) @if(in_array($area->id,$pharmacy_areas1) || in_array($area->id,$pharmacy_areas2) || in_array($area->id,$pharmacy_areas3))"{{$area->id}}", @endif @endforeach];
    function reInitArea() {
        $('#areas option').each(function(index){
            if($.inArray($(this).val(),selectedArea)>-1) {
                $(this).attr('hidden','hidden');
            } else {
                $(this).removeAttr('hidden')
            }
        });
        $('#areas2 option').each(function(index){
            if($.inArray($(this).val(),selectedArea)>-1) {
                $(this).attr('hidden','hidden');
            } else {
                $(this).removeAttr('hidden')
            }
        });
        $('#areas3 option').each(function(index){
            if($.inArray($(this).val(),selectedArea)>-1) {
                $(this).attr('hidden','hidden');
            } else {
                $(this).removeAttr('hidden')
            }
        });
        setTimeout(() => {
            $('#areas').select2("destroy").select2({templateResult: hideSelect2Option});
            $('#areas2').select2("destroy").select2({templateResult: hideSelect2Option});
            $('#areas3').select2("destroy").select2({templateResult: hideSelect2Option});
        }, 20);
    }
    function hideSelect2Option(data, container) {
        if(data.element) {
            $(container).addClass($(data.element).attr("class"));
            $(container).attr('hidden',$(data.element).attr("hidden"));
        }
        return data.text;
    }
    $('body').on("change",'#areas',function(){
        selectedArea = $('#areas').val().concat($('#areas2').val()).concat($('#areas3').val());
        setTimeout(() => {
            reInitArea();
        }, 20);
    });
    $('body').on("change",'#areas2',function(){
        selectedArea = $('#areas').val().concat($('#areas2').val()).concat($('#areas3').val());
        setTimeout(() => {
            reInitArea();
        }, 20);
    });
    $('body').on("change",'#areas3',function(){
        selectedArea = $('#areas').val().concat($('#areas2').val()).concat($('#areas3').val());
        setTimeout(() => {
            reInitArea();
        }, 20);
    });
    $( document ).ready(function() {
        $('#areas').select2();
        $('#areas2').select2();
        $('#areas3').select2();
        reInitArea();
    });
</script>
@endsection