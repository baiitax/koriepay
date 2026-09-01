<?php

namespace App\Domain\Aggregator;

use App\Models\AgencyOperation;
use App\Models\Agent;
use App\Models\Aggregator;
use App\Models\AggregatorDailyMetric;
use App\Models\AggregatorSettlement;
use App\Models\CommissionEntry;
use App\Models\LiquidityRequest;
use App\Models\RiskAlert;
use Illuminate\Support\Carbon;

/**
 * AGGREGATOR CONSOLE — Stage I (analytical read model + insights, §100–110).
 *
 * `aggregator_daily_metrics` is a DERIVED read model materialized from the
 * real operational records. snapshotDaily() is idempotent per
 * (aggregator, date) — re-running recomputes, never duplicates. Balances and
 * authorizations are NEVER materialized here; the read model only carries
 * day-level aggregates for trend/growth/retention/EOD reporting.
 */
class AggregatorInsightsService
{
    public function __construct(private readonly AggregatorTenantService $tenant)
    {
    }

    /**
     * Pure per-day computation — the single source of truth for both the
     * snapshot writer and live fallback series.
     */
    public function dayAggregates(Aggregator $aggregator, Carbon $date): array
    {
        $agentIds = $this->tenant->agentIds($aggregator);
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        $ops = AgencyOperation::whereIn('agent_id', $agentIds)
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $total = $ops->count();
        $posted = $ops->where('status', 'posted');
        $failed = $ops->where('status', 'failed');

        $settlements = AggregatorSettlement::where('aggregator_id', $aggregator->id)
            ->whereBetween('created_at', [$start, $end])
            ->get();

        return [
            'total_ops' => $total,
            'posted_ops' => $posted->count(),
            'failed_ops' => $failed->count(),
            'volume' => number_format((float) $posted->sum('amount'), 2, '.', ''),
            'commission_accrued' => number_format((float) CommissionEntry::where(function ($q) use ($aggregator, $agentIds) {
                $q->where(fn ($w) => $w->where('beneficiary_type', 'aggregator')->where('beneficiary_id', $aggregator->id))
                    ->orWhere(fn ($w) => $w->where('beneficiary_type', 'agent')->whereIn('beneficiary_id', $agentIds));
            })->whereBetween('created_at', [$start, $end])->sum('amount'), 2, '.', ''),
            'active_agents' => $posted->pluck('agent_id')->unique()->count(),
            'new_agents' => Agent::whereIn('id', $agentIds)->whereBetween('created_at', [$start, $end])->count(),
            'success_rate' => $total > 0 ? round($posted->count() / $total * 100, 2) : 0,
            'failure_rate' => $total > 0 ? round($failed->count() / $total * 100, 2) : 0,
            'settlements_created' => $settlements->count(),
            'settlement_value' => number_format((float) $settlements->sum('net_amount'), 2, '.', ''),
            'is_empty' => $total === 0 && $posted->sum('amount') == 0,
        ];
    }

    /**
     * Idempotent snapshot write for one date. Returns the row.
     */
    public function snapshotDaily(Aggregator $aggregator, Carbon $date): AggregatorDailyMetric
    {
        $a = $this->dayAggregates($aggregator, $date->copy()->startOfDay());

        // NOTE: metric_date is cast to `date` (stored as Y-m-d H:i:s), so the
        // lookup must use the full Carbon value — a bare 'Y-m-d' string never
        // matches in SQLite and would defeat idempotency with a duplicate.
        $row = AggregatorDailyMetric::firstOrNew([
            'aggregator_id' => $aggregator->id,
            'metric_date' => $date->copy()->startOfDay(),
        ]);

        $row->forceFill([
            'total_ops' => $a['total_ops'],
            'posted_ops' => $a['posted_ops'],
            'failed_ops' => $a['failed_ops'],
            'volume' => $a['volume'],
            'commission_accrued' => $a['commission_accrued'],
            'active_agents' => $a['active_agents'],
            'new_agents' => $a['new_agents'],
            'success_rate' => $a['success_rate'],
            'failure_rate' => $a['failure_rate'],
            'settlements_created' => $a['settlements_created'],
            'settlement_value' => $a['settlement_value'],
            'is_empty' => $a['is_empty'],
            'computed_at' => now(),
        ])->save();

        return $row;
    }

    /** Backfill a date range (used by the seeder; idempotent). */
    public function backfill(Aggregator $aggregator, Carbon $from, Carbon $to): int
    {
        $count = 0;
        for ($d = $from->copy()->startOfDay(); $d->lte($to); $d->addDay()) {
            $this->snapshotDaily($aggregator, $d);
            $count++;
        }

        return $count;
    }

