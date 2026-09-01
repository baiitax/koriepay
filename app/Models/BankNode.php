<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankNode extends Model
{
    protected $fillable = ['bank_name', 'account_no', 'currency', 'balance', 'api_status', 'last_sync'];
}
