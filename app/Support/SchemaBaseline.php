<?php

namespace App\Support;

use Illuminate\Database\Schema\Builder;

/**
 * Describes the tables of a live database as data a migration can replay, so the tables that
 * were created by hand before migrations existed can be built on a fresh installation.
 *
 * describe() reads a connection, render() writes the migration. The migration creates a table
 * that is missing, adds columns that are missing from a table that exists, and otherwise
 * changes nothing, so on the database it was generated from it is a no-op.
 */
class SchemaBaseline
{
    /** MySQL and SQLite type names with the Blueprint method that recreates them. */
    private const TYPES = [
        'tinyint' => 'tinyInteger', 'smallint' => 'smallInteger', 'mediumint' => 'mediumInteger',
        'int' => 'integer', 'integer' => 'integer', 'bigint' => 'bigInteger',
        'varchar' => 'string', 'char' => 'char',
        'tinytext' => 'tinyText', 'text' => 'text', 'mediumtext' => 'mediumText', 'longtext' => 'longText',
        'decimal' => 'decimal', 'numeric' => 'decimal', 'float' => 'float', 'double' => 'double', 'real' => 'double',
        'date' => 'date', 'datetime' => 'dateTime', 'timestamp' => 'timestamp', 'time' => 'time', 'year' => 'year',
        'enum' => 'enum', 'set' => 'set', 'json' => 'json', 'boolean' => 'boolean',
        'tinyblob' => 'binary', 'blob' => 'binary', 'mediumblob' => 'binary', 'longblob' => 'binary', 'binary' => 'binary', 'varbinary' => 'binary',
    ];

    private const INTEGERS = ['tinyInteger', 'smallInteger', 'mediumInteger', 'integer', 'bigInteger', 'boolean'];

    private const UNINDEXABLE = ['tinyText', 'text', 'mediumText', 'longText', 'binary', 'json'];

    /**
     * @param  list<string>  $except  tables to leave out, such as the migrations table
     * @return array{tables: array<string, array{columns: list<array<int, mixed>>, indexes: list<array<int, mixed>>, foreign: list<array<int, mixed>>}>, warnings: list<string>}
     */
    public static function describe(Builder $schema, array $except = []): array
    {
        $tables = [];
        $warnings = [];
        $references = [];
        foreach ($schema->getTableListing(schemaQualified: false) as $name) {
            if (in_array($name, $except, true) || str_starts_with($name, 'sqlite_')) {
                continue;
            }

            $columns = [];
            $types = [];
            $autoIncrement = null;
            foreach ($schema->getColumns($name) as $column) {
                [$definition, $warning] = self::column($column);
                if ($warning !== null) {
                    $warnings[] = "$name.{$column['name']}: $warning";
                }
                $columns[] = $definition;
                $types[$column['name']] = $definition[1];
                if (str_contains($definition[3], 'autoIncrement')) {
                    $autoIncrement = $column['name'];
                }
            }

            $indexes = [];
            foreach ($schema->getIndexes($name) as $index) {
                if ($index['primary'] && $index['columns'] === [$autoIncrement]) {
                    continue; // an auto-increment column is created as the primary key
                }
                $unindexable = array_values(array_filter($index['columns'], fn (string $column): bool => in_array($types[$column] ?? null, self::UNINDEXABLE, true)));
                if (($index['type'] ?? '') !== 'fulltext' && $unindexable !== []) {
                    $warnings[] = "$name: index {$index['name']} is left out because it covers a text or binary column (".implode(', ', $unindexable).') and its prefix length is not known';

                    continue;
                }
                $kind = match (true) {
                    $index['primary'] => 'primary',
                    ($index['type'] ?? '') === 'fulltext' => 'fullText',
                    $index['unique'] => 'unique',
                    default => 'index',
                };
                $indexes[] = [$kind, $index['columns'], $index['primary'] ? null : $index['name']];
            }

            $foreign = [];
            foreach ($schema->getForeignKeys($name) as $key) {
                $foreign[] = [$key['columns'], $key['foreign_table'], $key['foreign_columns'], strtolower($key['on_delete']), strtolower($key['on_update']), $key['name']];
                if ($key['foreign_table'] !== $name) {
                    $references[$name][] = $key['foreign_table'];
                }
            }

            $tables[$name] = ['columns' => $columns, 'indexes' => $indexes, 'foreign' => $foreign];
        }

        return ['tables' => self::referencedFirst($tables, $references, $warnings), 'warnings' => $warnings];
    }

