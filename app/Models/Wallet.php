<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $fillable = [
        'user_id', 'currency_code', 'balance', 'frozen_balance', 'is_primary'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}