<?php

namespace App\Domain\Customer;

use App\Domain\Customer\Exceptions\ExchangePairUnavailableException;
use App\Domain\Customer\Exceptions\ExchangeQuoteExpiredException;
use App\Models\Currency;
use App\Models\CustomerWallet;
use App\Models\CustomerWalletConfig;
use App\Models\ExchangeQuote;
use App\Models\FxRate;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * CUSTOMER BANKING — Stage 1.
 *
 * ExchangeQuoteService — server-authoritative, expiring quotes (§39, §41,
 * §91). The rate comes ONLY from fx_rates; the frontend never computes it.
 * The quote is bound to customer + source/destination wallets + currency
 * pair + amount + expiry, and revalidated on execute (Stage 3).
 */
class ExchangeQuoteService
{
    public const QUOTE_TTL_SECONDS = 60;

    /**
     * Create an authoritative quote for source→destination.
     *
     * @throws ExchangePairUnavailableException
     */
    public function createQuote(User $user, CustomerWallet $from, CustomerWallet $to, string $sourceAmount): ExchangeQuote
    {
        $this->guardOwnership($user, $from, $to);
        $this->guardActive($from, $to);
        $this->guardPair($user, $from, $to);
        $this->guardAmountFormat($sourceAmount, $from->currency_code);
        $this->guardLimit($user, $from->currency_code, $sourceAmount);

        $rate = $this->authoritativeRate($from->currency_code, $to->currency_code);
        $fee = $this->feeFor($user, $from->currency_code, $sourceAmount);
        $destination = bcmul($sourceAmount, (string) $rate, 2);
        $totalDebit = bcadd($sourceAmount, $fee, 2);

        return ExchangeQuote::create([
            'quote_id' => 'Q-'.strtoupper(Str::random(12)),
            'user_id' => $user->id,
            'from_wallet_id' => $from->id,
            'to_wallet_id' => $to->id,
            'from_currency' => $from->currency_code,
            'to_currency' => $to->currency_code,
            'source_amount' => $sourceAmount,
            'destination_amount' => $destination,
            'exchange_rate' => (string) $rate,
            'exchange_fee' => $fee,
            'total_debit' => $totalDebit,
            'status' => ExchangeQuote::STATUS_CREATED,
            'expires_at' => now()->addSeconds(self::QUOTE_TTL_SECONDS),
        ]);
    }

    /**
     * Authoritative rate for a pair (base→target). Fails loudly rather than
     * fabricating — no active rate means the service is unavailable.
     */
    public function authoritativeRate(string $from, string $to): string
    {
        if ($from === $to) {
            return '1.000000';
        }

        $rate = FxRate::query()
            ->where('base_currency', $from)
            ->where('target_currency', $to)
            ->where('is_active', true)
            ->first();

        if ($rate === null) {
            throw new ExchangePairUnavailableException(
                "Currency exchange between [{$from}] and [{$to}] is temporarily unavailable."
            );
        }

        return (string) $rate->rate;
    }

    /**
     * Mark a quote expired (called when a customer abandons it or a timer
     * sweeps it).
     */
    public function expire(ExchangeQuote $quote): ExchangeQuote
    {
        if ($quote->status === ExchangeQuote::STATUS_CREATED) {
            $quote->update(['status' => ExchangeQuote::STATUS_EXPIRED]);
        }

        return $quote->fresh();
    }

    /**
     * Revalidate a quote at execution time (Stage 3 calls this before any
     * ledger movement). Throws on expired/used/cancelled or foreign quotes.
     */
    public function revalidate(User $user, ExchangeQuote $quote): ExchangeQuote
    {
        if ((int) $quote->user_id !== (int) $user->id) {
            throw new \DomainException('Quote does not belong to this customer.');
        }

        if ($quote->status !== ExchangeQuote::STATUS_CREATED) {
            throw new ExchangeQuoteExpiredException('This exchange quote is no longer valid.');
        }

        if ($quote->isExpired()) {
            $this->expire($quote);
            throw new ExchangeQuoteExpiredException('The exchange rate has expired. Get a new quote to continue.');
        }

        return $quote;
    }

    public function markUsed(ExchangeQuote $quote): ExchangeQuote
    {
        $quote->update(['status' => ExchangeQuote::STATUS_USED]);

        return $quote->fresh();
    }

