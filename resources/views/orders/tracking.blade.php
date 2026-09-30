<div class="card">
    <div class="card-body">
        <h5 class="qm-section-title">Order status</h5>
        <p>Current status: <x-order-status :name="$order->statusename ?? null" :color="$order->statusecolor ?? null" /></p>
        @can('change-order-status')
            <form method="POST" action="{{ route('orders.status.update', [$order->pharmacy_id, $order->id]) }}" class="form-inline mb-3">
                @csrf
                <label for="order-status" class="mr-2">Change status</label>
                <select id="order-status" name="statuse" class="form-control mr-2" required>
                    <option value="">Choose status…</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status->id }}" @selected((string) old('statuse', $order->statuse_id) === (string) $status->id)>{{ (int) $status->id === 7 ? 'Warehouse ('.$status->name.')' : $status->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary">Update status</button>
            </form>
            @error('statuse')
                <p class="text-danger" role="alert">{{ $message }}</p>
            @enderror
        @endcan
        @if(Auth::user()->hasAnyRole('medic', 'superadmin', 'admin', 'dispadmin', 'driver', 'logist'))
            <details>
                <summary>QR codes for {{ max(1, (int) $order->count_bags) }} bag(s)</summary>
                <div class="d-flex flex-wrap">
                    @for($bag = 1; $bag <= max(1, (int) $order->count_bags); $bag++)
                        <figure class="text-center mr-3 mt-3">
                            <img src="data:image/png;base64,{{ DNS2D::getBarcodePNG($order->id.'_'.$bag, 'QRCODE', 5, 5) }}" alt="QR code for order {{ $order->id }}, bag {{ $bag }}" style="padding: 20px; background: white;">
                            <figcaption>Bag {{ $bag }}</figcaption>
                        </figure>
                    @endfor
                </div>
            </details>
            <a href="{{ route('orders.ticket', ['order_id' => $order->id, 'print' => 1]) }}" class="btn btn-outline-secondary mt-2" target="_blank" rel="noopener">Print QR labels</a>
        @endif
    </div>
</div>
