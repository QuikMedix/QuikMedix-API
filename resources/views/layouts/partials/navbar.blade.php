@php
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
@endphp
<header id="page-topbar">
            <div class="navbar-header">
                <div class="container-fluid" style="z-index: 9999999999;">
                    <div class="float-left">
                        <!-- LOGO -->
                        <div class="navbar-brand-box">
                            <a href="/" class="logo logo-dark">
                                <span class="logo-sm">
                                    <img src="{{ URL::asset('/images/icon.svg')}}" alt="" height="50">
                                </span>
                                <span class="logo-lg">
                                    <img src="{{ URL::asset('/images/logo.svg')}}" alt="" height="60">
                                </span>
                            </a>
                        </div>
    
                        <button type="button" class="btn btn-sm px-3 font-size-24 d-lg-none header-item waves-effect waves-light" data-toggle="collapse" data-target="#topnav-menu-content">
                            <i class="mdi mdi-menu"></i>
                        </button>
                        @if(Auth::user()->role=='admin' || Auth::user()->role=='superadmin')
                            <div class="total-inc d-inline-block" style="background: #d9ffe1;padding: 4px 8px 4px 8px;border-radius: 7px;">
                                <a href="/reports/billing"><p style="font-size: 13px;color: black;margin-bottom: 0;"> <i class="mdi mdi-bank-transfer-in"></i> Income Today <span style="">${{Auth::user()->income_today()}}</span></p></a>
                            </div>  
                            <div class="total-inc d-inline-block" style="background: #d9ffe1;padding: 4px 8px 4px 8px;border-radius: 7px;">
                                <a href="/reports/billing"><p style="font-size: 13px;color: black;margin-bottom: 0;"> <i class="mdi mdi-account-cash"></i> Co-pays Cash: ${{Auth::user()->copay_cash_today()}}</p></a>
                            </div>
                            <div class="total-inc d-inline-block" style="background: #d9ffe1;padding: 4px 8px 4px 8px;border-radius: 7px;">
                                <a href="/reports/billing"><p style="font-size: 13px;color: black;margin-bottom: 0;"> <i class="mdi mdi-cellphone-arrow-down"></i> Co-pays App: ${{Auth::user()->copay_app_today()}}</p></a>
                            </div>                      
                        @endif
                        @if(Auth::user()->role=='medic' && Auth::user()->pharmacy_balance()<0)
                            <div class="total-inc d-inline-block" style="background: #930000;padding: 7px 15px 0px 15px;border-radius: 20px;">
                                <a href="/billing/{{ Auth::user()->pharmacy_id }}"><h4 style="font-size: 13px;color: white;font-weight: 100;"> <i class="ti-alert"></i> You have unpaid bills | <span style="">Amount - ${{abs(Auth::user()->pharmacy_balance())}}</span></h4></a>
                            </div>                       
                        @endif
                    </div>
    
                    <div class="float-right">
                        <div class="d-lg-inline-block mr-4">
                        @if(Auth::user()->role=='logist')
                            <p class="role-line">Hello, Dispatcher</p>
                        @elseif(Auth::user()->role=='medic')
                            <p class="role-line">Hello, {{Auth::user()->pharmacy_name()}}</p>
                        @elseif(Auth::user()->role=='user')
                            <p class="role-line">Hello, Patient</p>
                        @elseif(Auth::user()->role=='sale')
                            <p class="role-line">Hello, Sale Manager</p>
                        @else
                            <p class="role-line">Hello, {{ Auth::user()->role }}</p>
                        @endif
                        </div>
                        <!-- App Search-->
                        <div class="dropdown d-none d-lg-inline-block ">
                            <button type="button" class="btn header-item noti-icon waves-effect searchnew" data-bs-toggle="modal" data-bs-target="#searchmodal">
                                <i class="ti-search"></i>
                            </button>
                        </div>
                        <!-- searchmodalcontent -->
                        <div id="searchmodal" class="modal fade" tabindex="-1" aria-labelledby="#searchmodalLabel" style="display: none;" aria-hidden="true">
                            <div class="modal-dialog modal-fullscreen newmodal">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title mt-0" id="searchmodalLabel">
                                        Advanced Search</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body bgmodal">
                                        <div class="row">
                                            <div class="col-2"></div>
                                            <div class="col-8">
                                                <div class="position-relative">
                                                <input type="text" class="form-control" id="search-inp" autocomplete="off" placeholder="Start typing...">
                                                </div>
                                            </div>
                                            <div class="col-2"></div>
                                        </div>
                                        <div class="row result-search2">
                                
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary waves-effect" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div><!-- /.modal-content -->
                            </div><!-- /.modal-dialog -->
                        </div><!-- /.modal -->

                        <div class="dropdown d-none d-lg-inline-block">
                            <button type="button" class="btn header-item noti-icon waves-effect" data-toggle="fullscreen">
                                <i class="mdi mdi-fullscreen font-size-24"></i>
                            </button>
                        </div>
                        
                        @if((Auth::user()->role == 'superadmin' || Auth::user()->role == 'admin' || Auth::user()->role == 'dispadmin') || Auth::user()->role == 'logist')
                        <div class="dropdown d-none d-lg-inline-block">
                            <form action="/ready_call" method="POST">
                                @csrf
                                <button type="submit" class="btn header-item noti-icon waves-effect" style="display:grid" title="Call Ready Status">
                                    @if(Auth::user()->call_ready=='1')
                                    <i class="mdi mdi-phone-in-talk font-size-24"></i>
                                    <span class="badge rounded-pill bg-success" style="position: relative !important;top: -8px !important;color:#fff;">Online</span>
                                    @else
                                    <i class="mdi mdi-phone-off font-size-24"></i>
                                    <span class="badge rounded-pill bg-danger" style="position: relative !important;top: -8px !important;color:#fff;">Offline</span>
                                    @endif
                                </button>
                            </form>
                        </div>
                        @endif
    
                        <div class="dropdown d-inline-block d-lg-none ml-2">
                            <button type="button" class="btn header-item noti-icon waves-effect" id="page-header-search-dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="mdi mdi-magnify"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right p-0" aria-labelledby="page-header-search-dropdown">
    
                                <div class="p-3">
                                    <div class="form-group m-0">
                                        <div class="input-group">
                                            <input type="text" id="search-inp1" class="form-control" autocomplete="off" placeholder="Search ...">
                                            <div class="input-group-append">
                                                <button class="btn btn-primary" type="button"><i class="mdi mdi-magnify"></i></button>
                                            </div>
                                        </div>
                                        <div class="result-search01">
                                           
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                
    
                        <div class="dropdown d-inline-block ml-1">
                            <button type="button" class="btn header-item noti-icon waves-effect" id="page-header-notifications-dropdown"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="ti-bell"></i>
                                <span class="badge badge-danger badge-pill">{{Auth::user()->notification_count()}}</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right p-0"
                                aria-labelledby="page-header-notifications-dropdown">
                                <div class="p-3">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <h5 class="m-0"> Notifications ({{Auth::user()->notification_count()}}) </h5>
                                        </div>
                                    </div>
                                </div>
                                <div data-simplebar style="max-height: 270px;">
                                    @foreach (Auth::user()->notifications() as $notification)
                                        <div class="alert alert-{{$notification->type}} alert-block" role="alert" style="margin-bottom: 5px;">
                                            @if($notification->link!='')
                                                <a href="{{$notification->link}}">
                                            @else 
                                                <a href="#">
                                            @endif
                                            <div class="icon-block">
                                            @if($notification->type=='danger' || $notification->type=='warning')
                                                <i class="fas fa-info-circle"></i>
                                            @else
                                                <i class="fas fa-check-circle"></i>
                                            @endif
                                            </div>
                                            <div class="info-block">
                                                <div class="text">{{$notification->text}}</div>
                                                @if(round((strtotime(date('now')) - strtotime($notification->created))/3600, 1)<48)
                                                    <div class="datetime">{{time_elapsed_string($notification->created)}}</div>
                                                @else
                                                    <div class="datetime">{{date('m/d/Y g:i A', strtotime($notification->created))}}</div>
                                                @endif
                                            </div>
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="p-2 border-top">
                                    <a class="btn btn-sm btn-link font-size-14 btn-block text-center" href="/notifications">
                                        View all
                                    </a>
                                </div>
                            </div>
                        </div>
    
                        <div class="dropdown d-inline-block">
                            <button type="button" class="btn header-item waves-effect" id="page-header-user-dropdown"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                @if(Auth::user()->image != '')
                                    <img class="rounded-circle header-profile-user" src="{{ Auth::user()->image }}"
                                        alt="Header Avatar">
                                @else
                                    <img class="rounded-circle header-profile-user" src="{{ URL::asset('/images/users/default-user-image.png')}}"
                                    alt="Header Avatar">
                                @endif
                            </button>
                            <div class="dropdown-menu dropdown-menu-right">
                                <!-- item-->
                                <a class="dropdown-item" href="/profile"><i class="mdi mdi-account-circle font-size-17 text-muted align-middle mr-1"></i> Profile</a>
                                <a class="dropdown-item" href="#"><i class="mdi mdi-lock-open-outline font-size-17 text-muted align-middle mr-1"></i> Lock screen</a>
                                @if(!empty(session('login_superadmin')))
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="javascript:void();" onclick="event.preventDefault(); document.getElementById('login-superadmin-form').submit();"><i class="mdi mdi-account-key-outline font-size-17 text-muted align-middle mr-1"></i> Back To Super Admin</a>
                                <form id="login-superadmin-form" action="{{ route('back_to_superadmin_user') }}" method="POST" style="display: none;">
                                    @csrf
                                </form>
                                @endif
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-danger" href="javascript:void();" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i class="mdi mdi-power font-size-17 text-muted align-middle mr-1 text-danger"></i> {{ __('Logout') }}</a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                    @csrf
                                </form>
                            </div>
                        </div>
    
                    </div>
                </div>
            </div>

        <div class="top-navigation">
            <div class="page-title-content">
                <div class="container-fluid">
                    <!-- start page title -->
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="page-title-box"> 
                                <h4 id="h-title">{{$title}}</h4>
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="javascript: void(0);">A2BRx</a></li>
                                </ol>
                                
                            </div>
                        </div>
                        @if(Auth::user()->role == 'driver')
                        <div class="col-sm-6">
                            <div class="state-information d-none d-sm-block">
                                <div class="state-graph float-right">
                                    <div id="header-chart-1"></div>
                                    <div class="info">Drivers Balance $ 0</div>
                                </div>
                            </div>
                        </div>
                        @elseif(Auth::user()->role == 'medic')
                        <div class="col-sm-6">
                            <div class="page-title-box float-right">
                                <h4>Support <i class="mdi mdi-phone-in-talk-outline ml-2 mr-2"></i> (855) 657-9595</h4>
                            </div>
                        </div>
                        @elseif(Auth::user()->role == 'sale')
                        <div class="col-sm-6">
                            <div class="page-title-box float-right">
                                <h4 style="margin-top: 12px;"><span style="font-size: 16px;">Sale Balance <b>$ 0</b></span></h4>
                            </div>
                        </div>
                        @else
                        <div class="col-sm-6">
                            <div class="page-title-box float-right" style="padding-right: 370px;">
                               <!-- <h6 style="margin-top: 11px;border: solid 2px #fff;padding: 2px 20px;border-radius: 36px;line-height: 24px;color: white;font-weight: 100;">Ready for pick up <i class="mdi mdi-truck-fast"></i> {{Auth::user()->ready_pickup_count()}}</h6> -->
                            </div>
                        </div>
                        @endif
                    </div>
                    <!-- end page title -->
                </div>
            </div>

            <div class="container-fluid">
                <div class="topnav">
                    <nav class="navbar navbar-light navbar-expand-lg topnav-menu">

                        <div class="collapse navbar-collapse" id="topnav-menu-content">
                            <ul class="navbar-nav">
                                <li class="nav-item">
                                    <a class="nav-link" href="/">
                                        <i class="ti-dashboard"></i>Dashboard
                                    </a>
                                </li>
                                @if((Auth::user()->role == 'superadmin' || Auth::user()->role == 'admin' || Auth::user()->role == 'dispadmin'))
                                <li class="nav-item">
                                    <a href="/orders" class="nav-link">
                                        <i class="ti-bag"></i>
                                        <span>Orders</span>
                                    </a>
                                </li>
                                @elseif(Auth::user()->role != 'sale')
                                <li class="nav-item">
                                    <a href="/orders/{{ Auth::user()->pharmacy_id }}" class="nav-link">
                                        <i class="ti-bag"></i>
                                        <span>Orders</span>
                                    </a>
                                </li>
                                @endif
                                @if(Auth::user()->role == 'medic1')
                                    <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-email" role="button"
                                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="ti-location-pin"></i>Pharmacy 
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-left" aria-labelledby="topnav-email">
                                            <a href="/pharmacy/{{ Auth::user()->pharmacy_id }}/users/" class="dropdown-item">Pharmacists</a>
                                            <a href="/pharmacy/{{ Auth::user()->pharmacy_id }}/invoices/" class="dropdown-item">Invoices</a>
                                        </div>
                                    </li>
                                @endif

                                @if((Auth::user()->role == 'superadmin' || Auth::user()->role == 'admin' || Auth::user()->role == 'dispadmin') || Auth::user()->role == 'sale')
                                    <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle arrow-none" href="/pharmacys" id="topnav-email2" role="button" aria-haspopup="true" aria-expanded="false">
                                            <i class="ti-location-pin"></i>Pharmacies 
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-left" aria-labelledby="topnav-email2">
                                            <a href="/pharmacys/integrations" class="dropdown-item">Integrations</a>
                                        </div>
                                    </li>
                                @endif

                                @if(Auth::user()->role == 'user')
                                    <li class="nav-item dropdown">
                                        <a class="nav-link" href="/pharmacys/add">
                                            <i class="ti-heart"></i>Create Pharmacy
                                        </a>
                                    </li>
                                @endif

                                @if(Auth::user()->role == 'medic')
                                    <li class="nav-item">
                                        <a href="/pharmacy/{{ Auth::user()->pharmacy_id }}/users" class="nav-link">
                                            <i class="ti-heart"></i>
                                            <span>Pharmacists</span>
                                        </a>
                                    </li>

                                    <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-email" role="button"
                                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="ti-face-smile"></i>Clients
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-left" aria-labelledby="topnav-email">
                                            <a href="/patients/{{ Auth::user()->pharmacy_id }}" class="dropdown-item">Patients</a>
                                            <a href="/facilitys/{{ Auth::user()->pharmacy_id }}" class="dropdown-item">Facility <span class="badge" style="background-color: #d30000 !important;color: white;position: fixed;margin: 0 8px;">New</span></a>
                                        </div>
                                    </li>

                                    <li class="nav-item">
                                        <a href="/drivers/{{ Auth::user()->pharmacy_id }}/users" class="nav-link">
                                            <i class="ti-truck"></i>
                                            <span>Drivers</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="/process/{{ Auth::user()->pharmacy_id }}" class="nav-link">
                                            <i class="ti-direction-alt"></i>
                                            <span>Process</span>
                                        </a>
                                    </li>
                                @endif

                                @if(Auth::user()->role == 'driver')
                                    <li class="nav-item">
                                        <a href="/drivers/{{ Auth::user()->pharmacy_id }}/payouts" class="nav-link">
                                            <i class="ti-credit-card"></i>
                                            <span>Payouts</span>
                                        </a>
                                    </li>
                                @endif

                                @if((Auth::user()->role == 'superadmin' || Auth::user()->role == 'admin' || Auth::user()->role == 'dispadmin') || Auth::user()->role == 'logist' || Auth::user()->role == 'medic')
                                    <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-email" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            @php
                                            $get_pharmacy_route = Auth::user()->get_pharmacy_route();
                                            @endphp
                                            @if($get_pharmacy_route>0)
                                            <span class="badge badge-danger badge-pill" style="position: absolute;top: 5px;right: 10px;">{{$get_pharmacy_route}}</span>
                                            @endif
                                            <i class="ti-location-arrow"></i>
                                            <span>Routes</span> 
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-left" aria-labelledby="topnav-email">
                                            @if(Auth::user()->role == 'medic')
                                            <a href="/routes-drivers" class="dropdown-item">Drivers</a>
                                            @else
                                            <a href="/routes-list" class="dropdown-item">Orders</a>
                                            <a href="/routes-drivers" class="dropdown-item">Drivers</a>
                                            <a href="/routes-pharmacys" class="dropdown-item">
                                                Pharmacies
                                                @if($get_pharmacy_route>0)
                                                <span class="badge badge-danger badge-pill">{{$get_pharmacy_route}}</span>
                                                @endif
                                            </a>
                                            <a href="/delivery-calendar" class="dropdown-item">Delivery Calendar</a>
                                            <a href="/route-templates" class="dropdown-item">Route Templates</a>
                                            @endif
                                        </div>
                                    </li>
                                    <li class="nav-item dropdown">
                                        <a class="nav-link" href="/dispatching">
                                            <i class="ti-headphone-alt"></i>Dispatching 
                                        </a>
                                    </li>
                                @endif
                                @if(Auth::user()->role == 'medic')
                                    <li class="nav-item">
                                        <a href="/billing/{{ Auth::user()->pharmacy_id }}" class="nav-link">
                                            <i class="ti-credit-card"></i>
                                            <span>Billing</span>
                                        </a>
                                    </li>
                                @endif
                                @if(Auth::user()->role == 'medic')
                                <li class="nav-item">
                                    <a href="/news" class="nav-link">
                                        @if(Auth::user()->get_unread_news()>0)
                                        <span class="badge badge-danger badge-pill" style="position: absolute;top: 5px;right: 10px;">{{Auth::user()->get_unread_news()}}</span>
                                        @endif
                                        <i class="ti-comment-alt"></i>
                                        <span>News</span>
                                       <!--  <span class="badge" style="background-color: #d30000 !important;color: white;position: absolute;top: 5px;right: 5px;">New</span> -->
                                    </a>
                                </li>
                                @endif
                                @if((Auth::user()->role == 'superadmin' || Auth::user()->role == 'admin' || Auth::user()->role == 'dispadmin'))
                                <li class="nav-item @if(Request::is('reports/*')){{'active'}}@endif">
                                    <a href="/reports" class="nav-link @if(Request::is('reports/*')){{'active'}}@endif">
                                        <i class="ti-bar-chart"></i>
                                        <span>Reports</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="/ads" class="nav-link">
                                        <i class="ti-stats-up"></i>
                                        <span>ADS</span>
                                    </a>
                                </li> 
                                @endif
                                @if((Auth::user()->role == 'superadmin' || Auth::user()->role == 'admin' || Auth::user()->role == 'dispadmin'))
                                <li class="nav-item @if(Request::is('reports/*')){{'active'}}@endif">
                                    <a href="/payroll" class="nav-link">
                                        <i class="ti-wallet"></i>
                                        <span>Payroll</span>
                                    </a>
                                </li>
                                @endif
                                @if(Auth::user()->role == 'medic')
                                <li class="nav-item">
                                    <a href="/faq" class="nav-link">
                                        <i class="ti-info-alt"></i>
                                        <span>FAQ</span>
                                    </a>
                                </li>
                                @endif
                                 <!-- <li class="nav-item">
                                    <a href="/drivers" class="nav-link">
                                        <i class="ti-id-badge"></i>
                                        <span>A2B Rx Drivers</span>
                                    </a>
                                </li> -->
                                @if(Auth::user()->role == 'medic')
                                <li class="nav-item">
                                    <a href="/delivery-calendar" class="nav-link">
                                        <i class="ti-calendar"></i>
                                        <span>Calendar</span>
                                    </a>
                                </li>
                                @endif
                                @if((Auth::user()->role == 'superadmin' || Auth::user()->role == 'admin' || Auth::user()->role == 'dispadmin'))
                                <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-email" role="button"
                                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="ti-settings"></i>Settings 
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-left" aria-labelledby="topnav-email">
                                            @if(Auth::user()->role == 'superadmin')
                                            <a href="/settings/admins" class="dropdown-item">Admins</a>
                                            <a href="/settings/admin_areas" class="dropdown-item">Admin Zones</a>
                                            <a href="/offices" class="dropdown-item">Offices</a>
                                            @endif
                                            <a href="/settings/users" class="dropdown-item">Users</a>
                                            <a href="/settings/medics" class="dropdown-item">Medics</a>
                                            <a href="/settings/drivers" class="dropdown-item">Drivers</a>
                                            <a href="/settings/logists" class="dropdown-item">Logists</a>
                                            <a href="/settings/wishes" class="dropdown-item">Print Text</a>
                                            <a href="/settings/plans" class="dropdown-item">Tariff Plans</a>
                                            <a href="/settings/area" class="dropdown-item">Area Tariff</a>
                                        </div>
                                    </li>
                                    <!-- <li class="nav-item ">
                                    <a href="#" class="nav-link">
                                        <i class="ti-comments-smiley"></i>
                                        <span>Technical support</span>
                                        <span class="badge" style="background-color: #d9ffe1 !important;color: black;position: absolute;top: 5px;font-weight: 100;right: 5px;">Soon</span>
                                    </a>
                                    </li> -->                                    
                                @endif
                                <li class="nav-item ">
                                    <a href="/chat" class="nav-link" target="_blank">
                                        @if(Auth::user()->get_unread_mess()>0)
                                        <span class="badge badge-danger badge-pill" style="position: absolute;top: 5px;right: 10px;">{{Auth::user()->get_unread_mess()}}</span>
                                        @else
                                        <!--<span class="badge" style="background-color: #d30000 !important;color: white;position: absolute;top: 5px;right: 5px;">New</span>-->
                                        @endif
                                        <i class="ti-comments"></i>
                                        <span>Internal chat</span>
                                    </a>
                                </li>
                                <!-- @if(Auth::user()->role == 'medic')                           
                                <li class="nav-item @if(Request::is('feedback/*')){{'active'}}@endif">
                                    
                                    <a href="/feedback" class="nav-link @if(Request::is('feedback/*')){{'active'}}@endif">
                                        <i class="ti-comments-smiley"></i>
                                        <span>Feedback</span>
                                        <span class="badge" style="background-color: #d30000 !important;color: white;position: absolute;top: 5px;right: 5px;">New</span>
                                    </a>
                                </li>   
                                @endif -->
                            </ul>
                            @if(Request::is('routes-list/driver/*'))
                                <div class="trash_block"><i class="ti-trash"></i> Trash</div>
                            @endif
                        </div>
                    </nav>
                </div>
            </div>
        </div>
</header>
