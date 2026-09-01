<?php

namespace App\Domain\Aggregator;

use App\Domain\Accounting\LedgerAccount;
use App\Models\AgencyOperation;
use App\Models\Agent;
use App\Models\Aggregator;
use App\Models\CommissionEntry;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * AGGREGATOR CONSOLE — Stage A (command-center metrics).
 *
 * Every number is computed from REAL records, scoped to the aggregator's
 * tenant (§3), and clearly typed:
 *   - authoritative: derived from ledger accounts / posted operations;
 *   - estimated: labelled estimates (e.g. liquidity demand from 7-day history).
 * No fabricated figures, no cached-then-stale balances.
 *
 * Money always stays per-currency (XOF and NGN are never mixed); the
 * aggregator's country currency is offered as the display default.
 */
class AggregatorMetricsService
{
    public function __construct(private readonly AggregatorTenantService $tenant)
    {
    }

    /**
     * All home-command-center data in one call.
     *
     * @param  array{range?: string, from?: string, to?: string, agent?: string, region?: string, city?: string, type?: string, status?: string, currency?: string}  $filters
     */
    public function commandCenter(Aggregator $aggregator, array $filters = []): array
    {
        [$from, $to] = $this->resolveRange($filters);
        $agentIds = $this->tenant->agentIds($aggregator);
        $primaryCurrency = $this->primaryCurrency($aggregator);
        $currency = strtoupper((string) ($filters['currency'] ?? '')) ?: $primaryCurrency;

        return [
            'primary_currency' => $primaryCurrency,
            'overview' => $this->overview($aggregator, $agentIds, $from, $to, $currency),
            'attention' => $this->attention($aggregator, $agentIds),
            'brief' => $this->brief($aggregator, $agentIds),
            'series' => $this->series($agentIds, $from, $to, $currency),
            'liquidity' => $this->liquidity($aggregator, $agentIds),
            'top_agents' => $this->topAgents($agentIds, $from, $to, $currency),
            'recent_activity' => $this->recentActivity($agentIds, $currency),
        ];
    }

    /**
     * KPI cards (§10–11) — always with context vs the previous comparable
     * period, never a bare number.
     */
    public function overview(Aggregator $aggregator, array $agentIds, Carbon $from, Carbon $to, string $currency = ''): array
    {
        [$prevFrom, $prevTo] = $this->previousPeriod($from, $to);

        $agents = $this->tenant->agents($aggregator)->get();
        $totalAgents = $agents->count();
        $activeAgents = $agents->where('status', Agent::STATUS_ACTIVE)->count();
        $activeRate = $totalAgents > 0 ? round($activeAgents / $totalAgents * 100, 1) : 0.0;

        $opsQuery = fn (Carbon $f, Carbon $t) => AgencyOperation::query()
            ->whereIn('agent_id', $agentIds)
            ->whereBetween('created_at', [$f, $t]);

        $ops = $opsQuery($from, $to)->get();
        $prevOps = $opsQuery($prevFrom, $prevTo)->get();

        $volume = $this->sumByCurrency($ops->where('status', 'posted'), $currency);
        $prevVolume = $this->sumByCurrency($prevOps->where('status', 'posted'), $currency);

        $commission = $this->commissionSum($aggregator, $from, $to, $currency);
        $prevCommission = $this->commissionSum($aggregator, $prevFrom, $prevTo, $currency);

        $agentsAdded = Agent::query()
            ->where('aggregator_id', $aggregator->id)
            ->whereBetween('created_at', [$from, $to])->count();

        return [
            'total_agents' => $totalAgents,
            'active_agents' => $activeAgents,
            'active_rate' => $activeRate,
            'agents_added' => $agentsAdded,
            'transactions' => $ops->count(),
            'transactions_prev' => $prevOps->count(),
            'volume' => $volume,
            'volume_prev' => $prevVolume,
            'commission' => $commission,
            'commission_prev' => $prevCommission,
            'currency' => $currency ?: $this->primaryCurrency($aggregator),
        ];
    }

