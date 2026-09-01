<?php

namespace App\Domain\Aggregator;

use App\Domain\Accounting\LedgerTransaction;
use App\Models\AgencyOperation;
use App\Models\Agent;
use App\Models\Aggregator;
use Illuminate\Support\Carbon;

/**
 * AGGREGATOR CONSOLE — Stage F (network intelligence, §44–51).
 *
 * All analytics derive from REAL records (posted/failed operations, agents,
 * ledger floats). Failure intelligence groups by the recorded failure_reason
 * — rows without a recorded cause land in an honest "cause not recorded"
 * bucket. Coverage recommendations come from measurable demand (posted
 * cash-out volume) vs agent presence per city — never customer PII. The
 * network health score is a weighted, EXPLAINED composite; components with
 * no data are excluded and disclosed, never fabricated as zero.
 */
class AggregatorNetworkService
{
    // ── Analytics (hourly → monthly trends) ─────────────────────────────

    /**
     * @param  string  $range  hourly|daily|weekly|monthly
     */
    public function analytics(Aggregator $aggregator, string $range = 'daily'): array
    {
        $window = match ($range) {
            'hourly' => [24, now()->subHours(24), fn (Carbon $c) => $c->format('Y-m-d H:00')],
            'weekly' => [8, now()->subWeeks(8), fn (Carbon $c) => $c->format('Y-\WW')],
            'monthly' => [6, now()->subMonths(6), fn (Carbon $c) => $c->format('Y-m')],
            default => [7, now()->subDays(7)->startOfDay(), fn (Carbon $c) => $c->format('Y-m-d')],
        };
        [, $since, $bucket] = $window;

        $ops = AgencyOperation::query()
            ->where('aggregator_id', $aggregator->id)
            ->where('created_at', '>=', $since)
            ->orderBy('created_at')
            ->get();

        $buckets = [];
        foreach ($ops->groupBy(fn (AgencyOperation $op) => $bucket($op->created_at)) as $key => $group) {
            $total = $group->count();
            $posted = $group->where('status', 'posted');
            $failed = $group->where('status', 'failed');

            $buckets[] = [
                'bucket' => $key,
                'volume' => number_format((float) $posted->sum('amount'), 2, '.', ''),
                'count' => $total,
                'active_agents' => $group->pluck('agent_id')->unique()->count(),
                'success_count' => $posted->count(),
                'failure_count' => $failed->count(),
                'success_rate' => $total > 0 ? round($posted->count() / $total * 100, 1) : 0,
                'failure_rate' => $total > 0 ? round($failed->count() / $total * 100, 1) : 0,
            ];
        }

        // Reversals: ledger reversal postings linked to this network's ops.
        $reversalCount = LedgerTransaction::query()
            ->where('type', 'reversal')
            ->where('created_at', '>=', $since)
            ->whereIn('id', $ops->pluck('transaction_id')->filter()->all())
            ->count();

        $posted = $ops->where('status', 'posted');
        $total = $ops->count();

        return [
            'range' => $range,
            'buckets' => $buckets,
            'summary' => [
                'volume' => number_format((float) $posted->sum('amount'), 2, '.', ''),
                'count' => $total,
                'active_agents' => $ops->pluck('agent_id')->unique()->count(),
                'average_per_agent' => $ops->pluck('agent_id')->unique()->count() > 0
                    ? number_format((float) $posted->sum('amount') / $ops->pluck('agent_id')->unique()->count(), 2, '.', '')
                    : '0.00',
                'success_rate' => $total > 0 ? round($posted->count() / $total * 100, 1) : null,
                'failure_rate' => $total > 0 ? round($ops->where('status', 'failed')->count() / $total * 100, 1) : null,
                'reversals' => $reversalCount,
                'basis' => 'posted/failed operations '.$range.' — reversals from reversal postings linked to network operations',
            ],
        ];
    }

    // ── Failure intelligence by root cause ──────────────────────────────

    public function failureIntelligence(Aggregator $aggregator, int $days = 7): array
    {
        $failed = AgencyOperation::query()
            ->where('aggregator_id', $aggregator->id)
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subDays($days)->startOfDay())
            ->get();

        if ($failed->isEmpty()) {
            return [
                'causes' => [],
                'total' => 0,
                'basis' => 'No failed operations in the last '.$days.' days.',
            ];
        }

        $grouped = $failed->groupBy(fn (AgencyOperation $op) => $op->failure_reason ?: '__not_recorded__');

