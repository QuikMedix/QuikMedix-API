<?php

// Run: php tests/smoke/tariff-setup.php
// Isolated SQLite data and a fake geocoder; no real records or external requests.
namespace {
    use App\User;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Schema;

    error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    require __DIR__.'/../../vendor/autoload.php';
    $app = require __DIR__.'/../../bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $app->make(Illuminate\Contracts\Http\Kernel::class);
    $app['env'] = 'testing';
    set_exception_handler(function (Throwable $error) {
        fwrite(STDERR, get_class($error).': '.$error->getMessage()."\n");
        exit(1);
    });

    class TariffSetupUser extends User {
        public function isblocked_or_isactive() { return !$this->isactive || $this->isblocked; }
    }
    $temporary = sys_get_temp_dir().'/quikmedix-tariffs-'.bin2hex(random_bytes(6));
    mkdir($temporary.'/layouts', 0700, true);
    mkdir($temporary.'/compiled', 0700);
    file_put_contents($temporary.'/layouts/master.blade.php', "@yield('content')\n@yield('footerScript')");
    register_shutdown_function(function () use ($temporary) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($temporary, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($temporary);
    });
    config([
        'database.default' => 'tariff_smoke',
        'database.connections.tariff_smoke' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
        'session.driver' => 'array', 'cache.default' => 'array',
        'view.compiled' => $temporary.'/compiled',
        'app.googlemaps_apikey' => 'test-only-key',
    ]);
    $geocodedAddress = ['status' => 'OK', 'results' => [['geometry' => ['location' => ['lat' => 40.7, 'lng' => -74]]]]];
    $geocoderResponse = $geocodedAddress;
    Illuminate\Support\Facades\Http::fake(function () use (&$geocoderResponse) {
        if ($geocoderResponse === null) throw new Illuminate\Http\Client\ConnectionException('Fixture timeout');
        return Illuminate\Support\Facades\Http::response($geocoderResponse);
    });
    DB::setDefaultConnection('tariff_smoke');
    // SQLite has no spatial extension here. Check the saved WKT and its round trip.
    DB::connection()->getPdo()->sqliteCreateFunction('ST_GeomFromText', fn ($value) => $value);
    DB::connection()->getPdo()->sqliteCreateFunction('ST_AsText', fn ($value) => $value);
    $app['view']->getFinder()->prependLocation($temporary);

    $priceFields = ['tariff', 'tariff_next_day', 'tariff_same_day', 'tariff_asap', 'tariff_after_hours', 'tariff_fridge', 'tariff_area2', 'tariff_area3', 'tariff_area_more'];
    Schema::create('plans', function ($table) use ($priceFields) {
        $table->increments('id'); $table->string('name'); $table->integer('order_rate');
        foreach ($priceFields as $field) $table->decimal($field)->nullable();
    });
    Schema::create('area', function ($table) {
        $table->increments('id'); $table->string('name'); $table->string('state');
        $table->text('polygon')->nullable(); $table->decimal('tariff')->nullable();
    });
    Schema::create('area_zip', function ($table) { $table->integer('area_id'); $table->string('zip'); });
    foreach (['states', 'admin_areas'] as $name) {
        Schema::create($name, function ($table) { $table->increments('id'); $table->string('name'); });
    }
    Schema::create('pharmacys', function ($table) use ($priceFields) {
        $table->increments('id');
        foreach (['name', 'email', 'phone', 'logo', 'image_front', 'site', 'address', 'location', 'zone_id', 'plan_id', 'massiveBagsTransfer'] as $field) $table->string($field)->nullable();
        foreach ($priceFields as $field) $table->decimal($field)->nullable();
    });
    Schema::create('pharmacy_areas', function ($table) {
        $table->integer('pharmacy_id'); $table->integer('area_id'); $table->integer('type');
    });
    Schema::create('white_list_ip', function ($table) { $table->string('ip'); });

