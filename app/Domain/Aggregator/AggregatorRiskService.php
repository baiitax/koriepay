<?php

namespace App\Domain\Aggregator;

use App\Models\AgencyOperation;
use App\Models\Aggregator;
use App\Models\AuditLog;
use App\Models\RiskAlert;
use Illuminate\Validation\ValidationException;

/**
 * AGGREGATOR CONSOLE — Stage G (risk & alerts, §52–57, §142–143).
 *
 * Velocity monitoring and pattern signals are derived from REAL operations
 * and are ALWAYS labelled "risk signal" — never "fraud confirmed". Alert
 * severities are normalized to INFO → CRITICAL for display. The alert
 * workflow (detected → assigned → investigating → resolved / false
 * positive) is audited and permission-gated server-side. Notifications are
 * open alerts grouped + deduplicated — no separate notification store.
 */
class AggregatorRiskService
{
    public const SEVERITY_MAP = [
        'P0' => 'critical', 'P1' => 'high', 'P2' => 'medium', 'P3' => 'low',
        'critical' => 'critical', 'high' => 'high', 'medium' => 'medium', 'low' => 'low', 'info' => 'info',
    ];

    // ── Velocity monitoring ─────────────────────────────────────────────

    public function velocity(Aggregator $aggregator): array
    {
        $rows = [];
        $since = now()->subHours(24);

        $opsByAgent = AgencyOperation::query()
            ->where('aggregator_id', $aggregator->id)
            ->where('created_at', '>=', $since)
            ->get()
            ->groupBy('agent_id');

        $agents = app(AggregatorTenantService::class)->agents($aggregator)->with('user')->get()->keyBy('id');

        foreach ($opsByAgent as $agentId => $ops) {
            $agent = $agents->get($agentId);
            if ($agent === null) {
                continue;
            }

            $count = $ops->count();
            $perHour = $count / 24;
            $volume = (float) $ops->where('status', 'posted')->sum('amount');
            $cashOut24h = (float) $ops->where('status', 'posted')
                ->where('operation_type', AgencyOperation::TYPE_CASH_OUT)->sum('amount');

            // Thresholds (§52): >5 ops in the trailing hour, or cash-out in
            // the last 24h above 3× the agent's 7-day daily cash-out average.
            $opsLastHour = $ops->where('created_at', '>=', now()->subHour())->count();
            $avg7d = $this->agentAverageCashOut($agentId);
            $threshold = $avg7d * 3;

            $reasons = [];
            if ($opsLastHour > 5) {
                $reasons[] = 'velocity: '.$opsLastHour.' ops in the last hour exceeds the 5/hour threshold';
            }
            if ($threshold > 0 && $cashOut24h > $threshold) {
                $reasons[] = 'cash-out in the last 24h ('.number_format($cashOut24h, 2, '.', '').') exceeds 3× the 7-day daily average ('.number_format($threshold, 2, '.', '').')';
            }

            $rows[] = [
                'agent_id' => $agent->id,
                'agent_code' => $agent->agent_code,
                'name' => $agent->user?->name,
                'ops_24h' => $count,
                'ops_last_hour' => $opsLastHour,
                'ops_per_hour' => round($perHour, 2),
                'volume_24h' => number_format($volume, 2, '.', ''),
                'cash_out_24h' => number_format($cashOut24h, 2, '.', ''),
                'cash_out_avg_7d' => number_format($avg7d, 2, '.', ''),
                'cash_out_threshold' => number_format($threshold, 2, '.', ''),
                'flag' => $reasons === [] ? 'normal' : 'elevated',
                'reasons' => $reasons,
                'estimate' => true,
            ];
        }

        usort($rows, fn ($a, $b) => $b['ops_24h'] <=> $a['ops_24h']);

        return [
            'rows' => $rows,
            'elevated' => collect($rows)->where('flag', 'elevated')->count(),
            'basis' => 'Operations in the last 24h per agent; thresholds documented (§52): 5 ops/hour and 3× the 7-day cash-out daily average.',
        ];
    }

    protected function agentAverageCashOut(int $agentId): float
    {
        $total = (float) AgencyOperation::query()
            ->where('agent_id', $agentId)
            ->where('status', 'posted')
            ->where('operation_type', AgencyOperation::TYPE_CASH_OUT)
            ->where('created_at', '>=', now()->subDays(7)->startOfDay())
            ->sum('amount');

        return $total / 7;
    }

    // ── Collusion signals (labelled risk signals, never fraud) ──────────

