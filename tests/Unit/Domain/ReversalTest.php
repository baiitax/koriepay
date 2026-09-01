<?php

namespace Tests\Unit\Domain;

use App\Domain\Accounting\LedgerAccount;
use App\Domain\Accounting\LedgerEntry;
use App\Domain\Accounting\LedgerService;
use App\Domain\Accounting\LedgerTransaction;
use App\Domain\Accounting\ReversalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Formal reversal: the original posting is immutable; a new opposite posting
 * restores balances. Nothing is ever edited or deleted.
 */
class ReversalTest extends TestCase
{
    use RefreshDatabase;

    private LedgerService $ledger;

    private ReversalService $reversal;

    private LedgerAccount $alice;

    private LedgerAccount $bob;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ledger = app(LedgerService::class);
        $this->reversal = app(ReversalService::class);

        $this->alice = LedgerAccount::create([
            'account_type' => 'liability', 'currency_code' => 'NGN',
            'name' => 'Alice', 'owner_type' => 'user', 'owner_id' => 1,
        ]);
        $this->bob = LedgerAccount::create([
            'account_type' => 'liability', 'currency_code' => 'NGN',
            'name' => 'Bob', 'owner_type' => 'user', 'owner_id' => 2,
        ]);
        $cash = LedgerAccount::create([
            'account_type' => 'asset', 'currency_code' => 'NGN',
            'name' => 'Cash', 'is_system' => true,
        ]);

        $this->ledger->post([
            ['account_id' => $cash->id, 'side' => 'debit', 'amount' => '5000.00'],
            ['account_id' => $this->alice->id, 'side' => 'credit', 'amount' => '5000.00'],
        ], 'opening_balance', 'OPEN-REV');
    }

    public function test_reversal_restores_balances_without_editing_original(): void
    {
        $original = $this->ledger->post([
            ['account_id' => $this->alice->id, 'side' => 'debit', 'amount' => '2000.00'],
            ['account_id' => $this->bob->id, 'side' => 'credit', 'amount' => '2000.00'],
        ], 'p2p_transfer', 'TX-ORIG-1');

        $this->assertSame('3000.00', $this->alice->fresh()->balance);
        $this->assertSame('2000.00', $this->bob->fresh()->balance);

        $reversal = $this->reversal->reverse($original, 'duplicate_request', createdBy: 9);

        $this->assertSame('reversal', $reversal->type);
        $this->assertSame('REV-TX-ORIG-1', $reversal->reference);

        // Balances restored
        $this->assertSame('5000.00', $this->alice->fresh()->balance);
        $this->assertSame('0.00', $this->bob->fresh()->balance);

        // Original posting untouched
        $this->assertSame('TX-ORIG-1', $original->fresh()->reference);
        $this->assertSame(2, $original->fresh()->entries()->count());

        // Reversal posting balanced and opposite-sided
        $reversalEntries = $reversal->entries()->get();
        $this->assertSame(2, $reversalEntries->count());
        $this->assertSame(LedgerEntry::SIDE_CREDIT, $reversalEntries->firstWhere('account_id', $this->alice->id)->side);
        $this->assertSame(LedgerEntry::SIDE_DEBIT, $reversalEntries->firstWhere('account_id', $this->bob->id)->side);
    }

    public function test_reversal_is_idempotent(): void
    {
        $original = $this->ledger->post([
            ['account_id' => $this->alice->id, 'side' => 'debit', 'amount' => '100.00'],
            ['account_id' => $this->bob->id, 'side' => 'credit', 'amount' => '100.00'],
        ], 'p2p_transfer', 'TX-ORIG-2');

        $first = $this->reversal->reverse($original, 'manual');
        $second = $this->reversal->reverse($original, 'manual');

        $this->assertSame($first->reference, $second->reference);
        $this->assertSame(1, LedgerTransaction::where('type', 'reversal')->count());
        $this->assertSame('5000.00', $this->alice->fresh()->balance, 'Reversed once only → balance fully restored.');
        $this->assertSame('0.00', $this->bob->fresh()->balance);
    }
}
