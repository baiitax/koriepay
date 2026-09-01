<?php

namespace Tests\Unit\Domain;

use App\Domain\Accounting\IdempotencyService;
use App\Domain\Accounting\LedgerAccount;
use App\Domain\Accounting\LedgerService;
use App\Domain\Accounting\LedgerTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Idempotency: same key → same outcome, never a second posting.
 * This is the "never debit twice" guarantee.
 */
class IdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private LedgerService $ledger;

    private LedgerAccount $alice;

    private LedgerAccount $bob;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ledger = app(LedgerService::class);
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
        app(LedgerService::class)->post([
            ['account_id' => $cash->id, 'side' => 'debit', 'amount' => '10000.00'],
            ['account_id' => $this->alice->id, 'side' => 'credit', 'amount' => '10000.00'],
        ], 'opening_balance', 'OPEN-IDEM');
    }

    public function test_replay_returns_original_posting_without_duplicating(): void
    {
        $key = 'txn-'.str_repeat('a', 16);

        $first = $this->ledger->post([
            ['account_id' => $this->alice->id, 'side' => 'debit', 'amount' => '500.00'],
            ['account_id' => $this->bob->id, 'side' => 'credit', 'amount' => '500.00'],
        ], 'p2p_transfer', 'TX-IDEM-1', idempotencyKey: $key);

        $second = $this->ledger->post([
            ['account_id' => $this->alice->id, 'side' => 'debit', 'amount' => '500.00'],
            ['account_id' => $this->bob->id, 'side' => 'credit', 'amount' => '500.00'],
        ], 'p2p_transfer', 'TX-IDEM-1', idempotencyKey: $key);

        $this->assertSame($first->reference, $second->reference);
        // 1 opening posting + 1 transfer = 2; the replay must NOT add a third.
        $this->assertSame(2, LedgerTransaction::count(), 'Duplicate posting must not occur.');
        $this->assertSame('500.00', $this->bob->fresh()->balance, 'Receiver credited exactly once.');
    }

    public function test_different_keys_create_distinct_postings(): void
    {
        $this->ledger->post([
            ['account_id' => $this->alice->id, 'side' => 'debit', 'amount' => '100.00'],
            ['account_id' => $this->bob->id, 'side' => 'credit', 'amount' => '100.00'],
        ], 'p2p_transfer', 'TX-IDEM-2A', idempotencyKey: 'key-1');

        $this->ledger->post([
            ['account_id' => $this->alice->id, 'side' => 'debit', 'amount' => '100.00'],
            ['account_id' => $this->bob->id, 'side' => 'credit', 'amount' => '100.00'],
        ], 'p2p_transfer', 'TX-IDEM-2B', idempotencyKey: 'key-2');

        // 1 opening posting + 2 transfers = 3
        $this->assertSame(3, LedgerTransaction::count());
        $this->assertSame('200.00', $this->bob->fresh()->balance);
    }

    public function test_idempotency_service_wraps_arbitrary_operations(): void
    {
        $service = app(IdempotencyService::class);
        $runs = 0;

        $result1 = $service->execute('op-custom-1', function () use (&$runs) {
            $runs++;
            return ['reference' => 'R-9', 'status' => 'created'];
        });
        $result2 = $service->execute('op-custom-1', function () use (&$runs) {
            $runs++;
            return ['reference' => 'R-9', 'status' => 'created'];
        });

        $this->assertSame($result1, $result2);
        $this->assertSame(1, $runs, 'Callback must execute exactly once per key.');
        $this->assertTrue($service->isReplay('op-custom-1'));
    }
}
