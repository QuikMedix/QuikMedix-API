<?php

namespace App\Actions;

use App\Notifications;
use App\Support\HereMaps;
use App\Support\PatientAddress;
use Illuminate\Support\Facades\DB;

/**
 * Tells the next patient on a driver's route that their delivery is next, with the travel time
 * from the driver's last shared location to the patient's primary address, and sets the order's ETA.
 * When HERE finds no route the notification goes out without a travel time.
 *
 * One implementation for the dispatch pages and the driver app. The dispatch pages used to carry
 * a copy that always measured to the patient's first address, whichever address was primary.
 */
class NotifyNextPatient
{
    private const BICYCLE = '2';

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
        $location = PatientAddress::primaryLocation($patient);
        if ($location === null) {
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
        $message = "Your delivery is next. \nPlease track your order #".$nextRoute->order_id.' via our app.';
        if (! empty($directions->routes)) {
            $duration = 0;
            foreach ($directions->routes[0]->sections as $leg) {
                $duration += $leg->summary->duration;
            }
            // Order ETAs are kept in minutes, as the route recalculation stores them and the order pages read them.
            $minutes = ceil($duration / 60);
            DB::table('orders')->where('id', $nextRoute->order_id)->update(['eta' => $minutes]);
            $message .= ' ETA: '.floor($minutes / 60).' hours '.($minutes % 60).' minutes';
        }
        // Without a route the patient is still told, but no travel time is claimed and the order keeps its ETA.
        Notifications::send_push($nextRoute->type_id, 'QuikMedix', $message." \nThank you for using our service.");

        return true;
    }
}
