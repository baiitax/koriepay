<?php

namespace App\Domain\Customer;

use App\Models\CustomerWallet;
use App\Models\CustomerWalletConfig;
use App\Models\Device;
use App\Models\Transaction;
use App\Models\User;

/**
 * CUSTOMER BANKING — Stage 5 (security center).
 *
 * Everything here is honest read-side data:
 *   - devices: real rows from the `devices` table (registered server-side
 *     from request context); an empty table is reported as an empty list,
 *     never fabricated.
 *   - per-device limits: from CustomerWalletConfig for the customer's
 *     country+currency (daily send / daily exchange).
 *   - daily spend today: computed from the customer's real transactions
 *     (transfer/exchange/bill/airtime/data debits since start of day).
 *
 * PIN/biometric preferences are SESSION-ONLY by design (§44 security brief):
 * the app never persists credentials, and this service never touches a PIN
 * hash. `sessionLimitEdits` holds limit changes for the current session only.
 */
class CustomerSecurityService
{
    public function devices(User $user): array
    {
        $rows = Device::query()
            ->where('user_id', $user->id)
            ->orderByDesc('last_seen_at')
            ->get();

        if ($rows->isEmpty()) {
            return [
                'items' => [],
                'empty_reason' => 'insufficient_usage_data',
                'empty_message' => 'No sign-in devices recorded for this account yet. Devices appear here after you sign in from a new browser or phone.',
            ];
        }

        return [
            'items' => $rows->map(fn (Device $d) => [
                'id' => $d->id,
                'device_id' => substr((string) $d->device_id, 0, 12).'…',
                'platform' => $d->platform ?: 'Unknown device',
                'browser' => $d->browser,
                'is_current' => (bool) $d->is_current,
                'is_trusted' => (bool) $d->is_trusted,
                'last_seen_at' => $d->last_seen_at?->toIso8601String(),
            ])->all(),
            'empty_reason' => null,
            'empty_message' => null,
        ];
    }

    /**
     * Per-wallet limit rows — semantics come from the country+currency config
     * that actually governs the wallet. Values that are not configured are
     * honestly null (the UI shows "not set" instead of inventing a number).
     */
    public function walletLimits(User $user): array
    {
        $iso2 = app(CustomerWalletService::class)->iso2For($user);

        return CustomerWallet::query()
            ->where('user_id', $user->id)
            ->get()
            ->map(function (CustomerWallet $wallet) use ($user, $iso2) {
                $config = CustomerWalletConfig::query()
                    ->where('country_iso2', $iso2)
                    ->where('currency_code', $wallet->currency_code)
                    ->first();

                $spendToday = $this->spentToday($user, $wallet->currency_code);

                return [
                    'wallet_id' => $wallet->id,
                    'currency' => $wallet->currency_code,
                    'display_name' => $wallet->display_name,
                    'is_primary' => (bool) $wallet->is_primary,
                    'daily_send_limit' => $config?->daily_send_limit !== null
                        ? (string) $config->daily_send_limit : null,
                    'daily_exchange_limit' => $config?->daily_exchange_limit !== null
                        ? (string) $config->daily_exchange_limit : null,
                    'daily_spent_today' => $spendToday,
                    'config_country' => $iso2,
                ];
            })
            ->all();
    }

    /**
     * Honest daily spend — sum of the customer's real money-out transactions
     * (transfers, exchanges, bills, airtime, data) in a currency since today.
     */
    public function spentToday(User $user, string $currency): string
    {
        $sum = Transaction::query()
            ->where('sender_id', $user->id)
            ->where('source_currency', $currency)
            ->whereIn('type', ['transfer', 'exchange', 'bill', 'airtime', 'data'])
            ->where('created_at', '>=', now()->startOfDay())
            ->sum('source_amount');

        return number_format((float) $sum, 2, '.', '');
    }

    /**
     * Session-only limit edits — stored in the session, never in the ledger
     * config tables. A refresh or logout clears them.
     */
    public function sessionLimitEdits(User $user): array
    {
        return (array) session('security.limit_edits.'.$user->id, []);
    }

    public function saveLimitEdit(User $user, string $currency, string $kind, string $value): void
    {
        $edits = $this->sessionLimitEdits($user);
        $edits[$currency][$kind] = $value;
        session(['security.limit_edits.'.$user->id => $edits]);
    }

    // ── Session-only security preferences ─────────────────────────────────

    public function biometricEnabled(User $user): bool
    {
        return (bool) session('security.biometric.'.$user->id, false);
    }

    public function setBiometric(User $user, bool $enabled): void
    {
        session(['security.biometric.'.$user->id => $enabled]);
    }

    public function pinEnrolled(User $user): bool
    {
        return (bool) session('security.pin.'.$user->id, false);
    }
}
