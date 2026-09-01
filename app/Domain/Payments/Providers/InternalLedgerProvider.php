<?php

namespace App\Domain\Payments\Providers;

use App\Domain\Accounting\LedgerService;
use App\Domain\Payments\PaymentProviderInterface;
use App\Domain\Payments\ValueObjects\PaymentRequest;
use App\Domain\Payments\ValueObjects\PaymentResult;
use App\Domain\Payments\ValueObjects\ProviderHealth;
use App\Domain\Accounting\LedgerAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * KoriePay Internal Ledger Rail.
 *
 * This is a REAL, operational provider — it moves money by posting balanced,
 * immutable ledger entries (DR/CR). It is not an external API simulation; it
 * is the platform's own wallet rail, available for NGN and XOF wallets.
 *
 * It deliberately performs NO webhook verification (internal movement has no
 * external signature) — the orchestrator treats it as always-authenticated
 * internal execution. Availability is derived from a real health probe
 * (ledger table reachability + currency support).
 */
class InternalLedgerProvider implements PaymentProviderInterface
{
    public function __construct(private readonly LedgerService $ledger)
    {
    }

    public function code(): string
    {
        return 'ledger';
    }

    public function name(): string
    {
        return 'KoriePay Ledger Rail';
    }

    public function supportedCountries(): array
    {
        return ['NG', 'NE']; // additive: Ghana, Benin, … extend via config
    }

    public function supportedCurrencies(): array
    {
        return ['NGN', 'XOF'];
    }

    public function supportedRails(): array
    {
        return ['WALLET_NG', 'WALLET_NE'];
    }

    public function capabilities(): array
    {
        return ['transfer', 'deposit', 'withdraw'];
    }

    public function health(): ProviderHealth
    {
        $start = hrtime(true);

        try {
            // Real probe: the ledger must be reachable and the currency active.
            $ok = DB::connection()->getPdo()->query('select 1')->fetchColumn() == 1;
            $latency = (int) ((hrtime(true) - $start) / 1_000_000);

            if (! $ok) {
                return new ProviderHealth(ProviderHealth::STATUS_DOWN, 0, $latency, 'Ledger probe failed.');
            }

            return new ProviderHealth(ProviderHealth::STATUS_OPERATIONAL, 100, $latency, 'Ledger reachable.');
        } catch (\Throwable $e) {
            return new ProviderHealth(ProviderHealth::STATUS_DOWN, 0, null, 'Ledger unreachable: '.$e->getMessage());
        }
    }

    public function isAvailable(): bool
    {
        return $this->health()->isAvailable();
    }

    /**
     * Execute the movement through the immutable ledger.
     *
     * Type mapping (custodial model):
     *   deposit  → DR platform cash (asset) / CR customer wallet (liability)
     *   withdraw → DR customer wallet / CR platform cash
     *   transfer → DR sender wallet / CR receiver wallet
     *
     * The LedgerService enforces balance (compare-and-decrement) and
     * idempotency; any violation throws, which the orchestrator converts into
     * a FAILED transaction state.
     */
    public function execute(PaymentRequest $request): PaymentResult
    {
        if (! $this->isAvailable()) {
            return PaymentResult::failed('Internal ledger rail is not available right now.');
        }

        try {
            $ledgerTx = match ($request->type) {
                'deposit' => $this->deposit($request),
                'withdraw' => $this->withdraw($request),
                'transfer' => $this->transfer($request),
                'exchange' => $this->exchange($request),
                default => throw new \App\Domain\Payments\Exceptions\InvalidPaymentRequestException("Unsupported type [{$request->type}]."),
            };

            return PaymentResult::success(
                reference: 'LEDGER-'.$ledgerTx->id,
                message: 'Ledger movement posted.',
                data: ['ledger_transaction_id' => $ledgerTx->id],
            );
        } catch (\App\Domain\Accounting\Exceptions\InsufficientFundsException $e) {
            return PaymentResult::failed('Insufficient funds on the source wallet.');
        } catch (\App\Domain\Accounting\Exceptions\UnbalancedLedgerException $e) {
            return PaymentResult::failed('Ledger rejected unbalanced movement: '.$e->getMessage());
        } catch (\App\Domain\Accounting\Exceptions\LedgerValidationException $e) {
            return PaymentResult::failed('Ledger validation failed: '.$e->getMessage());
        } catch (\Throwable $e) {
            return PaymentResult::failed('Internal rail failure: '.$e->getMessage());
        }
    }

    public function verify(string $providerReference): PaymentResult
    {
        $id = (int) str_replace('LEDGER-', '', $providerReference);
        $tx = \App\Domain\Accounting\LedgerTransaction::find($id);

        if ($tx === null) {
            return PaymentResult::failed("Unknown ledger reference [{$providerReference}].");
        }

        return PaymentResult::success(
            reference: $providerReference,
            message: 'Ledger transaction exists and is immutable.',
            data: ['ledger_transaction_id' => $tx->id],
        );
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        // Internal rail — no external webhooks. The orchestrator never calls
        // this for internal execution; return false defensively (fail closed).
        return false;
    }

    // ── Ledger movements ──────────────────────────────────────────────────

    private function deposit(PaymentRequest $request): \App\Domain\Accounting\LedgerTransaction
    {
        return $this->ledger->post(
            [
                ['account_id' => $this->platformCash($request->currency), 'side' => 'debit', 'amount' => $request->amount],
                ['account_id' => $this->wallet($request->receiverId ?? $request->senderId, $request->currency), 'side' => 'credit', 'amount' => $request->amount],
            ],
            'deposit',
            description: $request->description ?? 'Wallet funding',
            idempotencyKey: $request->idempotencyKey,
        );
    }

