<?php

namespace App\Domain\Payments;

use App\Domain\Accounting\TransactionStateMachine;
use App\Domain\Payments\Exceptions\ProviderUnavailableException;
use App\Domain\Payments\Exceptions\UnsupportedCountryException;
use App\Domain\Payments\Exceptions\UnsupportedCurrencyException;
use App\Domain\Payments\Providers\InternalLedgerProvider;
use App\Domain\Payments\ValueObjects\PaymentRequest;
use App\Domain\Payments\ValueObjects\PaymentResult;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * PaymentOrchestrator — the single entry point for every payment operation.
 *
 * Responsibilities:
 *   1. Idempotency: one idempotency key ⇒ exactly one Transaction + one
 *      ledger posting, even under concurrent duplicate submissions.
 *   2. State machine: INITIATED → PROCESSING → AUTHORIZED → POSTED → SETTLED
 *      (FAILED on provider errors), every transition audited in
 *      transaction_states.
 *   3. Provider routing: country/currency/rail → provider; provider health is
 *      always checked (never assume availability).
 *   4. Attempts: every provider call is recorded in transaction_attempts.
 *   5. Money movement: ONLY through the provider → LedgerService. The legacy
 *      `transactions` row is the OPERATIONAL record the UI reads; the ledger
 *      is the immutable source of truth.
 */
class PaymentOrchestrator
{
    /** @var array<string, PaymentProviderInterface> */
    private array $providers = [];

    public function __construct(
        private readonly TransactionStateMachine $stateMachine,
        private readonly InternalLedgerProvider $internalProvider,
    ) {
        $this->providers['ledger'] = $internalProvider;
    }

    /**
     * Register an additional provider (additive — external adapters land here).
     */
    public function registerProvider(PaymentProviderInterface $provider): void
    {
        $this->providers[$provider->code()] = $provider;
    }

    // ── Public operations ──────────────────────────────────────────────────

    public function deposit(
        int $customerId,
        string $amount,
        string $currency,
        string $countryIso2,
        string $idempotencyKey,
        ?string $description = null,
        ?string $rail = null,
    ): Transaction {
        return $this->orchestrate(
            PaymentRequest::class,
            type: 'deposit',
            amount: $amount,
            currency: $currency,
            countryIso2: $countryIso2,
            rail: $rail ?? $this->defaultRail($countryIso2, $currency),
            senderId: $customerId,
            receiverId: $customerId,
            description: $description,
            idempotencyKey: $idempotencyKey,
        );
    }

    public function withdraw(
        int $customerId,
        string $amount,
        string $currency,
        string $countryIso2,
        string $idempotencyKey,
        ?string $description = null,
        ?string $rail = null,
    ): Transaction {
        return $this->orchestrate(
            PaymentRequest::class,
            type: 'withdraw',
            amount: $amount,
            currency: $currency,
            countryIso2: $countryIso2,
            rail: $rail ?? $this->defaultRail($countryIso2, $currency),
            senderId: $customerId,
            description: $description,
            idempotencyKey: $idempotencyKey,
        );
    }

    public function transfer(
        int $senderId,
        int $receiverId,
        string $amount,
        string $currency,
        string $countryIso2,
        string $idempotencyKey,
        ?string $description = null,
        ?string $rail = null,
        array $meta = [],
    ): Transaction {
        return $this->orchestrate(
            PaymentRequest::class,
            type: 'transfer',
            amount: $amount,
            currency: $currency,
            countryIso2: $countryIso2,
            rail: $rail ?? $this->defaultRail($countryIso2, $currency),
            senderId: $senderId,
            receiverId: $receiverId,
            description: $description,
            idempotencyKey: $idempotencyKey,
            meta: $meta,
        );
    }

