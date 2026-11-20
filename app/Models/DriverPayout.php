<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What a driver earned for one order.
 */
class DriverPayout extends Model
{
    /**
     * The amount recorded for a completed stop whose route has no rate yet. It is a marker, not
     * money: when dispatch closes the route, the route's pay is shared out over these rows.
     */
    public const AWAITING_RATE = -1;

    protected $table = 'payouts_driver';

    // The table keeps its own date columns; Eloquent must not add created_at or updated_at.
    public $timestamps = false;

    protected $guarded = [];

    /** Payouts with a real amount. */
    public function scopeSettled(Builder $query): void
    {
        $query->where($this->qualifyColumn('amount'), '!=', self::AWAITING_RATE);
    }

    /** Completed stops still waiting for dispatch to close the route and set what it pays. */
    public function scopeAwaitingRate(Builder $query): void
    {
        $query->where($this->qualifyColumn('amount'), self::AWAITING_RATE);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
