<?php

namespace App\Domain\Aggregator;

use App\Domain\Accounting\LedgerAccount;
use App\Domain\Accounting\LedgerService;
use App\Models\Aggregator;
use App\Models\AggregatorSettlement;
use App\Models\AuditLog;
use App\Models\CommissionEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * AGGREGATOR CONSOLE — Stage E (settlement center, §38–43, §66–67).
 *
 * Aggregator-scoped settlement batches with the full breakdown (gross /
 * fees / commission / adjustments / net). Money movement on settle() goes
 * through the ledger: DR Settlement Expense / CR Aggregator Float — the
 * posting is idempotent and audited, and the ledger guard prevents the
 * float from ever going negative. Reconciliation compares expected (Σ
 * accrued entries in the period) against actual (what was paid) — a
 * difference is shown, never silently reconciled.
 */
class AggregatorSettlementsService
{
    public function __construct(private readonly LedgerService $ledger)
    {
    }

    public function summary(Aggregator $aggregator): array
    {
        $rows = AggregatorSettlement::query()->where('aggregator_id', $aggregator->id)->get();

        return [
            'pending' => $rows->where('status', AggregatorSettlement::STATUS_PENDING)->count(),
            'processing' => $rows->where('status', AggregatorSettlement::STATUS_PROCESSING)->count(),
            'settled' => $rows->where('status', AggregatorSettlement::STATUS_SETTLED)->count(),
            'failed' => $rows->where('status', AggregatorSettlement::STATUS_FAILED)->count(),
            'under_review' => $rows->where('status', AggregatorSettlement::STATUS_UNDER_REVIEW)->count(),
        ];
    }

    /** @param  array{status?: string}  $filters */
    public function center(Aggregator $aggregator, array $filters = []): array
    {
        $query = AggregatorSettlement::query()
            ->where('aggregator_id', $aggregator->id)
            ->latest('created_at');

        $status = (string) ($filters['status'] ?? 'all');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $settlements = $query->get()->map(function (AggregatorSettlement $s) {
            $row = $s->toArray();
            $row['reconciliation'] = $s->reconciliation();

            return $row;
        });

        return [
            'rows' => $settlements,
            'filter' => $status,
            'summary' => $this->summary($aggregator),
        ];
    }

    /**
     * Build a settlement batch from the period's accrued aggregator
     * commission entries (gross / adjustments / reversals / net).
     */
    public function create(Aggregator $aggregator, string $currency, ?Carbon $periodStart = null, ?Carbon $periodEnd = null, ?int $actorId = null): AggregatorSettlement
    {
        $currency = strtoupper($currency);
        $periodStart ??= now()->startOfMonth();
        $periodEnd ??= now()->endOfMonth();

        $entries = CommissionEntry::query()
            ->where('beneficiary_type', 'aggregator')
            ->where('beneficiary_id', $aggregator->id)
            ->where('currency_code', $currency)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->get();

        $gross = bcadd('0', (string) $entries->filter(fn ($e) => ! str_starts_with((string) $e->rule_id, 'adj:') && ! str_starts_with((string) $e->rule_id, 'rev:'))->sum('amount'), 2);
        $adjustments = bcadd('0', (string) $entries->filter(fn ($e) => str_starts_with((string) $e->rule_id, 'adj:'))->sum('amount'), 2);
        $reversals = bcadd('0', (string) $entries->filter(fn ($e) => str_starts_with((string) $e->rule_id, 'rev:'))->sum('amount'), 2);
        $net = bcadd(bcadd($gross, $adjustments, 2), $reversals, 2);

        $settlement = AggregatorSettlement::create([
            'reference' => 'ASL-'.strtoupper(Str::random(8)),
            'aggregator_id' => $aggregator->id,
            'currency_code' => $currency,
            'gross_amount' => $gross,
            'fees' => '0',
            'commission_amount' => '0',
            'adjustments' => bcadd($adjustments, $reversals, 2), // combined reductions (adjustments + reversals)
            'net_amount' => $net,
            'expected_amount' => $net,
            'status' => AggregatorSettlement::STATUS_PENDING,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'created_by' => $actorId,
        ]);

        AuditLog::record('settlement.created', $actorId, $aggregator->user_id, [
            'description' => "Settlement batch {$settlement->reference} created — {$currency} gross {$gross} net {$net}.",
            'event_type' => 'financial',
            'metadata' => ['aggregator_settlement_id' => $settlement->id, 'reference' => $settlement->reference, 'currency' => $currency],
        ]);

        return $settlement;
    }

    public function markProcessing(AggregatorSettlement $settlement, ?int $actorId = null): AggregatorSettlement
    {
        $this->guardNotFinal($settlement, 'processing');

        $settlement->forceFill([
            'status' => AggregatorSettlement::STATUS_PROCESSING,
            'processed_by' => $actorId,
            'processed_at' => now(),
        ])->save();

        AuditLog::record('settlement.processing', $actorId, $settlement->aggregator->user_id, [
            'description' => "Settlement batch {$settlement->reference} moved to processing.",
            'event_type' => 'financial',
            'metadata' => ['aggregator_settlement_id' => $settlement->id],
        ]);

        return $settlement;
    }

