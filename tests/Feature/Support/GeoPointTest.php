<?php

namespace Tests\Feature\Support;

use App\Support\GeoPoint;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GeoPointTest extends TestCase
{
    public static function locations(): array
    {
        return [
            'stored location' => ['40.748545,-73.9857634', [40.748545, -73.9857634]],
            'spaces around parts' => [' 40.7 , -74.0 ', [40.7, -74.0]],
            'missing location' => [null, [0.0, 0.0]],
            'injection attempt' => ['1,1)) OR 1=1 -- ', [0.0, 0.0]],
            'three parts' => ['1,2,3', [0.0, 0.0]],
        ];
    }

    #[DataProvider('locations')]
    public function test_turns_a_stored_location_into_numeric_point_bindings(?string $location, array $expected): void
    {
        $this->assertSame($expected, GeoPoint::bindings($location));
    }
}
