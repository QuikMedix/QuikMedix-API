<?php

namespace App\Support;

class GeoPoint
{
    /**
     * Query bindings for "POINT(?, ?)" from a stored "lat,lng" location.
     * Unparseable locations become 0,0, which lies inside no delivery area.
     *
     * @return array{0: float, 1: float}
     */
    public static function bindings(?string $location): array
    {
        $parts = explode(',', (string) $location);

        if (count($parts) !== 2 || !is_numeric(trim($parts[0])) || !is_numeric(trim($parts[1]))) {
            return [0.0, 0.0];
        }

        return [(float) trim($parts[0]), (float) trim($parts[1])];
    }
}
