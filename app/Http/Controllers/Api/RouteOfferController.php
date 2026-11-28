<?php

namespace App\Http\Controllers\Api;

use App\Actions\AcceptRouteOffer;
use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Controller;
use App\Models\RouteOffer;
use App\Support\RouteOffers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Route offers in the driver app: the routes a driver can take, the one they took, and taking one.
 */
class RouteOfferController extends Controller
{
    use RespondsWithApiErrors;

    /**
     * The open offers, soonest delivery day first, and the offer this driver took that is still
     * on their route. An open offer whose orders have all gone elsewhere is not listed.
     */
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->hasAnyRole('driver')) {
            return $this->forbidden();
        }
        $offers = DB::table('route_offers')->join('dispatch_zones', 'dispatch_zones.id', '=', 'route_offers.dispatch_zone_id')
            ->where(fn ($query) => $query->where('route_offers.status', RouteOffer::OPEN)
                ->orWhere(fn ($mine) => $mine->where('route_offers.status', RouteOffer::ACCEPTED)->where('route_offers.driver_id', $request->user()->id)))
            ->orderBy('route_offers.delivery_date')->orderBy('route_offers.id')
            ->get(['route_offers.*', 'dispatch_zones.name as zone_name', 'dispatch_zones.state as zone_state']);
        $open = [];
        $mine = null;
        foreach ($offers as $offer) {
            if ($offer->status === RouteOffer::OPEN) {
                $orders = RouteOffers::freeOrders($offer->id);
                if ($orders->isNotEmpty()) {
                    $open[] = RouteOffers::forDriver($offer, $orders);
                }
            } elseif (AcceptRouteOffer::holdsUnfinishedOffer($request->user()->id, $offer->id)) {
                $mine = ['id' => (int) $offer->id, 'code' => $offer->code, 'zone' => $offer->zone_name, 'stops' => (int) $offer->stops, 'pay' => (float) $offer->pay, 'pay_text' => '$'.number_format((float) $offer->pay, 2)];
            }
        }

        return response()->json(['offers' => $open, 'mine' => $mine, 'can_accept' => $mine === null]);
    }

    public function accept(Request $request, int $offerId, AcceptRouteOffer $accept): JsonResponse
    {
        if (! $request->user()->hasAnyRole('driver')) {
            return $this->forbidden();
        }
        $result = $accept->handle($offerId, (int) $request->user()->id);
        if (! $result['accepted']) {
            return response()->json(['message' => $result['message'], 'errors' => 'Conflict'], 409);
        }

        return response()->json(['message' => $result['message'], 'orders' => $result['orders']]);
    }
}
