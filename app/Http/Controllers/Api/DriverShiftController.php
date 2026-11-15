<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * A driver's working shift in the mobile app: on/off duty, reported locations, the check-in QR code
 * and the payout balance. Formerly the driverStatus*, locationHandler, driverQr and get_payouts_driver
 * actions of LexaAdminApi.
 */
class DriverShiftController extends Controller
{
    use RespondsWithApiErrors;

    public function __construct()
    {
        $this->middleware(['auth:api', 'active.api']);
    }

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
        DB::table('locations')->insert(['user_id' => $request->user()->id, 'location' => $location]);

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
     * The driver's earned amount that has not been withdrawn yet.
     */
    public function payouts(Request $request): JsonResponse
    {
        if (! $request->user()->hasAnyRole('admin', 'driver')) {
            return $this->forbidden();
        }

        $amount = round(DB::table('payouts_driver')->where('driver_id', $request->user()->id)->where('withdraw', 0)->sum('amount'), 1);

        return response()->json(['amount' => $amount]);
    }

    private function setWorkingNow(Request $request, int $workingNow): JsonResponse
    {
        DB::table('users')->where('id', $request->user()->id)->update(['work_now' => $workingNow]);

        return response()->json(['message' => 'Driver Status was updated']);
    }
}
