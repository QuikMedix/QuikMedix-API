@extends('layouts.master')

@section('title') Pharmacys @endsection

@section('headerCss')

    <!-- Responsive Table css -->
    <link href="{{ URL::asset('/libs/rwd-table/rwd-table.min.css')}}" rel="stylesheet" type="text/css" /> <!-- Bootstrap Css -->
    <link rel="stylesheet" href="{{ URL::asset('/css/bootstrap-lib.min.css')}}">
    <link href="{{ URL::asset('/css/bootstrap-datepicker.min.css')}}" rel="stylesheet">
    <style>
    .btn-group.pull-right {
        float: right;
    }
    .text-balance {
    background: #6b6691;
    color: white;
    text-transform: uppercase;
    margin: 0px 10px;
    box-shadow: 0 -3px 31px 0 rgb(64 59 59 / 5%), 0 6px 20px 0 rgb(58 57 57 / 20%);
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
    .img-thumbnail {
        height: 6rem;
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
                                <div class="card-body" style="margin-bottom:30px;">
                                    <div class="btn-toolbar d-block">
                                        <div class="btn-group focus-btn-group"></div>
                                        <div class="btn-group dropdown-btn-group pull-right"></div>
                                    </div>
                                    <div class="row">
                                    @foreach ($pharmacys as $pharmacy)
                                    <div class="col-xl-3 col-md-6">
                                        <div class="card directory-card">
                                            <div>
                                                <div class="directory-bg text-center" @if(!empty($pharmacy->image_front)) style="background-image:url('{{$pharmacy->image_front}}');" @endif>
                                                    <div class="directory-overlay">
                                                        @if(!empty($pharmacy->logo))
                                                        <img class="rounded-circle avatar-lg img-thumbnail" src="{{$pharmacy->logo}}" alt="Logo">
                                                        @else
                                                        <img class="rounded-circle avatar-lg img-thumbnail" src="https://test.a2brx.com/images/users/logo_ph.jpg" alt="Logo">
                                                        @endif
                                                    </div>
                                                </div>
                                                    <div class="directory-content text-center p-4">
                                                        @if($pharmacy->isblocked == 1)
                                                                            <span class="badge badge-dark float-right">Blocked</span>
                                                                        @elseif($pharmacy->isactive == 0)
                                                                            <span class="badge badge-warning float-right">No active</span>
                                                                        @else 
                                                                            <span class="badge badge-info float-right">Active</span>
                                                        @endif                                     
                                                        <div style="padding: 0 10px;"> <p class=" mt-4" style="margin-bottom: 0.4rem;font-weight: bold;background: #2b3a4a;color: #ffffff;border-radius: 5px;">{{$pharmacy->name}}</p>                                     
                                                        <p class="text-muted" style="margin-bottom: 0.2rem;"><i class="mdi mdi-google-maps"></i> {{$pharmacy->address}}</p>
                                                        <p class="text-muted" style="margin-bottom: 0.2rem;"><i class="mdi mdi-phone-in-talk-outline"></i> {{$pharmacy->phone}}</p>
                                                        <p class="text-muted" style="margin-bottom: 0.2rem;"><i class="mdi mdi-gmail"></i> {{$pharmacy->email}}</p>    </div> 
                                                        <ul class="list-inline mb-0 mt-4">
                                                            <li class="list-inline-item mb-1">
                                                                <a title="" data-placement="top" class="btn btn-outline-dark btn-sm waves-effect waves-light" data-toggle="tooltip" class="tooltips" href="/pharmacy/{{ $pharmacy->id }}/users" data-original-title="Pharmacists"><i class="mdi mdi-smart-card"></i> Pharmacists</a>
                                                            </li>
                                                            <li class="list-inline-item mb-1">
                                                                <a title="" data-placement="top" class="btn btn-outline-dark btn-sm waves-effect waves-light" data-toggle="tooltip" class="tooltips" href="/drivers/{{ $pharmacy->id }}/users" data-original-title="Drivers"><i class="mdi mdi-car"></i> Drivers</a>
                                                            </li>
                                                            <li class="list-inline-item mb-1">
                                                                <a title="" data-placement="top" class="btn btn-outline-dark btn-sm waves-effect waves-light" data-toggle="tooltip" class="tooltips" href="/patients/{{ $pharmacy->id }}" data-original-title="Patients"><i class="mdi mdi-emoticon-happy-outline"></i> Patients</a>
                                                            </li>
                                                            <li class="list-inline-item mb-1">
                                                                <a title="" data-placement="top" class="btn btn-outline-dark btn-sm waves-effect waves-light" data-toggle="tooltip" class="tooltips" href="/facilitys/{{ $pharmacy->id }}" data-original-title="Facilitys"><i class="mdi mdi-emoticon-happy-outline"></i> Facilitys</a>
                                                            </li>
                                                            <li class="list-inline-item mb-1">
                                                                <a title="" data-placement="top" class="btn btn-outline-dark btn-sm waves-effect waves-light" data-toggle="tooltip" class="tooltips" href="/orders/{{ $pharmacy->id }}" data-original-title="Orders"><i class="mdi mdi-medical-bag"></i> Orders</a>
                                                            </li>
                                                            <li class="list-inline-item mb-1">
                                                                <a title="" data-placement="top" class="btn btn-outline-dark btn-sm waves-effect waves-light" data-toggle="tooltip" class="tooltips" href="/orders/{{ $pharmacy->id }}/statistic" data-original-title="Orders Map Statistic"><i class="mdi mdi-google-maps"></i> Map Statistic</a>
                                                            </li>
                                                            <li class="list-inline-item mb-1">
                                                                <a title="" data-placement="top" class="btn btn-outline-dark btn-sm waves-effect waves-light" data-toggle="tooltip" class="tooltips" href="/billing/{{ $pharmacy->id }}" data-original-title="Billing"><i class="mdi mdi-finance"></i> Billing</a>
                                                            </li>
                                                            
                                                        </ul>  
                                                        
                                                        <p class="text-balance mt-4 mb-2 pt-1 pb-1">Current Balance: {{($pharmacy->balance<0)?'-$'.round(abs($pharmacy->balance),2):'$'.round($pharmacy->balance,2)}}</p> <hr style="margin-top: 0rem;">

                                                        @if($pharmacy->isactive == 0)
                                                            <form method="post" style="display: inline-block;">
                                                                @csrf
                                                                <input type="hidden" name="user_id" value="{{$pharmacy->id}}">
                                                                <input type="hidden" name="activate" value="1">
                                                                <button class="btn btn-sm btn-success">Activate</button>
                                                            </form>
                                                        @endif
                                                        <a href="/pharmacys/edit/{{ $pharmacy->id }}"><button class="btn btn-sm btn-warning">Edit</button></a>
                                                        @if($pharmacy->isblocked == 1)
                                                            <form method="post" style="display: inline-block;">
                                                                @csrf
                                                                <input type="hidden" name="user_id" value="{{$pharmacy->id}}">
                                                                <input type="hidden" name="unblock" value="1">
                                                                <button class="btn btn-sm btn-success" type="button" onclick="if(confirm('Are you sure?')){$(this).parent('form').submit();}">Unblock</button>
                                                            </form>
                                                        @else 
                                                            <form method="post" style="display: inline-block;">
                                                                @csrf
                                                                <input type="hidden" name="user_id" value="{{$pharmacy->id}}">
                                                                <input type="hidden" name="block" value="1">
                                                                <button class="btn btn-sm btn-danger" type="button" onclick="if(confirm('Are you sure?')){$(this).parent('form').submit();}">Block</button>
                                                            </form>
                                                        @endif
                                                        @if($pharmacy->isactiveuser === 0)
                                                            <form method="post" style="display: inline-block;">
                                                                @csrf
                                                                <input type="hidden" name="user_id" value="{{$pharmacy->admin_id}}">
                                                                <input type="hidden" name="activateuser" value="1">
                                                                <button class="btn btn-sm btn-success">Activate Medic</button>
                                                            </form>
                                                        @endif
                                                         <!-- <form method="post" class="filter-form" style="display: inline-block;">
                                                            @csrf
                                                            <input type="hidden" name="user_id" value="{{$pharmacy->id}}">
                                                            <input type="hidden" name="remove" value="1">
                                                            <button class="btn btn-sm btn-dark" type="button" onclick="if(confirm('Are you sure?')){$(this).parent('form').submit();}">Remove</button>
                                                        </form> -->
                                                    </div>
                                                </div>
                                            </div>
                                    </div>
                                    @endforeach
                                </div>

                                </div>
                                <div style="position: absolute;text-align: center;width: 100%;bottom: 10px;">Pages: 
                                    @foreach ($pages as $page)
                                        <form style="display: inline-block;">
                                            <input type="hidden" name="page" value="{{ $page['id'] }}">
                                            <input type="hidden" name="search" value="{{ $search }}">
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
                    <button class="btn btn-primary filter_fix" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasLeft" aria-controls="offcanvasLeft">Filter <i class="mdi mdi-filter"></i></button>
                    <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasLeft" aria-labelledby="offcanvasLeftLabel" aria-modal="true" role="dialog">
                        <div class="offcanvas-header">
                            <h5 id="offcanvasLeftLabel">Filter Form</h5>
                            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                        </div>
                        <div class="offcanvas-body">
                            <form method="GET" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label>Date Created</label>
                                    <div>
                                        <div class="input-daterange input-group" data-date-format="mm/dd/yyyy" data-date-autoclose="true" data-provide="datepicker">
                                            <input type="text" class="form-control" name="start" value="{{$filter['start']}}" autocomplete="off">
                                            <input type="text" class="form-control" name="end" value="{{$filter['end']}}" autocomplete="off">
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="time">Advanced Property</label>
                                    <select class="select2 form-control select2-multiple" name="advanced" data-placeholder="Choose ...">
                                        <option value="">-----</option>
                                        <option value="without_orders" @if($filter['advanced']=='without_orders'){{'selected'}}@endif>Inactive pharmacies (week)</option>
                                        <option value="pharmacys_balance" @if($filter['advanced']=='pharmacys_balance'){{'selected'}}@endif>Pharmacies with debt</option>
                                        <option value="pharmacys_blocked" @if($filter['advanced']=='pharmacys_blocked'){{'selected'}}@endif>Pharmacies with blocked balance</option>
                                        <option value="pharmacys_blocked_permanently" @if($filter['advanced']=='pharmacys_blocked_permanently'){{'selected'}}@endif>Pharmacies with blocked permanently</option>
                                    </select>
                                </div>
                                <input type="hidden" name="filter" value="1">
                                <button class="btn btn-primary w-100">Filter <i class="mdi mdi-filter-plus"></i></button>
                            </form>
                            <form method="GET">
                                <button class="btn btn-outline-primary w-100 mt-3" type="submit">Clear filter <i class="mdi mdi-filter-remove"></i></button>
                            </form>
                        </div>
                    </div>
@endsection

@section('footerScript')
<!-- Responsive Table js -->
<script src="{{ URL::asset('/libs/rwd-table/rwd-table.min.js')}}"></script>
<script src="{{ URL::asset('/js/bootstrap-datepicker.min.js')}}"></script>
<!-- Init js -->
<script src="{{ URL::asset('/js/pages/table-responsive.init.js')}}"></script>
<script>
    $(document).ready(function(){
        $('.select2').selectize();
    });
</script>
@endsection