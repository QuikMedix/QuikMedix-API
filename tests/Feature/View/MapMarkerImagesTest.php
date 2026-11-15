<?php

namespace Tests\Feature\View;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The Leaflet pages (order detail, live dispatch, tariff map) draw their markers from image files.
 */
class MapMarkerImagesTest extends TestCase
{
    private const PAGES = ['orders/show', 'dispatching/drivers', 'pharmacy/tariff_map'];

    /**
     * Each marker image with the box, in CSS pixels, that the pages draw it in.
     *
     * @return array<string, array{string, int}>
     */
    public static function markers(): array
    {
        return ['pharmacy' => ['i011', 50], 'driver' => ['i022', 50], 'patient' => ['i033', 80], 'driver location' => ['i055', 90]];
    }

    public function test_every_marker_image_a_map_page_asks_for_exists(): void
    {
        $asked = [];
        foreach (self::PAGES as $page) {
            // Marker images are the ones inside Leaflet divIcon markup, which are not draggable.
            preg_match_all('#src="/images/([A-Za-z0-9_-]+\.svg)" draggable="false"#', file_get_contents(resource_path("views/$page.blade.php")), $matches);
            $this->assertNotEmpty($matches[1], "$page draws image markers");
            $asked = array_merge($asked, $matches[1]);
        }

        $asked = array_values(array_unique($asked));
        sort($asked);
        $this->assertSame(['i011.svg', 'i022.svg', 'i033.svg', 'i055.svg'], $asked);
        foreach ($asked as $file) {
            $this->assertFileExists(public_path("images/$file"));
        }
    }

    #[DataProvider('markers')]
    public function test_marker_image_is_a_square_svg_of_the_size_it_is_drawn_at(string $name, int $size): void
    {
        $svg = simplexml_load_string(file_get_contents(public_path("images/$name.svg")));

        $this->assertNotFalse($svg);
        $this->assertSame('svg', $svg->getName());
        $this->assertSame("0 0 $size $size", (string) $svg['viewBox']);
        $this->assertSame([(string) $size, (string) $size], [(string) $svg['width'], (string) $svg['height']]);
        // Self-contained: nothing is loaded from elsewhere and nothing runs.
        $this->assertDoesNotMatchRegularExpression('/<script|href=|url\(/i', $svg->asXML());
    }
}
