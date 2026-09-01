<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * FX RATES — authoritative server-side rates for the Customer Banking App.
 *
 * These are STATIC development seed values (approximate mid-market), loaded
 * into the `fx_rates` table that the quote/portfolio services read. In
 * production these rows are maintained by an operations/feed job; the app
 * NEVER computes a rate on the client.
 *
 * Legacy columns (pair / mid_market_rate / effective_rate) are kept in sync
 * so older surfaces keep working; `base_currency`/`target_currency`/`rate`
 * are what the customer services actually read.
 */
class FxRatesSeeder extends Seeder
{
    public function run(): void
    {
        $rates = [
            // XOF ↔ NGN (cross of approximate USD levels: XOF 620/USD, NGN 1,550/USD)
            ['XOF', 'NGN', '2.500000'],
            ['NGN', 'XOF', '0.400000'],
            // XOF ↔ USD
            ['XOF', 'USD', '0.001613'],
            ['USD', 'XOF', '620.000000'],
            // NGN ↔ USD
            ['NGN', 'USD', '0.000645'],
            ['USD', 'NGN', '1550.000000'],
        ];

        foreach ($rates as [$base, $target, $rate]) {
            DB::table('fx_rates')->updateOrInsert(
                ['base_currency' => $base, 'target_currency' => $target],
                [
                    'pair' => $base.'/'.$target,
                    'mid_market_rate' => $rate,
                    'corporate_spread' => 0,
                    'volatility_buffer' => 0,
                    'effective_rate' => $rate,
                    'rate' => $rate,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command?->info('FX rates seeded (XOF↔NGN, XOF↔USD, NGN↔USD).');
    }
}
