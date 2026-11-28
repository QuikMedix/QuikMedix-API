<?php

namespace App\Support;

use App\Models\RouteOffer;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * What route offers are made of: the orders that can still go into one, the orders of an offer
 * that are still free to hand over, and the order of its stops with the miles between them.
 */
class RouteOffers
{
    /** Roads are longer than straight lines; this turns one into roughly the other. */
    private const float ROAD_FACTOR = 1.3;

    /**
     * Orders waiting for a QuikMedix driver: ready for pickup, with no driver, and not the
     * pharmacy's own delivery. Leaves out orders already in an offer unless asked not to.
     */
    public static function waitingOrders(bool $notOffered = true): Builder
    {
        return DB::table('orders')->whereIn('orders.statuse_id', DispatchZones::WAITING)->whereNull('orders.driver_id')->where('orders.type_driver', 1)
            ->when($notOffered, fn (Builder $query) => $query->whereNotIn('orders.id', DB::table('route_offer_orders')
                ->join('route_offers', 'route_offers.id', '=', 'route_offer_orders.route_offer_id')
                ->where('route_offers.status', '!=', RouteOffer::WITHDRAWN)->select('route_offer_orders.order_id')));
    }

    /**
     * The orders of an offer that can still be handed to a driver, with where they are picked up
     * and delivered. An order that was cancelled or given to a driver since is left out.
     *
     * @return Collection<int, object>
     */
    public static function freeOrders(int $offerId): Collection
    {
        return self::waitingOrders(notOffered: false)
            ->join('route_offer_orders', 'route_offer_orders.order_id', '=', 'orders.id')->where('route_offer_orders.route_offer_id', $offerId)
            ->join('users', 'users.id', '=', 'orders.user_id')->join('pharmacys', 'pharmacys.id', '=', 'orders.pharmacy_id')
            ->orderBy('orders.id')
            ->get(['orders.id', 'orders.user_id', 'orders.pharmacy_id', 'users.primary_address', 'users.location', 'users.location2', 'users.location3',
                'pharmacys.name as pharmacy_name', 'pharmacys.address as pharmacy_address', 'pharmacys.location as pharmacy_location']);
    }

    /**
     * The order to make the stops in, and about how many miles they are by road: the pharmacies
     * first, the busiest one to start, then each time the nearest patient not yet visited.
     *
     * @param  Collection<int, object>  $orders  rows as freeOrders() returns them
     * @return array{pharmacies: list<int>, patients: list<int>, miles: float|null}
     */
    public static function plan(Collection $orders): array
    {
        $pharmacies = $orders->groupBy('pharmacy_id')->sortByDesc(fn (Collection $group): int => $group->count())
            ->map(fn (Collection $group): ?string => $group->first()->pharmacy_location ?: null)->all();
        $patients = $orders->keyBy('user_id')->map(fn (object $order): ?string => PatientAddress::primaryLocation($order))->all();

        $miles = 0.0;
        $measured = 0;
        $at = null;
        $visit = function (array $places, bool $nearestFirst) use (&$miles, &$measured, &$at): array {
            $sequence = [];
            while ($places !== []) {
                $next = array_key_first($places);
                if ($nearestFirst && $at !== null) {
                    $nearest = null;
                    foreach ($places as $id => $location) {
                        $distance = $location === null ? INF : DispatchZones::milesBetween($at, $location);
                        if ($nearest === null || $distance < $nearest) {
                            [$nearest, $next] = [$distance, $id];
                        }
                    }
                }
                $location = $places[$next];
                if ($location !== null) {
                    if ($at !== null) {
                        $miles += DispatchZones::milesBetween($at, $location);
                        $measured++;
                    }
                    $at = $location;
                }
                $sequence[] = (int) $next;
                unset($places[$next]);
            }

            return $sequence;
        };

        return [
            'pharmacies' => $visit($pharmacies, nearestFirst: false),
            'patients' => $visit($patients, nearestFirst: true),
            'miles' => $measured === 0 ? null : round($miles * self::ROAD_FACTOR, 1),
        ];
    }

    /**
     * The offer a driver took that dispatch has not paid yet: its orders are still on the
     * driver's route, or delivered and waiting for the route to be closed. Null when there is
     * none, and when the offers cannot be read at all, so the route page opens regardless.
     */
    public static function awaitingPay(int|string $driverId): ?object
    {
        try {
            return DB::table('route_offers')->where('route_offers.driver_id', $driverId)->where('route_offers.status', RouteOffer::ACCEPTED)
                ->where(function (Builder $query) use ($driverId): void {
                    $query->whereExists(fn (Builder $stops) => $stops->from('routes_priority')->join('route_offer_orders', 'route_offer_orders.order_id', '=', 'routes_priority.order_id')
                        ->whereColumn('route_offer_orders.route_offer_id', 'route_offers.id')->where('routes_priority.driver_id', $driverId))
                        ->orWhereExists(fn (Builder $payouts) => $payouts->from('payouts_driver')->join('route_offer_orders', 'route_offer_orders.order_id', '=', 'payouts_driver.order_id')
                            ->whereColumn('route_offer_orders.route_offer_id', 'route_offers.id')->where('payouts_driver.driver_id', $driverId)->where('payouts_driver.amount', -1));
                })->orderByDesc('route_offers.accepted_at')->first(['route_offers.id', 'route_offers.code', 'route_offers.stops', 'route_offers.pay', 'route_offers.accepted_at']);
        } catch (\Throwable $problem) {
            report($problem);

            return null;
        }
    }

    /**
     * An offer as the driver app is told about it. Patients' names and addresses are not part
     * of it: a driver sees those once the route is theirs.
     *
     * @param  Collection<int, object>  $orders  the offer's orders, as freeOrders() returns them
     * @return array<string, mixed>
     */
    public static function forDriver(object $offer, Collection $orders): array
    {
        return [
            'id' => (int) $offer->id,
            'code' => $offer->code,
            'status' => $offer->status,
            'zone' => $offer->zone_name,
            'state' => $offer->zone_state,
            'delivery_date' => $offer->delivery_date === null ? null : substr((string) $offer->delivery_date, 0, 10),
            'pickup_time' => $offer->pickup_time,
            'stops' => (int) $offer->stops,
            'orders' => $orders->count(),
            'miles' => $offer->miles === null ? null : (float) $offer->miles,
            'pay' => (float) $offer->pay,
            'pay_text' => '$'.number_format((float) $offer->pay, 2),
            'note' => $offer->note,
            'pickups' => $orders->unique('pharmacy_id')->map(fn (object $order): array => [
                'id' => (int) $order->pharmacy_id, 'name' => $order->pharmacy_name, 'address' => $order->pharmacy_address, 'location' => $order->pharmacy_location,
            ])->values()->all(),
        ];
    }
}
