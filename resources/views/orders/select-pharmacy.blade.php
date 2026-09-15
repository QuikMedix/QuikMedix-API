@extends('layouts.master')

@section('title', $title)
@section('headerCss')@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7 col-xl-6">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">{{ $title }}</h4>
                @if($pharmacies->isEmpty())
                    <p>No active pharmacies are available. Add a pharmacy before creating an order.</p>
                    <a href="/pharmacys/add" class="btn btn-primary">Add Pharmacy</a>
                    <a href="/orders" class="btn btn-outline-secondary ml-2">Back to Orders</a>
                @else
                    <p>Choose the pharmacy for this order. Its patients and delivery options will appear on the next page.</p>
                    <form method="get" action="{{ route($selectionRoute) }}">
                        <div class="form-group">
                            <label for="order-pharmacy">Pharmacy</label>
                            <select id="order-pharmacy" name="pharmacy_id" class="form-control" required>
                                <option value="">Choose a pharmacy…</option>
                                @foreach($pharmacies as $pharmacy)
                                    <option value="{{ $pharmacy->id }}">{{ $pharmacy->name }} — {{ $pharmacy->address }}</option>
                                @endforeach
                            </select>
                            @error('pharmacy_id')
                                <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary">Continue</button>
                        <a href="/orders" class="btn btn-outline-secondary ml-2">Cancel</a>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('footerScript')@endsection
