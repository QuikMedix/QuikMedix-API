<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Delivery labels</title>
    @include('orders.label-styles')
</head>
<body>
    <div class="print-controls">
        <button type="button" onclick="window.print()">Print QR labels</button>
    </div>
    <div class="qm-label-document">
        @foreach ($orders as $order)
            @if (isset($patients[$order->user_id]))
                @include('orders.label', ['order' => $order, 'patient' => $patients[$order->user_id]])
            @endif
        @endforeach
    </div>
    <script>window.addEventListener('load', function () { window.print(); });</script>
</body>
</html>
