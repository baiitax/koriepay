<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agent extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_TERMINATED = 'terminated';

    public const TIERS = ['bronze', 'silver', 'gold'];

    protected $fillable = [
        'user_id', 'agent_code', 'status', 'tier', 'country_iso2', 'region', 'city',
        'aggregator_id', 'kyc_status', 'risk_score', 'commission_override_rate',
    ];

    protected $casts = [
        'risk_score' => 'decimal:2',
        'commission_override_rate' => 'decimal:4',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function aggregator(): BelongsTo
    {
        return $this->belongsTo(Aggregator::class);
    }

    public function operations(): HasMany
    {
        return $this->hasMany(AgencyOperation::class);
    }

    /**
     * The agent's float ledger account (liability) for a currency.
     */
    public function floatAccount(string $currency): ?\App\Domain\Accounting\LedgerAccount
    {
        return \App\Domain\Accounting\LedgerAccount::query()
            ->where('owner_type', 'agent')
            ->where('owner_id', $this->id)
            ->where('currency_code', $currency)
            ->first();
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
