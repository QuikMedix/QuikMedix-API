<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\Support\InsertsRows;
use Tests\TestCase;

/**
 * The payroll page lists the drivers who completed stops in a week with what they earned, and
 * marking a week paid takes it out of the wallet total in the driver's app.
 */
class PayrollTest extends TestCase
{
    use InsertsRows;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('setex')->andReturn(true);
        foreach ([1 => ['Root', 'superadmin'], 3 => ['Dee', 'driver'], 4 => ['Sam', 'driver'], 5 => ['Ann', 'driver'], 6 => ['Med', 'medic']] as $id => [$name, $role]) {
            $this->row('users', ['id' => $id, 'name' => $name, 'last_name' => 'Test', 'email' => "$name@example.test", 'role' => $role, 'isactive' => 1, 'isblocked' => 0]);
        }
        // Wednesday noon in New York. That week runs from Monday 16 November 00:00 New York, which is 05:00 UTC.
        Carbon::setTestNow(Carbon::parse('2026-11-18 12:00:00', 'America/New_York'));
        $payouts = [
            [3, 100, 0, '2026-11-16 04:59:59'],  // Sunday 23:59:59 in New York: the week before
            [3, 10.25, 0, '2026-11-16 05:00:00'],
            [3, 5.5, 0, '2026-11-18 15:00:00'],
            [3, -1, 0, '2026-11-19 15:00:00'],   // a stop on a route with no rate yet
            [4, -1, 0, '2026-11-17 15:00:00'],
            [4, -1, 0, '2026-11-17 16:00:00'],
            [5, 20, 1, '2026-11-20 15:00:00'],   // already paid
            [5, 30, 0, '2026-11-23 05:00:00'],   // Monday 00:00 in New York: the week after
        ];
        foreach ($payouts as [$driver, $amount, $withdraw, $created]) {
            $this->row('payouts_driver', ['driver_id' => $driver, 'order_id' => 0, 'amount' => $amount, 'withdraw' => $withdraw, 'created' => $created]);
        }
    }

    private function asAdmin(): static
    {
        return $this->actingAs(User::findOrFail(1));
    }

    /** @return array<string, array{int, float, int, int}> name => [stops, total, awaiting a rate, unpaid stops] */
    private function shown(string $query = ''): array
    {
        return $this->asAdmin()->get('/payroll'.$query)->assertOk()->viewData('drivers')
            ->mapWithKeys(fn ($driver) => [$driver->name => [(int) $driver->stops, round((float) $driver->total, 2), (int) $driver->awaiting_rate, (int) $driver->unpaid_stops]])->all();
    }

    public function test_the_page_lists_this_weeks_drivers_with_their_stops_totals_and_status(): void
    {
        $this->assertSame(['Ann' => [1, 20.0, 0, 0], 'Dee' => [3, 15.75, 1, 2], 'Sam' => [2, 0.0, 2, 0]], $this->shown());

        $this->asAdmin()->get('/payroll')
            ->assertSeeInOrder(['Working drivers', '3', 'Completed stops', '6', '11.16.2026 - 11.22.2026'])
            ->assertSeeInOrder(['Ann Test', '$20.00', 'Paid', 'Dee Test', '$15.75', '1 stop', 'Not paid', 'Mark paid', 'Sam Test', '$0.00', '2 stops', 'No rate yet'])
            ->assertSee('/routes-list/driver/4', false)
            ->assertSee('/payroll?week=2026-11-09', false)
            ->assertSee('/payroll?week=2026-11-23', false)
            ->assertDontSee('Rustem Mambetov');
    }

    public function test_other_weeks_are_reached_by_any_date_in_them(): void
    {
        $this->assertSame(['Dee' => [1, 100.0, 0, 1]], $this->shown('?week=2026-11-15'));
        $this->assertSame(['Ann' => [1, 30.0, 0, 1]], $this->shown('?week=2026-11-29'));
        $this->assertSame([], $this->shown('?week=2026-01-07'));
        $this->asAdmin()->get('/payroll?week=2026-01-07')->assertSee('No driver completed a stop in this week.');
        // A week that is not a date is this week.
        $this->assertCount(3, $this->shown('?week=next'));
    }

    public function test_marking_a_week_paid_takes_it_out_of_the_drivers_wallet_and_can_be_undone(): void
    {
        $wallet = fn () => $this->actingAs(User::findOrFail(3), 'api')->getJson('/api/driver/payouts')->assertOk()->json();
        $this->assertSame(['amount' => 115.75, 'pending' => 1], $wallet());

        $this->asAdmin()->post('/payroll', ['driver_id' => 3, 'week' => '2026-11-18', 'paid' => 1])
            ->assertRedirect('/payroll?week=2026-11-16')->assertSessionHas('success', "Marked 2 stops paid. They no longer count in the driver's wallet.");

        // The two rated stops of this week are paid; the week before and the stop without a rate are untouched.
        $this->assertSame([[100.0, 0], [10.25, 1], [5.5, 1], [-1.0, 0]], DB::table('payouts_driver')->where('driver_id', 3)->orderBy('id')->get()->map(fn ($p) => [(float) $p->amount, (int) $p->withdraw])->all());
        $this->assertSame(['amount' => 100, 'pending' => 1], $wallet());
        $this->assertSame([3, 15.75, 1, 0], $this->shown()['Dee']);

        $this->asAdmin()->post('/payroll', ['driver_id' => 3, 'week' => '2026-11-16', 'paid' => 1])->assertSessionHas('error', 'Nothing to change for that driver in this week.');
        $this->asAdmin()->post('/payroll', ['driver_id' => 3, 'week' => '2026-11-16', 'paid' => 0])->assertSessionHas('success', "Marked 2 stops not paid. They count in the driver's wallet again.");
        $this->assertSame(['amount' => 115.75, 'pending' => 1], $wallet());
    }

    public function test_only_admin_staff_can_open_or_change_payroll(): void
    {
        $this->post('/payroll', ['driver_id' => 3, 'week' => '2026-11-16', 'paid' => 1])->assertRedirect('/login');
        foreach ([6, 3] as $id) {
            $this->actingAs(User::findOrFail($id))->get('/payroll')->assertForbidden();
            $this->actingAs(User::findOrFail($id))->post('/payroll', ['driver_id' => 3, 'week' => '2026-11-16', 'paid' => 1])->assertForbidden();
        }
        $this->asAdmin()->post('/payroll', ['driver_id' => 'x', 'week' => 'soon'])->assertSessionHasErrors(['driver_id', 'week', 'paid']);

        $this->assertSame(1, DB::table('payouts_driver')->where('withdraw', 1)->count());
    }
}
