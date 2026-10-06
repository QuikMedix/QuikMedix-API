@php
    $bagCount = max(1, (int) $order->count_bags);
    $orderNumber = 'QM-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT);
    $recipientName = trim(($patient->name ?? '').' '.($patient->last_name ?? ''));
    $recipientAddress = implode(', ', array_filter([
        trim($patient->address ?? ''),
        empty($patient->apartment) ? '' : 'Apt '.$patient->apartment,
        trim($patient->zip ?? ''),
    ], static fn (string $part): bool => $part !== ''));
@endphp
@for ($bag = 1; $bag <= $bagCount; $bag++)
    <section class="qm-delivery-label" aria-label="Order {{ $orderNumber }}, package {{ $bag }} of {{ $bagCount }}">
        <div class="qm-label-header">
            <div class="qm-label-brand">QuikMedix</div>
            <div class="qm-label-tagline">Your health - our priority</div>
        </div>
        @if ($order->fridge > 0 && $bag === 1)
            <div class="qm-label-handling">
                <div class="qm-label-refrigerated">
                    <span class="qm-label-snowflake" aria-hidden="true">&#10052;</span>
                    KEEP REFRIGERATED
                </div>
                <div class="qm-label-temperature">Follow pharmacy temperature instructions.</div>
            </div>
        @endif
        <div class="qm-label-recipient">
            <span class="qm-label-caption">DELIVER TO</span>
            <div class="qm-label-name {{ mb_strlen($recipientName) > 28 ? 'qm-label-name-compact' : '' }}">{{ $recipientName }}</div>
            <div class="qm-label-address {{ mb_strlen($recipientAddress) > 90 ? 'qm-label-address-compact' : '' }}">{{ $recipientAddress }}</div>
        </div>
        <div class="qm-label-code">
            <div class="qm-label-qr">
                <img src="data:image/png;base64,{{ DNS2D::getBarcodePNG($order->id.'_'.$bag, 'QRCODE', 5, 5, [0, 0, 0], [255, 255, 255]) }}" alt="qrcode" width="84" height="84">
            </div>
            <div class="qm-label-order">
                <span class="qm-label-order-caption">ORDER NUMBER</span>
                <strong class="qm-label-order-number {{ strlen($orderNumber) > 9 ? 'qm-label-order-number-compact' : '' }}">{{ $orderNumber }}</strong>
                <span class="qm-label-scan-help">Scan to view order</span>
            </div>
        </div>
        <div class="qm-label-package {{ $bagCount > 9 ? 'qm-label-package-compact' : '' }}">PACKAGE {{ $bag }} OF {{ $bagCount }}</div>
        @if ($order->signature && in_array((int) ($order->delivery_method_id ?? 0), [3, 4], true) && $bag === 1)
            <div class="qm-label-signature">SIGNATURE REQUIRED</div>
        @endif
    </section>
@endfor
