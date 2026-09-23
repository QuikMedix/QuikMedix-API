<?php

namespace App\Support;

use DateTime;

class TimeAgo
{
    /**
     * "3 days ago" style text; $full lists every unit ("1 week, 2 days, 3 hours ago").
     */
    public static function format(?string $datetime, bool $full = false): string
    {
        $diff = (new DateTime)->diff(new DateTime($datetime ?? 'now'));
        $weeks = intdiv($diff->d, 7);

        $parts = [
            'year' => $diff->y,
            'month' => $diff->m,
            'week' => $weeks,
            'day' => $diff->d - $weeks * 7,
            'hour' => $diff->h,
            'minute' => $diff->i,
            'second' => $diff->s,
        ];

        $labels = [];
        foreach ($parts as $unit => $value) {
            if ($value) {
                $labels[] = $value.' '.$unit.($value > 1 ? 's' : '');
            }
        }

        if (!$full) {
            $labels = array_slice($labels, 0, 1);
        }

        return $labels ? implode(', ', $labels).' ago' : 'just now';
    }
}