    /**
     * One column as [name, Blueprint method, arguments, flags, default?] and a warning when
     * something about it cannot be reproduced. The input is one row of Schema::getColumns().
     *
     * @param  array<string, mixed>  $column
     * @return array{0: array<int, mixed>, 1: string|null}
     */
    public static function column(array $column): array
    {
        preg_match('/^\s*(\w+)\s*(?:\(([^)]*)\))?(.*)$/s', strtolower((string) $column['type']), $match);
        $base = $match[1] ?? strtolower((string) ($column['type_name'] ?? ''));
        $parameters = array_values(array_filter(array_map('trim', explode(',', $match[2] ?? '')), fn (string $value): bool => $value !== ''));
        $warning = null;

        $method = self::TYPES[$base] ?? null;
        if ($method === null) {
            $method = 'text';
            $warning = "type {$column['type']} is not known and is written as text";
        }
        if (! empty($column['generation'])) {
            $warning = 'generated column is written as a plain column';
        }

        $arguments = match ($method) {
            'string', 'char' => isset($parameters[0]) ? [(int) $parameters[0]] : [],
            'decimal' => count($parameters) === 2 ? [(int) $parameters[0], (int) $parameters[1]] : [],
            'dateTime', 'timestamp', 'time' => isset($parameters[0]) && (int) $parameters[0] > 0 ? [(int) $parameters[0]] : [],
            'enum', 'set' => [self::quotedList((string) preg_replace('/^\s*\w+\s*\((.*)\)[^)]*$/s', '$1', (string) $column['type']))],
            default => [],
        };

        $flags = [];
        if (! empty($column['nullable'])) {
            $flags[] = 'nullable';
        }
        if (str_contains($match[3] ?? '', 'unsigned')) {
            $flags[] = 'unsigned';
        }
        if (! empty($column['auto_increment'])) {
            $flags[] = 'autoIncrement';
        }

        $definition = [$column['name'], $method, $arguments];
        $default = $column['default'] ?? null;
        if ($default !== null && strtoupper((string) $default) !== 'NULL' && empty($column['auto_increment'])) {
            $default = (string) $default;
            if (preg_match('/^current_timestamp(\(\d*\))?$/i', $default) === 1) {
                $flags[] = 'useCurrent';
            } else {
                // MariaDB and SQLite report text defaults in quotes, MySQL 8 without them.
                if (preg_match("/^'(.*)'$/s", $default, $quoted) === 1) {
                    $default = str_replace("''", "'", $quoted[1]);
                }
                $definition[4] = in_array($method, self::INTEGERS, true) && is_numeric($default) ? (int) $default : $default;
            }
        }
        $definition[3] = implode(' ', $flags);
        ksort($definition);

        return [$definition, $warning];
    }

    /**
     * The migration file's source for these tables.
     *
     * @param  array<string, array{columns: list<array<int, mixed>>, indexes: list<array<int, mixed>>, foreign: list<array<int, mixed>>}>  $tables
     */
    public static function render(array $tables, string $source): string
    {
        $body = '';
        foreach ($tables as $name => $definition) {
            $body .= '        '.var_export($name, true)." => [\n";
            foreach (['columns', 'indexes', 'foreign'] as $part) {
                $body .= "            '$part' => [".($definition[$part] === [] ? '' : "\n");
                foreach ($definition[$part] as $row) {
                    $body .= '                '.self::export($row).",\n";
                }
                $body .= ($definition[$part] === [] ? '' : '            ')."],\n";
            }
            $body .= "        ],\n";
        }

        return str_replace(['{{ source }}', "{{ tables }}\n"], [$source, $body], (string) file_get_contents(base_path('stubs/legacy-schema-migration.stub')));
    }

    private static function export(mixed $value): string
    {
        if (is_array($value)) {
            return '['.implode(', ', array_map(self::export(...), $value)).']';
        }

        return $value === null ? 'null' : var_export($value, true);
    }

    /**
     * @return list<string>
     */
    private static function quotedList(string $list): array
    {
        preg_match_all("/'((?:[^']|'')*)'/", $list, $matches);

        return array_map(fn (string $value): string => str_replace("''", "'", $value), $matches[1]);
    }

    /**
     * Tables ordered so that a table comes after the tables its foreign keys point to. Keys that
     * form a circle cannot be created together with their tables and are left out with a warning.
     *
     * @param  array<string, array{columns: list<array<int, mixed>>, indexes: list<array<int, mixed>>, foreign: list<array<int, mixed>>}>  $tables
     * @param  array<string, list<string>>  $references
     * @param  list<string>  $warnings
     * @return array<string, array{columns: list<array<int, mixed>>, indexes: list<array<int, mixed>>, foreign: list<array<int, mixed>>}>
     */
    private static function referencedFirst(array $tables, array $references, array &$warnings): array
    {
        ksort($tables);
        $ordered = [];
        while (count($ordered) < count($tables)) {
            $placed = false;
            foreach ($tables as $name => $definition) {
                if (isset($ordered[$name])) {
                    continue;
                }
                $waiting = array_filter($references[$name] ?? [], fn (string $other): bool => isset($tables[$other]) && ! isset($ordered[$other]));
                if ($waiting === []) {
                    $ordered[$name] = $definition;
                    $placed = true;
                }
            }
            if (! $placed) {
                foreach ($tables as $name => $definition) {
                    if (! isset($ordered[$name])) {
                        $warnings[] = "$name: foreign keys are left out because they refer to each other in a circle";
                        $ordered[$name] = ['foreign' => []] + $definition;
                    }
                }
            }
        }

        return $ordered;
    }
}
