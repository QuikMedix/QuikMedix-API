<?php

namespace Tests\Feature\Auth;

use App\Support\AccessMessages;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Who gets past the door of every web route: sent to login, refused for the role, refused as a
 * blocked account, or let through to the page. The expected answers are in web-access.json;
 * after an intended change, rerun with UPDATE_ACCESS_MATRIX=1 and review the file's diff.
 */
class WebAccessMatrixTest extends TestCase
{
    private const ROLES = ['guest', 'superadmin', 'admin', 'dispadmin', 'logist', 'medic', 'sale', 'driver', 'user', 'facility', 'blocked'];

    private const FILE = __DIR__.'/web-access.json';

    public function test_every_route_admits_and_refuses_the_same_roles(): void
    {
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        Redis::spy();
        foreach (['pharmacys' => 2] as $table => $count) {
            for ($id = 1; $id <= $count; $id++) {
                $this->row($table, ['id' => $id, 'isactive' => 1, 'isblocked' => 0]);
            }
        }
        $users = [];
        foreach (array_slice(self::ROLES, 1) as $index => $role) {
            $id = $this->row('users', [
                'id' => $index + 1, 'name' => ucfirst($role), 'email' => "$role@example.test", 'password' => 'x',
                'role' => $role === 'blocked' ? 'admin' : $role, 'isactive' => 1, 'isblocked' => $role === 'blocked' ? 1 : 0,
                'pharmacy_id' => in_array($role, ['medic', 'user', 'facility'], true) ? 1 : 0,
            ]);
            $users[$role] = User::findOrFail($id);
        }

        $matrix = [];
        foreach ($this->webRoutes() as $key => [$method, $uri]) {
            $answers = [];
            foreach (self::ROLES as $role) {
                $this->flushSession();
                $this->app['auth']->forgetGuards();
                if ($role !== 'guest') {
                    $this->actingAs($users[$role])->withSession(['user_2fa' => $users[$role]->id]);
                }
                $response = $this->call($method, $uri);
                $message = $response->exception?->getMessage();
                $answers[match (true) {
                    $response->isRedirect() && str_ends_with((string) $response->headers->get('Location'), '/login') => 'login',
                    $response->getStatusCode() === 403 && $message === AccessMessages::ACCOUNT_INACTIVE => 'blocked account',
                    $response->getStatusCode() === 403 && $message === AccessMessages::NO_PERMISSION => 'no permission',
                    $response->getStatusCode() === 403 => 'refused',
                    default => 'through',
                }][] = $role;
            }
            unset($answers['through']);
            $matrix[$key] = array_map(fn (array $roles): string => implode(' ', $roles), $answers);
        }

        if (getenv('UPDATE_ACCESS_MATRIX')) {
            file_put_contents(self::FILE, json_encode($matrix, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
        }

        $this->assertGreaterThan(200, count($matrix));
        $this->assertSame(json_decode((string) file_get_contents(self::FILE), true), $matrix);
    }

    /**
     * Every route of the web group that an application controller serves, with 1 for each parameter.
     *
     * @return array<string, array{string, string}>
     */
    private function webRoutes(): array
    {
        $routes = [];
        foreach (Route::getRoutes() as $route) {
            if (! str_starts_with($route->getActionName(), 'App\\Http\\Controllers\\') || ! in_array('web', $route->gatherMiddleware(), true) || str_contains($route->uri(), 'logout')) {
                continue;
            }
            foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                $uri = '/'.ltrim((string) preg_replace('/\{[^}]+\}/', '1', $route->uri()), '/');
                $routes["$method $uri"] = [$method, $uri];
            }
        }
        ksort($routes);

        return $routes;
    }

    /**
     * Inserts a row, giving every required column without a default a value of its type.
     *
     * @param array<string, mixed> $values
     */
    private function row(string $table, array $values): int
    {
        foreach (Schema::getColumns($table) as $column) {
            if (array_key_exists($column['name'], $values) || $column['nullable'] || $column['default'] !== null || $column['auto_increment']) {
                continue;
            }
            $type = strtolower($column['type_name']);
            $values[$column['name']] = match (true) {
                str_contains($type, 'int'), in_array($type, ['float', 'double', 'decimal', 'numeric', 'real'], true) => 1,
                $type === 'date' => '2026-01-05',
                in_array($type, ['datetime', 'timestamp'], true) => '2026-01-05 10:00:00',
                default => 'x',
            };
        }

        return (int) DB::table($table)->insertGetId($values);
    }
}
