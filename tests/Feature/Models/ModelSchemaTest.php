<?php

namespace Tests\Feature\Models;

use App\Models\Order;
use App\Models\Pharmacy;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Tests\Support\InsertsRows;
use Tests\TestCase;

/**
 * Every model against the migrated schema: its table exists, its timestamps setting matches the
 * table's columns, and every relationship names columns that exist and can be queried.
 */
class ModelSchemaTest extends TestCase
{
    use InsertsRows;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
    }

    public function test_every_model_matches_its_table_and_every_relationship_can_be_queried(): void
    {
        $relationships = 0;
        $models = $this->models();
        $this->assertGreaterThan(35, count($models));

        foreach ($models as $class) {
            $model = new $class;
            $table = $model->getTable();
            $this->assertTrue(Schema::hasTable($table), "$class: table $table");
            $this->assertSame(
                Schema::hasColumns($table, ['created_at', 'updated_at']),
                $model->usesTimestamps(),
                "$class: timestamps setting against the columns of $table",
            );

            foreach ((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $type = $method->getReturnType();
                if ($method->getFileName() !== (new ReflectionClass($class))->getFileName() || $method->getNumberOfParameters() > 0 || ! $type instanceof ReflectionNamedType || ! is_subclass_of($type->getName(), Relation::class)) {
                    continue;
                }
                $relation = $model->{$method->name}();
                $name = "$class::{$method->name}()";
                if ($relation instanceof BelongsTo) {
                    $this->assertTrue(Schema::hasColumn($table, $relation->getForeignKeyName()), "$name: {$relation->getForeignKeyName()} on $table");
                }
                if ($relation instanceof HasOneOrMany) {
                    $this->assertTrue(Schema::hasColumn($relation->getRelated()->getTable(), $relation->getForeignKeyName()), "$name: foreign key on the related table");
                }
                $relation->getQuery()->toBase()->get();
                $relationships++;
            }
        }

        $this->assertGreaterThan(70, $relationships);
    }

    public function test_an_order_reaches_its_pharmacy_patient_and_route_stop(): void
    {
        $this->row('pharmacys', ['id' => 7, 'name' => 'Corner Pharmacy']);
        $this->row('users', ['id' => 3, 'name' => 'Pat', 'email' => 'pat@example.test', 'password' => 'x', 'role' => 'user', 'pharmacy_id' => 7]);
        $this->row('users', ['id' => 4, 'name' => 'Dee', 'email' => 'dee@example.test', 'password' => 'x', 'role' => 'driver']);
        $this->row('statuses', ['id' => 1, 'name' => 'New']);
        $this->row('delivery_methods', ['id' => 1]);
        $this->row('delivery_times', ['id' => 1]);
        $this->row('orders', ['id' => 20, 'pharmacy_id' => 7, 'user_id' => 3, 'driver_id' => 4, 'statuse_id' => 1, 'delivery_method_id' => 1, 'delivery_time_id' => 1]);
        $this->row('routes_priority', ['driver_id' => 4, 'order_id' => 20, 'priority' => 1]);

        $order = Order::with(['pharmacy', 'user', 'driver', 'status', 'routeStop'])->findOrFail(20);

        $this->assertSame('Corner Pharmacy', $order->pharmacy->name);
        $this->assertSame('Pat', $order->user->name);
        $this->assertSame('Dee', $order->driver->name);
        $this->assertSame('New', $order->status->name);
        $this->assertSame(1, (int) $order->routeStop->priority);
        $this->assertSame([20], Pharmacy::findOrFail(7)->orders->modelKeys());
        $this->assertSame([20], User::findOrFail(4)->deliveries->modelKeys());
        $this->assertSame(7, User::findOrFail(3)->pharmacy->getKey());
    }

    /**
     * @return list<class-string<Model>>
     */
    private function models(): array
    {
        $classes = [];
        foreach (glob(app_path('Models/*.php')) as $file) {
            $class = 'App\\Models\\'.basename($file, '.php');
            if (is_subclass_of($class, Model::class)) {
                $classes[] = $class;
            }
        }

        return $classes;
    }
}