    public function collusionSignals(Aggregator $aggregator, int $windowMinutes = 60): array
    {
        $agentIds = app(AggregatorTenantService::class)->agentIds($aggregator);

        $ops = AgencyOperation::query()
            ->whereIn('agent_id', $agentIds)
            ->where('status', 'posted')
            ->where('customer_user_id', '!=', null)
            ->where('created_at', '>=', now()->subDays(7)->startOfDay())
            ->orderBy('created_at')
            ->get();

        $signals = [];
        foreach ($ops->groupBy('customer_user_id') as $customerId => $customerOps) {
            $distinctAgents = $customerOps->pluck('agent_id')->unique();
            if ($distinctAgents->count() < 2) {
                continue;
            }

            // Same customer at 2+ distinct agents within the window.
            foreach ($distinctAgents as $a1) {
                foreach ($distinctAgents as $a2) {
                    if ($a1 >= $a2) {
                        continue;
                    }
                    $pair = $customerOps->where('agent_id', $a1)->merge($customerOps->where('agent_id', $a2))->sortBy('created_at')->values();
                    for ($i = 0; $i < $pair->count() - 1; $i++) {
                        $delta = $pair[$i + 1]->created_at->diffInMinutes($pair[$i]->created_at);
                        if ($delta <= $windowMinutes && $pair[$i]->agent_id !== $pair[$i + 1]->agent_id) {
                            $signals[] = [
                                'customer_user_id' => $customerId,
                                'agents' => [$a1, $a2],
                                'window_minutes' => $windowMinutes,
                                'observed_delta_minutes' => $delta,
                                'ops_count' => $pair->count(),
                                'label' => 'Risk signal (pattern): one customer transacted at two different agents within '.$windowMinutes.' minutes.',
                                'disclaimer' => 'Pattern signal only — not a fraud conclusion. Review the transactions before acting.',
                            ];
                            break 2;
                        }
                    }
                }
            }
        }

        return [
            'signals' => $signals,
            'total' => count($signals),
            'basis' => 'Posted operations in the last 7 days, same customer at 2+ agents within '.$windowMinutes.' minutes.',
            'warning' => 'These are risk signals, never fraud confirmations.',
        ];
    }

    // ── KYC inconsistencies ─────────────────────────────────────────────

    public function kycInconsistencies(Aggregator $aggregator): array
    {
        $rows = [];
        foreach (app(AggregatorTenantService::class)->agents($aggregator)->with('user')->get() as $agent) {
            $userKyc = strtolower((string) $agent->user?->kyc_status);

            if ($agent->kyc_status === 'verified' && $userKyc !== 'verified') {
                $rows[] = [
                    'agent_id' => $agent->id,
                    'agent_code' => $agent->agent_code,
                    'name' => $agent->user?->name,
                    'agent_kyc' => $agent->kyc_status,
                    'user_kyc' => $userKyc ?: 'unverified',
                    'type' => 'kyc_mismatch',
                    'message' => 'Agent record says verified but the linked user record is '.($userKyc ?: 'unverified').'.',
                ];
            }

            if ($agent->kyc_status === 'rejected' && $agent->status === Agent::STATUS_ACTIVE) {
                $rows[] = [
                    'agent_id' => $agent->id,
                    'agent_code' => $agent->agent_code,
                    'name' => $agent->user?->name,
                    'type' => 'rejected_still_active',
                    'message' => 'Agent KYC was rejected but the agent is still active.',
                ];
            }
        }

        return [
            'rows' => $rows,
            'total' => count($rows),
            'basis' => 'Cross-check of agent.kyc_status vs users.kyc_status and status flags.',
        ];
    }

    // ── Alerts ──────────────────────────────────────────────────────────

    public function alerts(Aggregator $aggregator, string $status = 'all'): array
    {
        $agentIds = app(AggregatorTenantService::class)->agentIds($aggregator);
        $agents = app(AggregatorTenantService::class)->agents($aggregator)->with('user')->get()->keyBy('id');

        $query = RiskAlert::query()
            ->where(function ($q) use ($agentIds, $aggregator) {
                $q->where(fn ($qq) => $qq->where('entity_type', 'agent')->whereIn('entity_id', $agentIds))
                    ->orWhere(fn ($qq) => $qq->where('entity_type', 'aggregator')->where('entity_id', $aggregator->id));
            })
            ->latest('created_at');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        return $query->get()->map(function (RiskAlert $alert) use ($agents) {
            $agent = $alert->entity_type === 'agent' ? $agents->get($alert->entity_id) : null;

            return [
                'id' => $alert->id,
                'reference' => $alert->reference,
                'category' => $alert->category,
                'severity' => $this->displaySeverity($alert->severity),
                'raw_severity' => $alert->severity,
                'status' => $alert->status,
                'message' => $alert->message,
                'details' => $alert->details,
                'risk_score' => (string) $alert->risk_score,
                'affected' => $agent !== null
                    ? ['type' => 'agent', 'name' => $agent->user?->name, 'code' => $agent->agent_code]
                    : ['type' => $alert->entity_type, 'name' => null, 'code' => null],
                'created_at' => $alert->created_at,
                'assigned_to' => $alert->assigned_to,
                'resolved_at' => $alert->resolved_at,
                'resolution_note' => $alert->resolution_note,
            ];
        })->all();
    }

    public function displaySeverity(string $severity): string
    {
        return self::SEVERITY_MAP[$severity] ?? self::SEVERITY_MAP[strtolower($severity)] ?? 'info';
    }

