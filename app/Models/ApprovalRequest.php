<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Maker–checker approval inbox. The maker creates a pending request; only a
 * DIFFERENT user (checker) may approve/reject it — enforced in
 * ApprovalService, never in the frontend (§42 of the Command Center brief).
 */
class ApprovalRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'reference', 'maker_id', 'checker_id', 'action_type', 'entity_type',
        'entity_id', 'payload', 'reason', 'status', 'decided_by',
        'decided_at', 'decision_note', 'sla_due_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'decided_at' => 'datetime',
        'sla_due_at' => 'datetime',
    ];

    public function maker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'maker_id');
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checker_id');
    }
}
