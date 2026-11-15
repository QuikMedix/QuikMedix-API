<?php

namespace Tests\Feature\Console;

use App\Console\Tasks\CancelStalePickupOrders;
use App\Console\Tasks\EndExpiredTrials;
use App\Console\Tasks\FlagPharmaciesWithNegativeBalance;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Tests\Support\InsertsRows;
use Tests\TestCase;

/**
 * The scheduled tasks are classes in app/Console/Tasks. These run three of them the way the
 * scheduler does, on the migrated schema.
 */
class ScheduledTasksTest extends TestCase
{
    use InsertsRows;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
    }

    public function test_pharmacies_below_zero_are_flagged(): void
    {
        $this->row('pharmacys', ['id' => 1, 'isactive' => 1, 'isblocked' => 0, 'balance' => -5, 'balance_ban' => 0]);
        $this->row('pharmacys', ['id' => 2, 'isactive' => 1, 'isblocked' => 0, 'balance' => 10, 'balance_ban' => 0]);

        $this->runScheduled(FlagPharmaciesWithNegativeBalance::class);

        $this->assertSame([1 => 1, 2 => 0], DB::table('pharmacys')->orderBy('id')->pluck('balance_ban', 'id')->map(fn ($flag) => (int) $flag)->all());
    }

    public function test_an_ended_trial_moves_to_the_standard_plan(): void
    {
        $this->row('pharmacys', ['id' => 1, 'isactive' => 1, 'isblocked' => 0, 'plan_id' => 2, 'date_end_trial' => now()->subDay()->toDateString()]);
        $this->row('pharmacys', ['id' => 2, 'isactive' => 1, 'isblocked' => 0, 'plan_id' => 2, 'date_end_trial' => now()->addWeek()->toDateString()]);

        $this->runScheduled(EndExpiredTrials::class);

        $this->assertSame([1 => 1, 2 => 2], DB::table('pharmacys')->orderBy('id')->pluck('plan_id', 'id')->map(fn ($plan) => (int) $plan)->all());
        $this->assertNull(DB::table('pharmacys')->where('id', 1)->value('date_end_trial'));
    }

    public function test_orders_waiting_for_pickup_for_over_a_week_are_cancelled(): void
    {
        foreach ([1, 5] as $status) {
            $this->row('statuses', ['id' => $status]);
        }
        $this->row('delivery_methods', ['id' => 1]);
        $this->row('delivery_times', ['id' => 1]);
        $order = ['statuse_id' => 1, 'delivery_method_id' => 1, 'delivery_time_id' => 1];
        $this->row('orders', ['id' => 1, 'created' => now()->subDays(9)->toDateTimeString()] + $order);
        $this->row('orders', ['id' => 2, 'created' => now()->toDateTimeString()] + $order);

        $this->runScheduled(CancelStalePickupOrders::class);

        $this->assertSame([1 => 5, 2 => 1], DB::table('orders')->orderBy('id')->pluck('statuse_id', 'id')->map(fn ($status) => (int) $status)->all());
    }

    /**
     * @param  class-string  $task
     */
    private function runScheduled(string $task): void
    {
        $event = collect($this->app->make(Schedule::class)->events())->first(fn ($event): bool => $event->getSummaryForDisplay() === $task);
        $this->assertNotNull($event, "$task is scheduled");

        $event->run($this->app);
    }
}
