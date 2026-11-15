<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8" />
    <title> @yield('title')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="QuikMedix Control Panel" name="description" />
    <!-- App favicon -->
    @include('layouts.partials.brand-icons')
    <!-- Bootstrap Css -->
    <link href="{{ URL::asset('/css/bootstrap.min.css?v=quikmedix-1') }}" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="{{ URL::asset('/css/icons.min.css')}}" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="{{ URL::asset('/css/app.min.css?v=quikmedix-1')}}" id="app-style" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.12.6/css/selectize.bootstrap3.min.css" integrity="sha256-ze/OEYGcFbPRmvCnrSeKbRTtjG4vGLHXgOqsyLFTRjg=" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">   
    <link rel="stylesheet" href="{{ URL::asset('/libs/magnific-popup/magnific-popup.min.css') }}" type="text/css" />
    <link rel="stylesheet" href="/css/sweetalert2.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,200;1,300;1,400;1,500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ URL::asset('/css/bootstrap-lib.min.css?v=quikmedix-1')}}">
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
    color: #c90016;
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
    background: #c90016;
    box-shadow: 5px 5px 15px #f4ccd0, -5px -5px 15px #ffffff;
    padding: 10px;
    border-radius: 0.25rem;
    text-align: right;
    float: right;
}
.toast-info  {
    color: white;
    background: #c90016;
}
</style>
    @include('layouts.partials.brand-colors')
</head>
<body  data-topbar="light" data-layout="horizontal">
    <div id="headerCss">
        <!-- headerCss -->
        @yield('headerCss')
    </div>
    <!-- Begin page -->
    <div id="layout-wrapper">
        
        @include('layouts/partials/navbar', ['userDropdownToggleAttribute' => 'data-bs-toggle'])

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
    @if(Auth::user()->hasAnyRole('superadmin', 'admin', 'medic'))
    @include('layouts.partials.qr-scanner')
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



    @if(((Auth::user()->can('admin')) || Auth::user()->hasRole('logist')) && Auth::user()->call_ready=='1')
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
    @if(Auth::user()->hasRole('medic') && config('services.tidio.widget_key'))
    <script src="https://code.tidio.co/{{ config('services.tidio.widget_key') }}.js" async></script>
    <script>
        document.tidioIdentify = {
            distinct_id: "{{Auth::user()->id}}",
            email: "{{Auth::user()->email}}",
            name: "{{Auth::user()->name}} {{Auth::user()->last_name}} ({{Auth::user()->pharmacy_name()}})",
            phone: "{{Auth::user()->phone}}"
        };
    </script>
    @endif
    @if(config('services.beams.instance_id'))
    <script src="https://js.pusher.com/beams/1.0/push-notifications-cdn.js"></script>
    <script>
        const beamsClient = new PusherPushNotifications.Client({
            instanceId: @json(config('services.beams.instance_id')),
        });
        const beamsTokenProvider = new PusherPushNotifications.TokenProvider({
            url: "/pusher/beams-auth",
        });
        beamsClient.start()
        .then(() => beamsClient.setUserId("{{Auth::user()->id}}", beamsTokenProvider))
        .catch(console.error);
    </script>
    @endif
    <!-- App js -->
    <script src="{{ URL::asset('/js/app.min.js?v=quikmedix-1')}}"></script>
    <script src="{{ asset('js/qr-scanner.js') }}"></script>
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
            @if(Auth::user()->hasRole('medic'))
            @if((Auth::user()->pharmacy_balance()<0 && session()->has('flash_show_popup')) || Auth::user()->pharmacy_balance_ban())
            Swal.fire({
                title:"Couldn't process your last payment",
                html: @json('<p>Payment failed—please update your payment details.</p><p>For questions about your account, contact '.e(\App\Support\Branding::supportContact()).'.</p>'),
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
    @if(Auth::user()->hasAnyRole('superadmin', 'admin', 'dispadmin', 'logist'))
    <script>
    $(".call_patient").on("click",function(){
        var phone = $(this).data("phone").replace("(","").replace(")","").replace(" ","").replace("-","");
        zdrmWebrtcPhone.setCallingNumber(phone);
        $(".zdrm-webphone-call-btn").get(0).click();
    });
    </script>
    @endif
    <script src="{{ URL::asset('/js/layout-ready.js?v=1') }}"></script>
</body>

</html>
