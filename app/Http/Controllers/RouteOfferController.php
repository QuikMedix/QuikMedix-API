<?php

namespace App\Http\Controllers;

use App\Actions\PublishRouteOffer;
use App\Http\Controllers\Concerns\RendersPages;
use App\Models\DispatchZone;
use App\Models\RouteOffer;
use App\Support\DispatchZones;
use App\Support\RouteOffers;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Route offers for dispatch: the orders waiting for a driver grouped by zone, publishing a set
 * of them as a route that drivers can take in the app, and taking an open offer back.
 */
class RouteOfferController extends Controller
{
    use RendersPages;

    /** How many days of taken and withdrawn offers the page lists under the open ones. */
    private const int DAYS_SHOWN = 7;

    public function index(Request $request)
    {
        // Orders made before the zones were loaded, and patients who moved, are put in their zone first.
        DispatchZones::refreshWaitingOrders();

        $counts = $this->own(RouteOffers::waitingOrders())->groupBy('orders.dispatch_zone_id')->selectRaw('orders.dispatch_zone_id as zone_id, count(*) as orders')->pluck('orders', 'zone_id');
        $zones = DispatchZone::query()->whereIn('id', $counts->keys()->filter())->orderBy('name')->get(['id', 'name', 'code', 'state', 'target_stops', 'pay_per_stop'])
            ->sortByDesc(fn (DispatchZone $zone): int => (int) $counts[$zone->id])->values();
        $zone = $zones->firstWhere('id', (int) $request->query('zone'));
        $orders = collect();
        if ($zone !== null || $request->query('zone') === 'none') {
            $orders = $this->own(RouteOffers::waitingOrders())->join('users', 'users.id', '=', 'orders.user_id')->join('pharmacys', 'pharmacys.id', '=', 'orders.pharmacy_id')
                ->leftJoin('delivery_times', 'delivery_times.id', '=', 'orders.delivery_time_id')
                ->when($zone !== null, fn ($query) => $query->where('orders.dispatch_zone_id', $zone->id), fn ($query) => $query->whereNull('orders.dispatch_zone_id'))
                ->orderBy('orders.pharmacy_id')->orderBy('orders.id')
                ->get(['orders.id', 'orders.pharmacy_id', 'orders.user_id', 'orders.created', 'orders.delivery_date', 'orders.count_bags', 'orders.fridge', 'orders.ready',
                    'users.name', 'users.last_name', 'users.primary_address', 'users.address', 'users.address2', 'users.address3', 'users.zip', 'users.zip2', 'users.zip3',
                    'pharmacys.name as pharmacy_name', 'delivery_times.name as delivery_time'])
                ->each(function (object $order): void {
                    // The address deliveries go to: the one the patient marked as primary.
                    $suffix = in_array((int) $order->primary_address, [2, 3], true) ? (string) $order->primary_address : '';
                    $order->delivery_address = trim($order->{'address'.$suffix}.' '.$order->{'zip'.$suffix});
                });
        }
        $offers = DB::table('route_offers')->join('dispatch_zones', 'dispatch_zones.id', '=', 'route_offers.dispatch_zone_id')
            ->leftJoin('users', 'users.id', '=', 'route_offers.driver_id')
            ->where(fn ($query) => $query->where('route_offers.status', RouteOffer::OPEN)->orWhere('route_offers.updated_at', '>=', now()->subDays(self::DAYS_SHOWN)))
            ->orderByRaw("case when route_offers.status = 'open' then 0 else 1 end")->orderByDesc('route_offers.id')
            ->get(['route_offers.*', 'dispatch_zones.name as zone_name', 'users.name as driver_name', 'users.last_name as driver_last_name']);

        return $this->page($request, view('routes.offers', [
            'zones' => $zones, 'counts' => $counts, 'zone' => $zone, 'noZone' => $request->query('zone') === 'none', 'orders' => $orders, 'offers' => $offers,
            'title' => 'Route Offers', 'br1' => 'Routes', 'br2' => 'Route Offers',
        ]));
    }

    public function store(Request $request, PublishRouteOffer $publish): RedirectResponse
    {
        $data = $request->validate([
            'zone_id' => ['required', 'integer'],
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer'],
            'delivery_date' => ['nullable', 'date'],
            'pickup_time' => ['nullable', 'string', 'max:20'],
            'pay' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'note' => ['nullable', 'string', 'max:255'],
        ], ['order_ids.required' => 'Tick at least one order to put in the route.']);
        $zone = DispatchZone::query()->findOrFail($data['zone_id']);
        $orderIds = $this->own(RouteOffers::waitingOrders())->whereIn('orders.id', $data['order_ids'])->pluck('orders.id')->all();
        $offer = $publish->handle($zone, $orderIds, $data, Auth::id());
        if ($offer === null) {
            return redirect('/route-offers?zone='.$zone->id)->with('error', 'None of those orders can be offered any more: they were taken, cancelled or moved to another zone. Nothing was published.');
        }

        return redirect('/route-offers?zone='.$zone->id)->with('success', "Route {$offer->code} is now offered to drivers: {$offer->stops} ".($offer->stops === 1 ? 'stop' : 'stops').', pays $'.number_format((float) $offer->pay, 2).'.');
    }

    /**
     * A dispatcher with an admin zone works with the orders of that zone's pharmacies only, as on the other route pages.
     */
    private function own(Builder $orders): Builder
    {
        $adminZone = Auth::user()->zone_id;

        return empty($adminZone) ? $orders : $orders->whereIn('orders.pharmacy_id', DB::table('pharmacys')->where('zone_id', $adminZone)->select('id'));
    }

    /**
     * Takes back an offer no driver has taken; its orders wait for a driver again.
     */
    public function withdraw(int $offerId): RedirectResponse
    {
        $offer = RouteOffer::query()->findOrFail($offerId);
        $withdrawn = RouteOffer::query()->where('id', $offerId)->where('status', RouteOffer::OPEN)->update(['status' => RouteOffer::WITHDRAWN, 'updated_at' => now()]);

        return redirect('/route-offers')->with($withdrawn ? 'success' : 'error', $withdrawn
            ? "Route {$offer->code} was withdrawn. Its orders are waiting for a driver again."
            : "Route {$offer->code} was already taken by a driver, so it was not withdrawn. Change it on the driver's route page.");
    }
}
