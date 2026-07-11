@extends('layouts.master')

@section('title') Feedback @endsection

@section('headerCss')
    <link rel="stylesheet" href="https://cp.a2brx.com/feedb/custom.css">
	<link rel="stylesheet" href="https://cp.a2brx.com/feedb/vendors.min.css">
	<link rel="stylesheet" href="https://cp.a2brx.com/feedb/grey.css">
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
                                    <div id="wizard_container">
                                        <form name="start" id="wrapped" method="POST" enctype="multipart/form-data">
                                            <input id="website" name="website" type="text" value="">
                                            <div id="middle-wizard">
                                                <div class="step" data-state="branchtype">
                                                    <div class="question_title">
                                                        <h3>Feedback</h3>
                                                        <p>If you are reporting a problem, please remember to provide as much information that is relevant to the issue as possible. </p>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-lg-2 animated zoomIn delay-fast">
                                                        </div>
                                                        <div class="col-lg-4 animated zoomIn delay-normal">
                                                            <div class="item">
                                                                <input id="answer_2" name="branch_1_group_1" type="radio" value="Claim" class="required">
                                                                <label for="answer_2"><img src="https://cp.a2brx.com/feedb/f_icon_1.svg" alt=""><strong>Claim</strong> 
                                                               </label>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-4 animated zoomIn delay-medium">
                                                            <div class="item">
                                                                <input id="answer_3" name="branch_1_group_1" type="radio" value="new-features" class="required">
                                                                <label for="answer_3"><img src="https://cp.a2brx.com/feedb/f_icon_2.svg" alt=""><strong>Improved functionality</strong>
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-2 animated zoomIn delay-fast">
                                                        </div>
                                                    </div>
                                                </div>
                                            
                                                <div class="branch" id="Claim">
                                                    <div class="step" data-state="end">
                                                        <div class="question_title">
                                                            <h3>Claim</h3>
                                                            <p>Describe the problem in more detail</p>
                                                        </div>
                                                        <div class="row justify-content-center">
                                                            <div class="col-lg-5 animated zoomIn delay-fast">
                                                                <div class="box_general">
                                                                    <div class="form-group short">
                                                                        <label><input type="checkbox" name="branch_2_2_answers[]" class="icheck" value="Problem with the driver">Problem with the driver</label>
                                                                    </div>
                                                                    <div class="form-group short">
                                                                        <label><input type="checkbox" name="branch_2_2_answers[]" class="icheck" value="Problem with the customer">Problem with the customer</label>
                                                                    </div>
                                                                    <div class="form-group short">
                                                                        <label><input type="checkbox" name="branch_2_2_answers[]" class="icheck" value="Difficulties with ordering">Difficulties with ordering</label>
                                                                    </div>
                                                                    <div class="form-group short">
                                                                        <label><input type="checkbox" name="branch_2_2_answers[]" class="icheck" value="Problems with payment">Problems with payment</label>
                                                                    </div>
                                                                    <div class="form-group short">
                                                                        <label><input type="checkbox" name="branch_2_2_answers[]" class="icheck" value="Any CMS">Wrong statistics</label>
                                                                    </div>
                                                                    <div class="form-group short">
                                                                        <label><input type="checkbox" name="branch_2_2_answers[]" class="icheck" value="Wrong calculation">Wrong calculation</label>
                                                                    </div>
                                                                    <div class="form-group short">
                                                                        <label><input type="checkbox" name="branch_2_2_answers[]" class="icheck" value="Copayment">Copayment</label>
                                                                    </div>
                                                                    <hr>                                                     
                                                                    <textarea class="form-control" style="height:100px; margin-bottom:0;" placeholder="Detailed..." name="cms_development_notes"></textarea>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="branch" id="new-features">
                                                    <div class="step" data-state="end">
                                                        <div class="question_title">
                                                            <h3>Improved functionality</h3>
                                                            <p>What features would you like us to add?</p>
                                                        </div>
                                                        <div class="row justify-content-center">
                                                            <div class="col-lg-5 animated zoomIn delay-fast">
                                                                <div class="box_general">
                                                                    <div class="form-group short">
                                                                        <label><input type="checkbox" name="branch_2_1_answers[]" class="icheck" value="Application for the customer">Application for the customer</label>
                                                                    </div>
                                                                    <div class="form-group short">
                                                                        <label><input type="checkbox" name="branch_2_1_answers[]" class="icheck" value="Creating Orders">Creating Orders</label>
                                                                    </div>
                                                                    <div class="form-group short">
                                                                        <label><input type="checkbox" name="branch_2_1_answers[]" class="icheck" value="Payment Options">Payment Options</label>
                                                                    </div>
                                                                    <div class="form-group short">
                                                                        <label><input type="checkbox" name="branch_2_1_answers[]" class="icheck" value="Other">Other</label>
                                                                    </div>
                                                                    <hr>
                                                                    <textarea class="form-control" style="height:100px; margin-bottom:0;" placeholder="Detailed..." name="html_development_notes"></textarea>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div>
                                                    
                                                    <div class="submit step" id="end">
                                                        <div class="question_title">
                                                            <h3>We will answer as soon as possible.</h3>
                                                            <p></p>
                                                        </div>
                                                        <div class="row justify-content-center">
                                                            <div class="col-lg-5 animated zoomIn delay-fast">
                                                                <div class="box_general">
                                                                    <div class="form-group">
                                                                        <input type="text" name="first_last_name" class="required form-control" placeholder="Your name *">
                                                                    </div>
                                                                    <!--<div class="form-group">
                                                                        <input type="email" name="email" class="form-control" placeholder="Email *">
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <input type="text" name="telephone" class="form-control" placeholder="Phone Number *">
                                                                    </div> -->
                                                                    <div class="form-group">
                                                                        <input type="text" name="telephone"  value="{{Auth::user()->pharmacy_name()}}" class="form-control" placeholder="{{Auth::user()->pharmacy_name()}}" style="opacity: .6;" readonly>
                                                                    </div>
                                                                    <div class="form-group add_bottom_30">
                                                                        <label>Optional File upload<br><small>(Files accepted: .jpg,.png,.pdf,.doc,.docx)</small></label>
                                                                        <div class="fileupload">
                                                                            <input type="file" name="fileupload" accept="image/*,.pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                                                                        </div>
                                                                    </div>
                                                                    <hr>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="bottom-wizard">
                                                <button type="button" name="backward" class="backward">Back </button>
                                                <button type="button" name="forward" class="forward">Next</button>
                                                <button type="submit" name="process" class="submit">Submit</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- end col -->
                    </div>
                    <!-- end row -->

@endsection

@section('footerScript')
    <script src="https://cp.a2brx.com/feedb/jquery-3.6.1.min.js"></script>
    <script src="https://cp.a2brx.com/feedb/common_scripts.min.js"></script>
	<script src="https://cp.a2brx.com/feedb/main.js"></script>
	<script src="https://cp.a2brx.com/feedb/file-validator.js"></script>
	<script src="https://cp.a2brx.com/feedb/wizard_func_multiple_branch_fileupload.js"></script>
@endsection