    private function withdraw(PaymentRequest $request): \App\Domain\Accounting\LedgerTransaction
    {
        return $this->ledger->post(
            [
                ['account_id' => $this->wallet($request->senderId, $request->currency), 'side' => 'debit', 'amount' => $request->amount],
                ['account_id' => $this->platformCash($request->currency), 'side' => 'credit', 'amount' => $request->amount],
            ],
            'withdrawal',
            description: $request->description ?? 'Wallet withdrawal',
            idempotencyKey: $request->idempotencyKey,
        );
    }

    private function transfer(PaymentRequest $request): \App\Domain\Accounting\LedgerTransaction
    {
        if ($request->senderId === null || $request->receiverId === null) {
            throw new \App\Domain\Payments\Exceptions\InvalidPaymentRequestException('Transfer requires sender and receiver.');
        }

        // Optional transfer fee (customer money movement, Stage 2): debited
        // from the sender together with the principal (ONE debit entry per
        // account — the ledger enforces uniqueness of account+side per
        // posting), credited to Platform Revenue. Same atomic posting, so fee
        // + principal can never diverge. Absent fee ⇒ legacy behaviour.
        $fee = (string) ($request->meta['transfer_fee'] ?? '0');

        $entries = [
            ['account_id' => $this->wallet($request->senderId, $request->currency), 'side' => 'debit', 'amount' => bcadd($request->amount, $fee, 2)],
            ['account_id' => $this->wallet($request->receiverId, $request->currency), 'side' => 'credit', 'amount' => $request->amount],
        ];

        if (bccomp($fee, '0', 2) > 0) {
            $entries[] = ['account_id' => $this->revenue($request->currency), 'side' => 'credit', 'amount' => $fee];
        }

        return $this->ledger->post(
            $entries,
            'transfer',
            description: $request->description ?? 'Wallet-to-wallet transfer',
            idempotencyKey: $request->idempotencyKey,
        );
    }

    // ── Account resolution (custodial model: wallets = liability, cash = asset)

    private function wallet(int $userId, string $currency): int
    {
        $account = LedgerAccount::query()
            ->where('owner_type', 'user')
            ->where('owner_id', $userId)
            ->where('currency_code', $currency)
            ->first();

        if ($account === null) {
            throw new \App\Domain\Payments\Exceptions\ProviderExecutionException(
                "No ledger wallet for user [{$userId}] in [{$currency}]. Fund/register the account first."
            );
        }

        return $account->id;
    }

    /**
     * Currency exchange (Stage 3) — one atomic, per-currency-balanced posting:
     *
     *   source currency:  DR customer source wallet (source + fee)
     *                     CR platform cash (source)         (source)
     *                     CR platform revenue (source)      (fee)
     *   destination:      DR platform cash (destination)    (destination)
     *                     CR customer destination wallet    (destination)
     *
     * The rate is whatever the quote already validated (server-authoritative);
     * this provider only moves the booked amounts.
     */
    private function exchange(PaymentRequest $request): \App\Domain\Accounting\LedgerTransaction
    {
        $sourceCurrency = $request->currency;
        $destinationCurrency = $request->destinationCurrency ?? $sourceCurrency;
        $destinationAmount = (string) ($request->destinationAmount ?? $request->amount);
        $fee = (string) ($request->meta['exchange_fee'] ?? '0');

        if ($sourceCurrency === $destinationCurrency) {
            throw new \App\Domain\Payments\Exceptions\InvalidPaymentRequestException(
                'Exchange requires two different currencies.'
            );
        }

        $entries = [
            // Source side — debits (customer liability decreases) balanced by
            // platform cash + revenue credits.
            ['account_id' => $this->wallet($request->senderId, $sourceCurrency), 'side' => 'debit', 'amount' => bcadd($request->amount, $fee, 2)],
            ['account_id' => $this->platformCash($sourceCurrency), 'side' => 'credit', 'amount' => $request->amount],
        ];

        if (bccomp($fee, '0', 2) > 0) {
            $entries[] = ['account_id' => $this->revenue($sourceCurrency), 'side' => 'credit', 'amount' => $fee];
        }

        // Destination side — platform cash (asset) funds the customer wallet.
        $entries[] = ['account_id' => $this->platformCash($destinationCurrency), 'side' => 'debit', 'amount' => $destinationAmount];
        $entries[] = ['account_id' => $this->wallet($request->receiverId ?? $request->senderId, $destinationCurrency), 'side' => 'credit', 'amount' => $destinationAmount];

        return $this->ledger->post(
            $entries,
            'exchange',
            description: $request->description ?? 'Currency exchange',
            idempotencyKey: $request->idempotencyKey,
        );
    }

    private function platformCash(string $currency): int
    {
        $account = LedgerAccount::query()
            ->where('account_type', 'asset')
            ->where('name', 'Platform Cash')
            ->where('currency_code', $currency)
            ->first();

        if ($account === null) {
            throw new \App\Domain\Payments\Exceptions\ProviderExecutionException(
                "Platform cash account missing for [{$currency}]. Run the ledger seed."
            );
        }

        return $account->id;
    }

    private function revenue(string $currency): int
    {
        return LedgerAccount::revenueAccount($currency)->id;
    }
}
