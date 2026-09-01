<?php

namespace Tests\Feature;

use App\Domain\Accounting\LedgerAccount;
use App\Domain\Accounting\LedgerService;
use App\Domain\Reconciliation\ReconciliationService;
use App\Domain\Reconciliation\SettlementService;
use App\Models\BalanceSnapshot;
use App\Models\ReconciliationItem;
use App\Models\ReconciliationRun;
use App\Models\Settlement;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * PHASE 8 — Reconciliation & settlement.
 *
 * Settlement lifecycle is guarded + audited; cash movement goes through the
 * ledger (DR Settlement Payable / CR Platform Cash). Reconciliation matches
 * real internal records against real provider records, computes a health
 * score, and balance-snapshot comparison guards against balance drift.
 */
class ReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private LedgerService $ledger;
    private SettlementService $settlements;
    private ReconciliationService $reconciliation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ledger = app(LedgerService::class);
        $this->settlements = app(SettlementService::class);
        $this->reconciliation = app(ReconciliationService::class);
    }

    // ── Settlements ──────────────────────────────────────────────────────

    public function test_schedule_then_settle_with_ledger_movement(): void
    {
        $this->seedCash('NGN', '500000.00');
        $actor = User::factory()->create(['name' => 'Actor']);

        $stl = $this->settlements->schedule(
            providerCode: 'ledger',
            countryIso2: 'NG',
            currencyCode: 'NGN',
            amount: '250000.00',
            opts: ['scheduled_at' => now()->addHours(6)],
            actorId: $actor->id,
        );

        $this->assertSame(Settlement::STATUS_SCHEDULED, $stl->status);
        $this->assertStringStartsWith('STL-', $stl->reference);
        $this->assertNotNull($stl->scheduled_at, 'Next Settlement must be schedulable');
        $this->assertDatabaseHas('audit_logs', ['action' => 'settlement.scheduled']);

        $this->settlements->markPending($stl);
        $this->settlements->markProcessing($stl);

        $settled = $this->settlements->settle($stl, 'PRV-123', '250000.00', postLedger: true, actorId: $actor->id);

        $this->assertSame(Settlement::STATUS_SETTLED, $settled->status);
        $this->assertSame('250000.00', (string) $settled->settled_amount);
        $this->assertSame('PRV-123', $settled->provider_reference);
        $this->assertNotNull($settled->settled_at);

        // Cash movement: cash 500,000 − 250,000 = 250,000; payable credited 250,000 then debited on settle.
        $cash = LedgerAccount::where('account_type', 'asset')->where('name', 'Platform Cash')->where('currency_code', 'NGN')->first();
        $this->assertSame('250000.00', bcadd((string) $cash->balance, '0', 2));

        $payable = LedgerAccount::where('owner_type', 'provider')->where('owner_id', \App\Domain\Reconciliation\SettlementService::providerOwnerId('ledger'))->where('name', 'Settlement Payable')->where('currency_code', 'NGN')->first();
        $this->assertNotNull($payable);
        $this->assertSame('0.00', bcadd((string) $payable->balance, '0', 2), 'payable debited on settlement');

        $this->assertLedgerBalanced('NGN');
        $this->assertDatabaseHas('audit_logs', ['action' => 'settlement.settled']);
    }

    public function test_settle_without_platform_cash_fails_loudly(): void
    {
        $actor = User::factory()->create(['name' => 'Actor']);
        $stl = $this->settlements->schedule('ledger', 'NG', 'NGN', '1000.00', actorId: $actor->id);
        $this->settlements->markPending($stl);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Platform Cash account missing');
        $this->settlements->settle($stl, 'PRV-X', '1000.00', postLedger: true, actorId: $actor->id);
    }

    public function test_illegal_settlement_transition_is_rejected(): void
    {
        $actor = User::factory()->create(['name' => 'Actor']);
        $stl = $this->settlements->schedule('ledger', 'NG', 'NGN', '1000.00', actorId: $actor->id);

        $this->settlements->markPending($stl);
        $this->settlements->markProcessing($stl);

        // already processing → cannot transition to processing again.
        $this->expectException(\DomainException::class);
        $this->settlements->markProcessing($stl);
    }

    public function test_exposure_and_next_settlement(): void
    {
        $this->settlements->schedule('ledger', 'NG', 'NGN', '100000.00', ['scheduled_at' => now()->addHours(2)]);
        $this->settlements->schedule('ledger', 'NE', 'XOF', '500000', ['scheduled_at' => now()->addHours(4)]);
        $stl = $this->settlements->schedule('ledger', 'NG', 'NGN', '75000.00', ['scheduled_at' => now()->addHours(1)]);

        $this->assertSame('675000.00', bcadd((string) $this->settlements->exposure(), '0', 2));
        $this->assertSame('500000.00', bcadd((string) $this->settlements->exposure(countryIso2: 'NE'), '0', 2));
        $this->assertSame('175000.00', bcadd((string) $this->settlements->exposure(currencyCode: 'NGN'), '0', 2));

        $next = $this->settlements->nextSettlement();
        $this->assertSame($stl->id, $next->id, 'next settlement = earliest scheduled');
    }

    // ── Reconciliation runs ──────────────────────────────────────────────

    public function test_run_matches_perfectly_and_scores_100(): void
    {
        $this->seedTxWithAttempt('2026-08-01 10:00:00', 'PRV-1', '1000.00', '1000.00');
        $this->seedTxWithAttempt('2026-08-01 11:00:00', 'PRV-2', '2500.00', '2500.00');

        $recon = User::factory()->create(['name' => 'Recon Officer']);
        $run = $this->reconciliation->run(
            Carbon::parse('2026-08-01 00:00:00'),
            Carbon::parse('2026-08-01 23:59:59'),
            runBy: $recon->id,
        );

        $this->assertSame(ReconciliationRun::STATUS_COMPLETED, $run->status);
        $this->assertSame(2, $run->internal_count);
        $this->assertSame(2, $run->provider_count);
        $this->assertSame(2, $run->matched_count);
        $this->assertSame(0, $run->unmatched_internal_count);
        $this->assertSame(0, $run->unmatched_provider_count);
        $this->assertSame(0, $run->amount_mismatch_count);
        $this->assertSame('100.00', (string) $run->health_score);
        $this->assertSame('3500.00', (string) $run->internal_amount);
        $this->assertSame('3500.00', (string) $run->provider_amount);
        $this->assertSame('0.00', (string) $run->difference);
        $this->assertDatabaseHas('audit_logs', ['action' => 'reconciliation.run']);
    }

    public function test_run_detects_amount_mismatch_and_unmatched_sides(): void
    {
        // Matched pair + a mismatch (provider says 900, internal says 1000).
        $this->seedTxWithAttempt('2026-08-01 10:00:00', 'PRV-OK', '1000.00', '1000.00');
        $this->seedTxWithAttempt('2026-08-01 10:05:00', 'PRV-BAD', '1000.00', '900.00');
        // Internal without provider confirmation.
        $this->seedTx('2026-08-01 10:10:00', 'PRV-INT', '500.00');
        // Provider record without internal transaction.
        $this->seedAttemptOnly('2026-08-01 10:15:00', 'PRV-PROV', '300.00');

        $run = $this->reconciliation->run(
            Carbon::parse('2026-08-01 00:00:00'),
            Carbon::parse('2026-08-01 23:59:59'),
        );

        $this->assertSame(1, $run->matched_count);
        $this->assertSame(1, $run->amount_mismatch_count);
        $this->assertSame(1, $run->unmatched_internal_count);
        $this->assertSame(1, $run->unmatched_provider_count);

        $this->assertLessThan(100.0, (float) $run->health_score);
        $this->assertSame('300.00', (string) $run->difference, 'internal 2500 − provider 2200');

        $mismatch = ReconciliationItem::where('run_id', $run->id)->where('status', 'amount_mismatch')->first();
        $this->assertSame('100.00', (string) $mismatch->discrepancy);
    }

    public function test_run_detects_duplicate_provider_records(): void
    {
        $this->seedTxWithAttempt('2026-08-01 10:00:00', 'PRV-1', '1000.00', '1000.00');
        // Second attempt, same reference → duplicate.
        $tx = Transaction::where('provider_reference', 'PRV-1')->first();
        DB::table('transaction_attempts')->insert([
            'transaction_id' => $tx->id,
            'attempt_number' => 2,
            'provider' => 'ledger',
            'provider_reference' => 'PRV-1',
            'amount' => '1000.00',
            'status' => 'success',
            'created_at' => '2026-08-01 10:01:00',
            'updated_at' => '2026-08-01 10:01:00',
        ]);

        $run = $this->reconciliation->run(
            Carbon::parse('2026-08-01 00:00:00'),
            Carbon::parse('2026-08-01 23:59:59'),
        );

        $this->assertSame(1, $run->matched_count);
        $this->assertSame(1, $run->duplicate_count);
        $this->assertDatabaseHas('reconciliation_items', ['run_id' => $run->id, 'status' => 'duplicate']);
    }

    public function test_run_is_scoped_by_provider_and_country(): void
    {
        $this->seedTxWithAttempt('2026-08-01 10:00:00', 'PRV-A1', '1000.00', '1000.00', 'ledger', 'NGA');
        $this->seedTxWithAttempt('2026-08-01 10:00:00', 'PRV-B1', '2000.00', '2000.00', 'other', 'NER');

        $run = $this->reconciliation->run(
            Carbon::parse('2026-08-01 00:00:00'),
            Carbon::parse('2026-08-01 23:59:59'),
            providerCode: 'ledger',
            countryIso2: 'NG',
        );

        $this->assertSame(1, $run->internal_count);
        $this->assertSame('PRV-A1', ReconciliationItem::where('run_id', $run->id)->first()->match_key);
        $this->assertSame('NG', $run->country_iso2);
    }

    public function test_reconciliation_health_returns_latest_run(): void
    {
        $this->seedTxWithAttempt('2026-08-01 10:00:00', 'PRV-1', '1000.00', '1000.00');
        $this->reconciliation->run(Carbon::parse('2026-08-01 00:00:00'), Carbon::parse('2026-08-01 23:59:59'));

        $health = $this->reconciliation->reconciliationHealth();
        $this->assertSame('100.00', $health['health_score']);
        $this->assertSame(0, $health['open_exceptions']);
        $this->assertStringStartsWith('REC-', $health['run_reference']);
        $this->assertNotNull($health['freshness']);
    }

    public function test_item_resolution_is_audited(): void
    {
        $this->seedTxWithAttempt('2026-08-01 10:00:00', 'PRV-BAD', '1000.00', '900.00');
        $run = $this->reconciliation->run(Carbon::parse('2026-08-01 00:00:00'), Carbon::parse('2026-08-01 23:59:59'));

        $item = ReconciliationItem::where('run_id', $run->id)->where('status', 'amount_mismatch')->first();
        $actor = User::factory()->create(['name' => 'Recon Officer']);

        $resolved = $this->reconciliation->resolveItem($item, 'adjusted', actorId: $actor->id, note: 'Provider fee not reflected');

        $this->assertSame('adjusted', $resolved->resolution);
        $this->assertSame($actor->id, $resolved->resolved_by);
        $this->assertNotNull($resolved->resolved_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'reconciliation.resolved']);
    }

    // ── Balance-snapshot comparison ──────────────────────────────────────

    public function test_balance_snapshot_matches_when_projection_consistent(): void
    {
        $account = $this->seedLiabilityAccount('NGN', 'Wallet A', '5000.00');
        $this->fund($account, '5000.00');

        $snapshot = $this->reconciliation->takeBalanceSnapshot($account->fresh());

        $this->assertSame(BalanceSnapshot::STATUS_MATCHED, $snapshot->status);
        $this->assertSame('5000.00', (string) $snapshot->derived_balance);
        $this->assertSame('5000.00', (string) $snapshot->projected_balance);
        $this->assertSame('0.00', (string) $snapshot->difference);
    }

    public function test_balance_snapshot_detects_direct_balance_drift(): void
    {
        $account = $this->seedLiabilityAccount('NGN', 'Wallet B', '5000.00');
        $this->fund($account, '5000.00');

        // Direct balance mutation (what "Edit Balance" would do) — the
        // snapshot must catch the drift.
        $account->forceFill(['balance' => '999999.00'])->save();

        $snapshot = $this->reconciliation->takeBalanceSnapshot($account->fresh());

        $this->assertSame(BalanceSnapshot::STATUS_MISMATCH, $snapshot->status);
        $this->assertSame('5000.00', (string) $snapshot->derived_balance);
        $this->assertSame('999999.00', (string) $snapshot->projected_balance);
        $this->assertSame('-994999.00', (string) $snapshot->difference);
    }

    // ── RBAC ─────────────────────────────────────────────────────────────

    public function test_reconciliation_permissions_are_enforced(): void
    {
        $admin = User::factory()->create(['name' => 'Admin']);
        $admin->forceFill(['role' => 'admin'])->save();
        $customer = User::factory()->create(['name' => 'Customer']);
        $customer->forceFill(['role' => 'customer'])->save();

        $this->assertTrue(Gate::forUser($admin)->allows('reconciliation.view'));
        $this->assertTrue(Gate::forUser($admin)->allows('settlement.approve'));
        $this->assertFalse(Gate::forUser($customer)->allows('reconciliation.view'));
        $this->assertFalse(Gate::forUser($customer)->allows('settlement.approve'));
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function seedCash(string $currency, string $amount): LedgerAccount
    {
        return LedgerAccount::create([
            'account_type' => 'asset', 'currency_code' => $currency,
            'name' => 'Platform Cash', 'is_system' => true, 'balance' => $amount,
        ]);
    }

    private function seedLiabilityAccount(string $currency, string $name, string $balance): LedgerAccount
    {
        // Balance starts at 0 — the funding posting below is what creates the
        // projection, so projected == derived when the ledger is consistent.
        return LedgerAccount::create([
            'account_type' => 'liability', 'currency_code' => $currency,
            'name' => $name, 'balance' => '0',
        ]);
    }

    private function fund(LedgerAccount $liability, string $amount): void
    {
        $cash = LedgerAccount::create([
            'account_type' => 'asset', 'currency_code' => $liability->currency_code,
            'name' => 'Platform Cash', 'is_system' => true, 'balance' => '0',
        ]);
        $this->ledger->post(
            [
                ['account_id' => $cash->id, 'side' => 'debit', 'amount' => $amount],
                ['account_id' => $liability->id, 'side' => 'credit', 'amount' => $amount],
            ],
            'opening_balance', 'OPEN-'.$liability->id
        );
    }

    private function seedTx(string $at, string $providerRef, string $amount, string $provider = 'ledger', string $countryIso3 = 'NGA'): Transaction
    {
        $sender = User::factory()->create(['name' => 'Sender']);
        $receiver = User::factory()->create(['name' => 'Receiver']);

        $tx = Transaction::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'receiver_name' => 'Receiver',
            'type' => 'deposit',
            'source_currency' => 'NGN',
            'destination_currency' => 'NGN',
            'source_amount' => $amount,
            'destination_amount' => $amount,
            'exchange_rate' => '1.0000',
            'status' => 'settled',
            'description' => 'Recon fixture',
            'provider' => $provider,
            'provider_reference' => $providerRef,
            'country_code' => $countryIso3,
            'reference' => 'KP-REC-'.strtoupper(substr(uniqid('', true), -8)),
        ]);

        // created_at is not mass-assignable — force-fill the fixture date.
        $tx->forceFill(['created_at' => $at, 'updated_at' => $at])->save();

        return $tx->fresh();
    }

    private function seedTxWithAttempt(string $at, string $providerRef, string $internalAmount, string $attemptAmount, string $provider = 'ledger', string $countryIso3 = 'NGA'): Transaction
    {
        $tx = $this->seedTx($at, $providerRef, $internalAmount, $provider, $countryIso3);

        DB::table('transaction_attempts')->insert([
            'transaction_id' => $tx->id,
            'attempt_number' => 1,
            'provider' => $provider,
            'provider_reference' => $providerRef,
            'amount' => $attemptAmount,
            'status' => 'success',
            'created_at' => $at,
            'updated_at' => $at,
        ]);

        return $tx;
    }

    private function seedAttemptOnly(string $at, string $providerRef, string $amount): void
    {
        $sender = User::factory()->create(['name' => 'S']);
        $receiver = User::factory()->create(['name' => 'R']);
        $tx = Transaction::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'receiver_name' => 'R',
            'type' => 'deposit',
            'source_currency' => 'NGN',
            'destination_currency' => 'NGN',
            'source_amount' => $amount,
            'destination_amount' => $amount,
            'exchange_rate' => '1.0000',
            'status' => 'settled',
            'description' => 'Recon provider-only',
            'provider' => 'ledger',
            'country_code' => 'NGA',
            'reference' => 'KP-REC-'.strtoupper(substr(uniqid('', true), -8)),
        ]);
        $tx->forceFill(['created_at' => $at, 'updated_at' => $at])->save();

        // Provider confirmation for a reference that does NOT exist on any tx.
        DB::table('transaction_attempts')->insert([
            'transaction_id' => $tx->id,
            'attempt_number' => 1,
            'provider' => 'ledger',
            'provider_reference' => $providerRef,
            'amount' => $amount,
            'status' => 'success',
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }

    private function assertLedgerBalanced(string $currency): void
    {
        $rows = DB::table('ledger_entries')
            ->selectRaw('currency_code, side, SUM(amount) as total')
            ->groupBy('currency_code', 'side')
            ->get()
            ->groupBy('currency_code');

        foreach ($rows as $cur => $legs) {
            if ($cur !== $currency) {
                continue;
            }
            $debits = (string) ($legs->firstWhere('side', 'debit')->total ?? 0);
            $credits = (string) ($legs->firstWhere('side', 'credit')->total ?? 0);
            $this->assertSame($debits, $credits, "Σ debits must equal Σ credits for {$cur}");
        }
    }
}
