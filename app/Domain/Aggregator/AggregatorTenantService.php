<?php

namespace App\Domain\Aggregator;

use App\Domain\Aggregator\Exceptions\AggregatorNotProvisionedException;
use App\Models\Agent;
use App\Models\Aggregator;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * AGGREGATOR CONSOLE — Stage A (tenant isolation, §3/§8/§94).
 *
 * An aggregator can ONLY ever see resources that belong to `aggregator_id`.
 * Every service query in the console goes through this class so the scoping
 * is enforced server-side, in one place — never reconstructed ad-hoc in
 * controllers or views.
 *
 * Tenancy model: the authenticated user maps 1:1 to an `aggregators` row via
 * `aggregators.user_id`. Agents carry `aggregators.id` on `aggregator_id`.
 * Agency operations and commission entries are denormalized with the same id.
 */
class AggregatorTenantService
{
    /**
     * The Aggregator record for the authenticated user, or null when the user
     * is an aggregator without a provisioned record (honest: no data, no
     * fabricated identity).
     */
    public function current(?User $user = null): ?Aggregator
    {
        $user ??= auth()->user();

        if ($user === null) {
            return null;
        }

        return Aggregator::query()->where('user_id', $user->id)->first();
    }

    /**
     * Resolve the current aggregator or fail loudly — call before any
     * aggregator-scoped operation.
     *
     * @throws AggregatorNotProvisionedException
     */
    public function requireCurrent(?User $user = null): Aggregator
    {
        $aggregator = $this->current($user);

        if ($aggregator === null) {
            throw new AggregatorNotProvisionedException(
                'No aggregator profile is provisioned for this account.'
            );
        }

        return $aggregator;
    }

    /**
     * IDs of every agent in this aggregator's network. Empty array when the
     * aggregator has no agents — callers render honest empty states.
     *
     * @return list<int>
     */
    public function agentIds(Aggregator $aggregator): array
    {
        return Agent::query()
            ->where('aggregator_id', $aggregator->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Scoped agents query — the single entry point for agent listings.
     */
    public function agents(Aggregator $aggregator): Builder
    {
        return Agent::query()->where('aggregator_id', $aggregator->id);
    }

    /**
     * Does this agent belong to this aggregator? Ownership guard for
     * profile/detail endpoints (IDOR protection, §133).
     */
    public function ownsAgent(Aggregator $aggregator, Agent $agent): bool
    {
        return (int) $agent->aggregator_id === (int) $aggregator->id;
    }
}