    /**
     * Settle the batch: post the payout to the aggregator float on the
     * ledger (idempotent), mark the period's commission entries paid, and
     * record the actual amount for reconciliation. Settled batches are final.
     */
    public function settle(AggregatorSettlement $settlement, ?string $actualAmount = null, ?int $actorId = null): AggregatorSettlement
    {
        if ($settlement->status === AggregatorSettlement::STATUS_SETTLED) {
            return $settlement; // idempotent replay
        }

        if (in_array($settlement->status, [AggregatorSettlement::STATUS_FAILED, AggregatorSettlement::STATUS_UNDER_REVIEW], true)) {
            throw ValidationException::withMessages([
                'status' => 'A '.$settlement->status.' batch cannot be settled directly — create a new batch.',
            ]);
        }

        $aggregator = $settlement->aggregator;
        $float = $aggregator->floatAccount($settlement->currency_code);
        if ($float === null) {
            throw ValidationException::withMessages([
                'status' => 'No aggregator float provisioned for '.$settlement->currency_code.'.',
            ]);
        }

        $expense = LedgerAccount::firstOrCreate(
            [
                'account_type' => 'expense',
                'name' => 'Settlement Expense',
                'currency_code' => $settlement->currency_code,
            ],
            ['is_system' => true, 'balance' => '0'],
        );

        // A non-positive net batch has nothing to pay out — no posting, and
        // the settlement records the honest actual (0) for reconciliation.
        $txId = null;
        if (bccomp((string) $settlement->net_amount, '0', 2) > 0) {
            $tx = $this->ledger->post(
                [
                    ['account_id' => $expense->id, 'side' => 'debit', 'amount' => (string) $settlement->net_amount],
                    ['account_id' => $float->id, 'side' => 'credit', 'amount' => (string) $settlement->net_amount],
                ],
                type: 'aggregator_settlement',
                reference: 'LEDGER-'.$settlement->reference,
                description: "Settlement payout for {$settlement->reference} ({$settlement->net_amount} {$settlement->currency_code}) to aggregator float",
                idempotencyKey: 'ASL-SETTLE-'.$settlement->reference,
                createdBy: $actorId,
            );
            $txId = $tx->id;
        }

        $settlement->forceFill([
            'status' => AggregatorSettlement::STATUS_SETTLED,
            'actual_amount' => $actualAmount ?? (string) $settlement->net_amount,
            'ledger_transaction_id' => $txId,
            'processed_by' => $actorId,
            'processed_at' => now(),
        ])->save();

        // Mark the period's accrued aggregator entries as paid.
        CommissionEntry::query()
            ->where('beneficiary_type', 'aggregator')
            ->where('beneficiary_id', $aggregator->id)
            ->where('currency_code', $settlement->currency_code)
            ->where('status', 'accrued')
            ->whereBetween('created_at', [$settlement->period_start ?? now()->startOfMonth(), $settlement->period_end ?? now()->endOfMonth()])
            ->update(['status' => 'paid']);

        AuditLog::record('settlement.settled', $actorId, $aggregator->user_id, [
            'description' => "Settlement batch {$settlement->reference} settled — {$settlement->net_amount} {$settlement->currency_code} paid to aggregator float.",
            'event_type' => 'financial',
            'metadata' => ['aggregator_settlement_id' => $settlement->id, 'ledger_transaction_id' => $tx->id],
        ]);

        return $settlement;
    }

    public function fail(AggregatorSettlement $settlement, ?int $actorId = null, ?string $reason = null): AggregatorSettlement
    {
        $this->guardNotFinal($settlement, 'failed');

        $settlement->forceFill([
            'status' => AggregatorSettlement::STATUS_FAILED,
            'notes' => $reason ?: 'Settlement failed at the payout rail.',
            'processed_by' => $actorId,
            'processed_at' => now(),
        ])->save();

        AuditLog::record('settlement.failed', $actorId, $settlement->aggregator->user_id, [
            'description' => "Settlement batch {$settlement->reference} failed — ".$settlement->notes,
            'event_type' => 'financial',
            'metadata' => ['aggregator_settlement_id' => $settlement->id],
        ]);

        return $settlement;
    }

    public function underReview(AggregatorSettlement $settlement, ?int $actorId = null, ?string $reason = null): AggregatorSettlement
    {
        if ($settlement->status === AggregatorSettlement::STATUS_SETTLED) {
            throw ValidationException::withMessages(['status' => 'Settled batches are final.']);
        }

        $settlement->forceFill([
            'status' => AggregatorSettlement::STATUS_UNDER_REVIEW,
            'notes' => $reason ?: 'Placed under review.',
            'processed_by' => $actorId,
            'processed_at' => now(),
        ])->save();

        AuditLog::record('settlement.under_review', $actorId, $settlement->aggregator->user_id, [
            'description' => "Settlement batch {$settlement->reference} placed under review.",
            'event_type' => 'financial',
            'metadata' => ['aggregator_settlement_id' => $settlement->id],
        ]);

        return $settlement;
    }

    protected function guardNotFinal(AggregatorSettlement $settlement, string $target): void
    {
        if (in_array($settlement->status, [
            AggregatorSettlement::STATUS_SETTLED,
            AggregatorSettlement::STATUS_FAILED,
            AggregatorSettlement::STATUS_UNDER_REVIEW,
        ], true)) {
            throw ValidationException::withMessages([
                'status' => "A {$settlement->status} batch cannot be moved to {$target}.",
            ]);
        }
    }
}
