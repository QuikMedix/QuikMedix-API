<?php

namespace Tests\Feature\RouteOffers;

use App\Actions\AcceptRouteOffer;
use App\Actions\ImportDispatchZones;
use App\Models\User;
use App\Support\DispatchZones;
use App\Support\RouteOffers;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\Passport;
use Tests\Support\FakesSms;
use Tests\Support\InsertsRows;
use Tests\TestCase;

/**
 * Route offers: dispatch publishes a set of waiting orders in one zone as a route with what it
 * pays, drivers see it in the app, and the first driver to take it gets the orders on their route.
 */
class RouteOfferTest extends TestCase
{
    use FakesSms;
    use InsertsRows;

    private const BROOKLYN = 1;

    private const QUEENS = 2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('setex')->andReturn(true);
        // No push is configured unless a test sets it up, and nothing else may call out.
        config(['services.fcm.credentials' => null, 'services.beams.instance_id' => null]);
        Http::preventStrayRequests();
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'access-123', 'expires_in' => 3600]),
            'fcm.googleapis.com/*' => Http::response(['name' => 'projects/quikmedix/messages/1']),
        ]);
        $this->fakeSms();
        $this->travelTo('2026-11-27 09:00:00');

        app(ImportDispatchZones::class)->handle(['type' => 'FeatureCollection', 'features' => [
            $this->square('Brooklyn', 40.65, -73.95, 0.10, '36047'),
            $this->square('Queens', 40.75, -73.75, 0.10, '36081'),
        ]]);
        $this->row('pharmacys', ['id' => 1, 'name' => 'Corner Pharmacy', 'address' => '1 Court St, Brooklyn', 'location' => '40.6500,-73.9500', 'isactive' => 1, 'isblocked' => 0]);
        $this->row('pharmacys', ['id' => 2, 'name' => 'Second Pharmacy', 'address' => '9 Flatbush Ave, Brooklyn', 'location' => '40.6600,-73.9600', 'isactive' => 1, 'isblocked' => 0]);
        foreach ([1 => 'Ready for pickup', 2 => 'In process', 3 => 'On the way', 4 => 'Delivered', 5 => 'Cancelled'] as $id => $name) {
            $this->row('statuses', ['id' => $id, 'name' => $name]);
        }
        $this->row('delivery_methods', ['id' => 1, 'name' => 'Standard']);
        $this->row('delivery_times', ['id' => 1, 'name' => 'Next day']);
        $this->row('users', ['id' => 1, 'name' => 'Root', 'email' => 'root@example.test', 'phone' => '1', 'role' => 'superadmin', 'isactive' => 1, 'isblocked' => 0]);
        $this->row('users', ['id' => 20, 'name' => 'Dee', 'last_name' => 'Driver', 'email' => 'dee@example.test', 'phone' => '20', 'role' => 'driver', 'isactive' => 1, 'isblocked' => 0]);
        $this->row('users', ['id' => 21, 'name' => 'Sam', 'last_name' => 'Second', 'email' => 'sam@example.test', 'phone' => '21', 'role' => 'driver', 'isactive' => 1, 'isblocked' => 0]);
        // Three patients in Brooklyn, nearer and farther from the pharmacy, and one in Queens.
        $this->patient(101, 'Near', '40.6510,-73.9500');
        $this->patient(102, 'Far', '40.7000,-73.9000');
        $this->patient(103, 'Middle', '40.6700,-73.9300');
        $this->patient(104, 'Queens', '40.7500,-73.7500');
        $this->order(30, 102);
        $this->order(31, 101);
        $this->order(32, 103);
        $this->order(33, 101);
        $this->order(34, 104);
        $this->order(35, 103, ['pharmacy_id' => 2]);
    }

    private function square(string $name, float $lat, float $lng, float $half, string $fips): array
    {
        return ['type' => 'Feature', 'properties' => ['zone_name' => $name, 'state' => 'NY', 'county_fips' => $fips], 'geometry' => ['type' => 'Polygon', 'coordinates' => [[
            [$lng - $half, $lat - $half], [$lng + $half, $lat - $half], [$lng + $half, $lat + $half], [$lng - $half, $lat + $half], [$lng - $half, $lat - $half],
        ]]]];
    }

    private function patient(int $id, string $name, string $location): void
    {
        $this->row('users', ['id' => $id, 'name' => $name, 'last_name' => 'Patient', 'email' => "p$id@example.test", 'phone' => (string) $id, 'role' => 'user', 'pharmacy_id' => 1,
            'address' => "$id Main St", 'zip' => '11201', 'location' => $location, 'isactive' => 1, 'isblocked' => 0]);
    }

    private function order(int $id, int $patientId, array $values = []): void
    {
        $this->row('orders', $values + ['id' => $id, 'pharmacy_id' => 1, 'user_id' => $patientId, 'statuse_id' => 1, 'type_driver' => 1, 'delivery_method_id' => 1, 'delivery_time_id' => 1]);
        DispatchZones::stampOrder($id);
    }

    private function asDispatch(): static
    {
        return $this->actingAs(User::findOrFail(1))->withSession(['user_2fa' => 1]);
    }

    /** Dispatch publishes the given Brooklyn orders; returns the offer. */
    private function publish(array $orderIds = [30, 31, 32, 33, 35], array $details = []): object
    {
        $this->asDispatch()->post('/route-offers', $details + ['zone_id' => self::BROOKLYN, 'order_ids' => $orderIds])->assertRedirect('/route-offers?zone='.self::BROOKLYN)->assertSessionHas('success');

        return DB::table('route_offers')->orderByDesc('id')->first();
    }

    private function asDriver(int $id): static
    {
        $this->app['auth']->forgetGuards();
        Passport::actingAs(User::findOrFail($id));

        return $this;
    }

    public function test_new_orders_carry_the_zone_of_the_patients_address(): void
    {
        $this->assertSame(
            [30 => self::BROOKLYN, 31 => self::BROOKLYN, 32 => self::BROOKLYN, 33 => self::BROOKLYN, 34 => self::QUEENS, 35 => self::BROOKLYN],
            DB::table('orders')->orderBy('id')->pluck('dispatch_zone_id', 'id')->map(fn ($zone) => (int) $zone)->all(),
        );
    }

    public function test_the_board_groups_waiting_orders_by_zone_and_follows_a_patient_who_moved(): void
    {
        // An order with a driver, a delivered one and a pharmacy's own delivery are not waiting.
        $this->order(40, 101, ['driver_id' => 20]);
        $this->order(41, 101, ['statuse_id' => 4]);
        $this->order(42, 101, ['type_driver' => 2]);
        // The Queens patient moved to an address outside every zone.
        DB::table('users')->where('id', 104)->update(['location' => '42.6526,-73.7562']);

        $page = $this->asDispatch()->get('/route-offers?zone='.self::BROOKLYN)->assertOk();

        $page->assertSeeInOrder(['Brooklyn, NY', '5', 'No zone', '1'], false)->assertDontSee('Queens, NY');
        $page->assertViewHas('orders', fn ($orders) => $orders->pluck('id')->all() === [30, 31, 32, 33, 35]);
        $this->assertNull(DB::table('orders')->where('id', 34)->value('dispatch_zone_id'));
        $this->asDispatch()->get('/route-offers?zone=none')->assertOk()->assertViewHas('orders', fn ($orders) => $orders->pluck('id')->all() === [34])->assertSee('They cannot go into an offer');
    }

    public function test_dispatch_publishes_a_route_named_after_the_zone_and_day_at_the_zone_rate(): void
    {
        $offer = $this->publish(details: ['pickup_time' => '9:00 AM', 'note' => 'Fridge bags']);

        // Five orders for three patients are three stops, at $3.75 a stop.
        $this->assertSame(['BK-1127-01', 'open', 3, 11.25, '9:00 AM', 'Fridge bags', '2026-11-27', 1], [
            $offer->code, $offer->status, (int) $offer->stops, (float) $offer->pay, $offer->pickup_time, $offer->note, substr($offer->delivery_date, 0, 10), (int) $offer->created_by,
        ]);
        $this->assertSame([30, 31, 32, 33, 35], DB::table('route_offer_orders')->where('route_offer_id', $offer->id)->orderBy('order_id')->pluck('order_id')->map(fn ($id) => (int) $id)->all());
        // Corner Pharmacy, Second Pharmacy, then the nearest patient each time: about seven miles by road.
        $this->assertEqualsWithDelta(7.4, (float) $offer->miles, 0.5);
        // Orders in an offer no longer wait on the board, and nothing is on a driver's route yet.
        $this->asDispatch()->get('/route-offers')->assertOk()->assertDontSee('Brooklyn, NY')->assertSee('BK-1127-01')->assertSee('Offered to drivers');
        $this->assertSame(0, DB::table('routes_priority')->count());
    }

    public function test_publishing_notifies_the_app_of_every_active_driver_and_never_sends_a_text(): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $privateKey);
        config(['services.fcm.credentials' => json_encode([
            'project_id' => 'quikmedix', 'client_email' => 'push@quikmedix.iam.gserviceaccount.com',
            'private_key' => $privateKey, 'token_uri' => 'https://oauth2.googleapis.com/token',
        ])]);
        DB::table('users')->where('id', 20)->update(['device_token' => 'dee-phone']);
        // Without the app on a phone, a blocked driver, and a patient with the app: none is told.
        DB::table('users')->where('id', 21)->update(['device_token' => null]);
        $this->row('users', ['id' => 22, 'email' => 'blocked@example.test', 'phone' => '22', 'role' => 'driver', 'isactive' => 1, 'isblocked' => 1, 'device_token' => 'blocked-phone']);
        DB::table('users')->where('id', 101)->update(['device_token' => 'patient-phone']);

        $this->publish();

        $pushes = Http::recorded(fn (HttpRequest $request): bool => str_contains($request->url(), 'fcm.googleapis.com'))
            ->map(fn (array $exchange): array => [$exchange[0]['message']['token'], $exchange[0]['message']['notification']['title'], $exchange[0]['message']['notification']['body']])->values()->all();
        $this->assertSame([['dee-phone', 'New route in Brooklyn', '3 stops, about 8 miles, pays $11.25. Open Available Routes to take it.']], $pushes);
        $this->assertSame([], $this->texts);
    }

    public function test_the_next_route_that_day_gets_the_next_number_and_a_typed_pay_wins(): void
    {
        $this->publish([30]);
        $second = $this->publish([31, 33], ['pay' => '150', 'delivery_date' => '2026-11-27']);
        $tomorrow = $this->publish([32], ['delivery_date' => '2026-11-28']);

        $this->assertSame(['BK-1127-02', 150.0, 1], [$second->code, (float) $second->pay, (int) $second->stops]);
        $this->assertSame('BK-1128-01', $tomorrow->code);
    }

    public function test_orders_that_cannot_be_offered_are_left_out_and_an_empty_offer_is_refused(): void
    {
        $this->publish([30]);

        // Order 30 is already offered, 34 is in Queens, 999 does not exist: only 31 goes in.
        $offer = $this->publish([30, 31, 34, 999]);
        $this->assertSame([31], DB::table('route_offer_orders')->where('route_offer_id', $offer->id)->pluck('order_id')->map(fn ($id) => (int) $id)->all());

        $this->asDispatch()->post('/route-offers', ['zone_id' => self::BROOKLYN, 'order_ids' => [30, 34]])->assertSessionHas('error');
        $this->asDispatch()->post('/route-offers', ['zone_id' => self::BROOKLYN])->assertSessionHasErrors('order_ids');
        $this->assertSame(2, DB::table('route_offers')->count());
    }

    public function test_a_dispatcher_with_an_admin_zone_offers_only_that_zones_pharmacies(): void
    {
        $this->row('admin_areas', ['id' => 7, 'name' => 'North']);
        DB::table('pharmacys')->where('id', 2)->update(['zone_id' => 7]);
        DB::table('users')->where('id', 1)->update(['role' => 'dispadmin', 'zone_id' => 7]);

        $this->asDispatch()->get('/route-offers?zone='.self::BROOKLYN)->assertOk()->assertViewHas('orders', fn ($orders) => $orders->pluck('id')->all() === [35]);
        $offer = $this->publish([30, 35]);

        $this->assertSame([35], DB::table('route_offer_orders')->where('route_offer_id', $offer->id)->pluck('order_id')->map(fn ($id) => (int) $id)->all());
    }

    public function test_a_driver_sees_open_offers_without_any_patient_details(): void
    {
        $this->publish(details: ['pickup_time' => '9:00 AM']);

        $answer = $this->asDriver(20)->getJson('/api/route-offers')->assertOk();

        $answer->assertJson(['mine' => null, 'can_accept' => true]);
        $offer = $answer->json('offers.0');
        $this->assertSame(
            ['BK-1127-01', 'open', 'Brooklyn', 'NY', '2026-11-27', '9:00 AM', 3, 5, 11.25, '$11.25'],
            [$offer['code'], $offer['status'], $offer['zone'], $offer['state'], $offer['delivery_date'], $offer['pickup_time'], $offer['stops'], $offer['orders'], $offer['pay'], $offer['pay_text']],
        );
        $this->assertSame([
            ['id' => 1, 'name' => 'Corner Pharmacy', 'address' => '1 Court St, Brooklyn', 'location' => '40.6500,-73.9500'],
            ['id' => 2, 'name' => 'Second Pharmacy', 'address' => '9 Flatbush Ave, Brooklyn', 'location' => '40.6600,-73.9600'],
        ], $offer['pickups']);
        $this->assertStringNotContainsString('Patient', $answer->getContent());
        $this->assertStringNotContainsString('Main St', $answer->getContent());
    }

    public function test_taking_an_offer_puts_its_orders_on_the_drivers_route_pharmacies_first_then_the_nearest_patient(): void
    {
        $offer = $this->publish();
        // The driver is already carrying one order; the offer's stops come after it.
        $this->row('routes_priority', ['driver_id' => 20, 'order_id' => 900, 'type' => 'patient', 'type_id' => 900, 'priority' => 4]);

        $this->asDriver(20)->postJson("/api/route-offers/{$offer->id}/accept")->assertOk()->assertExactJson(['message' => 'Route BK-1127-01 is yours.', 'orders' => 5]);

        $this->assertSame([
            [5, 'pharmacy', 1, '30,31,32,33'],
            [6, 'pharmacy', 2, '35'],
            [7, 'patient', 101, '31,33'],
            [8, 'patient', 103, '32,35'],
            [9, 'patient', 102, '30'],
        ], DB::table('routes_priority')->where('driver_id', 20)->where('order_id', '!=', 900)->orderBy('priority')->orderBy('order_id')->get()
            ->groupBy('priority')->map(fn ($stop, $priority) => [$priority, $stop[0]->type, (int) $stop[0]->type_id, $stop->pluck('order_id')->implode(',')])->values()->all());
        // Flat rate with the amount still to come, so the stops count as waiting for the route to be closed.
        $this->assertSame([[1, -1.0]], DB::table('routes_priority')->where('order_id', '!=', 900)->distinct()->get(['type_pay', 'pay_value'])->map(fn ($row) => [(int) $row->type_pay, (float) $row->pay_value])->all());
        $this->assertSame([[20, '2']], DB::table('orders')->whereIn('id', [30, 31, 32, 33, 35])->distinct()->get(['driver_id', 'ready'])->map(fn ($row) => [(int) $row->driver_id, (string) $row->ready])->all());
        $this->assertNull(DB::table('orders')->where('id', 34)->value('driver_id'));
        $taken = DB::table('route_offers')->where('id', $offer->id)->first();
        $this->assertSame(['accepted', 20, '2026-11-27 09:00:00'], [$taken->status, (int) $taken->driver_id, $taken->accepted_at]);
        $this->asDispatch()->get('/route-offers')->assertOk()->assertSee('Taken by Dee Driver');
    }

    public function test_only_the_first_driver_gets_an_offer(): void
    {
        $offer = $this->publish();
        $this->asDriver(20)->postJson("/api/route-offers/{$offer->id}/accept")->assertOk();

        $this->asDriver(21)->postJson("/api/route-offers/{$offer->id}/accept")->assertStatus(409)->assertExactJson(['message' => AcceptRouteOffer::TAKEN, 'errors' => 'Conflict']);
        $this->asDriver(20)->postJson("/api/route-offers/{$offer->id}/accept")->assertStatus(409)->assertJson(['message' => 'This route is already yours.']);

        $this->assertSame([20], DB::table('routes_priority')->distinct()->pluck('driver_id')->map(fn ($id) => (int) $id)->all());
        // Each of the five orders has its pick-up and its delivery on the route, once.
        $this->assertSame(10, DB::table('routes_priority')->count());
        // The other driver no longer sees it; the one who took it sees it as theirs.
        $this->asDriver(21)->getJson('/api/route-offers')->assertOk()->assertExactJson(['offers' => [], 'mine' => null, 'can_accept' => true]);
        $this->asDriver(20)->getJson('/api/route-offers')->assertOk()->assertExactJson([
            'offers' => [], 'can_accept' => false,
            'mine' => ['id' => (int) $offer->id, 'code' => 'BK-1127-01', 'zone' => 'Brooklyn', 'stops' => 3, 'pay' => 11.25, 'pay_text' => '$11.25'],
        ]);
    }

    public function test_a_driver_takes_one_offer_at_a_time(): void
    {
        $first = $this->publish([30]);
        $second = $this->publish([31]);
        $this->asDriver(20)->postJson("/api/route-offers/{$first->id}/accept")->assertOk();

        $this->asDriver(20)->postJson("/api/route-offers/{$second->id}/accept")->assertStatus(409)->assertJson(['message' => AcceptRouteOffer::BUSY]);
        $this->assertSame('open', DB::table('route_offers')->where('id', $second->id)->value('status'));

        // Once the first route's stops are done, the next can be taken.
        DB::table('routes_priority')->delete();
        $this->asDriver(20)->postJson("/api/route-offers/{$second->id}/accept")->assertOk();
    }

    public function test_orders_that_left_an_open_offer_are_not_handed_over(): void
    {
        $offer = $this->publish([30, 31, 32]);
        // Since it was published one order was cancelled and dispatch gave another to a driver.
        DB::table('orders')->where('id', 30)->update(['statuse_id' => 5]);
        DB::table('orders')->where('id', 31)->update(['driver_id' => 21]);

        $this->assertSame(1, $this->asDriver(20)->getJson('/api/route-offers')->assertOk()->json('offers.0.orders'));
        $this->asDriver(20)->postJson("/api/route-offers/{$offer->id}/accept")->assertOk()->assertJson(['orders' => 1]);

        $this->assertSame([32], DB::table('routes_priority')->where('driver_id', 20)->distinct()->pluck('order_id')->map(fn ($id) => (int) $id)->all());
        $this->assertSame(21, (int) DB::table('orders')->where('id', 31)->value('driver_id'));
    }

    public function test_an_offer_with_no_orders_left_is_not_listed_and_cannot_be_taken(): void
    {
        $offer = $this->publish([30]);
        DB::table('orders')->where('id', 30)->update(['statuse_id' => 5]);

        $this->asDriver(20)->getJson('/api/route-offers')->assertOk()->assertJson(['offers' => []]);
        $this->asDriver(20)->postJson("/api/route-offers/{$offer->id}/accept")->assertStatus(409)->assertJson(['message' => AcceptRouteOffer::GONE]);

        $this->assertSame(['withdrawn', null], [DB::table('route_offers')->value('status'), DB::table('route_offers')->value('driver_id')]);
        $this->asDriver(20)->postJson('/api/route-offers/999/accept')->assertStatus(409)->assertJson(['message' => AcceptRouteOffer::GONE]);
    }

    public function test_dispatch_withdraws_an_open_offer_and_its_orders_wait_again(): void
    {
        $offer = $this->publish([30, 31]);

        $this->asDispatch()->post("/route-offers/{$offer->id}/withdraw")->assertRedirect('/route-offers')->assertSessionHas('success');

        $this->asDriver(20)->getJson('/api/route-offers')->assertOk()->assertJson(['offers' => []]);
        $this->asDriver(20)->postJson("/api/route-offers/{$offer->id}/accept")->assertStatus(409)->assertJson(['message' => AcceptRouteOffer::GONE]);
        $this->app['auth']->forgetGuards();
        $this->asDispatch()->get('/route-offers?zone='.self::BROOKLYN)->assertOk()->assertViewHas('orders', fn ($orders) => $orders->pluck('id')->all() === [30, 31, 32, 33, 35]);
        // The orders can go into a new offer, which gets the next number.
        $this->assertSame('BK-1127-02', $this->publish([30, 31])->code);
    }

    public function test_an_offer_a_driver_took_cannot_be_withdrawn(): void
    {
        $offer = $this->publish([30]);
        $this->asDriver(20)->postJson("/api/route-offers/{$offer->id}/accept")->assertOk();
        $this->app['auth']->forgetGuards();

        $this->asDispatch()->post("/route-offers/{$offer->id}/withdraw")->assertSessionHas('error');

        $this->assertSame('accepted', DB::table('route_offers')->value('status'));
    }

    public function test_only_drivers_use_the_offers_in_the_app_and_only_dispatch_the_board(): void
    {
        $offer = $this->publish([30]);

        $this->asDriver(101)->getJson('/api/route-offers')->assertForbidden();
        $this->asDriver(101)->postJson("/api/route-offers/{$offer->id}/accept")->assertForbidden();
        $this->assertSame('open', DB::table('route_offers')->value('status'));

        $this->app['auth']->forgetGuards();
        DB::table('users')->where('id', 1)->update(['role' => 'medic', 'pharmacy_id' => 1]);
        $this->asDispatch()->get('/route-offers')->assertForbidden();
        $this->asDispatch()->post('/route-offers', ['zone_id' => self::BROOKLYN, 'order_ids' => [31]])->assertForbidden();
        $this->asDispatch()->post("/route-offers/{$offer->id}/withdraw")->assertForbidden();
    }

    public function test_the_drivers_route_page_offers_the_route_pay_as_the_flat_rate(): void
    {
        $offer = $this->publish([30, 31], ['pay' => '135']);
        $this->asDriver(20)->postJson("/api/route-offers/{$offer->id}/accept")->assertOk();

        $waiting = RouteOffers::awaitingPay(20);
        $this->assertSame(['BK-1127-01', 135.0], [$waiting->code, (float) $waiting->pay]);

        // Delivered and waiting for the route to be closed: still to be paid.
        DB::table('routes_priority')->delete();
        $this->row('payouts_driver', ['driver_id' => 20, 'order_id' => 30, 'amount' => -1]);
        $this->assertSame('BK-1127-01', RouteOffers::awaitingPay(20)->code);

        // Once the route is closed the stops carry real amounts and nothing is waiting.
        DB::table('payouts_driver')->update(['amount' => 67.5]);
        $this->assertNull(RouteOffers::awaitingPay(20));
        $this->assertNull(RouteOffers::awaitingPay(21));
    }

    public function test_the_route_page_names_the_offer_and_uses_its_pay(): void
    {
        // The page itself needs MySQL to draw; these are the two places the offer reaches it.
        $controller = (string) file_get_contents(app_path('Http/Controllers/RoutePlanningController.php'));
        $this->assertStringContainsString('$route_offer = RouteOffers::awaitingPay($driver_id);', $controller);
        $this->assertStringContainsString('$pay_value = (float) $route_offer->pay;', $controller);
        $page = (string) file_get_contents(resource_path('views/routes/driver.blade.php'));
        $this->assertStringContainsString('The driver took route <strong>{{ $route_offer->code }}</strong>', $page);
    }
}
