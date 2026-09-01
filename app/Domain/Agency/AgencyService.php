<?php

namespace App\Domain\Agency;

use App\Domain\Accounting\LedgerAccount;
use App\Domain\Accounting\LedgerService;
use App\Domain\Agency\Exceptions\AgencyNotActiveException;
use App\Domain\Agency\Exceptions\MissingCustomerWalletException;
use App\Domain\Agency\Exceptions\MissingFloatAccountException;
use App\Models\AgencyOperation;
use App\Models\Agent;
use App\Models\Aggregator;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * PHASE 6 — Agency banking operations.
 *
 * The agency network (agents + aggregators) moves money ONLY through the
 * ledger. Every cash-in / cash-out is:
 *   - idempotent (replay returns the original operation)
 *   - audited (audit_logs + agency_operations)
 *   - commission-attributed through CommissionEngine (accrued, not paid)
 *
 * Custodial model: agent float = LIABILITY account (platform owes the agent),
 * exactly like customer wallets.
 */
class AgencyService
{
    public function __construct(
        private readonly LedgerService $ledger,
        private readonly CommissionEngine $commissionEngine,
    ) {
    }

    // ── Network lifecycle ────────────────────────────────────────────────

    public function registerAgent(User $user, array $profile, ?int $actorId = null): Agent
    {
        $country = strtoupper((string) ($profile['country_iso2'] ?? 'NG'));
        $currency = self::countryCurrency($country);

        $agent = Agent::create([
            'user_id' => $user->id,
            'agent_code' => 'AGT-'.strtoupper(Str::random(6)),
            'status' => Agent::STATUS_PENDING,
            'tier' => $profile['tier'] ?? 'bronze',
            'country_iso2' => $country,
            'region' => $profile['region'] ?? null,
            'city' => $profile['city'] ?? null,
            'kyc_status' => $profile['kyc_status'] ?? 'unverified',
            'risk_score' => $profile['risk_score'] ?? null,
        ]);

        // Explicit audited role assignment — role is never mass-assignable.
        $user->forceFill(['role' => 'agent'])->save();

        $this->provisionAgentLedger($agent, $currency);

        AuditLog::record('agent.registered', $actorId, $user->id, [
            'description' => 'Agent registered ('.$agent->agent_code.', '.$country.').',
            'event_type' => 'operations',
            'metadata' => ['agent_id' => $agent->id, 'agent_code' => $agent->agent_code, 'country_iso2' => $country],
        ]);

        return $agent;
    }

    public function activateAgent(Agent $agent, ?int $actorId = null): Agent
    {
        $this->transition($agent, Agent::STATUS_ACTIVE, ['pending', 'inactive', 'suspended'], 'agent.activated', $actorId);

        return $agent;
    }

    public function suspendAgent(Agent $agent, ?int $actorId = null, ?string $reason = null): Agent
    {
        $this->transition($agent, Agent::STATUS_SUSPENDED, ['active', 'pending'], 'agent.suspended', $actorId, $reason);

        return $agent;
    }

    public function reactivateAgent(Agent $agent, ?int $actorId = null): Agent
    {
        $this->transition($agent, Agent::STATUS_ACTIVE, ['suspended'], 'agent.reactivated', $actorId);

        return $agent;
    }

    public function terminateAgent(Agent $agent, ?int $actorId = null, ?string $reason = null): Agent
    {
        $this->transition($agent, Agent::STATUS_TERMINATED, ['pending', 'active', 'suspended', 'inactive'], 'agent.terminated', $actorId, $reason);

        return $agent;
    }

    public function registerAggregator(array $profile, ?User $user = null, ?int $actorId = null): Aggregator
    {
        $country = strtoupper((string) ($profile['country_iso2'] ?? 'NG'));
        $currency = self::countryCurrency($country);

        if ($user !== null) {
            $user->forceFill(['role' => 'aggregator'])->save();
        }

        $aggregator = Aggregator::create([
            'user_id' => $user?->id,
            'code' => 'AGG-'.strtoupper(Str::random(6)),
            'name' => $profile['name'],
            'status' => Aggregator::STATUS_PENDING,
            'country_iso2' => $country,
            'region' => $profile['region'] ?? null,
            'city' => $profile['city'] ?? null,
            'kyc_status' => $profile['kyc_status'] ?? 'unverified',
            'commission_override_rate' => $profile['commission_override_rate'] ?? null,
        ]);

        $this->provisionAggregatorLedger($aggregator, $currency);

        AuditLog::record('aggregator.registered', $actorId, $aggregator->id, [
            'description' => 'Aggregator registered ('.$aggregator->code.', '.$country.').',
            'event_type' => 'operations',
            'metadata' => ['aggregator_code' => $aggregator->code, 'country_iso2' => $country],
        ]);

        return $aggregator;
    }

    public function activateAggregator(Aggregator $aggregator, ?int $actorId = null): Aggregator
    {
        $this->transitionAggregator($aggregator, Aggregator::STATUS_ACTIVE, ['pending', 'inactive', 'suspended'], 'aggregator.activated', $actorId);

        return $aggregator;
    }