    $user = new TariffSetupUser;
    $user->forceFill(['id' => 101, 'role' => 'superadmin', 'isactive' => 1, 'isblocked' => 0]);
    function requestPage($path, $data = null) {
        global $app, $user;
        $request = Request::create('http://localhost'.$path, $data === null ? 'GET' : 'POST', $data ?? []);
        $request->headers->set('Accept', $data === null ? 'text/html' : 'application/json');
        $app->instance('request', $request);
        Auth::forgetGuards(); Auth::guard()->setUser($user);
        $request->setUserResolver(fn () => Auth::guard()->user());
        $_GET = $request->query->all();
        $_POST = $data ?? [];
        try {
            return $app['router']->dispatch($request);
        } catch (Throwable $error) {
            if (!$error instanceof Illuminate\Validation\ValidationException && !$error instanceof Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) throw $error;
            return $app->make(Illuminate\Contracts\Debug\ExceptionHandler::class)->render($request, $error);
        }
    }
    $checks = 0;
    function check($condition, $message) {
        global $checks;
        if (!$condition) throw new RuntimeException($message);
        $checks++;
    }
    $response = requestPage('/pharmacys/add');
    check($response->getStatusCode() === 200, 'Empty installation should render pharmacy setup.');
    $html = $response->getContent();
    check(str_contains($html, '/settings/plans/add'), 'Missing plan needs a setup link.');
    check(str_contains($html, '/settings/area/add'), 'Missing area needs a setup link.');
    check(str_contains($html, 'No tariff areas exist yet'), 'Empty picker needs an explanation.');
    check((bool) preg_match('/<select[^>]*id="areas"[^>]*disabled/', $html), 'Empty picker should not offer an unusable search.');
    check(str_contains($html, 'pharmacy-address.init.js') && str_contains($html, 'callback=initPharmacyAddress'), 'Pharmacy creation must load the replacement address widget.');
    check(!str_contains($html, 'new google.maps.places.Autocomplete('), 'Pharmacy creation must not load legacy Places autocomplete.');
    $response = requestPage('/settings/area/add');
    check($response->getStatusCode() === 200, 'Area setup must render with no states.');
    check(str_contains($response->getContent(), 'value="New York"'), 'State reference choices must be available.');
    check(DB::table('states')->count() === 0, 'Displaying fallback states must not modify the database.');
    check(!str_contains($response->getContent(), 'libraries=drawing'), 'Retired drawing library must not load.');
    check(str_contains($response->getContent(), 'Finish area'), 'Replacement drawing controls must be available.');

    $area = ['save' => 1, 'name' => 'Fixture coverage', 'state' => 'New York', 'polygon' => json_encode([
        ['lat' => 40.7, 'lng' => -74], ['lat' => 40.8, 'lng' => -74], ['lat' => 40.8, 'lng' => -73.9],
    ])];
    foreach (['', 'not-json', '[]', '[{"lat":40,"lng":-74}]', '[{"lat":40,"lng":-74},{"lat":40,"lng":-74},{"lat":40,"lng":-74}]', '[{"lat":100,"lng":0},{"lat":40,"lng":-74},{"lat":41,"lng":-73}]', '[{"lat":"1);DROP TABLE area;","lng":0},{"lat":40,"lng":-74},{"lat":41,"lng":-73}]'] as $invalid) {
        $response = requestPage('/settings/area/add', array_replace($area, ['polygon' => $invalid]));
        check($response->getStatusCode() === 422, 'Invalid boundary must produce a validation error; got '.$response->getStatusCode());
        check(DB::table('area')->count() === 0, 'Invalid boundary must not create an area.');
    }
    check(requestPage('/settings/area/add', $area)->getStatusCode() === 302, 'A valid area should save.');
    $saved = DB::table('area')->first();
    check($saved->polygon === 'POLYGON((40.7 -74,40.8 -74,40.8 -73.9,40.7 -74))', 'Geometry must retain coordinate order and close the boundary.');
    $response = requestPage('/settings/area/'.$saved->id.'/edit');
    check($response->getStatusCode() === 200 && str_contains($response->getContent(), 'Fixture coverage'), 'Saved area must reopen for editing.');
    check(requestPage('/settings/area/'.$saved->id.'/edit', array_replace($area, ['name' => 'Updated coverage']))->getStatusCode() === 302, 'Editing an area should save.');
    check(DB::table('area')->count() === 1 && DB::table('area')->value('name') === 'Updated coverage', 'Editing must update the existing area.');

