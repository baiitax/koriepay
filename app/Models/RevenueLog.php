<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevenueLog extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['entry_id', 'source', 'node_path', 'amount_usd'];

    /**
     * Tier-1 Precision: Force PHP to treat the amount as a high-precision decimal, 
     * preventing floating-point rounding errors during revenue aggregation.
     */
    protected $casts = [
        'amount' => 'decimal:6',
    ];

    /**
     * Relationship: Link back to the exact user transfer that generated this profit.
     * This powers the "Non-Repudiation" audit trail in your dashboard.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}