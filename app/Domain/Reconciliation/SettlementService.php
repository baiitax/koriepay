<?php

namespace App\Domain\Reconciliation;

use App\Domain\Accounting\LedgerAccount;
use App\Domain\Accounting\LedgerService;
use App\Models\AuditLog;
use App\Models\Settlement;
use App\Models\SettlementItem;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * PHASE 8 — Settlement.
 *
 * A settlement is a batch record of money the platform moves via a provider
 * or rail. Its status lifecycle (scheduled → pending → processing → settled |
 * failed | cancelled) is guarded here; every transition is audited.
 *
 * When the cash actually leaves the platform (postLedger), the movement goes
 * through the LEDGER: DR Settlement Payable (liability) / CR Platform Cash
 * (asset). Settlement never fabricates accounts — missing Platform Cash fails
 * loudly.
 */
class SettlementService
{
    public function __construct(private readonly LedgerService $ledger)
    {
    }

    /**
     * @param  array{rail_code?: string, period_start?: mixed, period_end?: mixed, scheduled_at?: mixed, notes?: string}  $opts
     */
    public function schedule(
        string $providerCode,
        string $countryIso2,
        string $currencyCode,
        string $amount,
        array $opts = [],
        ?int $actorId = null,
    ): Settlement {
        $settlement = Settlement::create([
            'reference' => 'STL-'.strtoupper(Str::random(10)),
            'provider_code' => $providerCode,
            'rail_code' => $opts['rail_code'] ?? null,
            'country_iso2' => strtoupper($countryIso2),
            'currency_code' => strtoupper($currencyCode),
            'amount' => $amount,
            'status' => Settlement::STATUS_SCHEDULED,
            'period_start' => $opts['period_start'] ?? null,
            'period_end' => $opts['period_end'] ?? null,
            'scheduled_at' => $opts['scheduled_at'] ?? now()->addDay(),
            'notes' => $opts['notes'] ?? null,
            'created_by' => $actorId,
        ]);

        // Accrual accounting: scheduling recognizes the liability.
        // DR Settlement Expense / CR Settlement Payable — so the payable is
        // never debited below zero when the settlement pays out.
        $expense = LedgerAccount::firstOrCreate(
            [
                'account_type' => 'expense',
                'name' => 'Settlement Expense',
                'currency_code' => $settlement->currency_code,
            ],
            ['is_system' => true, 'balance' => '0'],
        );

        $payable = LedgerAccount::firstOrCreate(
            [
                'account_type' => 'liability',
                'name' => 'Settlement Payable',
                'currency_code' => $settlement->currency_code,
                'owner_type' => 'provider',
                'owner_id' => self::providerOwnerId($providerCode),
            ],
            ['balance' => '0'],
        );

        $this->ledger->post(
            [
                ['account_id' => $expense->id, 'side' => 'debit', 'amount' => $amount],
                ['account_id' => $payable->id, 'side' => 'credit', 'amount' => $amount],
            ],
            type: 'settlement_accrual',
            description: 'Accrual for settlement '.$settlement->reference,
            idempotencyKey: 'STL-ACCRUE:'.$settlement->reference,
            createdBy: $actorId,
        );

        $this->audit('settlement.scheduled', $actorId, [
            'description' => 'Settlement '.$settlement->reference.' scheduled — '.$providerCode.' '.$amount.' '.$currencyCode,
            'event_type' => 'financial',
            'metadata' => ['settlement_id' => $settlement->id, 'provider' => $providerCode, 'amount' => $amount, 'currency' => $currencyCode],
        ]);

        return $settlement;
    }

    public function addItem(Settlement $settlement, Transaction $transaction): SettlementItem
    {
        return SettlementItem::firstOrCreate(
            ['settlement_id' => $settlement->id, 'transaction_id' => $transaction->id],
            [
                'amount' => $transaction->source_amount,
                'currency_code' => $transaction->source_currency,
                'status' => 'pending',
            ]
        );
    }

    public function markPending(Settlement $settlement, ?int $actorId = null): Settlement
    {
        return $this->transition($settlement, Settlement::STATUS_PENDING, [Settlement::STATUS_SCHEDULED], 'settlement.pending', $actorId);
    }

    public function markProcessing(Settlement $settlement, ?int $actorId = null): Settlement
    {
        return $this->transition($settlement, Settlement::STATUS_PROCESSING, [Settlement::STATUS_PENDING, Settlement::STATUS_SCHEDULED], 'settlement.processing', $actorId);
    }

    public function cancel(Settlement $settlement, ?int $actorId = null, ?string $reason = null): Settlement
    {
        return $this->transition($settlement, Settlement::STATUS_CANCELLED, [Settlement::STATUS_SCHEDULED, Settlement::STATUS_PENDING], 'settlement.cancelled', $actorId, $reason);
    }

    public function fail(Settlement $settlement, ?int $actorId = null, ?string $reason = null): Settlement
    {
        return $this->transition($settlement, Settlement::STATUS_FAILED, [Settlement::STATUS_SCHEDULED, Settlement::STATUS_PENDING, Settlement::STATUS_PROCESSING], 'settlement.failed', $actorId, $reason);
    }

