<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A commission rule is DATA, never code: it matches a transaction profile
 * (country, type, channel, tier, amount band) and yields a split. The engine
 * resolves the highest-priority matching rule (000500).
 */
class CommissionRule extends Model
{
    protected $fillable = [
        'name', 'country_iso2', 'transaction_type', 'channel', 'agent_tier',
        'customer_segment', 'min_amount', 'max_amount', 'rate', 'flat_amount',
        'priority', 'is_active',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'flat_amount' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];
}
