<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\Support\InsertsRows;
use Tests\TestCase;

/**
 * Pages that used to show sample markup from the theme (made-up names, dates, totals and "XXX")
 * show what is in the database, or say that there is nothing.
 */
class RealFiguresTest extends TestCase
{
    use InsertsRows;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('setex')->andReturn(true);
        $this->row('users', ['id' => 1, 'name' => 'Rita', 'last_name' => 'Root', 'email' => 'root@example.test', 'role' => 'superadmin', 'isactive' => 1, 'isblocked' => 0]);
        $this->row('users', ['id' => 3, 'name' => 'Dee', 'last_name' => 'Driver', 'email' => 'dee@example.test', 'role' => 'driver', 'isactive' => 1, 'isblocked' => 0, 'work_now' => '1', 'os' => 2]);
        $this->row('users', ['id' => 4, 'name' => 'Sam', 'last_name' => 'Driver', 'email' => 'sam@example.test', 'role' => 'driver', 'isactive' => 0, 'isblocked' => 0, 'work_now' => '0']);
        $this->row('users', ['id' => 5, 'name' => 'Pat', 'last_name' => 'Patient', 'email' => 'pat@example.test', 'role' => 'user', 'isactive' => 1, 'isblocked' => 0, 'os' => 1]);
        $this->row('users', ['id' => 6, 'name' => 'Pia', 'last_name' => 'Patient', 'email' => 'pia@example.test', 'role' => 'user', 'isactive' => 1, 'isblocked' => 0]);
        $this->row('pharmacys', ['id' => 1, 'name' => 'Corner Pharmacy', 'isactive' => 1, 'isblocked' => 0]);
        $this->row('pharmacys', ['id' => 2, 'name' => 'Closed Pharmacy', 'isactive' => 0, 'isblocked' => 0]);
        $this->actingAs(User::findOrFail(1));
    }

    public function test_a_drivers_profile_shows_their_own_stops_earnings_and_rating(): void
    {
        foreach ([[10.25, 0, '2026-11-17 15:00:00'], [5.5, 0, '2026-11-18 15:00:00'], [20, 1, '2026-11-18 16:00:00'], [-1, 0, '2026-11-19 20:30:00']] as [$amount, $withdraw, $created]) {
            $this->row('payouts_driver', ['driver_id' => 3, 'order_id' => 70, 'amount' => $amount, 'withdraw' => $withdraw, 'created' => $created]);
        }
        $this->row('statuses', ['id' => 4]);
        $this->row('delivery_methods', ['id' => 1]);
        $this->row('delivery_times', ['id' => 1]);
        foreach ([[71, 5], [72, 4], [73, null]] as [$id, $rating]) {
            $this->row('orders', ['id' => $id, 'pharmacy_id' => 1, 'user_id' => 5, 'driver_id' => 3, 'statuse_id' => 4, 'delivery_method_id' => 1, 'delivery_time_id' => 1, 'rating' => $rating]);
        }

        $page = $this->get('/drivers/3/profile')->assertOk();

        $page->assertSeeInOrder(['Driver rating', '4.5 from 2 ratings', 'Completed stops: 4', 'Waiting for a rate: 1', 'Unpaid earnings: $15.75'])
            // Newest first, in New York time; the stop without a rate says so.
            ->assertSeeInOrder(['Latest completed stops', '11.19.2026 3:30 PM', 'Waiting for a rate', '11.18.2026 11:00 AM', '$20.00', 'Yes', '$5.50', 'No', '$10.25'])
            ->assertDontSee('04.28.2023')->assertDontSee('$180.00')->assertDontSee('Salary: $0.00')->assertDontSee('Route history');
        // Five stars drawn, four and a half rounded to five filled.
        $this->assertSame(5, substr_count($page->getContent(), 'class="mdi mdi-star"'));

        $this->get('/drivers/4/profile')->assertOk()->assertSeeInOrder(['No ratings yet', 'Completed stops: 0', 'Unpaid earnings: $0.00', 'No completed stops yet.']);
    }

    public function test_the_report_pages_count_real_pharmacies_customers_and_drivers(): void
    {
        $this->get('/reports')->assertOk()->assertDontSee('XXX')
            ->assertSeeInOrder(['Pharmacies', 'Total: ', '<mark>2</mark>', 'Active: ', '<mark>1</mark>', 'Customers', '<mark>2</mark>', 'With application: ', '<mark>1</mark>', 'Drivers', '<mark>2</mark>', '<mark>1</mark>', 'Billing', 'Apps analytics'], false)
            ->assertDontSee('Send sms/push');

        $this->get('/reports/drivers')->assertOk()->assertDontSee('XXX')->assertDontSee('Queens')
            ->assertSeeInOrder(['Total: ', '<mark>2</mark>', 'Active: ', '<mark>1</mark>', 'On shift now: ', '<mark>1</mark>', 'With application: ', '<mark>1</mark>'], false);
    }

    public function test_report_pages_are_for_admin_staff_like_their_menu_entry(): void
    {
        foreach ([3, 5] as $id) {
            foreach (['/reports', '/reports/billing', '/reports/drivers', '/reports/customers', '/reports/invoices', '/reports/apps', '/reports/pharmacies'] as $page) {
                $this->actingAs(User::findOrFail($id))->get($page)->assertForbidden();
            }
        }
    }

    public function test_the_dashboard_adds_up_what_drivers_earned_today_this_week_and_this_month(): void
    {
        // Thursday 19 November 2026, noon in New York.
        Carbon::setTestNow(Carbon::parse('2026-11-19 12:00:00', 'America/New_York'));
        DB::connection()->getPdo()->sqliteCreateFunction('DATE_FORMAT', fn ($date, $format): string => date('m-Y', strtotime((string) $date)));
        $earned = [
            [8, '2026-11-19 16:00:00'],    // today
            [-1, '2026-11-19 16:30:00'],   // today, no rate yet: not money
            [6, '2026-11-18 16:00:00'],    // yesterday, this week
            [5, '2026-11-12 16:00:00'],    // last week, this month
            [4, '2026-10-20 16:00:00'],    // last month
            [3, '2026-11-01 04:30:00'],    // 00:30 on 1 November in New York: this month
        ];
        foreach ($earned as [$amount, $created]) {
            $this->row('payouts_driver', ['driver_id' => 3, 'order_id' => 70, 'amount' => $amount, 'withdraw' => 0, 'created' => $created]);
        }

        $page = $this->get('/')->assertOk();

        $this->assertSame(['today' => 8.0, 'today_prev' => 6.0, 'week' => 14.0, 'week_prev' => 5.0, 'month' => 22.0, 'month_prev' => 4.0], $page->viewData('payouts'));
        $page->assertSeeInOrder(['Payouts', '$8.00', '$6.00', 'Today', '$14.00', '$5.00', 'Week', '$22.00', '$4.00', 'Month'])->assertDontSee('$56241');
    }

    public function test_pages_with_nothing_behind_them_say_so_instead_of_showing_samples(): void
    {
        $this->get('/ads')->assertOk()->assertSee('c-soon.jpg', false)->assertDontSee('Views:')->assertDontSee('Clicks:');
        $this->get('/news')->assertOk()->assertDontSee('Lorem Ipsum')->assertDontSee('05.10.2020')->assertDontSee('19.03.2023');
    }

    public function test_no_template_carries_the_themes_sample_data(): void
    {
        $samples = ['Lorem Ipsum', '<mark>XXX</mark>', 'Shahzod', 'Rustem Mambetov', '$56241', '04.28.2023', '10.02.2023', 'Sinai Rx Pharmacy'];
        $found = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'), \FilesystemIterator::SKIP_DOTS)) as $file) {
            $source = (string) file_get_contents($file->getPathname());
            foreach ($samples as $sample) {
                if (str_contains($source, $sample)) {
                    $found[] = $file->getFilename().': '.$sample;
                }
            }
        }

        $this->assertSame([], $found);
    }
}
