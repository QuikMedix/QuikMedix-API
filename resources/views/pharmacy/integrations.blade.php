@extends('layouts.master')

@section('title') {{$title}} @endsection

@section('headerCss')
<style>
.integrations td {
    vertical-align: middle;
}
.btn-group.pull-right {
    display: block;
    text-align: end;
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
                                    <div class="btn-toolbar d-block mb-3">
                                        <div class="btn-group dropdown-btn-group pull-right"><div class="app-search d-none d-lg-inline-block" style="margin-right: 10px;padding:0px;"><div class="position-relative"><form name="search-form"><input type="text" class="form-control" id="search" name="search" placeholder="Search..."><span class="fa fa-search" style="cursor:pointer;" onclick="document.forms['search-form'].submit();"></span></form></div></div></div>
                                    </div>
                                    <div class="table-responsive mb-0" data-pattern="priority-columns">
                                        <table class="table table-striped integrations">
                                            <thead>
                                                <tr>
                                                    <th>Pharmacy</th>
                                                    <th data-priority="1">Integrations</th>
                                                    <th data-priority="3">Data</th>
                                                    <th data-priority="1">Status</th>
                                                    <th data-priority="3">Agreement</th>                                                   
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($pharmacys as $pharmacy)
                                                <tr>
                                                    <td>{{$pharmacy->name}}</span></td>
                                                    <td><img src="https://test.a2brx.com/images/micromerchantsystem.png" alt="micromerchantsystem" class="mr-3" height="40"></td>
                                                    <td>
                                                        NPI number: {{$pharmacy->npi}} </br>
                                                        Merchant Api Key: {{$pharmacy->merchantKey}}</br>
                                                        Merchant Api Secret: <span style="cursor:pointer;" data-secret="{{$pharmacy->merchantSecret}}" onclick="$(this).text($(this).data('secret'))">************************** <i class="mdi mdi-eye-off"></i></span></br>
                                                    </td>
                                                    <td class="text-success"><span class="badge bg-success text-white font-size-12 p-2">Active <i class="mdi mdi-check-circle-outline"></i></span></td>
                                                    <td>
                                                        @if(empty($pharmacy->merchant_agreement))
                                                        <form method="POST" enctype="multipart/form-data" class="form-group mb-0">
                                                            @csrf
                                                            <input type="hidden" name="pharmacy_id" value="{{$pharmacy->id}}">
                                                            <input onchange="$(this).parent().submit()" type="file" class="filestyle" accept="application/pdf" name="merchant_agreement" data-input="false" data-buttonname="btn-secondary" id="filestyle-1" tabindex="-1" style="position: absolute; clip: rect(0px, 0px, 0px, 0px);"><div class="bootstrap-filestyle input-group"><div name="filedrag" style="position: absolute; width: 100%; height: 33.5px; z-index: -1;"></div><span class="group-span-filestyle " tabindex="0"><label for="filestyle-1" style="margin-bottom: 0;" class="btn btn-warning waves-effect waves-light"><span class="buttonText">Upload PDF <i class="mdi mdi-file-pdf-outline"></i></span></label></span></div>
                                                        </form>
                                                        @else
                                                        <a class="btn btn-primary waves-effect waves-light" target="_blank" href="{{$pharmacy->merchant_agreement}}" role="button">Open PDF <i class="mdi mdi-file-pdf-outline"></i></a>
                                                        @endif
                                                    </td> 
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
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
                        </div>
                        <!-- end col -->
                    </div>
                    <!-- end row -->

@endsection

@section('footerScript')
            
@endsection