    /**
     * Currency exchange (Stage 3, customer app): same customer's money moves
     * from a source wallet to a destination wallet in a different currency at
     * the quote's authoritative rate. Idempotent like every other movement.
     *
     * @param  array{exchange_fee?: string, exchange_rate?: string, exchange_quote_id?: int|string}  $meta
     */
    public function exchange(
        int $customerId,
        string $sourceAmount,
        string $sourceCurrency,
        string $destinationCurrency,
        string $destinationAmount,
        string $countryIso2,
        string $idempotencyKey,
        array $meta = [],
        ?string $description = null,
    ): Transaction {
        return $this->orchestrate(
            PaymentRequest::class,
            type: 'exchange',
            amount: $sourceAmount,
            currency: $sourceCurrency,
            countryIso2: $countryIso2,
            rail: $this->defaultRail($countryIso2, $sourceCurrency),
            senderId: $customerId,
            receiverId: $customerId,
            description: $description,
            idempotencyKey: $idempotencyKey,
            meta: $meta,
            destinationCurrency: $destinationCurrency,
            destinationAmount: $destinationAmount,
        );
    }

    // ── Webhook settlement (providers that confirm asynchronously) ────────

    /**
     * Apply a provider-confirmed outcome to a transaction.
     * Deduplication happens upstream in WebhookService; here we only move the
     * state machine forward and record the attempt.
     */
    public function settle(Transaction $transaction, string $providerCode, PaymentResult $result): Transaction
    {
        if ($result->success) {
            // From AUTHORIZED → POSTED → SETTLED (or POSTED → SETTLED).
            $current = strtoupper((string) $transaction->status);
            if ($current === TransactionStateMachine::AUTHORIZED) {
                $transaction = $this->stateMachine->transition(
                    $transaction,
                    TransactionStateMachine::POSTED,
                    reason: 'Webhook confirmed authorization; posting ledger movement.',
                    context: ['provider' => $providerCode],
                );
            }

            return $this->stateMachine->transition(
                $transaction,
                TransactionStateMachine::SETTLED,
                reason: 'Provider confirmed: '.($result->message ?? 'success'),
                context: ['provider' => $providerCode, 'provider_reference' => $result->providerReference],
            );
        }

        return $this->stateMachine->transition(
            $transaction,
            TransactionStateMachine::FAILED,
            reason: 'Provider reported failure: '.($result->message ?? 'unknown'),
            context: ['provider' => $providerCode, 'provider_reference' => $result->providerReference],
        );
    }

    // ── Core orchestration ─────────────────────────────────────────────────

    private function orchestrate(string $requestClass, ...$args): Transaction
    {
        /** @var PaymentRequest $request */
        $request = new $requestClass(...$args);

        return DB::transaction(function () use ($request) {
            $transaction = $this->findOrCreateTransaction($request);

            // A transaction already at a terminal/completed state is the
            // idempotent replay — return it unchanged.
            if (in_array(strtoupper((string) $transaction->status), [
                TransactionStateMachine::POSTED,
                TransactionStateMachine::SETTLED,
                TransactionStateMachine::FAILED,
                TransactionStateMachine::REVERSED,
            ], true)) {
                return $transaction;
            }

            $provider = $this->resolveProvider($request);

            $tx = $this->stateMachine->transition(
                $transaction,
                TransactionStateMachine::PROCESSING,
                reason: 'Handing to provider ['.$provider->code().'].',
                context: ['provider' => $provider->code(), 'rail' => $request->rail],
            );

            $attemptNumber = $this->recordAttempt($transaction, $provider->code(), 'pending');

            $result = $provider->execute($request);

            if ($result->success) {
                $this->recordAttempt($transaction, $provider->code(), 'success', $result, $attemptNumber);
                $transaction->forceFill([
                    'provider' => $provider->code(),
                    'rail' => $request->rail,
                    'provider_reference' => $result->providerReference,
                ])->save();

                // Walk the FULL mandated chain: AUTHORIZED → POSTED → SETTLED.
                $tx = $this->stateMachine->transition(
                    $tx,
                    TransactionStateMachine::AUTHORIZED,
                    reason: 'Provider authorized the movement.',
                    context: ['provider' => $provider->code(), 'provider_reference' => $result->providerReference],
                );
                $tx = $this->stateMachine->transition(
                    $tx,
                    TransactionStateMachine::POSTED,
                    reason: 'Ledger movement posted.',
                    context: ['provider' => $provider->code()],
                );

                return $this->stateMachine->transition(
                    $tx,
                    TransactionStateMachine::SETTLED,
                    reason: 'Provider confirmed settlement.',
                    context: ['provider' => $provider->code(), 'provider_reference' => $result->providerReference],
                );
            }

            $this->recordAttempt($tx, $provider->code(), 'failed', $result, $attemptNumber);
            $tx->forceFill(['error_reason' => $result->message])->save();

            return $this->stateMachine->transition(
                $tx,
                TransactionStateMachine::FAILED,
                reason: $result->message ?? 'Provider failed without a message.',
                context: ['provider' => $provider->code()],
            );
        });
    }

