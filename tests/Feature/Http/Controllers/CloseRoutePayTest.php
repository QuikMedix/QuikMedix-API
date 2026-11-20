<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\Support\InsertsRows;
use Tests\TestCase;

/**
 * Completing a driver's route credits their completed stops that were waiting for a rate with
 * the pay dispatch chose: Half, Full, or a flat amount.
 */
class CloseRoutePayTest extends TestCase
{
    use InsertsRows;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('setex')->andReturn(true);
        $this->row('users', ['id' => 1, 'name' => 'Root', 'email' => 'root@example.test', 'role' => 'superadmin', 'isactive' => 1, 'isblocked' => 0]);
        $this->row('users', ['id' => 9, 'name' => 'Test', 'last_name' => 'Driver', 'email' => 'driver@example.test', 'role' => 'driver', 'isactive' => 1, 'isblocked' => 0]);
        $this->row('users', ['id' => 10, 'name' => 'Other', 'email' => 'other@example.test', 'role' => 'driver', 'isactive' => 1, 'isblocked' => 0]);
        // Seven completed stops waiting for a rate, one stop already rated, and another driver's waiting stop.
        foreach (array_fill(0, 7, [9, -1]) + [7 => [9, 12.5], 8 => [10, -1]] as [$driver, $amount]) {
            $this->row('payouts_driver', ['driver_id' => $driver, 'order_id' => 1, 'amount' => $amount, 'withdraw' => 0]);
        }
        $this->actingAs(User::findOrFail(1))->withSession(['user_2fa' => true]);
    }

    /** @return list<float> the amounts of the test driver's payouts, oldest first */
    private function amounts(): array
    {
        return DB::table('payouts_driver')->where('driver_id', 9)->orderBy('id')->pluck('amount')->map(fn ($amount) => round((float) $amount, 2))->all();
    }

    private function complete(array $choice)
    {
        return $this->from('/routes-list/driver/9')->post('/routes-list/driver/9', ['close_route' => 1] + $choice);
    }

    public function test_a_flat_amount_is_shared_over_the_waiting_stops_to_the_cent(): void
    {
        $this->complete(['type_pay' => 1, 'pay_value' => '50'])
            ->assertRedirect('/routes-list/driver/9')->assertSessionHas('success', 'Paid $50.00 to the driver for 7 completed stops.');

        // 50.00 over seven stops is 7.14 each with two cents left: the first two stops take them.
        $this->assertSame([7.15, 7.15, 7.14, 7.14, 7.14, 7.14, 7.14, 12.5], $this->amounts());
        $this->assertSame(-1.0, (float) DB::table('payouts_driver')->where('driver_id', 10)->value('amount'));

        // The driver's app now shows the money instead of seven stops waiting.
        $this->actingAs(User::findOrFail(9), 'api')->getJson('/api/driver/payouts')->assertExactJson(['amount' => 62.5, 'pending' => 0]);
    }

    public function test_half_and_full_pay_the_shift_rates(): void
    {
        $this->complete(['type_pay' => 4])->assertSessionHas('success', 'Paid $90.00 to the driver for 7 completed stops.');
        $this->assertEqualsWithDelta(90.0, array_sum($this->amounts()) - 12.5, 0.001);

        DB::table('payouts_driver')->where('driver_id', 9)->where('amount', '!=', 12.5)->update(['amount' => -1]);
        // A typed amount is ignored with Full.
        $this->complete(['type_pay' => 5, 'pay_value' => '3'])->assertSessionHas('success', 'Paid $180.00 to the driver for 7 completed stops.');
        $this->assertEqualsWithDelta(180.0, array_sum($this->amounts()) - 12.5, 0.001);
    }

    public function test_nothing_is_paid_without_a_choice_or_without_a_flat_amount(): void
    {
        $before = $this->amounts();

        $this->complete([])->assertRedirect('/routes-list/driver/9')->assertSessionHas('error', 'Choose Half, Full or Flat rate before completing the route.');
        foreach ([null, '', '0', '-1', 'abc'] as $amount) {
            $this->complete(['type_pay' => 1, 'pay_value' => $amount])->assertSessionHas('error', 'Enter the amount to pay the driver.');
        }

        $this->assertSame($before, $this->amounts());
    }

    public function test_completing_again_finds_nothing_left_to_pay(): void
    {
        $this->complete(['type_pay' => 1, 'pay_value' => '0.03'])->assertSessionHas('success', 'Paid $0.03 to the driver for 7 completed stops.');
        $this->assertSame([0.01, 0.01, 0.01, 0.0, 0.0, 0.0, 0.0, 12.5], $this->amounts());

        $this->complete(['type_pay' => 4])->assertSessionHas('error', 'Oh no, no completed orders have been found for the selected driver for the current shift.');
    }

    public function test_the_page_sends_the_choice_with_completed_and_never_shows_the_marker_as_an_amount(): void
    {
        $page = (string) file_get_contents(resource_path('views/routes/driver.blade.php'));
        preg_match('/<form method="POST" id="close_route"[^>]*>(.*?)<\/form>/s', $page, $closing);

        // The form Completed sends carries the pay choice; it used to carry only "close_route".
        $this->assertStringContainsString('name="type_pay"', $closing[1]);
        $this->assertStringContainsString('name="pay_value"', $closing[1]);
        $this->assertStringContainsString('id="close-route-button"', $page);
        $this->assertStringContainsString('driver-route-pay.js', $page);
        $this->assertStringContainsString('value="{{ $pay_value > 0 ? $pay_value : \'\' }}"', $page);
        $this->assertStringNotContainsString('value="{{ $pay_value }}"', $page);
    }
}