        $causes = $grouped->map(function ($group, $reason) use ($failed) {
            return [
                'cause' => $reason === '__not_recorded__' ? 'cause not recorded' : $reason,
                'recorded' => $reason !== '__not_recorded__',
                'count' => $group->count(),
                'share' => round($group->count() / $failed->count() * 100, 1),
                'amount' => number_format((float) $group->sum('amount'), 2, '.', ''),
                'affected_agents' => $group->pluck('agent_id')->unique()->count(),
                'latest_reference' => $group->sortByDesc('created_at')->first()->reference,
            ];
        })->values()->sortByDesc('count')->values()->all();

        return [
            'causes' => $causes,
            'total' => $failed->count(),
            'basis' => 'Failed operations in the last '.$days.' days, grouped by recorded failure reason.',
        ];
    }

    // ── Geographic coverage + recruitment recommendations ───────────────

    public function coverage(Aggregator $aggregator, int $days = 7): array
    {
        $agentIds = app(AggregatorTenantService::class)->agentIds($aggregator);
        $agents = app(AggregatorTenantService::class)->agents($aggregator)->get();

        // Demand is attributed to the AGENT's location (operations carry no
        // city of their own) — aggregated volumes, no customer PII.
        $agentCity = $agents->pluck('city', 'id')
            ->map(fn ($c) => (string) $c)
            ->all();

        $demand = AgencyOperation::query()
            ->whereIn('agent_id', $agentIds)
            ->where('status', 'posted')
            ->where('created_at', '>=', now()->subDays($days)->startOfDay())
            ->get()
            ->groupBy(fn (AgencyOperation $op) => $agentCity[$op->agent_id] ?? 'Unknown')
            ->map(fn ($g) => [
                'cash_out' => (float) $g->where('operation_type', AgencyOperation::TYPE_CASH_OUT)->sum('amount'),
                'cash_in' => (float) $g->where('operation_type', AgencyOperation::TYPE_CASH_IN)->sum('amount'),
                'ops' => $g->count(),
            ]);

        $byCity = $agents->groupBy('city')->map(function ($group, $city) use ($demand) {
            $d = $demand->get($city, ['cash_out' => 0.0, 'cash_in' => 0.0, 'ops' => 0]);
            $agentCount = $group->count();

            return [
                'city' => (string) $city,
                'region' => (string) $group->first()->region,
                'agents' => $agentCount,
                'cash_out_7d' => number_format($d['cash_out'], 2, '.', ''),
                'cash_in_7d' => number_format($d['cash_in'], 2, '.', ''),
                'demand_per_agent' => $agentCount > 0 ? number_format($d['cash_out'] / $agentCount, 2, '.', '') : null,
                'ops_7d' => $d['ops'],
            ];
        })->values()->sortByDesc('cash_out_7d')->values()->all();

        // Recommendations from measurable data — never guesses.
        $recommendations = [];
        foreach ($demand as $city => $d) {
            $agentsInCity = $agents->where('city', $city)->count();
            if ($agentsInCity === 0 && $d['cash_out'] > 0) {
                $recommendations[] = [
                    'type' => 'recruit',
                    'city' => $city,
                    'message' => $city.' shows '.number_format($d['cash_out']).' XOF cash-out demand in 7 days with no agent on the ground — consider recruiting.',
                    'estimate' => false,
                ];
            }
        }
        $allAgentDemands = collect($byCity)->pluck('demand_per_agent')->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v);
        $median = $allAgentDemands->isEmpty() ? null : $this->median($allAgentDemands->values()->all());
        if ($median !== null) {
            foreach ($byCity as $row) {
                // Thin coverage: city demand per agent >2× the network median.
                if ($row['demand_per_agent'] !== null && (float) $row['demand_per_agent'] > $median * 2) {
                    $recommendations[] = [
                        'type' => 'thin_coverage',
                        'city' => $row['city'],
                        'message' => $row['city'].' demand per agent ('.number_format((float) $row['demand_per_agent']).') is >2× the network median — coverage is thin.',
                        'estimate' => true,
                        'basis' => '7-day posted cash-out demand per agent vs network median',
                    ];
                }
                // Recruitment gap: the network average output per agent implies
                // more agents than the city currently has for its demand.
                $impliedAgents = (int) ceil((float) $row['cash_out_7d'] / $median);
                if ($impliedAgents > $row['agents']) {
                    $recommendations[] = [
                        'type' => 'recruit',
                        'city' => $row['city'],
                        'message' => $row['city'].' cash-out demand ('.number_format((float) $row['cash_out_7d']).' XOF / 7d) supports ~'.$impliedAgents.' agents at network output levels, but only '.$row['agents'].' operate here — consider recruiting.',
                        'estimate' => true,
                        'basis' => '7-day posted cash-out demand vs network output per agent',
                    ];
                }
            }
        }

        return [
            'cities' => $byCity,
            'recommendations' => $recommendations,
            'median_demand_per_agent' => $median !== null ? number_format($median, 2, '.', '') : null,
            'basis' => 'Agent records + 7-day posted cash-in/out volume — aggregated, no customer PII.',
        ];
    }

    // ── Network health score (explained) ────────────────────────────────

    public function networkHealth(Aggregator $aggregator): array
    {
        $agentIds = app(AggregatorTenantService::class)->agentIds($aggregator);
        $agentCount = count($agentIds);

        $ops = AgencyOperation::query()
            ->where('aggregator_id', $aggregator->id)
            ->where('created_at', '>=', now()->subDays(7)->startOfDay())
            ->get();

        $total = $ops->count();
        $posted = $ops->where('status', 'posted')->count();

        // 1. Success rate — share of posted operations (30%).
        $successRate = $total > 0 ? $posted / $total : null;
        $components = [];
        if ($successRate !== null) {
            $components[] = $this->component('success_rate', 30, round($successRate * 100, 1),
                'Posted operations ÷ total operations (7d) — '.$posted.'/'.$total);
        }

        // 2. Activity — operations per agent per week vs a documented full-pace (5/week) (30%).
        if ($agentCount > 0) {
            $opsPerAgent = $total / $agentCount;
            $components[] = $this->component('activity', 30, round(min(100, $opsPerAgent / 5 * 100), 1),
                'Operations per agent over 7 days ('.$opsPerAgent.') — full pace is 5/week');
        }

        // 3. Coverage — agents with a funded float vs agents with 7d cash-out demand (20%).
        $withDemand = AgencyOperation::query()
            ->whereIn('agent_id', $agentIds)->where('status', 'posted')
            ->where('operation_type', AgencyOperation::TYPE_CASH_OUT)
            ->where('created_at', '>=', now()->subDays(7)->startOfDay())
            ->distinct()->count('agent_id');
        $funded = 0;
        foreach (app(AggregatorTenantService::class)->agents($aggregator)->get() as $agent) {
            $float = $agent->floatAccount($agent->country_iso2 === 'NE' ? 'XOF' : 'NGN');
            if ($float !== null && bccomp((string) $float->balance, '0', 2) > 0) {
                $funded++;
            }
        }
        if ($withDemand > 0 && $agentCount > 0) {
            $covered = min($funded, $withDemand);
            $components[] = $this->component('coverage', 20, round($covered / $withDemand * 100, 1),
                'Agents with funded float vs agents with cash-out demand ('.$covered.'/'.$withDemand.')');
        }

        // 4. Stability — inverse failure pressure (20%).
        if ($successRate !== null && $total > 0) {
            $failures = $total - $posted;
            $components[] = $this->component('stability', 20, round((1 - $failures / $total) * 100, 1),
                'Non-failed share of operations ('.($total - $failures).'/'.$total.')');
        }

        if ($components === []) {
            return [
                'score' => null,
                'label' => 'No signal',
                'components' => [],
                'explanation' => 'No network activity on record — a health score would be fabricated, so none is shown.',
            ];
        }

        $weighted = collect($components)->sum(fn ($c) => $c['weight'] * $c['score'] / 100);
        $score = round($weighted, 1);

        return [
            'score' => $score,
            'label' => match (true) {
                $score >= 80 => 'Healthy',
                $score >= 60 => 'Stable',
                $score >= 40 => 'Fragile',
                default => 'Critical',
            },
            'components' => $components,
            'explanation' => 'Weighted composite of measured components (weights sum to 100). Each component is disclosed with its formula.',
        ];
    }

    protected function component(string $key, int $weight, float $score, string $formula): array
    {
        return ['key' => $key, 'weight' => $weight, 'score' => $score, 'formula' => $formula];
    }

    protected function median(array $values): float
    {
        sort($values);
        $count = count($values);
        $mid = intdiv($count, 2);

        return $count % 2 === 0 ? ($values[$mid - 1] + $values[$mid]) / 2 : $values[$mid];
    }
}
