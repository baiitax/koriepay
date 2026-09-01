<?php

namespace App\Domain\Accounting;

use App\Domain\Accounting\Exceptions\LedgerValidationException;

/**
 * Formal reversal (§45). The original posting is never touched; a NEW posting
 * with opposite sides references it. Original remains immutable.
 */
class ReversalService
{
    public function __construct(private readonly LedgerService $ledger)
    {
    }

    public function reverse(
        LedgerTransaction $original,
        ?string $reason = null,
        ?int $createdBy = null,
    ): LedgerTransaction {
        $entries = $original->entries()->get();

        if ($entries->isEmpty()) {
            throw new LedgerValidationException("Ledger transaction {$original->reference} has no entries.");
        }

        $opposite = $entries->map(fn (LedgerEntry $e) => [
            'account_id' => $e->account_id,
            'side' => $e->side === LedgerEntry::SIDE_DEBIT ? LedgerEntry::SIDE_CREDIT : LedgerEntry::SIDE_DEBIT,
            'amount' => (string) $e->amount,
        ])->all();

        return $this->ledger->post(
            entries: $opposite,
            type: 'reversal',
            reference: 'REV-'.$original->reference,
            description: 'Reversal of '.$original->reference.($reason ? " — {$reason}" : ''),
            idempotencyKey: 'rev-'.md5($original->reference),
            relatedTransactionId: $original->related_transaction_id,
            createdBy: $createdBy,
            metadata: ['reverses_ledger_transaction_id' => $original->id, 'reason' => $reason],
        );
    }
}