    /**
     * EXECUTE a quote (Stage 3) — the only path that moves money on a quote.
     *
     * Serialized: the quote row is locked before revalidation so two
     * concurrent executes cannot both pass. Re-runs every guard (ownership,
     * status, expiry, pair/KYC availability, daily limit, balance), then
     * moves money through PaymentOrchestrator (Phase 5 state machine +
     * idempotency) and marks the quote USED atomically with the posting.
     *
     * @return \App\Models\Transaction (SETTLED | FAILED)
     *
     * @throws \App\Domain\Customer\Exceptions\ExchangeQuoteExpiredException
     * @throws \App\Domain\Customer\Exceptions\ExchangePairUnavailableException
     * @throws \App\Domain\Customer\Exceptions\CustomerBankingException
     * @throws \DomainException
     */
    public function execute(User $user, ExchangeQuote $quote, string $idempotencyKey): \App\Models\Transaction
    {
        try {
            return $this->executeInTransaction($user, $quote, $idempotencyKey);
        } catch (ExchangeQuoteExpiredException $e) {
            // The serialized transaction rolled back — persist the terminal
            // state outside it so the customer sees the quote as expired.
            $fresh = ExchangeQuote::query()->find($quote->id);
            if ($fresh !== null && $fresh->status === ExchangeQuote::STATUS_CREATED && $fresh->isExpired()) {
                $this->expire($fresh);
            }

            throw $e;
        }
    }

