<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A cash-in / cash-out executed by an agent on behalf of a customer.
 * The ledger (DR/CR) is the source of truth; this row is the auditable
 * operation record the command center's network/liquidity surfaces read.
 */
class AgencyOperation extends Model
{
    use HasFactory;

    public const TYPE_CASH_IN = 'cash_in';
    public const TYPE_CASH_OUT = 'cash_out';

    protected $fillable = [
        'agent_id', 'aggregator_id', 'customer_user_id', 'transaction_id',
        'operation_type', 'currency_code', 'amount', 'fee', 'commission_amount',
        'status', 'reference', 'idempotency_key', 'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'commission_amount' => 'decimal:2',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function aggregator(): BelongsTo
    {
        return $this->belongsTo(Aggregator::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_user_id');
    }
}
