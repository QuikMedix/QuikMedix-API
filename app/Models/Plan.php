<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A pricing plan.
 */
class Plan extends Model
{
    protected $table = 'plans';

    // The table keeps its own date columns; Eloquent must not add created_at or updated_at.
    public $timestamps = false;

    protected $guarded = [];

    public function pharmacies(): HasMany
    {
        return $this->hasMany(Pharmacy::class, 'plan_id');
    }
}
