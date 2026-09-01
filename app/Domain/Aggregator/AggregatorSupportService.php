<?php

namespace App\Domain\Aggregator;

use App\Models\Aggregator;
use App\Models\AuditLog;
use App\Models\SupportReply;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * AGGREGATOR CONSOLE — Stage H (support center, §59–62).
 *
 * The aggregator sees tickets attributed to their network: tickets raised in
 * the console (aggregator_id set) plus tickets raised by the network's users
 * (the aggregator account itself or its agents). SLA is computed from
 * priority at creation and counted down honestly (within / overdue / none).
 *
 * Every privileged action (raise, reply, status, priority) is audited.
 */
class AggregatorSupportService
{
    public function __construct(private readonly AggregatorTenantService $tenant)
    {
    }

    /** Fixed category set exposed to the UI (§59). */
    public function categories(): array
    {
        return SupportTicket::CATEGORIES;
    }

    /** Priorities with their SLA budget in hours (§61). */
    public function priorities(): array
    {
        return [
            ['key' => SupportTicket::PRIORITY_CRITICAL, 'label' => 'Critical', 'sla_hours' => SupportTicket::SLA_HOURS[SupportTicket::PRIORITY_CRITICAL]],
            ['key' => SupportTicket::PRIORITY_HIGH, 'label' => 'High', 'sla_hours' => SupportTicket::SLA_HOURS[SupportTicket::PRIORITY_HIGH]],
            ['key' => SupportTicket::PRIORITY_MEDIUM, 'label' => 'Medium', 'sla_hours' => SupportTicket::SLA_HOURS[SupportTicket::PRIORITY_MEDIUM]],
            ['key' => SupportTicket::PRIORITY_LOW, 'label' => 'Low', 'sla_hours' => SupportTicket::SLA_HOURS[SupportTicket::PRIORITY_LOW]],
        ];
    }

