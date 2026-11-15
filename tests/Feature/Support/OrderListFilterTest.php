<?php

namespace Tests\Feature\Support;

use App\Support\OrderListFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderListFilterTest extends TestCase
{
    private const NAMES = ['delivery_time', 'status', 'facility', 'create_start', 'create_end', 'micromerchant'];

    public function test_filters_count_only_when_the_filter_form_was_submitted(): void
    {
        $query = ['status' => ['4'], 'create_start' => '11/01/2026', 'micromerchant' => '1'];

        $this->assertSame(
            ['delivery_time' => [], 'status' => [], 'facility' => [], 'create_start' => '', 'create_end' => '', 'micromerchant' => ''],
            OrderListFilter::fromRequest(Request::create('/orders', 'GET', $query), self::NAMES),
        );
        $this->assertSame(
            ['delivery_time' => [], 'status' => ['4'], 'facility' => [], 'create_start' => '11/01/2026', 'create_end' => '', 'micromerchant' => '1'],
            OrderListFilter::fromRequest(Request::create('/orders', 'GET', $query + ['filter' => '1']), self::NAMES),
        );
    }

    public function test_the_shared_filters_narrow_the_query(): void
    {
        $filter = ['delivery_time' => ['2', '3'], 'delivery_method' => [], 'status' => ['4'], 'facility' => ['0'],
            'create_start' => '11/01/2026', 'create_end' => '11/15/2026', 'delivered_start' => '', 'delivered_end' => '', 'need_delivery_start' => '', 'need_delivery_end' => ''];

        $orders = OrderListFilter::apply(DB::table('orders'), $filter);

        $this->assertSame(
            'select * from "orders" where "delivery_time_id" in (?, ?) and "orders"."statuse_id" in (?) and "orders"."facility" is null and "orders"."created" between ? and ?',
            $orders->toSql(),
        );
        $this->assertSame(['2', '3', '4', '2026-11-01', '2026-11-15'], $orders->getBindings());
    }

    public function test_one_facility_choice_other_than_none_means_facility_orders_and_no_choice_changes_nothing(): void
    {
        $empty = ['delivery_time' => [], 'delivery_method' => [], 'status' => [], 'facility' => [], 'create_start' => '', 'create_end' => '',
            'delivered_start' => '', 'delivered_end' => '', 'need_delivery_start' => '', 'need_delivery_end' => ''];

        $this->assertSame('select * from "orders"', OrderListFilter::apply(DB::table('orders'), $empty)->toSql());
        $this->assertSame('select * from "orders" where "orders"."facility" is not null', OrderListFilter::apply(DB::table('orders'), ['facility' => ['1']] + $empty)->toSql());
        $this->assertSame('select * from "orders"', OrderListFilter::apply(DB::table('orders'), ['facility' => ['0', '1']] + $empty)->toSql());
    }
}
