<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A reply on a support ticket (customer-facing) or an internal note.
 * `is_internal` keeps aggregator-internal commentary out of any future
 * customer-facing view of the thread.
 */
class SupportReply extends Model
{
    protected $fillable = ['support_ticket_id', 'user_id', 'message', 'is_internal'];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