    /**
     * Daily series over a range, newest-first. Reads the read model when a
     * snapshot exists for the day; otherwise falls back to the live
     * computation (identical numbers) and labels the row accordingly.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function dailySeries(Aggregator $aggregator, ?string $from = null, ?string $to = null): \Illuminate\Support\Collection
    {
        $toDate = $to ? Carbon::parse($to) : now();
        $fromDate = $from ? Carbon::parse($from) : $toDate->copy()->subDays(13);

        $rows = collect();
        for ($d = $fromDate->copy()->startOfDay(); $d->lte($toDate); $d->addDay()) {
            $snapshot = AggregatorDailyMetric::where('aggregator_id', $aggregator->id)
                ->where('metric_date', $d->copy()->startOfDay())->first();

            if ($snapshot !== null) {
                $rows->push([
                    'date' => $d->toDateString(),
                    'total_ops' => $snapshot->total_ops,
                    'volume' => (string) $snapshot->volume,
                    'commission' => (string) $snapshot->commission_accrued,
                    'active_agents' => $snapshot->active_agents,
                    'new_agents' => $snapshot->new_agents,
                    'success_rate' => (float) $snapshot->success_rate,
                    'source' => 'read_model',
                    'computed_at' => $snapshot->computed_at?->toIso8601String(),
                ]);
                continue;
            }

            $a = $this->dayAggregates($aggregator, $d);
            $rows->push([
                'date' => $d->toDateString(),
                'total_ops' => $a['total_ops'],
                'volume' => $a['volume'],
                'commission' => $a['commission_accrued'],
                'active_agents' => $a['active_agents'],
                'new_agents' => $a['new_agents'],
                'success_rate' => (float) $a['success_rate'],
                'source' => 'live',
                'computed_at' => now()->toIso8601String(),
            ]);
        }

        return $rows->sortByDesc('date')->values();
    }

    /** Monthly growth: new agents per month + cumulative network size. */
    public function growth(Aggregator $aggregator, int $months = 6): array
    {
        $agentIds = $this->tenant->agentIds($aggregator);

        $monthly = collect();
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i)->startOfMonth();
            $new = Agent::whereIn('id', $agentIds)
                ->whereBetween('created_at', [$month, $month->copy()->endOfMonth()])
                ->count();
            $cumulative = Agent::whereIn('id', $agentIds)
                ->where('created_at', '<=', $month->copy()->endOfMonth())
                ->count();

            $monthly->push(['month' => $month->format('Y-m'), 'new' => $new, 'total' => $cumulative]);
        }

        return [
            'monthly' => $monthly->all(),
            'current_total' => count($agentIds),
            'basis' => 'agents.created_at — new registrations and cumulative network size per month.',
        ];
    }

    /** Retention: active rate + honest dormancy buckets. */
    public function retention(Aggregator $aggregator): array
    {
        $agentIds = $this->tenant->agentIds($aggregator);
        $total = count($agentIds);

        $withOps = function (int $days) use ($agentIds) {
            if ($agentIds === []) {
                return 0;
            }

            return AgencyOperation::whereIn('agent_id', $agentIds)
                ->where('created_at', '>=', now()->subDays($days))
                ->distinct()->count('agent_id');
        };

        return [
            'active_30d' => $withOps(30),
            'active_7d' => $withOps(7),
            'total' => $total,
            'active_rate_30d' => $total > 0 ? round($withOps(30) / $total * 100, 1) : null,
            'dormant_30d' => max(0, $total - $withOps(30)),
            'dormant_60d' => max(0, $total - $withOps(60)),
            'dormant_90d' => max(0, $total - $withOps(90)),
            'basis' => 'Agents with ≥1 operation in the window ÷ total agents (honest null on empty networks).',
        ];
    }

    /** Productivity: operations and volume per active agent over the range. */
    public function productivity(Aggregator $aggregator, int $days = 30): array
    {
        $agentIds = $this->tenant->agentIds($aggregator);
        $since = now()->subDays($days)->startOfDay();

        $ops = AgencyOperation::whereIn('agent_id', $agentIds)->where('created_at', '>=', $since)->get();
        $activeIds = $ops->where('status', 'posted')->pluck('agent_id')->unique();
        $activeCount = $activeIds->count();

        return [
            'days' => $days,
            'ops_total' => $ops->count(),
            'ops_per_active_agent' => $activeCount > 0 ? round($ops->count() / $activeCount, 2) : null,
            'volume_per_active_agent' => $activeCount > 0
                ? number_format((float) $ops->where('status', 'posted')->sum('amount') / $activeCount, 2, '.', '')
                : null,
            'active_agents' => $activeCount,
            'basis' => 'Posted/failed operations in the last '.$days.' days across active agents.',
        ];
    }

    /** End-of-day summary (Stage I, §113–116). */
    public function eod(Aggregator $aggregator, ?Carbon $date = null): array
    {
        $date = ($date ?? now())->copy()->startOfDay();
        $a = $this->dayAggregates($aggregator, $date);
        $agentIds = $this->tenant->agentIds($aggregator);

        $snapshot = AggregatorDailyMetric::where('aggregator_id', $aggregator->id)
            ->where('metric_date', $date)->first();

        return [
            'date' => $date->toDateString(),
            'volume' => $a['volume'],
            'transactions' => $a['total_ops'],
            'success_rate' => $a['success_rate'],
            'failed_ops' => $a['failed_ops'],
            'new_agents' => $a['new_agents'],
            'commission_accrued' => $a['commission_accrued'],
            'settlement_value' => $a['settlement_value'],
            'settlements_created' => $a['settlements_created'],
            'open_alerts' => RiskAlert::where(function ($q) use ($agentIds, $aggregator) {
                $q->where(fn ($w) => $w->where('entity_type', 'agent')->whereIn('entity_id', $agentIds))
                    ->orWhere(fn ($w) => $w->where('entity_type', 'aggregator')->where('entity_id', $aggregator->id));
            })->whereNotIn('status', ['resolved', 'false_positive'])->count(),
            'pending_liquidity' => (string) LiquidityRequest::where('aggregator_id', $aggregator->id)
                ->whereIn('status', ['pending', 'in_review', 'approved'])->sum('amount'),
            'snapshot' => $snapshot !== null
                ? ['computed_at' => $snapshot->computed_at?->toIso8601String(), 'is_empty' => $snapshot->is_empty]
                : null,
            'basis' => 'Real records for the day; snapshot column reflects the read-model write when present.',
        ];
    }
}
