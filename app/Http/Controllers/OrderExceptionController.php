<?php

namespace App\Http\Controllers;

use App\Actions\FinalizeFailedDelivery;
use App\Actions\RequestDispatchApproval;
use App\Notifications;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use stdClass;

/**
 * The order's pharmacy staff (or operations staff) approve or reject a driver's "patient unavailable" / "refused" report after checking with the pharmacy.
 */
class OrderExceptionController extends Controller
{
    /**
     * Order statuses after which a delivery report can no longer be applied.
     *
     * @var list<int>
     */
    private const array CLOSED_STATUSES = [4, 5, 8, 9, 10];

    public function __construct()
    {
        $this->middleware(['auth', '2fa', 'active']);
    }

    public function approve(int $pharmacy_id, int $order_id, int $exception_id, FinalizeFailedDelivery $finalizeFailedDelivery): RedirectResponse
    {
        $this->authorizeReview($pharmacy_id, $order_id);

        $outcome = DB::transaction(function () use ($order_id, $exception_id, $finalizeFailedDelivery): string {
            $exception = $this->lockPending($order_id, $exception_id);
            if ($exception === null) {
                return 'This report was already reviewed.';
            }

            $order = DB::table('orders')->where('id', $order_id)->lockForUpdate()->first();
            if (in_array((int) $order->statuse_id, self::CLOSED_STATUSES, true)) {
                $this->close($exception, 'superseded', 'Order was already closed when dispatch reviewed it.');

                return 'The order was already closed, so the report was not applied.';
            }

            $driverPharmacyId = DB::table('users')->where('id', $exception->driver_id)->value('pharmacy_id');
            $finalizeFailedDelivery->handle($order_id, (int) $exception->requested_status_id, (int) $exception->driver_id, $driverPharmacyId ? (int) $driverPharmacyId : null);
            $this->close($exception, 'approved', null);

            DB::afterCommit(fn () => $this->notifyDriver($exception, "Dispatch approved \"".RequestDispatchApproval::reasonLabel($exception->requested_status_id)."\" for order #{$order_id}. Return the packages as routed."));

            return 'approved';
        });

        return $this->backToOrder($pharmacy_id, $order_id, $outcome === 'approved' ? 'Report approved and the order was closed.' : null, $outcome !== 'approved' ? $outcome : null);
    }

    public function reject(Request $request, int $pharmacy_id, int $order_id, int $exception_id): RedirectResponse
    {
        $this->authorizeReview($pharmacy_id, $order_id);
        $validated = $request->validate([
            'review_note' => ['required', 'string', 'max:1000'],
        ], [
            'review_note.required' => 'Tell the driver what to do instead.',
        ]);

        $rejected = DB::transaction(function () use ($order_id, $exception_id, $validated): bool {
            $exception = $this->lockPending($order_id, $exception_id);
            if ($exception === null) {
                return false;
            }
            $this->close($exception, 'rejected', trim($validated['review_note']));
            DB::afterCommit(fn () => $this->notifyDriver($exception, "Dispatch did not approve \"".RequestDispatchApproval::reasonLabel($exception->requested_status_id)."\" for order #{$order_id}: ".trim($validated['review_note'])));

            return true;
        });

        return $this->backToOrder($pharmacy_id, $order_id, $rejected ? 'Report rejected; the driver was notified.' : null, $rejected ? null : 'This report was already reviewed.');
    }

    private function authorizeReview(int $pharmacyId, int $orderId): void
    {
        Gate::authorize('review-delivery-report', $pharmacyId);
        Gate::authorize('access-order', $orderId);
        abort_unless(DB::table('orders')->where('id', $orderId)->where('pharmacy_id', $pharmacyId)->exists(), 404);
    }

    private function lockPending(int $orderId, int $exceptionId): ?stdClass
    {
        return DB::table('order_exceptions')->where('id', $exceptionId)->where('order_id', $orderId)
            ->where('status', 'pending')->lockForUpdate()->first();
    }

    private function close(stdClass $exception, string $status, ?string $reviewNote): void
    {
        DB::table('order_exceptions')->where('id', $exception->id)->update([
            'status' => $status,
            'reviewed_by' => Auth::id(),
            'review_note' => $reviewNote,
            'reviewed_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function notifyDriver(stdClass $exception, string $body): void
    {
        try {
            Notifications::send_push($exception->driver_id, 'QuikMedix Dispatch', $body);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function backToOrder(int $pharmacyId, int $orderId, ?string $success, ?string $error): RedirectResponse
    {
        $redirect = redirect()->route('orders.show', [$pharmacyId, $orderId]);

        return $success !== null ? $redirect->with('success', $success) : $redirect->with('error', $error);
    }
}
