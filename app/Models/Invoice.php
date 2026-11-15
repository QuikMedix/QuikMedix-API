<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A pharmacy invoice.
 */
class Invoice extends Model
{
    protected $table = 'invoices';

    // The table keeps its own date columns; Eloquent must not add created_at or updated_at.
    public $timestamps = false;

    protected $guarded = [];

    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(Pharmacy::class, 'pharmacy_id');
    }

    public function exclusions(): HasMany
    {
        return $this->hasMany(InvoiceExclusion::class, 'invoice_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PharmacyPayment::class, 'invoice_id');
    }
}
