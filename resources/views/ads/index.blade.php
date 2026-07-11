@extends('layouts.master')

@section('title') ADS @endsection

@section('headerCss')
<style>
.rep-menu a {
    font-size: 14px;
    line-height: 30px;
    color: black;
    display: block;
    margin: 10px 0;
    padding: 5px 0 5px 20px;

}
.rep-menu a:hover {
    background: #7a6fbe;
    color: #ffffff !important;
}
.rep-menu i {
    font-size: 16px;
    margin: 0px 7px;
}

</style>
        

@endsection

@section('content')
 <!-- start page title -->
                    <div class="row">

                   
                    </div>
                    <!-- end page title -->                    
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="card">
                                <div class="card-body">
                                <h4 class="card-title">ADS</h4>
                                        <div class="row">
                                         
                                            <div class="col-lg-6">
                                                <div class="card">
                                                    <div class="card-body">
                                                      <h4>New</h4>
                                                      <div class="ads">

                                                      <form action="#">
                                                        <div class="mb-3">
                                                            <label class="form-label" for="default-input">Title</label>
                                                            <input class="form-control" type="text" id="default-input" placeholder="max 120 characters">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="default-input">Link</label>
                                                            <input class="form-control" type="text" id="default-input" placeholder="https://website.com">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-lable">Upload new banner</label>
                                                            <input type="file" class="filestyle" data-buttonname="btn-secondary" id="filestyle-0" tabindex="-1" style="position: absolute; clip: rect(0px, 0px, 0px, 0px);"><div class="bootstrap-filestyle input-group"><div name="filedrag" style="position: absolute; width: 100%; height: 33.5px; z-index: -1;"></div><input type="text" class="form-control " placeholder="" disabled="" style="border-top-left-radius: 0.25rem; border-bottom-left-radius: 0.25rem;"> <span class="group-span-filestyle input-group-btn" tabindex="0"><label for="filestyle-0" style="margin-bottom: 0;" class="btn btn-secondary "><span class="buttonText">Choose file</span></label></span></div>
                                                        </div>
                                                        <div class="mt-4 text-center">
                                                            <button type="submit" class="btn btn-primary w-md">Submit</button>
                                                        </div>
                                                    </form>
                                                      </div>
                                                       
                                                    </div>
                                                </div>
                                            </div><!-- end col --> 
                                            <div class="col-lg-6">
                                                <div class="card">
                                                    <div class="card-body">
                                                      <h4>Current </h4>
                                                      
                                                      <div class="ads row">
                                                        <div class="col-lg-12 mt-2">
                                                      <img src="https://cp.a2brx.com/public/images/ads/prev.png" width="100%" alt="Benner"></div>
                                                      
                                                      <div class="col-lg-6 mt-4">
                                                            <div class="alert alert-info text-center" role="alert">
                                                                    <strong>Views: </strong> 0
                                                            </div>
                                                      </div>
                                                       <div class="col-lg-6 mt-4">
                                                            <div class="alert alert-success text-center" role="alert">
                                                                <strong>Clicks: </strong> 0
                                                            </div>
                                                        </div>
                                                    </div>

                                                    </div>
                                                </div>
                                            </div><!-- end col -->  
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
