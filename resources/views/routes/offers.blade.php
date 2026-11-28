@extends('layouts.master')

@section('title') {{ $title }} @endsection

@section('content')
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="card">
                                <div class="card-body">
                                    <x-flash-messages />
                                    @if ($errors->any())
                                        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
                                    @endif
                                    <h4 class="card-title">Orders waiting for a driver</h4>
                                    <p class="text-muted">Pick a zone, tick the orders for one route and publish it. Drivers see the offer in the app under Available Routes with its stops, miles and pay; the first driver to take it gets the orders on their route.</p>
                                    <div class="mb-2">
                                        @forelse($zones as $each)
                                            <a href="/route-offers?zone={{ $each->id }}" class="btn btn-sm mb-1 {{ $zone && $zone->id === $each->id ? 'btn-primary' : 'btn-outline-primary' }}">{{ $each->name }}, {{ $each->state }} <span class="badge badge-light">{{ $counts[$each->id] }}</span></a>
                                        @empty
                                            @if(($counts[''] ?? 0) == 0)
                                                <span class="text-muted">No order is waiting for a driver.</span>
                                            @endif
                                        @endforelse
                                        @if(($counts[''] ?? 0) > 0)
                                            <a href="/route-offers?zone=none" class="btn btn-sm mb-1 {{ $noZone ? 'btn-secondary' : 'btn-outline-secondary' }}">No zone <span class="badge badge-light">{{ $counts[''] }}</span></a>
                                        @endif
                                    </div>

                                    @if($noZone)
                                        <p class="text-muted mt-3">These orders are in no zone: the patient's address has no map position, or it is outside every zone in use. They cannot go into an offer; assign them on the Routes pages, or fix the address and load the zones it needs in Settings, Dispatch Zones.</p>
                                    @endif

                                    @if($zone || $noZone)
                                    <form method="POST" action="/route-offers" id="offer-form">
                                        @csrf
                                        @if($zone)
                                        <input type="hidden" name="zone_id" value="{{ $zone->id }}">
                                        <div class="form-row align-items-end mt-3">
                                            <div class="form-group col-md-2">
                                                <label for="offer-date">Delivery day</label>
                                                <input type="date" name="delivery_date" id="offer-date" value="{{ old('delivery_date', now()->toDateString()) }}" class="form-control">
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="offer-pickup">Pickup time</label>
                                                <input type="text" name="pickup_time" id="offer-pickup" value="{{ old('pickup_time') }}" maxlength="20" placeholder="9:00 AM" class="form-control">
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="offer-pay">Route pays</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend"><div class="input-group-text">$</div></div>
                                                    <input type="number" name="pay" id="offer-pay" value="{{ old('pay') }}" min="0" step="0.01" class="form-control" data-per-stop="{{ (float) $zone->pay_per_stop }}">
                                                </div>
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label for="offer-note">Note for drivers</label>
                                                <input type="text" name="note" id="offer-note" value="{{ old('note') }}" maxlength="255" class="form-control">
                                            </div>
                                            <div class="form-group col-md-2">
                                                <button type="submit" class="btn btn-success btn-block waves-effect">Publish route</button>
                                            </div>
                                        </div>
                                        <p class="text-muted small"><span id="offer-summary"></span> The target for {{ $zone->name }} is {{ $zone->target_stops }} stops at ${{ number_format((float) $zone->pay_per_stop, 2) }} a stop. Leave the pay empty to use that rate.</p>
                                        @endif
                                        <div class="table-responsive">
                                            <table class="table table-striped mb-0">
                                                <thead>
                                                    <tr>
                                                        @if($zone)<th><input type="checkbox" id="offer-all" title="Tick or clear every order"></th>@endif
                                                        <th>Order</th>
                                                        <th>Patient</th>
                                                        <th>Address</th>
                                                        <th>Pharmacy</th>
                                                        <th>Delivery</th>
                                                        <th>Bags</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php $stops = []; @endphp
                                                    @foreach($orders as $order)
                                                    @php
                                                        // The first orders up to the zone's target are ticked; two orders for one patient are one stop.
                                                        $stops[$order->user_id] = true;
                                                        $ticked = $zone && count($stops) <= $zone->target_stops;
                                                    @endphp
                                                    <tr>
                                                        @if($zone)<td><input type="checkbox" name="order_ids[]" value="{{ $order->id }}" data-patient="{{ $order->user_id }}" class="offer-order" {{ $ticked ? 'checked' : '' }}></td>@endif
                                                        <td><a href="/orders/{{ $order->pharmacy_id }}/show/{{ $order->id }}">#{{ $order->id }}</a></td>
                                                        <td>{{ trim($order->name.' '.$order->last_name) }}</td>
                                                        <td>{{ $order->delivery_address }}</td>
                                                        <td>{{ $order->pharmacy_name }}@if($order->ready === '1') <span class="badge badge-success">Ready</span>@endif</td>
                                                        <td>{{ $order->delivery_time }}@if($order->delivery_date) <span class="text-muted small">{{ date('m/d', strtotime($order->delivery_date)) }}</span>@endif</td>
                                                        <td>{{ $order->count_bags }}@if($order->fridge == 1) <span class="badge badge-info">Fridge</span>@endif</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </form>
                                    @endif
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Route offers</h4>
                                    <div class="table-responsive">
                                        <table class="table table-striped mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Route</th>
                                                    <th>Zone</th>
                                                    <th>Delivery day</th>
                                                    <th>Pickup</th>
                                                    <th>Stops</th>
                                                    <th>Miles</th>
                                                    <th>Pays</th>
                                                    <th>Status</th>
                                                    <th class="text-right">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($offers as $offer)
                                                <tr>
                                                    <td><strong>{{ $offer->code }}</strong>@if($offer->note)<br><span class="text-muted small">{{ $offer->note }}</span>@endif</td>
                                                    <td>{{ $offer->zone_name }}</td>
                                                    <td>{{ $offer->delivery_date ? date('m/d/Y', strtotime($offer->delivery_date)) : '' }}</td>
                                                    <td>{{ $offer->pickup_time }}</td>
                                                    <td>{{ $offer->stops }}@if($offer->orders_gone > 0)<br><span class="text-danger small">{{ $offer->orders_gone }} {{ $offer->orders_gone == 1 ? 'order has' : 'orders have' }} left this route since. Withdraw it and publish again to change what it pays.</span>@endif</td>
                                                    <td>{{ $offer->miles === null ? '' : 'about '.round((float) $offer->miles) }}</td>
                                                    <td>${{ number_format((float) $offer->pay, 2) }}</td>
                                                    <td>
                                                        @if($offer->status === 'open')
                                                            <i class="mdi mdi-checkbox-blank-circle text-warning"></i> Offered to drivers
                                                        @elseif($offer->status === 'accepted')
                                                            <i class="mdi mdi-checkbox-blank-circle text-success"></i> Taken by {{ trim($offer->driver_name.' '.$offer->driver_last_name) ?: 'driver #'.$offer->driver_id }}
                                                            <span class="text-muted small">{{ $offer->accepted_at ? \Illuminate\Support\Carbon::parse($offer->accepted_at)->format('m/d g:i A') : '' }}</span>
                                                        @else
                                                            <i class="mdi mdi-checkbox-blank-circle text-secondary"></i> Withdrawn
                                                        @endif
                                                    </td>
                                                    <td class="text-right">
                                                        @if($offer->status === 'open')
                                                            <form method="POST" action="/route-offers/{{ $offer->id }}/withdraw" class="d-inline">
                                                                @csrf
                                                                <button type="submit" class="btn btn-outline-danger btn-sm waves-effect">Withdraw</button>
                                                            </form>
                                                        @elseif($offer->status === 'accepted')
                                                            <a href="/routes-list/driver/{{ $offer->driver_id }}" class="btn btn-primary btn-sm waves-effect">Driver's route</a>
                                                        @endif
                                                    </td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="9" class="text-center text-muted">No route has been offered yet.</td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
@endsection

@section('footerScript')
    <script src="{{ URL::asset('/js/route-offer-form.js') }}"></script>
@endsection
