@extends('layouts.master')

@section('title') {{$title}} @endsection

@section('headerCss')
    <!-- Responsive Table css -->
    <link href="{{ URL::asset('/libs/rwd-table/rwd-table.min.css')}}" rel="stylesheet" type="text/css" /> <!-- Bootstrap Css -->
    <style>
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
    </style>
@endsection
@section('content')
 <!-- start page title -->

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                            <div class="card-body">
                                    <form method="post" enctype="multipart/form-data">
                                        @csrf
                                        <input type="hidden" name="save" value="1">
                                        @if($alert!='') 
                                            <div class="alert alert-danger" role="alert">{{ $alert }}</div>
                                        @endif
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Name Tariff Plan</label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" required name="name" value="{{$plan->name}}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Monthly Order Rate</label>
                                            <div class="col-sm-10">
                                                <input type="number" step="1" min="1" class="form-control" required name="order_rate" value="{{$plan->order_rate}}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Default Tariff</label>
                                            <div class="col-sm-10">
                                                <input type="number" step="0.01" min="0" class="form-control" required name="tariff" value="{{$plan->tariff}}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Markup Time Next Day</label>
                                            <div class="col-sm-10">
                                                <input type="number" step="0.01" min="0" class="form-control" required name="tariff_next_day" value="{{$plan->tariff_next_day}}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Markup Time Same Day</label>
                                            <div class="col-sm-10">
                                                <input type="number" step="0.01" min="0" class="form-control" required name="tariff_same_day" value="{{$plan->tariff_same_day}}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Markup Time Asap</label>
                                            <div class="col-sm-10">
                                                <input type="number" step="0.01" min="0" class="form-control" required name="tariff_asap" value="{{$plan->tariff_asap}}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Markup Time After Hours</label>
                                            <div class="col-sm-10">
                                                <input type="number" step="0.01" min="0" class="form-control" required name="tariff_after_hours" value="{{$plan->tariff_after_hours}}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Markup Delivery With Fridge</label>
                                            <div class="col-sm-10">
                                                <input type="number" step="0.01" min="0" class="form-control" required name="tariff_fridge" value="{{$plan->tariff_fridge}}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Second Area</label>
                                            <div class="col-sm-10">
                                                <input type="number" step="0.01" min="0" class="form-control" required name="tariff_area2" value="{{$plan->tariff_area2}}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Third Area</label>
                                            <div class="col-sm-10">
                                                <input type="number" step="0.01" min="0" class="form-control" required name="tariff_area3" value="{{$plan->tariff_area3}}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Far Area</label>
                                            <div class="col-sm-10">
                                                <input type="number" step="0.01" min="0" class="form-control" required name="tariff_area_more" value="{{$plan->tariff_area_more}}">
                                            </div>
                                        </div>
                                        <button type="submit" style="margin-top:10px;" class="btn btn-primary">Save</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- end col -->
                    </div>
                    <!-- end row -->

@endsection

@section('footerScript')
   
@endsection