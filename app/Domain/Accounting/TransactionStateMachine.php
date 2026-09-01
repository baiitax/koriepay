<?php

namespace App\Domain\Accounting;

use App\Domain\Accounting\Exceptions\IllegalStateTransitionException;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

/**
 * Explicit transaction state machine (§11 of the rebuild brief).
 *
 * States: INITIATED → PROCESSING → AUTHORIZED → POSTED → SETTLED
 * with FAILED / REVERSED / REFUNDED / HELD / CANCELLED / EXPIRED branches.
 *
 * Every transition is persisted to transaction_states (auditable, replayable).
 * Once a transaction reaches a terminal state it can only move to a reversal
 * family state via ReversalService.
 */
class TransactionStateMachine
{
    public const INITIATED = 'INITIATED';
    public const PENDING = 'PENDING';
    public const PROCESSING = 'PROCESSING';
    public const AUTHORIZED = 'AUTHORIZED';
    public const POSTED = 'POSTED';
    public const SETTLED = 'SETTLED';
    public const FAILED = 'FAILED';
    public const REVERSED = 'REVERSED';
    public const REFUNDED = 'REFUNDED';
    public const HELD = 'HELD';
    public const CANCELLED = 'CANCELLED';
    public const EXPIRED = 'EXPIRED';

    public const TERMINAL = [
        self::FAILED,
        self::REVERSED,
        self::REFUNDED,
        self::CANCELLED,
        self::EXPIRED,
    ];

    private const ALLOWED = [
        self::INITIATED => [self::PROCESSING, self::CANCELLED, self::EXPIRED, self::FAILED],
        self::PENDING => [self::PROCESSING, self::FAILED, self::CANCELLED],
        self::PROCESSING => [self::AUTHORIZED, self::FAILED, self::HELD],
        self::AUTHORIZED => [self::POSTED, self::FAILED, self::HELD],
        self::POSTED => [self::SETTLED, self::REVERSED, self::HELD],
        self::SETTLED => [self::REVERSED, self::REFUNDED],
        self::HELD => [self::POSTED, self::CANCELLED],
        self::FAILED => [],
        self::REVERSED => [],
        self::REFUNDED => [],
        self::CANCELLED => [],
        self::EXPIRED => [],
    ];

    /**
     * Move a transaction to $toState, recording an auditable transition.
     * The transactions.status column stores the current state (legacy UI
     * compatibility reads lowercase statuses — we map on write).
     */
    public function transition(
        Transaction $transaction,
        string $toState,
        ?string $reason = null,
        ?int $actorId = null,
        array $context = [],
    ): Transaction {
        $toState = strtoupper($toState);
        $fromState = strtoupper((string) ($transaction->status ?: self::INITIATED));

        if ($toState === $fromState) {
            return $transaction;
        }

        if (! in_array($toState, self::ALLOWED[$fromState] ?? [], true)) {
            throw new IllegalStateTransitionException(
                "Illegal transition {$fromState} → {$toState} for transaction {$transaction->reference}"
            );
        }

        return DB::transaction(function () use ($transaction, $toState, $fromState, $reason, $actorId, $context) {
            // Serialize on the row to avoid double transitions.
            $locked = Transaction::whereKey($transaction->getKey())->lockForUpdate()->firstOrFail();
            $current = strtoupper((string) $locked->status ?: self::INITIATED);

            if ($current !== $fromState) {
                throw new IllegalStateTransitionException(
                    "Stale transition: {$current} !== {$fromState} for {$locked->reference}"
                );
            }

            $locked->update(['status' => strtolower($toState)]);

            DB::table('transaction_states')->insert([
                'transaction_id' => $locked->id,
                'from_state' => $fromState === self::INITIATED && $locked->status === null ? null : $fromState,
                'to_state' => $toState,
                'reason' => $reason,
                'actor_id' => $actorId,
                'context' => json_encode($context),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $locked->fresh();
        });
    }

    /**
     * Record the genesis (INITIATED) audit row for a freshly created
     * transaction. The first real transition (INITIATED → …) then chains off
     * it, so the full lifecycle is replayable from transaction_states alone.
     */
    public function recordGenesis(
        Transaction $transaction,
        ?string $reason = null,
        ?int $actorId = null,
        array $context = [],
    ): Transaction {
        DB::table('transaction_states')->insert([
            'transaction_id' => $transaction->id,
            'from_state' => null,
            'to_state' => self::INITIATED,
            'reason' => $reason,
            'actor_id' => $actorId,
            'context' => json_encode($context),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $transaction;
    }

    public static function canTransition(string $from, string $to): bool
    {
        return in_array(strtoupper($to), self::ALLOWED[strtoupper($from)] ?? [], true);
    }

    public static function isTerminal(string $state): bool
    {
        return in_array(strtoupper($state), self::TERMINAL, true);
    }

    /**
     * @return string[]
     */
    public static function allowedFrom(string $state): array
    {
        return self::ALLOWED[strtoupper($state)] ?? [];
    }
}
