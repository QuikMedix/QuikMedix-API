<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A driver's report that an order could not be delivered as planned.
 */
class OrderException extends Model
{
    protected $table = 'order_exceptions';

    protected $guarded = [];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function requestedStatus(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'requested_status_id');
    }
}
