@include('orders.label-styles')
<div id="ticket_{{ $order->id }}" class="qm-label-document">
    @include('orders.label', ['order' => $order, 'patient' => $patients[$order->user_id]])
</div>
