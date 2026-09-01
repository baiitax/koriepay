<?php

namespace App\Domain\Aggregator;

use App\Models\Aggregator;
use App\Models\CommissionEntry;
use App\Models\CommissionRule;
use Illuminate\Support\Carbon;

/**
 * AGGREGATOR CONSOLE — Stage E (commission intelligence, §38–43).
 *
 * Reads only CommissionEntry + CommissionRule records. Earnings are reported
 * with EVERY component shown separately (gross / adjustments / reversals /
 * net / paid / pending) — gross is never labelled as net. Reversals and
 * adjustments are identified by their rule key namespace (rev:… / adj:…)
 * and are real rows, never derived by mutation of the original.
 */
class AggregatorCommissionsService
{
    // ── Overview (today / week / month) ─────────────────────────────────

    public function overview(Aggregator $aggregator, string $currency = ''): array
    {
        $windows = [
            'today' => [now()->startOfDay(), now()],
            'week' => [now()->subDays(7)->startOfDay(), now()],
            'month' => [now()->startOfMonth(), now()],
        ];

        $result = [];
        foreach ($windows as $key => [$from, $to]) {
            $entries = $this->aggregatorEntries($aggregator, $currency, $from, $to);
            $result[$key] = [
                'gross' => $this->fmt($entries, fn ($e) => ! $this->isAdjustment($e) && ! $this->isReversal($e)),
                'paid' => $this->fmt($entries, fn ($e) => $e->status === 'paid'),
                'pending' => $this->fmt($entries, fn ($e) => $e->status === 'accrued'),
                'count' => $entries->count(),
            ];
        }

        return $result;
    }

    public function productBreakdown(Aggregator $aggregator, string $currency = ''): array
    {
        $entries = $this->aggregatorEntries($aggregator, $currency, now()->subDays(30)->startOfDay(), now());

        // Adjustments and reversals are not products — exclude from the rule
        // breakdown (they surface separately in earnings + audit trail).
        $byRule = $entries
            ->whereNotIn('rule_id', $this->namespaceRules())
            ->groupBy('rule_id')
            ->map(function ($group) {
                $gross = (float) $group->sum('amount');

                return [
                    'rule_id' => (string) $group->first()->rule_id,
                    'amount' => number_format($gross, 2, '.', ''),
                    'count' => $group->count(),
                ];
            })->values()->all();

        // Order by amount desc, then merge rule definition where on record.
        usort($byRule, fn ($a, $b) => (float) $b['amount'] <=> (float) $a['amount']);

        $rules = CommissionRule::query()->whereIn('name', array_column($byRule, 'rule_id'))->get()->keyBy('name');

        foreach ($byRule as &$row) {
            $row['definition'] = $rules->get($row['rule_id']) !== null
                ? [
                    'rate' => (string) $rules->get($row['rule_id'])->rate,
                    'flat_amount' => (string) $rules->get($row['rule_id'])->flat_amount,
                    'priority' => $rules->get($row['rule_id'])->priority,
                    'is_active' => $rules->get($row['rule_id'])->is_active,
                ]
                : null;
        }

        return $byRule;
    }

    /**
     * Earnings: gross / adjustments / reversals / net / paid / pending.
     * Net is ALWAYS derived and labelled; gross is never presented as net.
     */
    public function earnings(Aggregator $aggregator, string $currency = ''): array
    {
        $entries = $this->aggregatorEntries($aggregator, $currency, now()->subDays(30)->startOfDay(), now());

        $gross = $this->sum($entries->whereNotIn('rule_id', $this->namespaceRules()));
        $adjustments = $this->sum($entries->filter(fn ($e) => $this->isAdjustment($e)));
        $reversals = $this->sum($entries->filter(fn ($e) => $this->isReversal($e)));
        $net = bcadd(bcadd($gross, $adjustments, 2), $reversals, 2);
        $paid = $this->sum($entries->where('status', 'paid'));
        $pending = $this->sum($entries->where('status', 'accrued'));

        return [
            'gross' => number_format((float) $gross, 2, '.', ''),
            'adjustments' => number_format((float) $adjustments, 2, '.', ''),
            'reversals' => number_format((float) $reversals, 2, '.', ''),
            'net' => number_format((float) $net, 2, '.', ''),
            'paid' => number_format((float) $paid, 2, '.', ''),
            'pending' => number_format((float) $pending, 2, '.', ''),
            'formula' => 'net = gross + adjustments + reversals (all figures shown separately; gross ≠ net)',
        ];
    }

