<?php

namespace App\Http\Controllers\Api;

use App\Models\Location;
use App\Models\DriverPayout;
use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * A driver's working shift in the mobile app: on/off duty, reported locations, the check-in QR code
 * and the driver's earnings. Formerly the driverStatus*, locationHandler, driverQr and get_payouts_driver
 * actions of LexaAdminApi.
 */
class DriverShiftController extends Controller
{
    use RespondsWithApiErrors;


    public function status(Request $request): JsonResponse
    {
        return response()->json(['work_now' => $request->user()->work_now]);
    }

    public function start(Request $request): JsonResponse
    {
        return $this->setWorkingNow($request, 1);
    }

    public function finish(Request $request): JsonResponse
    {
        return $this->setWorkingNow($request, 0);
    }

    public function storeLocation(Request $request): JsonResponse
    {
        if (! $request->user()->hasAnyRole('admin', 'driver')) {
            return $this->forbidden();
        }

        $location = $request->input('location');
        if (empty($location)) {
            return $this->badRequest();
        }
        Location::query()->insert(['user_id' => $request->user()->id, 'location' => $location]);

        return response()->json(['message' => 'Location was added']);
    }

    /**
     * Issues a one-time token that staff scan to identify the driver at the hub.
     */
    public function qr(Request $request): JsonResponse
    {
        if (! $request->user()->hasAnyRole('driver')) {
            return $this->forbidden();
        }

        $token = bin2hex(random_bytes(24));
        DB::table('tokens_driver')->insert(['user_id' => $request->user()->id, 'token' => $token]);

        return response()->json(['qr-code' => "https://quickchart.io/qr?text=$token"]);
    }

    /**
     * What the driver has earned and not yet been paid, and how many completed stops are still
     * waiting for dispatch to close the route and set what it pays. Those stops are counted, not
     * added in: they carry a marker amount, not money.
     */
    public function payouts(Request $request): JsonResponse
    {
        if (! $request->user()->hasAnyRole('admin', 'driver')) {
            return $this->forbidden();
        }
        $unpaid = DriverPayout::query()->where('driver_id', $request->user()->id)->where('withdraw', 0);

        return response()->json([
            'amount' => round((float) (clone $unpaid)->settled()->sum('amount'), 2),
            'pending' => (clone $unpaid)->awaitingRate()->count(),
        ]);
    }

    private function setWorkingNow(Request $request, int $workingNow): JsonResponse
    {
        DB::table('users')->where('id', $request->user()->id)->update(['work_now' => $workingNow]);

        return response()->json(['message' => 'Driver Status was updated']);
    }

    /** How many weeks of history the app is given. */
    private const HISTORY_WEEKS = 12;

    /**
     * What the driver has earned: totals for all time, this week, and the latest weeks in which
     * they completed stops. A week runs Monday to Sunday in the application's time zone. Stops
     * still waiting for dispatch to set their pay are counted, not added in.
     */
    public function earnings(Request $request): JsonResponse
    {
        if (! $request->user()->hasAnyRole('admin', 'driver')) {
            return $this->forbidden();
        }
        $payouts = DriverPayout::query()->where('driver_id', $request->user()->id);
        $money = fn ($query): float => round((float) $query->sum('amount'), 2);
        $zone = config('app.timezone');
        $thisWeek = CarbonImmutable::now($zone)->startOfWeek(CarbonImmutable::MONDAY);

        // The table's timestamps are the database's, in UTC. A year of rows is enough to find the latest weeks.
        $weeks = [];
        $rows = (clone $payouts)->where('created', '>=', $thisWeek->subWeeks(52)->utc()->toDateTimeString())->orderByDesc('created')->get(['amount', 'withdraw', 'created']);
        foreach ($rows as $row) {
            $from = CarbonImmutable::parse($row->created, 'UTC')->setTimezone($zone)->startOfWeek(CarbonImmutable::MONDAY)->toDateString();
            $weeks[$from] ??= ['from' => $from, 'to' => CarbonImmutable::parse($from, $zone)->addDays(6)->toDateString(), 'stops' => 0, 'earned' => 0, 'paid' => 0, 'unpaid' => 0, 'pending' => 0];
            $weeks[$from]['stops']++;
            if ((float) $row->amount === (float) DriverPayout::AWAITING_RATE) {
                $weeks[$from]['pending']++;
            } else {
                // Whole cents while adding, so a week of small shares does not drift.
                $cents = (int) round($row->amount * 100);
                $weeks[$from]['earned'] += $cents;
                $weeks[$from][$row->withdraw ? 'paid' : 'unpaid'] += $cents;
            }
        }
        $weeks = array_map(fn (array $week): array => ['earned' => $week['earned'] / 100, 'paid' => $week['paid'] / 100, 'unpaid' => $week['unpaid'] / 100] + $week, array_slice(array_values($weeks), 0, self::HISTORY_WEEKS));

        return response()->json([
            'earned' => $money((clone $payouts)->settled()),
            'paid' => $money((clone $payouts)->settled()->where('withdraw', 1)),
            'unpaid' => $money((clone $payouts)->settled()->where('withdraw', 0)),
            'pending' => (clone $payouts)->awaitingRate()->count(),
            'this_week' => $weeks !== [] && $weeks[0]['from'] === $thisWeek->toDateString() ? $weeks[0]['earned'] : 0,
            'weeks' => $weeks,
        ]);
    }
}
