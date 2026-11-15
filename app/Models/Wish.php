<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A delivery wish a patient can choose.
 */
class Wish extends Model
{
    protected $table = 'wishes';

    // The table keeps its own date columns; Eloquent must not add created_at or updated_at.
    public $timestamps = false;

    protected $guarded = [];

    public function category(): BelongsTo
    {
        return $this->belongsTo(WishCategory::class, 'category_id');
    }
}
