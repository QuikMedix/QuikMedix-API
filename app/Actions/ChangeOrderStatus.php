<?php

namespace App\Actions;

use App\Notifications;
use App\Support\GeoPoint;
use Illuminate\Support\Facades\DB;
use stdClass;

class ChangeOrderStatus
{
    public function __construct(private SendOrderStatusToBestRx $sendOrderStatusToBestRx) {}

    public function handle(int $orderId, int $statusId): void
    {
        DB::transaction(function () use ($orderId, $statusId): void {
            $order = DB::table('orders')->where('id', $orderId)->lockForUpdate()->first();
            abort_if($order === null, 404, 'Order not found');

            if ((int) $order->statuse_id === $statusId) {
                return;
            }

            $changes = ['statuse_id' => $statusId];
            if (in_array($statusId, [4, 8, 9, 10], true)) {
                $changes += $this->completionDetails($order);
                DB::table('routes_priority')->where('order_id', $orderId)->delete();
            } elseif (in_array((int) $order->statuse_id, [4, 8, 9, 10], true)) {
                $changes['finish'] = null;
            }

            DB::table('orders')->where('id', $orderId)->update($changes);
            DB::afterCommit(function () use ($order, $orderId, $statusId): void {
                if (! empty($order->bestrx_order_id)) {
                    $this->sendOrderStatusToBestRx->handle($orderId);
                }
                if ($statusId === 3) {
                    Notifications::send_push($order->user_id, 'QuikMedix', "Your order #{$orderId} is on its way!");
                }
            });
        });
    }

    /**
     * Preserve the existing manual completion tariff and delivery address rules.
     *
     * @return array{finish: string, delivery_address: string, delivery_location: ?string, tariff: float}
     */
    private function completionDetails(stdClass $order): array
    {
        $pharmacy = DB::table('pharmacys')->where('id', $order->pharmacy_id)->first();
        $plan = DB::table('plans')->where('id', $pharmacy->plan_id)->first();
        $patient = DB::table('users')->where('id', $order->user_id)->first();
        $tariffColumn = 'tariff_area_more';

        foreach ([1 => 'tariff', 2 => 'tariff_area2', 3 => 'tariff_area3'] as $type => $column) {
            $areaIds = DB::table('pharmacy_areas')->where('pharmacy_id', $order->pharmacy_id)->where('type', $type)->pluck('area_id');
            if ($areaIds->isNotEmpty() && DB::table('area')->whereIn('id', $areaIds)
                ->whereRaw('ST_CONTAINS(polygon, POINT(?, ?))', GeoPoint::bindings($patient->location))->exists()) {
                $tariffColumn = $column;
                break;
            }
        }

        $tariff = $this->tariff($pharmacy, $plan, $tariffColumn);
        if ((int) $order->type_driver === 1) {
            $timeColumn = match ((int) $order->delivery_time_id) {
                1 => 'tariff_next_day',
                2 => 'tariff_same_day',
                3 => 'tariff_asap',
                4 => 'tariff_after_hours',
                default => throw new \UnexpectedValueException("Unknown delivery time {$order->delivery_time_id} for order {$order->id}"),
            };
            $tariff += $this->tariff($pharmacy, $plan, $timeColumn) + (float) $order->extra_charge_driver;
            if ((int) $order->fridge === 1) {
                $tariff += $this->tariff($pharmacy, $plan, 'tariff_fridge');
            }
        }

        $suffix = match ((int) $patient->primary_address) {
            2 => '2',
            3 => '3',
            default => '',
        };

        return [
            'finish' => now()->toDateTimeString(),
            'delivery_address' => $patient->{'address'.$suffix}.', '.$patient->{'zip'.$suffix}.', Apt '.$patient->{'apartment'.$suffix},
            'delivery_location' => $patient->{'location'.$suffix},
            'tariff' => $tariff,
        ];
    }

    private function tariff(stdClass $pharmacy, ?stdClass $plan, string $column): float
    {
        return (float) (is_numeric($pharmacy->{$column}) ? $pharmacy->{$column} : $plan?->{$column});
    }
}
