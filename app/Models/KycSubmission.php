<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Formal KYC/KYB submission record (Phase 4).
 *
 * The submission is the source of truth for a verification attempt; the
 * user.kyc_status column is a denormalized mirror kept in sync by
 * App\Services\KycWorkflow. Aging/SLA for the dashboard is derived from
 * submitted_at.
 *
 * Statuses: pending → approved | rejected | expired | manual_review.
 * A decision is never "fraud" — it is a risk indicator until formally
 * reviewed (directive §31).
 */
class KycSubmission extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_MANUAL_REVIEW = 'manual_review';

    public const TYPE_PERSONAL = 'personal';
    public const TYPE_BUSINESS = 'business';

    protected $fillable = [
        'user_id', 'type', 'status', 'tier', 'country_code', 'data',
        'reviewer_id', 'reviewed_at', 'rejection_reason', 'submitted_at',
    ];

    protected $casts = [
        'data' => 'array',
        'reviewed_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
