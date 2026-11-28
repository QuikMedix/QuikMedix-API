<?php

namespace App\Actions;

use App\Models\Order;
use App\Models\RouteOffer;
use App\Models\RouteStop;
use App\Support\RouteOffers;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * A driver takes a route offer: the offer becomes theirs and its orders go onto their route,
 * the pharmacies first and then the patients, after any stops they already have. Only one
 * driver can take an offer, and a driver takes one at a time.
 */
class AcceptRouteOffer
{
    public const TAKEN = 'This route was just taken by another driver.';

    public const GONE = 'This route is no longer available.';

    public const BUSY = 'Finish the route you already took before taking another.';

    /**
     * @return array{accepted: bool, message: string, orders: int}
     */
    public function handle(int $offerId, int $driverId): array
    {
        $result = DB::transaction(function () use ($offerId, $driverId): array {
            $offer = RouteOffer::query()->find($offerId);
            if ($offer === null || $offer->status === RouteOffer::WITHDRAWN) {
                return ['accepted' => false, 'message' => self::GONE, 'orders' => 0];
            }
            if ($offer->status !== RouteOffer::OPEN) {
                return ['accepted' => false, 'message' => (int) $offer->driver_id === $driverId ? 'This route is already yours.' : self::TAKEN, 'orders' => 0];
            }
            if (self::holdsUnfinishedOffer($driverId)) {
                return ['accepted' => false, 'message' => self::BUSY, 'orders' => 0];
            }
            // The offer changes hands only if it is still open at this instant: of two drivers pressing at once, one wins.
            $claimed = RouteOffer::query()->where('id', $offerId)->where('status', RouteOffer::OPEN)
                ->update(['status' => RouteOffer::ACCEPTED, 'driver_id' => $driverId, 'accepted_at' => now()]);
            if ($claimed === 0) {
                return ['accepted' => false, 'message' => self::TAKEN, 'orders' => 0];
            }
            $orders = RouteOffers::freeOrders($offerId);
            if ($orders->isEmpty()) {
                RouteOffer::query()->where('id', $offerId)->update(['status' => RouteOffer::WITHDRAWN, 'driver_id' => null, 'accepted_at' => null]);

                return ['accepted' => false, 'message' => self::GONE, 'orders' => 0];
            }

            $plan = RouteOffers::plan($orders);
            $priority = (int) RouteStop::query()->where('driver_id', $driverId)->max('priority');
            $stops = [];
            foreach (['pharmacy' => [$plan['pharmacies'], 'pharmacy_id'], 'patient' => [$plan['patients'], 'user_id']] as $type => [$sequence, $column]) {
                foreach ($sequence as $placeId) {
                    $priority++;
                    foreach ($orders->where($column, $placeId) as $order) {
                        // Flat rate, with the amount still to come: dispatch pays the offer's amount when it closes the route.
                        $stops[] = ['driver_id' => $driverId, 'order_id' => $order->id, 'type' => $type, 'type_id' => $placeId, 'priority' => $priority, 'type_pay' => 1, 'pay_value' => -1];
                    }
                }
            }
            RouteStop::query()->insert($stops);
            Order::query()->whereIn('id', $orders->pluck('id'))->update(['driver_id' => $driverId, 'ready' => '2']);

            return ['accepted' => true, 'message' => "Route {$offer->code} is yours.", 'orders' => $orders->count()];
        });
        if ($result['accepted']) {
            try {
                app(RecalculateDriverEta::class)->handle($driverId, force: true);
            } catch (Throwable $problem) {
                // The route is the driver's either way; arrival times are worked out again on the next refresh.
                report($problem);
            }
        }

        return $result;
    }

    /**
     * Whether the driver took an offer, or this offer when one is named, whose orders are still on their route.
     */
    public static function holdsUnfinishedOffer(int $driverId, ?int $offerId = null): bool
    {
        return DB::table('route_offers')->where('route_offers.driver_id', $driverId)->where('route_offers.status', RouteOffer::ACCEPTED)
            ->when($offerId !== null, fn ($query) => $query->where('route_offers.id', $offerId))
            ->join('route_offer_orders', 'route_offer_orders.route_offer_id', '=', 'route_offers.id')
            ->join('routes_priority', fn ($join) => $join->on('routes_priority.order_id', '=', 'route_offer_orders.order_id')->where('routes_priority.driver_id', $driverId))
            ->exists();
    }
}
