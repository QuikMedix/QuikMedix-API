<?php

namespace App\Console\Tasks;

use App\Models\User;
use App\Support\HereMaps;
use Illuminate\Support\Facades\DB;

/**
 * Takes a minute off every arrival time of drivers who have moved since the last calculation. Scheduled in routes/console.php.
 */
class CountDownEtas
{
    public function __invoke(): void
    {
        $drivers = User::where('role','driver')->where('work_now','1')->where('isblocked',0)->leftJoin("routes_priority","routes_priority.driver_id","=","users.id")->whereNotNull('routes_priority.id')->select('users.id')->groupBy('users.id')->get();
        foreach($drivers as $driver){
            try {
                $driver_location = DB::table('locations')->whereIn('id', [DB::raw("select max(`id`) from locations GROUP BY user_id")])->where('user_id',$driver->id)->value('location');
                if(empty($driver_location)) {
                    continue;
                }
                $driver_loc = HereMaps::roundLocation($driver_location);
                $eta = DB::table('drivers_eta')->where('driver_id',$driver->id)->first();
                $access=TRUE;
                if(!empty($eta)) {
                    if($eta->last_location==$driver_loc) {
                        $access=FALSE;
                    }
                }
                if($access){
                    DB::table('orders')->where('driver_id',$driver->id)->whereNotNull('eta')->where('eta','>','0')->update(['eta'=>DB::raw('eta - 1')]);
                    DB::table('drivers_eta')->where('driver_id',$driver->id)->whereNotNull('eta')->where('eta','>','0')->update(['eta'=>DB::raw('eta - 1')]);
                    DB::table('routes_priority')->where('driver_id',$driver->id)->whereNotNull('eta')->where('eta','>','0')->update(['eta'=>DB::raw('eta - 1')]);
                }
            } catch (\Throwable $exception) {
                // The work around it continues, but the failure is no longer lost.
                report($exception);
            }
        }
    }
}
