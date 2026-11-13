@can('review-delivery-report', $order->pharmacy_id)
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
    <p class="mb-2">Dispatch must obtain pharmacy approval through live chat before confirming Unavailable or Refused. The delivery stays in the driver’s app until dispatch confirms the final status.</p>

    <div class="mb-3">
        <a href="{{ url(config('chatify.routes.prefix').'/'.$pendingException->driver_id) }}" class="btn btn-sm btn-outline-dark mr-1 mb-1" target="_blank" rel="noopener">Chat with driver</a>
        @foreach($pharmacyStaff as $staff)
            <a href="{{ url(config('chatify.routes.prefix').'/'.$staff->id) }}" class="btn btn-sm btn-outline-dark mr-1 mb-1" target="_blank" rel="noopener">
                Pharmacy chat: {{ trim($staff->name.' '.$staff->last_name) }}
            </a>
        @endforeach
        @if($pharmacyStaff->isEmpty())
            <p>No active pharmacy chat contact is available. Keep this report pending until pharmacy approval can be obtained in live chat.</p>
        @endif
    </div>

    <div class="d-flex flex-wrap align-items-start">
        <form method="POST" action="{{ route('orders.exceptions.approve', [$order->pharmacy_id, $order->id, $pendingException->id]) }}" class="mr-3 mb-2">
            @csrf
            <label for="dispatch-status">Final status</label>
            <select id="dispatch-status" name="statuse" class="form-control mb-2" required>
                <option value="8" @selected((int) old('statuse', $pendingException->requested_status_id) === 8)>Unavailable</option>
                <option value="9" @selected((int) old('statuse', $pendingException->requested_status_id) === 9)>Refused</option>
            </select>
            <div class="form-check mb-2">
                <input id="pharmacy-approval" name="pharmacy_approval" type="checkbox" value="1" class="form-check-input" required @checked(old('pharmacy_approval'))>
                <label for="pharmacy-approval" class="form-check-label">I obtained pharmacy approval for this status through live chat.</label>
            </div>
            <button type="submit" class="btn btn-danger">Confirm status and remove delivery stop</button>
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
    @error('pharmacy_approval')
        <p class="text-danger mb-0" role="alert">{{ $message }}</p>
    @enderror
</div>
@endcan
