<?php

namespace App\Domain\Aggregator;

use App\Models\AgencyOperation;
use App\Models\Agent;
use App\Models\Aggregator;
use App\Models\AggregatorDailyMetric;

/**
 * AGGREGATOR CONSOLE — Stage I (data quality center).
 *
 * Honest, live, tenant-scoped quality checks over the aggregator's own data:
 * ledger projection drift, orphan/malformed operations, agent record
 * completeness and read-model freshness. Nothing is cached and no check ever
 * reports healthy when its source cannot be read — gaps surface as `unknown`.
 */
class AggregatorDataQualityService
{
    public function __construct(private readonly AggregatorTenantService $tenant)
    {
    }

    public function scan(Aggregator $aggregator): array
    {
        $agentIds = $this->tenant->agentIds($aggregator);

        $checks = collect([
            $this->ledgerDrift($aggregator),
            $this->orphanOperations($aggregator, $agentIds),
            $this->missingReferences($aggregator),
            $this->agentsWithoutUser($agentIds),
            $this->agentsMissingGeo($agentIds),
            $this->readModelFreshness($aggregator),
            $this->emptySnapshotDays($aggregator),
        ]);

        $summary = [
            'ok' => $checks->where('status', 'ok')->count(),
            'attention' => $checks->where('status', 'attention')->count(),
            'warning' => $checks->where('status', 'warning')->count(),
            'unknown' => $checks->where('status', 'unknown')->count(),
        ];

        $overall = match (true) {
            $summary['warning'] > 0 => 'issues',
            $summary['attention'] > 0 => 'attention',
            $summary['unknown'] > 0 && $summary['ok'] === 0 => 'unknown',
            default => 'healthy',
        };

        return [
            'checks' => $checks->all(),
            'summary' => $summary,
            'overall' => $overall,
            'scanned_at' => now()->toIso8601String(),
            'basis' => 'Every check is computed live from tenant-scoped records — nothing cached, nothing fabricated.',
        ];
    }

    /** Ledger projection drift — reused from the observability indicators. */
    protected function ledgerDrift(Aggregator $aggregator): array
    {
        $observability = app(AggregatorObservabilityService::class)->indicators($aggregator);
        $indicator = collect($observability['indicators'])->firstWhere('key', 'ledger_drift');

        return [
            'key' => 'ledger_drift',
            'label' => 'Ledger projection drift',
            'status' => ($indicator['status'] ?? 'unknown') === 'ok' ? 'ok' : 'warning',
            'value' => (string) ($indicator['value'] ?? 'unknown'),
            'detail' => $indicator['explanation'] ?? 'Unable to read ledger accounts.',
            'source' => 'float_accounts ⇄ ledger_entries',
        ];
    }

    /** Operations attributed to this network whose agent no longer belongs to it. */
    protected function orphanOperations(Aggregator $aggregator, array $agentIds): array
    {
        $query = AgencyOperation::query()->where('aggregator_id', $aggregator->id);
        $count = $agentIds === []
            ? $query->count()
            : (clone $query)->whereNotIn('agent_id', $agentIds)->count();

        return [
            'key' => 'orphan_operations',
            'label' => 'Orphaned operations',
            'status' => $count === 0 ? 'ok' : 'attention',
            'value' => (string) $count,
            'detail' => $count === 0
                ? 'Every operation belongs to an agent of this network.'
                : $count.' operation(s) reference agents outside this network.',
            'source' => 'agency_operations',
        ];
    }

    /**
     * Operations missing reference/idempotency data, or POSTED operations
     * without a transaction link (failed ops may legitimately lack one).
     */
    protected function missingReferences(Aggregator $aggregator): array
    {
        $count = AgencyOperation::where('aggregator_id', $aggregator->id)
            ->where(function ($q) {
                $q->whereNull('reference')->orWhere('reference', '')
                    ->orWhereNull('idempotency_key')->orWhere('idempotency_key', '')
                    ->orWhere(function ($w) {
                        $w->where('status', 'posted')->whereNull('transaction_id');
                    });
            })
            ->count();

        return [
            'key' => 'missing_references',
            'label' => 'Malformed operations',
            'status' => $count === 0 ? 'ok' : 'attention',
            'value' => (string) $count,
            'detail' => $count === 0
                ? 'All operations carry reference, idempotency key and a transaction link.'
                : $count.' operation(s) are missing reference/idempotency data, or posted without a transaction link.',
            'source' => 'agency_operations',
        ];
    }

