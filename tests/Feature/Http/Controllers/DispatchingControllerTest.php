<?php

namespace Tests\Feature\Http\Controllers;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesAuthenticationSchema;
use Tests\Support\FakeMapServices;
use Tests\Support\StubsDashboardLayout;
use Tests\TestCase;
use App\Support\AccessMessages;

/**
 * Dispatching: the live board of drivers on shift, the dispatcher's decisions about an
 * order's delivery confirmation, and one driver's route page.
 */
class DispatchingControllerTest extends TestCase
{
    use CreatesAuthenticationSchema;
    use StubsDashboardLayout;

    private User $driver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticationSchema();
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('user');
            $table->integer('isactive')->default(1);
            $table->integer('isblocked')->default(0);
            $table->integer('pharmacy_id')->nullable();
            $table->integer('zone_id')->nullable();
            $table->integer('work_now')->default(0);
            $table->string('last_name')->nullable();
            $table->string('image')->nullable();
            $table->string('transport')->nullable();
            $table->integer('primary_address')->default(1);
            foreach (['', '2', '3'] as $suffix) {
                foreach (['address', 'apartment', 'zip', 'location'] as $column) {
                    $table->string($column.$suffix)->nullable();
                }
            }
        });
        Schema::create('pharmacys', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('location')->nullable();
            $table->integer('isactive')->default(1);
            $table->integer('isblocked')->default(0);
        });
        Schema::create('offices', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('location')->nullable();
        });
        Schema::create('orders', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('user_id')->nullable();
            $table->integer('pharmacy_id')->nullable();
            $table->integer('driver_id')->nullable();
            $table->integer('statuse_id')->default(1);
            $table->string('actual')->default('0');
            $table->integer('not_delivered')->default(0);
            $table->string('delivery_date')->nullable();
            $table->string('drop_off_photo')->nullable();
            $table->string('signature_photo')->nullable();
            $table->string('signature_type')->nullable();
            $table->integer('eta')->nullable();
        });
        foreach (['routes_priority' => true, 'routes_priority_logs' => false] as $name => $live) {
            Schema::create($name, function (Blueprint $table) use ($live): void {
                $live ? $table->increments('id') : $table->integer('id');
                $table->integer('driver_id');
                $table->integer('order_id');
                $table->string('type');
                $table->integer('type_id');
                $table->string('type_pay')->nullable();
                $table->string('pay_value')->nullable();
                if ($live) {
                    $table->integer('priority');
                    $table->integer('eta')->nullable();
                }
            });
        }
        Schema::create('notes', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('order_id');
            $table->integer('user_id');
            $table->string('type');
            $table->text('note');
        });
        Schema::create('try_call', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('order_id');
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

        $this->driver = $this->account('driver', ['name' => 'Dana', 'last_name' => 'Driver', 'work_now' => 1]);
        $this->stubDashboardLayout();
    }

    private function account(string $role, array $attributes = []): User
    {
        return User::factory()->create($attributes + ['role' => $role, 'isactive' => 1, 'isblocked' => 0])->fresh();
    }

    private function actingAsDispatcher(string $role = 'logist'): User
    {
        $dispatcher = $this->account($role);
        $this->actingAs($dispatcher)->withSession(['user_2fa' => $dispatcher->id]);

        return $dispatcher;
    }

    /**
     * An order on Dana's route with its patient stop; $stop holds the stop's pay.
     *
     * @param array<string, mixed> $order
     */
    private function orderOnRoute(array $order = [], int $priority = 2): int
    {
        $id = DB::table('orders')->insertGetId($order + [
            'user_id' => 50, 'driver_id' => $this->driver->id, 'drop_off_photo' => 'drop.jpg', 'signature_photo' => 'sign.jpg', 'signature_type' => 'patient',
        ]);
        DB::table('routes_priority')->insert([
            'driver_id' => $this->driver->id, 'order_id' => $id, 'type' => 'patient', 'type_id' => 50, 'type_pay' => 'fix', 'pay_value' => '7.5', 'priority' => $priority,
        ]);

        return $id;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rows(string $table, array $columns): array
    {
        return DB::table($table)->orderBy('id')->get($columns)->map(fn ($row) => (array) $row)->all();
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function pages(): array
    {
        return [
            'board' => ['get', '/dispatching'],
            'decision' => ['post', '/dispatching'],
            'driver page' => ['get', '/dispatching/show/1'],
            'recalculate ETA' => ['post', '/dispatching/show/1'],
        ];
    }

    #[DataProvider('pages')]
    public function test_guest_is_sent_to_login(string $method, string $uri): void
    {
        $this->{$method}($uri)->assertRedirectToRoute('login');
    }

    #[DataProvider('pages')]
    public function test_only_dispatch_staff_may_enter(string $method, string $uri): void
    {
        foreach (['sale', 'medic', 'driver', 'user', 'facility'] as $role) {
            $account = $this->account($role);
            $this->actingAs($account)->withSession(['user_2fa' => $account->id])->{$method}($uri)
                ->assertForbidden()->assertSee(AccessMessages::NO_PERMISSION);
        }
        $this->assertDatabaseCount('notes', 0);
    }

    #[DataProvider('pages')]
    public function test_blocked_dispatcher_is_stopped_first(string $method, string $uri): void
    {
        $blocked = $this->account('admin', ['isblocked' => 1]);

        $this->actingAs($blocked)->withSession(['user_2fa' => $blocked->id])->{$method}($uri)
            ->assertForbidden()->assertSee(AccessMessages::ACCOUNT_INACTIVE);
    }

    public function test_dispatcher_note_is_added_to_each_order_and_nothing_else_happens(): void
    {
        $dispatcher = $this->actingAsDispatcher();
        $first = $this->orderOnRoute();
        $second = $this->orderOnRoute();

        $response = $this->post('/dispatching', ['order_id' => "$first,$second", 'dispatcher_notes' => "Patient's gate code is 12", 'confirmed' => 1, 'skip' => 1]);

        $response->assertOk();
        $this->assertSame('{"message":"OK"}', $response->getContent());
        $this->assertSame([
            ['order_id' => $first, 'user_id' => $dispatcher->id, 'type' => '1', 'note' => "Patient\\'s gate code is 12"],
            ['order_id' => $second, 'user_id' => $dispatcher->id, 'type' => '1', 'note' => "Patient\\'s gate code is 12"],
        ], $this->rows('notes', ['order_id', 'user_id', 'type', 'note']));
        $this->assertDatabaseCount('try_call', 0);
        $this->assertSame(['0', '0'], DB::table('orders')->pluck('actual')->all());
    }

    public function test_skipped_call_and_confirmation_are_recorded(): void
    {
        $this->actingAsDispatcher('dispadmin');
        $first = $this->orderOnRoute();
        $second = $this->orderOnRoute();
        $other = $this->orderOnRoute();

        $this->post('/dispatching', ['order_id' => "$first,$second", 'skip' => 1])->assertRedirect('dispatching');
        $this->assertSame([$first, $second], DB::table('try_call')->orderBy('id')->pluck('order_id')->all());

        $this->post('/dispatching', ['order_id' => "$first,$second", 'confirmed' => 1])->assertRedirect('dispatching');
        $this->assertSame([$first => '1', $second => '1', $other => '0'], DB::table('orders')->pluck('actual', 'id')->all());
        // A confirmed order stays on the route with its proof of delivery.
        $this->assertDatabaseCount('routes_priority', 3);
        $this->assertDatabaseHas('orders', ['id' => $first, 'drop_off_photo' => 'drop.jpg', 'not_delivered' => 0]);
    }

    public function test_an_order_that_is_not_confirmed_goes_back_to_the_office_stop_already_on_the_route(): void
    {
        $dispatcher = $this->actingAsDispatcher('admin');
        $order = $this->orderOnRoute();
        $stop = DB::table('routes_priority')->where('order_id', $order)->value('id');
        DB::table('routes_priority')->insert(['driver_id' => $this->driver->id, 'order_id' => 999, 'type' => 'office', 'type_id' => 4, 'type_pay' => 'percent', 'pay_value' => '3', 'priority' => 9]);

        $this->post('/dispatching', ['order_id' => (string) $order, 'not_confirmed' => 1])->assertRedirect('dispatching');

        $this->assertDatabaseHas('orders', ['id' => $order, 'actual' => '2', 'not_delivered' => 1, 'drop_off_photo' => null, 'signature_photo' => null, 'signature_type' => null]);
        // The patient stop is logged under its own id and replaced by a return to the driver's office stop.
        $this->assertSame(
            [['id' => $stop, 'driver_id' => $this->driver->id, 'order_id' => $order, 'type' => 'patient', 'type_id' => 50, 'type_pay' => 'fix', 'pay_value' => '7.5']],
            $this->rows('routes_priority_logs', ['id', 'driver_id', 'order_id', 'type', 'type_id', 'type_pay', 'pay_value']),
        );
        $this->assertSame([
            ['order_id' => 999, 'type' => 'office', 'type_id' => 4, 'type_pay' => 'percent', 'pay_value' => '3', 'priority' => 9],
            ['order_id' => $order, 'type' => 'office', 'type_id' => 4, 'type_pay' => 'percent', 'pay_value' => '3', 'priority' => 9],
        ], $this->rows('routes_priority', ['order_id', 'type', 'type_id', 'type_pay', 'pay_value', 'priority']));
        $this->assertSame(
            [['order_id' => $order, 'user_id' => $dispatcher->id, 'type' => '1', 'note' => 'order not confirmed']],
            $this->rows('notes', ['order_id', 'user_id', 'type', 'note']),
        );
    }

    public function test_without_an_office_stop_the_return_is_added_at_the_end_of_the_route_to_the_first_office(): void
    {
        $this->actingAsDispatcher('superadmin');
        $kept = $this->orderOnRoute(priority: 6);
        $order = $this->orderOnRoute(priority: 2);

        $this->post('/dispatching', ['order_id' => (string) $order, 'not_confirmed' => 1])->assertRedirect('dispatching');

        $this->assertSame([
            ['order_id' => $kept, 'type' => 'patient', 'type_id' => 50, 'type_pay' => 'fix', 'pay_value' => '7.5', 'priority' => 6],
            ['order_id' => $order, 'type' => 'office', 'type_id' => 1, 'type_pay' => 'fix', 'pay_value' => '7.5', 'priority' => 7],
        ], $this->rows('routes_priority', ['order_id', 'type', 'type_id', 'type_pay', 'pay_value', 'priority']));
    }

    public function test_an_order_without_a_patient_stop_is_still_marked_and_noted(): void
    {
        $this->actingAsDispatcher();
        $order = DB::table('orders')->insertGetId(['user_id' => 50, 'driver_id' => $this->driver->id]);

        $this->post('/dispatching', ['order_id' => (string) $order, 'not_confirmed' => 1])->assertRedirect('dispatching');

        $this->assertDatabaseHas('orders', ['id' => $order, 'actual' => '2', 'not_delivered' => 1]);
        $this->assertSame(['order not confirmed'], DB::table('notes')->pluck('note')->all());
        $this->assertDatabaseCount('routes_priority', 0);
        $this->assertDatabaseCount('routes_priority_logs', 0);
    }

    public function test_a_rescheduled_order_gets_its_new_date_and_returns_to_the_office(): void
    {
        $dispatcher = $this->actingAsDispatcher();
        $first = $this->orderOnRoute(priority: 1);
        $second = $this->orderOnRoute(priority: 2);
        DB::table('routes_priority')->insert(['driver_id' => $this->driver->id, 'order_id' => 999, 'type' => 'office', 'type_id' => 4, 'type_pay' => 'percent', 'pay_value' => '3', 'priority' => 9]);

        $this->post('/dispatching', ['order_id' => "$first,$second", 'reschedule' => 1, 'date' => '2026-12-01'])->assertRedirect('dispatching');

        foreach ([$first, $second] as $order) {
            // Unlike a refusal, a new date does not mark the order as not delivered or change its confirmation.
            $this->assertDatabaseHas('orders', ['id' => $order, 'delivery_date' => '2026-12-01', 'actual' => '0', 'not_delivered' => 0, 'drop_off_photo' => null, 'signature_photo' => null, 'signature_type' => null]);
        }
        $this->assertSame([$first, $second], DB::table('routes_priority_logs')->orderBy('id')->pluck('order_id')->all());
        $this->assertSame([
            ['order_id' => 999, 'type' => 'office', 'type_id' => 4, 'priority' => 9],
            ['order_id' => $first, 'type' => 'office', 'type_id' => 4, 'priority' => 9],
            ['order_id' => $second, 'type' => 'office', 'type_id' => 4, 'priority' => 9],
        ], $this->rows('routes_priority', ['order_id', 'type', 'type_id', 'priority']));
        $this->assertSame([
            ['order_id' => $first, 'user_id' => $dispatcher->id, 'note' => 'reschedule to 2026-12-01'],
            ['order_id' => $second, 'user_id' => $dispatcher->id, 'note' => 'reschedule to 2026-12-01'],
        ], $this->rows('notes', ['order_id', 'user_id', 'note']));
    }

    public function test_a_request_without_a_decision_only_returns_to_the_board(): void
    {
        $this->actingAsDispatcher();
        $order = $this->orderOnRoute();

        $this->post('/dispatching', ['order_id' => (string) $order])->assertRedirect('dispatching');

        $this->assertDatabaseHas('orders', ['id' => $order, 'actual' => '0', 'drop_off_photo' => 'drop.jpg']);
        $this->assertDatabaseCount('routes_priority', 1);
        $this->assertDatabaseCount('notes', 0);
    }

    public function test_driver_page_lists_the_drivers_open_orders_by_patient_with_their_primary_address(): void
    {
        $this->actingAsDispatcher();
        $pharmacy = DB::table('pharmacys')->insertGetId(['name' => 'Corner Pharmacy', 'location' => '40.71,-74.01']);
        $casey = $this->account('user', ['name' => 'Casey', 'last_name' => 'Patient', 'phone' => '555-0101', 'address' => '1 First St', 'location' => '40.72,-74.02', 'zip' => '10001']);
        $jordan = $this->account('user', ['name' => 'Jordan', 'last_name' => 'Second', 'primary_address' => 2, 'address' => '2 Old Rd', 'address2' => '9 New Ave', 'apartment2' => '5C', 'zip2' => '10002', 'location' => '40.60,-74.60', 'location2' => '40.73,-74.03']);
        $orders = [];
        foreach ([[$casey->id, 2], [$casey->id, 3], [$jordan->id, 7], [$jordan->id, 5]] as [$patient, $status]) {
            $orders[] = DB::table('orders')->insertGetId(['user_id' => $patient, 'pharmacy_id' => $pharmacy, 'driver_id' => $this->driver->id, 'statuse_id' => $status]);
        }
        // Delivered (5) orders and other drivers' orders are not on the page.
        DB::table('orders')->insert(['user_id' => $casey->id, 'pharmacy_id' => $pharmacy, 'driver_id' => $this->driver->id + 100, 'statuse_id' => 2]);
        DB::table('locations')->insert([['user_id' => $this->driver->id, 'location' => '40.60,-74.60'], ['user_id' => $this->driver->id, 'location' => '40.70,-74.00']]);
        DB::table('drivers_eta')->insert(['driver_id' => $this->driver->id, 'distance' => 3.2, 'eta' => 30, 'last_location' => '40.7,-74']);
        DB::table('routes_priority')->insert([
            ['driver_id' => $this->driver->id, 'order_id' => $orders[0], 'type' => 'pharmacy', 'type_id' => $pharmacy, 'priority' => 1],
            ['driver_id' => $this->driver->id, 'order_id' => $orders[1], 'type' => 'pharmacy', 'type_id' => $pharmacy, 'priority' => 1],
            ['driver_id' => $this->driver->id, 'order_id' => $orders[0], 'type' => 'patient', 'type_id' => $casey->id, 'priority' => 2],
            ['driver_id' => $this->driver->id, 'order_id' => $orders[1], 'type' => 'patient', 'type_id' => $casey->id, 'priority' => 2],
            ['driver_id' => $this->driver->id, 'order_id' => $orders[2], 'type' => 'office', 'type_id' => 1, 'priority' => 3],
        ]);

        $response = $this->get('/dispatching/show/'.$this->driver->id)->assertOk()->assertViewIs('dispatching.show');

        $response->assertViewHas('title', 'Process Detail')->assertViewHas('show_ids', [])->assertViewHas('show_priority', []);
        $this->assertSame(['Dana', 30, 3.2], [$response->viewData('driver')->name, $response->viewData('driver')->eta, $response->viewData('driver')->distance]);
        $this->assertSame('40.70,-74.00', $response->viewData('locations')->location);
        $this->assertSame([
            ['id' => (string) $orders[2], 'user_id' => $jordan->id, 'username' => 'Jordan', 'useraddress' => '9 New Ave', 'userapartment' => '5C', 'userzip' => '10002', 'userlocation' => '40.73,-74.03'],
            ['id' => "$orders[0],$orders[1]", 'user_id' => $casey->id, 'username' => 'Casey', 'useraddress' => '1 First St', 'userapartment' => null, 'userzip' => '10001', 'userlocation' => '40.72,-74.02'],
        ], $response->viewData('orders')->map(fn ($row) => ['id' => (string) $row->id, 'user_id' => $row->user_id, 'username' => $row->username, 'useraddress' => $row->useraddress, 'userapartment' => $row->userapartment, 'userzip' => $row->userzip, 'userlocation' => $row->userlocation])->all());
        $this->assertSame([
            ['id' => $jordan->id, 'order_id' => (string) $orders[2], 'location' => '40.73,-74.03'],
            ['id' => $casey->id, 'order_id' => "$orders[0],$orders[1]", 'location' => '40.72,-74.02'],
        ], array_map(fn (array $row): array => ['id' => $row['id'], 'order_id' => (string) $row['order_id'], 'location' => $row['location']], array_values($response->viewData('patients_locations'))));
        $this->assertSame([], $response->viewData('pharmacy_locations'));
        $this->assertSame(["$orders[0],{$casey->id}", "$orders[1],{$casey->id}"], $response->viewData('patient_routes_priority'));
        $this->assertSame(["$orders[0],$pharmacy", "$orders[1],$pharmacy"], $response->viewData('pharmacy_routes_priority'));
        // Stops in route order: orders sharing a pharmacy or patient are one stop.
        $this->assertSame(
            [['pharmacy', "$orders[0],$orders[1]", 1], ['patient', "$orders[0],$orders[1]", 2], ['office', (string) $orders[2], 3]],
            array_map(fn ($stop): array => [$stop->type, (string) $stop->order_id, (int) $stop->priority], $response->viewData('routes_priority')),
        );

        // Loaded into the dashboard over AJAX, the page is returned as its sections.
        $this->assertArrayHasKey('content', $this->get('/dispatching/show/'.$this->driver->id.'?ajax=1')->assertOk()->json());
    }

    public function test_dispatcher_recalculates_the_drivers_eta_from_the_driver_page(): void
    {
        $this->actingAsDispatcher();
        Redis::shouldReceive('get')->with('here_access_token')->andReturn('here-token');
        FakeMapServices::install();
        $pharmacy = DB::table('pharmacys')->insertGetId(['location' => '40.71,-74.01']);
        DB::table('locations')->insert(['user_id' => $this->driver->id, 'location' => '40.70,-74.00']);
        DB::table('routes_priority')->insert(['driver_id' => $this->driver->id, 'order_id' => 1, 'type' => 'pharmacy', 'type_id' => $pharmacy, 'priority' => 1]);

        $this->from('/dispatching/show/'.$this->driver->id)->post('/dispatching/show/'.$this->driver->id, ['eta_calculate' => 1])
            ->assertRedirect('/dispatching/show/'.$this->driver->id)->assertSessionHas('success', 'Successfully the route ETA was updated.');

        $this->assertCount(1, FakeMapServices::$requests);
        $this->assertDatabaseHas('drivers_eta', ['driver_id' => $this->driver->id, 'eta' => 10]);

        // Any other post to the page does nothing and answers with an empty page.
        $response = $this->post('/dispatching/show/'.$this->driver->id, ['something' => 1])->assertOk();
        $this->assertSame('', $response->getContent());
        $this->assertCount(1, FakeMapServices::$requests);
    }
}
