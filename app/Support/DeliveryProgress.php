<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * What a patient is told while an order is on its way: the minutes until the driver arrives, how
 * many stops the driver has before theirs, and the note they left for the driver.
 *
 * The minutes are `orders.eta`, which RecalculateDriverEta works out by routing the driver's
 * remaining stops in order and CountDownEtas counts down every minute.
 */
class DeliveryProgress
{
    /** The driver's live position is shared with the patient once this few stops are left before theirs. */
    public const int LOCATION_WITHIN_STOPS = 5;

    /** Delivered, cancelled, patient unavailable, refused, returned to the pharmacy. */
    private const array NOT_COMING = [OrderStatus::DELIVERED, 5, OrderStatus::PATIENT_UNAVAILABLE, OrderStatus::REFUSED, 10];

    /**
     * Minutes until the driver reaches this order, or null when no arrival is expected:
     * the order is finished, or it is not on a driver's route yet.
     */
    public static function etaMinutes(int|string|null $statusId, int|string|null $eta): ?int
    {
        if (in_array((int) $statusId, self::NOT_COMING, true) || (int) $eta <= 0) {
            return null;
        }

        return (int) $eta;
    }

    /**
     * The same time as text, for the apps to show as it is: "25 min", "1 h 5 min", or "Processing"
     * while there is none. This field has always been a string.
     */
    public static function etaText(?int $minutes): string
    {
        if ($minutes === null) {
            return 'Processing';
        }
        if ($minutes < 60) {
            return $minutes.' min';
        }

        return intdiv($minutes, 60).' h'.($minutes % 60 > 0 ? ' '.($minutes % 60).' min' : '');
    }

    /**
     * How many stops the driver makes before this order's, or null when the order is not on a
     * driver's route. Stops are counted the way the driver's route lists them: orders for one
     * pharmacy or one patient are a single stop, every office visit is its own.
     */
    public static function stopsAhead(object $order): ?int
    {
        if (empty($order->driver_id) || in_array((int) $order->statuse_id, self::NOT_COMING, true)) {
            return null;
        }
        $rows = DB::table('routes_priority')->where('driver_id', $order->driver_id)->get(['order_id', 'type', 'type_id', 'priority']);
        $own = $rows->first(fn ($row) => $row->type === 'patient' && (int) $row->order_id === (int) $order->id);
        if ($own === null) {
            return null;
        }

        $stops = [];
        foreach ($rows as $row) {
            $key = $row->type === 'office' ? 'office:'.$row->type_id.':'.$row->priority : $row->type.':'.$row->type_id;
            $stops[$key] = min($stops[$key] ?? PHP_INT_MAX, (int) $row->priority);
        }
        $ownPriority = $stops['patient:'.$own->type_id];

        return count(array_filter($stops, fn (int $priority) => $priority < $ownPriority));
    }

    /**
     * Whether the patient may see where the driver is: the order is on its way and the driver is
     * within LOCATION_WITHIN_STOPS stops of them.
     */
    public static function sharesDriverLocation(int|string|null $statusId, ?int $stopsAhead): bool
    {
        return (int) $statusId === OrderStatus::ON_THE_WAY && $stopsAhead !== null && $stopsAhead <= self::LOCATION_WITHIN_STOPS;
    }

    /**
     * The latest note the patient left for the driver on this order, such as where to leave it.
     */
    public static function patientNote(int|string $orderId): ?string
    {
        $note = DB::table('notes')->where('order_id', $orderId)->where('type', '2')->orderByDesc('id')->value('note');

        // Notes are stored with their quotes escaped.
        return $note === null || trim($note) === '' ? null : stripslashes($note);
    }
}
