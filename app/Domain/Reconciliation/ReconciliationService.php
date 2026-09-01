<?php

namespace App\Domain\Reconciliation;

use App\Domain\Accounting\LedgerAccount;
use App\Domain\Accounting\LedgerEntry;
use App\Models\AuditLog;
use App\Models\BalanceSnapshot;
use App\Models\ReconciliationItem;
use App\Models\ReconciliationRun;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * PHASE 8 — Reconciliation.
 *
 * Matches INTERNAL records (transactions with a provider_reference) against
 * PROVIDER records (transaction_attempts with a provider_reference + amount).
 * Both sides are real persisted records — nothing is invented. Each run
 * persists aggregate counts + item-level evidence and a 0–100 health score.
 *
 * balance-snapshot comparison: derives an account's balance from ledger
 * entries and compares it to the maintained projection (the guard against
 * direct balance mutation).
 */
class ReconciliationService
{
    /**
     * Run a reconciliation over a period, optionally scoped.
     */
    public function run(
        Carbon $periodStart,
        Carbon $periodEnd,
        ?string $providerCode = null,
        ?string $countryIso2 = null,
        ?string $currencyCode = null,
        ?int $runBy = null,
    ): ReconciliationRun {
        $iso3 = $countryIso2 !== null ? self::iso2ToIso3($countryIso2) : null;

        $run = ReconciliationRun::create([
            'reference' => 'REC-'.strtoupper(Str::random(10)),
            'provider_code' => $providerCode,
            'country_iso2' => $countryIso2 !== null ? strtoupper($countryIso2) : null,
            'currency_code' => $currencyCode !== null ? strtoupper($currencyCode) : null,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'status' => ReconciliationRun::STATUS_RUNNING,
            'started_at' => now(),
            'run_by' => $runBy,
        ]);

        try {
            $internal = Transaction::query()
                ->whereNotNull('provider_reference')
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->when($providerCode, fn ($q) => $q->where('provider', $providerCode))
                ->when($iso3, fn ($q) => $q->where('country_code', $iso3))
                ->when($currencyCode, fn ($q) => $q->where('source_currency', strtoupper($currencyCode)))
                ->get(['id', 'provider', 'provider_reference', 'source_amount', 'source_currency', 'country_code']);

            $providerRows = DB::table('transaction_attempts as ta')
                ->join('transactions as t', 't.id', '=', 'ta.transaction_id')
                ->whereNotNull('ta.provider_reference')
                ->whereBetween('ta.created_at', [$periodStart, $periodEnd])
                ->when($providerCode, fn ($q) => $q->where('ta.provider', $providerCode))
                ->when($iso3, fn ($q) => $q->where('t.country_code', $iso3))
                ->when($currencyCode, fn ($q) => $q->where('t.source_currency', strtoupper($currencyCode)))
                ->get(['ta.id', 'ta.transaction_id', 'ta.provider', 'ta.provider_reference', 'ta.amount', 't.source_amount']);

            // Provider side grouped by reference (effective amount = attempt
            // amount when recorded, else the transaction's own amount).
            $providerByRef = [];
            foreach ($providerRows as $row) {
                $ref = (string) $row->provider_reference;
                $providerByRef[$ref][] = [
                    'id' => $row->id,
                    'provider' => $row->provider,
                    'amount' => $row->amount !== null ? (string) $row->amount : (string) $row->source_amount,
                ];
            }

            $internalAmount = '0';
            $providerAmount = '0';
            $items = [];
            $matched = $mismatch = $unmatchedInternal = $unmatchedProviderRefs = 0;
            $duplicates = 0;

            // Internal side.
            foreach ($internal as $tx) {
                $internalAmount = bcadd($internalAmount, (string) $tx->source_amount, 2);
                $ref = (string) $tx->provider_reference;
                $providerRecords = $providerByRef[$ref] ?? null;

                if ($providerRecords === null) {
                    $unmatchedInternal++;
                    $items[] = [
                        'transaction_id' => $tx->id,
                        'provider' => $tx->provider,
                        'provider_reference' => $ref,
                        'match_key' => $ref,
                        'status' => ReconciliationItem::STATUS_UNMATCHED_INTERNAL,
                        'internal_amount' => $tx->source_amount,
                    ];
                    continue;
                }

                $first = $providerRecords[0];
                $providerAmount = bcadd($providerAmount, $first['amount'], 2);

                if (bccomp((string) $tx->source_amount, $first['amount'], 2) === 0) {
                    $matched++;
                    $items[] = [
                        'transaction_id' => $tx->id,
                        'provider' => $tx->provider,
                        'provider_reference' => $ref,
                        'match_key' => $ref,
                        'status' => ReconciliationItem::STATUS_MATCHED,
                        'internal_amount' => $tx->source_amount,
                        'provider_amount' => $first['amount'],
                        'discrepancy' => '0.00',
                    ];
                } else {
                    $mismatch++;
                    $items[] = [
                        'transaction_id' => $tx->id,
                        'provider' => $tx->provider,
                        'provider_reference' => $ref,
                        'match_key' => $ref,
                        'status' => ReconciliationItem::STATUS_AMOUNT_MISMATCH,
                        'internal_amount' => $tx->source_amount,
                        'provider_amount' => $first['amount'],
                        'discrepancy' => bcsub((string) $tx->source_amount, $first['amount'], 2),
                    ];
                }

                // Duplicate provider confirmations for the same reference.
                foreach (array_slice($providerRecords, 1) as $extra) {
                    $duplicates++;
                    $providerAmount = bcadd($providerAmount, $extra['amount'], 2);
                    $items[] = [
                        'transaction_id' => $tx->id,
                        'provider' => $tx->provider,
                        'provider_reference' => $ref,
                        'match_key' => $ref,
                        'status' => ReconciliationItem::STATUS_DUPLICATE,
                        'internal_amount' => $tx->source_amount,
                        'provider_amount' => $extra['amount'],
                        'discrepancy' => $extra['amount'],
                    ];
                }

                unset($providerByRef[$ref]);
            }

            // Provider side without an internal match.
            foreach ($providerByRef as $ref => $records) {
                $unmatchedProviderRefs++;
                $providerAmount = bcadd($providerAmount, $records[0]['amount'], 2);
                $items[] = [
                    'transaction_id' => null,
                    'provider' => $records[0]['provider'],
                    'provider_reference' => $ref,
                    'match_key' => $ref,
                    'status' => ReconciliationItem::STATUS_UNMATCHED_PROVIDER,
                    'provider_amount' => $records[0]['amount'],
                ];
                foreach (array_slice($records, 1) as $extra) {
                    $duplicates++;
                    $providerAmount = bcadd($providerAmount, $extra['amount'], 2);
                    $items[] = [
                        'transaction_id' => null,
                        'provider' => $extra['provider'],
                        'provider_reference' => $ref,
                        'match_key' => $ref,
                        'status' => ReconciliationItem::STATUS_DUPLICATE,
                        'provider_amount' => $extra['amount'],
                        'discrepancy' => $extra['amount'],
                    ];
                }
            }

            $internalCount = $internal->count();
            $providerCount = count($providerRows);
            $total = max(1, $internalCount);
            $base = 100 * $matched / $total;
            $penalty = ($mismatch * 2) + $duplicates + $unmatchedProviderRefs;
            $health = round(max(0.0, min(100.0, $base - $penalty)), 2);

            foreach ($items as $item) {
                ReconciliationItem::create(['run_id' => $run->id] + $item);
            }

            $run->update([
                'internal_count' => $internalCount,
                'provider_count' => $providerCount,
                'matched_count' => $matched,
                'unmatched_internal_count' => $unmatchedInternal,
                'unmatched_provider_count' => $unmatchedProviderRefs,
                'amount_mismatch_count' => $mismatch,
                'duplicate_count' => $duplicates,
                'internal_amount' => $internalAmount,
                'provider_amount' => $providerAmount,
                'difference' => bcsub($internalAmount, $providerAmount, 2),
                'health_score' => $health,
                'status' => ReconciliationRun::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);

            if ($runBy !== null) {
                AuditLog::record('reconciliation.run', $runBy, $runBy, [
                    'description' => 'Reconciliation '.$run->reference.' completed — health '.$health.'% ('.($internalCount - $unmatchedInternal).'/'.$internalCount.' matched)',
                    'event_type' => 'financial',
                    'metadata' => ['run_id' => $run->id, 'health_score' => $health],
                ]);
            }

            return $run->fresh();
        } catch (\Throwable $e) {
            $run->update(['status' => ReconciliationRun::STATUS_FAILED]);
            throw $e;
        }
    }

    /**
     * Latest completed run + open mismatch totals — the "Reconciliation
     * Health" KPI.
     */
    public function reconciliationHealth(): array
    {
        $latest = ReconciliationRun::query()
            ->where('status', ReconciliationRun::STATUS_COMPLETED)
            ->orderByDesc('completed_at')
            ->first();

        if ($latest === null) {
            return ['health_score' => null, 'open_exceptions' => 0, 'run_reference' => null, 'freshness' => null];
        }

        $openExceptions = ReconciliationItem::query()
            ->where('run_id', $latest->id)
            ->whereIn('status', [
                ReconciliationItem::STATUS_UNMATCHED_INTERNAL,
                ReconciliationItem::STATUS_UNMATCHED_PROVIDER,
                ReconciliationItem::STATUS_AMOUNT_MISMATCH,
                ReconciliationItem::STATUS_DUPLICATE,
            ])
            ->count();

        return [
            'health_score' => (string) $latest->health_score,
            'open_exceptions' => $openExceptions,
            'run_reference' => $latest->reference,
            'freshness' => $latest->completed_at?->toIso8601String(),
        ];
    }

    // ── Balance-snapshot comparison ──────────────────────────────────────

    /**
     * Project an account's balance from its ledger entries and compare against
     * the maintained projection. Stores a MATCHED|MISMATCH snapshot.
     */
    public function takeBalanceSnapshot(LedgerAccount $account): BalanceSnapshot
    {
        $derived = $this->derivedBalance($account);
        $projected = (string) $account->balance;
        $difference = bcsub($derived, $projected, 2);

        return BalanceSnapshot::create([
            'account_id' => $account->id,
            'projected_balance' => $projected,
            'derived_balance' => $derived,
            'difference' => $difference,
            'status' => bccomp($difference, '0', 2) === 0
                ? BalanceSnapshot::STATUS_MATCHED
                : BalanceSnapshot::STATUS_MISMATCH,
            'snapshot_at' => now(),
        ]);
    }

    public function snapshotAllActiveAccounts(): int
    {
        $count = 0;
        foreach (LedgerAccount::where('is_active', true)->cursor() as $account) {
            $this->takeBalanceSnapshot($account);
            $count++;
        }

        return $count;
    }

    /**
     * Derived balance from ledger entries: asset/expense are debit-normal
     * (Σ debit − Σ credit); liability/equity/income are credit-normal.
     */
    public function derivedBalance(LedgerAccount $account): string
    {
        $debits = (string) LedgerEntry::where('account_id', $account->id)->where('side', 'debit')->sum('amount');
        $credits = (string) LedgerEntry::where('account_id', $account->id)->where('side', 'credit')->sum('amount');

        return in_array($account->account_type, ['asset', 'expense'], true)
            ? bcsub($debits, $credits, 2)
            : bcsub($credits, $debits, 2);
    }

    // ── Item resolution ──────────────────────────────────────────────────

    public function resolveItem(ReconciliationItem $item, string $resolution, ?int $actorId = null, ?string $note = null): ReconciliationItem
    {
        if (! in_array($resolution, ['accepted', 'rejected', 'adjusted'], true)) {
            throw new \InvalidArgumentException("Invalid resolution [{$resolution}].");
        }

        $item->update([
            'resolution' => $resolution,
            'resolved_by' => $actorId,
            'resolved_at' => now(),
            'resolution_note' => $note,
        ]);

        if ($actorId !== null) {
            AuditLog::record('reconciliation.resolved', $actorId, $actorId, [
                'description' => 'Reconciliation item #'.$item->id.' ('.$item->status.') resolved as '.$resolution,
                'event_type' => 'financial',
                'metadata' => ['item_id' => $item->id, 'resolution' => $resolution, 'note' => $note],
            ]);
        }

        return $item;
    }

    public static function iso2ToIso3(string $iso2): string
    {
        return match (strtoupper($iso2)) {
            'NG' => 'NGA',
            'NE' => 'NER',
            default => strtoupper($iso2),
        };
    }
}
