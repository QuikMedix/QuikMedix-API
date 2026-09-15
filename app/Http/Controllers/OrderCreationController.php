<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderCreationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
    }

    public function create(Request $request)
    {
        return $this->selectPharmacy($request, false);
    }

    public function createFacility(Request $request)
    {
        return $this->selectPharmacy($request, true);
    }

    private function selectPharmacy(Request $request, bool $facility)
    {
        $user = $request->user();
        abort_if($user->isblocked_or_isactive(), 403, LexaAdmin::$err_act_ban);
        abort_unless(in_array($user->role, ['medic', 'superadmin', 'admin', 'dispadmin'], true), 403, LexaAdmin::$err_perm);

        $formRoute = $facility ? 'orders.pharmacy.facility.create' : 'orders.pharmacy.create';
        $pharmacies = DB::table('pharmacys')->select('id', 'name', 'address');

        if ($user->role === 'medic') {
            // Pharmacy users always create orders for their own pharmacy.
            $pharmacy = $pharmacies->where('id', $user->pharmacy_id)->first();
            abort_unless($pharmacy, 404, 'Pharmacy not found.');

            return redirect()->route($formRoute, ['pharmacy_id' => $pharmacy->id]);
        }

        if (!empty($user->zone_id)) {
            $pharmacies->where('zone_id', $user->zone_id);
        }
        $pharmacies->where('isactive', 1)->where('isblocked', 0);

        if ($request->filled('pharmacy_id')) {
            $request->validate(['pharmacy_id' => 'required|integer|min:1']);
            $pharmacy = $pharmacies->where('id', $request->input('pharmacy_id'))->first();
            abort_unless($pharmacy, 404, 'Pharmacy not found.');

            return redirect()->route($formRoute, ['pharmacy_id' => $pharmacy->id]);
        }

        $title = $facility ? 'New Facility Order' : 'New Order';
        $view = view('orders.select-pharmacy', [
            'pharmacies' => $pharmacies->orderBy('name')->get(),
            'selectionRoute' => $facility ? 'orders.facility.create' : 'orders.create',
            'title' => $title,
            'br1' => 'Orders',
            'br2' => $title,
        ]);

        return $request->has('ajax') ? $view->renderSections() : $view;
    }
}
