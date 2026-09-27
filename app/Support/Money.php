<?php

namespace App\Support;

class Money
{
    /**
     * Whole cents for a dollar amount, as payment APIs expect. Rounds instead of truncating,
     * because 0.29 * 100 is 28.999... in floating point.
     */
    public static function cents(float|int|string $dollars): int
    {
        return (int) round((float) $dollars * 100);
    }
}
