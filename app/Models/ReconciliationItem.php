<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReconciliationItem extends Model
{
    public const STATUS_MATCHED = 'matched';
    public const STATUS_UNMATCHED_INTERNAL = 'unmatched_internal';
    public const STATUS_UNMATCHED_PROVIDER = 'unmatched_provider';
    public const STATUS_AMOUNT_MISMATCH = 'amount_mismatch';
    public const STATUS_DUPLICATE = 'duplicate';

    protected $fillable = [
        'run_id', 'transaction_id', 'provider', 'provider_reference', 'match_key',
        'status', 'internal_amount', 'provider_amount', 'discrepancy', 'resolution',
        'resolved_by', 'resolved_at', 'resolution_note',
    ];

    protected $casts = [
        'internal_amount' => 'decimal:2',
        'provider_amount' => 'decimal:2',
        'discrepancy' => 'decimal:2',
        'resolved_at' => 'datetime',
    ];
}
