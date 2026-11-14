<?php

namespace Tests\Feature\Actions;

use App\Http\Controllers\LexaAdmin;
use App\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CreatesAuthenticationSchema;
use Tests\Support\FakeHereRouting;
use Tests\TestCase;

/**
 * Driver ETAs: the route is sent to HERE in stop order from the driver's last location, and the
 * running arrival time (travel plus four minutes per stop) is stored on each stop, its orders and the driver.
 */
class RecalculateDriverEtaTest extends TestCase
{
    use CreatesAuthenticationSchema;

    private const PHARMACY = '40.71,-74.01';

    private const PATIENT = '40.72,-74.02';

    private const OFFICE = '40.73,-74.03';

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticationSchema();
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('user');
            $table->integer('isactive')->default(1);
            $table->integer('isblocked')->default(0);
            $table->integer('work_now')->default(0);
            $table->string('transport')->nullable();
            $table->string('location')->nullable();
        });
        Schema::create('locations', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('location');
        });
        Schema::create('drivers_eta', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('driver_id');
            $table->float('distance')->nullable();
            $table->integer('eta')->nullable();
            $table->string('last_location')->nullable();
        });
        Schema::create('routes_priority', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('driver_id');
            $table->integer('order_id');
            $table->string('type');
            $table->integer('type_id');
            $table->integer('priority');
            $table->integer('eta')->nullable();
        });
        foreach (['pharmacys', 'offices'] as $name) {
            Schema::create($name, function (Blueprint $table): void {
                $table->increments('id');
                $table->string('location')->nullable();
            });
        }
        Schema::create('orders', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('driver_id')->nullable();
            $table->integer('eta')->nullable();
        });

        Redis::shouldReceive('get')->with('here_access_token')->andReturn('here-token');
        FakeHereRouting::install();
    }

    protected function tearDown(): void
    {
        FakeHereRouting::uninstall();
        parent::tearDown();
    }

    /**
     * A working driver at 40.70,-74.00 with a pharmacy pickup, a patient with two orders and an office return.
     */
    private function driverWithRoute(?string $transport = null): User
    {
        $driver = User::factory()->create(['role' => 'driver', 'work_now' => 1, 'transport' => $transport]);
        $patient = User::factory()->create(['role' => 'user', 'location' => self::PATIENT]);
        DB::table('locations')->insert([
            ['user_id' => $driver->id, 'location' => '40.60,-74.60'],
            ['user_id' => $driver->id, 'location' => '40.700004,-74.000004'],
        ]);
        $pharmacy = DB::table('pharmacys')->insertGetId(['location' => self::PHARMACY]);
        $office = DB::table('offices')->insertGetId(['location' => self::OFFICE]);
        $first = DB::table('orders')->insertGetId(['driver_id' => $driver->id]);
        $second = DB::table('orders')->insertGetId(['driver_id' => $driver->id]);
        DB::table('routes_priority')->insert([
            ['driver_id' => $driver->id, 'order_id' => $first, 'type' => 'pharmacy', 'type_id' => $pharmacy, 'priority' => 1],
            ['driver_id' => $driver->id, 'order_id' => $second, 'type' => 'pharmacy', 'type_id' => $pharmacy, 'priority' => 1],
            ['driver_id' => $driver->id, 'order_id' => $first, 'type' => 'patient', 'type_id' => $patient->id, 'priority' => 2],
            ['driver_id' => $driver->id, 'order_id' => $second, 'type' => 'patient', 'type_id' => $patient->id, 'priority' => 2],
            ['driver_id' => $driver->id, 'order_id' => $first, 'type' => 'office', 'type_id' => $office, 'priority' => 3],
        ]);

        return $driver;
    }

    /**
     * @return list<int|null>
     */
    private function stopEtas(User $driver): array
    {
        return DB::table('routes_priority')->where('driver_id', $driver->id)->orderBy('id')->pluck('eta')->all();
    }

    public function test_route_is_sent_in_stop_order_and_arrival_times_are_stored(): void
    {
        $driver = $this->driverWithRoute();

        $distance = LexaAdmin::eta_calculate($driver->id);

        $this->assertCount(1, FakeHereRouting::$requests);
        $this->assertStringStartsWith('https://router.hereapi.com/v8/routes?', FakeHereRouting::$requests[0]['url']);
        $this->assertSame('Authorization: Bearer here-token', FakeHereRouting::$requests[0]['header']);
        $this->assertSame([
            'via' => [self::PHARMACY, self::PATIENT],
            'transportMode' => 'car',
            'origin' => '40.7,-74',
            'destination' => self::OFFICE,
            'return' => 'summary',
        ], FakeHereRouting::query(0));

        // Three legs of 1609 m and 6 minutes each, plus 4 minutes at every stop.
        $this->assertSame(3.0, $distance);
        $this->assertSame([10, 10, 20, 20, 30], $this->stopEtas($driver));
        $this->assertSame([20, 20], DB::table('orders')->orderBy('id')->pluck('eta')->all());
        $this->assertDatabaseHas('drivers_eta', ['driver_id' => $driver->id, 'eta' => 30, 'distance' => 3.0, 'last_location' => '40.7,-74']);
    }

    public function test_a_bicycle_driver_is_routed_by_bicycle(): void
    {
        $driver = $this->driverWithRoute('2');

        LexaAdmin::eta_calculate($driver->id);

        $this->assertSame('bicycle', FakeHereRouting::query(0)['transportMode']);
    }

    public function test_nothing_is_recalculated_until_the_driver_moves_unless_forced(): void
    {
        $driver = $this->driverWithRoute();
        DB::table('drivers_eta')->insert(['driver_id' => $driver->id, 'distance' => 9.9, 'eta' => 99, 'last_location' => '40.7,-74']);

        $this->assertFalse(LexaAdmin::eta_calculate($driver->id));
        $this->assertSame([], FakeHereRouting::$requests);
        $this->assertDatabaseHas('drivers_eta', ['driver_id' => $driver->id, 'eta' => 99]);

        $this->assertSame(3.0, LexaAdmin::eta_calculate($driver->id, true));
        $this->assertCount(1, FakeHereRouting::$requests);
        $this->assertSame(1, DB::table('drivers_eta')->count());
        $this->assertDatabaseHas('drivers_eta', ['driver_id' => $driver->id, 'eta' => 30, 'distance' => 3.0]);

        DB::table('locations')->insert(['user_id' => $driver->id, 'location' => '40.75,-74.05']);
        $this->assertSame(3.0, LexaAdmin::eta_calculate($driver->id));
        $this->assertSame('40.75,-74.05', FakeHereRouting::query(1)['origin']);
        $this->assertDatabaseHas('drivers_eta', ['driver_id' => $driver->id, 'last_location' => '40.75,-74.05']);
    }

    public function test_stops_without_a_location_are_left_out_of_the_route(): void
    {
        $driver = $this->driverWithRoute();
        DB::table('pharmacys')->update(['location' => null]);

        LexaAdmin::eta_calculate($driver->id);

        $this->assertSame([self::PATIENT], FakeHereRouting::query(0)['via']);
        $this->assertSame([null, null, 10, 10, 20], $this->stopEtas($driver));
        $this->assertDatabaseHas('drivers_eta', ['driver_id' => $driver->id, 'eta' => 20, 'distance' => 2.0]);
    }

    public function test_long_routes_are_requested_fifty_stops_at_a_time_from_the_driver(): void
    {
        $driver = User::factory()->create(['role' => 'driver', 'work_now' => 1]);
        DB::table('locations')->insert(['user_id' => $driver->id, 'location' => '40.70,-74.00']);
        for ($stop = 1; $stop <= 52; $stop++) {
            $pharmacy = DB::table('pharmacys')->insertGetId(['location' => '41.'.str_pad((string) $stop, 2, '0', STR_PAD_LEFT).',-74.5']);
            DB::table('routes_priority')->insert(['driver_id' => $driver->id, 'order_id' => $stop, 'type' => 'pharmacy', 'type_id' => $pharmacy, 'priority' => $stop]);
        }

        $distance = LexaAdmin::eta_calculate($driver->id);

        $this->assertCount(2, FakeHereRouting::$requests);
        $first = FakeHereRouting::query(0);
        $second = FakeHereRouting::query(1);
        $this->assertSame(['40.7,-74', 49, '41.01,-74.5', '41.49,-74.5', '41.5,-74.5'], [$first['origin'], count($first['via']), $first['via'][0], $first['via'][48], $first['destination']]);
        $this->assertSame(['40.7,-74', ['41.51,-74.5'], '41.52,-74.5'], [$second['origin'], $second['via'], $second['destination']]);
        $this->assertSame(52.0, $distance);
        $this->assertSame(range(10, 520, 10), $this->stopEtas($driver));
    }

    public function test_unknown_driver_or_driver_without_a_location_or_route(): void
    {
        $this->assertFalse(LexaAdmin::eta_calculate(999));
        $this->assertFalse(LexaAdmin::eta_calculate(null));

        $driver = User::factory()->create(['role' => 'driver', 'work_now' => 1]);
        $this->assertFalse(LexaAdmin::eta_calculate($driver->id));
        $this->assertDatabaseCount('drivers_eta', 0);

        // A located driver with no stops is recorded with a zero ETA and no routing request.
        DB::table('locations')->insert(['user_id' => $driver->id, 'location' => '40.70,-74.00']);
        $this->assertSame(0.0, LexaAdmin::eta_calculate($driver->id));
        $this->assertSame([], FakeHereRouting::$requests);
        $this->assertDatabaseHas('drivers_eta', ['driver_id' => $driver->id, 'eta' => 0, 'last_location' => '40.7,-74']);
    }

    /**
     * Runs the scheduled task that recalculates ETAs every fifteen minutes.
     */
    private function runScheduledEtaTask(): void
    {
        $tasks = array_values(array_filter(
            $this->app->make(Schedule::class)->events(),
            fn ($event): bool => $event->expression === '*/15 * * * *',
        ));
        $this->assertCount(1, $tasks, 'Exactly one task runs every fifteen minutes: the ETA recalculation.');

        $tasks[0]->run($this->app);
    }

    public function test_the_scheduled_task_routes_each_working_driver_by_their_own_transport(): void
    {
        $cyclist = $this->driverWithRoute('2');
        $motorist = $this->driverWithRoute('1');
        $offShift = $this->driverWithRoute('2');
        $blocked = $this->driverWithRoute('2');
        User::whereKey($offShift->id)->update(['work_now' => 0]);
        User::whereKey($blocked->id)->update(['isblocked' => 1]);
        // A working driver without stops is not part of the scheduled run.
        $idle = User::factory()->create(['role' => 'driver', 'work_now' => 1]);
        DB::table('locations')->insert(['user_id' => $idle->id, 'location' => '40.70,-74.00']);

        $this->runScheduledEtaTask();

        $this->assertCount(2, FakeHereRouting::$requests);
        $this->assertSame(['bicycle', 'car'], [FakeHereRouting::query(0)['transportMode'], FakeHereRouting::query(1)['transportMode']]);
        $this->assertSame([10, 10, 20, 20, 30], $this->stopEtas($cyclist));
        $this->assertSame([10, 10, 20, 20, 30], $this->stopEtas($motorist));
        $this->assertSame([null, null, null, null, null], $this->stopEtas($offShift));
        $this->assertSame([$cyclist->id, $motorist->id], DB::table('drivers_eta')->orderBy('driver_id')->pluck('driver_id')->all());
    }

    public function test_the_scheduled_task_skips_unlocated_drivers_and_continues_after_a_failed_request(): void
    {
        $unlocated = $this->driverWithRoute();
        $unreachable = $this->driverWithRoute();
        $working = $this->driverWithRoute('2');
        DB::table('locations')->where('user_id', $unlocated->id)->delete();
        DB::table('locations')->insert(['user_id' => $unreachable->id, 'location' => '41.00,-75.00']);
        FakeHereRouting::$unreachableOrigin = '41,-75';

        $this->runScheduledEtaTask();

        $this->assertSame(['41,-75', '40.7,-74'], [FakeHereRouting::query(0)['origin'], FakeHereRouting::query(1)['origin']]);
        $this->assertSame([10, 10, 20, 20, 30], $this->stopEtas($working));
        $this->assertSame([null, null, null, null, null], $this->stopEtas($unreachable));
        $this->assertSame([null, null, null, null, null], $this->stopEtas($unlocated));
        $this->assertSame([$working->id], DB::table('drivers_eta')->pluck('driver_id')->all());
    }
}
