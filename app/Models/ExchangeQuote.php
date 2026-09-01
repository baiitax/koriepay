<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A server-authoritative, expiring currency-exchange quote (§39/§91).
 * The frontend never computes the rate; execute() revalidates expiry,
 * ownership, balance, limits and KYC before any money moves.
 */
class ExchangeQuote extends Model
{
    public const STATUS_CREATED = 'created';
    public const STATUS_USED = 'used';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'quote_id', 'user_id', 'from_wallet_id', 'to_wallet_id',
        'from_currency', 'to_currency', 'source_amount', 'destination_amount',
        'exchange_rate', 'exchange_fee', 'total_debit', 'status', 'expires_at',
    ];

    protected $casts = [
        'source_amount' => 'decimal:2',
        'destination_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'exchange_fee' => 'decimal:2',
        'total_debit' => 'decimal:2',
        'expires_at' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return now()->greaterThan($this->expires_at);
    }
}
