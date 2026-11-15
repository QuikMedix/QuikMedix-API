<?php

namespace App\Console\Commands;

use App\Support\SchemaBaseline;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * Most of this application's tables were created by hand and have no migration, so a fresh
 * installation cannot build them. Run against a database that has them, this writes one
 * migration that recreates every table and column of that database when it is missing.
 */
class WriteSchemaBaseline extends Command
{
    private const SUFFIX = '_create_legacy_schema.php';

    protected $signature = 'quikmedix:schema-baseline
        {--path=database/migrations : Folder the migration is written to}
        {--except= : Tables to leave out, comma separated; * stands for any characters}
        {--force : Replace a baseline migration that already exists}';

    protected $description = 'Write a migration that recreates the tables of the connected database when they are missing';

    public function handle(): int
    {
        $folder = base_path($this->option('path'));
        $existing = File::glob($folder.'/*'.self::SUFFIX);
        if ($existing !== [] && ! $this->option('force')) {
            $this->error('A baseline already exists: '.basename($existing[0]).'. Use --force to replace it.');

            return self::FAILURE;
        }

        $connection = DB::connection();
        $except = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('except')))));
        $baseline = SchemaBaseline::describe(Schema::connection($connection->getName()), [config('database.migrations.table', 'migrations'), ...$except]);
        if ($baseline['tables'] === []) {
            $this->error('The connected database has no tables, so there is nothing to describe.');

            return self::FAILURE;
        }

        $file = $existing[0] ?? $folder.'/'.now()->format('Y_m_d_His').self::SUFFIX;
        File::ensureDirectoryExists($folder);
        $source = 'the '.$connection->getDriverName().' database "'.$connection->getDatabaseName().'" on '.now()->toDateString();
        File::put($file, SchemaBaseline::render($baseline['tables'], $source, $except));

        $columns = array_sum(array_map(fn (array $table): int => count($table['columns']), $baseline['tables']));
        $this->info('Wrote '.count($baseline['tables'])." tables and $columns columns to ".str_replace(base_path().'/', '', $file));
        foreach ($baseline['warnings'] as $warning) {
            $this->warn("  $warning");
        }
        if (! $this->replays($file, $baseline['tables'])) {
            return self::FAILURE;
        }
        $this->line('Review the file and commit it. `php artisan migrate` records it without changing a database that already has these tables.');

        return self::SUCCESS;
    }

    /**
     * Builds the written migration on an empty in-memory SQLite database and compares the result
     * with what was described, so a file that cannot run is noticed here and not on a new server.
     *
     * @param  array<string, array{columns: list<array<int, mixed>>, indexes: list<array<int, mixed>>, foreign: list<array<int, mixed>>}>  $tables
     */
    private function replays(string $file, array $tables): bool
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->warn('The file was not test-run because the pdo_sqlite extension is not loaded.');

            return true;
        }

        config(['database.connections.schema_baseline_replay' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false]]);
        try {
            $schema = Schema::connection('schema_baseline_replay');
            (require $file)->apply($schema);
            foreach ($tables as $name => $definition) {
                $expected = array_column($definition['columns'], 0);
                if ($schema->getColumnListing($name) !== $expected) {
                    $this->error("Test run on an empty SQLite database: $name came out with different columns.");

                    return false;
                }
            }
        } catch (\Throwable $exception) {
            $this->error('Test run on an empty SQLite database failed: '.$exception->getMessage());

            return false;
        } finally {
            DB::purge('schema_baseline_replay');
        }
        $this->info('Test run on an empty SQLite database rebuilt all '.count($tables).' tables with the same columns.');

        return true;
    }
}
