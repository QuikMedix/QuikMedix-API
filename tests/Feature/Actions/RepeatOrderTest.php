<?php

namespace Tests\Feature\Actions;

use App\Actions\RepeatOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Support\InsertsRows;
use Tests\TestCase;

class RepeatOrderTest extends TestCase
{
    use InsertsRows;

    public function test_the_copy_is_a_new_order_for_tomorrow_with_the_same_prescriptions(): void
    {
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        foreach ([1, 4] as $status) {
            $this->row('statuses', ['id' => $status]);
        }
        $this->row('delivery_methods', ['id' => 1]);
        $this->row('delivery_times', ['id' => 2]);
        $this->row('users', ['id' => 9, 'email' => 'medic@example.test', 'role' => 'medic']);
        $this->row('orders', ['id' => 40, 'pharmacy_id' => 3, 'user_id' => 12, 'medic_id' => 1, 'driver_id' => 5, 'statuse_id' => 4, 'statuse_copay' => 2,
            'delivery_method_id' => 1, 'delivery_time_id' => 2, 'copay' => 7.5, 'tariff' => 11, 'rating' => 5, 'finish' => '2026-11-01 10:00:00',
            'drop_off_photo' => 'a.jpg', 'signature_photo' => 'b.jpg', 'invoice_payed' => 1, 'eta' => '10:30', 'delivery_date' => '2026-11-01']);
        $this->row('rxs', ['order_id' => 40, 'rx_id' => 'RX-1']);
        $this->row('rxs', ['order_id' => 40, 'rx_id' => 'RX-2']);
        $this->actingAs(User::findOrFail(9));
        $this->travelTo('2026-11-15 09:00:00');

        [$pharmacyId, $newId] = app(RepeatOrder::class)->handle(40);

        $copy = DB::table('orders')->where('id', $newId)->first();
        $this->assertNotSame(40, $newId);
        $this->assertSame(3, (int) $pharmacyId);
        $this->assertSame([3, 12, 9, 1, 1, 5], [(int) $copy->pharmacy_id, (int) $copy->user_id, (int) $copy->medic_id, (int) $copy->statuse_id, (int) $copy->statuse_copay, (int) $copy->driver_id]);
        $this->assertEqualsWithDelta(7.5, (float) $copy->copay, 0.001);
        foreach (['finish', 'rating', 'tariff', 'drop_off_photo', 'signature_photo', 'invoice_payed', 'eta'] as $cleared) {
            $this->assertNull($copy->{$cleared}, $cleared);
        }
        $this->assertSame(date('Y-m-d', strtotime('+1 day')), substr((string) $copy->delivery_date, 0, 10));
        $this->assertSame(['RX-1', 'RX-2'], DB::table('rxs')->where('order_id', $newId)->orderBy('id')->pluck('rx_id')->all());
        $this->assertSame(2, DB::table('rxs')->where('order_id', 40)->count());
        $this->assertSame(4, (int) DB::table('orders')->where('id', 40)->value('statuse_id'));
    }
}
