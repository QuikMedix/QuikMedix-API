<?php

namespace App\Console\Tasks;

use App\Models\Pharmacy;
use Illuminate\Support\Facades\DB;

/**
 * Moves pharmacies whose trial has ended to the standard plan. Scheduled in routes/console.php.
 */
class EndExpiredTrials
{
    public function __invoke(): void
    {
        $pharmacys = Pharmacy::query()->where('isactive','1')->where('isblocked','0')->where('plan_id',2)->get();
        foreach($pharmacys as $pharmacy) {
            if(strtotime($pharmacy->date_end_trial ?? '')<=strtotime('now')) {
                DB::table('pharmacys')->where("id",$pharmacy->id)->update(["date_end_trial"=>NULL,"plan_id"=>1]);
            }
        }
    }
}
