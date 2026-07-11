@extends('layouts.master')

@section('title') Billing @endsection

@section('headerCss')

    <link rel="stylesheet" href="{{ URL::asset('/css/bootstrap-lib.min.css')}}">
    <!-- Responsive Table css -->
    <link href="{{ URL::asset('/libs/rwd-table/rwd-table.min.css')}}" rel="stylesheet" type="text/css" /> <!-- Bootstrap Css -->
    <style>
        .balance-inp {
            display:flex;
        }
        .refill-inp {
            display:flex;
        }
        .offcanvas-orders {
            padding: 10px 15px 10px 25px;
            border-radius: 10px;
            background: #ffffff;
            box-shadow: 0 -3px 21px 0 rgb(122 111 190 / 5%), 0 6px 10px 0 rgb(122 111 190 / 20%);
        }
        .offcanvas-orders:hover {
            background: #f2fbf6;
        }
        .corrections-inp {
            display:inline-flex;
        }
        .billing-stat .dropdown-menu.show {
            top: 0% !important;
        }
    </style>
@endsection

@section('content')
 <!-- start page title -->
                    
                    <div class="row mb-4">
                        <div class="col-12">
                            <div  class="card-title-5">
                                <div class="row">
                                    <div class="col-8">
                                        <h5 class="mt-2"><i class="mdi mdi-medical-bag"></i> {{$pharmacy->name}} - <i class="mdi mdi-google-maps"></i> {{$pharmacy->address}}</h5> 
                                    </div> 
                                    <div class="col-4 text-right">
                                    @if((Auth::user()->role == 'superadmin' || Auth::user()->role == 'admin' || Auth::user()->role == 'dispadmin'))
                                        @if($pharmacy->balance_ban>0)
                                        <form method="post" style="display: inline-block;">
                                            @csrf
                                            <input type="hidden" name="unblock" value="1">
                                            <button class="btn btn-danger" type="button" onclick="if(confirm('Are you sure?')){$(this).parent('form').submit();}">Unblock Balance</button>
                                        </form>
                                        @endif
                                        <a href="/billing/{{ $pharmacy->id }}"><button type="button" class="btn btn-dark waves-effect waves-light">Billing</button></a>
                                        <a href="/pharmacys/edit/{{ $pharmacy->id }}"><button type="button" class="btn btn-outline-dark waves-effect waves-light">Edit</button></a>                    
                                    @endif   
                                    </div>  
                                </div>                                            
                            </div>
                        </div>    
                    </div>
                  
                    
                    <!-- end page title -->

                    <div class="row">
                        <div class="col-xl-4 col-sm-6">
                            <div class="card mini-stat bg-primary">
                                <div class="card-body mini-stat-img">
                                    <div class="mini-stat-icon">
                                        <i class="mdi mdi-currency-usd float-right"></i>
                                    </div>
                                    <div class="text-light">
                                        <h6 class="text-uppercase mb-3 font-size-16">Account</h6>
                                        <h4 class="mb-4" style="display: flex;align-items: center;"><b class="balance">{{($pharmacy->balance<0)?'-$'.round(abs($pharmacy->balance),2):'+$'.round($pharmacy->balance,2)}}</b>
                                        <form class="balance-inp" method="POST" style="display: none;">
                                            @csrf 
                                            <input type="number" step="0.01" class="form-control" value="{{round($pharmacy->balance,2)}}" style="width:100px;font-weight:500;line-height:1.2;font-size:1.21875rem;" name="balance-change">
                                             <button type="submit" class="btn btn-success">Save</button>
                                        </form>
                                        <form class="refill-inp" method="POST" style="display: none;">
                                            @csrf 
                                            <input type="number" step="0.01" class="form-control" value="0" style="width:100px;font-weight:500;line-height:1.2;font-size:1.21875rem;" name="refill-amount">
                                             <button type="submit" class="btn btn-success">Refill</button>
                                        </form>
                                        </h4>
                                        <span class="">
                                            <a style="cursor:pointer;" onclick="$('.balance').hide();$('.refill-inp').show();" class="text-light mr-2"> Refill <i class="mdi mdi-plus-circle-outline"></i> </a> 
                                            @if(Auth::user()->role=="admin" || Auth::user()->role == 'superadmin')
                                            <a style="cursor:pointer;" onclick="$('.balance').hide();$('.balance-inp').show();" class="text-light"> Edit <i class="mdi mdi-circle-edit-outline"></i></a>
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-sm-6">
                                <div class="card mini-stat bg-primary" style="background-color: #2b3a4a !important;">
                                    <div class="card-body mini-stat-img">
                                        <div class="mini-stat-icon">
                                            <i class="mdi mdi-credit-card-multiple-outline float-right"></i>
                                        </div>
                                        <div class="text-light">
                                            <h6 class="text-uppercase mb-3 font-size-16">Payment Method</h6>
                                            @if(!empty($payment_account))
                                            @if($payment_account->type=="card")
                                            <h4 class="mb-4">Card - XXXX{{substr($payment_account->card,-4)}}</h4>
                                            @else
                                            <h4 class="mb-4">Bank Account</h4>
                                            @endif
                                            @else
                                            <h4 class="mb-4">-</h4>
                                            @endif
                                            <span class="ms-2"><a style="cursor:pointer;" onclick="var win=window.open('/payment-method/{{$pharmacy_id}}','Add Payment Method','width=800,height=700');var timer=setInterval(function(){if(win.closed){clearInterval(timer);document.location.reload();}},600);" class="text-light"> Edit <i class="mdi mdi-circle-edit-outline"></i></a></span>
                                        </div>
                                    </div>
                                </div>
                        </div>                       
                        <div class="col-xl-4 col-sm-6">
                                <div class="card mini-stat bg-primary" style="background-color: #2b3a4a !important;">
                                    <div class="card-body mini-stat-img">
                                        <div class="mini-stat-icon">
                                            <i class="mdi mdi-map-marker-distance float-right"></i>
                                        </div>
                                        <div class="text-light">
                                            <h6 class="text-uppercase mb-3 font-size-16">Current plan</h6>
                                            <h4 class="mb-4">{{$pharmacy_plan->name}}</h4>
                                            <span class="ms-2">Details <i class="mdi mdi-link-variant"></i></span>
                                        </div>
                                    </div>
                                </div>
                        </div>                       
                        @if(!empty($error))
                            <div class="alert alert-danger" role="alert">{{ $error }}</div>
                        @endif
                        @foreach($invoices as $invoice)
                        <div class="col-12">
                            <div class="card border-billing">
                                <div class="card-body">
                                    <div class="row">  
                                        <div class="col-md-1 billing-stat text-center">
                                            <i class="fas fa-file-invoice-dollar"></i>                                        
                                        </div>                                      
                                        <div class="col-md-2 billing-stat text-center text-black border-right">                                        
                                            #{{$invoice->id}} </br>
                                            {{date('m.d.Y', strtotime($invoice->date_from))}} - {{date('m.d.Y', strtotime($invoice->date_to))}}
                                        </div>                                        
                                        <div class="col-md-1 billing-stat text-center text-black border-right mt-2" >
                                            @if($invoice->payed=='1')
                                            <i class="mdi mdi-checkbox-blank-circle text-success"></i> Paid
                                            @else
                                            <i class="mdi mdi-checkbox-blank-circle text-warning"></i> Waiting payment
                                            @endif
                                        </div>
                                        <div class="col-md-1 billing-stat text-center text-black border-right">
                                        Amount</br>
                                        ${{number_format($invoice->amount,2)}}
                                        </div>
                                        <div class="col-md-1 billing-stat text-center text-black border-right">
                                        Orders</br>
                                        {{$invoice->count}}
                                        </div>
                                        <div class="col-md-1 billing-stat text-center text-black border-right">
                                        Co-pay </br>
                                        ${{number_format($invoice->copay,2)}}
                                        </div>
                                        <div class="col-md-1 billing-stat text-center text-black border-right">
                                        Correction</br>
                                        <p class="corrections mb-0">
                                            {{($invoice->corrections<0)?'-$'.number_format(abs($invoice->corrections),2):'+$'.number_format($invoice->corrections,2)}}
                                            @if((Auth::user()->role=="admin" || Auth::user()->role == 'superadmin') && $invoice->payed=='0')
                                            <a style="cursor:pointer;" onclick="$(this).parent('.corrections').hide();$(this).parent('.corrections').next('.corrections-inp').show();" class="text-light"><i style="color:#000;" class="mdi mdi-circle-edit-outline"></i></a>
                                            @endif
                                        </p>
                                        @if($invoice->payed=='0')
                                            <form class="corrections-inp" method="POST" style="display: none;">
                                                @csrf 
                                                <input type="hidden" name="invoice_id" value="{{$invoice->id}}">
                                                <input type="number" step="0.01" class="form-control" value="{{round($invoice->corrections,2)}}" style="width:70px;" name="corrections-change">
                                                <button type="submit" class="btn btn-primary"><i class="mdi mdi-content-save-edit"></i></button>
                                            </form>
                                        @endif
                                        </div>
                                        <div class="col-md-1 billing-stat text-center text-black border-right">
                                        Total</br>
                                        @if($invoice->payed=='1')
                                        ${{number_format($invoice->payed_amount,2)}}
                                        @else
                                        @if($pharmacy->copay_bill=='1')
                                        @if((($invoice->amount+$invoice->corrections)-$invoice->copay)<0)
                                        $0
                                        @else
                                        ${{number_format((($invoice->amount+$invoice->corrections)-$invoice->copay),2)}}
                                        @endif
                                        @else
                                        ${{number_format(($invoice->amount+$invoice->corrections),2)}}
                                        @endif
                                        @endif
                                        </div>
                                        <div class="col-md-3 billing-stat text-center mt-2">                                        
                                            <button type="button" class="btn btn-secondary btn-sm waves-effect load_orders" data-id="{{$invoice->id}}" data-bs-toggle="offcanvas" data-bs-target="#offcanvasWithBothOptions" aria-controls="offcanvasWithBothOptions">Orders <i class="mdi mdi-history"></i></button>                                                            
                                            <a href="/billing/{{$pharmacy_id}}/print/{{$invoice->id}}" target="_blank" rel="noopener noreferrer"><button class="btn btn-primary btn-sm waves-effect">Invoice <i class="mdi mdi-file-pdf"></i></button></a>
                                            <a href="/billing/{{$pharmacy_id}}/print_report/{{$invoice->id}}" target="_blank" rel="noopener noreferrer"><button class="btn btn-primary btn-sm waves-effect">Report <i class="mdi mdi-file-pdf"></i></button></a>
                                            @if($invoice->payed=='0')
                                            <form method="post" style="display: inline-block;">
                                                @csrf
                                                <input type="hidden" name="invoice_id" value="{{$invoice->id}}">
                                                <input type="hidden" name="pay" value="1">
                                                <a class="btn btn-success btn-sm waves-effect" type="button" onclick="if(confirm('Are you sure?')){$(this).parent('form').submit();}" style="color: #fff;">Pay</a>
                                            </form>
                                            <div class="btn-group">
                                                <button class="btn btn-warning btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" style="color: #fff;">Paid</button>
                                                <div class="dropdown-menu">
                                                    <form method="post">
                                                        @csrf
                                                        <input type="hidden" name="invoice_id" value="{{$invoice->id}}">
                                                        <input type="hidden" name="paid" value="1">
                                                        <input type="hidden" name="type" value="check">
                                                        <button class="dropdown-item" type="button" onclick="if(confirm('Are you sure?')){$(this).parent('form').submit();}"><i class="mdi mdi-cash-register"></i> Check</button>
                                                    </form>
                                                    <form method="post">
                                                        @csrf
                                                        <input type="hidden" name="invoice_id" value="{{$invoice->id}}">
                                                        <input type="hidden" name="paid" value="1">
                                                        <input type="hidden" name="type" value="cash">
                                                        <button class="dropdown-item" type="button" onclick="if(confirm('Are you sure?')){$(this).parent('form').submit();}"><i class="mdi mdi-cash-multiple"></i> Cash</button>
                                                    </form>
                                                </div>
                                            </div>
                                            @endif                                            
                                        </div> 
                                    </div>
                                </div>
                            </div>                            
                        </div>
                        @endforeach
                       
                        @if(Auth::user()->role=="admin" || Auth::user()->role == 'superadmin')
                        <div class="col-12" style="text-align: right;margin-top: 10px;">
                                <a href="/billing/{{$pharmacy_id}}/invoice/add" class="addorder"><button class="btn btn-primary">Create Invoice</button></a>   
                        </div>                                                    
                        @endif
                    </div>
                    <!-- end row -->  
                    <div class="offcanvas offcanvas-start" data-bs-scroll="true" tabindex="-1" id="offcanvasWithBothOptions" aria-labelledby="offcanvasWithBothOptionsLabel">
                        <div class="offcanvas-header">
                            <h5 class="offcanvas-title" id="offcanvasWithBothOptionsLabel">Invoice #</h5>
                            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                        </div>
                        <div class="offcanvas-body">
                            
                        </div>
                    </div>
