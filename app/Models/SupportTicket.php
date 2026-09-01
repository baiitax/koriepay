<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A support ticket. Shared by the customer/agent portals (raised by end
 * users) and the aggregator console (network support center, §59–62).
 *
 * The console attributes tickets to a tenant via `aggregator_id` (set when
 * raised in the console, or resolved through the agent table for network
 * users). SLA is computed from priority at creation (§61).
 */
class SupportTicket extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    public const PRIORITY_CRITICAL = 'critical';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_LOW = 'low';

    /** Business hours budgeted per priority (§61). */
    public const SLA_HOURS = [
        self::PRIORITY_CRITICAL => 4,
        self::PRIORITY_HIGH => 8,
        self::PRIORITY_MEDIUM => 24,
        self::PRIORITY_LOW => 72,
    ];

    public const CATEGORIES = [
        'transaction', 'settlement', 'commission', 'liquidity',
        'agent_onboarding', 'kyc', 'security', 'technical', 'other',
    ];

    protected $fillable = [
        'user_id', 'aggregator_id', 'ticket_id', 'category', 'subject',
        'message', 'status', 'priority', 'sla_due_at', 'resolved_at', 'resolved_by',
    ];

    protected $casts = [
        'sla_due_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function aggregator(): BelongsTo
    {
        return $this->belongsTo(Aggregator::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(SupportReply::class, 'support_ticket_id')->orderBy('created_at');
    }

    /**
     * Honest SLA status. A ticket with no due date has no SLA (none).
     */
    public function slaStatus(): array
    {
        if ($this->sla_due_at === null) {
            return ['status' => 'none', 'remaining_hours' => null, 'due_at' => null];
        }

        $remaining = (float) max(0, now()->diffInMinutes($this->sla_due_at) / 60);

        return [
            'status' => in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED], true)
                ? 'none'
                : ($remaining > 0 ? 'within' : 'overdue'),
            'remaining_hours' => round($remaining, 1),
            'due_at' => $this->sla_due_at->toIso8601String(),
        ];
    }
}
