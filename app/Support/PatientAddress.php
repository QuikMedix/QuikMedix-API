<?php

namespace App\Support;

/**
 * A patient has up to three addresses; primary_address (1, 2 or 3) names the one deliveries go to.
 */
class PatientAddress
{
    /**
     * The stored "lat,lng" of the patient's primary address, or null when it has none.
     */
    public static function primaryLocation(object $patient): ?string
    {
        $location = match ((int) ($patient->primary_address ?? 1)) {
            3 => $patient->location3 ?? null,
            2 => $patient->location2 ?? null,
            default => $patient->location ?? null,
        };

        return empty($location) ? null : $location;
    }
}
