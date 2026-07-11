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
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Orders Created From</label>
                                            <div class="col-sm-10">
                                                <input type="date" class="form-control" required name="date_from" value="{{$invoice->date_from}}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Orders Created To</label>
                                            <div class="col-sm-10">
                                                <input type="date" class="form-control" required name="date_to" value="{{$invoice->date_to}}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Exclusion Orders ID</label>
                                            <div class="col-sm-10 rx-list">
                                                <textarea name="invoice_exclusions" rows="14" class="form-control">@foreach($invoice_exclusions as $key=>$invoice_exclusion){{$invoice_exclusion->order_id}}
@endforeach</textarea>
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