    public function assignAgentToAggregator(Agent $agent, Aggregator $aggregator, ?int $actorId = null): Agent
    {
        $agent->forceFill(['aggregator_id' => $aggregator->id])->save();

        AuditLog::record('agent.assigned.aggregator', $actorId, $agent->user_id, [
            'description' => 'Agent '.$agent->agent_code.' assigned to aggregator '.$aggregator->code.'.',
            'event_type' => 'operations',
            'metadata' => ['agent_id' => $agent->id, 'aggregator_id' => $aggregator->id],
        ]);

        return $agent;
    }

    // ── Money movement (ledger-sourced, idempotent) ──────────────────────

    /**
     * Customer hands cash to the agent; agent float decreases, customer
     * wallet increases. DR agent float / CR customer wallet.
     */
    public function cashIn(
        Agent $agent,
        User $customer,
        string $amount,
        string $currency,
        ?string $idempotencyKey = null,
        ?int $actorId = null,
    ): AgencyOperation {
        return $this->execute(
            operation: AgencyOperation::TYPE_CASH_IN,
            agent: $agent,
            customer: $customer,
            amount: $amount,
            currency: $currency,
            idempotencyKey: $idempotencyKey,
            actorId: $actorId,
        );
    }

    /**
     * Customer withdraws cash from the agent; customer wallet decreases,
     * agent float increases. DR customer wallet / CR agent float.
     */
    public function cashOut(
        Agent $agent,
        User $customer,
        string $amount,
        string $currency,
        ?string $idempotencyKey = null,
        ?int $actorId = null,
    ): AgencyOperation {
        return $this->execute(
            operation: AgencyOperation::TYPE_CASH_OUT,
            agent: $agent,
            customer: $customer,
            amount: $amount,
            currency: $currency,
            idempotencyKey: $idempotencyKey,
            actorId: $actorId,
        );
    }