    private function findOrCreateTransaction(PaymentRequest $request): Transaction
    {
        $existing = Transaction::query()
            ->where('idempotency_key', $request->idempotencyKey)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        try {
            $destinationAmount = $request->destinationAmount ?? $request->amount;

            $transaction = Transaction::create([
                'sender_id' => $request->senderId,
                'receiver_id' => $request->receiverId,
                'receiver_name' => $request->receiverId !== null
                    ? optional(\App\Models\User::find($request->receiverId))->name
                    : null,
                'type' => $request->type,
                'source_currency' => $request->currency,
                'destination_currency' => $request->destinationCurrency ?? $request->currency,
                'source_amount' => $request->amount,
                'destination_amount' => $destinationAmount,
                'exchange_rate' => (string) ($request->meta['exchange_rate'] ?? '1.0000'),
                'fee_charged' => (string) ($request->meta['exchange_fee'] ?? $request->meta['transfer_fee'] ?? '0.00'),
                'status' => 'initiated',
                'description' => $request->description,
                'provider' => null,
                'rail' => $request->rail,
                'country_code' => $this->iso2ToIso3($request->countryIso2),
                'idempotency_key' => $request->idempotencyKey,
                'reference' => 'KP-'.strtoupper(Str::random(12)),
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Concurrent duplicate with the same key won the race — adopt its row.
            $winner = Transaction::query()
                ->where('idempotency_key', $request->idempotencyKey)
                ->first();

            if ($winner !== null) {
                return $winner;
            }

            throw $e;
        }

        $this->stateMachine->recordGenesis(
            $transaction,
            reason: 'Payment initiated.',
            context: ['type' => $request->type, 'amount' => $request->amount, 'currency' => $request->currency],
        );

        return $transaction;
    }

    private function resolveProvider(PaymentRequest $request): PaymentProviderInterface
    {
        $available = [];
        foreach ($this->providers as $provider) {
            if (! in_array($request->countryIso2, $provider->supportedCountries(), true)) {
                continue;
            }
            if (! in_array($request->currency, $provider->supportedCurrencies(), true)) {
                throw new UnsupportedCurrencyException(
                    "Provider [{$provider->code()}] does not support [{$request->currency}]."
                );
            }
            if (! in_array($request->rail, $provider->supportedRails(), true)) {
                continue;
            }
            if (! $provider->isAvailable()) {
                continue;
            }
            $available[] = $provider;
        }

        if ($available === []) {
            throw new ProviderUnavailableException(
                "No available provider for {$request->countryIso2} / {$request->currency} / {$request->rail}."
            );
        }

        return $available[0];
    }

    private function recordAttempt(
        Transaction $transaction,
        string $provider,
        string $status,
        ?PaymentResult $result = null,
        ?int $attemptNumber = null,
    ): int {
        $attemptNumber ??= (int) DB::table('transaction_attempts')
            ->where('transaction_id', $transaction->id)
            ->max('attempt_number') + 1;

        DB::table('transaction_attempts')->insert([
            'transaction_id' => $transaction->id,
            'attempt_number' => $attemptNumber,
            'provider' => $provider,
            'provider_reference' => $result?->providerReference,
            'amount' => $transaction->source_amount,
            'status' => $status,
            'response_summary' => $result?->message,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $attemptNumber;
    }

    private function defaultRail(string $countryIso2, string $currency): string
    {
        return $countryIso2 === 'NE' ? 'WALLET_NE' : 'WALLET_NG';
    }

    private function iso2ToIso3(string $iso2): string
    {
        return match (strtoupper($iso2)) {
            'NG' => 'NGA',
            'NE' => 'NER',
            default => $iso2,
        };
    }
}