    /**
     * ACTION REQUIRED (§12) — real conditions only, grouped (never 100
     * identical alerts, §143).
     */
    public function attention(Aggregator $aggregator, array $agentIds): array
    {
        $items = [];
        $agents = $this->tenant->agents($aggregator)->get();

        if ($agents->isEmpty()) {
            return $items;
        }

        $agentMap = $agents->keyBy('id');
        $lastOpAt = AgencyOperation::query()
            ->whereIn('agent_id', $agentIds)
            ->selectRaw('agent_id, MAX(created_at) as last_at')
            ->groupBy('agent_id')->pluck('last_at', 'agent_id');

        $failed = AgencyOperation::query()
            ->whereIn('agent_id', $agentIds)
            ->selectRaw('agent_id, COUNT(*) total, SUM(CASE WHEN status = \'failed\' THEN 1 ELSE 0 END) failed')
            ->groupBy('agent_id')->get()->keyBy('agent_id');

        // 1. Inactive 7+ days (active agents with no operation in 7 days).
        $inactive = $agents->filter(function (Agent $agent) use ($lastOpAt, $agentMap) {
            if ($agent->status !== Agent::STATUS_ACTIVE) {
                return false;
            }
            $last = $lastOpAt->get($agent->id);
            return $last === null || Carbon::parse($last)->lt(now()->subDays(7));
        });
        if ($inactive->isNotEmpty()) {
            $items[] = $this->attentionItem('agents_inactive', 'medium', $inactive->count(),
                count($inactive).' active agent'.($inactive->count() > 1 ? 's' : '').' inactive for 7+ days',
                'View', $inactive->pluck('id')->all());
        }

        // 2. Low liquidity (float < 7-day average daily cash-out — labelled estimate).
        $low = $this->lowLiquidityAgents($agents, $agentIds);
        if (count($low) > 0) {
            $items[] = $this->attentionItem('liquidity_low', 'high', count($low),
                count($low).' agent'.(count($low) > 1 ? 's' : '').' may need liquidity support (estimate)',
                'Review', array_column($low, 'id'));
        }

        // 3. High failed-operation rate (real data; no fabricated reversals).
        $highFailure = $agents->filter(function (Agent $agent) use ($failed) {
            $row = $failed->get($agent->id);
            if ($row === null || (int) $row->total < 3) {
                return false;
            }
            return ((int) $row->failed / (int) $row->total) > 0.05;
        });
        if ($highFailure->isNotEmpty()) {
            $items[] = $this->attentionItem('failure_rate', 'medium', $highFailure->count(),
                count($highFailure).' agent'.(count($highFailure) > 1 ? 's' : '').' with an unusually high failed-operation rate',
                'Investigate', $highFailure->pluck('id')->all());
        }

        // 4. Restricted agents.
        $restricted = $agents->whereIn('status', [Agent::STATUS_SUSPENDED, Agent::STATUS_TERMINATED]);
        if ($restricted->isNotEmpty()) {
            $items[] = $this->attentionItem('agents_restricted', 'high', $restricted->count(),
                count($restricted).' agent'.(count($restricted) > 1 ? 's' : '').' currently restricted',
                'Review', $restricted->pluck('id')->all());
        }

        // 5. KYC attention.
        $kyc = $agents->whereIn('kyc_status', ['pending', 'unverified']);
        if ($kyc->isNotEmpty()) {
            $items[] = $this->attentionItem('kyc_pending', 'medium', $kyc->count(),
                count($kyc).' agent'.(count($kyc) > 1 ? 's' : '').' with incomplete verification',
                'View', $kyc->pluck('id')->all());
        }

        return $items;
    }

