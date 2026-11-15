<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A delivery area inside an admin area.
 */
class AdminAreaZone extends Model
{
    protected $table = 'admin_area_zones';

    // The table keeps its own date columns; Eloquent must not add created_at or updated_at.
    public $timestamps = false;

    protected $guarded = [];

    public function adminArea(): BelongsTo
    {
        return $this->belongsTo(AdminArea::class, 'admin_area_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }
}