    /** Agents whose linked user record is missing. */
    protected function agentsWithoutUser(array $agentIds): array
    {
        $count = $agentIds === []
            ? 0
            : Agent::whereIn('id', $agentIds)
                ->where(function ($q) {
                    $q->whereNull('user_id')->orWhereDoesntHave('user');
                })
                ->count();

        return [
            'key' => 'agents_without_user',
            'label' => 'Agents without a user account',
            'status' => $count === 0 ? 'ok' : 'attention',
            'value' => (string) $count,
            'detail' => $count === 0
                ? 'Every agent has a linked user account.'
                : $count.' agent(s) have no linked user record.',
            'source' => 'agents ⇄ users',
        ];
    }

    /** Agents missing geo attributes used by the network map and reports. */
    protected function agentsMissingGeo(array $agentIds): array
    {
        $count = $agentIds === []
            ? 0
            : Agent::whereIn('id', $agentIds)
                ->where(function ($q) {
                    $q->whereNull('country_iso2')->orWhere('country_iso2', '')
                        ->orWhereNull('region')->orWhere('region', '')
                        ->orWhereNull('city')->orWhere('city', '');
                })
                ->count();

        return [
            'key' => 'agents_missing_geo',
            'label' => 'Agents missing geo data',
            'status' => $count === 0 ? 'ok' : 'attention',
            'value' => (string) $count,
            'detail' => $count === 0
                ? 'Every agent has country, region and city populated.'
                : $count.' agent(s) are missing country/region/city.',
            'source' => 'agents',
        ];
    }

    /** Read-model freshness — stale snapshots are reported, never hidden. */
    protected function readModelFreshness(Aggregator $aggregator): array
    {
        $latest = AggregatorDailyMetric::where('aggregator_id', $aggregator->id)
            ->latest('metric_date')
            ->first();

        if ($latest === null) {
            return [
                'key' => 'read_model_freshness',
                'label' => 'Read-model freshness',
                'status' => 'unknown',
                'value' => 'none',
                'detail' => 'No daily snapshots exist yet — run a backfill to materialize the read model.',
                'source' => 'aggregator_daily_metrics',
            ];
        }

        $stale = $latest->computed_at?->lt(now()->subHours(26)) ?? true;

        return [
            'key' => 'read_model_freshness',
            'label' => 'Read-model freshness',
            'status' => $stale ? 'attention' : 'ok',
            'value' => $latest->metric_date->toDateString(),
            'detail' => $stale
                ? 'Latest snapshot is stale ('.$latest->metric_date->toDateString().') — recompute the read model.'
                : 'Latest snapshot is current ('.$latest->metric_date->toDateString().').',
            'source' => 'aggregator_daily_metrics',
        ];
    }

    /** Empty snapshot days in the trailing 14 days — honest zero days. */
    protected function emptySnapshotDays(Aggregator $aggregator): array
    {
        $count = AggregatorDailyMetric::where('aggregator_id', $aggregator->id)
            ->where('metric_date', '>=', now()->subDays(14)->startOfDay())
            ->where('is_empty', true)
            ->count();

        return [
            'key' => 'empty_days',
            'label' => 'Empty days in read model',
            'status' => $count <= 3 ? 'ok' : 'attention',
            'value' => (string) $count,
            'detail' => $count === 0
                ? 'No empty snapshot days in the last 14 days.'
                : $count.' day(s) with zero activity in the last 14 days (recorded honestly as empty).',
            'source' => 'aggregator_daily_metrics',
        ];
    }
}
