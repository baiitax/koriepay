<?php

namespace App\Domain\Accounting;

use App\Domain\Accounting\Exceptions\CurrencyMismatchException;
use App\Domain\Accounting\Exceptions\InsufficientFundsException;
use App\Domain\Accounting\Exceptions\LedgerValidationException;
use App\Domain\Accounting\Exceptions\UnbalancedLedgerException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * LedgerService — the single gateway for every monetary movement.
 *
 * Invariants enforced here (and by the automated test suite):
 *   1. Every posting is a balanced batch: Σ debits == Σ credits per currency.
 *   2. No account balance may go negative in its normal direction.
 *   3. Entries are append-only; projections are updated as a side effect.
 *   4. Concurrent postings against the same account are serialized by row locks.
 *   5. Idempotency keys return the original posting, never a duplicate.
 */
class LedgerService
{
    public function __construct(
        private readonly IdempotencyService $idempotency,
    ) {
    }

    /**
     * Post a balanced set of entries.
     *
     * @param  array<int, array{account_id:int, side:string, amount:string}>  $entries
     * @return LedgerTransaction
     *
     * @throws UnbalancedLedgerException|InsufficientFundsException|LedgerValidationException
     */
    public function post(
        array $entries,
        string $type,
        ?string $reference = null,
        ?string $description = null,
        ?string $idempotencyKey = null,
        ?int $relatedTransactionId = null,
        ?int $createdBy = null,
        array $metadata = [],
    ): LedgerTransaction {
        $reference ??= LedgerTransaction::generateReference();

        if ($idempotencyKey !== null) {
            $result = $this->idempotency->execute($idempotencyKey, fn () => $this->postFresh(
                $entries, $type, $reference, $description,
                $idempotencyKey, $relatedTransactionId, $createdBy, $metadata
            ));

            // Replay path: the idempotency store serialized the model attributes.
            // Resolve the ORIGINAL LedgerTransaction so callers always receive
            // the same object type, never a duplicate posting.
            if (is_array($result) && isset($result['id'])) {
                return LedgerTransaction::query()->findOrFail($result['id']);
            }

            return $result;
        }

        return $this->postFresh(
            $entries, $type, $reference, $description,
            null, $relatedTransactionId, $createdBy, $metadata
        );
    }

    protected function postFresh(
        array $entries,
        string $type,
        string $reference,
        ?string $description,
        ?string $idempotencyKey,
        ?int $relatedTransactionId,
        ?int $createdBy,
        array $metadata,
    ): LedgerTransaction {
        if (count($entries) < 2) {
            throw new LedgerValidationException('A ledger posting needs at least two entries.');
        }

        // 1. Normalize + validate shape & amounts
        $normalized = [];
        foreach ($entries as $i => $entry) {
            $accountId = (int) ($entry['account_id'] ?? 0);
            $side = strtolower((string) ($entry['side'] ?? ''));
            $amount = (string) ($entry['amount'] ?? '');

            if (! in_array($side, [LedgerEntry::SIDE_DEBIT, LedgerEntry::SIDE_CREDIT], true)) {
                throw new LedgerValidationException("Entry #{$i}: side must be debit|credit.");
            }
            if (! preg_match('/^\d+(\.\d{1,2})?$/', $amount) || (float) $amount <= 0) {
                throw new LedgerValidationException("Entry #{$i}: amount must be a positive decimal, got '{$amount}'.");
            }
            $normalized[] = ['account_id' => $accountId, 'side' => $side, 'amount' => $amount];
        }

        // 2. Lock accounts (serialize concurrent postings) and load currencies
        $accountIds = array_values(array_unique(array_column($normalized, 'account_id')));
        /** @var \Illuminate\Support\Collection<int, LedgerAccount> $accounts */
        $accounts = LedgerAccount::whereIn('id', $accountIds)->lockForUpdate()->get()->keyBy('id');

        foreach ($accountIds as $id) {
            if (! $accounts->has($id)) {
                throw new LedgerValidationException("Ledger account #{$id} does not exist.");
            }
            if (! $accounts[$id]->is_active) {
                throw new LedgerValidationException("Ledger account #{$id} is inactive.");
            }
        }

        // 3. Balance check per currency (decimal-string math via bcmath)
        $debits = [];
        $credits = [];
        foreach ($normalized as $entry) {
            $account = $accounts[$entry['account_id']];
            $currency = strtoupper($account->currency_code);
            $money = Money::fromDecimal($entry['amount'], $currency);

            if ($entry['side'] === LedgerEntry::SIDE_DEBIT) {
                $debits[$currency] = bcadd($debits[$currency] ?? '0', $money->toDecimal(), 2);
            } else {
                $credits[$currency] = bcadd($credits[$currency] ?? '0', $money->toDecimal(), 2);
            }
        }

        foreach (array_keys(array_merge($debits, $credits)) as $currency) {
            $d = $debits[$currency] ?? '0';
            $c = $credits[$currency] ?? '0';
            if (bccomp($d, $c, 2) !== 0) {
                throw new UnbalancedLedgerException(
                    "Unbalanced posting in {$currency}: debits {$d} ≠ credits {$c}"
                );
            }
        }

        // 4. Fast-path sufficiency check (friendly error, NOT authoritative —
        //    the authoritative guard is the atomic compare-and-decrement below).
        foreach ($normalized as $entry) {
            $account = $accounts[$entry['account_id']];
            $reduces = $entry['side'] === LedgerEntry::SIDE_CREDIT && $account->isDebitNormal()
                || $entry['side'] === LedgerEntry::SIDE_DEBIT && ! $account->isDebitNormal();

            if ($reduces && bccomp((string) $account->balance, $entry['amount'], 2) < 0) {
                throw new InsufficientFundsException(
                    "Insufficient funds on account #{$account->id} ({$account->name}): "
                    ."{$account->balance} {$account->currency_code} < {$entry['amount']}"
                );
            }
        }

        // 5. Create the immutable posting.
        // ORDER MATTERS for concurrency: the guarded projection UPDATE is the
        // FIRST write in the transaction — it acquires the DB write lock up
        // front, so concurrent workers serialize on the guard instead of
        // colliding on later INSERTs (verified under 100-way races).
        return DB::transaction(function () use (
            $normalized, $accounts, $type, $reference, $description,
            $idempotencyKey, $relatedTransactionId, $createdBy, $metadata
        ) {
            foreach ($normalized as $entry) {
                $this->applyProjectionUpdate(
                    $accounts[$entry['account_id']],
                    $entry['side'],
                    $entry['amount']
                );
            }

            $ledgerTx = LedgerTransaction::create([
                'reference' => $reference,
                'type' => $type,
                'related_transaction_id' => $relatedTransactionId,
                'idempotency_key' => $idempotencyKey,
                'description' => $description,
                'created_by' => $createdBy,
                'metadata' => $metadata,
            ]);

            foreach ($normalized as $entry) {
                $account = $accounts[$entry['account_id']];

                LedgerEntry::create([
                    'ledger_transaction_id' => $ledgerTx->id,
                    'account_id' => $entry['account_id'],
                    'side' => $entry['side'],
                    'amount' => $entry['amount'],
                    'currency_code' => $account->currency_code,
                ]);
            }

            return $ledgerTx;
        });
    }

