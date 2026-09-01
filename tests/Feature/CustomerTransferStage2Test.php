<?php

namespace Tests\Feature;

use App\Domain\Accounting\LedgerAccount;
use App\Domain\Accounting\LedgerService;
use App\Domain\Customer\CustomerTransferService;
use App\Domain\Customer\CustomerWalletService;
use App\Domain\Customer\Exceptions\CustomerBankingException;
use App\Models\CustomerWallet;
use App\Models\CustomerWalletConfig;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\FxRatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CUSTOMER BANKING APP — Stage 2 (Money movement).
 *
 * Guards that matter here:
 *   - send/receive are IDOR-safe (ownership checked server-side);
 *   - fee = server-computed from the sender's country/currency config and
 *     credited to Platform Revenue in the SAME ledger posting as principal;
 *   - idempotency: one key ⇒ exactly one transaction + one ledger posting;
 *   - every guard (self-transfer, balance, amount format, recipient
 *     eligibility, daily limit) fails before any money moves;
 *   - the state machine (Phase 5) reports honest outcomes to the UI.
 */
class CustomerTransferStage2Test extends TestCase
{
    use RefreshDatabase;

    private CustomerWalletService $wallets;
    private CustomerTransferService $transfers;
    private LedgerService $ledger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wallets = app(CustomerWalletService::class);
        $this->transfers = app(CustomerTransferService::class);
        $this->ledger = app(LedgerService::class);

        $this->seed(FxRatesSeeder::class);

        // Ops enables secondary wallets so both demo customers can hold XOF
        // and NGN (dev parity with CustomerBankingSeeder).
        CustomerWalletConfig::updateOrCreate(
            ['country_iso2' => 'NE', 'currency_code' => 'NGN'],
            ['is_available' => 1, 'is_primary_default' => 0, 'min_kyc_tier' => 1,
                'daily_send_limit' => '1000000.00', 'daily_exchange_limit' => '1000000.00',
                'exchange_fee_flat' => '500', 'exchange_fee_rate' => '0.5000', 'transfer_fee_flat' => '0', 'transfer_fee_rate' => '0'],
        );
        CustomerWalletConfig::updateOrCreate(
            ['country_iso2' => 'NG', 'currency_code' => 'XOF'],
            ['is_available' => 1, 'is_primary_default' => 0, 'min_kyc_tier' => 1,
                'daily_send_limit' => '1000000.00', 'daily_exchange_limit' => '1000000.00',
                'exchange_fee_flat' => '500', 'exchange_fee_rate' => '0.5000', 'transfer_fee_flat' => '0', 'transfer_fee_rate' => '0'],
        );
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function niger(): User
    {
        return User::factory()->create([
            'name' => 'Aminatou Niger', 'country_code' => 'NER', 'kyc_tier' => 2,
            'kyc_status' => 'verified', 'phone_number' => '+22790000001', 'is_active' => true,
        ]);
    }

    private function nigeria(): User
    {
        return User::factory()->create([
            'name' => 'Chidi Lagos', 'country_code' => 'NGA', 'kyc_tier' => 2,
            'kyc_status' => 'verified', 'phone_number' => '+23490000002', 'is_active' => true,
        ]);
    }

    private function fund(User $user, string $currency, string $amount): void
    {
        $asset = LedgerAccount::firstOrCreate(
            ['account_type' => 'asset', 'currency_code' => $currency],
            ['name' => 'Platform Cash '.$currency, 'is_system' => true, 'balance' => '0']
        );

        $this->wallets->provision($user);

        $liability = LedgerAccount::query()
            ->where('owner_type', 'user')->where('owner_id', $user->id)
            ->where('currency_code', $currency)->firstOrFail();

        $this->ledger->post(
            [
                ['account_id' => $asset->id, 'side' => 'debit', 'amount' => $amount],
                ['account_id' => $liability->id, 'side' => 'credit', 'amount' => $amount],
            ],
            'deposit', null, 'test funding', 'fund-'.$user->id.'-'.$currency,
        );
    }

