<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Frozen point-in-time view of an account's PROJECTED balance (ledger_accounts.balance)
 * vs the DERIVED balance (Σ ledger_entries). Status MATCHED|MISMATCH.
 * Table created in 000500; this phase adds the model + computation service.
 */
class BalanceSnapshot extends Model
{
    public const STATUS_MATCHED = 'MATCHED';
    public const STATUS_MISMATCH = 'MISMATCH';

    protected $fillable = [
        'account_id', 'projected_balance', 'derived_balance', 'difference',
        'status', 'snapshot_at',
    ];

    protected $casts = [
        'projected_balance' => 'decimal:2',
        'derived_balance' => 'decimal:2',
        'difference' => 'decimal:2',
        'snapshot_at' => 'datetime',
    ];
}
