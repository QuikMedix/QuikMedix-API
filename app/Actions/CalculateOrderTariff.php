<?php

namespace App\Actions;

use Illuminate\Support\Facades\DB;

/**
 * Works out the delivery price of an order: the base price of the area the patient lives in
 * and, for QuikMedix drivers, the price of the delivery window, the order's extra driver charge
 * and the fridge surcharge. A pharmacy's own prices take the place of its plan's.
 *
 * Used when an order is delivered, handed back at the pharmacy, or added to an invoice by hand.
 */
class CalculateOrderTariff
{
    /**
     * @param  object  $order  the orders row
     */
    public function handle(object $order): float
    {
        $pharmacy=DB::table('pharmacys')->where('pharmacys.id',$order->pharmacy_id)->first();
        $pharmacy_plan=DB::table('plans')->where('plans.id',$pharmacy->plan_id)->first();
        $patient=DB::table('users')->where('users.id',$order->user_id)->first();
        $pharmacy_areas=DB::table('pharmacy_areas')->where('pharmacy_id',$order->pharmacy_id)->where('type',1)->pluck('area_id')->toArray();
        $pharmacy_areas2=DB::table('pharmacy_areas')->where('pharmacy_id',$order->pharmacy_id)->where('type',2)->pluck('area_id')->toArray();
        $pharmacy_areas3=DB::table('pharmacy_areas')->where('pharmacy_id',$order->pharmacy_id)->where('type',3)->pluck('area_id')->toArray();
        $zip_tariff=DB::table('area')->whereIn('area.id',$pharmacy_areas)->whereRaw('ST_CONTAINS(polygon, POINT(?, ?))', \App\Support\GeoPoint::bindings($patient->location))->select("area.id")->first();
        $zip_tariff2=DB::table('area')->whereIn('area.id',$pharmacy_areas2)->whereRaw('ST_CONTAINS(polygon, POINT(?, ?))', \App\Support\GeoPoint::bindings($patient->location))->select("area.id")->first();
        $zip_tariff3=DB::table('area')->whereIn('area.id',$pharmacy_areas3)->whereRaw('ST_CONTAINS(polygon, POINT(?, ?))', \App\Support\GeoPoint::bindings($patient->location))->select("area.id")->first();
        if(!empty($zip_tariff)){
            if(is_numeric($pharmacy->tariff)) {
                $tariff = $pharmacy->tariff;
            } else {
                $tariff = $pharmacy_plan->tariff;
            }
        } else if(!empty($zip_tariff2)){
            if(is_numeric($pharmacy->tariff_area2)) {
                $tariff = $pharmacy->tariff_area2;
            } else {
                $tariff = $pharmacy_plan->tariff_area2;
            }
        } else if(!empty($zip_tariff3)){
            if(is_numeric($pharmacy->tariff_area3)) {
                $tariff = $pharmacy->tariff_area3;
            } else {
                $tariff = $pharmacy_plan->tariff_area3;
            }
        } else {
            if(is_numeric($pharmacy->tariff_area_more)) {
                $tariff = $pharmacy->tariff_area_more;
            } else {
                $tariff = $pharmacy_plan->tariff_area_more;
            }
        }
        if(is_numeric($pharmacy->tariff_next_day)) {
            $tariff_next_day = $pharmacy->tariff_next_day;
        } else {
            $tariff_next_day = $pharmacy_plan->tariff_next_day;
        }
        if(is_numeric($pharmacy->tariff_same_day)) {
            $tariff_same_day = $pharmacy->tariff_same_day;
        } else {
            $tariff_same_day = $pharmacy_plan->tariff_same_day;
        }
        if(is_numeric($pharmacy->tariff_asap)) {
            $tariff_asap = $pharmacy->tariff_asap;
        } else {
            $tariff_asap = $pharmacy_plan->tariff_asap;
        }
        if(is_numeric($pharmacy->tariff_after_hours)) {
            $tariff_after_hours = $pharmacy->tariff_after_hours;
        } else {
            $tariff_after_hours = $pharmacy_plan->tariff_after_hours;
        }
        if(is_numeric($pharmacy->tariff_fridge)) {
            $tariff_fridge = $pharmacy->tariff_fridge;
        } else {
            $tariff_fridge = $pharmacy_plan->tariff_fridge;
        }
        if($order->type_driver==1) {
            if($order->delivery_time_id==1) {
                $tariff_res = (floatval($tariff)+floatval($tariff_next_day)+floatval($order->extra_charge_driver));
            } elseif($order->delivery_time_id==2) {
                $tariff_res = (floatval($tariff)+floatval($tariff_same_day)+floatval($order->extra_charge_driver));
            } elseif($order->delivery_time_id==3) {
                $tariff_res = (floatval($tariff)+floatval($tariff_asap)+floatval($order->extra_charge_driver));
            } elseif($order->delivery_time_id==4) {
                $tariff_res = (floatval($tariff)+floatval($tariff_after_hours)+floatval($order->extra_charge_driver));
            } else {
                throw new \UnexpectedValueException("Unknown delivery time {$order->delivery_time_id} for order {$order->id}");
            }
            if($order->fridge==1) {
                $tariff_res+= floatval($tariff_fridge);
            }
        } else {
            $tariff_res = floatval($tariff);
        }

        return $tariff_res;
    }
}
