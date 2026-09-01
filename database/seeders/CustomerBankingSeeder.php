<?php

namespace Database\Seeders;

use App\Domain\Accounting\LedgerAccount;
use App\Domain\Accounting\LedgerService;
use App\Models\CustomerWalletConfig;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * CUSTOMER BANKING — dev data.
 *
 * Enables the SECONDARY wallet configs (ops decision, §75) so dev/demo users
 * can exercise exchange quotes and multi-wallet views:
 *   - Niger (NE):  XOF primary + NGN secondary
 *   - Nigeria (NG): NGN primary + XOF secondary
 *
 * Also seeds a demo customer (Niger) with funded ledger accounts so the API
 * and shell have real data. Idempotent — safe to run repeatedly.
 */
class CustomerBankingSeeder extends Seeder
{
    public function run(): void
    {
        // ── Ops enables secondary wallets ──────────────────────────────────
        $secondary = [
            ['NE', 'NGN', 'NGN Wallet', '1000000.00', '1000000.00', '100', '0.5000'],
            ['NG', 'XOF', 'XOF Wallet', '1000000.00', '1000000.00', '500', '0.5000'],
        ];

        foreach ($secondary as [$country, $currency, $name, $send, $ex, $flat, $rate]) {
            CustomerWalletConfig::updateOrCreate(
                ['country_iso2' => $country, 'currency_code' => $currency],
                [
                    'is_available' => 1,
                    'is_primary_default' => 0,
                    'min_kyc_tier' => 1,
                    'display_name' => $name,
                    'daily_send_limit' => $send,
                    'daily_exchange_limit' => $ex,
                    'exchange_fee_flat' => $flat,
                    'exchange_fee_rate' => $rate,
                ]
            );
        }

        // ── Demo Niger customer with funded XOF + NGN ledgers ──────────────
        $demo = User::firstOrCreate(
            ['email' => 'demo.ne@koriepay.test'],
            [
                'name' => 'Aminatou Niger',
                'password' => bcrypt('password123'),
                'country_code' => 'NER',
                'kyc_tier' => 2,
                'kyc_status' => 'verified',
                'phone_number' => '+22790000001',
            ]
        );

        // ── Stage 5 — demo device rows (Security Center has real data) ─────
        $this->seedDemoDevices();

        $this->fund($demo, 'XOF', '150000.00');
        $this->fund($demo, 'NGN', '75000.00');

        // ── Demo Nigeria customer with funded NGN + XOF ledgers ────────────
        $demoNg = User::firstOrCreate(
            ['email' => 'demo.ng@koriepay.test'],
            [
                'name' => 'Chidi Lagos',
                'password' => bcrypt('password123'),
                'country_code' => 'NGA',
                'kyc_tier' => 2,
                'kyc_status' => 'verified',
                'phone_number' => '+23490000002',
            ]
        );

        $this->fund($demoNg, 'NGN', '250000.00');
        $this->fund($demoNg, 'XOF', '50000.00');

        $this->command?->info('Customer banking dev data ready (secondary wallets enabled + demo users funded).');
    }

    /**
     * Idempotently credit a customer's liability ledger account for a currency
     * (creates the account if provisioning hasn't run yet).
     */
    private function fund(User $user, string $currency, string $amount): void
    {
        // Zero-minor currencies (XOF) must be whole units — Money rejects
        // decimal strings for them.
        $units = \App\Domain\Accounting\Money::minorUnitsFor($currency);
        if ($units === 0 && str_contains($amount, '.')) {
            $amount = explode('.', $amount)[0];
        }

        $account = LedgerAccount::query()
            ->where('owner_type', 'user')
            ->where('owner_id', $user->id)
            ->where('currency_code', $currency)
            ->first();

        if ($account === null) {
            $account = LedgerAccount::query()->create([
                'owner_type' => 'user',
                'owner_id' => $user->id,
                'currency_code' => $currency,
                'account_type' => 'liability',
                'name' => $user->name.' Wallet',
                'balance' => '0',
                'is_active' => true,
            ]);
        }

        if (bccomp((string) $account->balance, '0', 2) > 0) {
            return; // already funded
        }

        $asset = LedgerAccount::query()
            ->where('account_type', 'asset')
            ->where('currency_code', $currency)
            ->first();

        app(LedgerService::class)->post(
            [
                ['account_id' => $asset->id, 'side' => 'debit', 'amount' => $amount],
                ['account_id' => $account->id, 'side' => 'credit', 'amount' => $amount],
            ],
            'deposit',
            null,
            'CustomerBankingSeeder funding (dev)',
            'seed-customer-'.$user->id.'-'.$currency,
        );
    }

    /**
     * Stage 5 — demo device rows so the Security Center has real data in dev.
     * Idempotent (keyed on device_id fingerprint).
     */
    protected function seedDemoDevices(): void
    {
        foreach (User::all() as $user) {
            \App\Models\Device::updateOrCreate(
                ['user_id' => $user->id, 'device_id' => hash('sha256', 'dev-seed|'.$user->id)],
                [
                    'platform' => $user->country_code === 'NGA' ? 'Android' : 'iOS',
                    'browser' => 'Safari',
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'KoriePay demo device (seeded)',
                    'is_trusted' => true,
                    'is_current' => true,
                    'last_seen_at' => now(),
                ]
            );
        }
    }
}
