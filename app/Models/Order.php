<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A delivery order.
 */
class Order extends Model
{
    protected $table = 'orders';

    // The table keeps its own date columns; Eloquent must not add created_at or updated_at.
    public $timestamps = false;

    protected $guarded = [];

    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(Pharmacy::class, 'pharmacy_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'statuse_id');
    }

    public function deliveryMethod(): BelongsTo
    {
        return $this->belongsTo(DeliveryMethod::class, 'delivery_method_id');
    }

    public function deliveryTime(): BelongsTo
    {
        return $this->belongsTo(DeliveryTime::class, 'delivery_time_id');
    }

    public function familyMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class, 'family_id');
    }

    public function medic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'medic_id');
    }

    public function rxs(): HasMany
    {
        return $this->hasMany(Rx::class, 'order_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class, 'order_id');
    }

    public function packageTransitions(): HasMany
    {
        return $this->hasMany(PackageTransition::class, 'order_id');
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(OrderException::class, 'order_id');
    }

    public function medicines(): HasMany
    {
        return $this->hasMany(Medicine::class, 'order_id');
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(DriverPayout::class, 'order_id');
    }

    public function routeStop(): HasOne
    {
        return $this->hasOne(RouteStop::class, 'order_id');
    }
}
