<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Which dispatch zone a delivery belongs to: the zone whose outline holds the patient's address.
 * The outlines are checked here and not by the database, so it works the same on every database.
 */
class DispatchZones
{
    /** Order statuses in which an order has not been handed to a driver yet. */
    public const array WAITING = [OrderStatus::READY_FOR_PICKUP];

    /**
     * The zone that holds a stored "lat,lng", or null when there is none. Where zones overlap the
     * smallest wins, so a neighbourhood drawn inside a county takes the orders that fall in it.
     */
    public static function zoneIdFor(?string $location): ?int
    {
        [$lat, $lng] = GeoPoint::bindings($location);
        if ($lat === 0.0 && $lng === 0.0) {
            return null;
        }
        $zones = DB::table('dispatch_zones')->where('active', 1)
            ->where('min_lat', '<=', $lat)->where('max_lat', '>=', $lat)
            ->where('min_lng', '<=', $lng)->where('max_lng', '>=', $lng)
            ->orderBy('size')->orderBy('id')->get(['id', 'boundary']);
        foreach ($zones as $zone) {
            if (self::holds(json_decode($zone->boundary, true) ?: [], $lat, $lng)) {
                return (int) $zone->id;
            }
        }

        return null;
    }

    /**
     * Records the zone of an order from its patient's delivery address, and returns it.
     *
     * An order must never fail to be created because of its zone: any problem here, a missing
     * table included, is reported and the order is left without one.
     */
    public static function stampOrder(int|string $orderId): ?int
    {
        try {
            $order = DB::table('orders')->where('id', $orderId)->first(['id', 'user_id', 'dispatch_zone_id']);
            if ($order === null) {
                return null;
            }
            $patient = DB::table('users')->where('id', $order->user_id)->first(['primary_address', 'location', 'location2', 'location3']);
            $zoneId = $patient === null ? null : self::zoneIdFor(PatientAddress::primaryLocation($patient));
            if ($zoneId !== ($order->dispatch_zone_id === null ? null : (int) $order->dispatch_zone_id)) {
                DB::table('orders')->where('id', $order->id)->update(['dispatch_zone_id' => $zoneId]);
            }

            return $zoneId;
        } catch (Throwable $problem) {
            report($problem);

            return null;
        }
    }

    /**
     * Brings the zone of every order still waiting for a driver up to date, for orders made
     * before the zones were loaded and patients whose address changed since. Orders already in a
     * route offer keep the zone they were offered in.
     *
     * @return int how many orders changed zone
     */
    public static function refreshWaitingOrders(): int
    {
        $offered = DB::table('route_offer_orders')->join('route_offers', 'route_offers.id', '=', 'route_offer_orders.route_offer_id')
            ->where('route_offers.status', '!=', 'withdrawn')->pluck('route_offer_orders.order_id')->all();
        $orders = DB::table('orders')->join('users', 'users.id', '=', 'orders.user_id')
            ->whereIn('orders.statuse_id', self::WAITING)->whereNull('orders.driver_id')
            ->when($offered !== [], fn ($query) => $query->whereNotIn('orders.id', $offered))
            ->get(['orders.id', 'orders.dispatch_zone_id', 'users.primary_address', 'users.location', 'users.location2', 'users.location3']);
        $changed = 0;
        $known = [];
        foreach ($orders as $order) {
            $location = PatientAddress::primaryLocation($order);
            $zoneId = $known[$location ?? ''] ??= [self::zoneIdFor($location)];
            if ($zoneId[0] !== ($order->dispatch_zone_id === null ? null : (int) $order->dispatch_zone_id)) {
                DB::table('orders')->where('id', $order->id)->update(['dispatch_zone_id' => $zoneId[0]]);
                $changed++;
            }
        }

        return $changed;
    }

    /**
     * Whether the point lies inside the outline. Counting the edges crossed over every ring
     * handles outlines of several parts and parts with holes alike.
     *
     * @param  array<int, array<int, array<int, array{0: float, 1: float}>>>  $polygons  polygons of rings of [longitude, latitude]
     */
    public static function holds(array $polygons, float $lat, float $lng): bool
    {
        $inside = false;
        foreach ($polygons as $polygon) {
            foreach ($polygon as $ring) {
                $count = count($ring);
                for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
                    [$x1, $y1] = $ring[$i];
                    [$x2, $y2] = $ring[$j];
                    if (($y1 > $lat) !== ($y2 > $lat) && $lng < ($x2 - $x1) * ($lat - $y1) / ($y2 - $y1) + $x1) {
                        $inside = ! $inside;
                    }
                }
            }
        }

        return $inside;
    }

    /**
     * Miles in a straight line between two stored "lat,lng" locations.
     */
    public static function milesBetween(string $from, string $to): float
    {
        [$lat1, $lng1] = GeoPoint::bindings($from);
        [$lat2, $lng2] = GeoPoint::bindings($to);
        $a = sin(deg2rad($lat2 - $lat1) / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin(deg2rad($lng2 - $lng1) / 2) ** 2;

        return 3958.8 * 2 * asin(min(1.0, sqrt($a)));
    }
}
