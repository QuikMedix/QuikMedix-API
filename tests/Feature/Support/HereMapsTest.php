<?php

namespace Tests\Feature\Support;

use App\Support\HereMaps;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HereMapsTest extends TestCase
{
    /**
     * @return array<string, array{mixed, string}>
     */
    public static function locations(): array
    {
        return [
            'rounds to five decimals' => ['40.712776543,-74.005974321', '40.71278,-74.00597'],
            'keeps short coordinates' => ['40.7,-74', '40.7,-74'],
            'tolerates a space after the comma' => ['40.7127765, -74.0059743', '40.71278,-74.00597'],
            'leaves a single value alone' => ['40.712776543', '40.712776543'],
            'keeps extra parts' => ['40.7127765,-74.0059743,12', '40.71278,-74.00597,12'],
            'empty string' => ['', ''],
            'null' => [null, ''],
            'number' => [40.7, ''],
        ];
    }

    #[DataProvider('locations')]
    public function test_rounds_stored_locations_like_the_copies_it_replaced(mixed $location, string $expected): void
    {
        $this->assertSame($expected, HereMaps::roundLocation($location));
    }

    public function test_cached_access_token_is_used_without_a_refresh(): void
    {
        Redis::shouldReceive('get')->once()->with('here_access_token')->andReturn('cached-token');
        Redis::shouldReceive('setex')->never();

        $this->assertSame('cached-token', HereMaps::accessToken());
    }
}
