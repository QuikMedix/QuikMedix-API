<?php

namespace Tests\Feature\Support;

use App\Support\PageLinks;
use PHPUnit\Framework\TestCase;

class PageLinksTest extends TestCase
{
    /**
     * The block that used to be pasted into every LexaAdmin list action.
     *
     * @return list<array{id: int, class: string}>
     */
    private function pastedBlock(int $page, int|float $max_pages): array
    {
        $pages = [];
        if ($page > 2) {
            array_push($pages, ['id' => $page - 2, 'class' => 'btn-primary']);
        }
        if ($page > 1) {
            array_push($pages, ['id' => $page - 1, 'class' => 'btn-primary']);
        }
        array_push($pages, ['id' => $page, 'class' => 'btn-outline-primary']);
        if ($page + 1 <= $max_pages) {
            array_push($pages, ['id' => $page + 1, 'class' => 'btn-primary']);
        }
        if ($page + 2 <= $max_pages) {
            array_push($pages, ['id' => $page + 2, 'class' => 'btn-primary']);
        }

        return $pages;
    }

    public function test_links_match_the_block_it_replaced_for_every_page_and_page_count(): void
    {
        foreach (range(-3, 12) as $page) {
            foreach ([0, 0.0, 1.0, 2.0, 3.0, 5.0, 9.0, 10] as $maxPages) {
                $this->assertSame($this->pastedBlock($page, $maxPages), PageLinks::around($page, $maxPages), "page $page of $maxPages");
            }
        }
    }

    public function test_current_page_is_outlined_between_its_neighbours(): void
    {
        $this->assertSame([
            ['id' => 2, 'class' => 'btn-primary'],
            ['id' => 3, 'class' => 'btn-primary'],
            ['id' => 4, 'class' => 'btn-outline-primary'],
            ['id' => 5, 'class' => 'btn-primary'],
        ], PageLinks::around(4, 5.0));
    }
}
