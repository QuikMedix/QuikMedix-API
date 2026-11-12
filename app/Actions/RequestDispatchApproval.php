<?php

namespace App\Actions;

use App\ChMessage;
use App\Notifications;
use App\Support\OrderStatus;
use App\User;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Holds a driver's "patient unavailable" / "refused" report for dispatcher approval and forwards the
 * order, pharmacy and patient details to dispatch, so they can verify the issue with the pharmacy.
 */
class RequestDispatchApproval
{
    /**
     * @return array{exception_id: int, created: bool}
     */
    public function handle(int $orderId, int $statusId, User $driver, ?string $driverNote = null): array
    {
        $exception = DB::transaction(function () use ($orderId, $statusId, $driver, $driverNote): array {
            $order = DB::table('orders')->where('id', $orderId)->lockForUpdate()->first();
            abort_if($order === null, 404, 'Order not found');

            $pending = DB::table('order_exceptions')->where('order_id', $orderId)->where('status', 'pending')->first();
            if ($pending !== null) {
                return ['exception_id' => (int) $pending->id, 'created' => false];
            }

            $id = DB::table('order_exceptions')->insertGetId([
                'order_id' => $orderId,
                'driver_id' => $driver->id,
                'requested_status_id' => $statusId,
                'status' => 'pending',
                'driver_note' => $driverNote !== null && trim($driverNote) !== '' ? mb_substr(trim($driverNote), 0, 1000) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return ['exception_id' => $id, 'created' => true];
        });

        if ($exception['created']) {
            $this->forwardToDispatch($exception['exception_id']);
        }

        return $exception;
    }

    /**
     * The order's pharmacy staff, who verify and approve the report; the support superadmins when the pharmacy has no staff account.
     *
     * @return list<int>
     */
    public static function reviewerIds(int|string $pharmacyId): array
    {
        $ids = User::where('role', 'medic')->where('pharmacy_id', $pharmacyId)->orderBy('id')->pluck('id')->map(fn ($id): int => (int) $id)->all();

        return $ids !== [] ? $ids : User::supportContactIds();
    }

    private function forwardToDispatch(int $exceptionId): void
    {
        try {
            $exception = DB::table('order_exceptions')->where('id', $exceptionId)->first();
            $order = DB::table('orders')->where('id', $exception->order_id)->first();
            $summary = $this->summary($exception, $order);
            $link = url(route('orders.show', [$order->pharmacy_id, $order->id], false));
            $reviewerIds = self::reviewerIds($order->pharmacy_id);

            foreach ($reviewerIds as $reviewerId) {
                $message = new ChMessage;
                $message->from_id = $exception->driver_id;
                $message->to_id = $reviewerId;
                $message->body = htmlentities($summary."\n".$link, ENT_QUOTES, 'UTF-8');
                $message->save();
            }

            // Push services are third parties: the notification names the order, never the patient.
            Notifications::send_push_web(
                array_map('strval', $reviewerIds),
                'Delivery report needs approval',
                "Order #{$order->id}: driver reports ".self::reasonLabel($exception->requested_status_id).'. Review it on the order page.',
                $link,
                'dispatch_approval',
            );
        } catch (\Throwable $e) {
            // The request is already recorded and visible on the order page; a messaging outage must not fail the driver.
            report($e);
        }
    }

    private function summary(stdClass $exception, stdClass $order): string
    {
        $pharmacy = DB::table('pharmacys')->where('id', $order->pharmacy_id)->first();
        $patient = DB::table('users')->where('id', $order->user_id)->first();
        $driver = DB::table('users')->where('id', $exception->driver_id)->first();
        $suffix = match ((int) ($patient->primary_address ?? 1)) {
            2 => '2',
            3 => '3',
            default => '',
        };

        $lines = [
            'DELIVERY REPORT — APPROVAL NEEDED',
            "Order #{$order->id} — driver reports: ".self::reasonLabel($exception->requested_status_id),
            'Driver: '.$this->name($driver).($driver?->phone ? ', '.$driver->phone : ''),
            $exception->driver_note ? 'Driver note: '.$exception->driver_note : null,
            '',
            'Pharmacy: '.($pharmacy->name ?? 'Unknown'),
            $pharmacy?->address ? 'Pharmacy address: '.$pharmacy->address : null,
            $pharmacy?->phone ? 'Pharmacy phone: '.$pharmacy->phone : null,
            '',
            'Patient: '.$this->name($patient),
            $patient?->phone ? 'Patient phone: '.$patient->phone : null,
            ! empty($patient?->{'address'.$suffix}) ? 'Patient address: '.$patient->{'address'.$suffix}
                .(! empty($patient->{'apartment'.$suffix}) ? ', Apt '.$patient->{'apartment'.$suffix} : '')
                .(! empty($patient->{'zip'.$suffix}) ? ', '.$patient->{'zip'.$suffix} : '') : null,
            ! empty($order->special_instructions) ? 'Special instructions: '.$order->special_instructions : null,
            '',
            'Verify the issue (reply here to reach the driver), then approve or reject on the order page:',
        ];

        return implode("\n", array_filter($lines, fn (?string $line): bool => $line !== null));
    }

    public static function reasonLabel(int|string $statusId): string
    {
        return (int) $statusId === OrderStatus::REFUSED ? 'Delivery refused' : 'Patient unavailable';
    }

    private function name(?stdClass $user): string
    {
        return $user === null ? 'Unknown' : trim($user->name.' '.($user->last_name ?? ''));
    }
}