    /** Tickets attributable to this network, newest first, paginated. */
    public function center(Aggregator $aggregator, array $filters = [], int $perPage = 10, int $page = 1): array
    {
        $query = $this->scoped($aggregator);

        $status = $filters['status'] ?? '';
        if ($status !== '' && in_array($status, ['open', 'in_progress', 'resolved', 'closed'], true)) {
            $query->where('status', $status);
        }

        $priority = $filters['priority'] ?? '';
        if ($priority !== '' && in_array($priority, SupportTicket::SLA_HOURS, true)) {
            $query->where('priority', $priority);
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_id', 'like', '%'.$search.'%')
                    ->orWhere('subject', 'like', '%'.$search.'%');
            });
        }

        $total = (clone $query)->count();
        $tickets = $query->latest('created_at')->forPage($page, $perPage)->get();

        return [
            'tickets' => $tickets->map(fn (SupportTicket $t) => $this->present($t))->all(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'summary' => [
                'open' => $this->scoped($aggregator)->whereIn('status', [SupportTicket::STATUS_OPEN, SupportTicket::STATUS_IN_PROGRESS])->count(),
                'overdue' => $this->scoped($aggregator)
                    ->whereNotIn('status', [SupportTicket::STATUS_RESOLVED, SupportTicket::STATUS_CLOSED])
                    ->whereNotNull('sla_due_at')
                    ->where('sla_due_at', '<', now())
                    ->count(),
                'resolved' => $this->scoped($aggregator)->where('status', SupportTicket::STATUS_RESOLVED)->count(),
            ],
            'basis' => 'Tickets attributed to this network (raised in the console or by the network\'s users).',
        ];
    }

    /**
     * Raise a new support case. SLA due date derived from priority.
     */
    public function raise(Aggregator $aggregator, User $actor, string $category, string $subject, string $message, string $priority): SupportTicket
    {
        if (! in_array($category, SupportTicket::CATEGORIES, true)) {
            throw new \InvalidArgumentException("Unsupported support category [{$category}].");
        }
        if (! isset(SupportTicket::SLA_HOURS[$priority])) {
            throw new \InvalidArgumentException("Unsupported priority [{$priority}].");
        }
        if (trim($subject) === '' || trim($message) === '') {
            throw new \InvalidArgumentException('Subject and message are required.');
        }

        $ticket = SupportTicket::create([
            'user_id' => $actor->id,
            'aggregator_id' => $aggregator->id,
            'ticket_id' => 'SUP-'.strtoupper(Str::random(6)),
            'category' => $category,
            'subject' => trim($subject),
            'message' => trim($message),
            'status' => SupportTicket::STATUS_OPEN,
            'priority' => $priority,
            'sla_due_at' => now()->addHours(SupportTicket::SLA_HOURS[$priority]),
        ]);

        AuditLog::record('support.ticket.created', $actor->id, $actor->id, [
            'description' => "Support case {$ticket->ticket_id} raised (".$ticket->category.', '.$ticket->priority.').',
            'event_type' => 'operations',
            'metadata' => ['support_ticket_id' => $ticket->id, 'ticket_id' => $ticket->ticket_id, 'aggregator_id' => $aggregator->id],
        ]);

        return $ticket;
    }

    public function reply(SupportTicket $ticket, User $actor, string $message, bool $isInternal = false): SupportReply
    {
        if (trim($message) === '') {
            throw new \InvalidArgumentException('Reply message is required.');
        }

        $reply = SupportReply::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $actor->id,
            'message' => trim($message),
            'is_internal' => $isInternal,
        ]);

        if ($ticket->status === SupportTicket::STATUS_OPEN) {
            $ticket->forceFill(['status' => SupportTicket::STATUS_IN_PROGRESS])->save();
        }

        AuditLog::record('support.ticket.replied', $actor->id, $ticket->user_id, [
            'description' => "Reply added to support case {$ticket->ticket_id} (".($isInternal ? 'internal note' : 'customer-facing').').',
            'event_type' => 'operations',
            'metadata' => ['support_ticket_id' => $ticket->id, 'ticket_id' => $ticket->ticket_id, 'is_internal' => $isInternal],
        ]);

        return $reply;
    }

    public function setStatus(SupportTicket $ticket, User $actor, string $status): SupportTicket
    {
        $allowed = [SupportTicket::STATUS_OPEN, SupportTicket::STATUS_IN_PROGRESS, SupportTicket::STATUS_RESOLVED, SupportTicket::STATUS_CLOSED];
        if (! in_array($status, $allowed, true)) {
            throw new \InvalidArgumentException("Unsupported status [{$status}].");
        }

        $ticket->forceFill([
            'status' => $status,
            'resolved_at' => in_array($status, [SupportTicket::STATUS_RESOLVED, SupportTicket::STATUS_CLOSED], true)
                ? ($ticket->resolved_at ?? now())
                : null,
            'resolved_by' => in_array($status, [SupportTicket::STATUS_RESOLVED, SupportTicket::STATUS_CLOSED], true)
                ? ($ticket->resolved_by ?? $actor->id)
                : null,
        ])->save();

        AuditLog::record('support.ticket.status', $actor->id, $ticket->user_id, [
            'description' => "Support case {$ticket->ticket_id} moved to [{$status}].", 'event_type' => 'operations',
            'metadata' => ['support_ticket_id' => $ticket->id, 'ticket_id' => $ticket->ticket_id, 'status' => $status],
        ]);

        return $ticket;
    }

    /** Change priority and recompute the SLA deadline (honest re-baseline). */
    public function setPriority(SupportTicket $ticket, User $actor, string $priority): SupportTicket
    {
        if (! isset(SupportTicket::SLA_HOURS[$priority])) {
            throw new \InvalidArgumentException("Unsupported priority [{$priority}].");
        }

        $ticket->forceFill([
            'priority' => $priority,
            'sla_due_at' => now()->addHours(SupportTicket::SLA_HOURS[$priority]),
        ])->save();

        AuditLog::record('support.ticket.priority', $actor->id, $ticket->user_id, [
            'description' => "Support case {$ticket->ticket_id} priority set to [{$priority}] — SLA rebased.", 'event_type' => 'operations',
            'metadata' => ['support_ticket_id' => $ticket->id, 'ticket_id' => $ticket->ticket_id, 'priority' => $priority],
        ]);

        return $ticket;
    }

    /** Does this ticket belong to the aggregator's network? (IDOR guard, §133.) */
    public function owned(SupportTicket $ticket, Aggregator $aggregator): bool
    {
        return $this->scoped($aggregator)->whereKey($ticket->id)->exists();
    }

    protected function scoped(Aggregator $aggregator): \Illuminate\Database\Eloquent\Builder
    {
        $networkUserIds = app(AggregatorTenantService::class)->agents($aggregator)
            ->pluck('user_id')->push($aggregator->user_id)->filter()->values()->all();

        return SupportTicket::query()->where(function ($q) use ($aggregator, $networkUserIds) {
            $q->where('aggregator_id', $aggregator->id)
                ->orWhereIn('user_id', $networkUserIds);
        });
    }

    protected function present(SupportTicket $ticket): array
    {
        $sla = $ticket->slaStatus();

        return [
            'id' => $ticket->id,
            'ticket_id' => $ticket->ticket_id,
            'category' => $ticket->category,
            'subject' => $ticket->subject,
            'message' => $ticket->message,
            'status' => $ticket->status,
            'priority' => $ticket->priority,
            'sla' => $sla,
            'replies_count' => $ticket->replies()->count(),
            'raised_by' => $ticket->user?->name,
            'created_at' => $ticket->created_at?->toIso8601String(),
            'resolved_at' => $ticket->resolved_at?->toIso8601String(),
            'replies' => $ticket->replies->map(fn (SupportReply $r) => [
                'id' => $r->id,
                'author' => $r->user?->name ?? 'System',
                'message' => $r->message,
                'is_internal' => $r->is_internal,
                'created_at' => $r->created_at?->toIso8601String(),
            ])->all(),
        ];
    }
}
