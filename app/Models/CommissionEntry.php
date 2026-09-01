<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A resolved commission split for one beneficiary (agent | aggregator |
 * platform). Accrued at operation time, paid later — never silently reversed.
 */
class CommissionEntry extends Model
{
    protected $fillable = [
        'ledger_transaction_id', 'transaction_id', 'beneficiary_id',
        'beneficiary_type', 'rule_id', 'currency_code', 'amount', 'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}
