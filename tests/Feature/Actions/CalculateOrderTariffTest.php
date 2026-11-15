<?php

namespace Tests\Feature\Actions;

use App\Actions\CalculateOrderTariff;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\InsertsRows;
use Tests\TestCase;

/**
 * The delivery price of an order. The area lookup is MySQL's ST_CONTAINS; here an area "contains"
 * a point when its stored polygon text is the point's latitude.
 */
class CalculateOrderTariffTest extends TestCase
{
    use InsertsRows;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        $pdo = DB::connection()->getPdo();
        $pdo->sqliteCreateFunction('POINT', fn ($latitude, $longitude): string => (string) (float) $latitude);
        $pdo->sqliteCreateFunction('ST_CONTAINS', fn ($polygon, $point): int => (string) $polygon === (string) $point ? 1 : 0);

        $this->row('plans', ['id' => 1, 'tariff' => 5, 'tariff_area2' => 7, 'tariff_area3' => 9, 'tariff_area_more' => 20,
            'tariff_next_day' => 1, 'tariff_same_day' => 2, 'tariff_asap' => 3, 'tariff_after_hours' => 4, 'tariff_fridge' => 0.5]);
        // The pharmacy sets its own price for the first area and for same-day delivery; the rest comes from the plan.
        $this->row('pharmacys', ['id' => 1, 'plan_id' => 1, 'tariff' => 6, 'tariff_area2' => null, 'tariff_area3' => null, 'tariff_area_more' => null,
            'tariff_next_day' => null, 'tariff_same_day' => 2.5, 'tariff_asap' => null, 'tariff_after_hours' => null, 'tariff_fridge' => null]);
        foreach ([1 => '41', 2 => '42', 3 => '43'] as $type => $latitude) {
            $areaId = $this->row('area', ['polygon' => $latitude]);
            $this->row('pharmacy_areas', ['pharmacy_id' => 1, 'area_id' => $areaId, 'type' => $type]);
        }
        foreach ([11 => '41,-74', 12 => '42,-74', 13 => '43,-74', 14 => '50,-74'] as $id => $location) {
            $this->row('users', ['id' => $id, 'email' => "patient$id@example.test", 'location' => $location]);
        }
    }

    /**
     * @return array<string, array{int, array<string, mixed>, float}>
     */
    public static function orders(): array
    {
        $quikmedix = ['type_driver' => 1, 'extra_charge_driver' => 0, 'fridge' => 0];

        return [
            'first area, pharmacy price, next day' => [11, ['delivery_time_id' => 1] + $quikmedix, 6 + 1],
            'first area, pharmacy same-day price' => [11, ['delivery_time_id' => 2] + $quikmedix, 6 + 2.5],
            'second area, plan price, asap' => [12, ['delivery_time_id' => 3] + $quikmedix, 7 + 3],
            'third area, after hours, extra charge and fridge' => [13, ['delivery_time_id' => 4, 'extra_charge_driver' => 1.25, 'fridge' => 1] + $quikmedix, 9 + 4 + 1.25 + 0.5],
            'outside every area' => [14, ['delivery_time_id' => 1] + $quikmedix, 20 + 1],
            'pharmacy driver pays the area price only' => [12, ['type_driver' => 2, 'delivery_time_id' => 3, 'extra_charge_driver' => 5, 'fridge' => 1], 7],
        ];
    }

    /**
     * @param  array<string, mixed>  $order
     */
    #[DataProvider('orders')]
    public function test_the_price_follows_the_area_the_delivery_window_and_the_surcharges(int $patientId, array $order, float $expected): void
    {
        $price = app(CalculateOrderTariff::class)->handle((object) (['id' => 1, 'pharmacy_id' => 1, 'user_id' => $patientId] + $order));

        $this->assertEqualsWithDelta($expected, $price, 0.0001);
    }

    public function test_an_unknown_delivery_window_is_an_error(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        app(CalculateOrderTariff::class)->handle((object) ['id' => 1, 'pharmacy_id' => 1, 'user_id' => 11, 'type_driver' => 1, 'delivery_time_id' => 9, 'extra_charge_driver' => 0, 'fridge' => 0]);
    }
}
