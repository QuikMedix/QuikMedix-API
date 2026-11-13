<?php

namespace App\Http\Controllers;

use App\Actions\FinalizeFailedDelivery;
use App\Actions\RequestDispatchApproval;
use App\Notifications;
use App\Support\OrderStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use stdClass;

/**
 * Dispatch finalizes a driver's report after obtaining pharmacy approval through live chat.
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

    public function approve(Request $request, int $pharmacy_id, int $order_id, int $exception_id, FinalizeFailedDelivery $finalizeFailedDelivery): RedirectResponse
    {
        $this->authorizeReview($pharmacy_id, $order_id);
        $validated = $request->validate([
            'pharmacy_approval' => ['accepted'],
            'statuse' => ['required', 'integer', Rule::in(OrderStatus::NEEDS_DISPATCH_APPROVAL)],
        ], [
            'pharmacy_approval.accepted' => 'Obtain pharmacy approval in live chat and confirm it before closing this delivery.',
            'statuse.required' => 'Select Unavailable or Refused.',
            'statuse.integer' => 'Select Unavailable or Refused.',
            'statuse.in' => 'Select Unavailable or Refused.',
        ]);

        $outcome = DB::transaction(function () use ($order_id, $exception_id, $finalizeFailedDelivery, $validated): string {
            $order = DB::table('orders')->where('id', $order_id)->lockForUpdate()->first();
            $exception = $this->lockPending($order_id, $exception_id);
            if ($exception === null) {
                return 'This report was already reviewed.';
            }

            if (in_array((int) $order->statuse_id, self::CLOSED_STATUSES, true)) {
                $this->close($exception, 'superseded', 'Order was already closed when dispatch reviewed it.');

                return 'The order was already closed, so the report was not applied.';
            }

            if ((int) $order->driver_id !== (int) $exception->driver_id) {
                $this->close($exception, 'superseded', 'Order was reassigned before dispatch reviewed it.');

                return 'The order was reassigned. Ask the current driver to submit a new report.';
            }

            $driverPharmacyId = DB::table('users')->where('id', $exception->driver_id)->value('pharmacy_id');
            $statusId = (int) $validated['statuse'];
            $finalizeFailedDelivery->handle($order_id, $statusId, (int) $exception->driver_id, $driverPharmacyId ? (int) $driverPharmacyId : null);
            $this->close($exception, 'approved', 'Pharmacy approval obtained through live chat. Final status: '.RequestDispatchApproval::reasonLabel($statusId).'.');

            DB::afterCommit(fn () => $this->notifyDriver($exception, "Dispatch confirmed pharmacy approval and marked \"".RequestDispatchApproval::reasonLabel($statusId)."\" for order #{$order_id}. Return the packages as routed."));

            return 'approved';
        });

        return $this->backToOrder($pharmacy_id, $order_id, $outcome === 'approved' ? 'Status confirmed and the delivery stop was removed from the driver’s active route.' : null, $outcome !== 'approved' ? $outcome : null);
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
