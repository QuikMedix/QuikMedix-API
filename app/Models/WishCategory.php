<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A group of delivery wishes.
 */
class WishCategory extends Model
{
    protected $table = 'wishes_category';

    // The table keeps its own date columns; Eloquent must not add created_at or updated_at.
    public $timestamps = false;

    protected $guarded = [];

    public function wishes(): HasMany
    {
        return $this->hasMany(Wish::class, 'category_id');
    }
}
