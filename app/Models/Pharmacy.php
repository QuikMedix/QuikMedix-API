<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A pharmacy that sends orders.
 */
class Pharmacy extends Model
{
    protected $table = 'pharmacys';

    // The table keeps its own date columns; Eloquent must not add created_at or updated_at.
    public $timestamps = false;

    protected $guarded = [];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ref_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'pharmacy_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'pharmacy_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'pharmacy_id');
    }

    public function areas(): HasMany
    {
        return $this->hasMany(PharmacyArea::class, 'pharmacy_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PharmacyPayment::class, 'pharmacy_id');
    }
}
