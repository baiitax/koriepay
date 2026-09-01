<?php

namespace App\Domain\Aggregator;

use App\Models\Aggregator;
use App\Models\AuditLog;
use App\Models\CommissionEntry;
use App\Models\LiquidityRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * AGGREGATOR CONSOLE — Stage I (profile + backend-sourced limits, §64–65).
 *
 * The profile page shows identity from the aggregator record and limits that
 * are DERIVED from the ledger and real records — never invented caps. Every
 * number carries its source (ledger | records | authorization matrix). The
 * cache policy is explicit: balances and authorizations are never cached.
 */
class AggregatorProfileService
{
    public function __construct(
        private readonly AggregatorTenantService $tenant,
        private readonly AggregatorLiquidityService $liquidity,
    ) {
    }

    public function profile(Aggregator $aggregator): array
    {
        $user = $aggregator->user;
        $agentIds = $this->tenant->agentIds($aggregator);

        $currencies = $aggregator->floatAccount('XOF') !== null ? ['XOF'] : [];
        if ($aggregator->floatAccount('NGN') !== null) {
            $currencies[] = 'NGN';
        }

        $position = [];
        foreach ($currencies as $currency) {
            $position[$currency] = $this->liquidity->position($aggregator, $currency);
        }

        $agentCounts = [
            'total' => count($agentIds),
            'active' => $this->tenant->agents($aggregator)->where('status', 'active')->count(),
            'suspended' => $this->tenant->agents($aggregator)->where('status', 'suspended')->count(),
            'pending' => $this->tenant->agents($aggregator)->where('status', 'pending')->count(),
        ];

        return [
            'identity' => [
                'name' => $aggregator->name,
                'code' => $aggregator->code,
                'country' => $aggregator->country_iso2,
                'region' => $aggregator->region,
                'city' => $aggregator->city,
                'kyc_status' => $aggregator->kyc_status,
                'status' => $aggregator->status,
                'member_since' => $aggregator->created_at?->toDateString(),
                'user_name' => $user?->name,
                'user_email' => $user?->email,
                'user_phone' => $user?->phone_number,
                'last_login_at' => $user?->last_login_at?->toIso8601String(),
            ],
            'permissions' => $this->permissions(),
            'limits' => [
                'position' => $position,
                'agents' => $agentCounts,
                'outstanding_liquidity' => (string) LiquidityRequest::where('aggregator_id', $aggregator->id)
                    ->whereIn('status', ['pending', 'in_review', 'approved'])
                    ->sum('amount'),
                'pending_commission' => (string) CommissionEntry::where('beneficiary_type', 'aggregator')
                    ->where('beneficiary_id', $aggregator->id)
                    ->where('status', 'accrued')
                    ->sum('amount'),
                'capacity_note' => 'No hard network cap is configured for aggregator accounts; limits are the measured ledger positions above.',
            ],
            'cache_policy' => [
                'statement' => 'Balances and authorizations are never cached — every read hits the ledger and RBAC directly.',
                'cached_ok' => 'Only derived, non-authoritative aggregates (daily read model) are materialized, and they are always labelled as snapshots.',
            ],
            'basis' => 'Identity from the aggregator record; limits from ledger positions, liquidity requests and commission entries.',
        ];
    }

    public function updateName(Aggregator $aggregator, User $actor, string $name): Aggregator
    {
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('Display name is required.');
        }

        $aggregator->forceFill(['name' => $name])->save();
        $actor->forceFill(['name' => $name])->save();

        AuditLog::record('aggregator.profile.updated', $actor->id, $actor->id, [
            'description' => 'Aggregator display name updated.', 'event_type' => 'operations',
            'metadata' => ['aggregator_id' => $aggregator->id, 'name' => $name],
        ]);

        return $aggregator;
    }

    /** Backend-sourced authorization info: the aggregator role's permission set. */
    protected function permissions(): array
    {
        return DB::table('role_permissions')
            ->where('role', 'aggregator')
            ->orderBy('permission')
            ->pluck('permission')
            ->all();
    }
}
