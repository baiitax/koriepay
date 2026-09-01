<?php

namespace Tests\Unit\Domain;

use App\Domain\Accounting\Exceptions\InsufficientFundsException;
use App\Domain\Accounting\Exceptions\LedgerValidationException;
use App\Domain\Accounting\Exceptions\UnbalancedLedgerException;
use App\Domain\Accounting\LedgerAccount;
use App\Domain\Accounting\LedgerEntry;
use App\Domain\Accounting\LedgerService;
use App\Domain\Accounting\LedgerTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * THE core invariant: for every posted transaction, Σ debits = Σ credits,
 * balances never go negative, and projections derive from entries.
 *
 * Custodial model: wallets are LIABILITY accounts (platform owes customers);
 * the platform float is an ASSET account.
 */
class LedgerServiceTest extends TestCase
{
    use RefreshDatabase;

    private LedgerService $ledger;

    private LedgerAccount $alice;

    private LedgerAccount $bob;

    private LedgerAccount $revenue;

    private LedgerAccount $cash;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ledger = app(LedgerService::class);

        $this->alice = LedgerAccount::create([
            'account_type' => 'liability', 'currency_code' => 'NGN',
            'name' => 'Alice Wallet', 'owner_type' => 'user', 'owner_id' => 1,
        ]);
        $this->bob = LedgerAccount::create([
            'account_type' => 'liability', 'currency_code' => 'NGN',
            'name' => 'Bob Wallet', 'owner_type' => 'user', 'owner_id' => 2,
        ]);
        $this->revenue = LedgerAccount::create([
            'account_type' => 'income', 'currency_code' => 'NGN',
            'name' => 'Revenue', 'is_system' => true,
        ]);
        $this->cash = LedgerAccount::create([
            'account_type' => 'asset', 'currency_code' => 'NGN',
            'name' => 'Platform Cash', 'is_system' => true,
        ]);

