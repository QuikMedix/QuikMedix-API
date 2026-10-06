<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delivery labels - Order {{ $order->id }}</title>
    @include('orders.label-styles')
    <style>
        html, body { margin: 0; padding: 0; }
        .qm-label-document { display: block; }
        /* Dompdf needs explicit content dimensions to include the 2px borders in the paper size. */
        .qm-delivery-label { width: 302px; height: 402px; box-sizing: content-box; }
    </style>
</head>
<body>
    <div id="ticket_{{ $order->id }}" class="qm-label-document">
        @include('orders.label', ['order' => $order, 'patient' => $patient])
    </div>
</body>
</html>
