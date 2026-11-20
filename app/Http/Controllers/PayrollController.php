<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersPages;
use App\Models\DriverPayout;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * What each driver earned in a week, from the payouts recorded as stops are completed, and
 * whether it has been paid. A payout marked paid leaves the wallet total in the driver's app.
 */
class PayrollController extends Controller
{
    use RendersPages;

    public function index(Request $request)
    {
        Gate::authorize('admin');
        $week = $this->week($request->query('week'));
        $marker = DriverPayout::AWAITING_RATE;

        $drivers = $this->inWeek($week)
            ->leftJoin('users', 'users.id', '=', 'payouts_driver.driver_id')
            ->groupBy('payouts_driver.driver_id', 'users.name', 'users.last_name')
            ->orderBy('users.name')->orderBy('payouts_driver.driver_id')
            ->get([
                'payouts_driver.driver_id', 'users.name', 'users.last_name',
                DB::raw('COUNT(*) as stops'),
                DB::raw("SUM(CASE WHEN payouts_driver.amount = $marker THEN 1 ELSE 0 END) as awaiting_rate"),
                DB::raw("SUM(CASE WHEN payouts_driver.amount = $marker THEN 0 ELSE payouts_driver.amount END) as total"),
                DB::raw("SUM(CASE WHEN payouts_driver.amount <> $marker AND payouts_driver.withdraw = 0 THEN 1 ELSE 0 END) as unpaid_stops"),
            ]);

        return $this->page($request, view('payroll.index', [
            'drivers' => $drivers,
            'week' => $week,
            'title' => 'payroll', 'br1' => 'payroll', 'br2' => 'Index', 'alert' => '',
        ]));
    }

    /**
     * Marks a driver's rated payouts of one week paid, or not paid again.
     */
    public function update(Request $request): RedirectResponse
    {
        Gate::authorize('admin');
        $input = $request->validate([
            'driver_id' => ['required', 'integer'],
            'week' => ['required', 'date_format:Y-m-d'],
            'paid' => ['required', 'boolean'],
        ]);
        $week = $this->week($input['week']);
        $paid = (bool) $input['paid'];

        $changed = $this->inWeek($week)->where('driver_id', $input['driver_id'])->settled()
            ->where('withdraw', $paid ? 0 : 1)->update(['withdraw' => $paid ? 1 : 0]);

        return redirect('/payroll?week='.$week->toDateString())->with(
            $changed > 0 ? 'success' : 'error',
            $changed > 0
                ? ($paid ? "Marked $changed stops paid. They no longer count in the driver's wallet." : "Marked $changed stops not paid. They count in the driver's wallet again.")
                : 'Nothing to change for that driver in this week.'
        );
    }

    /** Monday of the week that holds the date, or of this week, in the application's time zone. */
    private function week(mixed $date): CarbonImmutable
    {
        $day = is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1
            ? CarbonImmutable::createFromFormat('!Y-m-d', $date, config('app.timezone'))
            : CarbonImmutable::now(config('app.timezone'));

        return ($day ?: CarbonImmutable::now(config('app.timezone')))->startOfWeek(CarbonImmutable::MONDAY);
    }

    /** Payouts recorded from that Monday to the next. The table's timestamps are the database's, in UTC. */
    private function inWeek(CarbonImmutable $week)
    {
        return DriverPayout::query()
            ->where('payouts_driver.created', '>=', $week->utc()->toDateTimeString())
            ->where('payouts_driver.created', '<', $week->addWeek()->utc()->toDateTimeString());
    }
}
