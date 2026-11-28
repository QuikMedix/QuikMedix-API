<?php

namespace Tests\Feature\Console;

use App\Actions\ImportDispatchZones;
use App\Support\DispatchZones;
use Illuminate\Support\Facades\DB;
use Tests\Support\InsertsRows;
use Tests\TestCase;

/**
 * Dispatch zones are loaded from a GeoJSON file of outlines, and an address belongs to the zone
 * whose outline holds it.
 */
class ImportDispatchZonesTest extends TestCase
{
    use InsertsRows;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
    }

    /** A square zone around a point, as one GeoJSON feature. */
    private function square(string $name, float $lat, float $lng, float $half, array $properties = []): array
    {
        return ['type' => 'Feature', 'properties' => $properties + ['zone_name' => $name, 'state' => 'NY'], 'geometry' => ['type' => 'Polygon', 'coordinates' => [[
            [$lng - $half, $lat - $half], [$lng + $half, $lat - $half], [$lng + $half, $lat + $half], [$lng - $half, $lat + $half], [$lng - $half, $lat - $half],
        ]]]];
    }

    private function zoneNameAt(?string $location): ?string
    {
        return DB::table('dispatch_zones')->where('id', DispatchZones::zoneIdFor($location))->value('name');
    }

    public function test_the_ny_and_nj_counties_that_come_with_the_project_are_loaded(): void
    {
        $this->artisan('quikmedix:import-zones')->expectsOutputToContain('Zones added: 83, updated: 0.')->assertSuccessful();

        $this->assertSame(['NJ' => 21, 'NY' => 62], DB::table('dispatch_zones')->selectRaw('state, count(*) as zones')->groupBy('state')->orderBy('state')->pluck('zones', 'state')->all());
        $this->assertSame(83, DB::table('dispatch_zones')->distinct()->count('code'));
        $this->assertSame(
            ['Brooklyn' => 'BK', 'Manhattan' => 'MN', 'Queens' => 'QN', 'Staten Island' => 'SI', 'The Bronx' => 'BX'],
            DB::table('dispatch_zones')->whereIn('code', ['BK', 'QN', 'MN', 'BX', 'SI'])->orderBy('name')->pluck('code', 'name')->all(),
        );
        // Two counties share a name across the states; each is a zone of its own.
        $this->assertSame(['NJ', 'NY'], DB::table('dispatch_zones')->where('name', 'Essex County')->orderBy('state')->pluck('state')->all());
    }

    public function test_an_address_belongs_to_the_zone_whose_outline_holds_it(): void
    {
        $this->artisan('quikmedix:import-zones')->assertSuccessful();

        $this->assertSame([
            'Times Square' => 'Manhattan',
            'Brooklyn Borough Hall' => 'Brooklyn',
            'Flushing' => 'Queens',
            'Yankee Stadium' => 'The Bronx',
            'Staten Island Mall' => 'Staten Island',
            'Roosevelt Island, a separate part of Manhattan' => 'Manhattan',
            'Jersey City' => 'Hudson County',
            'Newark' => 'Essex County',
            'Hackensack' => 'Bergen County',
            'Mineola' => 'Nassau County',
            'White Plains' => 'Westchester County',
            'Albany' => 'Albany County',
            'Philadelphia, outside both states' => null,
            'The Atlantic, off Long Island' => null,
            'No address' => null,
            'Not a location' => null,
        ], [
            'Times Square' => $this->zoneNameAt('40.7580,-73.9855'),
            'Brooklyn Borough Hall' => $this->zoneNameAt('40.6928, -73.9903'),
            'Flushing' => $this->zoneNameAt('40.7675,-73.8331'),
            'Yankee Stadium' => $this->zoneNameAt('40.8296,-73.9262'),
            'Staten Island Mall' => $this->zoneNameAt('40.5823,-74.1657'),
            'Roosevelt Island, a separate part of Manhattan' => $this->zoneNameAt('40.7614,-73.9510'),
            'Jersey City' => $this->zoneNameAt('40.7178,-74.0431'),
            'Newark' => $this->zoneNameAt('40.7357,-74.1724'),
            'Hackensack' => $this->zoneNameAt('40.8859,-74.0435'),
            'Mineola' => $this->zoneNameAt('40.7490,-73.6407'),
            'White Plains' => $this->zoneNameAt('41.0340,-73.7629'),
            'Albany' => $this->zoneNameAt('42.6526,-73.7562'),
            'Philadelphia, outside both states' => $this->zoneNameAt('39.9526,-75.1652'),
            'The Atlantic, off Long Island' => $this->zoneNameAt('40.3000,-72.5000'),
            'No address' => $this->zoneNameAt(null),
            'Not a location' => $this->zoneNameAt('somewhere'),
        ]);
        // Essex County, New Jersey, not its namesake upstate.
        $this->assertSame('NJ', DB::table('dispatch_zones')->where('id', DispatchZones::zoneIdFor('40.7357,-74.1724'))->value('state'));
    }

    public function test_only_one_state_or_only_named_zones_can_be_loaded(): void
    {
        $this->artisan('quikmedix:import-zones', ['--state' => 'nj'])->expectsOutputToContain('Zones added: 21, updated: 0.')->assertSuccessful();
        $this->artisan('quikmedix:import-zones', ['--only' => ['Brooklyn', 'queens']])->expectsOutputToContain('Zones added: 2, updated: 0.')->assertSuccessful();

        $this->assertSame(23, DB::table('dispatch_zones')->count());
    }

    public function test_loading_a_file_again_updates_the_outlines_and_keeps_what_was_set_by_hand(): void
    {
        $import = app(ImportDispatchZones::class);
        $import->handle(['type' => 'FeatureCollection', 'features' => [$this->square('Midtown', 40.75, -73.98, 0.01)]]);
        DB::table('dispatch_zones')->update(['code' => 'MID', 'active' => 0, 'target_stops' => 40, 'pay_per_stop' => 4.25]);

        $result = $import->handle(['type' => 'FeatureCollection', 'features' => [$this->square('Midtown', 40.75, -73.98, 0.02)]]);

        $this->assertSame(['added' => 0, 'updated' => 1, 'skipped' => []], $result);
        $zone = DB::table('dispatch_zones')->sole();
        $this->assertSame(['MID', 0, 40, 4.25, 40.73, 40.77], [$zone->code, (int) $zone->active, (int) $zone->target_stops, (float) $zone->pay_per_stop, (float) $zone->min_lat, (float) $zone->max_lat]);
    }

    public function test_where_zones_overlap_the_smaller_one_takes_the_address(): void
    {
        app(ImportDispatchZones::class)->handle(['type' => 'FeatureCollection', 'features' => [
            $this->square('Brooklyn', 40.65, -73.95, 0.10),
            $this->square('Park Slope', 40.67, -73.98, 0.01),
        ]]);

        $this->assertSame('Park Slope', $this->zoneNameAt('40.6720,-73.9790'));
        $this->assertSame('Brooklyn', $this->zoneNameAt('40.6000,-73.9000'));

        // A zone that is switched off takes nothing.
        DB::table('dispatch_zones')->where('name', 'Park Slope')->update(['active' => 0]);
        $this->assertSame('Brooklyn', $this->zoneNameAt('40.6720,-73.9790'));
    }

    public function test_a_hole_in_an_outline_is_not_part_of_the_zone(): void
    {
        $ring = $this->square('Ring', 40.0, -74.0, 0.10);
        $ring['geometry']['coordinates'][] = $this->square('Hole', 40.0, -74.0, 0.02)['geometry']['coordinates'][0];
        app(ImportDispatchZones::class)->handle(['type' => 'FeatureCollection', 'features' => [$ring]]);

        $this->assertNull($this->zoneNameAt('40.0000,-74.0000'));
        $this->assertSame('Ring', $this->zoneNameAt('40.0500,-74.0000'));
    }

    public function test_features_that_cannot_be_a_zone_are_skipped_and_named(): void
    {
        $nameless = $this->square('', 40.0, -74.0, 0.1);
        $line = ['type' => 'Feature', 'properties' => ['zone_name' => 'A road', 'state' => 'NY'], 'geometry' => ['type' => 'LineString', 'coordinates' => [[-74, 40], [-73, 41]]]];
        $offTheMap = $this->square('Nowhere', 40.0, -74.0, 0.1);
        $offTheMap['geometry']['coordinates'][0][1] = [-274.0, 40.0];

        $result = app(ImportDispatchZones::class)->handle(['type' => 'FeatureCollection', 'features' => [$nameless, $line, $offTheMap, $this->square('Fine', 41.0, -74.0, 0.1)]]);

        $this->assertSame(1, $result['added']);
        $this->assertSame([
            'Feature 1: it needs a zone_name and a two-letter state',
            'A road: its outline is not a valid Polygon or MultiPolygon',
            'Nowhere: its outline is not a valid Polygon or MultiPolygon',
        ], $result['skipped']);
        $this->assertSame(['Fine'], DB::table('dispatch_zones')->pluck('name')->all());
    }

    public function test_a_file_that_is_not_geojson_loads_nothing(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'zones');
        file_put_contents($file, '{"type":"Topology"}');

        $this->artisan('quikmedix:import-zones', ['file' => $file])->expectsOutputToContain('no list of features')->assertFailed();
        $this->artisan('quikmedix:import-zones', ['file' => $file.'.missing'])->expectsOutputToContain('not a readable GeoJSON file')->assertFailed();

        $this->assertSame(0, DB::table('dispatch_zones')->count());
        unlink($file);
    }

    public function test_zones_made_from_names_that_start_alike_get_different_route_prefixes(): void
    {
        app(ImportDispatchZones::class)->handle(['type' => 'FeatureCollection', 'features' => [
            $this->square('Chemung County', 42.1, -76.8, 0.1),
            $this->square('Chenango County', 42.5, -75.6, 0.1),
            $this->square('Che', 43.0, -75.0, 0.1),
            $this->square('Che', 44.0, -75.0, 0.1, ['state' => 'NJ']),
        ]]);

        $this->assertSame(['CHE', 'CHN', 'CHE2', 'CHE3'], DB::table('dispatch_zones')->orderBy('id')->pluck('code')->all());
    }

    public function test_loading_zones_gives_the_orders_waiting_for_a_driver_their_zone(): void
    {
        $this->row('pharmacys', ['id' => 1, 'name' => 'Corner Pharmacy']);
        $this->row('statuses', ['id' => 1, 'name' => 'Ready for pickup']);
        $this->row('statuses', ['id' => 4, 'name' => 'Delivered']);
        $this->row('delivery_methods', ['id' => 1, 'name' => 'Standard']);
        $this->row('delivery_times', ['id' => 1, 'name' => 'Next day']);
        // The second address is the one deliveries go to.
        $this->row('users', ['id' => 14, 'email' => 'a@example.test', 'phone' => '1', 'role' => 'user', 'primary_address' => 2, 'location' => '42.6526,-73.7562', 'location2' => '40.6928,-73.9903']);
        $this->row('users', ['id' => 15, 'email' => 'b@example.test', 'phone' => '2', 'role' => 'user', 'location' => '40.7580,-73.9855']);
        $this->row('orders', ['id' => 30, 'pharmacy_id' => 1, 'user_id' => 14, 'statuse_id' => 1, 'delivery_method_id' => 1, 'delivery_time_id' => 1]);
        $this->row('orders', ['id' => 31, 'pharmacy_id' => 1, 'user_id' => 15, 'statuse_id' => 1, 'delivery_method_id' => 1, 'delivery_time_id' => 1]);
        // Delivered, and already with a driver: neither is waiting.
        $this->row('orders', ['id' => 32, 'pharmacy_id' => 1, 'user_id' => 15, 'statuse_id' => 4, 'delivery_method_id' => 1, 'delivery_time_id' => 1]);
        $this->row('orders', ['id' => 33, 'pharmacy_id' => 1, 'user_id' => 15, 'statuse_id' => 1, 'driver_id' => 20, 'delivery_method_id' => 1, 'delivery_time_id' => 1]);

        $this->artisan('quikmedix:import-zones', ['--state' => 'NY'])->expectsOutputToContain('Waiting orders given a new zone: 2.')->assertSuccessful();

        $zones = DB::table('orders')->leftJoin('dispatch_zones', 'dispatch_zones.id', '=', 'orders.dispatch_zone_id')->orderBy('orders.id')->pluck('dispatch_zones.name', 'orders.id')->all();
        $this->assertSame([30 => 'Brooklyn', 31 => 'Manhattan', 32 => null, 33 => null], $zones);
    }
}
