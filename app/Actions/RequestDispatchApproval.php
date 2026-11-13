<?php

namespace App\Actions;

use App\ChMessage;
use App\Notifications;
use App\Support\OrderStatus;
use App\User;
use Chatify\Facades\ChatifyMessenger as Chatify;
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
            abort_unless((int) $order->driver_id === (int) $driver->id, 403, 'This order is not assigned to you.');
            abort_if(in_array((int) $order->statuse_id, [4, 5, 8, 9, 10], true), 409, 'This order is already closed.');

            $pending = DB::table('order_exceptions')->where('order_id', $orderId)->where('status', 'pending')->first();
            if ($pending !== null && (int) $pending->driver_id === (int) $driver->id) {
                return ['exception_id' => (int) $pending->id, 'created' => false];
            }
            if ($pending !== null) {
                DB::table('order_exceptions')->where('id', $pending->id)->update([
                    'status' => 'superseded',
                    'review_note' => 'Order was reassigned before the current driver submitted a new report.',
                    'reviewed_at' => now(),
                    'updated_at' => now(),
                ]);
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

            DB::afterCommit(fn () => $this->forwardToDispatch($id));

            return ['exception_id' => $id, 'created' => true];
        });

        return $exception;
    }

    /**
     * Active dispatch staff receive reports and obtain pharmacy approval in live chat.
     *
     * @return list<int>
     */
    public static function reviewerIds(): array
    {
        return User::whereIn('role', [...User::ADMIN_ROLES, 'logist'])->where('isactive', 1)->where('isblocked', 0)
            ->orderBy('id')->pluck('id')->map(fn ($id): int => (int) $id)->all();
    }

    private function forwardToDispatch(int $exceptionId): void
    {
        try {
            $exception = DB::table('order_exceptions')->where('id', $exceptionId)->first();
            $order = DB::table('orders')->where('id', $exception->order_id)->first();
            $summary = $this->summary($exception, $order);
            $link = url(route('orders.show', [$order->pharmacy_id, $order->id], false));
            $reviewerIds = self::reviewerIds();

            foreach ($reviewerIds as $reviewerId) {
                $message = new ChMessage;
                $message->from_id = $exception->driver_id;
                $message->to_id = $reviewerId;
                $message->body = htmlentities($summary."\n".$link, ENT_QUOTES, 'UTF-8');
                $message->save();
                $this->broadcastMessage($message);
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

    private function broadcastMessage(ChMessage $message): void
    {
        try {
            $data = Chatify::parseMessage($message);
            Chatify::push('private-chatify.'.$message->to_id, 'messaging', [
                'from_id' => $message->from_id,
                'to_id' => $message->to_id,
                'message' => Chatify::messageCard($data, true),
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
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
            'Dispatch: obtain pharmacy approval through live chat before selecting Unavailable or Refused on the order page. Reply here to reach the driver:',
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
