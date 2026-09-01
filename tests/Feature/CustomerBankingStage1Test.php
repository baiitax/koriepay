<?php

namespace Tests\Feature;

use App\Domain\Accounting\LedgerAccount;
use App\Domain\Accounting\LedgerService;
use App\Domain\Customer\CustomerWalletService;
use App\Domain\Customer\ExchangeQuoteService;
use App\Domain\Customer\Exceptions\ExchangePairUnavailableException;
use App\Domain\Customer\Exceptions\ExchangeQuoteExpiredException;
use App\Models\CustomerWallet;
use App\Models\CustomerWalletConfig;
use App\Models\ExchangeQuote;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\FxRatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * CUSTOMER BANKING APP — Stage 1 (Foundation).
 *
 * Guards that matter here:
 *   - eligibility is country/KYC-config driven (§75), never blanket wallets;
 *   - balances are LEDGER-sourced; `wallets.balance` is never read (§82);
 *   - pending is derived from real in-flight transactions;
 *   - quotes are server-authoritative and expire (§39, §91);
 *   - ownership checks on every customer API (IDOR-safe, §87/§88).
 */
class CustomerBankingStage1Test extends TestCase
{
    use RefreshDatabase;

    private CustomerWalletService $wallets;
    private ExchangeQuoteService $quotes;
    private LedgerService $ledger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wallets = app(CustomerWalletService::class);
        $this->quotes = app(ExchangeQuoteService::class);
        $this->ledger = app(LedgerService::class);

