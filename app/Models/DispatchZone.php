<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A place deliveries are grouped by for dispatch, such as Brooklyn or Bergen County.
 */
class DispatchZone extends Model
{
    protected $guarded = [];

    protected $casts = ['active' => 'boolean'];
}
