<?php

namespace App\Domain\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An immutable ledger posting: one reference, one balanced batch of entries.
 * Once created, neither this row nor its entries are ever updated or deleted.
 */
class LedgerTransaction extends Model
{
    protected $table = 'ledger_transactions';

    protected $fillable = [
        'reference',
        'type',
        'related_transaction_id',
        'idempotency_key',
        'description',
        'created_by',
        'approved_by',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class, 'ledger_transaction_id');
    }

    public static function generateReference(): string
    {
        return 'LEDGER-'.strtoupper(bin2hex(random_bytes(6)));
    }
}
