<?php

namespace Tests\Feature;

use App\Domain\Accounting\LedgerAccount;
use App\Models\Currency;
use App\Models\User;
use Database\Seeders\LedgerSeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PHASE 5 — the ledger seed must provision Platform Cash (asset) per currency
 * and wallet (liability) per user, so the internal ledger rail never has to
 * fabricate accounts at runtime.
 */
class LedgerSeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_provisions_cash_and_wallets(): void
    {
        // NGN + XOF are already seeded by migration 000100.
        $this->assertNotNull(Currency::where('code', 'NGN')->first());

        $alice = User::factory()->create(['name' => 'Alice', 'country_code' => 'NGA']);
        $mariam = User::factory()->create(['name' => 'Mariam', 'country_code' => 'NER']);

        $this->seed(LedgerSeed::class);

        // Platform Cash asset accounts.
        $this->assertNotNull(LedgerAccount::query()
            ->where('account_type', 'asset')->where('name', 'Platform Cash')
            ->where('currency_code', 'NGN')->first());
        $this->assertNotNull(LedgerAccount::query()
            ->where('account_type', 'asset')->where('name', 'Platform Cash')
            ->where('currency_code', 'XOF')->first());

        // Wallets: NGN for everyone, XOF additionally for the Niger user.
        $this->assertNotNull(LedgerAccount::query()
            ->where('owner_type', 'user')->where('owner_id', $alice->id)
            ->where('currency_code', 'NGN')->first());
        $this->assertNull(LedgerAccount::query()
            ->where('owner_type', 'user')->where('owner_id', $alice->id)
            ->where('currency_code', 'XOF')->first());
        $this->assertNotNull(LedgerAccount::query()
            ->where('owner_type', 'user')->where('owner_id', $mariam->id)
            ->where('currency_code', 'NGN')->first());
        $this->assertNotNull(LedgerAccount::query()
            ->where('owner_type', 'user')->where('owner_id', $mariam->id)
            ->where('currency_code', 'XOF')->first());

        // All seeded balances start at zero.
        $this->assertSame('0.00', (string) LedgerAccount::where('name', 'Platform Cash')->value('balance'));
    }

    public function test_seed_is_idempotent(): void
    {
        User::factory()->create(['name' => 'Alice', 'country_code' => 'NGA']);

        $this->seed(LedgerSeed::class);
        $counts = [
            LedgerAccount::where('account_type', 'asset')->where('name', 'Platform Cash')->count(),
            LedgerAccount::where('owner_type', 'user')->count(),
        ];

        $this->seed(LedgerSeed::class);

        $this->assertSame(
            $counts,
            [
                LedgerAccount::where('account_type', 'asset')->where('name', 'Platform Cash')->count(),
                LedgerAccount::where('owner_type', 'user')->count(),
            ],
            're-running the seed must not duplicate accounts'
        );
    }
}
