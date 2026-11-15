<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Support\InsertsRows;
use Tests\TestCase;

/**
 * The driver app scans each bag at the patient's door through two endpoints that differ only in
 * how the code arrives; both record the scan the same way.
 */
class PatientScanTest extends TestCase
{
    use InsertsRows;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        $this->row('statuses', ['id' => 3]);
        $this->row('delivery_methods', ['id' => 1]);
        $this->row('delivery_times', ['id' => 1]);
        $this->row('users', ['id' => 8, 'email' => 'driver@example.test', 'role' => 'driver', 'isactive' => 1, 'isblocked' => 0]);
        $this->row('orders', ['id' => 30, 'user_id' => 12, 'driver_id' => 8, 'count_bags' => 2, 'statuse_id' => 3, 'delivery_method_id' => 1, 'delivery_time_id' => 1]);
        $this->actingAs(User::findOrFail(8), 'api');
    }

    public function test_each_bag_is_recorded_once_through_the_first_endpoint(): void
    {
        $this->postJson('/api/orders/drop_off_qr', ['order_id' => '30_1'])->assertOk()->assertExactJson(['order_id' => 30, 'count_bags' => 2, 'count_scanned' => 1]);
        $this->postJson('/api/orders/drop_off_qr', ['order_id' => '30_1'])->assertNotFound()->assertJsonPath('message', 'This bag has already been scanned, please scan the next bag for this order.');
        $this->postJson('/api/orders/drop_off_qr', ['order_id' => '30_2'])->assertOk()->assertJsonPath('count_scanned', 2);
        $this->postJson('/api/orders/drop_off_qr', ['order_id' => '30_3'])->assertOk()->assertExactJson(['order_id' => 30, 'count_bags' => 2, 'count_scanned' => 2]);

        $this->assertSame([1, 2], DB::table('packages_transitions')->where('order_id', 30)->orderBy('bag')->pluck('bag')->map(fn ($bag) => (int) $bag)->all());
    }

    public function test_the_second_endpoint_also_requires_the_code_to_belong_to_the_order_on_screen(): void
    {
        $this->postJson('/api/orders/drop_off_qr2', ['order_id' => 30, 'code' => '31_1'])->assertNotFound()->assertJsonPath('message', 'Order with such a QR code was not found or order was alredy picked up.');
        $this->assertSame(0, DB::table('packages_transitions')->count());

        $this->postJson('/api/orders/drop_off_qr2', ['order_id' => 30, 'code' => '30_1'])->assertOk()->assertExactJson(['order_id' => 30, 'count_bags' => 2, 'count_scanned' => 1]);
        $this->postJson('/api/orders/drop_off_qr2', ['order_id' => 30])->assertForbidden()->assertJsonPath('message', 'Fields is not be empty.');
    }

    public function test_another_drivers_order_is_not_found(): void
    {
        DB::table('orders')->where('id', 30)->update(['driver_id' => 99]);

        $this->postJson('/api/orders/drop_off_qr', ['order_id' => '30_1'])->assertNotFound();
        $this->assertSame(0, DB::table('packages_transitions')->count());
    }
}
