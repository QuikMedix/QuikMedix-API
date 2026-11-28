<?php

namespace Tests\Feature\RouteOffers;

use App\Actions\ImportDispatchZones;
use App\Actions\RepeatOrder;
use App\Models\User;
use App\Support\DispatchZones;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Tests\Support\FakesSms;
use Tests\Support\InsertsRows;
use Tests\TestCase;

/**
 * Every new order is given the dispatch zone of its patient's address, however it is made, and
 * an order is never lost because its zone could not be worked out.
 */
class OrderZoneTest extends TestCase
{
    use FakesSms;
    use InsertsRows;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        config(['services.fcm.credentials' => null, 'services.beams.instance_id' => null, 'app.googlemaps_apikey' => 'test-key']);
        // Every address looked up is 1 Wall St, in the Downtown zone below.
        Http::fake(['maps.googleapis.com/*' => fn () => Http::response(['status' => 'OK', 'results' => [[
            'formatted_address' => '1 Wall St, New York, NY 10005, USA',
            'geometry' => ['location' => ['lat' => 40.7069, 'lng' => -74.0113]],
        ]]])]);
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('setex')->andReturn(true);
        $this->fakeSms();
        app(ImportDispatchZones::class)->handle(['type' => 'FeatureCollection', 'features' => [
            ['type' => 'Feature', 'properties' => ['zone_name' => 'Downtown', 'state' => 'NY'], 'geometry' => ['type' => 'Polygon', 'coordinates' => [[
                [-74.02, 40.70], [-74.00, 40.70], [-74.00, 40.72], [-74.02, 40.72], [-74.02, 40.70],
            ]]]],
        ]]);
        $this->row('pharmacys', ['id' => 2, 'name' => 'Corner Pharmacy', 'isactive' => 1, 'isblocked' => 0, 'bestrx_pharmacy_id' => 'BRX1', 'bestrxKey' => 'key', 'bestrxSecret' => 'secret']);
        $this->row('statuses', ['id' => 1, 'name' => 'Ready for pickup']);
        foreach ([1, 4] as $method) {
            $this->row('delivery_methods', ['id' => $method]);
        }
        foreach ([1, 2] as $time) {
            $this->row('delivery_times', ['id' => $time]);
        }
        $this->row('users', ['id' => 9, 'email' => 'medic@example.test', 'phone' => '9', 'role' => 'medic', 'pharmacy_id' => 2, 'isactive' => 1, 'isblocked' => 0]);
        $this->row('users', ['id' => 40, 'email' => 'in@example.test', 'phone' => '40', 'role' => 'user', 'pharmacy_id' => 2, 'address' => '1 Wall St', 'location' => '40.7069,-74.0113']);
        $this->row('users', ['id' => 41, 'email' => 'out@example.test', 'phone' => '41', 'role' => 'user', 'pharmacy_id' => 2, 'address' => '1 State St, Albany', 'location' => '42.6526,-73.7562']);
    }

    private function zoneOf(int|string $orderId): ?string
    {
        return DB::table('dispatch_zones')->where('id', DB::table('orders')->where('id', $orderId)->value('dispatch_zone_id'))->value('name');
    }

    private function webOrder(int $patientId): int
    {
        $this->actingAs(User::findOrFail(9))->post('/orders/2/add', [
            'save' => 1, 'user' => $patientId, 'count_bags' => 1, 'delivery_method' => 1, 'delivery_time' => 2,
            'type_driver' => 1, 'rx_id' => ['RX100001'], 'rf_id' => ['0'], 'rx_count' => [1], 'rx_date' => ['2026-09-22'],
        ])->assertSessionHasNoErrors();
        $orderId = (int) DB::table('orders')->max('id');
        $this->assertSame($patientId, (int) DB::table('orders')->where('id', $orderId)->value('user_id'));

        return $orderId;
    }

    private function bestRxOrder(): void
    {
        $this->withHeader('Authorization', 'Basic '.base64_encode('key:secret'))->postJson('/api/bestrx/Order/CreateOrder', [
            'bestrx_order_id' => 'B-1', 'bestrx_unique_order_id' => 'BU-1', 'amount_to_collect' => 0,
            'refrigeration_needed' => false, 'signature_required' => false,
            'store' => ['bestrx_pharmacy_id' => 'BRX1'],
            'recipient' => [
                'first_name' => 'Regina', 'last_name' => 'Stone', 'mobile_phone' => '5550103002', 'home_phone' => '', 'email' => '',
                'address' => ['address_line_1' => '1 Wall St', 'address_line_2' => '', 'city' => 'New York', 'state' => 'NY', 'zip_code' => '10005'],
            ],
            'prescriptions' => [['rx_number' => '700 1', 'partial_fill_seqno' => 0, 'fill_date' => '2026-11-20']],
        ])->assertOk();
    }

    public function test_an_order_made_on_the_site_gets_the_zone_of_the_patients_address(): void
    {
        $this->assertSame('Downtown', $this->zoneOf($this->webOrder(40)));
        // An address outside every zone leaves the order without one.
        $this->assertNull($this->zoneOf($this->webOrder(41)));
    }

    public function test_an_order_from_the_pharmacys_software_gets_its_zone(): void
    {
        $this->bestRxOrder();

        $this->assertSame('Downtown', $this->zoneOf(DB::table('orders')->where('bestrx_order_id', 'B-1')->value('id')));
    }

    public function test_a_repeated_order_gets_the_zone_the_patient_lives_in_now(): void
    {
        $first = $this->webOrder(40);
        // The patient has moved out of the zone since.
        DB::table('users')->where('id', 40)->update(['location' => '42.6526,-73.7562']);

        [, $copy] = app(RepeatOrder::class)->handle($first);

        $this->assertNull($this->zoneOf($copy));
        $this->assertSame('Downtown', $this->zoneOf($first));
    }

    public function test_an_order_is_still_made_when_the_zones_cannot_be_read(): void
    {
        // As on a server where the code is live and the migration has not run yet.
        Schema::drop('dispatch_zones');

        $orderId = $this->webOrder(40);
        $this->bestRxOrder();

        $this->assertSame(2, DB::table('orders')->count());
        $this->assertNull(DB::table('orders')->where('id', $orderId)->value('dispatch_zone_id'));
        $this->assertNull(DispatchZones::stampOrder($orderId));
        $this->assertNull(DispatchZones::stampOrder(999));
    }
}
