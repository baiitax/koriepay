<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Customer-facing wallet READ MODEL over the ledger. Balance is never stored
 * here — CustomerWalletService derives it from the linked ledger account
 * (available) and in-flight transactions (pending). The legacy `wallets`
 * table (balance field) is NOT used by the customer app (brief §82, §133).
 */
class CustomerWallet extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_UNAVAILABLE = 'unavailable';

    protected $fillable = [
        'wallet_id', 'user_id', 'currency_code', 'display_name',
        'is_primary', 'status', 'ledger_account_id',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ledgerAccount(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Accounting\LedgerAccount::class, 'ledger_account_id');
    }
}