    /**
     * Daily brief (§13) — sentences derived from actual data only.
     */
    public function brief(Aggregator $aggregator, array $agentIds): array
    {
        $today = now()->startOfDay();
        $todayOps = AgencyOperation::query()->whereIn('agent_id', $agentIds)
            ->where('created_at', '>=', $today)->get();
        $yesterdayOps = AgencyOperation::query()->whereIn('agent_id', $agentIds)
            ->whereBetween('created_at', [now()->subDay()->startOfDay(), $today])->get();

        $todayVolume = $this->sumByCurrency($todayOps, '');
        $yesterdayVolume = $this->sumByCurrency($yesterdayOps, '');

        $primary = $this->primaryCurrency($aggregator);
        $todayTotal = $todayVolume[$primary] ?? '0';
        $yesterdayTotal = $yesterdayVolume[$primary] ?? '0';

        $growth = $this->growthPercent($todayTotal, $yesterdayTotal);

        $topRegion = $todayOps->groupBy('agent_id')->map(function ($ops, $agentId) use ($agentIds, $primary) {
            $agent = Agent::find($agentId);
            return [
                'region' => $agent?->region ?? 'unknown',
                'volume' => (string) $ops->where('currency_code', $primary)->sum('amount'),
            ];
        })->sortByDesc('volume')->first();

        $peakHour = $todayOps->groupBy(fn ($op) => Carbon::parse($op->created_at)->format('H'))->map->count()->sortDesc()->keys()->first();

        $low = $this->lowLiquidityAgents(
            $this->tenant->agents($aggregator)->get(), $agentIds
        );

        $sentences = [];
        $sentences[] = sprintf('Your network processed %s %s today%s.',
            number_format((float) $todayTotal), $primary,
            $growth !== null ? ', '.($growth >= 0 ? 'up ' : 'down ').abs($growth).'% vs yesterday' : '');
        if ($topRegion !== null && $topRegion['region'] !== 'unknown') {
            $sentences[] = sprintf('Activity is strongest in %s (%s %s today).',
                $topRegion['region'], number_format((float) $topRegion['volume']), $primary);
        }
        if ($peakHour !== null) {
            $sentences[] = sprintf('Peak activity hour: %s:00–%s:00.', $peakHour, str_pad((string) ((int) $peakHour + 1), 2, '0', STR_PAD_LEFT));
        }
        if (count($low) > 0) {
            $sentences[] = count($low).' agent'.(count($low) > 1 ? 's' : '').' may require liquidity support (estimate).';
        }

        return $sentences;
    }

    /**
     * Daily series for the network performance chart (§44).
     */
    public function series(array $agentIds, Carbon $from, Carbon $to, string $currency = ''): array
    {
        $rows = AgencyOperation::query()
            ->whereIn('agent_id', $agentIds)
            ->where('status', 'posted')
            ->whereBetween('created_at', [$from, $to])
            ->get(['created_at', 'currency_code', 'amount']);

        $days = [];
        $cursor = $from->copy()->startOfDay();
        while ($cursor->lte($to->endOfDay())) {
            $days[$cursor->toDateString()] = ['date' => $cursor->toDateString(), 'label' => $cursor->format('M d'), 'volume' => 0, 'transactions' => 0];
            $cursor->addDay();
        }

        foreach ($rows as $row) {
            $key = Carbon::parse($row->created_at)->toDateString();
            if (! isset($days[$key])) {
                continue;
            }
            if ($currency === '' || $row->currency_code === $currency) {
                $days[$key]['volume'] += (float) $row->amount;
                $days[$key]['transactions']++;
            }
        }

        return array_values($days);
    }

