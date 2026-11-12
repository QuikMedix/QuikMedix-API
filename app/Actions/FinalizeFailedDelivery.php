<?php

namespace App\Actions;

use App\Support\GeoPoint;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Closes a stop the driver could not deliver (patient unavailable or refused): sets the status,
 * charges the pharmacy tariff, pays the driver, and sends the packages back to the office or pharmacy.
 *
 * Extracted unchanged from the driver API's update_status so dispatch approval can run it for the
 * reporting driver instead of the signed-in user.
 */
class FinalizeFailedDelivery
{
    public function handle(int $orderId, int $statusId, int $driverId, ?int $driverPharmacyId): void
    {
        $order = DB::table('orders')->where('id', $orderId)->first();
        abort_if($order === null, 404, 'Order not found');

        DB::table('orders')->where('id', $orderId)->update(['statuse_id' => $statusId]);
        DB::table('orders')->where('id', $orderId)->update([
            'finish' => date('Y-m-d H:i:s'),
            'drop_off_photo' => null,
            'signature_photo' => null,
            'signature_type' => null,
            'tariff' => $this->tariff($order),
        ]);

        $routes = DB::table('routes_priority')->where('order_id', $orderId)->where('driver_id', $driverId)->where('type', 'patient')->get();
        if ($routes->isEmpty()) {
            return;
        }

        $routeNeed = $routes[0];
        foreach ($routes as $route) {
            DB::table('routes_priority_logs')->insert(['id' => $route->id, 'driver_id' => $route->driver_id, 'order_id' => $route->order_id, 'type' => $route->type, 'type_id' => $route->type_id, 'type_pay' => $route->type_pay, 'pay_value' => $route->pay_value]);
            if ((int) $route->type_pay === 1 || (int) $route->type_pay === 2) {
                DB::table('payouts_driver')->insert(['driver_id' => $driverId, 'order_id' => $orderId, 'amount' => $route->pay_value]);
            }
            if ((int) $route->type_pay === 3) {
                DB::table('payouts_driver')->insert(['driver_id' => $driverId, 'order_id' => $orderId, 'amount' => $route->pay_value * intval($order->count_bags)]);
            }
        }

        $this->addReturnStop($orderId, $driverId, $driverPharmacyId, $routeNeed);

        $driverLocation = DB::table('locations')->whereIn('id', [DB::raw('select max(`id`) from locations GROUP BY user_id')])->where('user_id', $driverId)->first();
        if (! empty($driverLocation)) {
            DB::table('packages_transitions')->insert(['order_id' => $orderId, 'user_id' => $routeNeed->type_id, 'driver_id' => $driverId, 'target' => 'faild', 'location' => $driverLocation->location]);
        }
        $failedAttempts = DB::table('packages_transitions')->where('order_id', $orderId)->where('user_id', $routeNeed->type_id)->where('target', 'faild')->count();
        if ($failedAttempts >= 3) {
            DB::table('orders')->where('id', $orderId)->update(['not_delivered' => 1]);
        }

        DB::table('routes_priority')->where('order_id', $orderId)->where('driver_id', $driverId)->where('type', 'patient')->delete();
    }

    /**
     * QuikMedix drivers return the packages to the office; pharmacy drivers return them to their pharmacy.
     */
    private function addReturnStop(int $orderId, int $driverId, ?int $driverPharmacyId, stdClass $routeNeed): void
    {
        [$type, $typeId] = empty($driverPharmacyId) ? ['office', null] : ['pharmacy', $driverPharmacyId];

        $nextStop = DB::table('routes_priority')->where('driver_id', $driverId)->where('type', $type)
            ->when($typeId !== null, fn ($query) => $query->where('type_id', $typeId))->first();
        if (! empty($nextStop)) {
            DB::table('routes_priority')->insert(['driver_id' => $driverId, 'order_id' => $orderId, 'type' => $type, 'type_id' => $nextStop->type_id, 'type_pay' => $nextStop->type_pay, 'pay_value' => $nextStop->pay_value, 'priority' => $nextStop->priority]);

            return;
        }

        $lastPriority = DB::table('routes_priority')->where('driver_id', $driverId)->max('priority');
        DB::table('routes_priority')->insert(['driver_id' => $driverId, 'order_id' => $orderId, 'type' => $type, 'type_id' => $typeId ?? 1, 'type_pay' => $routeNeed->type_pay, 'pay_value' => $routeNeed->pay_value, 'priority' => intval($lastPriority) + 1]);
    }

    private function tariff(stdClass $order): float
    {
        $pharmacy = DB::table('pharmacys')->where('id', $order->pharmacy_id)->first();
        $plan = DB::table('plans')->where('id', $pharmacy->plan_id)->first();
        $patient = DB::table('users')->where('id', $order->user_id)->first();

        $areaColumn = 'tariff_area_more';
        foreach ([1 => 'tariff', 2 => 'tariff_area2', 3 => 'tariff_area3'] as $type => $column) {
            $areaIds = DB::table('pharmacy_areas')->where('pharmacy_id', $order->pharmacy_id)->where('type', $type)->pluck('area_id')->toArray();
            $inArea = DB::table('area')->whereIn('area.id', $areaIds)->whereRaw('ST_CONTAINS(polygon, POINT(?, ?))', GeoPoint::bindings($patient->location))->select('area.id')->first();
            if (! empty($inArea)) {
                $areaColumn = $column;
                break;
            }
        }
        $tariff = floatval($this->rate($pharmacy, $plan, $areaColumn));

        if ((int) $order->type_driver !== 1) {
            return $tariff;
        }

        $timeColumn = match ((int) $order->delivery_time_id) {
            1 => 'tariff_next_day',
            2 => 'tariff_same_day',
            3 => 'tariff_asap',
            4 => 'tariff_after_hours',
            default => throw new \UnexpectedValueException("Unknown delivery time {$order->delivery_time_id} for order {$order->id}"),
        };
        $tariff += floatval($this->rate($pharmacy, $plan, $timeColumn)) + floatval($order->extra_charge_driver);
        if ((int) $order->fridge === 1) {
            $tariff += floatval($this->rate($pharmacy, $plan, 'tariff_fridge'));
        }

        return $tariff;
    }

    private function rate(stdClass $pharmacy, ?stdClass $plan, string $column): mixed
    {
        return is_numeric($pharmacy->{$column}) ? $pharmacy->{$column} : $plan?->{$column};
    }
}
