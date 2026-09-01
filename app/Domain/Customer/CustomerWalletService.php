<?php

namespace App\Domain\Customer;

use App\Domain\Accounting\LedgerAccount;
use App\Domain\Customer\Exceptions\WalletUnavailableException;
use App\Models\Country;
use App\Models\Currency;
use App\Models\CustomerWallet;
use App\Models\CustomerWalletConfig;
use App\Models\ExchangeQuote;
use App\Models\FxRate;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * CUSTOMER BANKING — Stage 1.
 *
 * CustomerWalletService — the customer-facing wallet read model over the
 * LEDGER (brief §10, §82):
 *   - available = ledger account projection (never `wallets.balance`)
 *   - pending   = real in-flight transactions involving the user+currency
 *   - reserved  = 0 unless a hold feature exists (kept honest)
 *   - eligibility (which wallets a customer may hold) is data-driven from
 *     customer_wallet_configs (§75).
 */
class CustomerWalletService
{
    public function __construct(
        private readonly \App\Domain\Accounting\LedgerService $ledger,
    ) {
    }

    public function iso2For(User $user): string
    {
        $iso3 = strtoupper((string) $user->country_code);
        $country = Country::query()->where('iso3', $iso3)->first();

        return $country?->iso2 ?? 'NG';
    }

    /**
     * Provision the customer's eligible wallets (idempotent, country-aware).
     * Creates the underlying liability ledger account if missing, then the
     * customer_wallets read-model row.
     *
     * @return array<int, CustomerWallet>
     */
    public function provision(User $user): array
    {
        $iso2 = $this->iso2For($user);
        $configs = CustomerWalletConfig::query()
            ->where('country_iso2', $iso2)
            ->where('is_available', true)
            ->get();

        $provisioned = [];

        foreach ($configs as $config) {
            if ((int) $user->kyc_tier < (int) $config->min_kyc_tier) {
                continue;
            }

            $ledger = $this->ensureLedgerAccount($user, $config->currency_code);
            $isPrimary = (bool) $config->is_primary_default;

            $wallet = CustomerWallet::firstOrCreate(
                ['user_id' => $user->id, 'currency_code' => $config->currency_code],
                [
                    'wallet_id' => $this->generateWalletId($user, $config->currency_code),
                    'display_name' => $config->display_name ?? $config->currency_code.' Wallet',
                    'is_primary' => $isPrimary,
                    'status' => CustomerWallet::STATUS_ACTIVE,
                    'ledger_account_id' => $ledger->id,
                ]
            );

            // First creation wins for primary; keep others consistent.
            if ($isPrimary && ! $wallet->is_primary) {
                CustomerWallet::where('user_id', $user->id)->update(['is_primary' => false]);
                $wallet->update(['is_primary' => true]);
            }

            $provisioned[] = $wallet;
        }

        return $provisioned;
    }

    /**
     * All of the user's provisioned wallets, with derived balances.
     */
    public function walletsFor(User $user): array
    {
        $this->provision($user);

        return CustomerWallet::query()
            ->where('user_id', $user->id)
            ->orderByDesc('is_primary')
            ->get()
            ->map(fn (CustomerWallet $w) => $this->withBalance($w))
            ->all();
    }

    /**
     * Selected wallet (server-side session context). Defaults to primary.
     * Returns null when the customer has no eligible wallets yet (e.g. KYC
     * tier below the minimum) — callers render an honest eligibility state.
     */
    public function selectedWallet(User $user): ?CustomerWallet
    {
        $this->provision($user);

        $id = session()->get('customer.selected_wallet_id');
        $wallet = $id !== null
            ? CustomerWallet::where('user_id', $user->id)->where('id', $id)->first()
            : null;

        return $wallet ?? CustomerWallet::query()
            ->where('user_id', $user->id)
            ->orderByDesc('is_primary')
            ->first();
    }

    public function selectWallet(User $user, CustomerWallet $wallet): CustomerWallet
    {
        $this->assertOwned($user, $wallet);
        $this->assertActive($wallet);
        session()->put('customer.selected_wallet_id', $wallet->id);

        return $wallet;
    }

    /**
     * Balance details — ALL derived, never stored.
     *
     * @return array{available: string, pending: string, reserved: string, total: string, currency: string, minor_units: int}
     */
    public function balanceDetails(User $user, CustomerWallet $wallet): array
    {
        $this->assertOwned($user, $wallet);

        $available = (string) $wallet->ledgerAccount?->balance ?? '0.00';
        $pending = $this->pendingBalance($user, $wallet->currency_code);
        $reserved = '0.00';
        $total = bcadd($available, $pending, 2);

        return [
            'available' => $available,
            'pending' => $pending,
            'reserved' => $reserved,
            'total' => $total,
            'currency' => $wallet->currency_code,
            'minor_units' => Currency::where('code', $wallet->currency_code)->value('minor_units') ?? 2,
        ];
    }

    /**
     * Honest pending: sum of in-flight transactions (non-terminal states)
     * where the user is sender or receiver in this currency.
     */
    public function pendingBalance(User $user, string $currency): string
    {
        $inFlight = ['pending', 'processing', 'authorized'];

        $outgoing = (string) Transaction::query()
            ->where('sender_id', $user->id)
            ->where('source_currency', $currency)
            ->whereIn('status', $inFlight)
            ->sum('source_amount');

        $incoming = (string) Transaction::query()
            ->where('receiver_id', $user->id)
            ->where('source_currency', $currency)
            ->whereIn('status', $inFlight)
            ->sum('source_amount');

        // Outgoing pending reduces available; incoming pending increases it.
        return bcsub($incoming, $outgoing, 2);
    }