    private function walletOf(User $user, string $currency): CustomerWallet
    {
        return CustomerWallet::where('user_id', $user->id)->where('currency_code', $currency)->firstOrFail();
    }

    private function revenue(string $currency): string
    {
        return (string) LedgerAccount::where('account_type', 'income')
            ->where('name', 'Platform Revenue '.$currency)->first()?->balance ?? '0';
    }

    // ── Recipient resolution ──────────────────────────────────────────────

    public function test_recipient_resolves_by_koriepay_id_and_phone(): void
    {
        $recipient = $this->nigeria();

        $this->assertSame($recipient->id, $this->transfers->resolveRecipient($recipient->koriepay_id)->id);
        $this->assertSame($recipient->id, $this->transfers->resolveRecipient(strtolower($recipient->koriepay_id))->id);
        $this->assertSame($recipient->id, $this->transfers->resolveRecipient('+23490000002')->id);
        $this->assertSame($recipient->id, $this->transfers->resolveRecipient('23490000002')->id);
    }

    public function test_recipient_not_found_throws(): void
    {
        $this->expectException(CustomerBankingException::class);
        $this->expectExceptionMessage('No KoriePay account');
        $this->transfers->resolveRecipient('KP-UNKNOWN');
    }

    public function test_inactive_recipient_rejected(): void
    {
        $inactive = User::factory()->create(['country_code' => 'NGA', 'is_active' => false]);

        $this->expectException(CustomerBankingException::class);
        $this->expectExceptionMessage('not active');
        $this->transfers->resolveRecipient($inactive->koriepay_id);
    }

    // ── Preview (no money moves) ──────────────────────────────────────────

    public function test_preview_computes_fee_and_never_persists(): void
    {
        $sender = $this->niger();
        $recipient = $this->nigeria();
        $this->fund($sender, 'XOF', '50000');
        $this->fund($recipient, 'XOF', '10000');

        $beforeCount = Transaction::count();

        $preview = $this->transfers->preview($sender, $this->walletOf($sender, 'XOF'), $recipient->koriepay_id, '10000');

        $this->assertSame('50', $preview['fee']);            // NE XOF flat fee
        $this->assertSame('10050', $preview['total_debit']);
        $this->assertSame($recipient->koriepay_id, $preview['recipient']['koriepay_id']);
        $this->assertSame('2349 *** 0002', $preview['recipient']['phone_masked']); // recipient is Chidi (Nigeria)
        $this->assertSame($recipient->name, $preview['recipient']['name']);
        $this->assertSame($beforeCount, Transaction::count()); // nothing persisted
    }

    // ── Send (idempotent, through the state machine) ──────────────────────

    public function test_send_moves_ledger_with_fee_and_marks_settled(): void
    {
        $sender = $this->niger();
        $recipient = $this->nigeria();
        $this->fund($sender, 'XOF', '50000');
        $this->fund($recipient, 'XOF', '10000');

        $senderBefore = $this->wallets->balanceDetails($sender, $this->walletOf($sender, 'XOF'))['available'];
        $recipientBefore = $this->wallets->balanceDetails($recipient, $this->walletOf($recipient, 'XOF'))['available'];
        $revenueBefore = $this->revenue('XOF');

        $tx = $this->transfers->send($sender, $this->walletOf($sender, 'XOF'), $recipient->koriepay_id, '10000', 's2-1', 'Hello');

        $this->assertSame('SETTLED', strtoupper((string) $tx->status));
        $this->assertSame('10000.00', (string) $tx->source_amount); // decimal:2 cast
        $this->assertSame('50.00', (string) $tx->fee_charged);
        $this->assertSame('ledger', $tx->provider);

        $senderAfter = $this->wallets->balanceDetails($sender, $this->walletOf($sender, 'XOF'))['available'];
        $recipientAfter = $this->wallets->balanceDetails($recipient, $this->walletOf($recipient, 'XOF'))['available'];

        $this->assertSame(bcsub($senderBefore, '10050', 2), $senderAfter);      // principal + fee
        $this->assertSame(bcadd($recipientBefore, '10000', 2), $recipientAfter); // principal only
        $this->assertSame(bcadd($revenueBefore, '50', 2), $this->revenue('XOF')); // fee to revenue
    }

