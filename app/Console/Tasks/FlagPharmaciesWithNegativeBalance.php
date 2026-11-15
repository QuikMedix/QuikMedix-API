<?php

namespace App\Console\Tasks;

use Illuminate\Support\Facades\DB;

/**
 * Marks active pharmacies whose balance is below zero. Scheduled in routes/console.php.
 */
class FlagPharmaciesWithNegativeBalance
{
    public function __invoke(): void
    {
        DB::table('pharmacys')->where('isactive','1')->where('isblocked','0')->where('balance','<',0)->update(['balance_ban'=>'1']);
    }
}
