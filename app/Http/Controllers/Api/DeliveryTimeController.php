<?php

namespace App\Http\Controllers\Api;

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

    public function __construct()
    {
        $this->middleware(['auth:api', 'active.api']);
    }

    public function index(): JsonResponse
    {
        return response()->json(['delivery_times' => DB::table('delivery_times')->get()]);
    }

    public function update(Request $request): JsonResponse
    {
        if (! $request->user()->hasAnyRole('admin', 'user')) {
            return $this->forbidden();
        }
        if (empty($request->input('order_id')) || empty($request->input('time_id'))) {
            return $this->forbidden('Fields is not be empty.');
        }

        $order = DB::table('orders')->where('id', $request->input('order_id'))->first();
        if (empty($order) || $order->user_id != $request->user()->id) {
            return $this->notFound('Order not found');
        }
        DB::table('orders')->where('id', $request->input('order_id'))->update(['delivery_time_id' => intval($request->input('time_id'))]);

        return response()->json(['message' => 'OK']);
    }
}
