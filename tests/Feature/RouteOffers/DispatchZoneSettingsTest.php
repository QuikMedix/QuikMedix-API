<?php

namespace Tests\Feature\RouteOffers;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\Support\InsertsRows;
use Tests\TestCase;

/**
 * Settings > Dispatch Zones: loading zones from a file, and each zone's route prefix, target
 * stops, pay per stop and whether it is in use.
 */
class DispatchZoneSettingsTest extends TestCase
{
    use InsertsRows;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('setex')->andReturn(true);
        $this->row('users', ['id' => 1, 'name' => 'Root', 'email' => 'root@example.test', 'phone' => '1', 'role' => 'superadmin', 'isactive' => 1, 'isblocked' => 0]);
        $this->row('pharmacys', ['id' => 1, 'name' => 'Corner Pharmacy']);
        $this->row('statuses', ['id' => 1, 'name' => 'Ready for pickup']);
        $this->row('delivery_methods', ['id' => 1]);
        $this->row('delivery_times', ['id' => 1]);
        // A patient at Brooklyn Borough Hall with an order waiting for a driver.
        $this->row('users', ['id' => 14, 'email' => 'p@example.test', 'phone' => '14', 'role' => 'user', 'location' => '40.6928,-73.9903']);
        $this->row('orders', ['id' => 30, 'pharmacy_id' => 1, 'user_id' => 14, 'statuse_id' => 1, 'delivery_method_id' => 1, 'delivery_time_id' => 1]);
        $this->actingAs(User::findOrFail(1))->withSession(['user_2fa' => 1]);
    }

    private function file(array $features): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('zones.geojson', json_encode(['type' => 'FeatureCollection', 'features' => $features]));
    }

    private function downtownBrooklyn(): array
    {
        return ['type' => 'Feature', 'properties' => ['zone_name' => 'Downtown Brooklyn', 'state' => 'NY'], 'geometry' => ['type' => 'Polygon', 'coordinates' => [[
            [-74.00, 40.68], [-73.98, 40.68], [-73.98, 40.70], [-74.00, 40.70], [-74.00, 40.68],
        ]]]];
    }

    public function test_the_page_starts_empty_and_says_how_to_load_zones(): void
    {
        $this->get('/settings/dispatch-zones')->assertOk()->assertSee('No zones yet. Press Load zones');
    }

    public function test_load_zones_without_a_file_loads_the_ny_and_nj_counties_and_places_waiting_orders(): void
    {
        $this->post('/settings/dispatch-zones/import')->assertRedirect('/settings/dispatch-zones')
            ->assertSessionHas('success', 'Zones added: 83, updated: 0. Waiting orders given a new zone: 1.');

        $brooklyn = DB::table('dispatch_zones')->where('code', 'BK')->value('id');
        $this->assertSame((int) $brooklyn, (int) DB::table('orders')->where('id', 30)->value('dispatch_zone_id'));
        // Brooklyn has the waiting order, so it is listed first, with a link to offer it.
        $this->get('/settings/dispatch-zones')->assertOk()->assertSeeInOrder(['Brooklyn', '(Kings County)', "/route-offers?zone=$brooklyn", 'Albany County'], false);
    }

    public function test_an_uploaded_file_adds_its_zones_and_a_smaller_zone_takes_over_its_orders(): void
    {
        $this->post('/settings/dispatch-zones/import');

        $this->post('/settings/dispatch-zones/import', ['file' => $this->file([$this->downtownBrooklyn()])])
            ->assertSessionHas('success', 'Zones added: 1, updated: 0. Waiting orders given a new zone: 1.');

        $this->assertSame('Downtown Brooklyn', DB::table('dispatch_zones')->where('id', DB::table('orders')->where('id', 30)->value('dispatch_zone_id'))->value('name'));
        $this->assertSame(84, DB::table('dispatch_zones')->count());
    }

    public function test_a_file_that_is_not_a_list_of_zones_changes_nothing_and_says_why(): void
    {
        $this->post('/settings/dispatch-zones/import', ['file' => UploadedFile::fake()->createWithContent('notes.json', '{"hello":"world"}')])
            ->assertRedirect('/settings/dispatch-zones')->assertSessionHas('error', 'This is not a GeoJSON file of zones: it has no list of features.');
        $this->post('/settings/dispatch-zones/import', ['file' => UploadedFile::fake()->createWithContent('photo.geojson', 'not json at all')])
            ->assertSessionHas('error');

        $this->assertSame(0, DB::table('dispatch_zones')->count());
    }

    public function test_features_that_were_skipped_are_named_in_the_message(): void
    {
        $nameless = $this->downtownBrooklyn();
        $nameless['properties'] = ['state' => 'NY'];

        $this->post('/settings/dispatch-zones/import', ['file' => $this->file([$nameless, $this->downtownBrooklyn()])])
            ->assertSessionHas('success', 'Zones added: 1, updated: 0. Waiting orders given a new zone: 1. Skipped: Feature 1: it needs a zone_name and a two-letter state.');
    }

    public function test_a_zones_prefix_target_and_pay_are_saved(): void
    {
        $this->post('/settings/dispatch-zones/import', ['file' => $this->file([$this->downtownBrooklyn()])]);
        $zone = DB::table('dispatch_zones')->value('id');

        $this->post("/settings/dispatch-zones/$zone", ['code' => 'dtb', 'target_stops' => 40, 'pay_per_stop' => '4.25', 'active' => 1])
            ->assertRedirect('/settings/dispatch-zones')->assertSessionHas('success', 'Downtown Brooklyn was saved.');

        $saved = DB::table('dispatch_zones')->first();
        $this->assertSame(['DTB', 40, 4.25, 1], [$saved->code, (int) $saved->target_stops, (float) $saved->pay_per_stop, (int) $saved->active]);
        $this->get('/settings/dispatch-zones')->assertOk()->assertSee('value="DTB"', false)->assertSee('value="4.25"', false);
    }

    public function test_switching_a_zone_off_moves_its_waiting_orders_out(): void
    {
        $this->post('/settings/dispatch-zones/import');
        $this->post('/settings/dispatch-zones/import', ['file' => $this->file([$this->downtownBrooklyn()])]);
        $downtown = DB::table('dispatch_zones')->where('name', 'Downtown Brooklyn')->first();

        // No "active" in the form: the box was unticked.
        $this->post("/settings/dispatch-zones/{$downtown->id}", ['code' => $downtown->code, 'target_stops' => 35, 'pay_per_stop' => '3.75'])->assertSessionHas('success');

        $this->assertSame('BK', DB::table('dispatch_zones')->where('id', DB::table('orders')->where('id', 30)->value('dispatch_zone_id'))->value('code'));
    }

    public function test_a_prefix_must_be_letters_and_numbers_and_belong_to_one_zone(): void
    {
        $this->post('/settings/dispatch-zones/import');
        $queens = DB::table('dispatch_zones')->where('code', 'QN')->value('id');

        $this->post("/settings/dispatch-zones/$queens", ['code' => 'BK', 'target_stops' => 35, 'pay_per_stop' => '3.75'])->assertSessionHasErrors(['code' => 'Another zone already uses this route prefix.']);
        $this->post("/settings/dispatch-zones/$queens", ['code' => 'Q-N', 'target_stops' => 35, 'pay_per_stop' => '3.75'])->assertSessionHasErrors(['code' => 'A route prefix is letters and numbers only, such as BK.']);
        $this->post("/settings/dispatch-zones/$queens", ['code' => 'QN', 'target_stops' => 0, 'pay_per_stop' => '-1'])->assertSessionHasErrors(['target_stops', 'pay_per_stop']);
        // Its own prefix is not "already used".
        $this->post("/settings/dispatch-zones/$queens", ['code' => 'qn', 'target_stops' => 35, 'pay_per_stop' => '3.75', 'active' => 1])->assertSessionHasNoErrors();

        $this->assertSame('QN', DB::table('dispatch_zones')->where('id', $queens)->value('code'));
        $this->post('/settings/dispatch-zones/999', ['code' => 'ZZ', 'target_stops' => 35, 'pay_per_stop' => '3.75'])->assertNotFound();
    }

    public function test_only_administrators_open_and_change_the_zones(): void
    {
        DB::table('users')->where('id', 1)->update(['role' => 'logist']);
        $this->app['auth']->forgetGuards();
        $this->actingAs(User::findOrFail(1))->withSession(['user_2fa' => 1]);

        $this->get('/settings/dispatch-zones')->assertForbidden();
        $this->post('/settings/dispatch-zones/import')->assertForbidden();
        $this->post('/settings/dispatch-zones/1', ['code' => 'ZZ', 'target_stops' => 35, 'pay_per_stop' => '3.75'])->assertForbidden();
        $this->assertSame(0, DB::table('dispatch_zones')->count());
    }
}
