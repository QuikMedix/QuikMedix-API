<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A payment a pharmacy made.
 */
class PharmacyPayment extends Model
{
    protected $table = 'pharmacy_payments';

    // The table keeps its own date columns; Eloquent must not add created_at or updated_at.
    public $timestamps = false;

    protected $guarded = [];

    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(Pharmacy::class, 'pharmacy_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}