    /**
     * Settle the batch. With $postLedger, moves cash via the ledger —
     * DR Settlement Payable / CR Platform Cash.
     */
    public function settle(
        Settlement $settlement,
        string $providerReference,
        string $settledAmount,
        bool $postLedger = false,
        ?int $actorId = null,
    ): Settlement {
        $this->guardStatus($settlement, [Settlement::STATUS_SCHEDULED, Settlement::STATUS_PENDING, Settlement::STATUS_PROCESSING]);

        DB::transaction(function () use ($settlement, $providerReference, $settledAmount, $postLedger, $actorId) {
            if ($postLedger) {
                $cash = LedgerAccount::query()
                    ->where('account_type', 'asset')
                    ->where('name', 'Platform Cash')
                    ->where('currency_code', $settlement->currency_code)
                    ->first();

                if ($cash === null) {
                    throw new \DomainException(
                        "Platform Cash account missing for [{$settlement->currency_code}]. Run the ledger seed before settling."
                    );
                }

                $payable = LedgerAccount::firstOrCreate(
                    [
                        'account_type' => 'liability',
                        'name' => 'Settlement Payable',
                        'currency_code' => $settlement->currency_code,
                        'owner_type' => 'provider',
                        'owner_id' => self::providerOwnerId($settlement->provider_code),
                    ],
                    ['balance' => '0'],
                );

                $this->ledger->post(
                    [
                        ['account_id' => $payable->id, 'side' => 'debit', 'amount' => $settledAmount],
                        ['account_id' => $cash->id, 'side' => 'credit', 'amount' => $settledAmount],
                    ],
                    type: 'settlement',
                    description: 'Settlement '.$settlement->reference.' to '.$settlement->provider_code,
                    idempotencyKey: 'STL:'.$settlement->reference,
                    createdBy: $actorId,
                );
            }

            $settlement->update([
                'status' => Settlement::STATUS_SETTLED,
                'settled_amount' => $settledAmount,
                'provider_reference' => $providerReference,
                'settled_at' => now(),
            ]);
        });

        $this->audit('settlement.settled', $actorId, [
            'description' => 'Settlement '.$settlement->reference.' settled — '.$settledAmount.' '.$settlement->currency_code.' ('.$providerReference.')',
            'event_type' => 'financial',
            'metadata' => ['settlement_id' => $settlement->id, 'provider_reference' => $providerReference, 'settled_amount' => $settledAmount],
        ]);

        return $settlement->fresh();
    }

    // ── Command center inputs ────────────────────────────────────────────

    /**
     * Settlement exposure: total value of settlements not yet settled, per scope.
     */
    public function exposure(?string $countryIso2 = null, ?string $currencyCode = null): string
    {
        return (string) Settlement::query()
            ->whereIn('status', [Settlement::STATUS_SCHEDULED, Settlement::STATUS_PENDING, Settlement::STATUS_PROCESSING])
            ->when($countryIso2, fn ($q) => $q->where('country_iso2', strtoupper($countryIso2)))
            ->when($currencyCode, fn ($q) => $q->where('currency_code', strtoupper($currencyCode)))
            ->sum('amount');
    }

    /**
     * The next scheduled settlement (the "Next Settlement" KPI).
     */
    public function nextSettlement(): ?Settlement
    {
        return Settlement::query()
            ->where('status', Settlement::STATUS_SCHEDULED)
            ->whereNotNull('scheduled_at')
            ->orderBy('scheduled_at')
            ->first();
    }

    // ── Internals ────────────────────────────────────────────────────────

    protected function transition(Settlement $settlement, string $to, array $fromAllowed, string $auditAction, ?int $actorId, ?string $reason = null): Settlement
    {
        $this->guardStatus($settlement, $fromAllowed);
        $from = $settlement->status;
        $settlement->update(['status' => $to]);

        $this->audit($auditAction, $actorId, [
            'description' => 'Settlement '.$settlement->reference.' → '.$to.($reason !== null ? " ({$reason})" : ''),
            'event_type' => 'financial',
            'metadata' => ['settlement_id' => $settlement->id, 'from' => $from, 'to' => $to, 'reason' => $reason],
        ]);

        return $settlement->fresh();
    }

    protected function guardStatus(Settlement $settlement, array $allowed): void
    {
        if (! in_array($settlement->status, $allowed, true)) {
            throw new \DomainException(
                "Illegal settlement transition [{$settlement->status}] for [{$settlement->reference}]."
            );
        }
    }

    /**
     * Stable numeric owner for a provider-code owner on ledger accounts
     * (owner_id is BIGINT; provider codes are strings).
     */
    public static function providerOwnerId(string $providerCode): int
    {
        return crc32($providerCode);
    }

    /**
     * Audit only when a real actor exists — audit_logs.admin_id is a FK, so
     * system events without an actor are simply not recorded (no fake actors).
     */
    protected function audit(string $action, ?int $actorId, array $context): void
    {
        if ($actorId !== null) {
            AuditLog::record($action, $actorId, $actorId, $context);
        }
    }
}
