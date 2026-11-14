<?php

namespace App\Actions;

use App\Notifications;
use App\Support\HereMaps;
use Illuminate\Support\Facades\DB;

/**
 * Tells the next patient on a driver's route that their delivery is next, with the travel time
 * from the driver's last shared location to the patient's primary address, and sets the order's ETA.
 *
 * One implementation for the dispatch pages and the driver app. The dispatch pages used to carry
 * a copy that always measured to the patient's first address, whichever address was primary.
 */
class NotifyNextPatient
{
    private const BICYCLE = '2';

    private const MILES_PER_METER = 0.000621371192;

    /**
     * @param  object  $nextRoute  the patient stop: type_id is the patient, order_id the order
     * @return bool false when the driver, the patient or either location is unknown
     */
    public function handle(int|string|null $driverId, object $nextRoute): bool
    {
        $origin = DB::table('locations')->whereIn('id', [DB::raw('select max(`id`) from locations GROUP BY user_id')])->where('user_id', $driverId)->value('location');
        $driver = DB::table('users')->where('id', $driverId)->first();
        $patient = DB::table('users')->where('id', $nextRoute->type_id)->first();
        if (empty($origin) || empty($driver) || empty($patient)) {
            return false;
        }
        $location = match ((int) $patient->primary_address) {
            3 => $patient->location3,
            2 => $patient->location2,
            default => $patient->location,
        };
        if (empty($location)) {
            return false;
        }

        $transport = $driver->transport == self::BICYCLE ? 'bicycle' : 'car';
        $destination = str_replace(' ', '', $location);
        $context = stream_context_create(['http' => [
            'method' => 'GET',
            'ignore_errors' => true,
            'header' => 'Authorization: Bearer '.HereMaps::accessToken(),
        ]]);
        $directions = json_decode(file_get_contents("https://router.hereapi.com/v8/routes?transportMode=$transport&origin=$origin&destination=$destination&return=summary", false, $context));
        $distance = 0;
        $duration = 0;
        if (! empty($directions->routes)) {
            foreach ($directions->routes[0]->sections as $leg) {
                $distance += $leg->summary->length;
                $duration += $leg->summary->duration;
            }
        }

        $minutes = ceil($duration / 60);
        DB::table('orders')->where('id', $nextRoute->order_id)->update(['eta' => ceil($minutes / 60)]);
        $travelTime = floor($minutes / 60).' hours '.($minutes % 60).' minutes';
        Notifications::send_push($nextRoute->type_id, 'QuikMedix', "Your delivery is next. \nPlease track your order #".$nextRoute->order_id." via our app. ETA: $travelTime \nThank you for using our service.");

        return true;
    }
}