        // Opening: DR Platform Cash / CR Alice wallet (we owe Alice 10,000)
        $this->ledger->post([
            ['account_id' => $this->cash->id, 'side' => 'debit', 'amount' => '10000.00'],
            ['account_id' => $this->alice->id, 'side' => 'credit', 'amount' => '10000.00'],
        ], 'opening_balance', 'OPEN-ALICE-1');
    }

    public function test_balanced_transfer_posts_and_moves_balances(): void
    {
        // Alice → Bob 2500: DR Alice (liability↓) / CR Bob (liability↑)
        $posting = $this->ledger->post([
            ['account_id' => $this->alice->id, 'side' => 'debit', 'amount' => '2500.00'],
            ['account_id' => $this->bob->id, 'side' => 'credit', 'amount' => '2500.00'],
        ], 'p2p_transfer', 'TX-100', 'Alice → Bob');

        $this->assertInstanceOf(LedgerTransaction::class, $posting);
        $this->assertSame('TX-100', $posting->reference);

        $this->assertSame('7500.00', $this->alice->fresh()->balance);
        $this->assertSame('2500.00', $this->bob->fresh()->balance);

        // Invariant: total debits == total credits for the posting
        $sums = $this->entrySums($posting);
        $this->assertSame($sums['debit'], $sums['credit']);
    }

    public function test_unbalanced_posting_is_rejected_atomically(): void
    {
        $this->expectException(UnbalancedLedgerException::class);

        $this->ledger->post([
            ['account_id' => $this->alice->id, 'side' => 'debit', 'amount' => '100.00'],
            ['account_id' => $this->bob->id, 'side' => 'credit', 'amount' => '99.99'], // off by 0.01
        ], 'p2p_transfer');

        // Nothing may have been persisted
        $this->assertSame(0, LedgerTransaction::count());
        $this->assertSame(0, LedgerEntry::count());
    }

    public function test_insufficient_funds_rejected(): void
    {
        $this->expectException(InsufficientFundsException::class);

        $this->ledger->post([
            ['account_id' => $this->alice->id, 'side' => 'debit', 'amount' => '10001.00'],
            ['account_id' => $this->bob->id, 'side' => 'credit', 'amount' => '10001.00'],
        ], 'p2p_transfer');
    }

    public function test_currency_isolation_between_pairs(): void
    {
        // Alice also holds XOF (liability, XOF)
        $aliceXof = LedgerAccount::create([
            'account_type' => 'liability', 'currency_code' => 'XOF',
            'name' => 'Alice XOF', 'owner_type' => 'user', 'owner_id' => 1,
        ]);
        $cashXof = LedgerAccount::create([
            'account_type' => 'asset', 'currency_code' => 'XOF',
            'name' => 'Platform Cash XOF', 'is_system' => true,
        ]);
        $this->ledger->post([
            ['account_id' => $cashXof->id, 'side' => 'debit', 'amount' => '5000'],
            ['account_id' => $aliceXof->id, 'side' => 'credit', 'amount' => '5000'],
        ], 'opening_balance', 'OPEN-ALICE-XOF');

        // A ₦10 transfer must not touch the XOF account
        $this->ledger->post([
            ['account_id' => $this->alice->id, 'side' => 'debit', 'amount' => '10.00'],
            ['account_id' => $this->bob->id, 'side' => 'credit', 'amount' => '10.00'],
        ], 'p2p_transfer', 'TX-CURR-1');

        $this->assertSame('5000.00', $aliceXof->fresh()->balance);
        $this->assertSame('9990.00', $this->alice->fresh()->balance);
    }

    public function test_fee_capture_as_single_balanced_posting(): void
    {
        // Alice sends 1000 with 15 fee:
        // DR Alice 1015 (liability↓) / CR Bob 1000 (liability↑) / CR Revenue 15
        $this->ledger->post([
            ['account_id' => $this->alice->id, 'side' => 'debit', 'amount' => '1015.00'],
            ['account_id' => $this->bob->id, 'side' => 'credit', 'amount' => '1000.00'],
            ['account_id' => $this->revenue->id, 'side' => 'credit', 'amount' => '15.00'],
        ], 'p2p_transfer_with_fee', 'TX-FEE-1');

        $this->assertSame('8985.00', $this->alice->fresh()->balance, 'Alice pays 1015 total.');
        $this->assertSame('1000.00', $this->bob->fresh()->balance, 'Bob receives 1000.');
        $this->assertSame('15.00', $this->revenue->fresh()->balance, 'Platform earns 15.');
        $this->assertSame('10000.00', $this->cash->fresh()->balance, 'Platform cash untouched by internal transfer.');
    }

    public function test_rejects_negative_or_zero_amounts(): void
    {
        $this->expectException(LedgerValidationException::class);

        $this->ledger->post([
            ['account_id' => $this->alice->id, 'side' => 'debit', 'amount' => '0'],
            ['account_id' => $this->bob->id, 'side' => 'credit', 'amount' => '0'],
        ], 'p2p_transfer');
    }

    public function test_derived_balance_matches_projection(): void
    {
        $this->ledger->post([
            ['account_id' => $this->alice->id, 'side' => 'debit', 'amount' => '333.33'],
            ['account_id' => $this->bob->id, 'side' => 'credit', 'amount' => '333.33'],
        ], 'p2p_transfer', 'TX-DERIVED-1');

        $derived = $this->ledger->derivedBalance($this->alice->fresh());
        $projected = $this->alice->fresh()->balance;

        $this->assertSame($projected, $derived);
        $this->assertSame('9666.67', $derived);
    }

    /**
     * @return array{debit:string, credit:string}
     */
    private function entrySums(LedgerTransaction $posting): array
    {
        $debit = '0';
        $credit = '0';

        foreach ($posting->entries as $entry) {
            if ($entry->side === LedgerEntry::SIDE_DEBIT) {
                $debit = bcadd($debit, (string) $entry->amount, 2);
            } else {
                $credit = bcadd($credit, (string) $entry->amount, 2);
            }
        }

        return ['debit' => $debit, 'credit' => $credit];
    }
}
