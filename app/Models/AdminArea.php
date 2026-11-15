<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The territory an administrator is responsible for.
 */
class AdminArea extends Model
{
    protected $table = 'admin_areas';

    // The table keeps its own date columns; Eloquent must not add created_at or updated_at.
    public $timestamps = false;

    protected $guarded = [];

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'office_id');
    }

    public function zones(): HasMany
    {
        return $this->hasMany(AdminAreaZone::class, 'admin_area_id');
    }
}
