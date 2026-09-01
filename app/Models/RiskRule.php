<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A data-driven risk rule. Condition schema (documented, evaluated by
 * RiskService — never raw code per rule):
 *
 *   amount_exceeds          {"amount": "500000.00"}
 *   failed_attempts_exceed  {"count": 5}
 *   velocity_count_exceeds  {"count": 10, "window_minutes": 60}
 *   success_rate_below      {"rate": 95.0}
 */
class RiskRule extends Model
{
    public const CATEGORY_FRAUD = 'fraud';
    public const CATEGORY_AML = 'aml';
    public const CATEGORY_VELOCITY = 'velocity';
    public const CATEGORY_GEOGRAPHIC = 'geographic';
    public const CATEGORY_ANOMALY = 'anomaly';

    protected $fillable = [
        'code', 'name', 'category', 'entity_type', 'condition_type',
        'condition_config', 'severity', 'risk_score', 'priority',
        'country_iso2', 'dedupe_window_minutes', 'is_active',
    ];

    protected $casts = [
        'condition_config' => 'array',
        'risk_score' => 'decimal:2',
        'priority' => 'integer',
        'dedupe_window_minutes' => 'integer',
        'is_active' => 'boolean',
    ];
}
