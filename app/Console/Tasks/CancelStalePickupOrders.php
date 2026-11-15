<?php

namespace App\Console\Tasks;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Cancels orders that have waited for pickup for more than seven days. Scheduled in routes/console.php.
 */
class CancelStalePickupOrders
{
    public function __invoke(): void
    {
        DB::table('orders')->where('statuse_id',1)->where('created', '<', Carbon::now()->subDays(7)->startOfDay())->update(['statuse_id'=>5]);
    }
}