    public function test_send_is_idempotent_under_same_key(): void
    {
        $sender = $this->niger();
        $recipient = $this->nigeria();
        $this->fund($sender, 'XOF', '50000');
        $this->fund($recipient, 'XOF', '10000');

        $first = $this->transfers->send($sender, $this->walletOf($sender, 'XOF'), $recipient->koriepay_id, '10000', 's2-2');
        $before = $this->wallets->balanceDetails($sender, $this->walletOf($sender, 'XOF'))['available'];

        $second = $this->transfers->send($sender, $this->walletOf($sender, 'XOF'), $recipient->koriepay_id, '10000', 's2-2');

        $this->assertSame($first->id, $second->id);
        $this->assertSame('SETTLED', strtoupper((string) $second->status));
        $this->assertSame($before, $this->wallets->balanceDetails($sender, $this->walletOf($sender, 'XOF'))['available']);
        $this->assertSame(1, Transaction::where('idempotency_key', 's2-2')->count());
    }

    // ── Guards (all before money moves) ───────────────────────────────────

    public function test_self_transfer_rejected(): void
    {
        $sender = $this->niger();
        $this->fund($sender, 'XOF', '50000');

        $this->expectException(CustomerBankingException::class);
        $this->expectExceptionMessage('yourself');
        $this->transfers->send($sender, $this->walletOf($sender, 'XOF'), $sender->koriepay_id, '1000', 's2-3');
    }

    public function test_insufficient_balance_including_fee_rejected(): void
    {
        $sender = $this->niger();
        $recipient = $this->nigeria();
        $this->fund($sender, 'XOF', '10000'); // 10,000 but fee makes total 10,050 > 10,000
        $this->fund($recipient, 'XOF', '1000');

        $this->expectException(CustomerBankingException::class);
        $this->expectExceptionMessage('Insufficient balance');
        $this->transfers->send($sender, $this->walletOf($sender, 'XOF'), $recipient->koriepay_id, '10000', 's2-4');
    }

    public function test_xof_decimal_amount_rejected(): void
    {
        $sender = $this->niger();
        $recipient = $this->nigeria();
        $this->fund($sender, 'XOF', '50000');
        $this->fund($recipient, 'XOF', '1000');

        $this->expectException(CustomerBankingException::class);
        $this->expectExceptionMessage('valid amount');
        $this->transfers->send($sender, $this->walletOf($sender, 'XOF'), $recipient->koriepay_id, '100.50', 's2-5');
    }

    public function test_recipient_without_supported_currency_rejected(): void
    {
        $sender = $this->niger();
        $recipient = $this->nigeria();
        $this->fund($sender, 'XOF', '50000');

        // NG recipient cannot hold XOF in this scenario — disable the secondary config.
        CustomerWalletConfig::where('country_iso2', 'NG')->where('currency_code', 'XOF')->update(['is_available' => 0]);

        $this->expectException(CustomerBankingException::class);
        $this->expectExceptionMessage('cannot receive');
        $this->transfers->send($sender, $this->walletOf($sender, 'XOF'), $recipient->koriepay_id, '1000', 's2-6');
    }

    public function test_daily_send_limit_enforced(): void
    {
        $sender = $this->niger();
        $recipient = $this->nigeria();
        $this->fund($sender, 'XOF', '50000');
        $this->fund($recipient, 'XOF', '1000');

        CustomerWalletConfig::where('country_iso2', 'NE')->where('currency_code', 'XOF')
            ->update(['daily_send_limit' => '5000.00']);

        $this->expectException(CustomerBankingException::class);
        $this->expectExceptionMessage('daily send limit');
        $this->transfers->send($sender, $this->walletOf($sender, 'XOF'), $recipient->koriepay_id, '10000', 's2-7');
    }

