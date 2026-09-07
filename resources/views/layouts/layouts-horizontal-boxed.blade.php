<!DOCTYPE html>
<html lang="en">

    <meta charset="utf-8" />
    <title> @yield('title')  | QuikMedix</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="QuikMedix pharmacy delivery platform" name="description" />
    <meta content="QuikMedix" name="author" />
    <!-- App favicon -->
    @include('layouts.partials.brand-icons')
    
     <!-- headerCss -->
    @yield('headerCss')

    <!-- Bootstrap Css -->
    <link href="{{ URL::asset('/css/bootstrap.min.css?v=quikmedix-1') }}" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="{{ URL::asset('/css/icons.min.css')}}" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="{{ URL::asset('/css/app.min.css?v=quikmedix-1')}}" id="app-style" rel="stylesheet" type="text/css" />

</head>

<body  data-topbar="light" data-layout="horizontal" data-layout-size="boxed">

    <!-- Begin page -->
    <div id="layout-wrapper">

        @include('layouts/partials/navbar')

        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">

            <div class="page-content">
                <div class="container-fluid">

                  <!-- content -->
                   @yield('content')

                  @include('layouts/partials/footer')

               </div>
                <!-- end main content-->
            </div>
            <!-- END layout-wrapper -->

             @include('layouts/partials/rightbar')
   
            <!-- JAVASCRIPT -->
            <script src="{{ URL::asset('/libs/jquery/jquery.min.js')}}"></script>
            <script src="{{ URL::asset('/libs/bootstrap/bootstrap.min.js')}}"></script>
            <script src="{{ URL::asset('/libs/metismenu/metismenu.min.js')}}"></script>
            <script src="{{ URL::asset('/libs/simplebar/simplebar.min.js')}}"></script>
            <script src="{{ URL::asset('/libs/node-waves/node-waves.min.js')}}"></script>
            <script src="{{ URL::asset('/libs/jquery-sparkline/jquery-sparkline.min.js')}}"></script>

            <!-- footerScript -->
             @yield('footerScript')

            <!-- App js -->
            <script src="{{ URL::asset('/js/app.min.js')}}"></script>
</body>

</html>