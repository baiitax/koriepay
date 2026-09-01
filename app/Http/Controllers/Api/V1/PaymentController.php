<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Payments\Exceptions\InvalidPaymentRequestException;
use App\Domain\Payments\Exceptions\ProviderUnavailableException;
use App\Domain\Payments\Exceptions\UnsupportedCountryException;
use App\Domain\Payments\Exceptions\UnsupportedCurrencyException;
use App\Domain\Payments\PaymentOrchestrator;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Phase 5 — transactional payment API (v1).
 *
 * Every money-moving call REQUIRES an Idempotency-Key header; replays return
 * the original outcome and never move money twice. All business exceptions
 * map to explicit, machine-readable HTTP statuses (no stack traces).
 */
class PaymentController extends Controller
{
    public function __construct(private readonly PaymentOrchestrator $orchestrator)
    {
    }

    public function deposit(Request $request): JsonResponse
    {
        $validated = $this->validateCommon($request, [
            'amount' => ['required', 'string', 'regex:/^\d+(\.\d{1,2})?$/'],
            'currency' => ['required', 'string', 'size:3'],
            'country' => ['required', 'string', 'size:2'],
        ]);

        $transaction = $this->guard(function () use ($request, $validated) {
            return $this->orchestrator->deposit(
                customerId: $request->user()->id,
                amount: $validated['amount'],
                currency: strtoupper($validated['currency']),
                countryIso2: strtoupper($validated['country']),
                idempotencyKey: $this->idempotencyKey($request),
                description: $validated['description'] ?? null,
            );
        });

        return $this->paymentResponse($transaction, 201);
    }

    public function withdraw(Request $request): JsonResponse
    {
        $validated = $this->validateCommon($request, [
            'amount' => ['required', 'string', 'regex:/^\d+(\.\d{1,2})?$/'],
            'currency' => ['required', 'string', 'size:3'],
            'country' => ['required', 'string', 'size:2'],
        ]);

        $transaction = $this->guard(function () use ($request, $validated) {
            return $this->orchestrator->withdraw(
                customerId: $request->user()->id,
                amount: $validated['amount'],
                currency: strtoupper($validated['currency']),
                countryIso2: strtoupper($validated['country']),
                idempotencyKey: $this->idempotencyKey($request),
                description: $validated['description'] ?? null,
            );
        });

        return $this->paymentResponse($transaction);
    }

    public function transfer(Request $request): JsonResponse
    {
        $validated = $this->validateCommon($request, [
            'receiver_id' => ['required', 'integer', 'exists:users,id'],
            'amount' => ['required', 'string', 'regex:/^\d+(\.\d{1,2})?$/'],
            'currency' => ['required', 'string', 'size:3'],
            'country' => ['required', 'string', 'size:2'],
        ]);

        $transaction = $this->guard(function () use ($request, $validated) {
            return $this->orchestrator->transfer(
                senderId: $request->user()->id,
                receiverId: (int) $validated['receiver_id'],
                amount: $validated['amount'],
                currency: strtoupper($validated['currency']),
                countryIso2: strtoupper($validated['country']),
                idempotencyKey: $this->idempotencyKey($request),
                description: $validated['description'] ?? null,
            );
        });

        return $this->paymentResponse($transaction);
    }

    public function status(Request $request, string $reference): JsonResponse
    {
        $transaction = Transaction::where('reference', $reference)->first();

        if ($transaction === null) {
            return response()->json([
                'success' => false,
                'error' => 'not_found',
                'message' => 'No transaction with that reference.',
            ], 404);
        }

        // Ownership check — a user may only query their own transactions.
        if ($transaction->sender_id !== $request->user()->id
            && $transaction->receiver_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'error' => 'forbidden',
                'message' => 'You do not have access to this transaction.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transactionPayload($transaction),
        ]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function validateCommon(Request $request, array $rules): array
    {
        $validator = Validator::make($request->all(), $rules + [
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            abort(422, $validator->errors()->first());
        }

        return $validator->validated();
    }

    private function idempotencyKey(Request $request): string
    {
        $key = $request->header('Idempotency-Key', '');

        if ($key === '' || strlen($key) > 64) {
            abort(422, 'Idempotency-Key header is required (1–64 characters).');
        }

        return $key;
    }

    private function guard(callable $fn): Transaction
    {
        try {
            return $fn();
        } catch (InvalidPaymentRequestException $e) {
            abort(422, $e->getMessage());
        } catch (UnsupportedCountryException | UnsupportedCurrencyException $e) {
            abort(422, $e->getMessage());
        } catch (ProviderUnavailableException $e) {
            abort(503, $e->getMessage());
        }
    }

    private function paymentResponse(Transaction $transaction, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => in_array(strtolower((string) $transaction->status), ['settled', 'completed'], true),
            'data' => $this->transactionPayload($transaction),
        ], $status);
    }

    private function transactionPayload(Transaction $transaction): array
    {
        return [
            'reference' => $transaction->reference,
            'type' => $transaction->type,
            'status' => strtolower((string) $transaction->status),
            'amount' => $transaction->source_amount,
            'currency' => $transaction->source_currency,
            'provider' => $transaction->provider,
            'rail' => $transaction->rail,
            'provider_reference' => $transaction->provider_reference,
            'error_reason' => $transaction->error_reason,
            'created_at' => optional($transaction->created_at)->toIso8601String(),
        ];
    }
}
