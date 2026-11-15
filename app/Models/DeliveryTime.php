<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A delivery time window.
 */
class DeliveryTime extends Model
{
    protected $table = 'delivery_times';

    // The table keeps its own date columns; Eloquent must not add created_at or updated_at.
    public $timestamps = false;

    protected $guarded = [];
}
