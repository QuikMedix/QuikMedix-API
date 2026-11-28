<?php

namespace App\Console\Commands;

use App\Actions\ImportDispatchZones as Import;
use App\Support\DispatchZones;
use Illuminate\Console\Command;
use InvalidArgumentException;

/**
 * Loads the dispatch zones from a GeoJSON file, by default the New York and New Jersey counties
 * that come with the project, and gives every order still waiting for a driver its zone.
 */
class ImportDispatchZones extends Command
{
    protected $signature = 'quikmedix:import-zones
        {file? : A GeoJSON file of zone outlines; without it, the NY and NJ counties in database/data}
        {--state= : Load only this state, such as NY}
        {--only=* : Load only the zone with this name; repeat it for several}';

    protected $description = 'Load dispatch zones from a GeoJSON file and give waiting orders their zone';

    public function handle(Import $import): int
    {
        $file = $this->argument('file') ?? database_path('data/ny_nj_dispatch_zones.geojson');
        $geoJson = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
        if (! is_array($geoJson)) {
            $this->error("$file is not a readable GeoJSON file; nothing was loaded.");

            return self::FAILURE;
        }
        try {
            $result = $import->handle($geoJson, $this->option('state'), $this->option('only'));
        } catch (InvalidArgumentException $problem) {
            $this->error($problem->getMessage());

            return self::FAILURE;
        }
        foreach ($result['skipped'] as $skipped) {
            $this->warn("Skipped $skipped.");
        }
        $this->info("Zones added: {$result['added']}, updated: {$result['updated']}.");
        $this->info('Waiting orders given a new zone: '.DispatchZones::refreshWaitingOrders().'.');

        return self::SUCCESS;
    }
}
