<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * A patient's note on, and rating of, their own order. Formerly customer_notesAddHandler and
 * ratingHandler of LexaAdminApi. A missing order is still unhandled for patients, as it was there.
 */
class OrderFeedbackController extends Controller
{
    use RespondsWithApiErrors;

    private const DELIVERED = 4;

    public function __construct()
    {
        $this->middleware(['auth:api', 'active.api']);
    }

    public function storeNote(Request $request, string $order_id): JsonResponse
    {
        $order = DB::table('orders')->where('orders.id', $order_id)->first();
        if (! $this->isPatientOf($request, $order)) {
            return $this->forbidden();
        }
        if (Validator::make($request->all(), ['note' => 'required'])->fails()) {
            return $this->badRequest();
        }

        DB::table('notes')->insert([
            'order_id' => $order_id,
            'user_id' => $request->user()->id,
            'type' => '2',
            'note' => addslashes($request->input('note')),
        ]);

        return response()->json(['message' => 'OK']);
    }

    /**
     * A delivered order can be rated once; the rating is kept between 0 and 5.
     */
    public function storeRating(Request $request, string $order_id): JsonResponse
    {
        $order = DB::table('orders')->where('orders.id', $order_id)->first();
        if (! $this->isPatientOf($request, $order) || $order->statuse_id != self::DELIVERED || ! empty($order->rating)) {
            return $this->forbidden();
        }
        if (Validator::make($request->all(), ['rating' => 'required'])->fails()) {
            return $this->badRequest();
        }

        $rating = intval($request->input('rating'));
        if ($rating < 0) {
            $rating = 1;
        }
        DB::table('orders')->where('id', $order_id)->update(['rating' => min($rating, 5)]);

        return response()->json(['message' => 'OK']);
    }

    private function isPatientOf(Request $request, ?object $order): bool
    {
        return $request->user()->role == 'user' && $request->user()->id == $order->user_id;
    }
}
