@php($reason = \App\Actions\RequestDispatchApproval::reasonLabel($pendingException->requested_status_id))
<div class="alert alert-warning" role="alert" id="dispatch-review">
    <h5 class="alert-heading">Driver report needs approval: {{ $reason }}</h5>
    <p class="mb-1">
        Driver {{ trim($pendingException->driver_name.' '.$pendingException->driver_last_name) }}
        @if($pendingException->driver_phone) ({{ $pendingException->driver_phone }}) @endif
        reported this stop at {{ \Illuminate\Support\Carbon::parse($pendingException->created_at)->format('M j, g:i A') }}.
    </p>
    @if($pendingException->driver_note)
        <p class="mb-1">Driver note: {{ $pendingException->driver_note }}</p>
    @endif
    <p class="mb-2">The order stays open until you verify the issue and approve or reject it.</p>

    <div class="mb-3">
        <a href="{{ url(config('chatify.routes.prefix').'/'.$pendingException->driver_id) }}" class="btn btn-sm btn-outline-dark mr-1 mb-1" target="_blank" rel="noopener">Chat with driver</a>
        @foreach($pharmacyStaff as $staff)
            <a href="{{ url(config('chatify.routes.prefix').'/'.$staff->id) }}" class="btn btn-sm btn-outline-dark mr-1 mb-1" target="_blank" rel="noopener">
                Chat with {{ trim($staff->name.' '.$staff->last_name) }}
            </a>
        @endforeach
    </div>

    <div class="d-flex flex-wrap align-items-start">
        <form method="POST" action="{{ route('orders.exceptions.approve', [$order->pharmacy_id, $order->id, $pendingException->id]) }}" class="mr-3 mb-2">
            @csrf
            <button type="submit" class="btn btn-danger">Approve: mark {{ strtolower($reason) }}</button>
        </form>
        <form method="POST" action="{{ route('orders.exceptions.reject', [$order->pharmacy_id, $order->id, $pendingException->id]) }}" class="form-inline mb-2">
            @csrf
            <label for="review-note" class="sr-only">Instructions for the driver</label>
            <input id="review-note" name="review_note" class="form-control mr-2" maxlength="1000" required placeholder="Instructions for the driver (e.g. leave with doorman)" value="{{ old('review_note') }}">
            <button type="submit" class="btn btn-outline-secondary">Reject and send back to driver</button>
        </form>
    </div>
    @error('review_note')
        <p class="text-danger mb-0" role="alert">{{ $message }}</p>
    @enderror
</div>
