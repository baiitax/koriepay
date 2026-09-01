<?php

namespace Database\Seeders;

use App\Domain\Accounting\LedgerAccount;
use Illuminate\Database\Seeder;

/**
 * PHASE 5 — Ledger seed (custodial model).
 *
 * The internal ledger rail refuses to fabricate accounts — it fails loudly
 * when Platform Cash or a user wallet is missing. This seed provisions:
 *
 *   1. Platform Cash ASSET accounts, one per configured currency (NGN, XOF).
 *   2. Wallet LIABILITY accounts for every existing user (NGN, plus XOF for
 *      users domiciled in XOF countries).
 *
 * Safe to re-run: account lookups are deterministic and skips on existence.
 * Balance is intentionally left at 0 — real money only enters via the ledger
 * (deposit posts DR cash / CR wallet).
 */
class LedgerSeed extends Seeder
{
    public function run(): void
    {
        $currencies = \App\Models\Currency::query()
            ->whereIn('code', ['NGN', 'XOF'])
            ->pluck('code')
            ->all();

        if ($currencies === []) {
            $currencies = ['NGN', 'XOF'];
        }

        // 1. Platform Cash (asset) per currency.
        foreach ($currencies as $currency) {
            $exists = LedgerAccount::query()
                ->where('account_type', 'asset')
                ->where('name', 'Platform Cash')
                ->where('currency_code', $currency)
                ->exists();

            if (! $exists) {
                LedgerAccount::create([
                    'account_type' => 'asset',
                    'name' => 'Platform Cash',
                    'currency_code' => $currency,
                    'is_system' => true,
                    'balance' => '0',
                ]);
                $this->command?->info("Platform Cash [{$currency}] created.");
            }
        }

        // 1b. Commission Expense (expense, system) per currency — consumed by
        // the CommissionEngine accrual posting (Phase 6).
        foreach ($currencies as $currency) {
            $exists = LedgerAccount::query()
                ->where('account_type', 'expense')
                ->where('name', 'Commission Expense')
                ->where('currency_code', $currency)
                ->exists();

            if (! $exists) {
                LedgerAccount::create([
                    'account_type' => 'expense',
                    'name' => 'Commission Expense',
                    'currency_code' => $currency,
                    'is_system' => true,
                    'balance' => '0',
                ]);
                $this->command?->info("Commission Expense [{$currency}] created.");
            }
        }

        // 2. Wallets (liability) for every user.
        foreach (\App\Models\User::all() as $user) {
            $userCurrencies = ['NGN'];
            if (strtoupper((string) $user->country_code) === 'NER') {
                $userCurrencies[] = 'XOF';
            }

            foreach ($userCurrencies as $currency) {
                $exists = LedgerAccount::query()
                    ->where('owner_type', 'user')
                    ->where('owner_id', $user->id)
                    ->where('currency_code', $currency)
                    ->exists();

                if (! $exists) {
                    LedgerAccount::create([
                        'account_type' => 'liability',
                        'name' => $user->name.' Wallet',
                        'currency_code' => $currency,
                        'owner_type' => 'user',
                        'owner_id' => $user->id,
                        'balance' => '0',
                    ]);
                    $this->command?->info("Wallet [{$user->name}] {$currency} created.");
                }
            }
        }
    }
}