    // ── Alert workflow (audited, permission-gated in the UI) ────────────

    public function assign(RiskAlert $alert, int $actorId): RiskAlert
    {
        $this->guardState($alert, RiskAlert::STATUS_OPEN, 'must be open (detected) before it can be assigned');

        $alert->forceFill([
            'status' => RiskAlert::STATUS_ACKNOWLEDGED,
            'assigned_to' => $actorId,
            'assigned_at' => now(),
        ])->save();

        AuditLog::record('risk.alert.assigned', $actorId, $this->targetUserId($alert), [
            'description' => "Risk alert {$alert->reference} assigned to user #{$actorId}.",
            'event_type' => 'security',
            'metadata' => ['risk_alert_id' => $alert->id, 'reference' => $alert->reference],
        ]);

        return $alert;
    }

    public function investigate(RiskAlert $alert, int $actorId): RiskAlert
    {
        $this->guardState($alert, RiskAlert::STATUS_ACKNOWLEDGED, 'must be assigned before investigation starts');

        $alert->forceFill([
            'status' => RiskAlert::STATUS_INVESTIGATING,
            'assigned_to' => $alert->assigned_to ?? $actorId,
            'assigned_at' => $alert->assigned_at ?? now(),
        ])->save();

        AuditLog::record('risk.alert.investigating', $actorId, $this->targetUserId($alert), [
            'description' => "Risk alert {$alert->reference} moved to investigating.",
            'event_type' => 'security',
            'metadata' => ['risk_alert_id' => $alert->id],
        ]);

        return $alert;
    }

    public function resolve(RiskAlert $alert, int $actorId, ?string $note = null): RiskAlert
    {
        $this->guardState($alert, RiskAlert::STATUS_INVESTIGATING, 'must be under investigation before it can be resolved');

        $alert->forceFill([
            'status' => RiskAlert::STATUS_RESOLVED,
            'resolved_by' => $actorId,
            'resolved_at' => now(),
            'resolution_note' => $note ?: 'Resolved by the aggregator.',
        ])->save();

        AuditLog::record('risk.alert.resolved', $actorId, $this->targetUserId($alert), [
            'description' => "Risk alert {$alert->reference} resolved — ".$alert->resolution_note,
            'event_type' => 'security',
            'metadata' => ['risk_alert_id' => $alert->id],
        ]);

        return $alert;
    }

    public function falsePositive(RiskAlert $alert, int $actorId, ?string $note = null): RiskAlert
    {
        $this->guardState($alert, RiskAlert::STATUS_INVESTIGATING, 'must be under investigation before it can be marked a false positive');

        $alert->forceFill([
            'status' => RiskAlert::STATUS_FALSE_POSITIVE,
            'resolved_by' => $actorId,
            'resolved_at' => now(),
            'resolution_note' => $note ?: 'Marked as a false positive.',
        ])->save();

        AuditLog::record('risk.alert.false_positive', $actorId, $this->targetUserId($alert), [
            'description' => "Risk alert {$alert->reference} marked false positive — ".$alert->resolution_note,
            'event_type' => 'security',
            'metadata' => ['risk_alert_id' => $alert->id],
        ]);

        return $alert;
    }

    protected function guardState(RiskAlert $alert, string $required, string $why): void
    {
        if ($alert->status !== $required) {
            $label = in_array($alert->status, [RiskAlert::STATUS_RESOLVED, RiskAlert::STATUS_FALSE_POSITIVE], true)
                ? 'closed alerts are immutable'
                : 'expected status ['.$required.'] but found ['.$alert->status.']';

            throw ValidationException::withMessages([
                'status' => 'Risk alert '.$alert->reference.' '.$why.' — '.$label.'.',
            ]);
        }
    }

    protected function targetUserId(RiskAlert $alert): int
    {
        $agent = \App\Models\Agent::find($alert->entity_id);

        return $agent?->user_id ?? $alert->entity_id;
    }

    // ── Notifications (grouped + deduplicated open alerts) ──────────────

    public function notifications(Aggregator $aggregator): array
    {
        $alerts = $this->alerts($aggregator, 'open');

        $grouped = collect($alerts)->groupBy(fn ($a) => $a['category'].'|'.$a['severity'])->map(function ($group) {
            $first = $group->first();

            return [
                'category' => $first['category'],
                'severity' => $first['severity'],
                'count' => $group->count(), // deduplicated: each alert reference appears once
                'latest' => $group->sortByDesc('created_at')->first()['created_at']->diffForHumans(),
                'references' => $group->pluck('reference')->all(),
            ];
        })->values()
            ->sortBy(fn ($g) => array_search($g['severity'], ['critical', 'high', 'medium', 'low', 'info'], true))
            ->values()->all();

        return [
            'groups' => $grouped,
            'total' => count($alerts),
            'basis' => 'Open alerts grouped by category + severity; each alert counted once.',
        ];
    }
}
