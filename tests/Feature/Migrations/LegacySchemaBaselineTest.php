<?php

namespace Tests\Feature\Migrations;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * With the baseline of the hand-made tables, `php artisan migrate` builds the whole schema on an
 * empty database, and the baseline changes nothing where its tables already exist.
 */
class LegacySchemaBaselineTest extends TestCase
{
    /**
     * Named by the QuickBooks pages but absent from the database the baseline was taken from.
     */
    private const MISSING_IN_SOURCE_DATABASE = ['quickbook'];

    /**
     * @return array<string, array{columns: list<array<int, mixed>>, indexes: list<array<int, mixed>>, foreign: list<array<int, mixed>>}>
     */
    private function baselineTables(): array
    {
        $files = glob(database_path('migrations/*_create_legacy_schema.php'));
        $this->assertCount(1, $files);

        return (new ReflectionClass(require $files[0]))->getConstant('TABLES');
    }

    public function test_a_fresh_database_gets_every_table_and_column_of_the_baseline(): void
    {
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();

        $tables = $this->baselineTables();
        $this->assertGreaterThan(60, count($tables));
        foreach ($tables as $name => $definition) {
            $this->assertTrue(Schema::hasTable($name), "$name exists");
            $this->assertSame([], array_values(array_diff(array_column($definition['columns'], 0), Schema::getColumnListing($name))), "$name has every column");
        }
        // A table made by the baseline has exactly its columns, in order, followed by what later migrations added.
        $addedLater = ['orders' => ['copay_method']];
        foreach (['orders', 'pharmacys', 'routes_priority', 'area', 'action_log'] as $name) {
            $this->assertSame([...array_column($tables[$name]['columns'], 0), ...($addedLater[$name] ?? [])], Schema::getColumnListing($name));
        }
        // users is created by an older migration; the baseline adds what that one lacks.
        $this->assertSame([], array_diff(['role', 'pharmacy_id', 'primary_address', 'location2', 'transport', 'work_now'], Schema::getColumnListing('users')));
    }

    public function test_every_table_the_application_queries_exists_after_migrating(): void
    {
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();

        $named = [];
        foreach (Finder::create()->files()->name('*.php')->in(app_path()) as $file) {
            preg_match_all('/DB::table\(\s*[\'"]([a-z_0-9]+)[\'"]/', $file->getContents(), $matches);
            $named = array_merge($named, $matches[1]);
        }
        $named = array_values(array_unique($named));
        $this->assertGreaterThan(40, count($named));

        $missing = array_values(array_filter($named, fn (string $table): bool => ! Schema::hasTable($table)));
        sort($missing);

        $this->assertSame(self::MISSING_IN_SOURCE_DATABASE, $missing);
    }

    public function test_migrating_again_and_reapplying_the_baseline_changes_nothing(): void
    {
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        DB::table('statuses')->insert(['id' => 1, 'name' => 'New', 'color' => 'blue']);
        $before = [];
        foreach (Schema::getTableListing(schemaQualified: false) as $table) {
            $before[$table] = [Schema::getColumns($table), Schema::getIndexes($table)];
        }

        $files = glob(database_path('migrations/*_create_legacy_schema.php'));
        (require $files[0])->up();
        $this->artisan('migrate', ['--force' => true])->expectsOutputToContain('Nothing to migrate')->assertSuccessful();

        $after = [];
        foreach (Schema::getTableListing(schemaQualified: false) as $table) {
            $after[$table] = [Schema::getColumns($table), Schema::getIndexes($table)];
        }
        $this->assertSame($before, $after);
        $this->assertSame('New', DB::table('statuses')->where('id', 1)->value('name'));
    }

    public function test_delivery_areas_keep_a_geometry_column(): void
    {
        $polygon = collect($this->baselineTables()['area']['columns'])->firstWhere(0, 'polygon');

        // Orders are matched to areas with ST_CONTAINS, which needs geometry, not text.
        $this->assertSame('geometry', $polygon[1]);
    }
}
