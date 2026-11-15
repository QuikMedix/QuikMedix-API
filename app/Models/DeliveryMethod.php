<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A way of delivering an order.
 */
class DeliveryMethod extends Model
{
    protected $table = 'delivery_methods';

    // The table keeps its own date columns; Eloquent must not add created_at or updated_at.
    public $timestamps = false;

    protected $guarded = [];
}
