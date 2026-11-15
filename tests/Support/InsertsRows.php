<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait InsertsRows
{
    /**
     * Inserts a row, filling every required column the caller left out with a harmless value.
     *
     * @param  array<string, mixed>  $values
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
