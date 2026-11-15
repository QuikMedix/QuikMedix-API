<?php

namespace App\Http\Controllers\Api;

use App\Models\DeliveryTime;
use App\Models\Order;
use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Delivery time slots and a patient's choice of one for their order.
 * Formerly ordersDelivery_times and ordersDelivery_timesHandler of LexaAdminApi.
 */
class DeliveryTimeController extends Controller
{
    use RespondsWithApiErrors;


    public function index(): JsonResponse
    {
        return response()->json(['delivery_times' => DeliveryTime::query()->get()]);
    }

    public function update(Request $request): JsonResponse
    {
        if (! $request->user()->hasAnyRole('admin', 'user')) {
            return $this->forbidden();
        }
        if (empty($request->input('order_id')) || empty($request->input('time_id'))) {
            return $this->forbidden('Fields is not be empty.');
        }

        $order = Order::query()->where('id', $request->input('order_id'))->first();
        if (empty($order) || $order->user_id != $request->user()->id) {
            return $this->notFound('Order not found');
        }
        Order::query()->where('id', $request->input('order_id'))->update(['delivery_time_id' => intval($request->input('time_id'))]);

        return response()->json(['message' => 'OK']);
    }
}
