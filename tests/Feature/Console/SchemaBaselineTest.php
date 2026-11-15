<?php

namespace Tests\Feature\Console;

use App\Support\SchemaBaseline;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * quikmedix:schema-baseline writes one migration that rebuilds the hand-made tables of the
 * connected database wherever they are missing, and changes nothing where they exist.
 */
class SchemaBaselineTest extends TestCase
{
    private const FOLDER = 'storage/framework/testing/schema-baseline';

    protected function setUp(): void
    {
        parent::setUp();
        File::deleteDirectory(base_path(self::FOLDER));
        config(['database.connections.fresh' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(base_path(self::FOLDER));
        DB::purge('fresh');
        parent::tearDown();
    }

    private function legacyDatabase(): void
    {
        Schema::create('migrations', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('migration');
        });
        Schema::create('orders', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('name', 100)->nullable();
            $table->tinyInteger('statuse_id')->default(0);
            $table->decimal('copay', 8, 2)->default('0.00');
            $table->string('delivery_type')->default("driver's choice");
            $table->text('note')->nullable();
            $table->dateTime('created')->nullable();
            $table->timestamp('updated')->useCurrent();
            $table->index('user_id', 'orders_user_id_index');
            $table->unique(['user_id', 'name'], 'orders_user_name_unique');
        });
        Schema::create('order_items', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedInteger('order_id');
            $table->string('sku')->default('n/a');
            $table->foreign('order_id', 'order_items_order_id_foreign')->references('id')->on('orders')->onDelete('cascade');
        });
        Schema::create('area_zip', function (Blueprint $table): void {
            $table->string('zip');
            $table->integer('area_id');
            $table->primary(['zip', 'area_id']);
        });
    }

    /**
     * @return array<string, mixed> everything Laravel can tell about the tables, apart from the migrations table
     */
    private function shape(Builder $schema): array
    {
        $shape = [];
        foreach ($schema->getTableListing(schemaQualified: false) as $table) {
            if ($table === 'migrations' || str_starts_with($table, 'sqlite_')) {
                continue;
            }
            $indexes = $schema->getIndexes($table);
            usort($indexes, fn (array $a, array $b): int => strcmp(implode(',', $a['columns']), implode(',', $b['columns'])));
            $shape[$table] = [
                'columns' => $schema->getColumns($table),
                // SQLite names a primary key index itself, so names are compared for the others only.
                'indexes' => array_map(fn (array $index): array => [$index['primary'] ? 'primary' : $index['name'], $index['columns'], $index['unique']], $indexes),
                'foreign' => array_map(fn (array $key): array => [$key['columns'], $key['foreign_table'], $key['foreign_columns'], $key['on_delete']], $schema->getForeignKeys($table)),
            ];
        }
        ksort($shape);

        return $shape;
    }

    private function writtenMigration(): string
    {
        $files = File::glob(base_path(self::FOLDER).'/*_create_legacy_schema.php');
        $this->assertCount(1, $files);

        return $files[0];
    }

    public function test_written_migration_rebuilds_the_same_tables_on_an_empty_database(): void
    {
        $this->legacyDatabase();

        $this->artisan('quikmedix:schema-baseline', ['--path' => self::FOLDER])
            ->expectsOutputToContain('Wrote 3 tables and 14 columns to '.self::FOLDER.'/')
            ->expectsOutputToContain('Test run on an empty SQLite database rebuilt all 3 tables with the same columns.')
            ->assertExitCode(0);

        $migration = require $this->writtenMigration();
        $migration->apply(Schema::connection('fresh'));

        $this->assertSame(['area_zip', 'order_items', 'orders'], array_keys($this->shape(Schema::connection('fresh'))));
        $this->assertSame($this->shape(Schema::connection(null)), $this->shape(Schema::connection('fresh')));
        // A table is written after the table its foreign key points to.
        $source = File::get($this->writtenMigration());
        $this->assertLessThan(strpos($source, "'order_items' => ["), strpos($source, "'orders' => ["));
        $this->assertStringNotContainsString("'migrations' => [", $source);
    }

    public function test_on_the_database_it_came_from_the_migration_changes_nothing(): void
    {
        $this->legacyDatabase();
        DB::table('orders')->insert(['user_id' => 7, 'name' => 'Kept']);
        $before = $this->shape(Schema::connection(null));
        $this->artisan('quikmedix:schema-baseline', ['--path' => self::FOLDER])->assertExitCode(0);

        $migration = require $this->writtenMigration();
        $migration->apply(Schema::connection(null));
        $migration->down();

        $this->assertSame($before, $this->shape(Schema::connection(null)));
        $this->assertSame(1, DB::table('orders')->where('name', 'Kept')->count());
    }

    public function test_columns_missing_from_an_existing_table_are_added_and_rows_are_kept(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('role')->default('user');
            $table->integer('pharmacy_id');
            $table->string('phone')->nullable();
        });
        $this->artisan('quikmedix:schema-baseline', ['--path' => self::FOLDER])->assertExitCode(0);
        // A newer installation where an ordinary migration made a smaller users table.
        Schema::connection('fresh')->create('users', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
        });
        DB::connection('fresh')->table('users')->insert(['name' => 'Casey']);

        $migration = require $this->writtenMigration();
        $migration->apply(Schema::connection('fresh'));
        $migration->apply(Schema::connection('fresh'));

        $this->assertSame(['id', 'name', 'role', 'pharmacy_id', 'phone'], Schema::connection('fresh')->getColumnListing('users'));
        $this->assertSame(['id' => 1, 'name' => 'Casey', 'role' => 'user', 'pharmacy_id' => null, 'phone' => null], (array) DB::connection('fresh')->table('users')->first());
    }

    public function test_an_existing_baseline_is_not_replaced_unless_forced(): void
    {
        $this->legacyDatabase();
        $this->artisan('quikmedix:schema-baseline', ['--path' => self::FOLDER])->assertExitCode(0);
        $file = $this->writtenMigration();
        File::put($file, '<?php // reviewed');

        $this->artisan('quikmedix:schema-baseline', ['--path' => self::FOLDER])
            ->expectsOutputToContain('A baseline already exists: '.basename($file).'. Use --force to replace it.')->assertExitCode(1);
        $this->assertSame('<?php // reviewed', File::get($file));

        $this->artisan('quikmedix:schema-baseline', ['--path' => self::FOLDER, '--force' => true])->assertExitCode(0);
        $this->assertSame($file, $this->writtenMigration());
        $this->assertStringContainsString("'orders' => [", File::get($file));
    }

    public function test_a_database_without_tables_writes_nothing(): void
    {
        $this->artisan('quikmedix:schema-baseline', ['--path' => self::FOLDER])
            ->expectsOutputToContain('The connected database has no tables')->assertExitCode(1);

        $this->assertSame([], File::glob(base_path(self::FOLDER).'/*'));
    }

    /**
     * Columns as MySQL 8 and MariaDB report them through Schema::getColumns().
     *
     * @return array<string, array{array<string, mixed>, array<int, mixed>}>
     */
    public static function mysqlColumns(): array
    {
        $column = fn (string $type, array $more = []): array => $more + ['name' => 'c', 'type_name' => strtok($type, '( '), 'type' => $type, 'nullable' => false, 'default' => null, 'auto_increment' => false, 'generation' => null];

        return [
            'auto-increment key' => [$column('int(11)', ['auto_increment' => true]), ['c', 'integer', [], 'autoIncrement']],
            'unsigned big key' => [$column('bigint unsigned', ['auto_increment' => true]), ['c', 'bigInteger', [], 'unsigned autoIncrement']],
            'flag with default' => [$column('tinyint(1)', ['default' => '0']), ['c', 'tinyInteger', [], '', 0]],
            'nullable text' => [$column('varchar(255)', ['nullable' => true]), ['c', 'string', [255], 'nullable']],
            'text default, MySQL 8' => [$column('varchar(20)', ['default' => 'user']), ['c', 'string', [20], '', 'user']],
            'text default, MariaDB' => [$column('varchar(20)', ['default' => "'it''s'"]), ['c', 'string', [20], '', "it's"]],
            'explicit null, MariaDB' => [$column('varchar(20)', ['nullable' => true, 'default' => 'NULL']), ['c', 'string', [20], 'nullable']],
            'money' => [$column('decimal(8,2)', ['default' => '0.00']), ['c', 'decimal', [8, 2], '', '0.00']],
            'double' => [$column('double', ['nullable' => true]), ['c', 'double', [], 'nullable']],
            'long text' => [$column('longtext', ['nullable' => true]), ['c', 'longText', [], 'nullable']],
            'timestamp now, MySQL' => [$column('timestamp', ['default' => 'CURRENT_TIMESTAMP']), ['c', 'timestamp', [], 'useCurrent']],
            'timestamp now, MariaDB' => [$column('timestamp', ['default' => 'current_timestamp()']), ['c', 'timestamp', [], 'useCurrent']],
            'precise datetime' => [$column('datetime(6)', ['nullable' => true]), ['c', 'dateTime', [6], 'nullable']],
            'date' => [$column('date', ['nullable' => true]), ['c', 'date', [], 'nullable']],
            'enum' => [$column("enum('car','bicycle','driver''s')", ['default' => 'car']), ['c', 'enum', [['car', 'bicycle', "driver's"]], '', 'car']],
            'number stored as text' => [$column('varchar(10)', ['default' => '0']), ['c', 'string', [10], '', '0']],
        ];
    }

    /**
     * @param array<string, mixed> $column
     * @param array<int, mixed> $expected
     */
    #[DataProvider('mysqlColumns')]
    public function test_mysql_columns_are_described(array $column, array $expected): void
    {
        $this->assertSame([$expected, null], SchemaBaseline::column($column));
    }

    public function test_what_cannot_be_reproduced_is_reported(): void
    {
        [$definition, $warning] = SchemaBaseline::column(['name' => 'area', 'type_name' => 'polygon', 'type' => 'polygon', 'nullable' => true, 'default' => null, 'auto_increment' => false, 'generation' => null]);
        $this->assertSame(['area', 'text', [], 'nullable'], $definition);
        $this->assertSame('type polygon is not known and is written as text', $warning);

        Schema::create('notes', function (Blueprint $table): void {
            $table->increments('id');
            $table->text('note');
            $table->index('note', 'notes_note_index');
        });
        $this->artisan('quikmedix:schema-baseline', ['--path' => self::FOLDER])
            ->expectsOutputToContain('notes: index notes_note_index is left out because it covers a text or binary column (note)')
            ->assertExitCode(0);
    }

    public function test_mysql_only_features_do_not_stop_the_migration_on_sqlite(): void
    {
        $tables = ['articles' => [
            'columns' => [['id', 'integer', [], 'unsigned autoIncrement'], ['kind', 'enum', [['news', 'faq']], '', 'news'], ['body', 'longText', [], 'nullable']],
            'indexes' => [['fullText', ['body'], 'articles_body_fulltext'], ['index', ['kind'], 'articles_kind_index']],
            'foreign' => [],
        ]];
        File::ensureDirectoryExists(base_path(self::FOLDER));
        File::put($file = base_path(self::FOLDER).'/2026_01_01_000000_create_legacy_schema.php', SchemaBaseline::render($tables, 'a test'));

        (require $file)->apply(Schema::connection('fresh'));

        $this->assertSame(['id', 'kind', 'body'], Schema::connection('fresh')->getColumnListing('articles'));
        $this->assertSame(['articles_kind_index'], array_values(array_filter(array_column(Schema::connection('fresh')->getIndexes('articles'), 'name'), fn (string $name): bool => $name !== 'primary')));
    }
}
