<?php

namespace App\Console\Tasks;

use App\Actions\RecalculateDriverEta;
use App\Models\User;

/**
 * Recalculates arrival times for every driver who is working and has a route. Scheduled in routes/console.php.
 */
class RecalculateDriverEtas
{
    public function __invoke(): void
    {
        $drivers = User::where('role','driver')->where('work_now','1')->where('isblocked',0)->leftJoin("routes_priority","routes_priority.driver_id","=","users.id")->whereNotNull('routes_priority.id')->select('users.id')->groupBy('users.id')->get();
        foreach($drivers as $driver){
            try {
                app(RecalculateDriverEta::class)->handle($driver->id);
            } catch (\Throwable $exception) {
                // The work around it continues, but the failure is no longer lost.
                report($exception);
            }
        }
    }
}
