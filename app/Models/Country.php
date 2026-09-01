<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Launch-market configuration (Phase 2). Nigeria + Niger are seeded;
 * Ghana/Benin/Togo/Côte d'Ivoire/Senegal/Mali are additive rows.
 */
class Country extends Model
{
    protected $fillable = [
        'iso2', 'iso3', 'name', 'calling_code', 'currency_code',
        'regulator', 'ecosystem', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public static function activeCountries(): array
    {
        return static::query()->where('is_active', true)->orderBy('name')->get()->all();
    }
}
