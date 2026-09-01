<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FxRate extends Model
{
    /**
     * The attributes that are mass assignable.
     * Tier-1 Tip: Always explicitly define these to prevent injection.
     */
    
    protected $fillable = [
    'pair', 
    'mid_market_rate', 
    'corporate_spread', 
    'volatility_buffer', // <-- The missing piece
    // 'retail_spread', 
    'effective_rate', 
    'status'
];

    /**
     * Ensure the rate is always treated as a float/decimal in PHP
     */
    protected $casts = [
        'rate' => 'decimal:6',
        'is_active' => 'boolean',
    ];
}