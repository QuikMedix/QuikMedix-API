<?php

namespace App\Http\Controllers;

use App\Actions\ImportDispatchZones;
use App\Http\Controllers\Concerns\RendersPages;
use App\Models\DispatchZone;
use App\Support\DispatchZones;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * Dispatch zones in settings: the list with how many orders wait in each, loading zones from a
 * GeoJSON file, and each zone's route prefix, target stops, pay per stop and whether it is in use.
 */
class DispatchZoneController extends Controller
{
    use RendersPages;

    public function index(Request $request)
    {
        $waiting = DB::table('orders')->whereIn('statuse_id', DispatchZones::WAITING)->whereNull('driver_id')->whereNotNull('dispatch_zone_id')
            ->groupBy('dispatch_zone_id')->selectRaw('dispatch_zone_id, count(*) as orders')->pluck('orders', 'dispatch_zone_id');
        $zones = DispatchZone::query()->orderBy('state')->orderBy('name')
            ->get(['id', 'name', 'code', 'state', 'county', 'active', 'target_stops', 'pay_per_stop'])
            // Zones with orders waiting come first, then the rest by state and name.
            ->sortByDesc(fn (DispatchZone $zone): int => (int) ($waiting[$zone->id] ?? 0))->values();

        return $this->page($request, view('zones.list', ['zones' => $zones, 'waiting' => $waiting, 'title' => 'Dispatch Zones', 'br1' => 'Settings', 'br2' => 'Dispatch Zones']));
    }

    /**
     * Loads zones from an uploaded GeoJSON file, or the NY and NJ counties that come with the project.
     */
    public function import(Request $request, ImportDispatchZones $import): RedirectResponse
    {
        $request->validate(['file' => ['nullable', 'file', 'max:20480']]);
        $path = $request->file('file')?->getRealPath() ?? database_path('data/ny_nj_dispatch_zones.geojson');
        $geoJson = json_decode((string) file_get_contents($path), true);
        try {
            $result = $import->handle(is_array($geoJson) ? $geoJson : []);
        } catch (InvalidArgumentException $problem) {
            return redirect('/settings/dispatch-zones')->with('error', $problem->getMessage());
        }
        $restamped = DispatchZones::refreshWaitingOrders();
        $message = "Zones added: {$result['added']}, updated: {$result['updated']}. Waiting orders given a new zone: $restamped.";
        if ($result['skipped'] !== []) {
            $message .= ' Skipped: '.implode('; ', $result['skipped']).'.';
        }

        return redirect('/settings/dispatch-zones')->with('success', $message);
    }

    public function update(Request $request, int $zoneId): RedirectResponse
    {
        $zone = DispatchZone::query()->findOrFail($zoneId);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9]+$/', Rule::unique('dispatch_zones', 'code')->ignore($zone->id)],
            'target_stops' => ['required', 'integer', 'min:1', 'max:500'],
            'pay_per_stop' => ['required', 'numeric', 'min:0', 'max:1000'],
        ], ['code.regex' => 'A route prefix is letters and numbers only, such as BK.', 'code.unique' => 'Another zone already uses this route prefix.']);
        $wasActive = $zone->active;
        $zone->update(['code' => strtoupper($data['code']), 'target_stops' => $data['target_stops'], 'pay_per_stop' => $data['pay_per_stop'], 'active' => $request->boolean('active')]);
        if ($wasActive !== $zone->active) {
            // Orders move out of a zone that was switched off, and into one that was switched on.
            DispatchZones::refreshWaitingOrders();
        }

        return redirect('/settings/dispatch-zones')->with('success', "{$zone->name} was saved.");
    }
}
