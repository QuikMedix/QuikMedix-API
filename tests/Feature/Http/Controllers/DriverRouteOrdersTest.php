<?php

namespace Tests\Feature\Http\Controllers;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesAuthenticationSchema;
use Tests\TestCase;

class DriverRouteOrdersTest extends TestCase
{
    use CreatesAuthenticationSchema;

    private User $driver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticationSchema();
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('user');
            $table->integer('pharmacy_id')->nullable();
            $table->integer('isactive')->default(1);
            $table->integer('isblocked')->default(0);
            $table->string('device_token')->nullable();
        });
        Schema::create('pharmacys', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('isactive')->default(1);
            $table->integer('isblocked')->default(0);
        });
        Schema::create('orders', function (Blueprint $table): void {
            $table->increments('id');
            foreach (['driver_id', 'pharmacy_id', 'user_id', 'statuse_id', 'ready'] as $column) {
                $table->integer($column)->nullable();
            }
        });
        Schema::create('offices', function (Blueprint $table): void {
            $table->increments('id');
        });
        Schema::create('routes_priority', function (Blueprint $table): void {
            $table->increments('id');
            foreach (['driver_id', 'order_id', 'type_id', 'priority'] as $column) {
                $table->integer($column);
            }
            $table->string('type');
            $table->integer('type_pay')->default(4);
            $table->decimal('pay_value')->default(-1);
        });
        Schema::create('locations', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('location');
        });
        config(['services.fcm.credentials' => null, 'app.twilio_sid' => null, 'app.googlemaps_apikey' => 'test-key']);
        $this->withSession(['user_2fa' => true]);
    }

    private function createRoute(): void
    {
        DB::table('pharmacys')->insert(['id' => 2]);
        DB::table('offices')->insert(['id' => 1]);
        $this->driver = User::factory()->state(['isactive' => 1, 'isblocked' => 0])->create(['role' => 'driver', 'pharmacy_id' => 2]);
        DB::table('orders')->insert([
            ['id' => 4, 'driver_id' => $this->driver->id, 'pharmacy_id' => 2, 'user_id' => 20, 'statuse_id' => 3, 'ready' => 2],
            ['id' => 5, 'driver_id' => null, 'pharmacy_id' => 2, 'user_id' => 21, 'statuse_id' => 7, 'ready' => 1],
        ]);
        DB::table('routes_priority')->insert([
            'driver_id' => $this->driver->id, 'order_id' => 4, 'type' => 'patient', 'type_id' => 20,
            'priority' => 1, 'type_pay' => 4, 'pay_value' => -1,
        ]);
    }

    private function form(array $overrides = []): array
    {
        return array_replace([
            'order_id' => ['4', '4,5', ''], 'type' => ['pharmacy', 'office', ''], 'type_id' => ['2', '1', ''],
            'type_pay' => 4, 'pay_value' => 90,
        ], $overrides);
    }

    public function test_dispatch_saves_selected_orders_under_the_office_without_a_map_request(): void
    {
        $this->createRoute();

        $this->actingAs(User::factory()->state(['isactive' => 1, 'isblocked' => 0])->create(['role' => 'dispadmin']))
            ->post('/routes-list/driver/'.$this->driver->id, $this->form())->assertRedirect('/routes-list/driver/'.$this->driver->id)->assertSessionHasNoErrors();

        $this->assertDatabaseCount('routes_priority', 3);
        foreach ([4, 5] as $orderId) {
            $this->assertDatabaseHas('routes_priority', ['driver_id' => $this->driver->id, 'order_id' => $orderId, 'type' => 'office', 'type_id' => 1, 'priority' => 2]);
            $this->assertDatabaseHas('orders', ['id' => $orderId, 'driver_id' => $this->driver->id, 'ready' => 2]);
        }
        $this->assertDatabaseMissing('routes_priority', ['type' => 'patient']);
        Http::assertNothingSent();
    }

    public function test_an_empty_office_selection_preserves_the_existing_route(): void
    {
        $this->createRoute();

        $this->actingAs(User::factory()->state(['isactive' => 1, 'isblocked' => 0])->create(['role' => 'admin']))->post('/routes-list/driver/'.$this->driver->id, $this->form([
            'order_id' => ['', '4', ''], 'type' => ['office', 'pharmacy', ''], 'type_id' => ['1', '2', ''],
        ]))->assertSessionHasErrors(['order_id.0' => 'Select at least one order for each office stop before saving.']);

        $this->assertDatabaseCount('routes_priority', 1);
        $this->assertDatabaseHas('routes_priority', ['order_id' => 4, 'type' => 'patient', 'type_id' => 20]);
        $this->assertDatabaseHas('orders', ['id' => 4, 'driver_id' => $this->driver->id, 'statuse_id' => 3]);
        Http::assertNothingSent();
    }

    public function test_dispatch_can_assign_deliveries_to_an_office_before_adding_other_stops(): void
    {
        $this->createRoute();

        $this->actingAs(User::factory()->state(['isactive' => 1, 'isblocked' => 0])->create(['role' => 'dispadmin']))
            ->post('/routes-list/driver/'.$this->driver->id, $this->form([
                'order_id' => ['4,5', ''], 'type' => ['office', ''], 'type_id' => ['1', ''],
            ]))->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseCount('routes_priority', 2);
        foreach ([4, 5] as $id) {
            $this->assertDatabaseHas('routes_priority', ['driver_id' => $this->driver->id, 'order_id' => $id, 'type' => 'office', 'type_id' => 1, 'priority' => 1]);
        }
    }

    public function test_empty_grid_slots_do_not_shift_office_order_pairings(): void
    {
        $this->createRoute();

        $this->actingAs(User::factory()->state(['isactive' => 1, 'isblocked' => 0])->create(['role' => 'logist']))->post('/routes-list/driver/'.$this->driver->id, $this->form([
            'order_id' => ['', '4', '', '5'], 'type' => ['', 'pharmacy', '', 'office'], 'type_id' => ['', '2', '', '1'],
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseCount('routes_priority', 2);
        $this->assertDatabaseHas('routes_priority', ['order_id' => 4, 'type' => 'pharmacy', 'type_id' => 2, 'priority' => 1]);
        $this->assertDatabaseHas('routes_priority', ['order_id' => 5, 'type' => 'office', 'type_id' => 1, 'priority' => 2]);
    }

    public static function invalidStops(): array
    {
        return [
            'missing arrays' => [['type_id' => null], 'type_id'],
            'mismatched arrays' => [['type_id' => ['2']], 'order_id'],
            'invalid orders' => [['order_id' => ['invalid', '4', '']], 'order_id.0'],
            'deleted order' => [['order_id' => ['999', '4', '']], 'order_id.0'],
            'deleted office' => [['type_id' => ['2', '999', '']], 'type_id.1'],
            'unknown stop type' => [['type' => ['pharmacy', 'unknown', '']], 'type.1'],
        ];
    }

    #[DataProvider('invalidStops')]
    public function test_invalid_route_inputs_do_not_delete_existing_stops(array $payload, string $error): void
    {
        $this->createRoute();

        $this->actingAs(User::factory()->state(['isactive' => 1, 'isblocked' => 0])->create(['role' => 'admin']))
            ->post('/routes-list/driver/'.$this->driver->id, $this->form($payload))->assertSessionHasErrors($error);

        $this->assertDatabaseCount('routes_priority', 1);
        $this->assertDatabaseHas('routes_priority', ['order_id' => 4, 'type' => 'patient', 'type_id' => 20]);
        $this->assertDatabaseHas('orders', ['id' => 4, 'driver_id' => $this->driver->id]);
    }

    public function test_route_write_failure_rolls_back_existing_route_and_assignments(): void
    {
        $this->createRoute();
        DB::statement("CREATE TRIGGER reject_route_insert BEFORE INSERT ON routes_priority BEGIN SELECT RAISE(ABORT, 'Route write failed'); END");

        $this->actingAs(User::factory()->state(['isactive' => 1, 'isblocked' => 0])->create(['role' => 'dispadmin']))
            ->post('/routes-list/driver/'.$this->driver->id, $this->form())->assertServerError();

        $this->assertDatabaseCount('routes_priority', 1);
        $this->assertDatabaseHas('routes_priority', ['order_id' => 4, 'type' => 'patient', 'type_id' => 20]);
        $this->assertDatabaseHas('orders', ['id' => 4, 'driver_id' => $this->driver->id]);
        $this->assertDatabaseHas('orders', ['id' => 5, 'driver_id' => null]);
    }

    public function test_pharmacy_cannot_route_orders_from_another_pharmacy(): void
    {
        $this->createRoute();
        DB::table('orders')->where('id', 5)->update(['pharmacy_id' => 3]);

        $this->actingAs(User::factory()->state(['isactive' => 1, 'isblocked' => 0])->create(['role' => 'medic', 'pharmacy_id' => 2]))
            ->post('/routes-list/driver/'.$this->driver->id, $this->form())->assertForbidden();

        $this->assertDatabaseCount('routes_priority', 1);
        $this->assertDatabaseHas('orders', ['id' => 5, 'driver_id' => null]);
    }

    public function test_pharmacy_can_save_its_own_drivers_office_stops(): void
    {
        $this->createRoute();

        $this->actingAs(User::factory()->state(['isactive' => 1, 'isblocked' => 0])->create(['role' => 'medic', 'pharmacy_id' => 2]))
            ->post('/routes-list/driver/'.$this->driver->id, $this->form())->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('routes_priority', ['order_id' => 4, 'type' => 'office', 'type_id' => 1, 'driver_id' => $this->driver->id]);
        $this->assertDatabaseHas('routes_priority', ['order_id' => 5, 'type' => 'office', 'type_id' => 1, 'driver_id' => $this->driver->id]);
    }

    public static function officeRouteViews(): array
    {
        return [
            'existing route' => [true, false, false],
            'office added first' => [false, false, false],
            'route-only with delivery stops' => [true, true, true],
            'route-only with office stop' => [true, true, false],
        ];
    }

    #[DataProvider('officeRouteViews')]
    public function test_route_view_offers_orders_and_map_points_for_normal_and_route_only_views(bool $hasExistingOrders, bool $showRoute, bool $deliveryStops): void
    {
        $this->createRoute();
        $this->actingAs(User::factory()->state(['isactive' => 1, 'isblocked' => 0])->create(['role' => 'dispadmin']));
        $directory = sys_get_temp_dir().'/quikmedix-office-view-'.bin2hex(random_bytes(6));
        mkdir($directory.'/layouts', 0700, true);
        file_put_contents($directory.'/layouts/master.blade.php', "@yield('headerCss')\n@yield('content')\n@yield('footerScript')");
        app('view')->getFinder()->prependLocation($directory);

        try {
            request()->query->set('show_route', $showRoute ? '1' : '0');
            $routeStops = [(object) ['order_id' => $hasExistingOrders ? '4' : '', 'type' => 'office', 'type_id' => 1, 'eta' => 0]];
            if ($deliveryStops) {
                $routeStops[] = (object) ['order_id' => '4', 'type' => 'pharmacy', 'type_id' => 2, 'eta' => 0];
                $routeStops[] = (object) ['order_id' => '4', 'type' => 'patient', 'type_id' => 20, 'eta' => 0];
            }
            $orders = collect([4, 5])->mapWithKeys(fn (int $id): array => [$id => (object) [
                'id' => $id, 'user_id' => $id + 16, 'pharmacy_id' => 2, 'delivery_date' => '2026-11-12',
                'delivery_time_range' => '', 'delivery_time' => 'Next day', 'finish' => null, 'not_delivered' => 0,
                'pharmacyname' => 'Fixture Pharmacy', 'pharmacylocation' => '40,-73', 'useraddress' => '1 Test St',
                'userzip' => '00001', 'userlocation' => '40,-73', 'special_instructions' => '',
            ]]);
            $view = $this->view('routes.driver', [
                'driver' => (object) ['id' => $this->driver->id, 'name' => 'Test', 'last_name' => 'Driver', 'email' => 'driver@example.org', 'phone' => '555-0100', 'isactive' => 1, 'isblocked' => 0, 'transport' => 1, 'image' => null, 'eta' => 0, 'distance' => 0],
                'locations' => (object) ['location' => '37,-122'], 'orders' => $orders, 'pharmacys' => collect(), 'pharmacys_list' => collect(),
                'delivery_times' => collect(), 'order_statuses' => collect(), 'offices' => collect([(object) ['id' => 1, 'location' => '40,-73'], (object) ['id' => 2, 'location' => '41,-74']]),
                'routes_priority' => $routeStops,
                'orders_id' => $hasExistingOrders ? collect([(object) ['order_id' => 4]]) : collect(), 'patient_routes_priority' => [], 'pharmacy_routes_priority' => [],
                'filter' => ['start' => '', 'end' => '', 'pharmacy' => [], 'status' => [], 'delivery_time' => []], 'type_pay' => 4, 'pay_value' => -1, 'patients_locations' => [['id' => 20, 'location' => '40.2,-73.2'], ['id' => 21, 'location' => '40.3,-73.3'], ['id' => 22, 'location' => '']],
                'pharmacy_locations' => [['id' => 99, 'location' => ''], ['id' => 2, 'location' => '40.1,-73.1'], ['id' => 4, 'location' => '42,-75']],
                'show_ids' => [], 'show_priority' => [], 'errors' => new \Illuminate\Support\ViewErrorBag,
            ])->assertSee('/js/route-order-select.js', false)
                ->assertSee('window.routeOrderSelect.options', false)
                ->assertSee('window.officeOrderPicker.updateOfficeId(ids)', false)
                ->assertSee('/js/office-order-picker.js?v=2', false)
                ->assertSee('window.officeOrderPicker.init([4,5', false)
                ->assertSee('id="office-order-dialog"', false)
                ->assertSee('data-office-order-picker', false)
                ->assertSee('/js/route-map-tools.js?v=1', false)
                ->assertDontSee('google.maps.drawing', false)
                ->assertDontSee('libraries=drawing', false)
                ->assertDontSee('i033.svg', false)
                ->assertDontSee('.disableSelection()', false)
                ->assertDontSee("class='multiple_select' required", false)
                ->assertSee('active-yellow office1', false)
                ->assertSee('Use Choose orders at each office stop to select its deliveries before saving.');

            $html = (string) $view;
            $this->assertSame($showRoute ? ['1'] : ['1', '2'], $this->mapValues($html, 'locationOfficeID'));
            $this->assertSame($showRoute ? ($deliveryStops ? ['2;2'] : []) : ['2', '4'], $this->mapValues($html, 'locationPharmacyID'));
            $this->assertSame($showRoute ? ($deliveryStops ? ['20;3'] : []) : ['20', '21'], $this->mapValues($html, 'locationPatientsID'));
            foreach (['locationOffice', 'locationPharmacy', 'locationPatients'] as $field) {
                $this->assertCount(count($this->mapValues($html, $field.'ID')), $this->mapValues($html, $field));
            }
            if ($previewPath = getenv('QUIKMEDIX_OFFICE_PREVIEW_PATH')) {
                $suffix = $showRoute ? ($deliveryStops ? 'route' : 'office') : 'all';
                $this->writeBrowserPreview($html, $previewPath.'.'.$suffix.'.html');
            }
        } finally {
            unlink($directory.'/layouts/master.blade.php');
            rmdir($directory.'/layouts');
            rmdir($directory);
        }
    }

    /**
     * @return list<string>
     */
    private function mapValues(string $html, string $field): array
    {
        preg_match('/var '.preg_quote($field, '/').' = \[([\s\S]*?)\];/', $html, $match);
        preg_match_all('/"([^"\n]*)"/', $match[1], $values);

        return $values[1];
    }

    private function writeBrowserPreview(string $html, string $path): void
    {
        $publicUri = 'file://'.str_replace(' ', '%20', public_path()).'/';
        $html = str_replace('http://localhost/', $publicUri, $html);
        $html = str_replace('src="/js/', 'src="'.$publicUri.'js/', $html);
        $scripts = '';
        foreach (['libs/jquery/jquery.min.js', 'libs/bootstrap/bootstrap.min.js', 'js/bootstrap.bundle.min.js', 'js/selectize.min.js'] as $asset) {
            $scripts .= '<script src="'.$publicUri.$asset.'"></script>';
        }
        $html = str_replace('<script src="'.$publicUri.'js/bootstrap.bundle.min.js"></script>', $scripts.'<script src="'.$publicUri.'js/bootstrap.bundle.min.js"></script>', $html);
        $html = '<!DOCTYPE html><meta charset="utf-8"><meta http-equiv="Content-Security-Policy" content="default-src \'none\'; script-src file: \'unsafe-inline\'; style-src file: \'unsafe-inline\'; font-src file: data:; img-src file: data:; connect-src \'none\'; form-action \'none\'"><title>Full office route grid preview</title><link rel="stylesheet" href="'.$publicUri.'css/bootstrap.min.css"><div id="qa-errors" role="alert"></div><script>window.addEventListener("error",function(e){document.getElementById("qa-errors").textContent=e.message;});</script>'.$html;
        $html .= <<<'HTML'
<p id="qa-captured-route"></p>
<script>
$('body').on('click','.dropdown-toggle',function(e){e.stopPropagation();if($(this).parent().find('.dropdown-menu').is(':hidden')){$(this).dropdown('toggle');}});
document.getElementById('driver-route-form').addEventListener('submit',function(e){
    if(e.defaultPrevented){return;}
    e.preventDefault();
    var result=[];
    this.querySelectorAll('.active-yellow').forEach(function(stop){result.push(stop.querySelector('input[name="order_id[]"]').value);});
    document.getElementById('qa-captured-route').textContent='Captured office orders: '+result.join(' | ');
});
</script>
HTML;
        file_put_contents($path, $html);
    }

    public function test_route_save_requires_login_and_dispatch_or_pharmacy_permission(): void
    {
        $this->createRoute();
        $this->post('/routes-list/driver/'.$this->driver->id, $this->form())->assertRedirectToRoute('login');
        $this->actingAs($this->driver)->post('/routes-list/driver/'.$this->driver->id, $this->form())->assertForbidden();

        $this->assertDatabaseCount('routes_priority', 1);
    }
}
