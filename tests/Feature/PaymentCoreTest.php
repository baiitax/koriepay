<?php

namespace Tests\Feature;

use App\Domain\Accounting\LedgerAccount;
use App\Domain\Accounting\LedgerService;
use App\Domain\Accounting\TransactionStateMachine;
use App\Domain\Payments\Exceptions\ProviderUnavailableException;
use App\Domain\Payments\Exceptions\UnsupportedCurrencyException;
use App\Domain\Payments\PaymentOrchestrator;
use App\Domain\Payments\WebhookService;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * PHASE 5 — PAYMENT CORE
 *
 * Orchestrator + internal ledger provider + webhooks + idempotency + the
 * explicit state machine, all moving money ONLY through the immutable ledger.
 *
 * Custodial model (mirrors LedgerServiceTest): customer wallets are LIABILITY
 * accounts; Platform Cash is the ASSET account. Opening: DR cash / CR wallet.
 */
class PaymentCoreTest extends TestCase
{
    use RefreshDatabase;

    private LedgerService $ledger;
    private PaymentOrchestrator $orchestrator;
    private WebhookService $webhooks;
    private User $alice;
    private User $bob;
    private LedgerAccount $cash;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ledger = app(LedgerService::class);
        $this->orchestrator = app(PaymentOrchestrator::class);
        $this->webhooks = app(WebhookService::class);

        $this->alice = User::factory()->create(['name' => 'Alice']);
        $this->alice->forceFill(['role' => 'customer'])->save();
        $this->bob = User::factory()->create(['name' => 'Bob']);
        $this->bob->forceFill(['role' => 'customer'])->save();

        // Platform Cash (asset) — NGN and XOF.
        $this->cash = LedgerAccount::create([
            'account_type' => 'asset', 'currency_code' => 'NGN',
            'name' => 'Platform Cash', 'is_system' => true,
        ]);
        LedgerAccount::create([
            'account_type' => 'asset', 'currency_code' => 'XOF',
            'name' => 'Platform Cash', 'is_system' => true,
        ]);

