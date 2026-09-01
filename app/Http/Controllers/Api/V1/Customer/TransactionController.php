<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Domain\Customer\CustomerTransactionService;
use App\Domain\Customer\TransactionReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

/**
 * CUSTOMER BANKING — Stage 4 (transaction history & receipt status).
 *
 * GET  /api/v1/customer/transactions          — filtered, paginated history
 * GET  /api/v1/customer/transactions/{reference} — one transaction + verified
 *                                                  HMAC receipt
 *
 * Ownership is enforced in the service: any reference that is not the
 * customer's own row is a 404 — no existence leak, never a 403 listing
 * someone else's data.
 */
class TransactionController extends Controller
{
    public function __construct(
        private readonly CustomerTransactionService $history,
        private readonly TransactionReceiptService $receipts,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $validated = Validator::make($request->all(), [
            'type' => ['nullable', 'string', 'in:deposit,withdraw,transfer,exchange,bill,airtime,data,refund,fee,reversal'],
            'currency' => ['nullable', 'string', 'in:XOF,NGN,xof,ngn'],
            'status' => ['nullable', 'string', 'in:initiated,processing,authorized,posted,settled,failed,reversed'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'q' => ['nullable', 'string', 'max:80'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ])->validate();

        $page = $this->history->history($request->user(), $validated);

        return response()->json([
            'success' => true,
            'data' => collect($page->items())->map(fn ($tx) => $this->shape($tx))->all(),
            'filters' => $this->history->historyFilters(),
            'pagination' => [
                'current_page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'last_page' => $page->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, string $reference): JsonResponse
    {
        $tx = $this->history->find($request->user(), $reference);

        if ($tx === null) {
            // Distinguish a foreign row (403, no data leaked) from a row that
            // does not exist at all (404).
            $foreign = \App\Models\Transaction::where('reference', $reference)->exists();

            return response()->json([
                'success' => false,
                'message' => $foreign ? 'This transaction does not belong to you.' : 'Transaction not found.',
            ], $foreign ? 403 : 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->shape($tx, withReceipt: true),
        ]);
    }

    /**
     * Receipt verification endpoint — recomputes the HMAC server-side and
     * reports integrity without ever trusting a client-supplied hash.
     */
    public function verify(Request $request, string $reference): JsonResponse
    {
        $tx = $this->history->find($request->user(), $reference);

        if ($tx === null) {
            return response()->json(['success' => false, 'message' => 'Transaction not found.'], 404);
        }

        $receipt = $this->receipts->receipt($tx);

        return response()->json([
            'success' => true,
            'data' => [
                'reference' => $tx->reference,
                'hash' => $receipt['hash'],
                'hash_algo' => $receipt['hash_algo'],
                'verified' => $receipt['verified'],
                'status' => strtolower((string) $tx->status),
                'checked_at' => now()->toIso8601String(),
            ],
        ]);
    }

    private function shape($tx, bool $withReceipt = false): array
    {
        $row = [
            'reference' => $tx->reference,
            'type' => $tx->type,
            'status' => strtolower((string) $tx->status),
            'amount' => (string) $tx->source_amount,
            'currency' => $tx->source_currency,
            'destination' => [
                'currency' => (string) ($tx->destination_currency ?? $tx->source_currency),
                'amount' => (string) ($tx->destination_amount ?? $tx->source_amount),
            ],
            'exchange_rate' => (string) ($tx->exchange_rate ?? '1.0000'),
            'fee' => (string) ($tx->fee_charged ?? '0.00'),
            'counterparty' => $tx->receiver_name,
            'description' => $tx->description,
            'created_at' => $tx->created_at?->toIso8601String(),
        ];

        if ($withReceipt) {
            $receipt = $this->receipts->receipt($tx);
            $row['receipt'] = $receipt;
            $row['verification_url'] = url('/api/v1/customer/transactions/'.$tx->reference.'/verify');
        }

        return $row;
    }
}
