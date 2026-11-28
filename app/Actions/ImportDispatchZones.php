<?php

namespace App\Actions;

use App\Models\DispatchZone;
use InvalidArgumentException;

/**
 * Loads dispatch zones from a GeoJSON file of outlines: one zone for each feature, named by its
 * zone_name. Loading the same file again updates the outlines and keeps what was set by hand
 * since: the route prefix, whether the zone is in use, its target stops and its pay per stop.
 */
class ImportDispatchZones
{
    /** Route prefixes for the five boroughs, by county code; other zones get one made from their name. */
    private const array BOROUGH_CODES = ['36047' => 'BK', '36081' => 'QN', '36061' => 'MN', '36005' => 'BX', '36085' => 'SI'];

    /**
     * @param  array<string, mixed>  $geoJson  a decoded FeatureCollection
     * @param  string|null  $state  load only this state, such as NY
     * @param  list<string>  $only  load only the zones with these names
     * @return array{added: int, updated: int, skipped: list<string>}
     */
    public function handle(array $geoJson, ?string $state = null, array $only = []): array
    {
        if (($geoJson['type'] ?? null) !== 'FeatureCollection' || ! is_array($geoJson['features'] ?? null)) {
            throw new InvalidArgumentException('This is not a GeoJSON file of zones: it has no list of features.');
        }
        $only = array_map(mb_strtolower(...), $only);
        $result = ['added' => 0, 'updated' => 0, 'skipped' => []];
        foreach ($geoJson['features'] as $index => $feature) {
            $properties = is_array($feature['properties'] ?? null) ? $feature['properties'] : [];
            $name = trim((string) ($properties['zone_name'] ?? $properties['name'] ?? $properties['county'] ?? ''));
            $zoneState = strtoupper(trim((string) ($properties['state'] ?? '')));
            if (($state !== null && $zoneState !== strtoupper($state)) || ($only !== [] && ! in_array(mb_strtolower($name), $only, true))) {
                continue;
            }
            $label = $name !== '' ? $name : 'Feature '.($index + 1);
            if ($name === '' || strlen($zoneState) !== 2) {
                $result['skipped'][] = "$label: it needs a zone_name and a two-letter state";

                continue;
            }
            $polygons = $this->polygons($feature['geometry'] ?? null);
            if ($polygons === null) {
                $result['skipped'][] = "$label: its outline is not a valid Polygon or MultiPolygon";

                continue;
            }
            $fips = preg_match('/^\d{5}$/', (string) ($properties['county_fips'] ?? '')) ? (string) $properties['county_fips'] : null;
            $zone = $fips !== null
                ? DispatchZone::query()->where('county_fips', $fips)->first()
                : DispatchZone::query()->whereNull('county_fips')->where('name', $name)->where('state', $zoneState)->first();
            $outline = $this->measure($polygons) + [
                'name' => mb_substr($name, 0, 100),
                'state' => $zoneState,
                'county' => isset($properties['county']) ? mb_substr((string) $properties['county'], 0, 100) : null,
                'county_fips' => $fips,
                'boundary' => json_encode($polygons),
            ];
            if ($zone === null) {
                DispatchZone::query()->create($outline + ['code' => $this->code($name, $fips)]);
                $result['added']++;
            } else {
                $zone->update($outline);
                $result['updated']++;
            }
        }

        return $result;
    }

    /**
     * The outline as polygons of rings of [longitude, latitude], or null when it is not one.
     *
     * @return list<list<list<array{0: float, 1: float}>>>|null
     */
    private function polygons(mixed $geometry): ?array
    {
        $coordinates = match ($geometry['type'] ?? null) {
            'Polygon' => [$geometry['coordinates'] ?? null],
            'MultiPolygon' => $geometry['coordinates'] ?? null,
            default => null,
        };
        if (! is_array($coordinates) || $coordinates === []) {
            return null;
        }
        $polygons = [];
        foreach ($coordinates as $polygon) {
            if (! is_array($polygon) || $polygon === []) {
                return null;
            }
            $rings = [];
            foreach ($polygon as $ring) {
                if (! is_array($ring) || count($ring) < 4) {
                    return null;
                }
                $points = [];
                foreach ($ring as $point) {
                    if (! is_array($point) || ! is_numeric($point[0] ?? null) || ! is_numeric($point[1] ?? null) || abs((float) $point[0]) > 180 || abs((float) $point[1]) > 90) {
                        return null;
                    }
                    $points[] = [round((float) $point[0], 6), round((float) $point[1], 6)];
                }
                $rings[] = $points;
            }
            $polygons[] = $rings;
        }

        return $polygons;
    }

    /**
     * The box around the outline and its size, in square degrees: enough to tell which of two overlapping zones is smaller.
     *
     * @param  list<list<list<array{0: float, 1: float}>>>  $polygons
     * @return array{min_lat: float, max_lat: float, min_lng: float, max_lng: float, size: float}
     */
    private function measure(array $polygons): array
    {
        $box = ['min_lat' => 90.0, 'max_lat' => -90.0, 'min_lng' => 180.0, 'max_lng' => -180.0, 'size' => 0.0];
        foreach ($polygons as $polygon) {
            foreach ($polygon as $number => $ring) {
                $twice = 0.0;
                foreach ($ring as $i => [$lng, $lat]) {
                    [$nextLng, $nextLat] = $ring[($i + 1) % count($ring)];
                    $twice += $lng * $nextLat - $nextLng * $lat;
                    $box['min_lat'] = min($box['min_lat'], $lat);
                    $box['max_lat'] = max($box['max_lat'], $lat);
                    $box['min_lng'] = min($box['min_lng'], $lng);
                    $box['max_lng'] = max($box['max_lng'], $lng);
                }
                // The first ring is the land; the others are holes in it.
                $box['size'] += ($number === 0 ? 1 : -1) * abs($twice) / 2;
            }
        }

        return $box;
    }

    /**
     * A short route prefix nobody else has: three letters of the name, other letters of it when
     * those are taken, and a number after them as the last resort.
     */
    private function code(string $name, ?string $fips): string
    {
        $candidates = [];
        if (isset(self::BOROUGH_CODES[$fips])) {
            $candidates[] = self::BOROUGH_CODES[$fips];
        }
        $letters = strtoupper(preg_replace('/[^A-Za-z]/', '', preg_replace('/\s+County$/i', '', $name)) ?: 'ZONE');
        $letters = str_pad($letters, 3, 'X');
        for ($second = 1; $second < strlen($letters) - 1; $second++) {
            for ($third = $second + 1; $third < strlen($letters); $third++) {
                $candidates[] = $letters[0].$letters[$second].$letters[$third];
            }
        }
        for ($number = 2; $number < 1000; $number++) {
            $candidates[] = substr($letters, 0, 3).$number;
        }
        $taken = DispatchZone::query()->pluck('code')->flip();
        foreach ($candidates as $candidate) {
            if (! $taken->has($candidate)) {
                return $candidate;
            }
        }

        throw new InvalidArgumentException("No free route prefix is left for $name.");
    }
}
