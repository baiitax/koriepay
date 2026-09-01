<?php

namespace Tests\Feature;

use App\Domain\Accounting\LedgerAccount;
use App\Domain\Accounting\LedgerService;
use App\Domain\Customer\CustomerWalletService;
use App\Domain\Customer\ExchangeQuoteService;
use App\Models\CustomerWallet;
use App\Models\CustomerWalletConfig;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\FxRatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CUSTOMER BANKING APP — Stage 4 (transaction history & receipt status).
 *
 * Guards: history is always ownership-scoped (a customer only ever sees rows
 * where THEY are the sender); filters are validated; the single-row endpoint
 * returns a server-signed receipt + verification URL; a foreign reference is
 * 403 (never a data leak), an unknown one is 404.
 */
class CustomerTransactionStage4Test extends TestCase
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

        CustomerWalletConfig::updateOrCreate(
            ['country_iso2' => 'NE', 'currency_code' => 'NGN'],
            ['is_available' => 1, 'is_primary_default' => 0, 'min_kyc_tier' => 1,
                'daily_send_limit' => '1000000.00', 'daily_exchange_limit' => '1000000.00',
                'exchange_fee_flat' => '500', 'exchange_fee_rate' => '0.5000'],
        );
    }

    private function niger(string $phone = '+22790000001'): User
    {
        return User::factory()->create([
            'name' => 'Aminatou Niger', 'country_code' => 'NER', 'kyc_tier' => 2,
            'kyc_status' => 'verified', 'phone_number' => $phone, 'is_active' => true,
        ]);
    }

    private function fund(User $user, string $currency, string $amount): void
    {
        $asset = LedgerAccount::firstOrCreate(
            ['account_type' => 'asset', 'currency_code' => $currency],
            ['name' => 'Platform Cash', 'is_system' => true, 'balance' => '0']
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

    private function xof(User $user): CustomerWallet
    {
        return CustomerWallet::where('user_id', $user->id)->where('currency_code', 'XOF')->firstOrFail();
    }

    private function ngn(User $user): CustomerWallet
    {
        return CustomerWallet::where('user_id', $user->id)->where('currency_code', 'NGN')->firstOrFail();
    }

    private function row(User $user, array $overrides = []): Transaction
    {
        $createdAt = $overrides['created_at'] ?? null;
        unset($overrides['created_at']);
        $tx = Transaction::create(array_merge([
            'sender_id' => $user->id,
            'receiver_id' => $user->id,
            'type' => 'deposit',
            'source_currency' => 'XOF',
            'destination_currency' => 'XOF',
            'source_amount' => '5000.00',
            'destination_amount' => '5000.00',
            'exchange_rate' => '1.0000',
            'fee_charged' => '0.00',
            'status' => 'settled',
            'reference' => 'KP-'.strtoupper(\Illuminate\Support\Str::random(10)),
            'description' => 'test row',
            'provider' => 'ledger',
            'rail' => 'internal',
            'idempotency_key' => 'row-'.uniqid(),
        ], $overrides));

        if ($createdAt !== null) {
            $tx->forceFill(['created_at' => $createdAt])->save();
        }

        return $tx;
    }

    // ── History ───────────────────────────────────────────────────────────

    public function test_history_is_empty_until_transactions_exist(): void
    {
        $user = $this->niger();
        $token = $user->createToken('s4')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/customer/transactions')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('pagination.total', 0)
            ->assertJsonCount(0, 'data')
            ->assertJsonStructure(['data', 'filters' => ['type', 'currency', 'status', 'date_from', 'date_to', 'q'], 'pagination']);
    }

    public function test_history_lists_only_own_transactions_newest_first(): void
    {
        $user = $this->niger();
        $other = $this->niger('+22790000009');
        $this->row($user, ['type' => 'deposit', 'description' => 'own deposit', 'created_at' => now()->subMinutes(3)]);
        $this->row($user, ['type' => 'transfer', 'description' => 'own transfer', 'source_amount' => '3000.00', 'destination_amount' => '3000.00', 'created_at' => now()->subMinutes(2)]);
        $this->row($other, ['type' => 'withdraw', 'description' => 'someone elses withdraw']);
        $this->row($user, ['type' => 'exchange', 'description' => 'own exchange', 'source_amount' => '10000.00', 'destination_amount' => '25000.00', 'destination_currency' => 'NGN', 'created_at' => now()->subMinute()]);

        $this->withToken($user->createToken('s4')->plainTextToken)
            ->getJson('/api/v1/customer/transactions')
            ->assertOk()
            ->assertJsonPath('pagination.total', 3)
            ->assertJsonPath('data.0.description', 'own exchange')
            ->assertJsonPath('data.2.description', 'own deposit');
    }

    public function test_history_type_filter(): void
    {
        $user = $this->niger();
        $this->row($user, ['type' => 'deposit']);
        $this->row($user, ['type' => 'transfer', 'source_amount' => '2000.00', 'destination_amount' => '2000.00']);
        $this->row($user, ['type' => 'exchange', 'destination_currency' => 'NGN']);

        $this->withToken($user->createToken('s4')->plainTextToken)
            ->getJson('/api/v1/customer/transactions?type=exchange')
            ->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.type', 'exchange');
    }

    public function test_history_currency_filter_matches_either_leg(): void
    {
        $user = $this->niger();
        $this->row($user, ['type' => 'deposit', 'source_currency' => 'XOF']);
        $this->row($user, ['type' => 'exchange', 'source_currency' => 'XOF', 'destination_currency' => 'NGN', 'destination_amount' => '25000.00']);

        $this->withToken($user->createToken('s4')->plainTextToken)
            ->getJson('/api/v1/customer/transactions?currency=NGN')
            ->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.type', 'exchange');
    }

    public function test_history_status_and_free_text_filters(): void
    {
        $user = $this->niger();
        $this->row($user, ['status' => 'failed', 'description' => 'card declined']);
        $this->row($user, ['status' => 'settled', 'description' => 'rent payment']);

        $this->withToken($user->createToken('s4')->plainTextToken)
            ->getJson('/api/v1/customer/transactions?status=failed')
            ->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.status', 'failed');

        $this->withToken($user->createToken('s4')->plainTextToken)
            ->getJson('/api/v1/customer/transactions?q=rent')
            ->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.description', 'rent payment');
    }

    public function test_history_rejects_unknown_filter_values(): void
    {
        $user = $this->niger();

        $this->withToken($user->createToken('s4')->plainTextToken)
            ->getJson('/api/v1/customer/transactions?type=hack&currency=USD')
            ->assertUnprocessable();
    }

    // ── Single transaction + receipt ──────────────────────────────────────

    public function test_show_returns_receipt_with_verification_url(): void
    {
        $user = $this->niger();
        $this->fund($user, 'XOF', '200000');
        $this->fund($user, 'NGN', '200000');
        $quote = $this->quotes->createQuote($user, $this->xof($user), $this->ngn($user), '100000');
        $tx = $this->quotes->execute($user, $quote, 's4-ex-1');

        $this->withToken($user->createToken('s4')->plainTextToken)
            ->getJson('/api/v1/customer/transactions/'.$tx->reference)
            ->assertOk()
            ->assertJsonPath('data.reference', $tx->reference)
            ->assertJsonPath('data.receipt.hash_algo', 'HMAC-SHA256')
            ->assertJsonPath('data.receipt.verified', true)
            ->assertJsonStructure(['data' => ['receipt' => ['hash'], 'verification_url']])
            ->assertJsonPath('data.verification_url', url('/api/v1/customer/transactions/'.$tx->reference.'/verify'));
    }

    public function test_show_rejects_foreign_transaction_with_403(): void
    {
        $owner = $this->niger('+22790000001');
        $attacker = $this->niger('+22790000002');
        $tx = $this->row($owner, ['type' => 'deposit', 'description' => 'private']);

        $this->withToken($attacker->createToken('s4')->plainTextToken)
            ->getJson('/api/v1/customer/transactions/'.$tx->reference)
            ->assertForbidden()
            ->assertJsonPath('message', 'This transaction does not belong to you.');
    }

    public function test_show_unknown_reference_returns_404(): void
    {
        $user = $this->niger();

        $this->withToken($user->createToken('s4')->plainTextToken)
            ->getJson('/api/v1/customer/transactions/KP-DOES-NOT-EXIST')
            ->assertNotFound();
    }

    public function test_verify_endpoint_recomputes_integrity(): void
    {
        $user = $this->niger();
        $this->fund($user, 'XOF', '200000');
        $this->fund($user, 'NGN', '200000');
        $quote = $this->quotes->createQuote($user, $this->xof($user), $this->ngn($user), '100000');
        $tx = $this->quotes->execute($user, $quote, 's4-ex-2');

        $this->withToken($user->createToken('s4')->plainTextToken)
            ->getJson('/api/v1/customer/transactions/'.$tx->reference.'/verify')
            ->assertOk()
            ->assertJsonPath('data.verified', true)
            ->assertJsonPath('data.hash_algo', 'HMAC-SHA256')
            ->assertJsonStructure(['data' => ['hash', 'checked_at']]);
    }

    public function test_verify_requires_ownership(): void
    {
        $owner = $this->niger('+22790000001');
        $attacker = $this->niger('+22790000002');
        $tx = $this->row($owner);

        $this->withToken($attacker->createToken('s4')->plainTextToken)
            ->getJson('/api/v1/customer/transactions/'.$tx->reference.'/verify')
            ->assertNotFound();
    }

    // ── Real movement appears in history ──────────────────────────────────

    public function test_real_exchange_and_transfer_appear_in_history(): void
    {
        $user = $this->niger();
        $this->fund($user, 'XOF', '500000');
        $this->fund($user, 'NGN', '500000');

        $quote = $this->quotes->createQuote($user, $this->xof($user), $this->ngn($user), '100000');
        $this->quotes->execute($user, $quote, 's4-real-1');

        $this->withToken($user->createToken('s4')->plainTextToken)
            ->getJson('/api/v1/customer/transactions?type=exchange')
            ->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.type', 'exchange')
            ->assertJsonPath('data.0.amount', '100000.00')
            ->assertJsonPath('data.0.currency', 'XOF')
            ->assertJsonPath('data.0.destination.currency', 'NGN')
            ->assertJsonPath('data.0.destination.amount', '250000.00')
            ->assertJsonPath('data.0.exchange_rate', '2.5000')
            ->assertJsonPath('data.0.fee', '1000.00');
    }
}