    /**
     * Liquidity snapshot (§23–24): per-currency totals + per-agent buckets.
     * Demand is a labelled estimate (7-day average daily cash-out).
     */
    public function liquidity(Aggregator $aggregator, array $agentIds): array
    {
        $agents = $this->tenant->agents($aggregator)->get();
        $currencies = array_unique($agents->pluck('country_iso2')->map(fn ($c) => $c === 'NE' ? 'XOF' : ($c === 'NG' || $c === 'NGA' ? 'NGN' : 'XOF'))->all());
        $currencies = $currencies ?: ['XOF', 'NGN'];

        $totals = [];
        $items = [];

        foreach ($agents as $agent) {
            $currency = $agent->country_iso2 === 'NE' ? 'XOF' : 'NGN';

            $float = '0';
            $account = $agent->floatAccount($currency);
            if ($account !== null) {
                $float = (string) $account->balance;
            }

            $demand = $this->averageDailyCashOut($agent->id, $currency, 7);
            $ratio = bccomp($demand, '0', 2) > 0 ? bcdiv($float, $demand, 2) : '9.00';
            $status = $this->liquidityBucket($ratio);

            $items[] = [
                'agent_id' => $agent->id,
                'agent_code' => $agent->agent_code,
                'name' => $agent->user?->name,
                'currency' => $currency,
                'float' => $float,
                'expected_demand_7d' => $demand,
                'buffer_ratio' => $ratio,
                'status' => $status,
                'status_label' => ucfirst($status),
                'estimate' => true,
            ];

            $totals[$currency] = bcadd($totals[$currency] ?? '0', $float, 2);
        }

        return [
            'totals' => $totals,
            'primary_currency' => $this->primaryCurrency($aggregator),
            'items' => $items,
        ];
    }

    public function topAgents(array $agentIds, Carbon $from, Carbon $to, string $currency = ''): array
    {
        $rows = AgencyOperation::query()
            ->whereIn('agent_id', $agentIds)
            ->whereBetween('created_at', [$from, $to])
            ->get(['agent_id', 'currency_code', 'amount']);

        $byAgent = $rows->groupBy('agent_id')->map(function ($ops) use ($currency) {
            $filtered = $currency !== '' ? $ops->where('currency_code', $currency) : $ops;

            return ['volume' => (string) $filtered->sum('amount'), 'transactions' => $filtered->count()];
        });

        $top = $byAgent->sortByDesc(fn ($r) => (float) $r['volume'])->take(5);

        $out = [];
        foreach ($top as $agentId => $row) {
            $agent = Agent::find($agentId);
            if ($agent === null) {
                continue;
            }
            $out[] = [
                'agent_id' => $agent->id,
                'agent_code' => $agent->agent_code,
                'name' => $agent->user?->name ?? 'Unknown agent',
                'status' => $agent->status,
                'volume' => $row['volume'],
                'transactions' => $row['transactions'],
            ];
        }

        return $out;
    }

    public function recentActivity(array $agentIds, string $currency = ''): array
    {
        $query = AgencyOperation::query()
            ->with('agent.user')
            ->whereIn('agent_id', $agentIds)
            ->latest()
            ->limit(10);

        if ($currency !== '') {
            $query->where('currency_code', $currency);
        }

        return $query->get()->map(fn (AgencyOperation $op) => [
            'reference' => $op->reference,
            'type' => $op->operation_type,
            'currency' => $op->currency_code,
            'amount' => (string) $op->amount,
            'status' => $op->status,
            'agent_name' => $op->agent?->user?->name,
            'agent_code' => $op->agent?->agent_code,
            'created_at' => $op->created_at?->toIso8601String(),
        ])->all();
    }

    // ── helpers ───────────────────────────────────────────────────────────

    /** @return array{Carbon, Carbon} */
    protected function resolveRange(array $filters): array
    {
        $range = $filters['range'] ?? 'today';

        if (isset($filters['from'], $filters['to'])) {
            return [
                Carbon::parse($filters['from'])->startOfDay(),
                Carbon::parse($filters['to'])->endOfDay(),
            ];
        }

        return match ($range) {
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            '7d' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            '30d' => [now()->subDays(29)->startOfDay(), now()->endOfDay()],
            'month' => [now()->startOfMonth(), now()->endOfDay()],
            'prev_month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            default => [now()->startOfDay(), now()->endOfDay()], // today
        };
    }

