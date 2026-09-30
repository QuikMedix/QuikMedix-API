<?php

namespace App\Http\Controllers;

use App\Actions\ChangeOrderStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class OrderStatusController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa', 'active']);
    }

    public function update(Request $request, int $pharmacy_id, int $order_id, ChangeOrderStatus $changeOrderStatus): RedirectResponse
    {
        Gate::authorize('change-order-status');
        Gate::authorize('access-order', $order_id);
        abort_unless(DB::table('orders')->where('id', $order_id)->where('pharmacy_id', $pharmacy_id)->exists(), 404);

        $validated = $request->validate([
            'statuse' => ['required', 'integer', 'exists:statuses,id'],
        ], [
            'statuse.required' => 'Choose an order status.',
            'statuse.integer' => 'Choose a valid order status.',
            'statuse.exists' => 'Choose a valid order status.',
        ]);

        $changeOrderStatus->handle($order_id, (int) $validated['statuse']);

        return redirect()->route('orders.show', [$pharmacy_id, $order_id])->with('success', 'Order status updated.');
    }
}
