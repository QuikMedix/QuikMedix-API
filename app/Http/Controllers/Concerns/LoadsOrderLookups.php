<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * Lookup tables shown alongside orders. Small reference tables are cached in Redis for an hour per host;
 * people are loaded only for the ids a page actually shows.
 */
trait LoadsOrderLookups
{
    private static function get_cached_value(string $key): mixed
    {
        $cached = Redis::get($key);
        if (! is_string($cached) || $cached === '') {
            return null;
        }
        try {
            return unserialize($cached);
        } catch (\Throwable) {
            Redis::del($key);

            return null;
        }
    }

    /**
     * A whole reference table keyed by id, from the Redis cache when present.
     */
    private static function cachedTable(string $table): Collection
    {
        $key = request()->getHttpHost().':'.$table;
        $rows = self::get_cached_value($key);
        if (empty($rows)) {
            $rows = DB::table($table)->get()->keyBy('id');
            Redis::setex($key, 3600, serialize($rows));
        }

        return $rows;
    }

    static function get_statuses(): Collection
    {
        return self::cachedTable('statuses');
    }

    static function get_statuses_copay(): Collection
    {
        return self::cachedTable('statuses_copay');
    }

    static function get_delivery_methods(): Collection
    {
        return self::cachedTable('delivery_methods');
    }

    static function get_delivery_times(): Collection
    {
        return self::cachedTable('delivery_times');
    }

    /**
     * The listed pharmacies, keyed by id.
     *
     * @param  array<int, int|string|null>  $ids
     */
    static function get_pharmacys(array $ids): Collection
    {
        return DB::table('pharmacys')->whereIn('id', array_filter(array_unique($ids)))->select('id', 'name', 'phone')->get()->keyBy('id');
    }

    /**
     * The listed patients and facilities, keyed by id.
     *
     * @param  array<int, int|string|null>  $ids
     */
    static function get_patients(array $ids): Collection
    {
        return DB::table('users')->whereIn('id', array_filter(array_unique($ids)))->select('id', 'name', 'last_name', 'phone', 'os', 'address', 'zip', 'apartment')->whereIn('role', ['user', 'facility'])->get()->keyBy('id');
    }

    /**
     * The listed drivers, keyed by id.
     *
     * @param  array<int, int|string|null>  $ids
     */
    static function get_drivers(array $ids): Collection
    {
        return DB::table('users')->whereIn('id', array_filter(array_unique($ids)))->select('id', 'name', 'last_name', 'phone', 'pharmacy_id')->where('role', 'driver')->get()->keyBy('id');
    }

    /**
     * @return list<string>
     */
    static function get_wishs(): array
    {
        $key = request()->getHttpHost().':orders_wishs';
        $wishes = self::get_cached_value($key);
        if (empty($wishes)) {
            $wishes = DB::table('wishes')->join('wishes_category', 'wishes.category_id', '=', 'wishes_category.id')->where('wishes_category.status', 1)->pluck('wishes.text')->toArray();
            Redis::setex($key, 3600, serialize($wishes));
        }

        return $wishes;
    }
}
