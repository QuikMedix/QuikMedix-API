<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\User;
use App\Support\DeliveryProgress;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\Passport;
use Tests\Support\InsertsRows;
use Tests\TestCase;

/**
 * While an order is on its way the patient app is told, the same on every screen: the minutes
 * until the driver arrives, how many stops come first, where the driver is once they are close,
 * and the note the patient left for the driver.
 */
class DeliveryProgressTest extends TestCase
{
    use InsertsRows;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('setex')->andReturn(true);
        $this->row('pharmacys', ['id' => 1, 'name' => 'Corner Pharmacy', 'location' => '40.7484,-73.9857', 'isactive' => 1, 'isblocked' => 0]);
        foreach ([2 => 'In process', 3 => 'On the way', 4 => 'Delivered'] as $id => $name) {
            $this->row('statuses', ['id' => $id, 'name' => $name]);
        }
        $this->row('delivery_methods', ['id' => 1, 'name' => 'Standard']);
        $this->row('delivery_times', ['id' => 1, 'name' => 'Next day']);
        $this->row('users', ['id' => 14, 'name' => 'Test', 'email' => 'test@example.test', 'phone' => '(555) 010-1111', 'role' => 'user', 'pharmacy_id' => 1, 'isactive' => 1, 'isblocked' => 0]);
        $this->row('users', ['id' => 20, 'name' => 'Dee', 'email' => 'dee@example.test', 'phone' => '(555) 010-3333', 'role' => 'driver', 'isactive' => 1, 'isblocked' => 0]);
        $this->row('orders', ['id' => 30, 'pharmacy_id' => 1, 'user_id' => 14, 'driver_id' => 20, 'statuse_id' => 3, 'delivery_method_id' => 1, 'delivery_time_id' => 1, 'eta' => 37]);
        $this->row('locations', ['user_id' => 20, 'location' => '40.7000,-74.0000']);
        $this->row('locations', ['user_id' => 20, 'location' => '40.7100,-74.0100']);
        Passport::actingAs(User::findOrFail(14));
    }

    /**
     * Puts the patient's stop on the driver's route after the given number of other stops:
     * first the pharmacy pick-up, then other patients, each holding two orders.
     */
    private function route(int $stopsBefore): void
    {
        $id = 0;
        for ($stop = 1; $stop <= $stopsBefore; $stop++) {
            foreach ([1, 2] as $order) {
                $this->row('routes_priority', ['id' => ++$id, 'driver_id' => 20, 'order_id' => 100 * $stop + $order, 'type' => $stop === 1 ? 'pharmacy' : 'patient', 'type_id' => $stop === 1 ? 1 : 500 + $stop, 'priority' => $stop]);
            }
        }
        $this->row('routes_priority', ['id' => ++$id, 'driver_id' => 20, 'order_id' => 30, 'type' => 'patient', 'type_id' => 14, 'priority' => $stopsBefore + 1]);
        // Stops after the patient's, and another driver's, do not count.
        $this->row('routes_priority', ['id' => ++$id, 'driver_id' => 20, 'order_id' => 900, 'type' => 'patient', 'type_id' => 900, 'priority' => $stopsBefore + 2]);
        $this->row('routes_priority', ['id' => ++$id, 'driver_id' => 21, 'order_id' => 901, 'type' => 'patient', 'type_id' => 901, 'priority' => 1]);
    }

    /** What the three answers say about order 30: home, the order list and the order details. */
    private function told(): array
    {
        return [
            'home' => $this->getJson('/api/patient/home')->assertOk()->json('last_order'),
            'list' => collect($this->getJson('/api/orders')->assertOk()->json('orders'))->firstWhere('id', 30),
            'details' => $this->getJson('/api/orders/show/30')->assertOk()->json('order'),
        ];
    }

    public function test_every_screen_is_told_the_same_arrival_time_and_stops_ahead(): void
    {
        $this->route(stopsBefore: 3);

        foreach ($this->told() as $screen => $order) {
            $this->assertSame([37, '37 min', 3, 'Next day'], [$order['eta_minutes'], $order['eta'], $order['stops_ahead'], $order['delivery_time']], $screen);
        }
    }

    public function test_an_office_visit_before_the_patient_is_a_stop_of_its_own(): void
    {
        $this->route(stopsBefore: 2);
        $this->row('routes_priority', ['id' => 50, 'driver_id' => 20, 'order_id' => 700, 'type' => 'office', 'type_id' => 1, 'priority' => 2]);
        $this->row('routes_priority', ['id' => 51, 'driver_id' => 20, 'order_id' => 701, 'type' => 'office', 'type_id' => 1, 'priority' => 1]);

        $this->assertSame(4, DeliveryProgress::stopsAhead(DB::table('orders')->where('id', 30)->first()));
    }

    public function test_the_drivers_position_is_shared_once_five_or_fewer_stops_are_left(): void
    {
        $this->route(stopsBefore: 5);

        $told = $this->told();
        $this->assertSame(['40.7100,-74.0100', '40.7100,-74.0100', '40.7100,-74.0100'], [$told['home']['order_location'], $told['list']['order_location'], $told['details']['order_location']]);
    }

    public function test_with_more_than_five_stops_left_the_patient_is_told_the_count_but_not_where_the_driver_is(): void
    {
        $this->route(stopsBefore: 6);

        $told = $this->told();
        $this->assertSame([6, 6, 6], array_column($told, 'stops_ahead'));
        // The details map falls back to the pharmacy; the other two say nothing.
        $this->assertSame([null, null, '40.7484,-73.9857'], [$told['home']['order_location'], $told['list']['order_location'], $told['details']['order_location']]);
    }

    public function test_an_order_not_yet_on_a_route_has_no_arrival_time_stops_or_position(): void
    {
        DB::table('orders')->where('id', 30)->update(['statuse_id' => 2, 'driver_id' => 0, 'eta' => null]);

        foreach ($this->told() as $screen => $order) {
            $this->assertSame([null, 'Processing', null], [$order['eta_minutes'], $order['eta'], $order['stops_ahead']], $screen);
        }
    }

    public function test_a_delivered_order_has_no_arrival_time_even_with_minutes_left_over(): void
    {
        $this->route(stopsBefore: 1);
        DB::table('orders')->where('id', 30)->update(['statuse_id' => 4, 'finish' => '2026-11-24 22:41:07']);

        foreach ($this->told() as $screen => $order) {
            $this->assertSame([null, 'Processing', null], [$order['eta_minutes'], $order['eta'], $order['stops_ahead']], $screen);
        }
    }

    public function test_long_waits_are_written_in_hours_and_minutes(): void
    {
        $this->assertSame(['59 min', '1 h', '1 h 15 min', 'Processing'], [
            DeliveryProgress::etaText(59), DeliveryProgress::etaText(60), DeliveryProgress::etaText(75), DeliveryProgress::etaText(null),
        ]);
        $this->assertNull(DeliveryProgress::etaMinutes(3, 0));
    }

    public function test_the_patients_note_for_the_driver_comes_back_on_the_order(): void
    {
        $this->postJson('/api/orders/30/add_note', ['note' => 'Ring twice'])->assertOk();
        $this->postJson('/api/orders/30/add_note', ['note' => "Leave it at the doorman's desk"])->assertOk();

        $this->assertSame("Leave it at the doorman's desk", $this->getJson('/api/patient/home')->json('last_order.customer_note'));
        $this->getJson('/api/orders/show/30')->assertJsonPath('order.customer_note', "Leave it at the doorman's desk");
        // The driver's route reads the same note for the stop.
        $this->assertSame("Leave it at the doorman's desk", DeliveryProgress::patientNote(30));
        $this->assertNull(DeliveryProgress::patientNote(31));
    }
}
