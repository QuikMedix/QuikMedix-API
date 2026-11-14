<?php

namespace Tests\Feature\View;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The dispatch driver page and the pharmacy process page share one stop map:
 * a partial that prints the stop locations and public/js/dispatch-stop-map.js that draws them.
 */
class DispatchStopMapTest extends TestCase
{
    /**
     * The JavaScript array literal a variable is assigned in the rendered partial, as PHP values.
     *
     * @return list<string>
     */
    private function javascriptList(string $html, string $variable): array
    {
        $this->assertSame(1, preg_match('/var '.$variable.' = \[(.*?)\];/s', $html, $match), "$variable is defined once");

        return json_decode('['.rtrim(trim($match[1]), ',').']', true, flags: JSON_THROW_ON_ERROR);
    }

    public function test_only_stops_selected_for_the_map_are_printed_with_their_route_position(): void
    {
        $html = (string) $this->view('dispatching.stop-map-data', [
            'locations' => (object) ['location' => '40.70,-74.00'],
            'patients_locations' => [
                ['id' => 11, 'location' => '40.71,-74.01'],
                ['id' => 12, 'location' => '40.72,-74.02'],
                ['id' => 13, 'location' => ''],
                ['id' => 14, 'location' => '40.74,-74.04'],
            ],
            'pharmacy_locations' => [
                ['id' => 5, 'location' => '40.75,-74.05'],
                ['id' => 6, 'location' => '40.76,-74.06'],
            ],
            'show_ids' => ['patient11', 'patient12', 'patient13', 'pharmacy5'],
            'show_priority' => ['patient11' => 1, 'pharmacy5' => 0],
        ]);

        $this->assertStringContainsString('var locationDriver = "40.70,-74.00";', $html);
        $this->assertSame(['40.71,-74.01', '40.72,-74.02'], $this->javascriptList($html, 'locationPatients'));
        // Stop numbers are one-based; a selected stop without a route position prints "0".
        // Patient 13 has no location but still gets an entry here, as before the extraction.
        $this->assertSame(['11;2', '0', '0'], $this->javascriptList($html, 'locationPatientsID'));
        $this->assertSame(['40.75,-74.05'], $this->javascriptList($html, 'locationPharmacy'));
        $this->assertSame(['5;1'], $this->javascriptList($html, 'locationPharmacyID'));
        $this->assertStringContainsString("function mapCenter() { return locationDriver || locationPharmacy[0] || locationPatients[0] || '40.7128,-74.0060'; }", $html);
    }

    public function test_page_without_a_driver_location_or_selected_stops_prints_empty_data(): void
    {
        $html = (string) $this->view('dispatching.stop-map-data', [
            'locations' => null,
            'patients_locations' => [['id' => 11, 'location' => '40.71,-74.01']],
            'pharmacy_locations' => [['id' => 5, 'location' => '40.75,-74.05']],
            'show_ids' => [],
            'show_priority' => [],
        ]);

        $this->assertStringContainsString('var locationDriver = "";', $html);
        foreach (['locationPatients', 'locationPatientsID', 'locationPharmacy', 'locationPharmacyID'] as $variable) {
            $this->assertSame([], $this->javascriptList($html, $variable));
        }
    }

    public function test_locations_are_escaped_for_the_script(): void
    {
        $html = (string) $this->view('dispatching.stop-map-data', [
            'locations' => (object) ['location' => '"</script><script>alert(1)</script>'],
            'patients_locations' => [], 'pharmacy_locations' => [], 'show_ids' => [], 'show_priority' => [],
        ]);

        $this->assertStringNotContainsString('alert(1)</script>', $html);
        $this->assertStringContainsString('&quot;&lt;/script&gt;', $html);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function pages(): array
    {
        return ['dispatch driver page' => ['dispatching/show'], 'pharmacy process page' => ['process/show']];
    }

    #[DataProvider('pages')]
    public function test_page_loads_the_shared_map_after_its_page_state(string $view): void
    {
        $source = file_get_contents(resource_path("views/$view.blade.php"));

        $state = strpos($source, 'var setup_route = false;');
        $google = strpos($source, 'callback=initMap');
        $data = strpos($source, "@include('dispatching.stop-map-data')");
        $script = strpos($source, "URL::asset('/js/dispatch-stop-map.js");
        $this->assertNotFalse($state);
        $this->assertTrue($state < $google && $google < $data && $data < $script, 'Page state, Maps loader, stop data and the map script keep their order.');
        $this->assertStringContainsString('<div id="map"', $source);
        $this->assertStringNotContainsString('function initMap', $source);
        $this->assertStringNotContainsString('function addMarker', $source);
        $this->assertFileExists(public_path('js/dispatch-stop-map.js'));
    }
}
