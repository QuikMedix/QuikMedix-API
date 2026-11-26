<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\User;
use App\Support\CopayStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\Passport;
use Tests\Support\FakesSquare;
use Tests\Support\InsertsRows;
use Tests\TestCase;

/**
 * A co-pay can be paid in cash or by check to the driver, or by card in the app. The patient says
 * which in the app; the driver records cash or a check at the door.
 */
class CopayByCashCheckOrCardTest extends TestCase
{
    use FakesSquare;
    use InsertsRows;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        config(['app.SQUARE_LOCATION_ID' => 'LOC-1']);
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('setex')->andReturn(true);
        $this->row('pharmacys', ['id' => 1, 'name' => 'Corner Pharmacy', 'isactive' => 1, 'isblocked' => 0]);
        foreach ([2 => 'In process', 3 => 'On the way', 4 => 'Delivered'] as $id => $name) {
            $this->row('statuses', ['id' => $id, 'name' => $name]);
        }
        $this->row('delivery_methods', ['id' => 1, 'name' => 'Standard']);
        $this->row('delivery_times', ['id' => 1, 'name' => 'Next day']);
        $this->row('users', ['id' => 14, 'name' => 'Test', 'email' => 'test@example.test', 'phone' => '(555) 010-1111', 'role' => 'user', 'pharmacy_id' => 1, 'isactive' => 1, 'isblocked' => 0]);
        $this->row('users', ['id' => 15, 'name' => 'Other', 'email' => 'other@example.test', 'phone' => '(555) 010-2222', 'role' => 'user', 'pharmacy_id' => 1, 'isactive' => 1, 'isblocked' => 0]);
        $this->row('users', ['id' => 20, 'name' => 'Dee', 'email' => 'dee@example.test', 'phone' => '(555) 010-3333', 'role' => 'driver', 'pharmacy_id' => null, 'isactive' => 1, 'isblocked' => 0]);
        // An order on its way with $12.50 due, and its stop on the driver's route.
        $this->row('orders', ['id' => 30, 'pharmacy_id' => 1, 'user_id' => 14, 'driver_id' => 20, 'statuse_id' => 3, 'delivery_method_id' => 1, 'delivery_time_id' => 1, 'copay' => 12.5, 'statuse_copay' => CopayStatus::DUE]);
        $this->row('routes_priority', ['id' => 1, 'driver_id' => 20, 'order_id' => 30, 'type' => 'patient', 'type_id' => 14, 'priority' => 1]);
    }

    private function as(int $userId): static
    {
        $this->app['auth']->forgetGuards();
        Passport::actingAs(User::findOrFail($userId));

        return $this;
    }

    private function order(): object
    {
        return DB::table('orders')->where('id', 30)->first();
    }

    public function test_the_lookup_table_names_paid_by_check(): void
    {
        $this->assertSame('Paid by check', DB::table('statuses_copay')->where('id', CopayStatus::PAID_BY_CHECK)->value('name'));
        $this->assertTrue(CopayStatus::isSettled(7) && CopayStatus::isCollected(7));
        $this->assertFalse(CopayStatus::isSettled(CopayStatus::DUE) || CopayStatus::isSettled(CopayStatus::NOT_PAID));
    }

    public function test_a_patient_says_how_they_will_pay_and_the_app_is_told_back(): void
    {
        foreach (CopayStatus::METHODS as $method) {
            $this->as(14)->postJson('/api/orders/copay/method/30', ['method' => $method])->assertOk()->assertExactJson(['message' => 'OK', 'copay_method' => $method]);
            $this->assertSame($method, $this->order()->copay_method);
        }

        $home = $this->as(14)->getJson('/api/patient/home')->assertOk()->json('last_order');
        $this->assertSame(['card', CopayStatus::DUE], [$home['copay_method'], $home['copay_status_id']]);
        $this->assertSame('card', collect($this->getJson('/api/orders')->json('orders'))->firstWhere('id', 30)['copay_method']);
        $this->getJson('/api/orders/show/30')->assertOk()->assertJsonPath('order.copay_method', 'card');
        // Choosing pays nothing.
        $this->assertSame(CopayStatus::DUE, (int) $this->order()->statuse_copay);
    }

    public function test_the_choice_is_refused_when_it_is_not_one_of_the_three_or_not_the_patients_to_make(): void
    {
        $this->as(14)->postJson('/api/orders/copay/method/30', ['method' => 'bitcoin'])->assertStatus(400)->assertJsonPath('message', 'Choose cash, check or card.');
        $this->as(15)->postJson('/api/orders/copay/method/30', ['method' => 'cash'])->assertNotFound();
        $this->as(20)->postJson('/api/orders/copay/method/30', ['method' => 'cash'])->assertNotFound();
        $this->as(14)->postJson('/api/orders/copay/method/999', ['method' => 'cash'])->assertNotFound();
        $this->assertNull($this->order()->copay_method);

        DB::table('orders')->where('id', 30)->update(['statuse_copay' => CopayStatus::PAID_BY_CHECK]);
        $this->as(14)->postJson('/api/orders/copay/method/30', ['method' => 'cash'])->assertForbidden()->assertJsonPath('message', 'This order has no co-pay left to pay.');
    }

    public function test_a_driver_records_a_check_like_cash_and_gets_a_stop_to_bring_it_back(): void
    {
        $this->as(20)->postJson('/api/orders/copay/pay_check/30')->assertOk()->assertExactJson(['message' => 'OK']);

        $this->assertSame(CopayStatus::PAID_BY_CHECK, (int) $this->order()->statuse_copay);
        $this->assertSame(
            ['order_id' => 30, 'driver_id' => 20, 'copay' => 12.5, 'method' => 'check'],
            array_map(fn ($value) => is_numeric($value) ? $value + 0 : $value, (array) DB::table('cash_log')->first(['order_id', 'driver_id', 'copay', 'method']))
        );
        // A driver with no pharmacy of their own takes what they collected to the office.
        $this->assertSame(['office', 2], array_values((array) DB::table('routes_priority')->where('order_id', 30)->where('type', '!=', 'patient')->first(['type', 'priority'])));

        // Paid once: neither the driver nor the patient can pay it again.
        $this->as(20)->postJson('/api/orders/copay/pay_check/30')->assertForbidden();
        $this->as(20)->postJson('/api/orders/copay/pay_cash/30')->assertForbidden();
        $this->as(14)->postJson('/api/orders/copay/pay/30')->assertForbidden();
        $this->as(14)->getJson('/api/orders/show/30')->assertJsonPath('order.statuse_copay_name', 'Paid by check');
    }

    public function test_cash_is_still_recorded_as_cash(): void
    {
        $this->as(20)->postJson('/api/orders/copay/pay_cash/30')->assertOk();

        $this->assertSame(CopayStatus::PAID_IN_CASH, (int) $this->order()->statuse_copay);
        $this->assertSame('cash', DB::table('cash_log')->value('method'));
    }

    public function test_only_the_orders_own_driver_records_a_check(): void
    {
        $this->row('users', ['id' => 21, 'email' => 'eve@example.test', 'phone' => '(555) 010-4444', 'role' => 'driver', 'isactive' => 1, 'isblocked' => 0]);

        $this->as(21)->postJson('/api/orders/copay/pay_check/30')->assertForbidden();
        $this->as(14)->postJson('/api/orders/copay/pay_check/30')->assertForbidden();
        $this->as(20)->postJson('/api/orders/copay/pay_check/999')->assertNotFound();
        $this->assertSame([CopayStatus::DUE, 0], [(int) $this->order()->statuse_copay, DB::table('cash_log')->count()]);
    }

    public function test_a_patient_who_chose_card_pays_in_the_app_with_the_saved_card(): void
    {
        $this->fakeSquare();
        $this->row('payment_accounts', ['user_id' => 14, 'type' => 'card', 'profile_id' => 'cust_1', 'payment_profile_id' => 'card_1', 'card' => '4242']);

        $this->as(14)->postJson('/api/orders/copay/method/30', ['method' => 'card'])->assertOk();
        $this->postJson('/api/orders/copay/pay/30')->assertOk();

        $this->assertSame(CopayStatus::PAID_BY_CARD, (int) $this->order()->statuse_copay);
        $this->assertSame(1250, $this->square['createPayment'][0]->getAmountMoney()->getAmount());
    }
}
