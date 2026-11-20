<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\Support\InsertsRows;
use Tests\TestCase;

/**
 * The driver app's earnings history: what was earned, what has been paid, and week by week.
 */
class DriverEarningsTest extends TestCase
{
    use InsertsRows;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        foreach ([3 => 'driver', 4 => 'driver', 5 => 'user'] as $id => $role) {
            $this->row('users', ['id' => $id, 'name' => 'Test', 'email' => "account$id@example.test", 'role' => $role, 'isactive' => 1, 'isblocked' => 0]);
        }
        // Thursday evening in New York. This week began Monday 16 November 00:00 there, which is 05:00 UTC.
        Carbon::setTestNow(Carbon::parse('2026-11-19 22:00:00', 'America/New_York'));
    }

    private function payout(int $driver, float $amount, int $withdraw, string $created): void
    {
        $this->row('payouts_driver', ['driver_id' => $driver, 'order_id' => 1, 'amount' => $amount, 'withdraw' => $withdraw, 'created' => $created]);
    }

    private function earnings(int $accountId)
    {
        return $this->actingAs(User::findOrFail($accountId), 'api')->getJson('/api/driver/earnings');
    }

    public function test_a_driver_sees_totals_and_each_week_with_what_was_earned_and_paid(): void
    {
        // Fifty dollars shared over 22 stops: seven completed last week (not paid yet), fifteen this week (paid).
        foreach (array_merge(array_fill(0, 6, 2.28), [2.27]) as $share) {
            $this->payout(3, $share, 0, '2026-11-14 15:00:00');
        }
        foreach (array_fill(0, 15, 2.27) as $share) {
            $this->payout(3, $share, 1, '2026-11-18 15:00:00');
        }
        $this->payout(3, -1, 0, '2026-11-19 20:00:00');     // completed today, no rate yet
        $this->payout(3, 1, 0, '2026-11-16 04:59:59');      // Sunday 23:59:59 in New York: last week
        $this->payout(3, 10, 1, '2026-08-05 15:00:00');     // a week in August
        $this->payout(3, 5, 1, '2025-01-08 15:00:00');      // older than the history, still part of the totals
        $this->payout(4, 99, 0, '2026-11-18 15:00:00');     // another driver

        $answer = $this->earnings(3)->assertOk()->json();

        $this->assertEquals(['earned' => 66.0, 'paid' => 49.05, 'unpaid' => 16.95, 'pending' => 1, 'this_week' => 34.05], array_diff_key($answer, ['weeks' => 0]));
        $this->assertEquals([
            ['from' => '2026-11-16', 'to' => '2026-11-22', 'stops' => 16, 'earned' => 34.05, 'paid' => 34.05, 'unpaid' => 0, 'pending' => 1],
            ['from' => '2026-11-09', 'to' => '2026-11-15', 'stops' => 8, 'earned' => 16.95, 'paid' => 0, 'unpaid' => 16.95, 'pending' => 0],
            ['from' => '2026-08-03', 'to' => '2026-08-09', 'stops' => 1, 'earned' => 10, 'paid' => 10, 'unpaid' => 0, 'pending' => 0],
        ], $answer['weeks']);
        // The wallet is the unpaid total.
        $this->assertEquals(16.95, $this->actingAs(User::findOrFail(3), 'api')->getJson('/api/driver/payouts')->json('amount'));
    }

    public function test_only_the_latest_twelve_weeks_with_stops_are_listed_and_a_week_without_work_is_zero(): void
    {
        // One stop in each of fourteen earlier weeks, none this week.
        for ($weeksAgo = 1; $weeksAgo <= 14; $weeksAgo++) {
            $this->payout(3, 3, 1, Carbon::parse('2026-11-18 15:00:00', 'UTC')->subWeeks($weeksAgo)->toDateTimeString());
        }

        $answer = $this->earnings(3)->assertOk()->json();

        $this->assertCount(12, $answer['weeks']);
        $this->assertSame(['2026-11-09', '2026-08-24'], [$answer['weeks'][0]['from'], $answer['weeks'][11]['from']]);
        $this->assertEquals([42.0, 0], [$answer['earned'], $answer['this_week']]);
    }

    public function test_a_driver_without_stops_gets_zeros_and_patients_are_refused(): void
    {
        $this->assertEquals(['earned' => 0, 'paid' => 0, 'unpaid' => 0, 'pending' => 0, 'this_week' => 0, 'weeks' => []], $this->earnings(4)->assertOk()->json());
        $this->earnings(5)->assertForbidden();
    }
}
