<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8" />
    <title> @yield('title')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="A2BRx Control Panel" name="description" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ URL::asset('/images/favicon.ico')}}">
    <!-- Bootstrap Css -->
    <link href="{{ URL::asset('/css/bootstrap.min.css?ver=4') }}" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="{{ URL::asset('/css/icons.min.css')}}" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="{{ URL::asset('/css/app.min.css')}}" id="app-style" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.12.6/css/selectize.bootstrap3.min.css" integrity="sha256-ze/OEYGcFbPRmvCnrSeKbRTtjG4vGLHXgOqsyLFTRjg=" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">   
    <link rel="stylesheet" href="https://cp.a2brx.com/public/libs/magnific-popup/magnific-popup.min.css" type="text/css" />   
    <link rel="stylesheet" href="/css/sweetalert2.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,200;1,300;1,400;1,500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ URL::asset('/css/bootstrap-lib.min.css')}}">
<style>
    .newmodal {
    width: 100vw;
    max-width: none;
    height: 100%;
    margin: 0;
    }
    .newmodal .modal-content {
    height: 100%;
    border: 0;
    border-radius: 0;
    }
    .modal-backdrop.show{
        display: none;
    }
    .modal-content .form-control {
    padding: 29px 30px;
    font-size: 21px;
    }
    .bgmodal {
    background: #ecf0f3;
    }
    .bgmodal .result {
    min-height: 90px;
    box-shadow: 5px 5px 15px #D1D9E6, -5px -5px 15px #ffffff;
    background: linear-gradient(145deg, #e2e8ec, #ffffff);
    padding: 20px;
    border-radius: 15px;
    color: black;
    }
    .bgmodal .sblock{
    min-height: 90px;
    box-shadow: 5px 5px 15px #D1D9E6, -5px -5px 15px #ffffff;
    background: linear-gradient(145deg, #e2e8ec, #ffffff);
    padding: 20px;
    border-radius: 15px;
    color: black;
    }
    .bgmodal .orderm .badge {
    color: #7a6fbe;
    }
    .bgmodal .patientm .badge {
    color: #00ac22;
    }
    .bgmodal .rxm .badge {
    color: #eb00e4;
    }
    .bgmodal .badge {
    font-size: 14px;
    width: 111px;
    white-space: normal;
    padding: 5px;
    color: #eb00e4;
    box-shadow: 5px 5px 15px #D1D9E6, -5px -5px 15px #ffffff;
    background-color: #fff;
    position: absolute;
    top: 20px;
    left: 20px;
    }
    .bgmodal h3 {
    display: inline-block;
    margin: -1px 0 0 136px;
   }
   .bgmodal td {
    padding: 0px 10px !important;
    font-size: 16px;
   }
   .bgmodal .btnm {
    color: white;
    background: #7a6fbe;
    box-shadow: 5px 5px 15px #d7d0ff, -5px -5px 15px #ffffff;
    padding: 10px;
    border-radius: 0.25rem;
    text-align: right;
    float: right;
}
.toast-info  {
    color: white;
    background: #7a6fbe;
}
</style>
</head>
<body  data-topbar="light" data-layout="horizontal">
    <div id="headerCss">
        <!-- headerCss -->
        @yield('headerCss')
    </div>
    <!-- Begin page -->
    <div id="layout-wrapper">
        
        @include('layouts/partials/navbar')

        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid" id="content">
                  <!-- content -->
                   @yield('content')
               </div>                
               <div id="loader" class="loading">
                    <i></i>
                    <i></i>
                    <i></i>
                </div>             
               
              <!-- <img src="https://i.pinimg.com/originals/67/2b/62/672b62d967f8d00d608d22f36c1831db.gif" id="loader" alt="load">-->
                <!-- end main content-->
            </div>
            <!-- END layout-wrapper -->
        </div>
        
        @include('layouts/partials/footer')

    </div>
    @if(Auth::user()->role=='superadmin' || Auth::user()->role=='admin' || Auth::user()->role=='medic')
    <div class="scan-qr">
        <img src="https://cp.a2brx.com/images/qr_new.svg" alt="qr scan">
    </div>    

    <div class="scan-preload">
        <i class="fas fa-times close-proload"></i>
        @csrf
        <input type="text" id="qr-code">
        <img src="https://cdn.dribbble.com/users/1187836/screenshots/6012802/13-qrcode.gif" alt="qr scan anim">
    </div>
    <div class="modal fade bs-example-modal-xl" tabindex="-1" aria-labelledby="myExtraLargeModalLabel" id="order_preview_popup" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title mt-0" id="myExtraLargeModalLabel">Order Info</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div>
    @endif
    <!-- JAVASCRIPT -->
    <script src="{{ URL::asset('/libs/jquery/jquery.min.js')}}"></script>
    <script src="{{ URL::asset('/libs/bootstrap/bootstrap.min.js')}}"></script>
    <script src="{{ URL::asset('/libs/metismenu/metismenu.min.js')}}"></script>
    <script src="{{ URL::asset('/libs/simplebar/simplebar.min.js')}}"></script>
    <script src="{{ URL::asset('/libs/node-waves/node-waves.min.js')}}"></script>
    <script src="{{ URL::asset('/libs/jquery-sparkline/jquery-sparkline.min.js')}}"></script>
    <script src="{{ URL::asset('/libs/magnific-popup/magnific-popup.min.js')}}"></script>
    <script src="{{ URL::asset('/js/pages/lightbox.init.js')}}"></script>
    <script src="{{ URL::asset('/js/selectize.min.js')}}"></script>
    <script src="{{ URL::asset('/js/toastr.min.js')}}"></script>
    <script src="{{ URL::asset('/js/sweetalert2.min.js')}}"></script>
    <script src="{{ URL::asset('/js/bootstrap-datepicker.min.js')}}"></script>
<!-- Responsive Table js -->
<script src="{{ URL::asset('/js/bootstrap.bundle.min.js')}}"></script>



    @if(((Auth::user()->role == 'superadmin' || Auth::user()->role == 'admin' || Auth::user()->role == 'dispadmin') || Auth::user()->role == 'logist') && Auth::user()->call_ready=='1')
    <script src="https://my.zadarma.com/webphoneWebRTCWidget/v8/js/loader-phone-lib.js?sub_v=61"></script>
    <script src="https://my.zadarma.com/webphoneWebRTCWidget/v8/js/loader-phone-fn.js?sub_v=61"></script>
    <script>
    var zadarma_keys = {!!Auth::user()->zadarma_key()!!};
    if (window.addEventListener) {
        window.addEventListener('load', function() {
        zadarmaWidgetFn(zadarma_keys.zadarma_key.key, zadarma_keys.zadarma_sip, 'rounded' /*square|rounded*/, 'en' /*ru, en, es, fr, de, pl, ua*/, true, "{left:'10px',top:'20px',z-index:'10000'}");
        }, false);
    } else if (window.attachEvent) {
        window.attachEvent('onload', function(){
        zadarmaWidgetFn(zadarma_keys.zadarma_key.key, zadarma_keys.zadarma_sip, 'rounded' /*square|rounded*/, 'en' /*ru, en, es, fr, de, pl, ua*/, true, "{left:'10px',top:'20px',z-index:'10000'}");
        });
    }
    </script>
    @endif
    <div id="footerScript">
        <!-- footerScript -->
        @yield('footerScript')
    </div>
    @if(Auth::user()->role=='medic')
    <script src="//code.tidio.co/jg7oyyyf1zpapjdhljgujy02dosvy60t.js" async></script>
    <script>
        document.tidioIdentify = {
            distinct_id: "{{Auth::user()->id}}",
            email: "{{Auth::user()->email}}",
            name: "{{Auth::user()->name}} {{Auth::user()->last_name}} ({{Auth::user()->pharmacy_name()}})",
            phone: "{{Auth::user()->phone}}"
        };
    </script>
    @endif
    <script src="https://js.pusher.com/beams/1.0/push-notifications-cdn.js"></script>
    <script>
        const beamsClient = new PusherPushNotifications.Client({
            instanceId: '686711de-e011-415d-9a38-c8a05adfaae2',
        });
        const beamsTokenProvider = new PusherPushNotifications.TokenProvider({
            url: "/pusher/beams-auth",
        });
        beamsClient.start()
        .then(() => beamsClient.setUserId("{{Auth::user()->id}}", beamsTokenProvider))
        .catch(console.error);
    </script>
    <!-- App js -->
    <script src="{{ URL::asset('/js/app.min.js?ver=3')}}"></script>
    <script src="{{ URL::asset('/js/jquery.maskedinput.min.js')}}" type="text/javascript"></script>
    <script>
        const synth = window.speechSynthesis;
        let voices;
        function speak(text) {
            voices = synth.getVoices();
            const message = new SpeechSynthesisUtterance();
            message.lang = "en-US";
            message.text = text;
            message.rate = 0.9;
            message.voice = voices[48]; //voices[32],voices[0],voices[11],voices[40]
            window.speechSynthesis.speak(message);
        }
        $(function(){
            @if(Auth::user()->role=='medic')
            @if((Auth::user()->pharmacy_balance()<0 && session()->has('flash_show_popup')) || Auth::user()->pharmacy_balance_ban())
            Swal.fire({
                title:"Couldn't process your last payment",
                html:"<p>Payment failed—please update ASAP.</p><p>We appreciate your attention and are happy to speak with you if you have any questions regarding your account.</p><p>Just call us at (855) 657-9595.</p>",
                icon:"warning",
                showCancelButton:!0,
                confirmButtonColor:"#29bbe3",
                cancelButtonColor:"#6c757d",
                confirmButtonText:"Review billing",
                cancelButtonText:"Skip"
            }).then(function(t){
                if (t.isConfirmed) {
                    window.location.href = "/billing/{{ Auth::user()->pharmacy_id }}";
                } else {
                    Swal.fire({
                        title:"Attention!",
                        html:"<p>Failure to update your payment method will result in limited-to-no access of your account.</p><p>To continue using our great tools and services, please check to make sure your payment method is up-to-date.</p>",
                        icon:"info",
                        confirmButtonText:"OK"
                    });
                }
            });
            @endif
            @endif
            $(document).on('click','.scan-qr',function() {
                $('.scan-preload').fadeIn(400);
                setInterval(() => {
                    if($('.scan-preload').css('display')=='block'){
                        $('#qr-code').focus();
                    }
                }, 100);
                toastr["info"]("Please scan the QR code!");
                speak("Please scan the QR code!");
            });
            $(document).on('click','.close-proload',function() {
                $('.scan-preload').fadeOut(400);
            });
            $(document).on('keyup','#qr-code',function(event) {
                if(event.code=='Enter' && $('#qr-code').val()!='') {
                    var qr = $('#qr-code').val();
                    if(isNumeric(qr.split('_')[0])){
                        $.get('/orders/preview/'+qr.split('_')[0])
                        .done(function(response) {
                            $('#qr-code').val("");
                            toastr.clear();
                            $('#order_preview_popup .modal-body').html(response);
                            $('#order_preview_popup').modal('show');
                        });
                    } else {
                        $.post('/drivers/qr', {code: qr, _token: $("input[name='_token']").val()}, null, 'json')
                        .done(function(response) {
                            $('#qr-code').val("");
                            if(response.user_id>0) {
                                window.open('/drivers/'+response.user_id+'/packages');
                                $('.scan-preload').fadeOut(400);
                                toastr.clear();
                            } else {
                                toastr["error"](response.message);
                                speak(response.message);
                            }
                        });
                    }
                }
            });
            function isNumeric(value) {
                return /^-?\d+$/.test(value);
            }
            $.fn.setCursorPosition = function(pos) {
                if($(this).val()=="") {
                    if ($(this).get(0).setSelectionRange) {
                        $(this).get(0).setSelectionRange(pos, pos);
                    } else if ($(this).get(0).createTextRange) {
                        var range = $(this).get(0).createTextRange();
                        range.collapse(true);
                        range.moveEnd('character', pos);
                        range.moveStart('character', pos);
                        range.select();
                    }
                }
            };
            $("#phone").mask("(999) 999-9999");
            $('#phone').click(function(){
                if(isNaN(Number.parseInt($(this).val()))) {
                    $(this).setCursorPosition(1);
                }
            });
            $("#home_phone").mask("(999) 999-9999");
            $('#home_phone').click(function(){
                if(isNaN(Number.parseInt($(this).val()))) {
                    $(this).setCursorPosition(1);
                }
            });
            $("#family_phone").mask("(999) 999-9999");
            $('#family_phone').click(function(){
                if(isNaN(Number.parseInt($(this).val()))) {
                    $(this).setCursorPosition(1);
                }
            });
            $("#family_phone2").mask("(999) 999-9999");
            $('#family_phone2').click(function(){
                if(isNaN(Number.parseInt($(this).val()))) {
                    $(this).setCursorPosition(1);
                }
            });
            toastr.options = {
                "closeButton": false,
                "debug": false,
                "newestOnTop": false,
                "progressBar": true,
                "positionClass": "toast-bottom-left",
                "preventDuplicates": false,
                "onclick": null,
                "showDuration": "3300",
                "hideDuration": "5000",
                "timeOut": "6000",
                "extendedTimeOut": "3000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            }
        });
    </script>
    @if(0)
    <script>//ajax loader
        $(document).ready(function(){
            if (window.history && window.history.pushState) {
                $(window).on('popstate', function() {
                    if(window.location.href.indexOf("#")==-1) {
                        var href = window.location.pathname+window.location.search;
                        var href2 = window.location.pathname;
                        if(href.indexOf("/routes-list/driver/")>-1) {
                            window.location.href = href;
                        } else {
                            var content = $("#content");
                            var headerCss = $("#headerCss");
                            var footerScript = $("#footerScript");
                            var title = $("#h-title");
                            var delim = (href.indexOf("?")>-1)?"&":"?";
                            $("body").addClass("load");
                            footerScript.html("");
                            try {
                                delete initAll;
                                delete google;
                            } catch (e) {
                                initAll = undefined;
                                google = undefined;
                            }
                            $("head").find("style").each(function( index ) {
                                if($(this).html().indexOf(".gm-")>-1 || $(this).html().indexOf(".ssQIHO")>-1){
                                    $(this).remove();
                                }
                            });
                            $("head").find("link").each(function( index ) {
                                if($(this).attr("href").indexOf("googleapis.")>-1){
                                    $(this).remove();
                                }
                            });
                            $("head").find("script").each(function( index ) {
                                if($(this).attr("src").indexOf("googleapis.")>-1){
                                    $(this).remove();
                                }
                            });
                            $.get(href+delim+"ajax=1", function(data, status){
                                document.title = data.title;
                                title.text(data.title);
                                $('.navbar-nav li .nav-link').removeClass("active");
                                $('.navbar-nav li').removeClass("active");
                                $('.navbar-nav li').each(function( index ) {
                                    if($(this).html().indexOf('href="'+href2+'"')>-1){
                                        $(this).find(".nav-link").addClass("active");
                                        $(this).addClass("active");
                                    }
                                });
                                headerCss.html(data.headerCss);
                                content.html(data.content);
                                footerScript.html(data.footerScript);
                                setTimeout(function(){
                                    $("body").removeClass("load");
                                },200);
                                if(typeof(initAll) != "undefined") {
                                    initAll();
                                }
                            }, "json").fail(function() {
                                window.location.href = href;
                            });
                        }
                    }
                });
            }
            $("body").on("click",'a',function(e){
                var href = $(this).attr("href");
                if(href.indexOf("#")==-1 && $(this).attr("target")!="_blank") {
                    if(href.indexOf("/routes-list/driver/")>-1) {
                        window.location.href = href;
                    } else {
                        var content = $("#content");
                        var headerCss = $("#headerCss");
                        var footerScript = $("#footerScript");
                        var title = $("#h-title");
                        var delim = (href.indexOf("?")>-1)?"&":"?";
                        var href2 = (href.indexOf("?")>-1)?href.split("?")[0]:href;
                        e.preventDefault();
                        $("body").addClass("load");
                        if(href2!=window.location.pathname) {
                            footerScript.html("");
                            try {
                                delete initAll;
                                delete google;
                            } catch (e) {
                                initAll = undefined;
                                google = undefined;
                            }
                            $("head").find("style").each(function( index ) {
                                if($(this).html().indexOf(".gm-")>-1 || $(this).html().indexOf(".ssQIHO")>-1){
                                    $(this).remove();
                                }
                            });
                            $("head").find("link").each(function( index ) {
                                if($(this).attr("href").indexOf("googleapis.")>-1){
                                    $(this).remove();
                                }
                            });
                            $("head").find("script").each(function( index ) {
                                if($(this).attr("src").indexOf("googleapis.")>-1){
                                    $(this).remove();
                                }
                            });
                        }
                        $.get(href+delim+"ajax=1", function(data, status){
                            document.title = data.title;
                            title.text(data.title);
                            $('.navbar-nav li .nav-link').removeClass("active");
                            $('.navbar-nav li').removeClass("active");
                            $('.navbar-nav li').each(function( index ) {
                                if($(this).html().indexOf('href="'+href2+'"')>-1){
                                    $(this).find(".nav-link").addClass("active");
                                    $(this).addClass("active");
                                }
                            });
                            if(href2!=window.location.pathname) {
                                headerCss.html(data.headerCss);
                                footerScript.html(data.footerScript);
                            }
                            content.html(data.content);
                            var newurl = window.location.protocol + "//" + window.location.host + href;
                            window.history.pushState({path:newurl},'',newurl);
                            setTimeout(function(){
                                $("body").removeClass("load");
                            },200);
                            if(typeof(initAll) != "undefined") {
                                initAll();
                            }
                        }, "json").fail(function() {
                            window.location.href = href;
                        });
                    }
                }
            });
            $('body').on("click",'.dropdown-toggle',function(e){
                // Kill click event:
                e.stopPropagation();
                // Toggle dropdown if not already visible:
                if ($(this).parent().find('.dropdown-menu').is(":hidden")){
                    $(this).dropdown('toggle');
                }
            });
            $("body").on("submit","#filter",function (e) {
                e.preventDefault();
                e.stopPropagation();
                var link = document.createElement('a');
                link.href = window.location.pathname+"?"+$(this).serialize();
                document.body.appendChild(link);
                link.click();    
                link.remove();
            });
            $("body").on("submit",".filter-form",function (e) {
                e.preventDefault();
                e.stopPropagation();
                var link = document.createElement('a');
                link.href = window.location.pathname+"?"+$(this).serialize();
                document.body.appendChild(link);
                link.click();    
                link.remove();
            });
        });
    </script>
    @endif
    @if((Auth::user()->role == 'superadmin' || Auth::user()->role == 'admin' || Auth::user()->role == 'dispadmin') || Auth::user()->role == 'logist')
    <script>
    $(".call_patient").on("click",function(){
        var phone = $(this).data("phone").replace("(","").replace(")","").replace(" ","").replace("-","");
        zdrmWebrtcPhone.setCallingNumber(phone);
        $(".zdrm-webphone-call-btn").get(0).click();
    });
    </script>
    @endif
    <script type='text/javascript'>
        $(document).ready(function () {
            $('#select-state').selectize({
                sortField: 'text'
            });
            $('#select-state1').selectize({
                create: true,
                sortField: 'text'
            });
            $('.select-state').selectize({
                sortField: 'text'
            });
            $('.select-state1').selectize({
                create: true,
                sortField: 'text'
            });
            $("#search-inp").keyup(function() {
                var search = $(this).val();
                if(search!='' && search.length>2){
                    $.get('/search/json?search='+search)
                    .done(function(response) {
                        var res = JSON.parse(response);
                        $('.result-search2').html('');
                        if(res.results.orders.length>0 || res.results.rxs.length>0 || res.results.users.length>0) {
                            for (const element of res.results.orders) {
                                var row = '<div class="col-2"></div><div class="col-8 mt-3 sblock orderm"><span class="badge">Order</span><h3>'+element.id+'</h3><hr><table class="table mb-3"><tbody><tr><td>Created: '+element.created+'</td><td>Status: <span style="position: unset;border-radius: 33px;font-size: 10px;" class="badge-pill badge-'+element.status_color+'">'+element.status_name+'</span></td></tr><tr><td>Delivered: '+element.finish+'</td><td>Pharmacy: '+element.pharmacy_name+' #'+element.pharmacy_id+'</td></tr><tr><td>Co-pay: $'+element.copay+' </td><td>Patient: '+element.user_name+' #'+element.user_id+'</td></tr></tbody></table><hr><a href="'+element.link+'" class="btnm">Details</a></div><div class="col-2"></div>';
                                $('.result-search2').append(row);
                            }
                            for (const element of res.results.users) {
                                var row = '<div class="col-2"></div><div class="col-8 mt-3  sblock patientm"><span class="badge">Patient</span><h3>'+element.name+' '+element.last_name+' #'+element.id+'</h3><hr><table class="table mb-3"><tbody><tr><td>Phone: '+element.phone+'</td><td>Orders: '+element.count_orders+'</td></tr><tr><td>'+element.address+' '+element.zip+'</td><td>Pharmacy: '+element.pharmacy_name+'</td></tr></tbody></table><hr><a href="'+element.link+'" class="btnm">Details</a></div><div class="col-2"></div>';
                                $('.result-search2').append(row);
                            }
                            for (const element of res.results.rxs) {
                                var row = '<div class="col-2"></div><div class="col-8 mt-3  sblock rxm"><span class="badge">RX</span><h3>'+element.rx_id+'</h3><hr><table class="table mb-3"><tbody><tr><td>Created: '+element.created+'</td><td>Order: #'+element.order_id+'</td></tr><tr><td>Delivered: '+element.finish+'</td><td>Co-pay: $'+element.copay+'</td></tr></tbody></table><hr><a href="'+element.link+'" class="btnm">Details</a></div><div class="col-2"></div>';
                                $('.result-search2').append(row);
                            }
                        } else {
                            var row = '<div class="result-search-row"><b>Nothing found</b></div>';
                            $('.result-search2').append(row);
                        }
                        $('.result-search2').show();
                    });
                } else {
                    $('.result-search2').hide();
                }
            });
            $("#search-inp1").keyup(function() {
                if($(this).val()!=''){
                    $('.result-search01').show();
                    
                } else {
                    $('.result-search01').hide();
                }
            });
        });
        $(window).on('load', function(){
            setTimeout(function(){
                if(typeof(initAll) != "undefined") {
                    initAll();
                }
            }, 150);
        }); 
    </script>    
</body>

</html>