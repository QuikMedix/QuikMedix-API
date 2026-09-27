<?php

namespace App\Support;

class WktPolygon
{
    /**
     * The points of a stored "POLYGON((x y, x y, ...))" as a JSON array of [x, y] pairs, for the map views.
     * The closing point, which repeats the first, is dropped.
     */
    public static function toJsonPoints(?string $polygon): string
    {
        $rows = explode(',', str_replace(['POLYGON((', '))'], '', (string) $polygon));
        $points = [];
        foreach (array_slice($rows, 0, -1) as $row) {
            $coordinates = explode(' ', $row);
            if (count($coordinates) > 1) {
                $points[] = [floatval($coordinates[0]), floatval($coordinates[1])];
            }
        }

        return json_encode($points);
    }
}
