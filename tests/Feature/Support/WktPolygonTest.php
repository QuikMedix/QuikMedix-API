<?php

namespace Tests\Feature\Support;

use App\Support\WktPolygon;
use PHPUnit\Framework\TestCase;

class WktPolygonTest extends TestCase
{
    public function test_converts_a_stored_polygon_to_map_points_without_the_closing_point(): void
    {
        $this->assertSame('[[40.1,-73.2],[40.3,-73.4],[40.5,-73.1]]', WktPolygon::toJsonPoints('POLYGON((40.1 -73.2,40.3 -73.4,40.5 -73.1,40.1 -73.2))'));
    }

    public function test_an_empty_polygon_has_no_points(): void
    {
        $this->assertSame('[]', WktPolygon::toJsonPoints(null));
    }
}
