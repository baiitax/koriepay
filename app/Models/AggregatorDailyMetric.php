<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Analytical read model (Stage I, §100–110) — per-day aggregates for an
 * aggregator's network, DERIVED from the real operational records.
 *
 * Snapshot rows are written idempotently (unique aggregator_id + metric_date)
 * and are never the source of truth: they are a materialization of
 * operations/commissions for trend, growth, retention and EOD reporting.
 * Balances and authorizations are NEVER materialized here or cached.
 */
class AggregatorDailyMetric extends Model
{
    protected $fillable = [
        'aggregator_id', 'metric_date', 'total_ops', 'posted_ops', 'failed_ops',
        'volume', 'commission_accrued', 'active_agents', 'new_agents',
        'success_rate', 'failure_rate', 'settlements_created', 'settlement_value',
        'is_empty', 'computed_at',
    ];

    protected $casts = [
        'metric_date' => 'date',
        'volume' => 'decimal:2',
        'commission_accrued' => 'decimal:2',
        'success_rate' => 'decimal:2',
        'failure_rate' => 'decimal:2',
        'settlement_value' => 'decimal:2',
        'is_empty' => 'boolean',
        'computed_at' => 'datetime',
    ];

    public function aggregator(): BelongsTo
    {
        return $this->belongsTo(Aggregator::class);
    }
}
