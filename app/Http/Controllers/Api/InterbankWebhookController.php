<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Transaction, Wallet};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Log};

class InterbankWebhookController extends Controller
{
    public function handlePaystack(Request $request)
    {
        // 1. SECURITY: Verify Paystack Signature
        $signature = $request->header('x-paystack-signature');
        if (!$signature || $signature !== hash_hmac('sha512', $request->getContent(), env('PAYSTACK_SECRET_KEY'))) {
            return response()->json(['message' => 'Invalid Signature'], 401);
        }

        $payload = $request->all();
        $event = $payload['event']; // transfer.success or transfer.failed
        $reference = $payload['data']['transfer_code'] ?? $payload['data']['reference'];

        return $this->processSettlement($reference, $event, 'Paystack');
    }

    public function handleDusuPay(Request $request)
    {
        // 1. SECURITY: Verify DusuPay Token (Usually sent as a query param or header)
        if ($request->header('Authorization') !== env('DUSUPAY_WEBHOOK_SECRET')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        $event = $payload['status']; // e.g., COMPLETED or FAILED
        $reference = $payload['merchant_reference'];

        // Normalize DusuPay events to match our internal logic
        $status = ($event === 'COMPLETED') ? 'transfer.success' : 'transfer.failed';

        return $this->processSettlement($reference, $status, 'DusuPay');
    }

    private function processSettlement($reference, $event, $gateway)
    {
        Log::info("Webhook Received: {$gateway} - Ref: {$reference} - Event: {$event}");

        // 2. ATOMIC LEDGER UPDATE
        try {
            DB::transaction(function () use ($reference, $event) {
                // Find the transaction with a row lock to prevent race conditions
                $transaction = Transaction::where('reference', $reference)
                    ->where('status', 'pending')
                    ->lockForUpdate()
                    ->first();

                if (!$transaction) {
                    return; // Already processed or doesn't exist
                }

                if ($event === 'transfer.success') {
                    // Success: Finalize the transaction
                    $transaction->update(['status' => 'completed']);
                } else {
                    // Failure: We must REFUND the user's wallet
                    $transaction->update(['status' => 'failed']);

                    $wallet = Wallet::where('user_id', $transaction->user_id)
                        ->where('currency_code', $transaction->currency)
                        ->lockForUpdate()
                        ->first();

                    // Refund Amount + Fee
                    $refundTotal = (float)$transaction->amount + (float)$transaction->fee;
                    $wallet->increment('balance', $refundTotal);

                    // Log the reversal in the ledger
                    Transaction::create([
                        'user_id' => $transaction->user_id,
                        'reference' => 'REV-' . $transaction->reference,
                        'type' => 'refund',
                        'amount' => $refundTotal,
                        'currency' => $transaction->currency,
                        'status' => 'completed',
                        'description' => "Reversal: External settlement failed via Gateway.",
                    ]);
                }
            });

            return response()->json(['status' => 'success'], 200);

        } catch (\Exception $e) {
            Log::error("Webhook Processing Error: " . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
    }
}