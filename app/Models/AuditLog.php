<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    /**
     * PHASE 4 alignment — columns now match the corrected schema
     * (2026_08_31_000900_fix_audit_logs_schema.php). Contract:
     *   admin_id   → acting user (NOT NULL)
     *   user_id    → primary target (NOT NULL)
     *   target_id  → secondary target (nullable)
     *   action     → machine key, e.g. kyc.approved
     *   description→ human-readable summary
     *   event_type → compliance | financial | security | operations | system
     *   metadata   → JSON string (structured before/after)
     *   payload    → legacy JSON field (kept for backward compatibility)
     */
    protected $fillable = [
        'admin_id',
        'user_id',
        'target_id',
        'user_name',
        'action',
        'event_type',
        'description',
        'metadata',
        'payload',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'payload' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Canonical audit write — the only path components should use.
     * Creates the row with real columns and sensible defaults.
     *
     * @param  array{target_id?: int, event_type?: string, description?: string, metadata?: mixed, payload?: mixed, ip_address?: string, user_agent?: string}  $context
     */
    public static function record(string $action, ?int $actorId, int $targetId, array $context = []): self
    {
        $actor = $actorId ? \App\Models\User::find($actorId) : null;

        return static::create([
            'admin_id' => $actorId ?? $targetId, // NOT NULL column; fall back to target for system events
            'user_id' => $targetId,
            'target_id' => $context['target_id'] ?? null,
            'user_name' => $actor?->name ?? $context['user_name'] ?? 'System',
            'action' => $action,
            'event_type' => $context['event_type'] ?? 'operations',
            'description' => $context['description'] ?? null,
            'metadata' => $context['metadata'] ?? null, // array; cast encodes JSON
            'payload' => $context['payload'] ?? null,
            'ip_address' => $context['ip_address'] ?? request()->ip(),
            'user_agent' => $context['user_agent'] ?? request()->userAgent(),
        ]);
    }

    /** Relationship: the admin who performed the action. */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /** Relationship: the user who was the primary target. */
    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Relationship: the agent who was the target (legacy alias). */
    public function targetAgent()
    {
        return $this->belongsTo(User::class, 'target_id');
    }
}