    protected function executeInTransaction(User $user, ExchangeQuote $quote, string $idempotencyKey): \App\Models\Transaction
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($user, $quote, $idempotencyKey) {
            // Idempotent replay wins over revalidation: a key that already
            // produced a transaction returns that transaction unchanged, even
            // if the quote has since been consumed.
            $existing = \App\Models\Transaction::query()
                ->where('idempotency_key', $idempotencyKey)
                ->where('sender_id', $user->id)
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            // Lock the quote row — serializes concurrent executes of one quote.
            $locked = ExchangeQuote::query()->whereKey($quote->id)->lockForUpdate()->firstOrFail();

            $this->revalidate($user, $locked);

            $from = CustomerWallet::query()->findOrFail($locked->from_wallet_id);
            $to = CustomerWallet::query()->findOrFail($locked->to_wallet_id);

            // Re-run the live guards — config/limits/rates may have changed.
            $this->guardActive($from, $to);
            $this->guardPair($user, $from, $to);
            $this->guardLimit($user, $locked->from_currency, (string) $locked->source_amount);

            $available = (string) ($from->ledgerAccount?->balance ?? '0');
            if (bccomp($available, (string) $locked->total_debit, 2) < 0) {
                throw new \App\Domain\Customer\Exceptions\CustomerBankingException(
                    'Insufficient balance for this exchange including the fee.'
                );
            }

            $iso2 = app(CustomerWalletService::class)->iso2For($user);
            $normalize = fn (string $a) => app(CustomerWalletService::class)->normalizeDecimal($a, $locked->from_currency);

            $transaction = app(\App\Domain\Payments\PaymentOrchestrator::class)->exchange(
                customerId: $user->id,
                sourceAmount: $normalize((string) $locked->source_amount),
                sourceCurrency: $locked->from_currency,
                destinationCurrency: $locked->to_currency,
                destinationAmount: $normalize((string) $locked->destination_amount),
                countryIso2: $iso2,
                idempotencyKey: $idempotencyKey,
                meta: [
                    'exchange_fee' => $normalize((string) $locked->exchange_fee),
                    'exchange_rate' => (string) $locked->exchange_rate,
                    'exchange_quote_id' => $locked->id,
                ],
                description: 'Currency exchange '.$locked->from_currency.' → '.$locked->to_currency,
            );

            // Only a settled execution consumes the quote — failed attempts
            // (insufficient funds surfaced by the ledger, provider errors)
            // leave the quote usable until it naturally expires.
            if (strtoupper((string) $transaction->status) === \App\Domain\Accounting\TransactionStateMachine::SETTLED) {
                $this->markUsed($locked);
            }

            return $transaction;
        });
    }

    /**
     * True when this customer has a live (unexpired) quote for the pair.
     */
    public function hasLiveQuote(User $user, string $from, string $to): bool
    {
        return ExchangeQuote::query()
            ->where('user_id', $user->id)
            ->where('from_currency', $from)
            ->where('to_currency', $to)
            ->where('status', ExchangeQuote::STATUS_CREATED)
            ->where('expires_at', '>', now())
            ->exists();
    }

    // ── Guards ───────────────────────────────────────────────────────────

    protected function guardOwnership(User $user, CustomerWallet $from, CustomerWallet $to): void
    {
        foreach ([$from, $to] as $wallet) {
            if ((int) $wallet->user_id !== (int) $user->id) {
                throw new \DomainException('Wallet does not belong to this customer.');
            }
        }
    }

    protected function guardActive(CustomerWallet $from, CustomerWallet $to): void
    {
        foreach ([$from, $to] as $wallet) {
            if ($wallet->status !== CustomerWallet::STATUS_ACTIVE) {
                throw new \DomainException("Wallet [{$wallet->display_name}] is not available for exchange.");
            }
        }
    }

    /**
     * Pair eligibility = both wallets are provisioned for the customer's
     * country config AND the customer's KYC tier permits them (§75).
     */
    protected function guardPair(User $user, CustomerWallet $from, CustomerWallet $to): void
    {
        $iso2 = app(CustomerWalletService::class)->iso2For($user);

        $fromConfig = CustomerWalletConfig::query()
            ->where('country_iso2', $iso2)->where('currency_code', $from->currency_code)
            ->where('is_available', true)->first();
        $toConfig = CustomerWalletConfig::query()
            ->where('country_iso2', $iso2)->where('currency_code', $to->currency_code)
            ->where('is_available', true)->first();

        if ($fromConfig === null || $toConfig === null) {
            throw new ExchangePairUnavailableException(
                "Currency exchange between [{$from->currency_code}] and [{$to->currency_code}] is not available for your account."
            );
        }

        foreach ([$fromConfig, $toConfig] as $config) {
            if ((int) $user->kyc_tier < (int) $config->min_kyc_tier) {
                throw new ExchangePairUnavailableException(
                    'Additional verification is required before you can exchange currencies.'
                );
            }
        }
    }

    protected function guardAmountFormat(string $amount, string $currency): void
    {
        $minorUnits = (int) (Currency::where('code', $currency)->value('minor_units') ?? 2);
        // Zero-minor-currency (e.g. XOF): whole units only. Otherwise accept
        // up to `minorUnits` decimals. Never build `{1,0}` — that regex is
        // invalid.
        $pattern = $minorUnits === 0
            ? '/^-?\d+$/'
            : '/^-?\d+(\.\d{1,'.$minorUnits.'})?$/';

        if (! preg_match($pattern, $amount) || bccomp($amount, '0') <= 0) {
            throw new \InvalidArgumentException("Invalid amount for {$currency}: {$amount}");
        }
    }

    /**
     * Exchange limits — data-driven per country+currency config; usage = real
     * quotes created today (created|used) in that currency.
     */
    protected function guardLimit(User $user, string $currency, string $sourceAmount): void
    {
        $iso2 = app(CustomerWalletService::class)->iso2For($user);
        $config = CustomerWalletConfig::query()
            ->where('country_iso2', $iso2)->where('currency_code', $currency)->first();

        $limit = $config?->daily_exchange_limit;
        if ($limit === null) {
            return; // no limit configured
        }

        $used = (string) ExchangeQuote::query()
            ->where('user_id', $user->id)
            ->where('from_currency', $currency)
            ->whereIn('status', [ExchangeQuote::STATUS_CREATED, ExchangeQuote::STATUS_USED])
            ->where('created_at', '>=', now()->startOfDay())
            ->sum('total_debit');

        if (bccomp(bcadd($used, $sourceAmount, 2), (string) $limit, 2) > 0) {
            throw new \DomainException(
                'This exchange exceeds your remaining daily exchange limit.'
            );
        }
    }

    /**
     * Fee = flat + rate% of source, in source currency — data-driven config.
     */
    protected function feeFor(User $user, string $currency, string $sourceAmount): string
    {
        $iso2 = app(CustomerWalletService::class)->iso2For($user);
        $config = CustomerWalletConfig::query()
            ->where('country_iso2', $iso2)->where('currency_code', $currency)->first();

        $flat = (string) ($config?->exchange_fee_flat ?? '0');
        $rate = (string) ($config?->exchange_fee_rate ?? '0');

        $fee = bcadd($flat, bcdiv(bcmul($sourceAmount, $rate, 4), '100', 2), 2);

        return $fee;
    }
}
