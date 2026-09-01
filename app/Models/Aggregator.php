<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Aggregator extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'user_id', 'code', 'name', 'status', 'country_iso2', 'region', 'city',
        'kyc_status', 'commission_override_rate',
    ];

    protected $casts = [
        'commission_override_rate' => 'decimal:4',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class);
    }

    public function floatAccount(string $currency): ?\App\Domain\Accounting\LedgerAccount
    {
        return \App\Domain\Accounting\LedgerAccount::query()
            ->where('owner_type', 'aggregator')
            ->where('owner_id', $this->id)
            ->where('currency_code', $currency)
            ->first();
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
