<?php

namespace Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Support\InsertsRows;
use Tests\TestCase;

class DataAgeCommandTest extends TestCase
{
    use InsertsRows;

    public function test_it_counts_rows_older_than_the_given_months_and_changes_nothing(): void
    {
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        Carbon::setTestNow(Carbon::parse('2026-11-19 12:00:00', 'UTC'));
        foreach (['2023-10-02 10:00:00', '2026-09-19 11:59:59', '2026-09-19 12:00:00', '2026-11-18 10:00:00'] as $created) {
            $this->row('payouts_driver', ['driver_id' => 3, 'order_id' => 1, 'amount' => 5, 'created' => $created]);
        }
        $this->row('pharmacys', ['id' => 1, 'name' => 'Corner Pharmacy']);

        Artisan::call('quikmedix:data-age');
        $output = Artisan::output();

        $this->assertStringContainsString('Rows made before 2026-09-19 12:00:00 UTC (2 months ago). Nothing is changed.', $output);
        $this->assertMatchesRegularExpression('/\| payouts_driver\s+\| 4\s+\| 2\s+\| 2023-10-02 10:00:00\s+\| created\s+\|/', $output);
        // Tables without rows are left out.
        $this->assertStringNotContainsString('| invoices ', $output);

        Artisan::call('quikmedix:data-age', ['--months' => 48]);
        $this->assertMatchesRegularExpression('/\| payouts_driver\s+\| 4\s+\| 0\s+\|/', Artisan::output());

        $this->assertSame(4, DB::table('payouts_driver')->count());
    }
}