    /**
     * Portfolio summary — an ESTIMATE converted at the authoritative rate,
     * never a claim of withdrawable value (§12, §49). Defaults to the user's
     * PRIMARY wallet currency; when an authoritative rate is missing for a
     * wallet it is EXCLUDED from the total (never estimated from thin air)
     * and flagged honestly in `rate_basis`.
     */
    public function portfolioSummary(User $user, ?string $inCurrency = null): array
    {
        $this->provision($user);

        $wallets = CustomerWallet::where('user_id', $user->id)->orderByDesc('is_primary')->get();
        $inCurrency ??= $wallets->first()?->currency_code ?? 'XOF';

        $rows = [];
        $total = '0.00';
        $missingRates = [];

        foreach ($wallets as $wallet) {
            $available = (string) $wallet->ledgerAccount?->balance ?? '0.00';
            $converted = $available;

            if ($wallet->currency_code !== $inCurrency) {
                try {
                    $converted = $this->convert($available, $wallet->currency_code, $inCurrency);
                } catch (\DomainException) {
                    $converted = null; // no authoritative rate → exclude, be honest
                    $missingRates[] = $wallet->currency_code.'→'.$inCurrency;
                }
            }

            if ($converted !== null) {
                $total = bcadd($total, $converted, 2);
            }

            $rows[] = [
                'wallet_id' => $wallet->wallet_id,
                'currency' => $wallet->currency_code,
                'display_name' => $wallet->display_name,
                'balance' => $available,
                'converted_in' => $inCurrency,
                'converted_balance' => $converted,
            ];
        }

        $rateUpdatedAt = FxRate::query()
            ->where('base_currency', $inCurrency)
            ->orWhere('target_currency', $inCurrency)
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->value('updated_at');

        return [
            'total' => $total,
            'currency' => $inCurrency,
            'is_estimate' => true,                       // never authoritative (§12)
            'rate_available' => $missingRates === [],
            'rate_basis' => $missingRates === []
                ? 'Current KoriePay exchange rate'
                : 'Partial — no active rate for '.implode(', ', array_slice($missingRates, 0, 2)),
            'rate_updated_at' => $rateUpdatedAt ? \Illuminate\Support\Carbon::parse($rateUpdatedAt)->toIso8601String() : null,
            'wallets' => $rows,
        ];
    }

    /**
     * Convert an amount at the authoritative active fx rate (base→target).
     * Fails loudly when no active rate exists — never fabricates a rate.
     */
    public function convert(string $amount, string $from, string $to): string
    {
        if ($from === $to) {
            return $amount;
        }

        $rate = FxRate::query()
            ->where('base_currency', $from)
            ->where('target_currency', $to)
            ->where('is_active', true)
            ->first();

        if ($rate === null) {
            throw new \DomainException("No active exchange rate for [{$from}→{$to}]. Configure fx_rates first.");
        }

        return bcmul((string) $amount, (string) $rate->rate, 6);
    }

    public function generateWalletId(User $user, string $currency): string
    {
        $iso2 = strtolower($this->iso2For($user));

        return 'wal_'.strtolower($currency).'_'.substr(Str::slug($user->name, '_'), 0, 6).'_'.substr((string) $user->id, -4).'_'.substr((string) $user->id, 0, 4);
    }

    public function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);
        if (strlen($digits) < 7) {
            return '***';
        }

        return substr($digits, 0, 4).' *** '.substr($digits, -4);
    }

    /**
     * Normalize a decimal string to the currency's minor-unit scale. Zero-
     * minor currencies (XOF) cannot represent decimals — trailing zeros are
     * stripped, non-zero fractions round half-up to the nearest whole unit.
     * This is the single gateway before any amount enters the ledger.
     */
    public function normalizeDecimal(string $amount, string $currency): string
    {
        $units = (int) (Currency::where('code', $currency)->value('minor_units') ?? 2);

        if ($units !== 0) {
            return $amount;
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        if ($fraction !== '' && (int) $fraction > 0) {
            $whole = (string) ((int) $whole + ((int) $fraction >= 5 ? 1 : 0));
        }

        return $whole === '' ? '0' : $whole;
    }

    // ── Internals ────────────────────────────────────────────────────────

    protected function ensureLedgerAccount(User $user, string $currency): LedgerAccount
    {
        return LedgerAccount::firstOrCreate(
            [
                'owner_type' => 'user',
                'owner_id' => $user->id,
                'currency_code' => $currency,
            ],
            [
                'account_type' => 'liability',
                'name' => $user->name.' Wallet',
                'balance' => '0',
            ]
        );
    }

    protected function withBalance(CustomerWallet $wallet): CustomerWallet
    {
        $owner = User::query()->find($wallet->user_id);

        $wallet->setAttribute('available_balance', (string) $wallet->ledgerAccount?->balance ?? '0.00');
        $wallet->setAttribute('pending_balance', $owner !== null ? $this->pendingBalance($owner, $wallet->currency_code) : '0.00');
        $wallet->setAttribute('last_updated_at', $wallet->ledgerAccount?->updated_at?->toIso8601String());

        return $wallet;
    }

    protected function assertOwned(User $user, CustomerWallet $wallet): void
    {
        if ((int) $wallet->user_id !== (int) $user->id) {
            throw new \DomainException('Wallet does not belong to this customer.');
        }
    }

    protected function assertActive(CustomerWallet $wallet): void
    {
        if ($wallet->status !== CustomerWallet::STATUS_ACTIVE) {
            throw new WalletUnavailableException(
                "Wallet [{$wallet->display_name}] is not currently available for your account."
            );
        }
    }
}
