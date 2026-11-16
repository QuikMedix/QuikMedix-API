<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Redis;
use Tests\Support\InsertsRows;
use Tests\TestCase;

/**
 * The order forms ask for the delivery tariff of the chosen customer's ZIP area.
 */
class OrderTariffLookupTest extends TestCase
{
    use InsertsRows;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('setex')->andReturn(true);
        $this->row('users', ['id' => 1, 'email' => 'root@example.test', 'role' => 'superadmin', 'isactive' => 1, 'isblocked' => 0]);
        $this->row('users', ['id' => 2, 'email' => 'pat@example.test', 'role' => 'user', 'zip' => '11201', 'isactive' => 1, 'isblocked' => 0]);
        $this->row('users', ['id' => 3, 'email' => 'far@example.test', 'role' => 'user', 'zip' => '99999', 'isactive' => 1, 'isblocked' => 0]);
        $this->row('pharmacys', ['id' => 1, 'name' => 'Corner Pharmacy', 'isactive' => 1, 'isblocked' => 0]);
        $this->row('area', ['id' => 4, 'tariff' => 7.5]);
        $this->row('area_zip', ['area_id' => 4, 'zip' => '11201']);
        $this->row('statuses', ['id' => 1]);
        $this->row('delivery_methods', ['id' => 1]);
        $this->row('delivery_times', ['id' => 1]);
        $this->row('orders', ['id' => 9, 'pharmacy_id' => 1, 'user_id' => 2, 'driver_id' => 1, 'statuse_id' => 1, 'delivery_method_id' => 1, 'delivery_time_id' => 1]);
        $this->actingAs(User::findOrFail(1));
    }

    public function test_the_new_order_and_edit_order_forms_get_the_tariff_of_the_customers_area(): void
    {
        foreach (['/orders/1/add', '/orders/1/edit/9'] as $form) {
            $this->assertEqualsWithDelta(7.5, (float) json_decode($this->post($form, ['user_id' => 2])->assertOk()->getContent())->tariff, 0.001, $form);
            // A ZIP outside every area, and a customer who does not exist, cost nothing extra.
            $this->assertSame(['message' => 'OK', 'tariff' => 0], json_decode($this->post($form, ['user_id' => 3])->getContent(), true), $form);
            $this->assertSame(['message' => 'OK', 'tariff' => 0], json_decode($this->post($form, ['user_id' => 404])->getContent(), true), $form);
        }
    }
}
