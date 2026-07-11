<div class="alert alert-info" role="alert">
    <strong>{{date('m.d.Y', strtotime($invoice->date_from))}}</strong> - <strong> {{date('m.d.Y', strtotime($invoice->date_to))}}</strong> 
</div>

@foreach($orders as $order)
<div class="offcanvas-orders text-black mb-3">
    <h6 class="mt-1 mb-1 d-inline-block">Order #{{$order->id}}</h6> 
    @if(($order->delivery_time=='ASAP Delivery'))
        <span style="font-size:10px;width:110px;white-space:normal;padding: 5px;color: white;float: right;" class="badge badge-pill badge-danger">{{$order->delivery_time}}</span>
    @elseif($order->delivery_time=='Same day delivery')
        <span style="font-size:10px;width:110px;white-space:normal;padding: 5px;color: white;float: right;" class="badge badge-pill badge-info">{{$order->delivery_time}}</span>
    @elseif($order->delivery_time=='After Hours Delivery')
        <span style="font-size:10px;width:110px;white-space:normal;padding: 5px;color: white;float: right;" class="badge badge-pill badge-danger">{{$order->delivery_time}}</span>
    @else
        <span style="font-size:10px;width:110px;white-space:normal;padding: 5px;color: white;float: right;" class="badge bg-primary badge-pill badge-info">{{$order->delivery_time}}</span>
    @endif
    <p class="mb-1">
    Created: {{date('m.d.Y g:i A', strtotime($order->created))}}<br>    
    Delivered: {{date('m.d.Y g:i A', strtotime($order->finish))}}<br>
    Co-Pay: ${{number_format($order->copay,2)}}</p>                                                
    <h5 class="list-inline-item mt-1 mb-0" >Cost: ${{number_format($order->tariff,2)}}</h5>
    <a target="_blank" href="/orders/{{$order->pharmacy_id}}/show/{{$order->id}}" class="float-right"><button type="button" class="btn btn-outline-dark waves-effect waves-light py-1 px-1" style="font-size: 10px;">View Order</button></a>
</div>
@endforeach