    protected function execute(
        string $operation,
        Agent $agent,
        User $customer,
        string $amount,
        string $currency,
        ?string $idempotencyKey,
        ?int $actorId,
    ): AgencyOperation {
        if (! $agent->isActive()) {
            throw new AgencyNotActiveException(
                "Agent [{$agent->agent_code}] is not active (status: {$agent->status})."
            );
        }

        $key = $idempotencyKey ?? 'AGY-'.strtoupper(Str::random(14));

        // Replay guard: an existing operation (posted OR failed) is returned unchanged.
        $existing = AgencyOperation::query()->where('idempotency_key', $key)->first();
        if ($existing !== null) {
            return $existing;
        }

        $float = $agent->floatAccount($currency);
        if ($float === null) {
            throw new MissingFloatAccountException(
                "No float ledger account for agent [{$agent->agent_code}] in [{$currency}]. Fund/register the account first."
            );
        }

        $wallet = LedgerAccount::query()
            ->where('owner_type', 'user')
            ->where('owner_id', $customer->id)
            ->where('currency_code', $currency)
            ->first();
        if ($wallet === null) {
            throw new MissingCustomerWalletException(
                "No ledger wallet for customer [{$customer->id}] in [{$currency}]. Register the account first."
            );
        }

        $reference = 'AGY-'.strtoupper(Str::random(12));

        try {
            $operationRow = DB::transaction(function () use ($operation, $agent, $customer, $amount, $currency, $key, $actorId, $reference, $float, $wallet) {
                $isCashIn = $operation === AgencyOperation::TYPE_CASH_IN;
                $entries = $isCashIn
                    ? [
                        ['account_id' => $float->id, 'side' => 'debit', 'amount' => $amount],
                        ['account_id' => $wallet->id, 'side' => 'credit', 'amount' => $amount],
                    ]
                    : [
                        ['account_id' => $wallet->id, 'side' => 'debit', 'amount' => $amount],
                        ['account_id' => $float->id, 'side' => 'credit', 'amount' => $amount],
                    ];

                $this->ledger->post(
                    $entries,
                    type: $operation,
                    reference: 'LEDGER-'.$reference,
                    description: ($isCashIn ? 'Agent cash-in' : 'Agent cash-out')." by {$agent->agent_code} for customer #{$customer->id}",
                    idempotencyKey: 'LEDGER:'.$key,
                    createdBy: $actorId,
                );

                $commission = 0;
                $rule = $this->commissionEngine->resolve(
                    countryIso2: $agent->country_iso2,
                    transactionType: $operation,
                    channel: 'agent',
                    agentTier: $agent->tier,
                    amount: $amount,
                );
                if ($rule !== null) {
                    $entry = $this->commissionEngine->accrue(
                        rule: $rule,
                        beneficiaryType: 'agent',
                        beneficiaryId: $agent->id,
                        currencyCode: $currency,
                        amount: $amount,
                        idempotencyKey: CommissionEngine::generateCommissionKey($key),
                    );
                    $commission = (string) ($entry?->amount ?? 0);
                }

                try {
                    return AgencyOperation::create([
                        'agent_id' => $agent->id,
                        'aggregator_id' => $agent->aggregator_id,
                        'customer_user_id' => $customer->id,
                        'operation_type' => $operation,
                        'currency_code' => $currency,
                        'amount' => $amount,
                        'fee' => 0,
                        'commission_amount' => $commission,
                        'status' => 'posted',
                        'reference' => $reference,
                        'idempotency_key' => $key,
                        'description' => ($isCashIn ? 'Agent cash-in' : 'Agent cash-out')." by {$agent->agent_code}",
                    ]);
                } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                    // Concurrent same-key caller won — adopt its row.
                    $winner = AgencyOperation::query()->where('idempotency_key', $key)->first();
                    if ($winner !== null) {
                        return $winner;
                    }

                    throw $e;
                }
            });

            AuditLog::record(
                $operation === AgencyOperation::TYPE_CASH_IN ? 'agency.cash_in' : 'agency.cash_out',
                $actorId,
                $customer->id,
                [
                    'description' => $operation.' of '.$amount.' '.$currency.' via agent '.$agent->agent_code,
                    'event_type' => 'financial',
                    'metadata' => ['agent_id' => $agent->id, 'reference' => $reference, 'amount' => $amount, 'currency' => $currency],
                ]
            );

            return $operationRow;
        } catch (\Throwable $e) {
            // Record the failed attempt (metrics source), then propagate.
            try {
                AgencyOperation::create([
                    'agent_id' => $agent->id,
                    'aggregator_id' => $agent->aggregator_id,
                    'customer_user_id' => $customer->id,
                    'operation_type' => $operation,
                    'currency_code' => $currency,
                    'amount' => $amount,
                    'fee' => 0,
                    'commission_amount' => 0,
                    'status' => 'failed',
                    'reference' => $reference,
                    'idempotency_key' => $key,
                    'description' => 'Failed: '.$e->getMessage(),
                ]);
            } catch (\Throwable) {
                // Best-effort; the ledger remains the source of truth.
            }

            throw $e;
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    public static function countryCurrency(string $countryIso2): string
    {
        return match (strtoupper($countryIso2)) {
            'NG' => 'NGN',
            'NE' => 'XOF',
            default => throw new \InvalidArgumentException("No currency configured for country [{$countryIso2}]."),
        };
    }

    protected function provisionAgentLedger(Agent $agent, string $currency): void
    {
        $this->provisionOwnerAccount('agent', $agent->id, 'Agent Float', $currency);
        $this->provisionOwnerAccount('agent', $agent->id, 'Agent Commission Accrual', $currency);
    }

    protected function provisionAggregatorLedger(Aggregator $aggregator, string $currency): void
    {
        $this->provisionOwnerAccount('aggregator', $aggregator->id, 'Aggregator Float', $currency);
    }

    protected function provisionOwnerAccount(string $ownerType, int $ownerId, string $name, string $currency): void
    {
        $exists = LedgerAccount::query()
            ->where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->where('currency_code', $currency)
            ->where('name', $name)
            ->exists();

        if (! $exists) {
            LedgerAccount::create([
                'account_type' => 'liability',
                'name' => $name,
                'currency_code' => $currency,
                'owner_type' => $ownerType,
                'owner_id' => $ownerId,
                'balance' => '0',
            ]);
        }
    }

    protected function transition(Agent $agent, string $to, array $fromAllowed, string $auditAction, ?int $actorId, ?string $reason = null): void
    {
        if (! in_array($agent->status, $fromAllowed, true)) {
            throw new \DomainException("Illegal agent transition [{$agent->status}] → [{$to}].");
        }

        $from = $agent->status;
        $agent->forceFill(['status' => $to])->save();

        AuditLog::record($auditAction, $actorId, $agent->user_id, [
            'description' => 'Agent '.$agent->agent_code.' → '.$to.($reason !== null ? " ({$reason})" : '').'.',
            'event_type' => 'operations',
            'metadata' => ['agent_id' => $agent->id, 'from' => $from, 'to' => $to, 'reason' => $reason],
        ]);
    }

    protected function transitionAggregator(Aggregator $aggregator, string $to, array $fromAllowed, string $auditAction, ?int $actorId): void
    {
        if (! in_array($aggregator->status, $fromAllowed, true)) {
            throw new \DomainException("Illegal aggregator transition [{$aggregator->status}] → [{$to}].");
        }

        $from = $aggregator->status;
        $aggregator->forceFill(['status' => $to])->save();

        AuditLog::record($auditAction, $actorId, $aggregator->user_id ?? $aggregator->id, [
            'description' => 'Aggregator '.$aggregator->code.' → '.$to.'.',
            'event_type' => 'operations',
            'metadata' => ['aggregator_id' => $aggregator->id, 'from' => $from, 'to' => $to],
        ]);
    }
}
