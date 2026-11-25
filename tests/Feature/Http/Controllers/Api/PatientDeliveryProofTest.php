<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\Passport;
use Tests\Support\InsertsRows;
use Tests\TestCase;

/**
 * What the patient app is told about a delivery: that the order was delivered, when, and the
 * driver's drop-off photo and signature.
 */
class PatientDeliveryProofTest extends TestCase
{
    use InsertsRows;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('setex')->andReturn(true);
        $this->row('pharmacys', ['id' => 1, 'name' => 'Corner Pharmacy', 'address' => '350 5th Ave', 'isactive' => 1, 'isblocked' => 0]);
        foreach ([1 => 'New', 3 => 'On the way', 4 => 'Delivered'] as $id => $name) {
            $this->row('statuses', ['id' => $id, 'name' => $name]);
        }
        $this->row('delivery_methods', ['id' => 1, 'name' => 'Standard']);
        $this->row('delivery_times', ['id' => 1, 'name' => 'Next day']);
        $this->row('users', ['id' => 14, 'name' => 'Test', 'email' => 'test@example.test', 'phone' => '(555) 010-1111', 'role' => 'user', 'pharmacy_id' => 1, 'isactive' => 1, 'isblocked' => 0]);
        $this->row('users', ['id' => 15, 'name' => 'Other', 'email' => 'other@example.test', 'phone' => '(555) 010-2222', 'role' => 'user', 'pharmacy_id' => 1, 'isactive' => 1, 'isblocked' => 0]);
        Passport::actingAs(User::findOrFail(14));
    }

    private function order(int $id, array $values): void
    {
        $this->row('orders', $values + ['id' => $id, 'pharmacy_id' => 1, 'user_id' => 14, 'driver_id' => 0, 'statuse_id' => 1, 'delivery_method_id' => 1, 'delivery_time_id' => 1]);
    }

    private function delivered(int $id = 20): void
    {
        // The drop-off writes the finish time in the application's zone, America/New_York.
        $this->order($id, ['statuse_id' => 4, 'finish' => '2026-11-24 22:41:07', 'drop_off_photo' => '/images/drop_off/a.jpg', 'signature_photo' => '/images/signature/b.png']);
    }

    public function test_the_home_screen_is_told_the_order_was_delivered_when_and_with_what_proof(): void
    {
        $this->delivered();

        $order = $this->getJson('/api/patient/home')->assertOk()->json('last_order');

        $expected = [
            'id' => 20, 'status' => 'Delivered', 'statuse_id' => 4, 'eta' => 'Processing',
            'delivered_at' => '2026-11-24T22:41:07-05:00',
            'drop_off_photo' => '/images/drop_off/a.jpg', 'signature_photo' => '/images/signature/b.png',
        ];
        $told = array_intersect_key($order, $expected);
        ksort($expected);
        ksort($told);
        $this->assertSame($expected, $told);
    }

    public function test_an_order_on_its_way_has_no_delivery_time_and_says_how_many_minutes_are_left(): void
    {
        // A finish time left over from an earlier attempt is not a delivery.
        $this->order(21, ['statuse_id' => 1, 'eta' => 25, 'finish' => '2026-11-23 10:00:00']);

        $order = $this->getJson('/api/patient/home')->assertOk()->json('last_order');

        $this->assertSame(['25 min', null, null, 1], [$order['eta'], $order['delivered_at'], $order['drop_off_photo'], $order['statuse_id']]);
    }

    public function test_the_order_list_carries_the_delivery_time_and_both_pictures(): void
    {
        $this->delivered();
        $this->order(21, []);

        $orders = collect($this->getJson('/api/orders')->assertOk()->json('orders'))->keyBy('id');

        $this->assertSame(
            ['2026-11-24T22:41:07-05:00', '/images/drop_off/a.jpg', '/images/signature/b.png'],
            [$orders[20]['delivered_at'], $orders[20]['drop_off_photo'], $orders[20]['signature_photo']],
        );
        $this->assertSame([null, null], [$orders[21]['delivered_at'], $orders[21]['drop_off_photo']]);
    }

    public function test_the_order_details_carry_the_delivery_time_and_both_pictures(): void
    {
        $this->delivered();

        $this->getJson('/api/orders/show/20')->assertOk()
            ->assertJsonPath('order.delivered_at', '2026-11-24T22:41:07-05:00')
            ->assertJsonPath('order.drop_off_photo', '/images/drop_off/a.jpg')
            ->assertJsonPath('order.signature_photo', '/images/signature/b.png')
            ->assertJsonPath('order.statusename', 'Delivered');
    }

    public function test_another_patient_cannot_open_the_order(): void
    {
        $this->delivered();
        Passport::actingAs(User::findOrFail(15));

        $this->getJson('/api/orders/show/20')->assertForbidden();
    }
}
