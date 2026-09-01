<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReconciliationRun extends Model
{
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'reference', 'provider_code', 'country_iso2', 'currency_code',
        'period_start', 'period_end', 'internal_count', 'provider_count',
        'matched_count', 'unmatched_internal_count', 'unmatched_provider_count',
        'amount_mismatch_count', 'duplicate_count', 'internal_amount',
        'provider_amount', 'difference', 'health_score', 'status',
        'started_at', 'completed_at', 'run_by',
    ];

    protected $casts = [
        'internal_amount' => 'decimal:2',
        'provider_amount' => 'decimal:2',
        'difference' => 'decimal:2',
        'health_score' => 'decimal:2',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ReconciliationItem::class);
    }
}
