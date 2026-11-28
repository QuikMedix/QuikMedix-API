<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A set of orders in one zone that dispatch offers to drivers as a route, with what it pays.
 */
class RouteOffer extends Model
{
    public const OPEN = 'open';

    public const ACCEPTED = 'accepted';

    public const WITHDRAWN = 'withdrawn';

    protected $guarded = [];

    protected $casts = ['delivery_date' => 'date', 'accepted_at' => 'datetime'];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(DispatchZone::class, 'dispatch_zone_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