        $this->seed(FxRatesSeeder::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function nigerUser(): User
    {
        return User::factory()->create([
            'name' => 'Aminatou Niger',
            'country_code' => 'NER',
            'kyc_tier' => 2,
            'kyc_status' => 'verified',
            'phone_number' => '+22790000001',
        ]);
    }

    private function nigeriaUser(): User
    {
        return User::factory()->create([
            'name' => 'Chidi Lagos',
            'country_code' => 'NGA',
            'kyc_tier' => 2,
            'kyc_status' => 'verified',
            'phone_number' => '+23490000002',
        ]);
    }

    /** Ops enables a secondary wallet (data-driven, §75). */
    private function enableSecondary(string $country, string $currency, array $overrides = []): void
    {
        CustomerWalletConfig::updateOrCreate(
            ['country_iso2' => $country, 'currency_code' => $currency],
            array_merge([
                'is_available' => 1,
                'is_primary_default' => 0,
                'min_kyc_tier' => 1,
                'daily_exchange_limit' => '1000000.00',
                'exchange_fee_flat' => '500',
                'exchange_fee_rate' => '0.5000',
            ], $overrides)
        );
    }

    private function fund(User $user, string $currency, string $amount): void
    {
        $asset = LedgerAccount::firstOrCreate(
            ['account_type' => 'asset', 'currency_code' => $currency],
            ['name' => 'Platform Cash '.$currency, 'is_system' => true, 'balance' => '0']
        );

        $this->wallets->provision($user);

        $liability = LedgerAccount::query()
            ->where('owner_type', 'user')
            ->where('owner_id', $user->id)
            ->where('currency_code', $currency)
            ->firstOrFail();

        $this->ledger->post(
            [
                ['account_id' => $asset->id, 'side' => 'debit', 'amount' => $amount],
                ['account_id' => $liability->id, 'side' => 'credit', 'amount' => $amount],
            ],
            'deposit',
            null,
            'test funding',
            'fund-'.$user->id.'-'.$currency,
        );
    }

    private function xofWallet(User $user): CustomerWallet
    {
        return CustomerWallet::where('user_id', $user->id)->where('currency_code', 'XOF')->firstOrFail();
    }

    private function ngnWallet(User $user): CustomerWallet
    {
        return CustomerWallet::where('user_id', $user->id)->where('currency_code', 'NGN')->firstOrFail();
    }

    // ── §75 Country-aware provisioning ────────────────────────────────────

    public function test_provisioning_is_country_aware(): void
    {
        $ne = $this->nigerUser();
        $ng = $this->nigeriaUser();

        $neWallets = collect($this->wallets->provision($ne));
        $ngWallets = collect($this->wallets->provision($ng));

        // Migration seed: NE → XOF primary; NG → NGN primary. No secondary
        // wallets are auto-enabled.
        $this->assertSame(['XOF'], $neWallets->pluck('currency_code')->all());
        $this->assertTrue($neWallets->firstWhere('currency_code', 'XOF')->is_primary);

        $this->assertSame(['NGN'], $ngWallets->pluck('currency_code')->all());
        $this->assertTrue($ngWallets->firstWhere('currency_code', 'NGN')->is_primary);
    }

    public function test_secondary_wallet_appears_when_ops_enables_config(): void
    {
        $this->enableSecondary('NE', 'NGN');
        $ne = $this->nigerUser();

        $wallets = collect($this->wallets->provision($ne));

        $this->assertEqualsCanonicalizing(['XOF', 'NGN'], $wallets->pluck('currency_code')->all());
        $this->assertFalse($wallets->firstWhere('currency_code', 'NGN')->is_primary);
    }

    public function test_wallet_skipped_when_kyc_tier_below_minimum(): void
    {
        $this->enableSecondary('NE', 'NGN', ['min_kyc_tier' => 3]);
        $lowTier = User::factory()->create(['country_code' => 'NER', 'kyc_tier' => 1]);

        $wallets = $this->wallets->provision($lowTier);

        $this->assertSame(['XOF'], array_column($wallets, 'currency_code'));
    }

    // ── §82 Ledger-sourced balances ───────────────────────────────────────

    public function test_customer_wallets_table_never_stores_balance(): void
    {
        $columns = collect(DB::select('PRAGMA table_info(customer_wallets)'))
            ->pluck('name')
            ->all();

        $this->assertNotContains('balance', $columns);
        $this->assertContains('ledger_account_id', $columns);
    }

    public function test_balance_comes_from_ledger_projection(): void
    {
        $user = $this->nigerUser();
        $this->fund($user, 'XOF', '150000');

        $details = $this->wallets->balanceDetails($user, $this->xofWallet($user));

        $this->assertSame('150000.00', $details['available']);
        $this->assertSame('0.00', $details['pending']);
        $this->assertSame('150000.00', $details['total']);
        $this->assertSame('XOF', $details['currency']);
        $this->assertSame(0, $details['minor_units']);
    }

    public function test_pending_is_derived_from_in_flight_transactions(): void
    {
        $user = $this->nigerUser();
        $other = $this->nigeriaUser();
        $this->fund($user, 'XOF', '50000');

        // Outgoing in-flight: reduces available.
        Transaction::create([
            'sender_id' => $user->id,
            'receiver_id' => $other->id,
            'receiver_name' => 'Chidi Lagos',
            'type' => 'transfer',
            'source_currency' => 'XOF',
            'destination_currency' => 'XOF',
            'source_amount' => '10000',
            'destination_amount' => '10000',
            'exchange_rate' => '1.0000',
            'status' => 'pending',
        ]);

        // Incoming in-flight: increases available.
        Transaction::create([
            'sender_id' => $other->id,
            'sender_name' => 'Chidi Lagos',
            'receiver_id' => $user->id,
            'type' => 'transfer',
            'source_currency' => 'XOF',
            'destination_currency' => 'XOF',
            'source_amount' => '20000',
            'destination_amount' => '20000',
            'exchange_rate' => '1.0000',
            'status' => 'processing',
        ]);

        $details = $this->wallets->balanceDetails($user, $this->xofWallet($user));

        $this->assertSame('50000.00', $details['available']);
        $this->assertSame('10000.00', $details['pending']);   // 20000 in − 10000 out
        $this->assertSame('60000.00', $details['total']);
    }

    public function test_portfolio_summary_is_clearly_an_estimate(): void
    {
        $this->enableSecondary('NE', 'NGN');
        $user = $this->nigerUser();
        $this->fund($user, 'XOF', '150000');
        $this->fund($user, 'NGN', '75000');

        $summary = $this->wallets->portfolioSummary($user, 'XOF');

        // 150,000 XOF + 75,000 NGN × 0.4 (NGN→XOF active rate) = 180,000 XOF.
        $this->assertSame('180000.00', $summary['total']);
        $this->assertTrue($summary['is_estimate']);
        $this->assertSame('XOF', $summary['currency']);
    }

    // ── §39/§91 Authoritative expiring quotes ─────────────────────────────

    public function test_quote_uses_authoritative_rate_fee_and_expiry(): void
    {
        $this->enableSecondary('NE', 'NGN');
        $user = $this->nigerUser();
        $this->wallets->provision($user);

        $quote = $this->quotes->createQuote($user, $this->xofWallet($user), $this->ngnWallet($user), '100000');

        $this->assertSame(ExchangeQuote::STATUS_CREATED, $quote->status);
        $this->assertStringStartsWith('Q-', $quote->quote_id);
        $this->assertSame('2.500000', (string) $quote->exchange_rate);   // seeded fx_rates
        $this->assertSame('250000.00', (string) $quote->destination_amount); // 100000 × 2.5
        $this->assertSame('1000.00', (string) $quote->exchange_fee);      // 500 flat + 0.5%
        $this->assertSame('101000.00', (string) $quote->total_debit);
        $this->assertSame('XOF', $quote->from_currency);
        $this->assertSame('NGN', $quote->to_currency);
        $this->assertTrue($quote->expires_at->isFuture());
        $this->assertEquals(60, $quote->created_at->diffInSeconds($quote->expires_at));
    }

    public function test_quote_fails_when_pair_disabled_by_ops(): void
    {
        // Wallet exists, but ops disabled the pair config → guard rejects.
        $this->enableSecondary('NE', 'NGN');
        $user = $this->nigerUser();
        $this->wallets->provision($user);
        CustomerWalletConfig::where('country_iso2', 'NE')->where('currency_code', 'NGN')->update(['is_available' => 0]);

        $this->expectException(ExchangePairUnavailableException::class);
        $this->quotes->createQuote($user, $this->xofWallet($user), $this->ngnWallet($user), '100000');
    }

    public function test_quote_rejected_when_rate_missing(): void
    {
        $this->enableSecondary('NE', 'NGN');
        DB::table('fx_rates')->where('base_currency', 'XOF')->where('target_currency', 'NGN')->update(['is_active' => false]);
        $user = $this->nigerUser();
        $this->wallets->provision($user);

        $this->expectException(ExchangePairUnavailableException::class);
        $this->quotes->createQuote($user, $this->xofWallet($user), $this->ngnWallet($user), '100000');
    }

    public function test_zero_minor_currency_amount_format(): void
    {
        $this->enableSecondary('NE', 'NGN');
        $user = $this->nigerUser();
        $this->wallets->provision($user);

        $this->expectException(\InvalidArgumentException::class);
        $this->quotes->createQuote($user, $this->xofWallet($user), $this->ngnWallet($user), '100000.50'); // XOF has no minor units
    }

    public function test_daily_exchange_limit_enforced(): void
    {
        $this->enableSecondary('NE', 'NGN');
        $user = $this->nigerUser();
        $this->wallets->provision($user);

        // Limit is on the SOURCE currency config (the currency being debited).
        CustomerWalletConfig::where('country_iso2', 'NE')->where('currency_code', 'XOF')
            ->update(['daily_exchange_limit' => '5000.00']);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('daily exchange limit');
        $this->quotes->createQuote($user, $this->xofWallet($user), $this->ngnWallet($user), '100000');
    }

    public function test_quote_expires_and_cannot_be_revalidated(): void
    {
        $this->enableSecondary('NE', 'NGN');
        $user = $this->nigerUser();
        $this->wallets->provision($user);
        $quote = $this->quotes->createQuote($user, $this->xofWallet($user), $this->ngnWallet($user), '100000');

        $this->quotes->revalidate($user, $quote); // fresh → OK

        Carbon::setTestNow(now()->addSeconds(61));
        try {
            $this->quotes->revalidate($user, $quote);
            $this->fail('Expected ExchangeQuoteExpiredException');
        } catch (ExchangeQuoteExpiredException $e) {
            $this->assertSame(ExchangeQuote::STATUS_EXPIRED, $quote->fresh()->status);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_quote_cannot_be_used_twice(): void
    {
        $this->enableSecondary('NE', 'NGN');
        $user = $this->nigerUser();
        $this->wallets->provision($user);
        $quote = $this->quotes->createQuote($user, $this->xofWallet($user), $this->ngnWallet($user), '100000');
        $this->quotes->markUsed($quote);

        $this->expectException(ExchangeQuoteExpiredException::class);
        $this->quotes->revalidate($user, $quote);
    }

    // ── Masking / ownership (IDOR) ────────────────────────────────────────

    public function test_phone_is_masked(): void
    {
        $user = $this->nigerUser();

        $this->assertSame('2279 *** 0001', $this->wallets->maskPhone($user->phone_number));
        $this->assertSame('***', $this->wallets->maskPhone('12'));
    }

    public function test_foreign_wallet_is_rejected_by_service(): void
    {
        $owner = $this->nigerUser();
        $attacker = $this->nigeriaUser();
        $this->fund($owner, 'XOF', '50000');

        $this->expectException(\DomainException::class);
        $this->wallets->balanceDetails($attacker, $this->xofWallet($owner));
    }

    // ── API surface (Sanctum + ownership + shape) ─────────────────────────

    public function test_dashboard_endpoint_shape(): void
    {
        $this->enableSecondary('NE', 'NGN');
        $user = $this->nigerUser();
        $this->fund($user, 'XOF', '150000');

        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/customer/dashboard')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.profile.phone', '2279 *** 0001')
            ->assertJsonPath('data.profile.kyc_status', 'verified')
            ->assertJsonPath('data.selected_wallet.currency', 'XOF')
            ->assertJsonPath('data.selected_wallet.available_balance', '150000.00')
            ->assertJsonStructure([
                'data' => [
                    'profile' => ['name', 'phone', 'country', 'kyc_status', 'kyc_tier'],
                    'selected_wallet' => ['wallet_id', 'currency', 'available_balance', 'pending_balance'],
                    'wallets',
                    'portfolio_summary' => ['total', 'currency', 'is_estimate'],
                    'quick_services',
                    'recent_transactions',
                    'notifications',
                    'security_status',
                    'system_status' => ['ledger_provider'],
                    'exchange_availability',
                    'data_freshness',
                ],
            ]);

        $walletsJson = $this->getJson('/api/v1/customer/wallets')->json('data.wallets');
        $this->assertEqualsCanonicalizing(['XOF', 'NGN'], array_column($walletsJson, 'currency'));
        foreach ($walletsJson as $walletJson) {
            // No wallet row ever exposes a stored balance field (§82).
            $this->assertArrayNotHasKey('balance', $walletJson);
            $this->assertArrayHasKey('available_balance', $walletJson);
            $this->assertArrayHasKey('pending_balance', $walletJson);
        }
    }

    public function test_wallet_endpoints(): void
    {
        $user = $this->nigerUser();
        $this->fund($user, 'XOF', '50000');
        $wallet = $this->xofWallet($user);

        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/customer/wallets')
            ->assertOk()
            ->assertJsonCount(1, 'data.wallets');

        $this->withToken($token)
            ->getJson('/api/v1/customer/wallets/'.$wallet->wallet_id)
            ->assertOk()
            ->assertJsonPath('data.currency', 'XOF');

        $this->withToken($token)
            ->getJson('/api/v1/customer/wallets/'.$wallet->wallet_id.'/balance')
            ->assertOk()
            ->assertJsonPath('data.available', '50000.00');
    }

    public function test_idor_wallet_access_returns_404(): void
    {
        $owner = $this->nigerUser();
        $this->fund($owner, 'XOF', '50000');

        // Attacker (Nigeria) has their own XOF wallet to use as the TO side.
        $this->enableSecondary('NG', 'XOF');
        $attacker = $this->nigeriaUser();
        $this->wallets->provision($attacker);

        $token = $attacker->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/customer/wallets/'.$this->xofWallet($owner)->wallet_id)
            ->assertNotFound();

        $this->withToken($token)
            ->getJson('/api/v1/customer/wallets/'.$this->xofWallet($owner)->wallet_id.'/balance')
            ->assertNotFound();

        $this->withToken($token)
            ->postJson('/api/v1/customer/exchange/quote', [
                'from_wallet_id' => $this->xofWallet($owner)->wallet_id,
                'to_wallet_id' => $this->xofWallet($attacker)->wallet_id,
                'source_amount' => '100',
            ])
            ->assertNotFound();
    }

    public function test_quote_endpoint_requires_auth_and_returns_quote(): void
    {
        $this->enableSecondary('NE', 'NGN');
        $user = $this->nigerUser();
        $this->wallets->provision($user);
        $this->fund($user, 'XOF', '150000');
        $token = $user->createToken('test')->plainTextToken;

        // Unauthenticated → 401.
        $this->postJson('/api/v1/customer/exchange/quote', [
            'from_wallet_id' => $this->xofWallet($user)->wallet_id,
            'to_wallet_id' => $this->ngnWallet($user)->wallet_id,
            'source_amount' => '100000',
        ])->assertUnauthorized();

        // Authenticated → 201 with server-authoritative quote.
        $this->withToken($token)
            ->postJson('/api/v1/customer/exchange/quote', [
                'from_wallet_id' => $this->xofWallet($user)->wallet_id,
                'to_wallet_id' => $this->ngnWallet($user)->wallet_id,
                'source_amount' => '100000',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.exchange_rate', '2.500000')
            ->assertJsonPath('data.destination_amount', '250000.00')
            ->assertJsonPath('data.exchange_fee', '1000.00')
            ->assertJsonPath('data.total_debit', '101000.00')
            ->assertJsonStructure(['data' => ['quote_id', 'source_amount', 'source_currency', 'destination_amount', 'destination_currency', 'exchange_rate', 'exchange_fee', 'total_debit', 'created_at', 'expires_at']]);
    }

    public function test_customer_shell_routes_render(): void
    {
        $user = User::factory()->withRole('customer')->create([
            'name' => 'Chidi Lagos',
            'country_code' => 'NGA',
            'kyc_tier' => 2,
            'kyc_status' => 'verified',
            'phone_number' => '+23490000002',
        ]);
        $this->fund($user, 'NGN', '100000');

        $this->actingAs($user)
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('KoriePay');

        $this->actingAs($user)
            ->get(route('customer.pay'))
            ->assertOk();
    }
}