    /**
     * Atomically update the balance projection with a guarded write.
     *
     * The authoritative race guard: `UPDATE … SET balance = balance - X
     * WHERE id = ? AND balance >= X` returns 0 affected rows when the funds
     * are gone — regardless of database driver or isolation level. This is
     * what makes 100 simultaneous withdrawals consume only available funds
     * (verified by tests/Feature/ConcurrencyTest.php).
     */
    protected function applyProjectionUpdate(LedgerAccount $account, string $side, string $amount): void
    {
        $increases = ($side === LedgerEntry::SIDE_DEBIT && $account->isDebitNormal())
            || ($side === LedgerEntry::SIDE_CREDIT && ! $account->isDebitNormal());

        if ($increases) {
            LedgerAccount::whereKey($account->id)->increment('balance', (float) $amount);

            return;
        }

        // Reducing write — guarded so the balance can never go negative.
        $affected = LedgerAccount::whereKey($account->id)
            ->where('balance', '>=', $amount)
            ->decrement('balance', (float) $amount);

        if ($affected === 0) {
            throw new InsufficientFundsException(
                "Insufficient funds on account #{$account->id} ({$account->name}): "
                ."cannot reduce by {$amount} {$account->currency_code}"
            );
        }
    }

    /**
     * Compute the balance of an account derived purely from its ledger entries.
     * Used by the reconciliation engine to compare against the projection.
     */
    public function derivedBalance(LedgerAccount $account): string
    {
        $debits = LedgerEntry::where('account_id', $account->id)
            ->where('side', LedgerEntry::SIDE_DEBIT)
            ->sum('amount');

        $credits = LedgerEntry::where('account_id', $account->id)
            ->where('side', LedgerEntry::SIDE_CREDIT)
            ->sum('amount');

        $balance = $account->isDebitNormal()
            ? bcsub((string) $debits, (string) $credits, 2)
            : bcsub((string) $credits, (string) $debits, 2);

        return $balance;
    }

    public static function assertSupportedCurrency(string $currency): void
    {
        if (! Money::supported($currency)) {
            throw new InvalidArgumentException("Unsupported currency: {$currency}");
        }
    }

    public static function assertSameCurrency(string $a, string $b): void
    {
        if (strtoupper($a) !== strtoupper($b)) {
            throw new CurrencyMismatchException("Currency mismatch: {$a} vs {$b}");
        }
    }
}
