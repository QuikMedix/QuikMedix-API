<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * The filter form above the two order lists: one pharmacy's orders and all orders. The lists
 * share most filters; each names the ones it offers.
 */
final class OrderListFilter
{
    /** Filters that take several values; the rest take one piece of text, such as a date. */
    private const SEVERAL = ['delivery_time', 'delivery_method', 'pharmacy', 'status', 'facility'];

    /**
     * What the person chose, by filter name. A filter counts only when the form itself was
     * submitted ("filter" is set); otherwise it is empty.
     *
     * @param  list<string>  $names
     * @return array<string, mixed>
     */
    public static function fromRequest(Request $request, array $names): array
    {
        $filter = [];
        foreach ($names as $name) {
            $empty = in_array($name, self::SEVERAL, true) ? [] : '';
            $filter[$name] = ! empty($request->query($name)) && ! empty($request->query('filter')) ? $request->query($name) : $empty;
        }

        return $filter;
    }

    /**
     * Narrows the orders query by the filters both lists share.
     *
     * @param  \Illuminate\Database\Query\Builder  $orders
     * @param  array<string, mixed>  $filter
     * @return \Illuminate\Database\Query\Builder
     */
    public static function apply($orders, array $filter)
    {
        if(!empty($filter["delivery_time"])) {
            $orders = $orders->whereIn("delivery_time_id",$filter["delivery_time"]);
        }
        if(!empty($filter["delivery_method"])) {
            $orders = $orders->whereIn("delivery_method_id",$filter["delivery_method"]);
        }
        if(!empty($filter["pharmacy"])) {
            $orders = $orders->whereIn('orders.pharmacy_id',$filter["pharmacy"]);
        }
        if(!empty($filter["status"])) {
            $orders = $orders->whereIn('orders.statuse_id',$filter["status"]);
        }
        if(count($filter["facility"])==1) {
            if($filter["facility"][0]==0) {
                $orders = $orders->whereNull('orders.facility');
            } else {
                $orders = $orders->whereNotNull('orders.facility');
            }
        }
        if(!empty($filter["create_start"]) && !empty($filter["create_end"])) {
            $orders = $orders->whereBetween('orders.created', [\DateTime::createFromFormat('m/d/Y',$filter["create_start"])->format('Y-m-d'), \DateTime::createFromFormat('m/d/Y',$filter["create_end"])->format('Y-m-d')]);
        }
        if(!empty($filter["delivered_start"]) && !empty($filter["delivered_end"])) {
            $orders = $orders->whereBetween('orders.finish', [\DateTime::createFromFormat('m/d/Y',$filter["delivered_start"])->format('Y-m-d'), \DateTime::createFromFormat('m/d/Y',$filter["delivered_end"])->format('Y-m-d')]);
        }
        if(!empty($filter["need_delivery_start"]) && !empty($filter["need_delivery_end"])) {
            $orders = $orders->whereBetween('orders.delivery_date', [\DateTime::createFromFormat('m/d/Y',$filter["need_delivery_start"])->format('Y-m-d'), \DateTime::createFromFormat('m/d/Y',$filter["need_delivery_end"])->format('Y-m-d')]);
        }

        return $orders;
    }
}
