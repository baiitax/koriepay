<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdashiMember extends Model
{
    protected $guarded = [];

    // A member belongs to a specific Adashi Group
    public function group()
    {
        return $this->belongsTo(AdashiGroup::class, 'adashi_group_id');
    }

    // A member belongs to a specific User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}