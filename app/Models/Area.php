<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A delivery area drawn on the map.
 */
class Area extends Model
{
    protected $table = 'area';

    // The table keeps its own date columns; Eloquent must not add created_at or updated_at.
    public $timestamps = false;

    protected $guarded = [];

    public function zips(): HasMany
    {
        return $this->hasMany(AreaZip::class, 'area_id');
    }

    public function pharmacies(): HasMany
    {
        return $this->hasMany(PharmacyArea::class, 'area_id');
    }
}
