<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Domain\Customer\CustomerTransferService;
use App\Domain\Customer\Exceptions\CustomerBankingException;
use App\Models\CustomerWallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

/**
 * CUSTOMER BANKING — Stage 2 money movement API.
 *
 * POST /transfers/preview — server-side preview (no money moves).
 * POST /transfers         — idempotent send (Idempotency-Key header required).
 * GET  /transfers/{ref}   — ownership-checked status (honest outcome mapping).
 *
 * Every amount/recipient/limit decision is made server-side; the client only
 * supplies intent.
 */
class TransferController extends Controller
{
    public function __construct(private readonly CustomerTransferService $transfers)
    {
    }

    public function preview(Request $request): JsonResponse
    {
        $validated = $this->validateIntent($request);

        try {
            $preview = $this->transfers->preview(
                $request->user(),
                $this->resolveOwnedWallet($request->user()->id, $validated['from_wallet_id']),
                $validated['recipient'],
                $validated['amount'],
            );
        } catch (CustomerBankingException $e) {
            return $this->error($e);
        }

        return response()->json(['success' => true, 'data' => $preview]);
    }

    public function send(Request $request): JsonResponse
    {
        $validated = $this->validateIntent($request);
        $key = $request->header('Idempotency-Key', '');

        if ($key === '' || strlen($key) > 64) {
            return response()->json([
                'success' => false,
                'error' => 'idempotency_key_required',
                'message' => 'Idempotency-Key header is required (1–64 characters).',
            ], 422);
        }

        try {
            $transaction = $this->transfers->send(
                $request->user(),
                $this->resolveOwnedWallet($request->user()->id, $validated['from_wallet_id']),
                $validated['recipient'],
                $validated['amount'],
                $key,
                $validated['note'] ?? null,
            );
        } catch (CustomerBankingException $e) {
            return $this->error($e);
        }

        $payload = $this->transfers->transactionPayload($transaction);

        return response()->json([
            'success' => $payload['outcome'] === 'success',
            'data' => $payload,
        ], $payload['outcome'] === 'success' ? 201 : 200);
    }

    public function status(Request $request, string $reference): JsonResponse
    {
        try {
            $transaction = $this->transfers->status($request->user(), $reference);
        } catch (CustomerBankingException $e) {
            return $this->error($e);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transfers->transactionPayload($transaction),
        ]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function validateIntent(Request $request): array
    {
        return Validator::make($request->all(), [
            'from_wallet_id' => ['required', 'string'],
            'recipient' => ['required', 'string', 'max:40'],
            'amount' => ['required', 'string'],
            'note' => ['nullable', 'string', 'max:140'],
        ])->validate();
    }

    private function resolveOwnedWallet(int $userId, string $walletId): CustomerWallet
    {
        $wallet = CustomerWallet::query()->where('wallet_id', $walletId)->first();

        if ($wallet === null || (int) $wallet->user_id !== $userId) {
            throw new CustomerBankingException('Wallet not found.');
        }

        return $wallet;
    }

    private function error(CustomerBankingException $e): JsonResponse
    {
        $status = $e->getCode() === 403 ? 403 : 422;

        return response()->json([
            'success' => false,
            'error' => $status === 403 ? 'forbidden' : 'unprocessable',
            'message' => $e->getMessage(),
        ], $status);
    }
}
