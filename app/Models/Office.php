<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A QuikMedix office or hub.
 */
class Office extends Model
{
    protected $table = 'offices';

    // The table keeps its own date columns; Eloquent must not add created_at or updated_at.
    public $timestamps = false;

    protected $guarded = [];

    public function adminAreas(): HasMany
    {
        return $this->hasMany(AdminArea::class, 'office_id');
    }
}