    public function test_foreign_wallet_rejected(): void
    {
        $owner = $this->niger();
        $attacker = $this->nigeria();
        $this->fund($owner, 'XOF', '50000');

        $this->expectException(CustomerBankingException::class);
        $this->expectExceptionMessage('Wallet not found');
        $this->transfers->send($attacker, $this->walletOf($owner, 'XOF'), $owner->koriepay_id, '1000', 's2-8');
    }

    // ── Status ownership ──────────────────────────────────────────────────

    public function test_status_rejects_foreign_reference(): void
    {
        $sender = $this->niger();
        $recipient = $this->nigeria();
        $this->fund($sender, 'XOF', '50000');
        $this->fund($recipient, 'XOF', '1000');

        $tx = $this->transfers->send($sender, $this->walletOf($sender, 'XOF'), $recipient->koriepay_id, '1000', 's2-9');

        // Recipient CAN read it (they are a party).
        $this->assertSame('SETTLED', strtoupper((string) $this->transfers->status($recipient, $tx->reference)->status));

        // A third party cannot.
        $stranger = User::factory()->create(['country_code' => 'NER']);
        try {
            $this->transfers->status($stranger, $tx->reference);
            $this->fail('Expected CustomerBankingException');
        } catch (CustomerBankingException $e) {
            $this->assertSame(403, $e->getCode());
        }
    }

    /** Laravel caches the resolved auth user per guard within a test — real
     *  requests are a fresh app each time, so this only matters in tests that
     *  switch users between requests. */
    private function forgetGuards(): void
    {
        $this->app['auth']->forgetGuards();
    }

    // ── Outcome mapping ───────────────────────────────────────────────────

    public function test_outcome_mapping(): void
    {
        $settled = new Transaction(['status' => 'SETTLED']);
        $failed = new Transaction(['status' => 'FAILED']);
        $processing = new Transaction(['status' => 'PROCESSING']);
        $unknown = new Transaction(['status' => 'WEIRD']);

        $this->assertSame('success', $this->transfers->outcomeFor($settled));
        $this->assertSame('failed', $this->transfers->outcomeFor($failed));
        $this->assertSame('processing', $this->transfers->outcomeFor($processing));
        $this->assertSame('unknown', $this->transfers->outcomeFor($unknown));
    }

    // ── Receive identity ──────────────────────────────────────────────────

    public function test_receive_identity_shape(): void
    {
        $user = $this->nigeria();

        $identity = $this->transfers->receiveIdentity($user);

        $this->assertNotEmpty($user->koriepay_id);
        $this->assertStringStartsWith('KP-', $user->koriepay_id);
        $this->assertSame('koriepay://pay/'.$user->koriepay_id, $identity['qr_payload']);
        $this->assertSame($user->name, $identity['name']);
        $this->assertArrayHasKey('phone_masked', $identity);
        $this->assertNotEmpty($identity['wallets']);
    }

    // ── API surface ───────────────────────────────────────────────────────

    public function test_send_api_preview_and_idempotent_send(): void
    {
        $sender = $this->niger();
        $recipient = $this->nigeria();
        $this->fund($sender, 'XOF', '50000');
        $this->fund($recipient, 'XOF', '1000');

        $token = $sender->createToken('s2')->plainTextToken;
        $walletId = $this->walletOf($sender, 'XOF')->wallet_id;

        // Preview — no money moves.
        $this->withToken($token)
            ->postJson('/api/v1/customer/transfers/preview', [
                'from_wallet_id' => $walletId, 'recipient' => $recipient->koriepay_id, 'amount' => '10000',
            ])
            ->assertOk()
            ->assertJsonPath('data.fee', '50')
            ->assertJsonPath('data.total_debit', '10050')
            ->assertJsonPath('data.recipient.name', $recipient->name);

        $this->assertSame(0, Transaction::where('sender_id', $sender->id)->count());

        // Send — 201 + settled.
        $this->withToken($token)
            ->postJson('/api/v1/customer/transfers', [
                'from_wallet_id' => $walletId, 'recipient' => $recipient->koriepay_id, 'amount' => '10000', 'note' => 'api send',
            ], ['Idempotency-Key' => 's2-api-1'])
            ->assertCreated()
            ->assertJsonPath('data.outcome', 'success')
            ->assertJsonPath('data.fee', '50')
            ->assertJsonPath('data.recipient.koriepay_id', $recipient->koriepay_id);

        // Replay — same reference, no double charge.
        $this->withToken($token)
            ->postJson('/api/v1/customer/transfers', [
                'from_wallet_id' => $walletId, 'recipient' => $recipient->koriepay_id, 'amount' => '10000',
            ], ['Idempotency-Key' => 's2-api-1'])
            ->assertCreated()
            ->assertJsonPath('data.outcome', 'success');
        $this->assertSame(1, Transaction::where('idempotency_key', 's2-api-1')->count());
    }

