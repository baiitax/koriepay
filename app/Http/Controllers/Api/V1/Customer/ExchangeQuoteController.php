<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Domain\Customer\CustomerWalletService;
use App\Domain\Customer\ExchangeQuoteService;
use App\Domain\Customer\Exceptions\ExchangePairUnavailableException;
use App\Models\CustomerWallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

/**
 * Server-authoritative exchange quotes (§91). The customer submits wallet
 * identifiers + a source amount; the backend computes the rate, fee and
 * destination. The frontend never supplies a rate.
 */
class ExchangeQuoteController extends Controller
{
    public function __construct(
        private readonly CustomerWalletService $wallets,
        private readonly ExchangeQuoteService $quotes,
    ) {
    }

    public function quote(Request $request): JsonResponse
    {
        $validated = Validator::make($request->all(), [
            'from_wallet_id' => ['required', 'string'],
            'to_wallet_id' => ['required', 'string', 'different:from_wallet_id'],
            'source_amount' => ['required', 'string'],
        ])->validate();

        $user = $request->user();
        $from = $this->resolve($user->id, $validated['from_wallet_id']);
        $to = $this->resolve($user->id, $validated['to_wallet_id']);

        try {
            $quote = $this->quotes->createQuote($user, $from, $to, $validated['source_amount']);
        } catch (ExchangePairUnavailableException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'human_message' => 'Currency exchange is temporarily unavailable. Your wallet balances remain accessible.',
            ], 422);
        } catch (\DomainException|\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'quote_id' => $quote->quote_id,
                'source_amount' => (string) $quote->source_amount,
                'source_currency' => $quote->from_currency,
                'destination_amount' => (string) $quote->destination_amount,
                'destination_currency' => $quote->to_currency,
                'exchange_rate' => (string) $quote->exchange_rate,
                'exchange_fee' => (string) $quote->exchange_fee,
                'total_debit' => (string) $quote->total_debit,
                'created_at' => $quote->created_at?->toIso8601String(),
                'expires_at' => $quote->expires_at?->toIso8601String(),
            ],
        ], 201);
    }

    private function resolve(int $userId, string $walletId): CustomerWallet
    {
        $wallet = CustomerWallet::query()->where('wallet_id', $walletId)->first();

        if ($wallet === null || (int) $wallet->user_id !== $userId) {
            abort(404, 'Wallet not found.');
        }

        return $wallet;
    }
}
