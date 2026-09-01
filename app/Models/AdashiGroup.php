<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdashiGroup extends Model
{
    protected $guarded = [];

    protected $fillable = [
        'creator_id', 'name', 'currency', 'contribution_amount', 
        'max_members', 'frequency', 'start_date', 'invite_code', 'status'
    ];

    // A group has many members
    public function members()
    {
        return $this->hasMany(AdashiMember::class, 'adashi_group_id');
    }

    // A group belongs to the user who created it
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}