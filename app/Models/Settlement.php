<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A settlement batch for a provider/rail/country/currency. Status lifecycle
 * (scheduled → pending → processing → settled | failed | cancelled) is driven
 * by SettlementService; every transition is audited.
 */
class Settlement extends Model
{
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SETTLED = 'settled';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'reference', 'provider_code', 'rail_code', 'country_iso2', 'currency_code',
        'amount', 'settled_amount', 'status', 'provider_reference', 'period_start',
        'period_end', 'scheduled_at', 'settled_at', 'created_by', 'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'settled_amount' => 'decimal:2',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'scheduled_at' => 'datetime',
        'settled_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(SettlementItem::class);
    }
}
