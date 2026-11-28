<?php

namespace App\Actions;

use App\Models\DispatchZone;
use App\Models\RouteOffer;
use App\Support\Notifications;
use App\Support\RouteOffers;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Dispatch offers a set of waiting orders in one zone to drivers as a route. The offer is named
 * from the zone's prefix, the delivery day and its number that day, such as BK-1127-01, and pays
 * the zone's rate for each stop unless another amount is given.
 */
class PublishRouteOffer
{
    /**
     * @param  list<int|string>  $orderIds  the orders chosen; those no longer waiting, in another offer or in another zone are left out
     * @param  array{delivery_date?: ?string, pickup_time?: ?string, pay?: float|string|null, note?: ?string}  $details
     * @return RouteOffer|null the offer, or null when none of the orders can be offered
     */
    public function handle(DispatchZone $zone, array $orderIds, array $details = [], ?int $createdBy = null): ?RouteOffer
    {
        $offer = DB::transaction(function () use ($zone, $orderIds, $details, $createdBy): ?RouteOffer {
            $ids = RouteOffers::waitingOrders()->where('orders.dispatch_zone_id', $zone->id)->whereIn('orders.id', $orderIds)->lockForUpdate()->pluck('orders.id');
            if ($ids->isEmpty()) {
                return null;
            }
            $day = empty($details['delivery_date']) ? today() : Carbon::parse($details['delivery_date']);
            $prefix = $zone->code.'-'.$day->format('md').'-';
            $number = RouteOffer::query()->where('code', 'like', $prefix.'%')->count();
            do {
                $code = $prefix.str_pad((string) ++$number, 2, '0', STR_PAD_LEFT);
            } while (RouteOffer::query()->where('code', $code)->exists());

            $offer = RouteOffer::query()->create([
                'code' => $code, 'dispatch_zone_id' => $zone->id, 'delivery_date' => $day->toDateString(),
                'pickup_time' => $details['pickup_time'] ?? null, 'note' => $details['note'] ?? null,
                'stops' => 0, 'pay' => 0, 'status' => RouteOffer::OPEN, 'created_by' => $createdBy,
            ]);
            DB::table('route_offer_orders')->insert($ids->map(fn ($id): array => ['route_offer_id' => $offer->id, 'order_id' => $id])->all());

            $orders = RouteOffers::freeOrders($offer->id);
            // Two orders for one patient are one stop.
            $stops = $orders->unique('user_id')->count();
            $pay = isset($details['pay']) && $details['pay'] !== '' ? (float) $details['pay'] : round($stops * (float) $zone->pay_per_stop, 2);
            $offer->update(['stops' => $stops, 'miles' => RouteOffers::plan($orders)['miles'], 'pay' => $pay]);

            return $offer;
        });
        if ($offer !== null) {
            $miles = $offer->miles === null ? '' : ', about '.round((float) $offer->miles).' miles';
            $body = "{$offer->stops} ".($offer->stops === 1 ? 'stop' : 'stops')."$miles, pays $".number_format((float) $offer->pay, 2).'. Open Available Routes to take it.';
            foreach (DB::table('users')->where('role', 'driver')->where('isactive', 1)->where('isblocked', 0)->whereNotNull('device_token')->get() as $driver) {
                Notifications::pushToApp($driver, "New route in {$zone->name}", $body);
            }
        }

        return $offer;
    }
}
