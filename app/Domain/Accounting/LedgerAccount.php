<?php

namespace App\Domain\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A ledger account in the KoriePay chart of accounts.
 *
 * The `balance` column is a PROJECTION maintained from ledger_entries — the
 * ledger entries are the source of truth. The projection exists only so reads
 * are fast and so we can reconcile (balance_snapshots) projection vs derived.
 */
class LedgerAccount extends Model
{
    public const TYPE_ASSET = 'asset';
    public const TYPE_LIABILITY = 'liability';
    public const TYPE_EQUITY = 'equity';
    public const TYPE_INCOME = 'income';
    public const TYPE_EXPENSE = 'expense';

    protected $table = 'ledger_accounts';

    protected $fillable = [
        'account_type',
        'currency_code',
        'owner_type',
        'owner_id',
        'name',
        'code',
        'balance',
        'is_system',
        'is_active',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class, 'account_id');
    }

    /**
     * Normal balance direction: for asset/expense accounts a debit increases
     * the balance; for liability/equity/income a credit increases it.
     */
    public function isDebitNormal(): bool
    {
        return in_array($this->account_type, [self::TYPE_ASSET, self::TYPE_EXPENSE], true);
    }

    public function scopeForOwner($query, string $ownerType, int $ownerId, ?string $currency = null)
    {
        $query->where('owner_type', $ownerType)->where('owner_id', $ownerId);

        if ($currency !== null) {
            $query->where('currency_code', strtoupper($currency));
        }

        return $query;
    }

    public static function openingAccount(string $currency): self
    {
        return static::firstOrCreate(
            [
                'code' => 'OPEN-'.strtoupper($currency),
                'currency_code' => strtoupper($currency),
            ],
            [
                'account_type' => self::TYPE_EQUITY,
                'name' => 'Opening Balances '.strtoupper($currency),
                'is_system' => true,
            ]
        );
    }

    public static function revenueAccount(string $currency): self
    {
        return static::firstOrCreate(
            [
                'code' => 'REV-'.strtoupper($currency),
                'currency_code' => strtoupper($currency),
            ],
            [
                'account_type' => self::TYPE_INCOME,
                'name' => 'Platform Revenue '.strtoupper($currency),
                'is_system' => true,
            ]
        );
    }
}