    /** @return array{Carbon, Carbon} */
    protected function previousPeriod(Carbon $from, Carbon $to): array
    {
        $span = $from->diffInSeconds($to) + 1;

        return [$from->copy()->subSeconds($span), $to->copy()->subSeconds($span)];
    }

    /** @return array<string, string> */
    protected function sumByCurrency(\Illuminate\Support\Collection $ops, string $currency = ''): array
    {
        $sums = $ops->groupBy('currency_code')->map(function ($g) {
            return number_format((float) $g->sum('amount'), 2, '.', '');
        })->all();

        if ($currency !== '') {
            return [$currency => number_format((float) ($sums[$currency] ?? 0), 2, '.', '')];
        }

        return $sums;
    }

    /** @return array<string, string> */
    protected function commissionSum(Aggregator $aggregator, Carbon $from, Carbon $to, string $currency = ''): array
    {
        $query = CommissionEntry::query()
            ->where('beneficiary_type', 'aggregator')
            ->where('beneficiary_id', $aggregator->id)
            ->whereBetween('created_at', [$from, $to]);

        if ($currency !== '') {
            $query->where('currency_code', $currency);
        }

        $sums = $query->get()->groupBy('currency_code')->map(function ($g) {
            return number_format((float) $g->sum('amount'), 2, '.', '');
        })->all();

        if ($currency !== '') {
            return [$currency => number_format((float) ($sums[$currency] ?? 0), 2, '.', '')];
        }

        return $sums;
    }

    protected function averageDailyCashOut(int $agentId, string $currency, int $days): string
    {
        $total = AgencyOperation::query()
            ->where('agent_id', $agentId)
            ->where('currency_code', $currency)
            ->where('operation_type', 'cash_out')
            ->where('status', 'posted')
            ->where('created_at', '>=', now()->subDays($days)->startOfDay())
            ->sum('amount');

        return number_format((float) $total / $days, 2, '.', '');
    }

    protected function liquidityBucket(string $ratio): string
    {
        if (bccomp($ratio, '2', 2) >= 0) {
            return 'healthy';
        }
        if (bccomp($ratio, '1', 2) >= 0) {
            return 'watch';
        }
        if (bccomp($ratio, '0.5', 2) >= 0) {
            return 'low';
        }

        return 'critical';
    }

    /** @return list<array{id: int, code: string, float: string, currency: string}> */
    protected function lowLiquidityAgents(\Illuminate\Support\Collection $agents, array $agentIds): array
    {
        $low = [];
        foreach ($agents as $agent) {
            if (! in_array($agent->status, [Agent::STATUS_ACTIVE], true)) {
                continue;
            }
            $currency = $agent->country_iso2 === 'NE' ? 'XOF' : 'NGN';
            $account = $agent->floatAccount($currency);
            $float = $account !== null ? (string) $account->balance : '0';
            $demand = $this->averageDailyCashOut($agent->id, $currency, 7);

            if (bccomp($demand, '0', 2) > 0 && bccomp($float, $demand, 2) < 0) {
                $low[] = ['id' => $agent->id, 'code' => $agent->agent_code, 'float' => $float, 'currency' => $currency];
            }
        }

        return $low;
    }

    protected function primaryCurrency(Aggregator $aggregator): string
    {
        return $aggregator->country_iso2 === 'NE' ? 'XOF' : 'NGN';
    }

    protected function growthPercent(string $current, string $previous): ?float
    {
        if (bccomp($previous, '0', 2) <= 0) {
            return $current === '0' ? null : null;
        }

        return round(((float) $current - (float) $previous) / (float) $previous * 100, 1);
    }

    protected function attentionItem(string $type, string $severity, int $count, string $message, string $action, array $entityIds): array
    {
        return [
            'type' => $type,
            'severity' => $severity,
            'count' => $count,
            'message' => $message,
            'action' => $action,
            'entity_ids' => $entityIds,
        ];
    }
}
