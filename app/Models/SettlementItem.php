<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SettlementItem extends Model
{
    protected $fillable = [
        'settlement_id', 'transaction_id', 'amount', 'currency_code', 'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}
