<?php

namespace App\Domain\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single DR/CR line of an immutable ledger posting.
 * Append-only: never UPDATE, never DELETE (enforced by service layer; add a
 * DB trigger in production deployments).
 */
class LedgerEntry extends Model
{
    public const SIDE_DEBIT = 'debit';
    public const SIDE_CREDIT = 'credit';

    protected $table = 'ledger_entries';

    protected $fillable = [
        'ledger_transaction_id',
        'account_id',
        'side',
        'amount',
        'currency_code',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(LedgerTransaction::class, 'ledger_transaction_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class, 'account_id');
    }
}
