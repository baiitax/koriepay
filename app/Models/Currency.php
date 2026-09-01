<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Currency configuration (Phase 2). minor_units is authoritative for money
 * precision (NGN=2, XOF=0). Never store money as floats.
 */
class Currency extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'code';

    protected $fillable = [
        'code', 'name', 'symbol', 'minor_units', 'is_fiat', 'is_active',
    ];

    protected $casts = [
        'minor_units' => 'integer',
        'is_fiat' => 'boolean',
        'is_active' => 'boolean',
    ];
}
