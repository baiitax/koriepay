<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A hold placed on a transaction. The ledger/state machine is authoritative:
 * hold = state HELD, release = HELD → POSTED, reject = HELD → CANCELLED.
 * This row records reason, SLA and the decision trail.
 */
class TransactionHold extends Model
{
    public const STATUS_HELD = 'held';
    public const STATUS_RELEASED = 'released';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_REVERSED = 'reversed';

    protected $fillable = [
        'transaction_id', 'amount', 'currency_code', 'reason', 'reason_code',
        'status', 'held_by', 'decided_by', 'decided_at', 'decision_note', 'sla_due_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'decided_at' => 'datetime',
        'sla_due_at' => 'datetime',
    ];
}
