<?php

// Run with: php tests/smoke/page-routing.php
// Uses only an in-memory SQLite database and a temporary layout/compiled views.
use App\User;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$app->make(Illuminate\Contracts\Http\Kernel::class);

class RoutingSmokeUser extends User
{
    public function isblocked_or_isactive()
    {
        return !$this->isactive || $this->isblocked;
    }

    public function pharmacy_balance_ban()
    {
        return false;
    }
}

$temporary = sys_get_temp_dir().'/quikmedix-routing-'.bin2hex(random_bytes(6));
mkdir($temporary.'/layouts', 0700, true);
mkdir($temporary.'/compiled', 0700);
// Avoid the unrelated dashboard/notification queries in the production shell.
file_put_contents($temporary.'/layouts/master.blade.php', "@yield('content')\n@yield('footerScript')");
register_shutdown_function(function () use ($temporary) {
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($temporary, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($temporary);
});
config([
    'database.default' => 'routing_smoke',
    'database.connections.routing_smoke' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
    'session.driver' => 'array',
    'cache.default' => 'array',
    'view.compiled' => $temporary.'/compiled',
]);
DB::setDefaultConnection('routing_smoke');
$app['view']->getFinder()->prependLocation($temporary);

Schema::create('pharmacys', function ($table) {
    $table->increments('id');
    foreach (['name', 'address', 'zone_id'] as $column) $table->string($column)->nullable();
    $table->integer('isactive')->default(1);
    $table->integer('isblocked')->default(0);
    foreach (['tariff', 'tariff_next_day', 'tariff_same_day', 'tariff_asap', 'tariff_after_hours'] as $column) $table->decimal($column)->default(5);
});
Schema::create('users', function ($table) {
    $table->increments('id');
    foreach (['role', 'pharmacy_id', 'name', 'last_name', 'phone', 'zip'] as $column) $table->string($column)->nullable();
});
foreach (['medicines', 'delivery_methods', 'delivery_times'] as $name) {
    Schema::create($name, function ($table) {
        $table->increments('id');
        $table->string('name');
    });
}
Schema::create('area', function ($table) { $table->increments('id'); $table->decimal('tariff'); });
Schema::create('area_zip', function ($table) { $table->string('zip'); $table->integer('area_id'); });
Schema::create('white_list_ip', function ($table) { $table->string('ip'); });

DB::table('pharmacys')->insert([
    ['id' => 1, 'name' => 'Fixture Pharmacy One', 'address' => 'Test Address', 'zone_id' => 10, 'isactive' => 1, 'isblocked' => 0],
    ['id' => 2, 'name' => 'Fixture Pharmacy Two', 'address' => 'Test Address', 'zone_id' => 20, 'isactive' => 1, 'isblocked' => 0],
    ['id' => 3, 'name' => 'Blocked Fixture Pharmacy', 'address' => 'Test Address', 'zone_id' => 10, 'isactive' => 1, 'isblocked' => 1],
]);
DB::table('users')->insert(['id' => 1, 'role' => 'user', 'pharmacy_id' => 1, 'name' => 'Fixture Patient One', 'zip' => '00001']);
DB::table('users')->insert(['id' => 2, 'role' => 'user', 'pharmacy_id' => 2, 'name' => 'Fixture Patient Two', 'zip' => '00002']);
DB::table('area')->insert(['id' => 1, 'tariff' => 7]);
DB::table('area_zip')->insert(['zip' => '00001', 'area_id' => 1]);

function smokeUser(array $attributes = [])
{
    $user = new RoutingSmokeUser;
    $user->forceFill(array_merge(['id' => 101, 'role' => 'superadmin', 'isactive' => 1, 'isblocked' => 0], $attributes));
    return $user;
}

function requestPage(string $path, ?User $user, string $method = 'GET', array $data = [])
{
    global $app;
    $request = Request::create('http://localhost'.$path, $method, $data);
    $app->instance('request', $request);
    Auth::forgetGuards();
    if ($user) Auth::guard()->setUser($user);
    $request->setUserResolver(function ($guard = null) { return Auth::guard($guard)->user(); });
    $_GET = $request->query->all();
    $_POST = $method === 'POST' ? $data : [];
    try {
        return $app['router']->dispatch($request);
    } catch (Throwable $error) {
        if ((!$error instanceof Symfony\Component\HttpKernel\Exception\HttpExceptionInterface && !$error instanceof Illuminate\Auth\AuthenticationException) || ($error instanceof Symfony\Component\HttpKernel\Exception\HttpExceptionInterface && $error->getStatusCode() >= 500)) {
            fwrite(STDERR, get_class($error).': '.$error->getMessage()."\n");
        }
        return $app->make(ExceptionHandler::class)->render($request, $error);
    }
}

$checks = 0;
function check($condition, string $message)
{
    global $checks;
    if (!$condition) {
        fwrite(STDERR, $message."\n");
        exit(1);
    }
    $checks++;
}

$admin = smokeUser();
foreach (['/orders/add', '/orders/facilitys_add'] as $path) {
    $response = requestPage($path, $admin);
    check($response->getStatusCode() === 200, "$path should render a pharmacy picker (got {$response->getStatusCode()})");
    check(str_contains($response->getContent(), 'Fixture Pharmacy One'), 'Picker must show available pharmacies.');
    check(!str_contains($response->getContent(), 'Blocked Fixture Pharmacy'), 'Picker must exclude blocked pharmacies.');
    $response = requestPage($path.'?pharmacy_id=1', $admin);
    $suffix = $path === '/orders/add' ? 'add' : 'facilitys_add';
    check($response->getStatusCode() === 302 && $response->headers->get('Location') === "http://localhost/orders/1/$suffix", 'Selection must redirect to the scoped form.');
    $response = requestPage($path.'?ajax=1', $admin);
    $sections = json_decode($response->getContent(), true);
    check($response->getStatusCode() === 200 && isset($sections['content'], $sections['title']), 'AJAX navigation must return view sections.');
    check(requestPage($path, null)->headers->get('Location') === 'http://localhost/login', 'Guests must sign in.');
    check(requestPage($path, smokeUser(['role' => 'driver']))->getStatusCode() === 403, 'Drivers must not gain order creation access.');
    check(requestPage($path, smokeUser(['isblocked' => 1]))->getStatusCode() === 403, 'Blocked accounts must remain blocked.');
    check(requestPage($path.'?pharmacy_id=999', $admin)->getStatusCode() === 404, 'Unknown pharmacies must not open a form.');
    $response = requestPage($path.'?pharmacy_id=2', smokeUser(['role' => 'medic', 'pharmacy_id' => 1]));
    check($response->headers->get('Location') === "http://localhost/orders/1/$suffix", 'Medic must stay within their pharmacy.');
}

$response = requestPage('/orders/add', smokeUser(['zone_id' => 10]));
check(!str_contains($response->getContent(), 'Fixture Pharmacy Two'), 'Zone-scoped picker must not expose another zone.');
check(requestPage('/orders/add?pharmacy_id=2', smokeUser(['zone_id' => 10]))->getStatusCode() === 404, 'Zone restriction must apply to submitted selection.');
check(requestPage('/orders/add', smokeUser(['role' => 'admin']))->headers->get('Location') === 'http://localhost/2fa', 'Two-factor authentication must remain enabled.');

foreach (['add', 'facilitys_add'] as $suffix) {
    $response = requestPage('/orders/1/'.$suffix, $admin);
    check($response->getStatusCode() === 200, "Scoped $suffix form must render (got {$response->getStatusCode()}).");
    check(str_contains($response->getContent(), 'name="save"'), 'Scoped form must retain its submit workflow.');
    check(requestPage('/orders/999/'.$suffix, $admin)->getStatusCode() === 404, 'Missing pharmacy must produce a real 404, not a view error.');
    check(requestPage('/orders/2/'.$suffix, smokeUser(['role' => 'medic', 'pharmacy_id' => 1]))->getStatusCode() === 403, 'Scoped form permissions must be preserved.');
}
$response = requestPage('/orders/1/add', $admin);
check(str_contains($response->getContent(), 'Fixture Patient One') && !str_contains($response->getContent(), 'Fixture Patient Two'), 'Order form must load only the selected pharmacy’s patients.');
check(requestPage('/home', $admin)->headers->get('Location') === 'http://localhost', 'Home must redirect to the registered dashboard.');
check(requestPage('/settings/wishes/1/add', $admin)->getStatusCode() === 200, 'Print Text creation must call the existing controller method.');

foreach (['/pharmacys/add', '/offices/add', '/settings/users/add', '/drivers/1/users/add', '/pharmacy/1/users/add', '/patients/1/add', '/patients/1/removed', '/facilitys/1/add'] as $path) {
    $route = $app['router']->getRoutes()->match(Request::create($path));
    [$controller, $action] = explode('@', $route->getActionName());
    check(method_exists($controller, $action), "$path must resolve to a callable action.");
}
foreach (['/orders/1/add', '/orders/1/facilitys_add'] as $path) {
    $route = $app['router']->getRoutes()->match(Request::create($path, 'POST'));
    check(str_ends_with($route->getActionName(), 'Handler'), 'Existing scoped POST handler must remain registered.');
}

DB::table('pharmacys')->delete();
$response = requestPage('/orders/add', $admin);
check($response->getStatusCode() === 200 && str_contains($response->getContent(), 'Add Pharmacy'), 'Empty installations must offer setup instead of a 404.');
echo "$checks page routing checks passed using an isolated SQLite database.\n";
