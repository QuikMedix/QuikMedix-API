<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An order status.
 */
class Status extends Model
{
    protected $table = 'statuses';

    // The table keeps its own date columns; Eloquent must not add created_at or updated_at.
    public $timestamps = false;

    protected $guarded = [];
}