    $plan = array_merge(['save' => 1, 'name' => 'Fixture plan', 'order_rate' => 100], array_fill_keys($priceFields, 5));
    check(requestPage('/settings/plans/add', $plan)->getStatusCode() === 302, 'User-defined tariff plan should save.');
    $planId = DB::table('plans')->value('id');
    $zoneId = DB::table('admin_areas')->insertGetId(['name' => 'Fixture admin zone']);
    $response = requestPage('/pharmacys/add');
    $html = $response->getContent();
    check(str_contains($html, 'Fixture plan') && str_contains($html, 'New York, Updated coverage'), 'Created plan and area must appear in pharmacy selectors.');
    check(!(bool) preg_match('/<select[^>]*id="areas"[^>]*disabled/', $html), 'Populated picker must be enabled.');
    $pharmacy = array_merge(array_fill_keys($priceFields, 5), [
        'save' => 1, 'name' => 'Fixture pharmacy', 'email' => 'tariff-smoke@example.invalid',
        'phone' => '2025550100', 'site' => 'https://example.invalid', 'address' => 'Fixture address',
        'plan_id' => $planId, 'zone_id' => $zoneId, 'tariff_areas' => [$saved->id],
    ]);
    foreach ([['plan_id' => ''], ['plan_id' => 999], ['tariff_areas' => [999]], ['tariff_areas' => 'bad'], ['tariff_areas' => [$saved->id, $saved->id]]] as $invalid) {
        check(requestPage('/pharmacys/add', array_replace($pharmacy, $invalid))->getStatusCode() === 422, 'Invalid tariff selection must be rejected.');
        check(DB::table('pharmacys')->count() === 0, 'Invalid selection must not create a pharmacy.');
    }
    config(['app.googlemaps_apikey' => null]);
    check(requestPage('/pharmacys/add', $pharmacy)->getStatusCode() === 422, 'Missing Maps configuration must show a validation error.');
    check(DB::table('pharmacys')->count() === 0, 'Missing Maps configuration must not create a pharmacy.');
    config(['app.googlemaps_apikey' => 'test-only-key']);
    check(requestPage('/pharmacys/add', array_replace($pharmacy, ['address' => '']))->getStatusCode() === 422, 'An empty address must be rejected even without browser validation.');
    $geocoderResponse = ['status' => 'REQUEST_DENIED', 'results' => []];
    $response = requestPage('/pharmacys/add', $pharmacy);
    check($response->getStatusCode() === 422 && str_contains($response->getContent(), 'enable the Geocoding API'), 'Disabled geocoding must explain the configuration needed to save.');
    check(DB::table('pharmacys')->count() === 0, 'Disabled geocoding must not save an unlocated pharmacy.');
    $geocoderResponse = ['status' => 'ZERO_RESULTS', 'results' => []];
    check(requestPage('/pharmacys/add', $pharmacy)->getStatusCode() === 422, 'Unknown addresses must show a validation error.');
    check(DB::table('pharmacys')->count() === 0, 'Unknown addresses must not create a pharmacy.');
    $geocoderResponse = null;
    check(requestPage('/pharmacys/add', $pharmacy)->getStatusCode() === 422, 'Geocoder connection failures must show a validation error.');
    check(DB::table('pharmacys')->count() === 0, 'Geocoder connection failures must not create a pharmacy.');
    $geocoderResponse = $geocodedAddress;
    check(requestPage('/pharmacys/add', $pharmacy)->getStatusCode() === 302, 'Pharmacy with a valid tariff should save.');
    $pharmacyId = DB::table('pharmacys')->value('id');
    check(DB::table('pharmacys')->value('plan_id') == $planId, 'Pharmacy must retain the selected plan.');
    $assignment = DB::table('pharmacy_areas')->first();
    check($assignment && $assignment->pharmacy_id == $pharmacyId && $assignment->area_id == $saved->id && $assignment->type == 1, 'Selected area must persist as the default tariff tier.');
    $response = requestPage('/pharmacys/add', $pharmacy);
    $html = $response->getContent();
    check(str_contains($html, 'Pharmacy with this email already exists'), 'Duplicate email should show the original error.');
    check((bool) preg_match('/<select[^>]*id="areas"[^>]*>\s*<option[^>]*selected/s', $html), 'Duplicate email must retain selected areas.');
    check((bool) preg_match('/<select[^>]*id="plan_id"[^>]*>.*?<option value="'.$planId.'"[^>]*selected/s', $html), 'Duplicate email must retain the plan.');
    check(requestPage('/pharmacys/add', array_replace($pharmacy, ['email' => 'no-areas@example.invalid', 'tariff_areas' => []]))->getStatusCode() === 302, 'Areas remain optional when using the plan fallback.');
    check(DB::table('pharmacy_areas')->count() === 1, 'Empty selection must not invent area assignments.');

    DB::statement("CREATE TRIGGER reject_assignment BEFORE INSERT ON pharmacy_areas BEGIN SELECT RAISE(ABORT, 'fixture failure'); END");
    $response = requestPage('/pharmacys/add', array_replace($pharmacy, ['email' => 'rollback@example.invalid']));
    check($response->getStatusCode() === 500, 'Fixture must trigger an area insert failure.');
    check(!DB::table('pharmacys')->where('email', 'rollback@example.invalid')->exists(), 'Area insert failure must roll back pharmacy creation.');
    $app['session']->flashInput(['tariff_areas' => 'bad']);
    $view = App\Http\Controllers\LexaAdmin::pharmacysListAdd();
    check(str_contains($view->render(), 'Updated coverage'), 'Malformed old input must not break the selection form.');
    $app['session']->flashInput([]);
    config(['app.googlemaps_apikey' => null]);
    $response = requestPage('/settings/area/add');
    check(str_contains($response->getContent(), 'Configure the Google Maps API key'), 'Missing Maps configuration needs a visible explanation.');
    check(!str_contains($response->getContent(), 'maps.googleapis.com/maps/api/js'), 'An unconfigured map must not load Google Maps with an empty key.');
    $user->role = 'driver';
    check(requestPage('/settings/area/add')->getStatusCode() === 403, 'Area setup must retain role restrictions.');
    check(requestPage('/pharmacys/add', $pharmacy)->getStatusCode() === 403, 'Drivers must not create pharmacies.');
    $user->role = 'superadmin'; $user->isblocked = 1;
    check(requestPage('/pharmacys/add', $pharmacy)->getStatusCode() === 403, 'Blocked accounts must remain blocked.');
    echo "$checks tariff setup checks passed with an isolated database and fake geocoder.\n";
}
