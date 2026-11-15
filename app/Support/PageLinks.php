<?php

namespace App\Support;

class PageLinks
{
    /**
     * Up to two pages either side of the current one, in the shape the dashboard list views expect.
     * Earlier pages are linked even when the current page is past the last one, as the lists always did.
     *
     * @return list<array{id: int, class: string}>
     */
    public static function around(int $page, int|float $maxPages): array
    {
        $links = [];
        foreach ([$page - 2, $page - 1] as $earlier) {
            if ($earlier >= 1) {
                $links[] = ['id' => $earlier, 'class' => 'btn-primary'];
            }
        }
        $links[] = ['id' => $page, 'class' => 'btn-outline-primary'];
        foreach ([$page + 1, $page + 2] as $later) {
            if ($later <= $maxPages) {
                $links[] = ['id' => $later, 'class' => 'btn-primary'];
            }
        }

        return $links;
    }
}
