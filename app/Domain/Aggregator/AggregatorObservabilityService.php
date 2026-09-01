<?php

namespace App\Domain\Aggregator;

use App\Domain\Accounting\LedgerEntry;
use App\Models\Aggregator;
use App\Models\RiskAlert;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\DB;

/**
 * AGGREGATOR CONSOLE — Stage I (observability indicators, §96–97).
 *
 * Honest health signals computed from real data:
 *   - ledger_drift : does the balance projection match the summed entries?
 *   - queue_failed : failed background jobs (global, honest);
 *   - pending_reports : report jobs stuck queued/processing;
 *   - open_alerts : unresolved risk alerts;
 *   - stale_agents : agents with no operation in 30 days;
 *   - support_overdue : SLA-overdue open support cases.
 * Unknown/unmeasurable states are reported as unknown, never as healthy.
 */
class AggregatorObservabilityService
{
    public function __construct(private readonly AggregatorTenantService $tenant)
    {
    }

    public function indicators(Aggregator $aggregator): array
    {
        $indicators = [
            $this->ledgerDrift($aggregator),
            $this->queueFailed(),
            $this->pendingReports($aggregator),
            $this->openAlerts($aggregator),
            $this->staleAgents($aggregator),
            $this->supportOverdue($aggregator),
        ];

        return [
            'indicators' => $indicators,
            'status' => $this->overall($indicators),
            'generated_at' => now()->toIso8601String(),
            'basis' => 'Signals recomputed from live records at render time — nothing is cached.',
        ];
    }

    public function overall(array $indicators): string
    {
        $statuses = array_column($indicators, 'status');

        if (in_array('critical', $statuses, true)) {
            return 'critical';
        }
        if (in_array('degraded', $statuses, true)) {
            return 'degraded';
        }
        if (in_array('unknown', $statuses, true)) {
            return 'unknown';
        }

        return 'operational';
    }

    /** Recompute each float's balance from its entries and compare with the projection. */
    protected function ledgerDrift(Aggregator $aggregator): array
    {
        $accounts = \App\Domain\Accounting\LedgerAccount::query()
            ->where('owner_type', 'agent')
            ->whereIn('owner_id', $this->tenant->agentIds($aggregator))
            ->orWhere(fn ($q) => $q->where('owner_type', 'aggregator')->where('owner_id', $aggregator->id))
            ->get();

        $drift = 0;
        foreach ($accounts as $account) {
            $debits = (string) LedgerEntry::where('account_id', $account->id)->where('side', 'debit')->sum('amount');
            $credits = (string) LedgerEntry::where('account_id', $account->id)->where('side', 'credit')->sum('amount');
            $derived = $account->isDebitNormal()
                ? bcsub($debits, $credits, 2)
                : bcsub($credits, $debits, 2);

            if (bccomp($derived, (string) $account->balance, 2) !== 0) {
                $drift++;
            }
        }

        return [
            'key' => 'ledger_drift',
            'label' => 'Ledger projection drift',
            'status' => $drift > 0 ? 'critical' : 'ok',
            'value' => (string) $drift,
            'explanation' => $drift > 0
                ? $drift.' float account(s) where the balance projection disagrees with the summed ledger entries — reconcile immediately.'
                : 'All float balance projections match the ledger entries for this network.',
            'source' => 'ledger_entries vs ledger_accounts.balance',
        ];
    }

    protected function queueFailed(): array
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('failed_jobs')) {
            return ['key' => 'queue_failed', 'label' => 'Failed background jobs', 'status' => 'unknown', 'value' => 'unknown', 'explanation' => 'The failed-jobs table does not exist on this environment.', 'source' => 'failed_jobs'];
        }

        $count = (int) DB::table('failed_jobs')->count();

        return [
            'key' => 'queue_failed',
            'label' => 'Failed background jobs',
            'status' => $count > 0 ? 'degraded' : 'ok',
            'value' => (string) $count,
            'explanation' => $count > 0 ? $count.' failed background job(s) — investigate the queue worker logs.' : 'No failed background jobs.',
            'source' => 'failed_jobs',
        ];
    }

    protected function pendingReports(Aggregator $aggregator): array
    {
        $count = (int) \App\Models\ReportJob::where('aggregator_id', $aggregator->id)
            ->whereIn('status', ['queued', 'processing'])
            ->count();

        return [
            'key' => 'pending_reports',
            'label' => 'Reports in flight',
            'status' => $count > 2 ? 'degraded' : 'ok',
            'value' => (string) $count,
            'explanation' => $count > 0 ? $count.' report(s) queued or generating.' : 'No reports in flight.',
            'source' => 'report_jobs',
        ];
    }

    protected function openAlerts(Aggregator $aggregator): array
    {
        $agentIds = $this->tenant->agentIds($aggregator);
        $count = (int) RiskAlert::where(function ($q) use ($agentIds, $aggregator) {
            $q->where(fn ($w) => $w->where('entity_type', 'agent')->whereIn('entity_id', $agentIds))
                ->orWhere(fn ($w) => $w->where('entity_type', 'aggregator')->where('entity_id', $aggregator->id));
        })->whereNotIn('status', ['resolved', 'false_positive'])->count();

        return [
            'key' => 'open_alerts',
            'label' => 'Open risk alerts',
            'status' => $count > 3 ? 'degraded' : ($count > 0 ? 'attention' : 'ok'),
            'value' => (string) $count,
            'explanation' => $count > 0 ? $count.' unresolved alert(s) in the network.' : 'No unresolved risk alerts.',
            'source' => 'risk_alerts',
        ];
    }

    protected function staleAgents(Aggregator $aggregator): array
    {
        $agentIds = $this->tenant->agentIds($aggregator);
        $count = 0;
        if ($agentIds !== []) {
            $recent = \App\Models\AgencyOperation::whereIn('agent_id', $agentIds)
                ->where('created_at', '>=', now()->subDays(30))
                ->distinct()->pluck('agent_id')->all();
            $count = count($agentIds) - count($recent);
        }

        return [
            'key' => 'stale_agents',
            'label' => 'Dormant agents (30d)',
            'status' => $count >= max(1, intdiv((int) count($agentIds), 2)) ? 'degraded' : 'ok',
            'value' => (string) $count,
            'explanation' => $count > 0 ? $count.' agent(s) with no operation in the last 30 days.' : 'Every agent transacted within the last 30 days.',
            'source' => 'agency_operations (30-day window)',
        ];
    }

    protected function supportOverdue(Aggregator $aggregator): array
    {
        $count = (int) SupportTicket::where('aggregator_id', $aggregator->id)
            ->whereNotIn('status', ['resolved', 'closed'])
            ->whereNotNull('sla_due_at')
            ->where('sla_due_at', '<', now())
            ->count();

        return [
            'key' => 'support_overdue',
            'label' => 'SLA-overdue cases',
            'status' => $count > 0 ? 'degraded' : 'ok',
            'value' => (string) $count,
            'explanation' => $count > 0 ? $count.' open support case(s) past their SLA deadline.' : 'No support cases past their SLA deadline.',
            'source' => 'support_tickets.sla_due_at',
        ];
    }
}