    // ── Per-agent table ─────────────────────────────────────────────────

    public function agentCommissions(Aggregator $aggregator, string $currency = ''): array
    {
        $agentIds = app(AggregatorTenantService::class)->agentIds($aggregator);

        $rows = [];
        foreach (app(AggregatorTenantService::class)->agents($aggregator)->with('user')->get() as $agent) {
            $entries = CommissionEntry::query()
                ->where('beneficiary_type', 'agent')
                ->where('beneficiary_id', $agent->id)
                ->when($currency !== '', fn ($q) => $q->where('currency_code', $currency))
                ->get();

            $rows[] = [
                'agent_id' => $agent->id,
                'agent_code' => $agent->agent_code,
                'name' => $agent->user?->name,
                'status' => $agent->status,
                'accrued' => number_format((float) $entries->where('status', 'accrued')->sum('amount'), 2, '.', ''),
                'paid' => number_format((float) $entries->where('status', 'paid')->sum('amount'), 2, '.', ''),
                'total' => number_format((float) $entries->sum('amount'), 2, '.', ''),
                'count' => $entries->count(),
            ];
        }

        usort($rows, fn ($a, $b) => (float) $b['total'] <=> (float) $a['total']);

        return $rows;
    }

    // ── Audit trail with rule versions (§41) ────────────────────────────

    public function auditTrail(Aggregator $aggregator, string $currency = '', int $limit = 100): array
    {
        $entries = CommissionEntry::query()
            ->where('beneficiary_type', 'aggregator')
            ->where('beneficiary_id', $aggregator->id)
            ->when($currency !== '', fn ($q) => $q->where('currency_code', $currency))
            ->latest('created_at')
            ->limit($limit)
            ->get();

        $ruleIds = $entries->pluck('rule_id')->unique()->all();
        $rules = CommissionRule::query()->whereIn('name', $ruleIds)->get()->keyBy('name');

        return $entries->map(function (CommissionEntry $entry) use ($rules) {
            $rule = $rules->get($entry->rule_id);

            return [
                'id' => $entry->id,
                'rule_id' => $entry->rule_id,
                'amount' => (string) $entry->amount,
                'status' => $entry->status,
                'currency' => $entry->currency_code,
                'created_at' => $entry->created_at,
                'ledger_transaction_id' => $entry->ledger_transaction_id,
                'kind' => $this->kindOf($entry),
                'version' => $rule !== null
                    ? [
                        'rate' => (string) $rule->rate,
                        'flat_amount' => (string) $rule->flat_amount,
                        'priority' => $rule->priority,
                        'active' => $rule->is_active,
                    ]
                    : null,
                'version_known' => $rule !== null,
            ];
        })->all();
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    /** @return \Illuminate\Support\Collection<int, CommissionEntry> */
    protected function aggregatorEntries(Aggregator $aggregator, string $currency, Carbon $from, Carbon $to): \Illuminate\Support\Collection
    {
        return CommissionEntry::query()
            ->where('beneficiary_type', 'aggregator')
            ->where('beneficiary_id', $aggregator->id)
            ->when($currency !== '', fn ($q) => $q->where('currency_code', $currency))
            ->whereBetween('created_at', [$from, $to])
            ->get();
    }

    /** @return list<string> */
    protected function namespaceRules(): array
    {
        return ['adj:dev-agg', 'rev:dev-agg'];
    }

    protected function isAdjustment(CommissionEntry $entry): bool
    {
        return str_starts_with((string) $entry->rule_id, 'adj:');
    }

    protected function isReversal(CommissionEntry $entry): bool
    {
        return str_starts_with((string) $entry->rule_id, 'rev:');
    }

    protected function kindOf(CommissionEntry $entry): string
    {
        if ($this->isAdjustment($entry)) {
            return 'adjustment';
        }
        if ($this->isReversal($entry)) {
            return 'reversal';
        }

        return 'commission';
    }

    protected function sum(\Illuminate\Support\Collection $entries): string
    {
        return bcadd('0', (string) $entries->sum('amount'), 2);
    }

    protected function fmt(\Illuminate\Support\Collection $entries, callable $filter): string
    {
        return number_format((float) $entries->filter($filter)->sum('amount'), 2, '.', '');
    }
}
