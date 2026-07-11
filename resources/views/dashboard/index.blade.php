@extends('layouts.master')

@section('title') Control Panel @endsection

@section('headerCss')
<style>
.stat-1-4 {
    font-size: 14px;
    background: #d9ffe1;
    color: black;
    padding: 5px 0px;
    display: block;
    border: solid 1px #2b3a4a;
}
.stat-1-4:hover {
    background: #2b3a4a;
    color: #d9ffe1;
    box-shadow: 0 -3px 21px 0 rgb(43 58 74 / 20%), 0 6px 15px 0 rgb(43 58 74 / 20%);
}
.stat-3 {
    font-size: 14px;
    background: #ffd356;
    color: black;
    padding: 5px 0px;
    display: block;
    border: solid 1px #2b3a4a;
}
.stat-3:hover {
    background: #2b3a4a;
    color: #ffd356;
    box-shadow: 0 -3px 21px 0 rgb(43 58 74 / 20%), 0 6px 15px 0 rgb(43 58 74 / 20%);
}
.stat-7 {
    font-size: 14px;
    background: #f1f1f1;
    color: black;
    padding: 5px 0px;
    display: block;
    border: solid 1px #2b3a4a;
}
.stat-7:hover {
    background: #2b3a4a;
    color: #f1f1f1;
    box-shadow: 0 -3px 21px 0 rgb(43 58 74 / 20%), 0 6px 15px 0 rgb(43 58 74 / 20%);
}
.stat-10-8 {
    font-size: 14px;
    background: #ffdddd;
    color: black;
    padding: 5px 0px;
    display: block;
    border: solid 1px #2b3a4a;
}
.stat-10-8:hover {
    background: #2b3a4a;
    color: #ffdddd;
    box-shadow: 0 -3px 21px 0 rgb(43 58 74 / 20%), 0 6px 15px 0 rgb(43 58 74 / 20%);
}
.table th, .table td {
    vertical-align: middle !important;
}
/*.status-home {
    background: white;
    color: #2b3a4a;
    font-size: 14px;
    padding: 8px 11px 7px 11px;
    border-radius: 2px;
    border-left: none;
    margin: 10px 10px;
    display: inline-block;
    border-radius: 3px;
} */

.status-home {
    padding: 40px 25px 37px 27px;    
    color: white;
    border-radius: 5px;
    text-align: center;
    min-height: 200px;
}

.status-home span {
    font-weight: bold;
    margin: 0 5px;
}
.status-home:hover {
    box-shadow: 0 -3px 31px 0 rgb(43 58 74 / 20%), 0 6px 20px 0 rgb(122 111 190 / 40%);
}

.status-home i {
    font-size: 16px;
    color: #2b3a4a;
    margin-left: 5px;
}

.status-home-new {
    padding: 10px 0px;
    margin: 10px 10px;
    font-size: 15px;    
    color: white;
    text-align: center;
    display: block;
}
.status-home-new a {   
    color: white;
}
.status-home-new a:hover {   
    color: white;
}
.status-home-new span {
    display: block;
    font-size: 26px !important;
    line-height: 36px;
    font-weight: bold;
}
.status-home-new i {
    font-size: 25px;
    position: absolute;
    width: 40px;
    right: 30px;
    height: 40px;
    transform: scale3d(1, 1, 1);
	transform-origin: 50% 50%;
	will-change: transform;
	transform-style: preserve-3d;
	transition: .25s ease-out;
}
.status-home-new:hover i {   
    transform: scale3d(1.5, 1.5, 1.5);
	transition: .25s ease-in;
}