    public function test_send_api_requires_idempotency_key(): void
    {
        $sender = $this->niger();
        $recipient = $this->nigeria();
        $this->fund($sender, 'XOF', '50000');
        $this->fund($recipient, 'XOF', '1000');

        $this->withToken($sender->createToken('s2')->plainTextToken)
            ->postJson('/api/v1/customer/transfers', [
                'from_wallet_id' => $this->walletOf($sender, 'XOF')->wallet_id,
                'recipient' => $recipient->koriepay_id, 'amount' => '1000',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('error', 'idempotency_key_required');
    }

    public function test_status_api_and_foreign_access(): void
    {
        $sender = $this->niger();
        $recipient = $this->nigeria();
        $this->fund($sender, 'XOF', '50000');
        $this->fund($recipient, 'XOF', '1000');

        $tx = $this->transfers->send($sender, $this->walletOf($sender, 'XOF'), $recipient->koriepay_id, '1000', 's2-api-2');
        $token = $sender->createToken('s2')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/customer/transfers/'.$tx->reference)
            ->assertOk()
            ->assertJsonPath('data.outcome', 'success')
            ->assertJsonPath('data.reference', $tx->reference);

        $stranger = User::factory()->create(['country_code' => 'NER']);
        $this->forgetGuards();
        $this->withToken($stranger->createToken('s2')->plainTextToken)
            ->getJson('/api/v1/customer/transfers/'.$tx->reference)
            ->assertStatus(403);
    }

    public function test_receive_api_returns_qr(): void
    {
        $user = $this->nigeria();

        $this->withToken($user->createToken('s2')->plainTextToken)
            ->getJson('/api/v1/customer/receive')
            ->assertOk()
            ->assertJsonPath('data.koriepay_id', $user->koriepay_id)
            ->assertJsonStructure(['data' => ['koriepay_id', 'name', 'qr_payload', 'qr_svg_data_uri']])
            ->assertJsonPath('data.qr_payload', 'koriepay://pay/'.$user->koriepay_id);
    }

    // ── Shell journeys (§128) ─────────────────────────────────────────────

    public function test_pay_hub_shell_renders_hub_send_and_receive(): void
    {
        $user = User::factory()->withRole('customer')->create([
            'name' => 'Aminatou Niger', 'country_code' => 'NER', 'kyc_tier' => 2,
            'kyc_status' => 'verified', 'phone_number' => '+22790000001', 'is_active' => true,
        ]);
        $this->fund($user, 'XOF', '50000');

        $this->actingAs($user)
            ->get(route('customer.pay'))
            ->assertOk()
            ->assertSee('KoriePay');

        $this->actingAs($user)
            ->get(route('customer.pay', ['view' => 'send']))
            ->assertOk()
            ->assertSee('Recipient');

        $this->actingAs($user)
            ->get(route('customer.pay', ['view' => 'receive']))
            ->assertOk()
            ->assertSee('Scan to pay me')
            ->assertSee($user->koriepay_id)
            ->assertSee('koriepay://pay/');
    }
}
