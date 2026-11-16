<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\Support\InsertsRows;
use Tests\TestCase;

/**
 * The staff dashboard's figures are limited to the person's zone and cached for a few minutes.
 * The cache used to have one key for everyone, so one zone could be shown another's numbers.
 */
class DashboardCacheTest extends TestCase
{
    use InsertsRows;

    public function test_each_zone_keeps_its_own_cached_dashboard(): void
    {
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        $this->row('users', ['id' => 1, 'email' => 'root@example.test', 'role' => 'superadmin', 'isactive' => 1, 'isblocked' => 0, 'zone_id' => null]);
        $this->row('users', ['id' => 2, 'email' => 'zone@example.test', 'role' => 'superadmin', 'isactive' => 1, 'isblocked' => 0, 'zone_id' => 3]);

        // The dashboard groups orders by month with MySQL's DATE_FORMAT.
        DB::connection()->getPdo()->sqliteCreateFunction('DATE_FORMAT', fn ($date, $format): string => date('m-Y', strtotime((string) $date)));

        $keys = [];
        Redis::shouldReceive('get')->andReturnUsing(function (string $key) use (&$keys) {
            $keys[] = "get $key";

            return null;
        });
        Redis::shouldReceive('setex')->andReturnUsing(function (string $key) use (&$keys) {
            $keys[] = "setex $key";

            return true;
        });

        $this->actingAs(User::findOrFail(1))->get('/')->assertOk();
        // The zone's own queries use MySQL's area functions, so on SQLite this request stops after reading the cache.
        $this->actingAs(User::findOrFail(2))->get('/');

        $dashboard = array_values(array_filter($keys, fn (string $key): bool => str_contains($key, ':admin_dash')));
        $this->assertSame(['get localhost:admin_dash:all', 'setex localhost:admin_dash:all', 'get localhost:admin_dash:3'], array_slice($dashboard, 0, 3));
        $this->assertNotContains('setex localhost:admin_dash:all', array_slice($dashboard, 2));
    }
}
