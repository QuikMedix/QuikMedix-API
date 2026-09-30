<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR labels — Order {{ $order->id }}</title>
    <link rel="stylesheet" href="{{ asset('css/bootstrap-lib.min.css') }}">
    <style>@media print { .print-controls { display: none; } }</style>
</head>
<body>
    <div class="print-controls p-3">
        <button type="button" class="btn btn-primary" onclick="window.print()">Print QR labels</button>
    </div>
    @include('orders.ticket')
    <script>window.addEventListener('load', function () { window.print(); });</script>
</body>
</html>
