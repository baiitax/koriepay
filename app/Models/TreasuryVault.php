<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreasuryVault extends Model
{
    protected $fillable = [
        'currency_code', 
        'bank_name', 
        'account_number', 
        'physical_balance', 
        'last_reconciled_at'
    ];

    protected $casts = [
        'physical_balance' => 'decimal:6',
        'last_reconciled_at' => 'datetime',
    ];
}