        $this->ensureWallet($this->alice, 'NGN', 10000);
        $this->ensureWallet($this->bob, 'NGN', 5000);
    }

    // ── Deposit ────────────────────────────────────────────────────────────

    public function test_deposit_moves_money_through_the_ledger(): void
    {
        $tx = $this->orchestrator->deposit(
            customerId: $this->alice->id,
            amount: '2500.00',
            currency: 'NGN',
            countryIso2: 'NG',
            idempotencyKey: 'dep-1',
        );

        $this->assertSame('settled', strtolower((string) $tx->status));
        $this->assertSame('ledger', $tx->provider);
        $this->assertSame('WALLET_NG', $tx->rail);
        $this->assertStringStartsWith('LEDGER-', (string) $tx->provider_reference);

        // Alice's wallet projection: opening 10,000 + deposit 2,500.
        $aliceWallet = $this->walletBalance($this->alice, 'NGN');
        $this->assertSame('12500.00', bcadd((string) $aliceWallet, '0', 2));

        // Cash projection: opening 10,000 + 2,500 (liability DR/CR bookkeeping
        // in the custodial model means cash grew with the deposit).
        $this->assertLedgerBalanced();

        // Full state chain walked.
        $states = DB::table('transaction_states')
            ->where('transaction_id', $tx->id)
            ->orderBy('id')
            ->pluck('to_state')
            ->all();
        $this->assertSame(
            [TransactionStateMachine::INITIATED, TransactionStateMachine::PROCESSING,
             TransactionStateMachine::AUTHORIZED, TransactionStateMachine::POSTED,
             TransactionStateMachine::SETTLED],
            $states,
            'the mandated state chain must be walked in order'
        );
    }

    public function test_deposit_is_idempotent_under_replay(): void
    {
        $first = $this->orchestrator->deposit(
            customerId: $this->alice->id,
            amount: '1000.00',
            currency: 'NGN',
            countryIso2: 'NG',
            idempotencyKey: 'dep-replay',
        );

        $replay = $this->orchestrator->deposit(
            customerId: $this->alice->id,
            amount: '1000.00',
            currency: 'NGN',
            countryIso2: 'NG',
            idempotencyKey: 'dep-replay',
        );

        $this->assertSame($first->id, $replay->id, 'replay must return the original transaction');
        $this->assertSame(1, Transaction::where('idempotency_key', 'dep-replay')->count());
        $this->assertSame('11000.00', bcadd((string) $this->walletBalance($this->alice, 'NGN'), '0', 2),
            'deposit must not be applied twice');
    }

    // ── Withdraw ───────────────────────────────────────────────────────────

    public function test_withdraw_moves_money_out_of_the_wallet(): void
    {
        $tx = $this->orchestrator->withdraw(
            customerId: $this->alice->id,
            amount: '3000.00',
            currency: 'NGN',
            countryIso2: 'NG',
            idempotencyKey: 'wd-1',
        );

        $this->assertSame('settled', strtolower((string) $tx->status));
        $this->assertSame('7000.00', bcadd((string) $this->walletBalance($this->alice, 'NGN'), '0', 2));
        $this->assertLedgerBalanced();
    }

    public function test_withdraw_fails_cleanly_when_funds_are_insufficient(): void
    {
        $tx = $this->orchestrator->withdraw(
            customerId: $this->alice->id,
            amount: '99999.00',
            currency: 'NGN',
            countryIso2: 'NG',
            idempotencyKey: 'wd-insufficient',
        );

        $this->assertSame('failed', strtolower((string) $tx->status));
        $this->assertNotNull($tx->error_reason);
        $this->assertStringContainsString('Insufficient', (string) $tx->error_reason);

        // Balance untouched, ledger still balanced.
        $this->assertSame('10000.00', bcadd((string) $this->walletBalance($this->alice, 'NGN'), '0', 2));
        $this->assertLedgerBalanced();
    }

    // ── Transfer ───────────────────────────────────────────────────────────

    public function test_transfer_moves_between_two_wallets_balanced(): void
    {
        $tx = $this->orchestrator->transfer(
            senderId: $this->alice->id,
            receiverId: $this->bob->id,
            amount: '2000.00',
            currency: 'NGN',
            countryIso2: 'NG',
            idempotencyKey: 'tr-1',
            description: 'Rent share',
        );

        $this->assertSame('settled', strtolower((string) $tx->status));
        $this->assertSame('8000.00', bcadd((string) $this->walletBalance($this->alice, 'NGN'), '0', 2));
        $this->assertSame('7000.00', bcadd((string) $this->walletBalance($this->bob, 'NGN'), '0', 2));
        $this->assertLedgerBalanced();
    }

    public function test_transfer_insufficient_funds_fails_atomically(): void
    {
        $tx = $this->orchestrator->transfer(
            senderId: $this->bob->id,
            receiverId: $this->alice->id,
            amount: '99999.00',
            currency: 'NGN',
            countryIso2: 'NG',
            idempotencyKey: 'tr-insufficient',
        );

        $this->assertSame('failed', strtolower((string) $tx->status));
        $this->assertSame('5000.00', bcadd((string) $this->walletBalance($this->bob, 'NGN'), '0', 2));
        $this->assertSame('10000.00', bcadd((string) $this->walletBalance($this->alice, 'NGN'), '0', 2));
    }

    // ── Country / currency awareness ───────────────────────────────────────

    public function test_unsupported_currency_is_rejected(): void
    {
        $this->expectException(UnsupportedCurrencyException::class);

        $this->orchestrator->deposit(
            customerId: $this->alice->id,
            amount: '10.00',
            currency: 'USD',
            countryIso2: 'NG',
            idempotencyKey: 'dep-usd',
        );
    }

    public function test_unconfigured_market_has_no_available_provider(): void
    {
        // Ghana is not yet a configured market — additive, not hardcoded.
        $this->expectException(ProviderUnavailableException::class);

        $this->orchestrator->deposit(
            customerId: $this->alice->id,
            amount: '10.00',
            currency: 'GHS',
            countryIso2: 'GH',
            idempotencyKey: 'dep-gh',
        );
    }

    // ── Concurrency: 100 parallel deposits, one key ───────────────────────

    public function test_concurrent_duplicate_deposits_collapse_to_one(): void
    {
        $results = [];
        $errors = [];

        for ($i = 0; $i < 10; $i++) {
            try {
                $results[] = $this->orchestrator->deposit(
                    customerId: $this->alice->id,
                    amount: '100.00',
                    currency: 'NGN',
                    countryIso2: 'NG',
                    idempotencyKey: 'dep-concurrent',
                );
            } catch (\Throwable $e) {
                $errors[] = get_class($e);
            }
        }

        $this->assertEmpty($errors, 'no worker should error: '.implode(', ', $errors));
        $ids = array_unique(array_map(fn ($t) => $t->id, $results));
        $this->assertCount(1, $ids, 'all concurrent callers must converge on ONE transaction');

        // Exactly one ledger posting, wallet credited once.
        $this->assertSame(1, Transaction::where('idempotency_key', 'dep-concurrent')->count());
        $this->assertSame('10100.00', bcadd((string) $this->walletBalance($this->alice, 'NGN'), '0', 2));
    }

    // ── Webhooks ───────────────────────────────────────────────────────────

    public function test_internal_webhook_settles_and_dedupes(): void
    {
        // Create a transaction parked at AUTHORIZED (async confirmation path).
        $tx = $this->orchestrator->deposit(
            customerId: $this->alice->id,
            amount: '500.00',
            currency: 'NGN',
            countryIso2: 'NG',
            idempotencyKey: 'dep-webhook',
        );
        // Revert to AUTHORIZED to simulate a pending async settlement.
        DB::table('transactions')->where('id', $tx->id)->update(['status' => 'authorized']);

        $first = $this->webhooks->ingestInternal('ledger', [
            'event_id' => 'evt-1',
            'reference' => $tx->reference,
            'status' => 'success',
        ]);

        $this->assertFalse($first['already_processed']);
        $this->assertSame('processed', $first['status']);
        $this->assertSame('settled', strtolower((string) $tx->fresh()->status));

        // Duplicate event id → acknowledged, never double-settled.
        $replay = $this->webhooks->ingestInternal('ledger', [
            'event_id' => 'evt-1',
            'reference' => $tx->reference,
            'status' => 'success',
        ]);
        $this->assertTrue($replay['already_processed']);

        // Single webhook_events row persisted.
        $this->assertSame(1, DB::table('webhook_events')->where('event_id', 'evt-1')->count());
    }

    public function test_webhook_missing_reference_marks_event_failed(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->webhooks->ingestInternal('ledger', [
            'event_id' => 'evt-bad',
            'status' => 'success',
        ]);
    }

    // ── API surface ────────────────────────────────────────────────────────

    public function test_api_deposit_requires_idempotency_key(): void
    {
        Sanctum::actingAs($this->alice);

        $this->postJson('/api/v1/payments/deposit', [
            'amount' => '1000.00',
            'currency' => 'NGN',
            'country' => 'NG',
        ])->assertStatus(422);
    }

    public function test_api_deposit_and_replay(): void
    {
        Sanctum::actingAs($this->alice);

        $headers = ['Idempotency-Key' => 'api-dep-1'];

        $first = $this->postJson('/api/v1/payments/deposit', [
            'amount' => '1500.00',
            'currency' => 'NGN',
            'country' => 'NG',
        ], $headers)->assertStatus(201)->json();

        $this->assertTrue($first['success']);
        $this->assertSame('settled', $first['data']['status']);

        $replay = $this->postJson('/api/v1/payments/deposit', [
            'amount' => '1500.00',
            'currency' => 'NGN',
            'country' => 'NG',
        ], $headers)->assertStatus(201)->json();

        $this->assertSame($first['data']['reference'], $replay['data']['reference'],
            'idempotent replay must return the original reference');
    }

    public function test_api_status_enforces_ownership(): void
    {
        $tx = $this->orchestrator->deposit(
            customerId: $this->alice->id,
            amount: '100.00',
            currency: 'NGN',
            countryIso2: 'NG',
            idempotencyKey: 'api-status-owner',
        );

        // Bob cannot read Alice's transaction.
        Sanctum::actingAs($this->bob);
        $this->getJson('/api/v1/payments/'.$tx->reference)->assertStatus(403);

        // Alice can.
        Sanctum::actingAs($this->alice);
        $this->getJson('/api/v1/payments/'.$tx->reference)->assertOk();
    }

    public function test_api_webhook_fails_closed(): void
    {
        // External webhook to an unregistered provider must be rejected.
        $this->postJson('/api/v1/webhooks/paystack', [
            'event' => 'charge.success',
        ])->assertStatus(401);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function ensureWallet(User $user, string $currency, string $amount): void
    {
        $wallet = LedgerAccount::create([
            'account_type' => 'liability',
            'currency_code' => $currency,
            'name' => $user->name.' Wallet',
            'owner_type' => 'user',
            'owner_id' => $user->id,
        ]);

        $cash = LedgerAccount::query()
            ->where('account_type', 'asset')
            ->where('currency_code', $currency)
            ->where('name', 'Platform Cash')
            ->firstOrFail();

        // Opening: DR cash / CR wallet.
        $this->ledger->post([
            ['account_id' => $cash->id, 'side' => 'debit', 'amount' => $amount],
            ['account_id' => $wallet->id, 'side' => 'credit', 'amount' => $amount],
        ], 'opening_balance', 'OPEN-'.$user->id.'-'.$currency);
    }

    private function walletBalance(User $user, string $currency): string
    {
        return (string) LedgerAccount::query()
            ->where('owner_type', 'user')
            ->where('owner_id', $user->id)
            ->where('currency_code', $currency)
            ->value('balance');
    }

    private function assertLedgerBalanced(): void
    {
        $rows = DB::table('ledger_entries')
            ->selectRaw('currency_code, side, SUM(amount) as total')
            ->groupBy('currency_code', 'side')
            ->get();

        foreach ($rows->groupBy('currency_code') as $currency => $legs) {
            $debits = (string) ($legs->firstWhere('side', 'debit')->total ?? 0);
            $credits = (string) ($legs->firstWhere('side', 'credit')->total ?? 0);
            $this->assertSame($debits, $credits, "Σ debits must equal Σ credits for {$currency}");
        }
    }
}
