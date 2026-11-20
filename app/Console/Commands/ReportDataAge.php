<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Shows, table by table, how many rows are older than a number of months. It only reads: it is
 * for deciding what, if anything, to archive or delete, not for doing it.
 */
class ReportDataAge extends Command
{
    protected $signature = 'quikmedix:data-age {--months=2 : Count rows older than this many months}';

    protected $description = 'Count the rows in each table that are older than a number of months (reads only)';

    /** The column that says when a row was made, in the order tried. */
    private const DATE_COLUMNS = ['created_at', 'created', 'date_created', 'datetime', 'date'];

    private const SKIPPED = ['migrations', 'sqlite_sequence'];

    public function handle(): int
    {
        $months = max(0, (int) $this->option('months'));
        $cutoff = Carbon::now('UTC')->subMonths($months)->toDateTimeString();
        $rows = [];
        foreach (Schema::getTables() as $table) {
            $name = $table['name'];
            if (in_array($name, self::SKIPPED, true)) {
                continue;
            }
            $columns = array_column(Schema::getColumns($name), 'name');
            $dateColumn = collect(self::DATE_COLUMNS)->first(fn (string $column): bool => in_array($column, $columns, true));
            $total = DB::table($name)->count();
            if ($total === 0) {
                continue;
            }
            $rows[] = $dateColumn === null
                ? [$name, $total, 'unknown', '', 'no date column']
                : [$name, $total, DB::table($name)->where($dateColumn, '<', $cutoff)->count(), (string) DB::table($name)->min($dateColumn), $dateColumn];
        }
        usort($rows, fn (array $a, array $b): int => [is_int($b[2]) ? $b[2] : -1, $a[0]] <=> [is_int($a[2]) ? $a[2] : -1, $b[0]]);

        $this->line("Rows made before $cutoff UTC ($months months ago). Nothing is changed.");
        $this->table(['Table', 'Rows', 'Older', 'Oldest', 'Dated by'], $rows);

        return self::SUCCESS;
    }
}
