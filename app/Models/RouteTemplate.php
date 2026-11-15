<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A saved route layout.
 */
class RouteTemplate extends Model
{
    protected $table = 'route_templates';

    protected $guarded = [];

    public function items(): HasMany
    {
        return $this->hasMany(RouteTemplateItem::class, 'route_template_id');
    }
}
