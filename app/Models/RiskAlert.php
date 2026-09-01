<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A detected risk alert (P0–P3). "Open" until formally reviewed — the alert
 * is a RISK INDICATOR, never a fraud label (§31 of the Command Center brief).
 */
class RiskAlert extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_ACKNOWLEDGED = 'acknowledged';
    public const STATUS_INVESTIGATING = 'investigating';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_FALSE_POSITIVE = 'false_positive';

    protected $fillable = [
        'reference', 'rule_id', 'category', 'severity', 'entity_type',
        'entity_id', 'transaction_id', 'country_iso2', 'message', 'details',
        'risk_score', 'status', 'resolved_by', 'resolved_at', 'resolution_note',
    ];

    protected $casts = [
        'details' => 'array',
        'risk_score' => 'decimal:2',
        'resolved_at' => 'datetime',
    ];
}
