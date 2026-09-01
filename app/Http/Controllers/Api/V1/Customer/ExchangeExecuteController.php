<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Domain\Customer\ExchangeQuoteService;
use App\Domain\Customer\Exceptions\CustomerBankingException;
use App\Domain\Customer\Exceptions\ExchangePairUnavailableException;
use App\Domain\Customer\Exceptions\ExchangeQuoteExpiredException;
use App\Domain\Customer\TransactionReceiptService;
use App\Models\ExchangeQuote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

/**
 * CUSTOMER BANKING — exchange execution (§91/§92).
 *
 * POST /api/v1/customer/exchange/execute — requires quote_id + an
 * Idempotency-Key header. The quote is revalidated server-side (ownership,
 * status, expiry, pair/KYC, daily limit, balance) and only then does money
 * move through the Phase 5 state machine. The response includes the
 * authoritative receipt (HMAC-signed).
 */
class ExchangeExecuteController extends Controller
{
    public function __construct(
        private readonly ExchangeQuoteService $quotes,
        private readonly TransactionReceiptService $receipts,
    ) {
    }

    public function execute(Request $request): JsonResponse
    {
        $validated = Validator::make($request->all(), [
            'quote_id' => ['required', 'string'],
        ])->validate();

        $key = $request->header('Idempotency-Key', '');
        if ($key === '' || strlen($key) > 64) {
            return response()->json([
                'success' => false,
                'error' => 'idempotency_key_required',
                'message' => 'Idempotency-Key header is required (1–64 characters).',
            ], 422);
        }

        $quote = ExchangeQuote::where('quote_id', $validated['quote_id'])->first();

        if ($quote === null) {
            return response()->json(['success' => false, 'message' => 'Quote not found.'], 404);
        }

        try {
            $transaction = $this->quotes->execute($request->user(), $quote, $key);
        } catch (ExchangeQuoteExpiredException $e) {
            return response()->json(['success' => false, 'error' => 'quote_expired', 'message' => $e->getMessage()], 409);
        } catch (ExchangePairUnavailableException | CustomerBankingException | \DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $settled = strtoupper((string) $transaction->status) === 'SETTLED';

        return response()->json([
            'success' => $settled,
            'data' => [
                'reference' => $transaction->reference,
                'type' => $transaction->type,
                'status' => strtolower((string) $transaction->status),
                'outcome' => $settled ? 'success' : 'failed',
                'source' => [
                    'currency' => $transaction->source_currency,
                    'amount' => (string) $transaction->source_amount,
                ],
                'destination' => [
                    'currency' => $transaction->destination_currency,
                    'amount' => (string) $transaction->destination_amount,
                ],
                'exchange_rate' => (string) $transaction->exchange_rate,
                'fee' => (string) $transaction->fee_charged,
                'quote_id' => $validated['quote_id'],
                'created_at' => $transaction->created_at?->toIso8601String(),
                'receipt' => $this->receipts->receipt($transaction),
            ],
        ], $settled ? 201 : 200);
    }
}