@endsection

@section('footerScript')


<script src="{{ URL::asset('/js/jquery.bootstrap-touchspin.min.js')}}"></script>
<script src="{{ URL::asset('/js/jquery-ui.min.js')}}" type="text/javascript"></script>

            <!-- Responsive Table js -->
            <script src="{{ URL::asset('/libs/rwd-table/rwd-table.min.js')}}"></script>

            <!-- Init js -->
            <script src="{{ URL::asset('/js/pages/table-responsive.init.js')}}"></script>
            <script>
                $(document).ready(function(){
                    @if(Auth::user()->role=='medic')
                    @if(Auth::user()->pharmacy_balance()<0)
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
                    $('.load_orders').on('click',function(){
                        $("#offcanvasWithBothOptionsLabel").text("Invoice #"+$(this).data('id'));
                        $.get(location.href+'/orders/'+$(this).data('id')).done(function( data ) {
                            $("#offcanvasWithBothOptions .offcanvas-body").html(data);
                        });
                    });
                    $('.text-reset').on('click',function(){
                        $("#offcanvasWithBothOptions .offcanvas-body").html("");
                    });
                    $("#search").keyup(function(){
                    _this = this;
                        $.each($("#mytable tbody tr"), function() {
                            if($(this).find("td").filter(":not(.action)").text().toLowerCase().indexOf($(_this).val().toLowerCase()) === -1) {
                                $(this).hide();
                            } else {
                                $(this).show();                
                            };
                        });
                    });
                });
            </script>
@endsection