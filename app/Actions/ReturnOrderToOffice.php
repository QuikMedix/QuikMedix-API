<?php

namespace App\Actions;

use Illuminate\Support\Facades\DB;

/**
 * Takes an order off its driver's delivery stops and puts it on a return to the office: the
 * patient stop is kept in the route log, and the order joins the office stop already on the
 * route or, when there is none, a new last stop at the first office.
 *
 * Used when a dispatcher marks a delivery as not confirmed or gives it a new date.
 */
class ReturnOrderToOffice
{
    private const FIRST_OFFICE = 1;

    /**
     * @param  object  $order  the orders row; its driver is the one whose route changes
     */
    public function handle(object $order): void
    {
        $patientStops = DB::table('routes_priority')->where('order_id', $order->id)->where('driver_id', $order->driver_id)->where('type', 'patient');
        $stops = (clone $patientStops)->get();
        $firstStop = (clone $patientStops)->first();

        foreach ($stops as $stop) {
            DB::table('routes_priority_logs')->insert([
                'id' => $stop->id, 'driver_id' => $stop->driver_id, 'order_id' => $stop->order_id,
                'type' => $stop->type, 'type_id' => $stop->type_id, 'type_pay' => $stop->type_pay, 'pay_value' => $stop->pay_value,
            ]);
        }

        $office = DB::table('routes_priority')->where('driver_id', $order->driver_id)->where('type', 'office')->first();
        if (! empty($office)) {
            DB::table('routes_priority')->insert([
                'driver_id' => $order->driver_id, 'order_id' => $order->id, 'type' => 'office', 'type_id' => $office->type_id,
                'type_pay' => $office->type_pay, 'pay_value' => $office->pay_value, 'priority' => $office->priority,
            ]);
        } elseif (! empty($firstStop)) {
            // Without a patient stop to take the pay from, no return stop is added.
            $lastPriority = DB::table('routes_priority')->where('driver_id', $order->driver_id)->max('priority');
            DB::table('routes_priority')->insert([
                'driver_id' => $order->driver_id, 'order_id' => $order->id, 'type' => 'office', 'type_id' => self::FIRST_OFFICE,
                'type_pay' => $firstStop->type_pay, 'pay_value' => $firstStop->pay_value, 'priority' => intval($lastPriority) + 1,
            ]);
        }

        (clone $patientStops)->delete();
    }
}