.hover {
  border: 3px solid;
  border-image: repeating-linear-gradient(135deg,#2b3a4a 0 10px,#2b3a4a 0 20px,#2b3a4a 0 30px) 8;
  -webkit-mask: 
    conic-gradient(from 180deg at top 3px right 3px, #0000 90deg,#000 0)
     var(--_i,200%) 0  /200% var(--_i,3px) border-box no-repeat,
    conic-gradient(at bottom 3px left  3px,  #0000 90deg,#000 0)
     0   var(--_i,200%)/var(--_i,3px) 200% border-box no-repeat,
    linear-gradient(#000 0 0) padding-box no-repeat;
  transition: .3s, -webkit-mask-position .3s .3s;
}
.hover:hover {
  --_i: 100%;
  color: #fff;
  transition: .3s, -webkit-mask-size .3s .3s;
}

@media (max-width: 992px){
.status-home {
    margin: 5px 0;
}
.sh .p-0 {
    min-height: 50px;
}
}
</style>
@endsection

@section('content')
     <!-- start page title -->
                    <div class="row">

                    
                    </div>
                    <!-- end page title -->

                    @if(Auth::user()->role == 'medic')  
                    <div class="row">
                            @if($count_orders_merchant>0)
                            <div class="col-lg-12">
                                <div class="card">
                                    <div class="card-body text-center">
                                    <img src="https://cp.a2brx.com/images/micromerchantsystem.png" alt="micromerchantsystem" class="mr-3" height="30">
                                    <span class="mb-1">New orders: {{$count_orders_merchant}} <a href="/orders/{{ Auth::user()->pharmacy_id }}?filter=1&status%5B%5D=1&micromerchant=1" class="btn btn-sm btn-outline-dark waves-effect waves-light ml-3"> Check <i class="mdi mdi-arrow-right-circle-outline"></i></a><span>
                                    </div>
                                </div>
                            </div>
                            @endif
                            <div class="col-xl-6 col-sm-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="card-title mb-4">Current Statistics <i class="mdi mdi-chart-bar"></i></h4>  
                                        <div class="row my-3"> 
                                            <div class="col-6 col-sm-6 status-home-box"> 
                                            <a href="/orders/{{ Auth::user()->pharmacy_id }}?filter=1&status%5B%5D=4" class="status-home-new hover" style="background: linear-gradient(148deg, #77e1c1 0%, #2b8b63 55%, #3587b7 100%);">
                                            <i class="mdi mdi-thumb-up-outline"></i>
                                            <span>{{(isset($count_orders_today[4]))?$count_orders_today[4]:0}}</span> Delivered </a> 
                                            <a href="/orders/{{ Auth::user()->pharmacy_id }}?filter=1&status%5B%5D=3" class="status-home-new hover" style="background: linear-gradient(148deg, #8177c2 0%, #4d4197 55%, #8177c2 100%);">
                                            <i class="mdi mdi-highway"></i>
                                            <span class="font-size-16">{{(isset($count_orders_all[3]))?$count_orders_all[3]:0}}</span> On the way </a>
                                            <a href="/orders/{{ Auth::user()->pharmacy_id }}?filter=1&status%5B%5D=10" class="status-home-new hover" style="background: linear-gradient(148deg, #ff5e5e 0%, #7a1010 55%, #621111 100%);">
                                            <i class="mdi mdi-pharmacy"></i>
                                            <span class="font-size-16">{{(isset($count_orders_today[10]))?$count_orders_today[10]:0}}</span> Back to Pharmacy </a>
                                            <a href="/orders/{{ Auth::user()->pharmacy_id }}?same_day=1" class="status-home-new hover" style="background: linear-gradient(148deg, #81dcff 0%, #136281 55%, #1b7193 100%);">
                                            <i class="mdi mdi-calendar-today"></i>
                                            <span class="font-size-16">{{(isset($count_orders_today[201]))?$count_orders_today[202]:0}}</span> Same day 
                                            </a>
                                            </div>  
                                            
                                            <div class="col-6 col-sm-6"> 
                                            <a href="/orders/{{ Auth::user()->pharmacy_id }}?filter=1&status%5B%5D=1"class="status-home-new hover" style="background: linear-gradient(148deg, #ff9100 0%, #b96f01 55%, #cd9a0c 100%);">
                                            <i class="mdi mdi-truck-check"></i>
                                            <span class="font-size-16">{{Auth::user()->ready_pickup_count()}}</span> Ready for pick up </a>
                                            <a href="/orders/{{ Auth::user()->pharmacy_id }}?filter=1&status%5B%5D=7"  class="status-home-new hover" style="background: linear-gradient(148deg, #8177c2 0%, #4d4197 55%, #8177c2 100%);">
                                            <i class="mdi mdi-office-building"></i>
                                            <span class="font-size-16">{{(isset($count_orders_all[7]))?$count_orders_all[7]:0}}</span> Office </a>
                                            <a href="/orders/{{ Auth::user()->pharmacy_id }}?filter=1&status%5B%5D=8" class="status-home-new hover" style="background: linear-gradient(148deg, #ff5e5e 0%, #7a1010 55%, #621111 100%);">
                                            <i class="mdi mdi-alien-outline"></i>
                                            <span class="font-size-16">{{(isset($count_orders_today[8]))?$count_orders_today[8]:0}}</span> Unavailable </a>
                                            <a href="/orders/{{ Auth::user()->pharmacy_id }}?asap=1" class="status-home-new hover" style="background: linear-gradient(148deg, #81dcff 0%, #136281 55%, #1b7193 100%);">
                                            <i class="mdi mdi-truck-fast"></i>
                                            <span class="font-size-16">{{(isset($count_orders_today[201]))?$count_orders_today[202]:0}}</span> ASAP </a>                                                        
                                            </div>                                                                                     
                                        </div>  
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-6 col-sm-6" style="display: flex;">
                                <div class="card" style="width: 100%;">
                                    <div class="card-body">
                                        <h4 class="card-title mb-4">Delivered Orders - last 7 days <i class="mdi mdi-chart-timeline-variant"></i></h4>  
                                        <div class="row text-center mt-4">
                                            <div class="col-12">
                                                <h5 class="font-size-20">&nbsp;</h5>
                                                <p class="text-muted">&nbsp;</p>
                                            </div>                                                                             
                                        </div>
                                        <div id="morris-line-example" class="morris-charts morris-charts-height" dir="ltr"></div>
                                    </div>
                                </div>
                            </div>

                        @component('common-components.dashboard-widget1')
                            @slot('icons') mdi mdi-cube-outline float-right  @endslot                     
                            @slot('title') Delivered  @endslot                     
                            @slot('price') {{ $count_orders }} @endslot                       
                            @slot('badgeClass') @if($orders_proc>=0) badge-info @else badge-danger @endif @endslot                          
                            @slot('per') @if($orders_proc>=0){{"+"}}@endif{{ $orders_proc }}%  @endslot                     
                        @endcomponent

                        @component('common-components.dashboard-widget1')
                            @slot('icons') mdi mdi-medical-bag float-right  @endslot                     
                            @slot('title') Patients  @endslot                     
                            @slot('price') {{ $new_patients }}  @endslot                       
                            @slot('badgeClass') @if($patients_proc>=0) badge-info @else badge-danger @endif @endslot                          
                            @slot('per') @if($patients_proc>=0){{"+"}}@endif{{ $patients_proc }}%  @endslot                       
                        @endcomponent

                        @component('common-components.dashboard-widget1')
                            @slot('icons') mdi mdi-cellphone-sound float-right  @endslot                     
                            @slot('title') Patients with app  @endslot                     
                            @slot('price') {{ $new_patients_app }}  @endslot                       
                            @slot('badgeClass') badge-info @endslot                          
                            @slot('per') +0%  @endslot                       
                        @endcomponent
                        </div>
                        <!-- end row -->
                        <div class="row">
                            <div class="col-xl-6">
                                <div class="card" >
                                    <div class="card-body">
                                        <div class="inbox-item-img float-start me-3"><img src="{{ URL::asset('/images/icon-d4.gif')}}" alt="icon-d4" class="ml-0 pr-4 float-left" height="75" style="margin-top: -15px;"> </div>
                                        <h4 class="card-title-3 mb-4">News from A2B Rx</h4>                                         
                                        <ol class="activity-feed mb-3">
                                            <li class="feed-item">
                                                <div class="feed-item-list">
                                                    <span class="date text-black">May 1, 2023</span>
                                                    <span class="activity-text">Delivery planning - <a href="/news#05012023-2" class="text-primary">Learn more <i class="mdi mdi-chevron-double-right"></i></a></span>
                                                </div>
                                            </li>
                                            <li class="feed-item">
                                                <div class="feed-item-list">
                                                    <span class="date text-black">May 1, 2023</span>
                                                    <span class="activity-text">New customer profile - More information about the customer <a href="news#05012023-1" class="text-primary">Learn more <i class="mdi mdi-chevron-double-right"></i></a></span>
                                                </div>
                                            </li>                                           
                                        </ol>
                                        <div class="mt-2 mb-4 text-center">
                                        <a class="btn btn-primary btn-sm waves-effect" href="/news" role="button">Viev all</a>
                                        </div>                             
                                    </div>    
                                </div>
                            </div>   
                            <div class="col-xl-6">
                                <div class="card" >
                                    <div class="card-body">
                                        <img src="{{ URL::asset('/images/icon-d3.gif')}}" alt="icon-d3" class="ml-0 pr-4 float-left" height="75" style="margin-top: -15px;">
                                        <h4 class="card-title-3 mb-4">Messages from customers</h4>                                        
                                        <p class="my-4 pt-4 text-black text-center">Will be here soon</p>                                        
                                    </div>    
                                </div>
                                <div class="card" >
                                    <div class="card-body">
                                        <img src="{{ URL::asset('/images/icon-d5.gif')}}" alt="icon-d4" class="ml-0 pr-4 float-left" height="75" style="margin-top: -15px;">
                                        <h4 class="card-title-3 mb-4">Available drivers in your area</h4>                                        
                                        <p class="my-4 pt-4 text-black text-center">Nearby drivers found - <strong><?php echo mt_rand(1, 7), "\n";?></strong></p>
                                    </div>    
                                </div>
                            </div>                                                  
                        </div>
                        <div class="row">
                            <div class="col-xl-12">
                                <div class="card" >
                                    <div class="card-body row">
                                    @foreach($orders as $order)
                                        <div class="col-xl-2 mb-4">
                                            <div class="mini-stat-home text-black  text-center">                                                    
                                                    <div class="inbox-item-img"><img src="{{ URL::asset(($order->image=='')?'/images/users/default-user-image.png':$order->image)}}" alt="user-image" class="avatar-xs mr-2 rounded-circle"></div>
                                                    <h5 class="font-size-14 mt-3">Order: #{{$order->id}}</h5>
                                                    <p class="mb-1" style="min-height: 42px;">{{$order->name}} {{$order->last_name}}  </p>

                                                    <span class="badge badge-pill badge-{{$order->statusecolor}} small mb-2">{{$order->statusename}}</span><br>
                                                    <a href="/orders/{{ Auth::user()->pharmacy_id }}?search={{$order->id}}" class="mt-1"><button type="button" class="btn btn-sm btn-outline-dark waves-effect waves-light">View order</button></a>
                                                    <hr class="mb-2">
                                                    <span class="small"> <i class="far fa-calendar-alt"></i> {{date('m.d.Y g:i A', strtotime($order->created))}}   </span>
                                            </div>
                                        </div> 
                                    @endforeach                                            
                                    </div>    
                                </div>
                            </div>                            
                        </div>
                        <div class="row">
                            

                            <div class="col-xl-12" style="display: none;">
                                <div class="card" style="min-height: 460px;">
                                    <div class="card-body">
                                        <h4 class="card-title mb-4">Delivered Orders Statistic</h4>
                                        <div id="morris-bar-stacked" class="morris-charts morris-charts-height" dir="ltr"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-12">
                                <div class="card">
                                    <div class="card-body row">    
                                        <div class="col-xl-6 pt-3">                                        
                                        <h4 class="mt-2 mr-4 float-left text-black">Compatible with</h4>
                                        <img src="{{ URL::asset('/images/micromerchantsystem.png')}}" alt="micromerchantsystem" class="mr-3" height="40">
                                        <img src="{{ URL::asset('/images/PioneerRx-0.png')}}" alt="PioneerRx" class="mr-3" height="40">
                                        <img src="{{ URL::asset('/images/bestrx-0.png')}}" alt="BestRx" class="mr-3" height="40">
                                        </div>
                                        <div class="col-xl-6">  
                                        <img src="{{ URL::asset('/images/icon-d6.gif')}}" alt="icon-d3" class="mr-2 pl-0 float-right " height="75">
                                        <h4 class="mt-3 mr-2 float-right text-right text-black">Bonus: 0 <br>                                        
                                        <span class="small">Not available</span></h4> 
                                        
                                        </div>                                 
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- end row -->
                    @elseif((Auth::user()->role == 'superadmin' || Auth::user()->role == 'admin' || Auth::user()->role == 'dispadmin'))
                            <div class="row"> 
                                        <div class="col-xl-6 col-sm-6">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h4 class="card-title mb-4">Current Statistics <i class="mdi mdi-chart-bar"></i></h4>  
                                                    <div class="row my-3"> 
                                                        <div class="col-6 col-sm-6 status-home-box"> 
                                                        <a href="/orders?filter=1&status%5B%5D=4" class="status-home-new hover" style="background: linear-gradient(148deg, #77e1c1 0%, #2b8b63 55%, #3587b7 100%);">
                                                        <i class="mdi mdi-thumb-up-outline"></i>
                                                        <span>{{(isset($count_orders_today[4]))?$count_orders_today[4]:0}}</span> Delivered </a> 
                                                        <a href="/orders?filter=1&status%5B%5D=3" class="status-home-new hover" style="background: linear-gradient(148deg, #8177c2 0%, #4d4197 55%, #8177c2 100%);">
                                                        <i class="mdi mdi-highway"></i>
                                                        <span class="font-size-16">{{(isset($count_orders_all[3]))?$count_orders_all[3]:0}}</span> On the way </a>
                                                        <a href="/orders?filter=1&status%5B%5D=10" class="status-home-new hover" style="background: linear-gradient(148deg, #ff5e5e 0%, #7a1010 55%, #621111 100%);">
                                                        <i class="mdi mdi-pharmacy"></i>
                                                        <span class="font-size-16">{{(isset($count_orders_today[10]))?$count_orders_today[10]:0}}</span> Back to Pharmacy </a>
                                                        <a href="/orders?same_day=1" class="status-home-new hover" style="background: linear-gradient(148deg, #81dcff 0%, #136281 55%, #1b7193 100%);">
                                                        <i class="mdi mdi-calendar-today"></i>
                                                        <span class="font-size-16">{{(isset($count_orders_today[201]))?$count_orders_today[202]:0}}</span> Same day 
                                                        </a>
                                                        </div>  
                                                        
                                                        <div class="col-6 col-sm-6"> 
                                                        <a href="/orders?filter=1&status%5B%5D=1"class="status-home-new hover" style="background: linear-gradient(148deg, #ff9100 0%, #b96f01 55%, #cd9a0c 100%);">
                                                        <i class="mdi mdi-truck-check"></i>
                                                        <span class="font-size-16">{{Auth::user()->ready_pickup_count()}}</span> Ready for pick up </a>
                                                        <a href="/orders?filter=1&status%5B%5D=7"  class="status-home-new hover" style="background: linear-gradient(148deg, #8177c2 0%, #4d4197 55%, #8177c2 100%);">
                                                        <i class="mdi mdi-office-building"></i>
                                                        <span class="font-size-16">{{(isset($count_orders_all[7]))?$count_orders_all[7]:0}}</span> Office </a>
                                                        <a href="/orders?filter=1&status%5B%5D=8" class="status-home-new hover" style="background: linear-gradient(148deg, #ff5e5e 0%, #7a1010 55%, #621111 100%);">
                                                        <i class="mdi mdi-alien-outline"></i>
                                                        <span class="font-size-16">{{(isset($count_orders_today[8]))?$count_orders_today[8]:0}}</span> Unavailable </a>
                                                        <a href="/orders?asap=1" class="status-home-new hover" style="background: linear-gradient(148deg, #81dcff 0%, #136281 55%, #1b7193 100%);">
                                                        <i class="mdi mdi-truck-fast"></i>
                                                        <span class="font-size-16">{{(isset($count_orders_today[201]))?$count_orders_today[202]:0}}</span> ASAP </a>                                                        
                                                        </div>                                                                                     
                                                    </div>  
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xl-6 col-sm-6" style="display: flex;">
                                            <div class="card" style="width: 100%;">
                                                <div class="card-body">
                                                    <h4 class="card-title mb-4">Delivered Orders - last 7 days <i class="mdi mdi-chart-timeline-variant"></i></h4>  
                                                    <div class="row text-center mt-4">
                                                        <div class="col-12">
                                                            <h5 class="font-size-20">&nbsp;</h5>
                                                            <p class="text-muted">&nbsp;</p>
                                                        </div>                                                                             
                                                    </div>
                                                    <div id="morris-line-example" class="morris-charts morris-charts-height" dir="ltr"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xl-6">
                                            <div class="card">
                                                <div class="card-body text-black">
                                                    <h4 class="card-title mb-4">Income <i class="mdi mdi-credit-card-plus-outline"></i></h4>
                                                    <div class="row"> 
                                                        <div class="col-md-4">
                                                            <div>
                                                                <h3 class="mb-0">
                                                                    ${{number_format($income_today,2)}}
                                                                </h3>
                                                                <p class="mt-1 mb-1" style="color: #474f58;" title="Previous Period"><i class="mdi mdi-page-previous"></i> ${{number_format($income_today_prev,2)}}</p>
                                                                <p class="text-muted">Today</p>                                                    
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div>
                                                                <h3 class="mb-0">
                                                                    ${{number_format($income_week,2)}}
                                                                </h3>
                                                                <p class="mt-1 mb-1" style="color: #474f58;" title="Previous Period"><i class="mdi mdi-page-previous"></i> ${{number_format($income_week_prev,2)}}</p>
                                                                <p class="text-muted">Week</p>                                                    
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div>
                                                                <h3 class="mb-0">
                                                                    ${{number_format($income_month,2)}}
                                                                </h3>
                                                                <p class="mt-1 mb-1" style="color: #474f58;" title="Previous Period"><i class="mdi mdi-page-previous"></i> ${{number_format($income_month_prev,2)}}</p>
                                                                <p class="text-muted">Month</p>                                                    
                                                            </div>
                                                        </div>
                                                        <div class="text-center col-12 mt-0">
                                                            <a href="/reports/billing"><button type="button" class="btn btn-sm btn-outline-dark waves-effect waves-light" style="width: 100%;">Reports<i class="mdi mdi-arrow-right-bold-outline ml-2"></i></button></a>  
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>    
                                        <div class="col-xl-6">
                                            <div class="card">
                                                <div class="card-body text-black">
                                                    <h4 class="card-title mb-4">Payouts <i class="mdi mdi-credit-card-minus-outline"></i></h4>
                                                        <div class="row"> 
                                                            <div class="col-md-4">
                                                                <div>
                                                                    <h3 class="mb-0">$0.00</h3>
                                                                    <p class="mt-1 mb-1" style="color: #474f58;" title="Previous Period"><i class="mdi mdi-page-previous"></i> ${{number_format(0,2)}}</p>
                                                                    <p class="text-muted">Today</p>                                                    
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div>
                                                                    <h3 class="mb-0">$0.00</h3>
                                                                    <p class="mt-1 mb-1" style="color: #474f58;" title="Previous Period"><i class="mdi mdi-page-previous"></i> ${{number_format(0,2)}}</p>
                                                                    <p class="text-muted">Week</p>                                                    
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div>
                                                                    <h3 class="mb-0">$0.00</h3>
                                                                    <p class="mt-1 mb-1" style="color: #474f58;" title="Previous Period"><i class="mdi mdi-page-previous"></i> ${{number_format(0,2)}}</p>
                                                                    <p class="text-muted">Month</p>                                                    
                                                                </div>
                                                            </div>
                                                            <div class="text-center col-12 mt-0">
                                                                <a href="#"><button type="button" class="btn btn-sm btn-outline-dark waves-effect waves-light" style="width: 100%;">Reports <i class="mdi mdi-arrow-right-bold-outline ml-2"></i></button></a>  
                                                            </div>
                                                        </div>
                                                </div>
                                            </div>
                                        </div> 

                                        <div class="col-xl-4 col-sm-6">
                                            <div class="card">
                                                <div class="card-body text-black pt-0 pb-2 row">
                                                    <div class="text-right mt-2 col-8">                                                                                    
                                                        <img src="{{ URL::asset('/images/icon-d7.gif')}}" alt="icon-d4" class="mr-0 pl-0 float-left" height="75">
                                                        <h6 class="mt-4 pt-2 float-left text-black">Pharmacy balance {{($pharmacys_balance<0)?'-$'.round(abs($pharmacys_balance),2):'$'.round($pharmacys_balance,2)}}</h6>                                                 
                                                    </div>
                                                    <div class="text-right col-4 pt-4 mt-2">
                                                        <a href="/pharmacys?pharmacys_balance=1"><button type="button" class="btn btn-sm btn-outline-dark waves-effect waves-light">View <i class="mdi mdi-arrow-right-circle-outline"></i></button></a>  
                                                    </div>
                                                </div>
                                            </div>
                                        </div> 
                                        <div class="col-xl-4 col-sm-6">
                                            <div class="card">
                                                <div class="card-body text-black pt-0 pb-2 row">
                                                    <div class="text-center mt-2 col-8">                                                                                     
                                                        <img src="{{ URL::asset('/images/icon-d9.gif')}}" alt="icon-d4" class="mr-0 pl-0 float-left" height="75">
                                                        <h6 class="mt-4 pt-2 float-left text-black">Blocked pharmacies - {{$pharmacys_blocked}}</h6>                                                        
                                                    </div>
                                                    <div class="text-right col-4 pt-4 mt-2">
                                                        <a href="/pharmacys?pharmacys_blocked=1"><button type="button" class="btn btn-sm btn-outline-dark waves-effect waves-light">View <i class="mdi mdi-arrow-right-circle-outline"></i></button></a>  
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xl-4 col-sm-6">
                                            <div class="card">
                                                <div class="card-body text-black pt-0 pb-2 row">
                                                    <div class="text-center mt-2 col-8">                                                                                    
                                                        <img src="{{ URL::asset('/images/icon-d8.gif')}}" alt="icon-d4" class="mr-0 pl-0 float-left" height="75">
                                                        <h6 class="mt-4 pt-2 float-left text-black">Orders without notes {{$orders_without_notes}}</h6>                                                        
                                                    </div>
                                                    <div class="text-right col-4 pt-4 mt-2">
                                                        <a href="/orders?orders_without_notes=1"><button type="button" class="btn btn-sm btn-outline-dark waves-effect waves-light">View <i class="mdi mdi-arrow-right-circle-outline"></i></button></a>  
                                                    </div>
                                                </div>
                                            </div>
                                        </div>                            
                                        <!--  <div class="col-xl-4 col-sm-6">
                                            <div class="card">
                                                <div class="card-body text-black">
                                                    <div class="row text-center mt-2">
                                                        <div class="col-6">
                                                            <h5 class="font-size-24 mb-3">{{Auth::user()->ready_pickup_count()}}</h5>
                                                            <a href="/orders?filter=1&status%5B%5D=1" class="stat-1-4"><i class="mdi mdi-truck-fast"></i> Ready for pick up</a>                                                           
                                                        </div>
                                                        <div class="col-6">
                                                            <h5 class="font-size-24 mb-3">{{(isset($count_orders_today[10]))?$count_orders_today[10]:0}}</h5>
                                                            <a href="/orders?filter=1&status%5B%5D=10" class="stat-10-8"><i class="mdi mdi-pharmacy"></i>  Back to Pharmacy</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xl-4 col-sm-6">
                                            <div class="card">
                                                <div class="card-body text-black">
                                                    <div class="row text-center mt-2">
                                                        <div class="col-6">
                                                            <h5 class="font-size-24 mb-3">{{(isset($count_orders_today[4]))?$count_orders_today[4]:0}}</h5>
                                                            <a href="/orders?filter=1&status%5B%5D=4" class="stat-1-4"><i class="mdi mdi-thumb-up-outline"></i> Delivered</a>
                                                        </div>
                                                        <div class="col-6">
                                                            <h5 class="font-size-24 mb-3">{{(isset($count_orders_all[3]))?$count_orders_all[3]:0}}</h5>
                                                            <a href="/orders?filter=1&status%5B%5D=3" class="stat-3"><i class="mdi mdi-highway"></i>  On the way</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xl-4 col-sm-6">
                                            <div class="card">
                                                <div class="card-body text-black">
                                                    <div class="row text-center mt-2">                                                        
                                                        <div class="col-6">
                                                            <h5 class="font-size-24 mb-3">{{(isset($count_orders_all[7]))?$count_orders_all[7]:0}}</h5>
                                                            <a href="/orders?filter=1&status%5B%5D=7" class="stat-7"><i class="mdi mdi-office-building"></i> Office</a>
                                                        </div>
                                                        <div class="col-6">
                                                            <h5 class="font-size-24 mb-3">{{(isset($count_orders_today[8]))?$count_orders_today[8]:0}}</h5>
                                                            <a href="/orders?filter=1&status%5B%5D=8" class="stat-10-8"><i class="mdi mdi-alien-outline"></i> Unavailable</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>   -->                                        
                                         


                                        <!--  <div class="col-xl-2 col-sm-12" style="border-right: solid 1px #7a6fbe;text-align: center;">
                                            <i class="mdi mdi-thumb-up-outline float-right" style="color: #00bf7f;font-size: 30px;position: absolute;right: 10px;top: 0px;"></i>                                           
                                            <h4 class="mb-4 mt-4"><span style="font-size: 14px;">Delivered</span> {{(isset($count_orders_today[4]))?$count_orders_today[4]:0}}</h4>
                                            <a href="/orders?filter=1&status%5B%5D=4" class="" style="margin-top: -30px;"><button type="button" class="btn btn-sm btn-outline-dark waves-effect waves-light">View orders </button></a>
                                        </div>
                                        <div class="col-xl-2 col-sm-12" style="border-right: solid 1px #7a6fbe;text-align: center;">
                                            <i class="mdi mdi-highway float-right" style="color: #7a6fbe;font-size: 30px;position: absolute;right: 10px;top: 0px;"></i>                                            
                                            <h4 class="mb-4 mt-4"><span style="font-size: 14px;">On the way</span> {{(isset($count_orders_all[3]))?$count_orders_all[3]:0}} </h4>
                                            <a href="/orders?filter=1&status%5B%5D=3" class="" style="margin-top: -30px;"><button type="button" class="btn btn-sm btn-outline-dark waves-effect waves-light">View orders </button></a>
                                        </div>
                                        <div class="col-xl-2 col-sm-12" style="border-right: solid 1px #7a6fbe;text-align: center;">
                                            <i class="mdi mdi-office-building float-right" style="color: #ffa500;font-size: 30px;position: absolute;right: 10px;top: 0px;"></i>                                            
                                            <h4 class="mb-4 mt-4"><span style="font-size: 14px;">Office</span> {{(isset($count_orders_all[7]))?$count_orders_all[7]:0}} </h4>
                                            <a href="/orders?filter=1&status%5B%5D=7" class="" style="margin-top: -30px;"><button type="button" class="btn btn-sm btn-outline-dark waves-effect waves-light">View orders </button></a>
                                        </div>
                                        <div class="col-xl-2 col-sm-12" style="border-right: solid 1px #7a6fbe;text-align: center;">
                                            <i class="mdi mdi-alien-outline float-right" style="color: #007ce9;font-size: 30px;position: absolute;right: 10px;top: 0px;"></i>                                          
                                            <h4 class="mb-4 mt-4"><span style="font-size: 14px;">Unavailable</span> {{(isset($count_orders_today[8]))?$count_orders_today[8]:0}} </h4>
                                            <a href="/orders?filter=1&status%5B%5D=8" class="" style="margin-top: -30px;"><button type="button" class="btn btn-sm btn-outline-dark waves-effect waves-light">View orders </button></a>
                                        </div>
                                        <div class="col-xl-2 col-sm-12" style="border-right: solid 1px #7a6fbe;text-align: center;">
                                            <i class="mdi mdi-alert float-right" style="color: red;font-size: 30px;position: absolute;right: 10px;top: 0px;"></i>                                           
                                            <h4 class="mb-4 mt-4"><span style="font-size: 14px;">Refused</span> {{(isset($count_orders_today[9]))?$count_orders_today[9]:0}} </h4>
                                            <a href="/orders?filter=1&status%5B%5D=9" class="" style="margin-top: -30px;"><button type="button" class="btn btn-sm btn-outline-dark waves-effect waves-light">View orders </button></a>
                                        </div>
                                        <div class="col-xl-2 col-sm-12" style="text-align: center;">
                                            <i class="mdi mdi-pharmacy float-right" style="color: #bfa700;font-size: 30px;position: absolute;right: 10px;top: 0px;"></i>                                            
                                            <h4 class="mb-4 mt-4"><span style="font-size: 14px;">Back to Pharmacy</span> {{(isset($count_orders_today[10]))?$count_orders_today[10]:0}} </h4>
                                            <a href="/orders?filter=1&status%5B%5D=10" class="" style="margin-top: -30px;"><button type="button" class="btn btn-sm btn-outline-dark waves-effect waves-light">View orders </button></a>
                                        </div> -->
                               
                               
                            



                        <!-- <div class="row">
                                <div class="col-xl-4 col-md-6">
                                    <div class="card mini-stat2 text-black mb-2">
                                        <div class="card-body">
                                            <div class="mini-stat-icon2">
                                                <i class="mdi mdi-thumb-up-outline float-right" style="color: #00bf7f;"></i>
                                            </div>
                                            <div class="">
                                                <h6 class="text-uppercase mb-3 font-size-12">Today</h6>
                                                <h2 class="mb-4"><span>Delivered</span> {{(isset($count_orders_today[4]))?$count_orders_today[4]:0}}</h2>
                                                <a href="/orders?filter=1&status%5B%5D=4" class="" style="margin-top: -30px;"><button type="button" class="btn btn-sm btn-outline-dark waves-effect waves-light">View orders </button></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xl-4 col-md-6">
                                    <div class="card mini-stat2 text-black mb-2">
                                        <div class="card-body">
                                            <div class="mini-stat-icon2">
                                                <i class="mdi mdi-highway float-right" style="color: #7a6fbe;"></i>
                                            </div>
                                            <div class="">
                                                <h6 class="text-uppercase mb-3 font-size-12">Today</h6>
                                                <h2 class="mb-4"><span>On the way</span> {{(isset($count_orders_today[3]))?$count_orders_today[3]:0}} </h2>
                                                <a href="/orders?filter=1&status%5B%5D=3" class="" style="margin-top: -30px;"><button type="button" class="btn btn-sm btn-outline-dark waves-effect waves-light">View orders </button></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xl-4 col-md-6">
                                    <div class="card mini-stat2 text-black mb-2">
                                        <div class="card-body">
                                            <div class="mini-stat-icon2">
                                                <i class="mdi mdi-office-building float-right" style="color: #ffa500;"></i>
                                            </div>
                                            <div class="">
                                                <h6 class="text-uppercase mb-3 font-size-12">Today</h6>
                                                <h2 class="mb-4"><span>Office</span> {{(isset($count_orders_today[7]))?$count_orders_today[7]:0}} </h2>
                                                <a href="/orders?filter=1&status%5B%5D=7" class="" style="margin-top: -30px;"><button type="button" class="btn btn-sm btn-outline-dark waves-effect waves-light">View orders </button></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xl-4 col-md-6">
                                    <div class="card mini-stat2 text-black mb-2">
                                        <div class="card-body">
                                            <div class="mini-stat-icon2">
                                                <i class="mdi mdi-alien-outline float-right" style="color: #007ce9;"></i>
                                            </div>
                                            <div class="">
                                                <h6 class="text-uppercase mb-3 font-size-12">Today</h6>
                                                <h2 class="mb-4"><span>Unavailable</span> {{(isset($count_orders_today[8]))?$count_orders_today[8]:0}} </h2>
                                                <a href="/orders?filter=1&status%5B%5D=8" class="" style="margin-top: -30px;"><button type="button" class="btn btn-sm btn-outline-dark waves-effect waves-light">View orders </button></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xl-4 col-md-6">
                                    <div class="card mini-stat2 text-black mb-2">
                                        <div class="card-body">
                                            <div class="mini-stat-icon2">
                                                <i class="mdi mdi-alert float-right" style="color: red;"></i>
                                            </div>
                                            <div class="">
                                                <h6 class="text-uppercase mb-3 font-size-12">Today</h6>
                                                <h2 class="mb-4"><span>Refused</span> {{(isset($count_orders_today[9]))?$count_orders_today[9]:0}} </h2>
                                                <a href="/orders?filter=1&status%5B%5D=9" class="" style="margin-top: -30px;"><button type="button" class="btn btn-sm btn-outline-dark waves-effect waves-light">View orders </button></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xl-4 col-md-6">
                                    <div class="card mini-stat2 text-black mb-2">
                                        <div class="card-body">
                                            <div class="mini-stat-icon2">
                                                <i class="mdi mdi-pharmacy float-right" style="color: #bfa700;"></i>
                                            </div>
                                            <div class="">
                                                <h6 class="text-uppercase mb-3 font-size-12">Today</h6>
                                                <h2 class="mb-4"><span>Back to Pharmacy</span> {{(isset($count_orders_today[10]))?$count_orders_today[10]:0}} </h2>
                                                <a href="/orders?filter=1&status%5B%5D=10" class="" style="margin-top: -30px;"><button type="button" class="btn btn-sm btn-outline-dark waves-effect waves-light">View orders </button></a>
                                            </div>
                                        </div>
                                    </div>                                
                                </div> -->
                       

                        @component('common-components.dashboard-widget')
                            @slot('icons') mdi mdi-cube-outline float-right  @endslot                     
                            @slot('title') Orders  @endslot                     
                            @slot('price') {{ $count_orders }}  @endslot
                            @slot('badgeClass') @if($orders_proc>0) badge-info @else badge-danger @endif @endslot                          
                            @slot('per') @if($orders_proc>0){{"+"}}@endif{{ $orders_proc }}%  @endslot                       
                        @endcomponent

                        @component('common-components.dashboard-widget')
                            @slot('icons') mdi mdi-hospital-box float-right  @endslot                     
                            @slot('title') Pharmacies  @endslot                     
                            @slot('price') {{ $count_pharmacies }}  @endslot                       
                            @slot('badgeClass') @if($pharmacies_proc>0) badge-info @else badge-danger @endif @endslot                          
                            @slot('per') @if($pharmacies_proc>0){{"+"}}@endif{{ $pharmacies_proc }}%  @endslot                    
                        @endcomponent

                        @component('common-components.dashboard-widget')
                            @slot('icons') mdi mdi-car-hatchback float-right  @endslot                     
                            @slot('title') Drivers  @endslot                     
                            @slot('price') {{ $count_drivers }}  @endslot                       
                            @slot('badgeClass') @if($drivers_proc>0) badge-info @else badge-danger @endif @endslot                          
                            @slot('per') @if($drivers_proc>0){{"+"}}@endif{{ $drivers_proc }}%  @endslot                 
                        @endcomponent

                        @component('common-components.dashboard-widget')
                            @slot('icons') mdi mdi-account float-right  @endslot                     
                            @slot('title') Patients  @endslot                     
                            @slot('price') {{ $count_patients }}  @endslot                       
                            @slot('badgeClass') @if($patients_proc>0) badge-info @else badge-danger @endif @endslot                          
                            @slot('per') @if($patients_proc>0){{"+"}}@endif{{ $patients_proc }}%  @endslot                
                        @endcomponent

                            <div class="col-xl-3 col-sm-6">
                                <div class="card">
                                    <div class="card-body text-black mini-stat-img2">
                                        <div class="row text-center mt-2">
                                            <div class="col-6">
                                                <h5 class="font-size-24">{{ $total_count_orders }}</h5>
                                                <p>Total Orders</p>
                                            </div>
                                            <div class="col-6 p-0">
                                                <h5 class="font-size-24">{{ $total_count_orders_a2brx }}</h5>
                                                <p>Delivered via A2B Rx</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-3 col-sm-6">
                                <div class="card">
                                    <div class="card-body text-black mini-stat-img2">
                                        <div class="row text-center mt-2">
                                            <div class="col-6">
                                                <h5 class="font-size-24">{{ $count_pharmacies }}</h5>
                                                <p>Total Pharmacies</p>
                                            </div>
                                            <div class="col-6">
                                                <h5 class="font-size-24">{{ $count_pharmacies_active }}</h5>
                                                <p>Active</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-3 col-sm-6">
                                <div class="card">
                                    <div class="card-body text-black mini-stat-img2">
                                        <div class="row text-center mt-2">
                                            <div class="col-6">
                                                <h5 class="font-size-24">{{$count_drivers_all}}</h5>
                                                <p>Total drivers</p>
                                            </div>
                                            <div class="col-6">
                                                <h5 class="font-size-24">{{$count_drivers_pharmacy}}</h5>
                                                <p>Pharmacy Drivers</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-3 col-sm-6">
                                <div class="card">
                                    <div class="card-body text-black mini-stat-img2">
                                        <div class="row text-center mt-2">
                                            <div class="col-6">
                                                <h5 class="font-size-24">{{ $count_patients }}</h5>
                                                <p>Total Patients</p>
                                            </div>
                                            <div class="col-6">
                                                <h5 class="font-size-24">{{($app_android_users+$app_ios_users)}}</h5>
                                                <p>With App</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                           
                        
                        <div class="row" style="align-items: stretch;">   
                            <div class="col-xl-6" style="display: flex;">
                                <div class="card" style="width: 100%;">
                                    <div class="card-body">
                                    <h4 class="card-title mb-4"> Warnings <i class="mdi mdi-alert-box-outline"></i></h4>

                                        <div class="table-responsive">
                                            <table class="table table-striped mb-0">        
                                                <tbody>
                                                    <tr>
                                                        <th scope="row" class="text-center"><i class="mdi mdi-signature-freehand" style="font-size: 24px;"></i></th>
                                                        <td>Total orders without signature</td>
                                                        <td class="text-center h4 text-black">{{$orders_without_sign}}</td>
                                                        <td class="text-center"><a href="/orders?without_sign=1"><button type="button" class="btn btn-sm btn-outline-dark waves-effect waves-light">View <i class="mdi mdi-arrow-right-circle-outline"></i></button></a></td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row" class="text-center"><i class="mdi mdi-signature-image" style="font-size: 24px;"></i></th>
                                                        <td>Total orders without photo confirmation</td>
                                                        <td class="text-center h4 text-black">{{$orders_without_photo}}</td>
                                                        <td class="text-center"><a href="/orders?without_photo=1"><button type="button" class="btn btn-sm btn-outline-dark waves-effect waves-light">View <i class="mdi mdi-arrow-right-circle-outline"></i></button></a></td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row" class="text-center"><i class="mdi mdi-account-cash-outline" style="font-size: 24px;"></i></th>
                                                        <td>Total co-pay orders in process</td>
                                                        <td class="text-center h4 text-black">{{$orders_copay_process}}</td>
                                                        <td class="text-center"><a href="/orders?copay_process=1"><button type="button" class="btn btn-sm btn-outline-dark waves-effect waves-light">View <i class="mdi mdi-arrow-right-circle-outline"></i></button></a></td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row" class="text-center"><i class="mdi mdi-emoticon-dead" style="font-size: 24px;"></i></th>
                                                        <td>Delivered orders without a driver</td>
                                                        <td class="text-center h4 text-black">{{$orders_without_driver}}</td>
                                                        <td class="text-center"><a href="/orders?without_driver=1"><button type="button" class="btn btn-sm btn-outline-dark waves-effect waves-light">View <i class="mdi mdi-arrow-right-circle-outline"></i></button></a></td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row" class="text-center"><i class="mdi mdi-cellphone-off" style="font-size: 24px;"></i></th>
                                                        <td>Customers without app (week)</td>
                                                        <td class="text-center h4 text-black">{{$users_without_app}}</td>
                                                        <td class="text-center"><a href="/settings/users?without_app=1"><button type="button" class="btn btn-sm btn-outline-dark waves-effect waves-light">View <i class="mdi mdi-arrow-right-circle-outline"></i></button></a></td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row" class="text-center"><i class="mdi mdi-emoticon-confused-outline" style="font-size: 24px;"></i></th>
                                                        <td>Inactive pharmacies (week)</td>
                                                        <td class="text-center h4 text-black">{{$pharmacys_without_orders}}</td>
                                                        <td class="text-center"><a href="/pharmacys?without_orders=1"><button type="button" class="btn btn-sm btn-outline-dark waves-effect waves-light">View <i class="mdi mdi-arrow-right-circle-outline"></i></button></a></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3" style="display: flex;">
                                <div class="card" style="width: 100%;">
                                    <div class="card-body">
                                    <h4 class="card-title mb-4"> New pharmacies <i class="mdi mdi-medical-bag"></i></h4>

                                        <div class="table-responsive">
                                            <table class="table table-striped mb-0">        
                                                <tbody>
                                                    @foreach($pharmacys as $pharmacy)
                                                    <tr>
                                                        <td scope="row"><b>{{$pharmacy->name}}</b> <br>
                                                        {{$pharmacy->address}} <br>
                                                        {{$pharmacy->phone}} <br>                                                      
                                                        </td>
                                                        <td class="text-center" style="min-width: 90px;"><a href="/billing/{{ $pharmacy->id }}"><button type="button" class="btn btn-sm btn-outline-dark waves-effect waves-light">View <i class="mdi mdi-arrow-right-circle-outline"></i></button></a></td>
                                                    </tr>
                                                    @endforeach                                  
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3" style="display: flex;">
                                <div class="card" style="width: 100%;">
                                    <div class="card-body">
                                    <h4 class="card-title mb-4"> Downloads <i class="mdi mdi-cellphone-arrow-down"></i></h4>

                                        <div class="row text-center mt-4">
                                            <div class="col-6">
                                                <h5 class="font-size-20">{{($app_android_users+$app_ios_users)}}</h5>
                                                <p class="text-muted">Total customers</p>
                                            </div>
                                            <div class="col-6">
                                                <h5 class="font-size-20">{{($app_android_drivers+$app_ios_drivers)}}</h5>
                                                <p class="text-muted">Total drivers</p>
                                            </div>
                                        </div>

                                        <div id="morris-donut-example" class="morris-charts morris-charts-height" dir="ltr"></div>
                                       
                                    </div>
                                </div>
                            </div>                             
                        </div>

                        <!-- end row -->
                        <div class="row">
                            <div class="col-xl-12">
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="card-title mb-4">Delivered Orders <i class="mdi mdi-chart-areaspline"></i></h4> 
                                        <div id="morris-bar-stacked" class="morris-charts morris-charts-height" dir="ltr"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- end row -->
                        <div class="row">

                          <!-- <div class="col-xl-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="card-title-3 mb-4">Latest Ready for pick up Orders</h4>
                                        <div class="row mt-2">
                                            @foreach($orders0 as $order)                                            
                                            <div class="col-6 p-3">
                                                <div class="mini-stat-home2 text-black">
                                                    <div class="inbox-item-img float-left mr-3"><img src="{{ URL::asset(($order->image=='')?'/images/users/default-user-image.png':$order->image)}}" alt="user-image" class="avatar-md mr-2 rounded-circle" /></div>
                                                    <h5 class="font-size-16">Order: {{$order->id}}</h5><a href="/orders?search={{$order->id}}" class="float-right" style="margin-top: -30px;"><button type="button" class="btn btn-sm btn-outline-dark waves-effect waves-light">View order</button></a>
                                                    <p>{{$order->name}} {{$order->last_name}} <br>                                                
                                                    
                                                    <br>                                                
                                                    <hr>
                                                    <span> <i class="far fa-calendar-alt"></i> {{date('m.d.Y g:i A', strtotime($order->created))}}</span> <span class="float-right"><i class="far fa-grin-alt"></i> {{$order->statusename}}</span></p> 
                                                </div>                                                                                         
                                            </div>
                                            @endforeach
                                        </div>                                      
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="card-title-3 mb-4">Latest Delivered Orders</h4>
                                        <div class="row mt-2">
                                            @foreach($orders as $order)                                            
                                            <div class="col-6 p-3">
                                                <div class="mini-stat-home text-black">
                                                    <div class="inbox-item-img float-left mr-3"><img src="{{ URL::asset(($order->image=='')?'/images/users/default-user-image.png':$order->image)}}" alt="user-image" class="avatar-md mr-2 rounded-circle" /></div>
                                                    <h5 class="font-size-16">Order: {{$order->id}}</h5><a href="/orders?search={{$order->id}}" class="float-right" style="margin-top: -30px;"><button type="button" class="btn btn-sm btn-outline-dark waves-effect waves-light">View order</button></a>
                                                    <p>{{$order->name}} {{$order->last_name}} <br>                                                
                                                    
                                                    <br>                                                
                                                    <hr>
                                                    <span> <i class="far fa-calendar-alt"></i> {{date('m.d.Y g:i A', strtotime($order->created))}}</span> <span class="float-right"><i class="far fa-grin-stars"></i> {{$order->statusename}}</span></p> 
                                                </div>                                                                                         
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div> -->


                        <!-- 
                            <div class="col-xl-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="card-title mb-4">Latest Ready for pick up Orders</h4>
                                        <div class="table-responsive">
                                            <table class="table table-centered table-vertical table-nowrap">
                                                <tbody>
                                                    @foreach($orders0 as $order)
                                                    <tr>
                                                        <td>{{$order->id}}</td>
                                                        <td>
                                                            <img src="{{ URL::asset(($order->image=='')?'/images/users/default-user-image.png':$order->image)}}" alt="user-image" class="avatar-xs mr-2 rounded-circle" /> {{$order->name}} {{$order->last_name}}
                                                        </td>
                                                        <td><span class="badge badge-pill badge-{{$order->statusecolor}}">{{$order->statusename}}</span></td>
                                                        <td>
                                                            {{date('m/d/Y g:i A', strtotime($order->created))}}
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-xl-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="card-title mb-4">Latest Delivered Orders</h4>
                                        <div class="table-responsive">
                                            <table class="table table-centered table-vertical table-nowrap">
                                                <tbody>
                                                    @foreach($orders as $order)
                                                    <tr>
                                                        <td>{{$order->id}}</td>
                                                        <td>
                                                            <img src="{{ URL::asset(($order->image=='')?'/images/users/default-user-image.png':$order->image)}}" alt="user-image" class="avatar-xs mr-2 rounded-circle" /> {{$order->name}} {{$order->last_name}}
                                                        </td>
                                                        <td><span class="badge badge-pill badge-{{$order->statusecolor}}">{{$order->statusename}}</span></td>
                                                        <td>
                                                            {{date('m/d/Y g:i A', strtotime($order->created))}}
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div> -->
                        </div>
                        <!-- end row -->
                    @else
                        @if(Auth::user()->role == 'sale')
                            <div class="col-xl-12">
                                <div class="card">
                                    <div class="card-body">
                                    <h4 class="card-title mb-4">Your referral link:</h4>
                                        <div class="row text-center mt-4">
                                            <div class="input-group">
                                                <input type="text" onlyread class="form-control" value="{{url('/register?ref='.str_replace('=','',base64_encode(Auth::user()->id)))}}" placeholder="Some link" id="copy-input">
                                                <span class="input-group-btn">
                                                    <button class="btn btn-primary" type="button" id="copy-button"
                                                        data-toggle="tooltip" data-placement="button"
                                                        title="Copy to Clipboard">
                                                        Copy
                                                    </button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                        <div class="row">
                            <div class="col-xl-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="card-title mb-4">Latest Ready for pick up Orders</h4>
                                        <div class="table-responsive">
                                            <table class="table table-centered table-vertical table-nowrap">
                                                <tbody>
                                                    @foreach($orders0 as $order)
                                                    <tr>
                                                        <td>{{$order->id}}</td>
                                                        <td>
                                                            <img src="{{ URL::asset(($order->image=='')?'/images/users/default-user-image.png':$order->image)}}" alt="user-image" class="avatar-xs mr-2 rounded-circle" /> {{$order->name}} {{$order->last_name}}
                                                        </td>
                                                        <td><span class="badge badge-pill badge-{{$order->statusecolor}}">{{$order->statusename}}</span></td>
                                                        <td>
                                                            {{date('m/d/Y g:i A', strtotime($order->created))}}
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-xl-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="card-title mb-4">Latest Delivered Orders</h4>
                                        <div class="table-responsive">
                                            <table class="table table-centered table-vertical table-nowrap">
                                                <tbody>
                                                    @foreach($orders as $order)
                                                    <tr>
                                                        <td>{{$order->id}}</td>
                                                        <td>
                                                            <img src="{{ URL::asset(($order->image=='')?'/images/users/default-user-image.png':$order->image)}}" alt="user-image" class="avatar-xs mr-2 rounded-circle" /> {{$order->name}} {{$order->last_name}}
                                                        </td>
                                                        <td><span class="badge badge-pill badge-{{$order->statusecolor}}">{{$order->statusename}}</span></td>
                                                        <td>
                                                            {{date('m/d/Y g:i A', strtotime($order->created))}}
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- end row -->
                    @endif
                    <!--
                    <div class="row">

                        <div class="col-xl-3">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-4">Monthly Earnings</h4>

                                    <div class="row text-center mt-4">
                                        <div class="col-6">
                                            <h5 class="font-size-20">$56241</h5>
                                            <p class="text-muted">Marketplace</p>
                                        </div>
                                        <div class="col-6">
                                            <h5 class="font-size-20">$23651</h5>
                                            <p class="text-muted">Total Income</p>
                                        </div>
                                    </div>

                                    <div id="morris-donut-example" class="morris-charts morris-charts-height" dir="ltr"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-4">Email Sent</h4>

                                    <div class="row text-center mt-4">
                                        <div class="col-4">
                                            <h5 class="font-size-20">$ 89425</h5>
                                            <p class="text-muted">Marketplace</p>
                                        </div>
                                        <div class="col-4">
                                            <h5 class="font-size-20">$ 56210</h5>
                                            <p class="text-muted">Total Income</p>
                                        </div>
                                        <div class="col-4">
                                            <h5 class="font-size-20">$ 8974</h5>
                                            <p class="text-muted">Last Month</p>
                                        </div>
                                    </div>

                                    <div id="morris-area-example" class="morris-charts morris-charts-height" dir="ltr"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-4">Monthly Earnings</h4>

                                    <div class="row text-center mt-4">
                                        <div class="col-6">
                                            <h5 class="font-size-20">$ 2548</h5>
                                            <p class="text-muted">Marketplace</p>
                                        </div>
                                        <div class="col-6">
                                            <h5 class="font-size-20">$ 6985</h5>
                                            <p class="text-muted">Total Income</p>
                                        </div>
                                    </div>

                                    <div id="morris-bar-stacked" class="morris-charts morris-charts-height" dir="ltr"></div>
                                </div>
                            </div>
                        </div>

                    </div>
                    -->
                    <!-- end row -->
@endsection

@section('footerScript')
<script>
var dataForChartDelivered = [
    @foreach($chartDelivered as $chartDeliver)
    { y: '{{date("F",strtotime("01-".$chartDeliver->date))}}', a: {{$chartDeliver->count}}},
    @endforeach
];
@if(isset($orders_7days_c))
var lineDataDelivered7days = [
    @foreach($orders_7days_c as $key=>$orders_7day)
    { y: '{{date("m.d.Y",strtotime($orders_7day->date))}}', a: {{$orders_7day->count}}, b: {{(isset($orders_7days[$key]))?$orders_7days[$key]->count:0}}},
    @endforeach
];
@else
var lineDataDelivered7days=[];
@endif
var app_android_users = {{$app_android_users}};
var app_android_drivers = {{$app_android_drivers}};
var app_ios_users = {{$app_ios_users}};
var app_ios_drivers = {{$app_ios_drivers}};
$(document).ready(function() {
    $('#copy-button,#copy-input').bind('click', function() {
        $("#copy-input").select();      
        try {
            var success = document.execCommand('copy');
            if (success) {
                toastr["info"]("Copied!");
            } else {
                toastr["error"]("Copy with Ctrl-c");
            }
        } catch (err) {
            toastr["error"]("Copy with Ctrl-c");
        }
    });
});
</script>
<!--Morris Chart-->
<script src="{{ URL::asset('/libs/morris.js/morris.js.min.js')}}"></script>
<script src="{{ URL::asset('/libs/raphael/raphael.min.js')}}"></script>
<script src="{{ URL::asset('/js/pages/dashboard.init.js')}}"></script>
@endsection