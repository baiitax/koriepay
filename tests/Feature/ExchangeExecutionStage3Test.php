<?php

namespace Tests\Feature;

use App\Domain\Accounting\LedgerAccount;
use App\Domain\Accounting\LedgerService;
use App\Domain\Customer\CustomerWalletService;
use App\Domain\Customer\ExchangeQuoteService;
use App\Domain\Customer\Exceptions\CustomerBankingException;
use App\Domain\Customer\Exceptions\ExchangeQuoteExpiredException;
use App\Domain\Customer\TransactionReceiptService;
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
 * CUSTOMER BANKING APP — Stage 3 (Exchange execution).
 *
 * Guards that matter here:
 *   - execute() revalidates EVERYTHING server-side before money moves
 *     (ownership, status, expiry, pair/KYC, daily limit, balance);
 *   - the ledger posting is per-currency balanced (source: wallet+revenue,
 *     destination: float+wallet);
 *   - the quote is consumed exactly once (locked row + status=used);
 *   - receipts are HMAC-signed server-side and verifiable.
 */
class ExchangeExecutionStage3Test extends TestCase
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

        // Ops enables the secondary wallet so NE users can hold NGN (parity
        // with the dev CustomerBankingSeeder).
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

    private function cash(string $currency): string
    {
        return (string) LedgerAccount::where('account_type', 'asset')
            ->where('name', 'Platform Cash')->where('currency_code', $currency)
            ->first()?->balance ?? '0';
    }

    private function revenue(string $currency): string
    {
        return (string) LedgerAccount::where('account_type', 'income')
            ->where('name', 'Platform Revenue '.$currency)->first()?->balance ?? '0';
    }

    // ── Execution success ─────────────────────────────────────────────────

    public function test_execute_moves_money_across_currencies_and_marks_quote_used(): void
    {
        $user = $this->niger();
        $this->fund($user, 'XOF', '500000');
        $this->fund($user, 'NGN', '500000');

        $xofBefore = $this->wallets->balanceDetails($user, $this->xof($user))['available'];
        $ngnBefore = $this->wallets->balanceDetails($user, $this->ngn($user))['available'];
        $cashXofBefore = $this->cash('XOF');
        $cashNgnBefore = $this->cash('NGN');
        $revBefore = $this->revenue('XOF');

        $quote = $this->quotes->createQuote($user, $this->xof($user), $this->ngn($user), '100000');
        // fee = 500 flat + 0.5% of 100000 = 500 + 500 = 1000 XOF; dest = 250000 NGN

        $tx = $this->quotes->execute($user, $quote, 'ex-1');

        $this->assertSame('SETTLED', strtoupper((string) $tx->status));
        $this->assertSame('exchange', $tx->type);
        $this->assertSame('XOF', $tx->source_currency);
        $this->assertSame('NGN', $tx->destination_currency);
        $this->assertSame('100000.00', (string) $tx->source_amount);
        $this->assertSame('250000.00', (string) $tx->destination_amount);
        $this->assertSame('2.5000', (string) $tx->exchange_rate);
        $this->assertSame('1000.00', (string) $tx->fee_charged);

        // Balances: XOF −101000, NGN +250000.
        $this->assertSame(bcsub($xofBefore, '101000', 2), $this->wallets->balanceDetails($user, $this->xof($user))['available']);
        $this->assertSame(bcadd($ngnBefore, '250000', 2), $this->wallets->balanceDetails($user, $this->ngn($user))['available']);

        // Platform float (asset): CR in source, DR in destination; revenue took
        // the fee. Ledger stays balanced per currency (DR wallet 101000 XOF =
        // CR cash 100000 + CR revenue 1000; DR cash 250000 NGN = CR wallet 250000).
        $this->assertSame(bcsub($cashXofBefore, '100000', 2), $this->cash('XOF'));
        $this->assertSame(bcadd($cashNgnBefore, '250000', 2), $this->cash('NGN'));
        $this->assertSame(bcadd($revBefore, '1000', 2), $this->revenue('XOF'));

        // Quote consumed.
        $this->assertSame(ExchangeQuote::STATUS_USED, $quote->fresh()->status);
    }

    public function test_execute_is_idempotent_under_same_key(): void
    {
        $user = $this->niger();
        $this->fund($user, 'XOF', '500000');
        $this->fund($user, 'NGN', '500000');

        $quote = $this->quotes->createQuote($user, $this->xof($user), $this->ngn($user), '100000');
        $first = $this->quotes->execute($user, $quote, 'ex-2');
        $xofAfter = $this->wallets->balanceDetails($user, $this->xof($user))['available'];

        // Replay with the SAME idempotency key — returns the original row,
        // no double movement (even though the quote is now used).
        $second = $this->quotes->execute($user, $quote, 'ex-2');

        $this->assertSame($first->id, $second->id);
        $this->assertSame($xofAfter, $this->wallets->balanceDetails($user, $this->xof($user))['available']);
    }

    // ── Revalidation failures (all BEFORE money moves) ────────────────────

    public function test_expired_quote_rejected(): void
    {
        $user = $this->niger();
        $this->fund($user, 'XOF', '500000');
        $this->fund($user, 'NGN', '500000');

        $quote = $this->quotes->createQuote($user, $this->xof($user), $this->ngn($user), '100000');
        Carbon::setTestNow(now()->addSeconds(61));
        try {
            $this->quotes->execute($user, $quote, 'ex-3');
            $this->fail('Expected ExchangeQuoteExpiredException');
        } catch (ExchangeQuoteExpiredException) {
            $this->assertSame(ExchangeQuote::STATUS_EXPIRED, $quote->fresh()->status);
        } finally {
            Carbon::setTestNow();
        }

        $this->assertSame('500000.00', $this->wallets->balanceDetails($user, $this->xof($user))['available']);
    }

    public function test_foreign_quote_rejected(): void
    {
        $owner = $this->niger('+22790000001');
        $attacker = $this->niger('+22790000002');
        $this->fund($owner, 'XOF', '50000');
        $this->fund($owner, 'NGN', '10000');
        $this->fund($attacker, 'XOF', '500000');

        $quote = $this->quotes->createQuote($owner, $this->xof($owner), $this->ngn($owner), '100000');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('does not belong');
        $this->quotes->execute($attacker, $quote, 'ex-4');
    }

    public function test_insufficient_balance_rejected(): void
    {
        $user = $this->niger();
        $this->fund($user, 'XOF', '10000'); // quote needs 101,000
        $this->fund($user, 'NGN', '500000');

        $quote = $this->quotes->createQuote($user, $this->xof($user), $this->ngn($user), '100000');

        $this->expectException(CustomerBankingException::class);
        $this->expectExceptionMessage('Insufficient balance');
        $this->quotes->execute($user, $quote, 'ex-5');

        $this->assertSame(ExchangeQuote::STATUS_CREATED, $quote->fresh()->status);
    }

    public function test_daily_limit_requard_rejected_at_execute(): void
    {
        $user = $this->niger();
        $this->fund($user, 'XOF', '500000');
        $this->fund($user, 'NGN', '500000');

        $quote = $this->quotes->createQuote($user, $this->xof($user), $this->ngn($user), '100000');
        // Ops lowers the limit after the quote was issued — execute re-checks.
        CustomerWalletConfig::where('country_iso2', 'NE')->where('currency_code', 'XOF')
            ->update(['daily_exchange_limit' => '5000.00']);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('daily exchange limit');
        $this->quotes->execute($user, $quote, 'ex-6');
    }

    public function test_pair_disabled_between_quote_and_execute_rejected(): void
    {
        $user = $this->niger();
        $this->fund($user, 'XOF', '500000');
        $this->fund($user, 'NGN', '500000');

        $quote = $this->quotes->createQuote($user, $this->xof($user), $this->ngn($user), '100000');
        CustomerWalletConfig::where('country_iso2', 'NE')->where('currency_code', 'NGN')
            ->update(['is_available' => 0]);

        $this->expectException(\App\Domain\Customer\Exceptions\ExchangePairUnavailableException::class);
        $this->quotes->execute($user, $quote, 'ex-7');
    }

    // ── Receipt integrity ─────────────────────────────────────────────────

    public function test_receipt_is_hmac_signed_and_verifiable(): void
    {
        $user = $this->niger();
        $this->fund($user, 'XOF', '500000');
        $this->fund($user, 'NGN', '500000');

        $quote = $this->quotes->createQuote($user, $this->xof($user), $this->ngn($user), '100000');
        $tx = $this->quotes->execute($user, $quote, 'ex-8');

        $service = app(TransactionReceiptService::class);
        $receipt = $service->receipt($tx);

        $this->assertSame($tx->reference, $receipt['reference']);
        $this->assertTrue($receipt['verified']);
        $this->assertSame('HMAC-SHA256', $receipt['hash_algo']);
        $this->assertTrue($service->verify($tx, $receipt['hash']));
        $this->assertFalse($service->verify($tx, str_repeat('0', 64)));

        // Tampering with a receipt field breaks the hash.
        $tampered = $tx;
        $tampered->source_amount = '99999';
        $this->assertFalse($service->verify($tampered, $receipt['hash']));
    }

    // ── API surface ───────────────────────────────────────────────────────

    public function test_execute_api_end_to_end(): void
    {
        $user = $this->niger();
        $this->fund($user, 'XOF', '500000');
        $this->fund($user, 'NGN', '500000');
        $token = $user->createToken('s3')->plainTextToken;

        $quote = $this->quotes->createQuote($user, $this->xof($user), $this->ngn($user), '100000');

        // Missing key → 422.
        $this->withToken($token)
            ->postJson('/api/v1/customer/exchange/execute', ['quote_id' => $quote->quote_id])
            ->assertUnprocessable()
            ->assertJsonPath('error', 'idempotency_key_required');

        // Success → 201 with receipt.
        $this->withToken($token)
            ->postJson('/api/v1/customer/exchange/execute', ['quote_id' => $quote->quote_id],
                ['Idempotency-Key' => 'ex-api-1'])
            ->assertCreated()
            ->assertJsonPath('data.outcome', 'success')
            ->assertJsonPath('data.destination.amount', '250000.00')
            ->assertJsonPath('data.destination.currency', 'NGN')
            ->assertJsonPath('data.receipt.hash_algo', 'HMAC-SHA256')
            ->assertJsonStructure(['data' => ['reference', 'status', 'outcome', 'source', 'destination', 'exchange_rate', 'fee', 'quote_id', 'receipt' => ['hash', 'verified']]]);

        // Replay → same reference.
        $this->withToken($token)
            ->postJson('/api/v1/customer/exchange/execute', ['quote_id' => $quote->quote_id],
                ['Idempotency-Key' => 'ex-api-1'])
            ->assertCreated();
    }

    public function test_execute_api_rejects_expired_quote_with_409(): void
    {
        $user = $this->niger();
        $this->fund($user, 'XOF', '500000');
        $this->fund($user, 'NGN', '500000');
        $token = $user->createToken('s3')->plainTextToken;

        $quote = $this->quotes->createQuote($user, $this->xof($user), $this->ngn($user), '100000');
        Carbon::setTestNow(now()->addSeconds(61));
        try {
            $this->withToken($token)
                ->postJson('/api/v1/customer/exchange/execute', ['quote_id' => $quote->quote_id],
                    ['Idempotency-Key' => 'ex-api-2'])
                ->assertStatus(409)
                ->assertJsonPath('error', 'quote_expired');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_execute_api_rejects_foreign_quote(): void
    {
        $owner = $this->niger('+22790000001');
        $attacker = $this->niger('+22790000002');
        $this->fund($owner, 'XOF', '50000');
        $this->fund($owner, 'NGN', '10000');
        $this->fund($attacker, 'XOF', '500000');
        $this->fund($attacker, 'NGN', '500000');

        $quote = $this->quotes->createQuote($owner, $this->xof($owner), $this->ngn($owner), '100000');

        $this->withToken($attacker->createToken('s3')->plainTextToken)
            ->postJson('/api/v1/customer/exchange/execute', ['quote_id' => $quote->quote_id],
                ['Idempotency-Key' => 'ex-api-3'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Quote does not belong to this customer.');
    }

    /** The legacy same-currency path must be untouched. */
    public function test_transfer_still_works_after_exchange_additions(): void
    {
        $user = $this->niger();
        $this->fund($user, 'XOF', '500000');
        $recipient = User::factory()->create(['country_code' => 'NER', 'kyc_tier' => 2, 'is_active' => true]);
        $this->fund($recipient, 'XOF', '1000');

        $tx = app(\App\Domain\Customer\CustomerTransferService::class)->send(
            $user, $this->xof($user), $recipient->koriepay_id, '10000', 'legacy-ok'
        );

        $this->assertSame('SETTLED', strtoupper((string) $tx->status));
        $this->assertSame('transfer', $tx->type);
    }
}
