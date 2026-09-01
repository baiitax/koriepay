<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AGGREGATOR CONSOLE — Stage C (liquidity request, §23–28).
 *
 * The agent→aggregator liquidity workflow record. Money movement never
 * happens on this row — approval earmarks operational cash on the ledger
 * and funding posts the earmark to the agent float. Status transitions are
 * driven exclusively by AggregatorLiquidityService (audited, idempotent).
 */
class LiquidityRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_REVIEW = 'in_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_FUNDED = 'funded';
    public const STATUS_CANCELLED = 'cancelled';

    public const RISK_LOW = 'low';
    public const RISK_MEDIUM = 'medium';
    public const RISK_HIGH = 'high';

    public const REASON_CASH_OUT_DEMAND = 'cash_out_demand';
    public const REASON_RESTOCK = 'restock';
    public const REASON_OTHER = 'other';

    protected $fillable = [
        'reference', 'aggregator_id', 'agent_id', 'currency_code', 'amount',
        'reason', 'status', 'risk_level', 'risk_notes', 'requested_by_type',
        'requested_by', 'reviewed_by', 'reviewed_at', 'review_note',
        'ledger_transaction_id', 'funded_at', 'cancelled_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'risk_notes' => 'array',
        'reviewed_at' => 'datetime',
        'funded_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function aggregator(): BelongsTo
    {
        return $this->belongsTo(Aggregator::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_IN_REVIEW,
            self::STATUS_APPROVED,
        ], true);
    }
}
