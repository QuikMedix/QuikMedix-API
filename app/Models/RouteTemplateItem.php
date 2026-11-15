<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One stop in a saved route layout.
 */
class RouteTemplateItem extends Model
{
    protected $table = 'route_template_items';

    protected $guarded = [];

    public function template(): BelongsTo
    {
        return $this->belongsTo(RouteTemplate::class, 'route_template_id');
    }
}
