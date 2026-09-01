<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Data-driven country/KYC eligibility for customer wallets (§75).
 * The backend decides which wallets a customer may hold/fund/send/receive;
 * the frontend only renders what these rows permit.
 */
class CustomerWalletConfig extends Model
{
    protected $fillable = [
        'country_iso2', 'currency_code', 'is_available', 'is_primary_default',
        'min_kyc_tier', 'display_name', 'daily_send_limit', 'daily_exchange_limit',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'is_primary_default' => 'boolean',
        'min_kyc_tier' => 'integer',
        'daily_send_limit' => 'decimal:2',
        'daily_exchange_limit' => 'decimal:2',
    ];
}
