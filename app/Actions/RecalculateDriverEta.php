<?php

namespace App\Actions;

use App\Support\HereMaps;
use App\Support\PatientAddress;
use Illuminate\Support\Facades\DB;

/**
 * Recalculates a driver's ETAs from their last shared location: asks HERE to route the remaining
 * stops in order, then stores the running arrival time on each stop, on the patient orders of a
 * stop and on the driver. The driver's own transport decides the routing mode, and a patient
 * stop is the patient's primary address, where dispatch and the driver app send the delivery.
 *
 * One implementation for the dispatch pages (LexaAdmin::eta_calculate) and the scheduled task,
 * which used to carry its own copy that never loaded the driver's transport and so routed by car.
 */
class RecalculateDriverEta
{
    private const ROUTING_URL = 'https://router.hereapi.com/v8/routes';

    private const BICYCLE = '2';

    /** Time allowed at every stop, on top of travel. */
    private const SECONDS_PER_STOP = 240;

    private const STOPS_PER_REQUEST = 50;

    private const MILES_PER_METER = 0.000621371192;

    /**
     * @param  bool  $force  recalculate even when the driver has not moved since the last calculation
     * @return float|false the route distance in miles, or false when nothing was calculated
     */
    public function handle(int|string|null $driverId, bool $force = false): float|false
    {
        $driver = DB::table('users')->where('id', $driverId)->first();
        if (empty($driver)) {
            return false;
        }
        $lastLocation = DB::table('locations')->whereIn('id', [DB::raw('select max(`id`) from locations GROUP BY user_id')])->where('user_id', $driver->id)->value('location');
        if (empty($lastLocation)) {
            return false;
        }
        $origin = HereMaps::roundLocation($lastLocation);
        $eta = DB::table('drivers_eta')->where('driver_id', $driver->id)->first();
        if (! $force && ! empty($eta) && $eta->last_location == $origin) {
            return false;
        }

        $distance = 0;
        $duration = 0;
        $stops = $this->locatedStops($driver->id);
        foreach ($this->legs($origin, array_column($stops, 'location'), $driver->transport == self::BICYCLE ? 'bicycle' : 'car') as $key => $leg) {
            $distance += $leg->summary->length;
            $duration += $leg->summary->duration + self::SECONDS_PER_STOP;
            if (! isset($stops[$key])) {
                continue;
            }
            $stop = $stops[$key];
            $minutes = ceil($duration / 60);
            DB::table('routes_priority')->where('driver_id', $driver->id)->where('type', $stop['type'])->where('type_id', $stop['type_id'])->where('priority', $stop['priority'])->update(['eta' => $minutes]);
            if ($stop['type'] == 'patient') {
                DB::table('orders')->whereIn('id', explode(',', $stop['order_id']))->update(['eta' => $minutes]);
            }
        }

        $distance = round($distance * self::MILES_PER_METER, 1);
        $totals = ['distance' => $distance, 'eta' => ceil($duration / 60), 'last_location' => $origin];
        if (! empty($eta)) {
            DB::table('drivers_eta')->where('driver_id', $driver->id)->update($totals);
        } else {
            DB::table('drivers_eta')->insert(['driver_id' => $driver->id] + $totals);
        }

        return $distance;
    }

    /**
     * The driver's stops in route order, without those that have no location. Orders sharing a
     * pharmacy or patient are one stop; every office visit is its own stop.
     *
     * @return list<array{order_id: string, type: string, type_id: int|string, priority: int|string, location: string}>
     */
    private function locatedStops(int|string $driverId): array
    {
        // GROUP_CONCAT without a separator joins with a comma on MySQL, as the explicit separator did.
        $pickupsAndDeliveries = DB::table('routes_priority')->select('driver_id', DB::raw('GROUP_CONCAT(order_id) as order_id'), 'type', 'type_id', DB::raw('min(priority) as priority'))->where('driver_id', $driverId)->where('type', '!=', 'office')->groupBy('type', 'type_id', 'driver_id')->orderBy('priority', 'asc')->get();
        $officeVisits = DB::table('routes_priority')->select('driver_id', DB::raw('GROUP_CONCAT(order_id) as order_id'), 'type', 'type_id', 'priority')->where('driver_id', $driverId)->where('type', 'office')->groupBy('type', 'type_id', 'driver_id', 'priority')->orderBy('priority', 'asc')->get();
        $routes = [...$pickupsAndDeliveries, ...$officeVisits];
        usort($routes, fn ($a, $b) => $a->priority - $b->priority);

        $stops = [];
        foreach ($routes as $route) {
            $location = $this->stopLocation($route->type, $route->type_id);
            if (empty($location)) {
                continue;
            }
            $stops[] = [
                'order_id' => $route->order_id,
                'type' => $route->type,
                'type_id' => $route->type_id,
                'priority' => $route->priority,
                'location' => HereMaps::roundLocation(str_replace(' ', '', $location)),
            ];
        }

        return $stops;
    }

    /**
     * Where a stop is: a patient's primary address, or the pharmacy's or office's own location.
     */
    private function stopLocation(string $type, int|string $id): ?string
    {
        if ($type === 'patient') {
            $patient = DB::table('users')->where('id', $id)->first();

            return $patient ? PatientAddress::primaryLocation($patient) : null;
        }
        $tables = ['pharmacy' => 'pharmacys', 'office' => 'offices'];

        return isset($tables[$type]) ? DB::table($tables[$type])->where('id', $id)->value('location') : null;
    }

    /**
     * The routed legs, one per stop, requested from HERE in groups of fifty stops.
     *
     * @param  list<string>  $locations
     * @return list<object>
     */
    private function legs(string $origin, array $locations, string $transport): array
    {
        if ($locations === []) {
            return [];
        }
        $context = stream_context_create(['http' => [
            'method' => 'GET',
            'ignore_errors' => true,
            'header' => 'Authorization: Bearer '.HereMaps::accessToken(),
        ]]);

        $legs = [];
        foreach (array_chunk($locations, self::STOPS_PER_REQUEST) as $chunk) {
            $destination = array_pop($chunk);
            $url = self::ROUTING_URL."?transportMode=$transport&origin=$origin&destination=$destination&return=summary";
            if ($chunk !== []) {
                $url .= '&via='.implode('&via=', $chunk);
            }
            $directions = json_decode(file_get_contents($url, false, $context));
            if (! empty($directions->routes)) {
                array_push($legs, ...$directions->routes[0]->sections);
            }
        }

        return $legs;
    }
}
