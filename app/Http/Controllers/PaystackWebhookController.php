<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Transaction, Wallet, AuditLog};
use Illuminate\Support\Facades\{DB, Log};

class PaystackWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // 1. CRYPTOGRAPHIC VERIFICATION (The Shield)
        // Only Paystack has the secret key, so only they can generate this signature.
        $signature = $request->header('x-paystack-signature');
        $secret = env('PAYSTACK_SECRET_KEY');

        if (!$signature || $signature !== hash_hmac('sha512', $request->getContent(), $secret)) {
            Log::alert('CRITICAL: Invalid Paystack Webhook Signature Detected.', ['ip' => $request->ip()]);
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 401);
        }

        // 2. PARSE THE EVENT
        $payload = $request->all();
        $event = $payload['event'] ?? '';

        // We only care about successful charges
        if ($event === 'charge.success') {
            $reference = $payload['data']['reference'] ?? null;
            $amountPaidInKobo = $payload['data']['amount'] ?? 0;
            $amountPaidInNaira = $amountPaidInKobo / 100;

            if (!$reference) {
                return response()->json(['status' => 'ignored', 'message' => 'No reference found'], 200);
            }

            // 3. ATOMIC LEDGER SETTLEMENT
            DB::beginTransaction();
            try {
                // Find the pending transaction and lock the row to prevent double-spending
                $tx = Transaction::where('reference', $reference)
                    ->where('type', 'wallet_funding')
                    ->where('status', 'pending')
                    ->lockForUpdate()
                    ->first();

                if ($tx) {
                    // 4. VALUE VERIFICATION (Anti-Tamper Check)
                    if ($amountPaidInNaira >= $tx->source_amount) {
                        
                        // Lock the Agent's NGN Wallet
                        $wallet = Wallet::where('user_id', $tx->sender_id)
                            ->where('currency_code', 'NGN')
                            ->lockForUpdate()
                            ->first();

                        if ($wallet) {
                            // Execute Top-Up
                            $wallet->increment('balance', $tx->source_amount);
                            $tx->update(['status' => 'completed']);

                            // Log for Compliance
                            AuditLog::forceCreate([
                                'user_id' => $tx->sender_id,
                                'user_name' => 'System / Paystack webhook',
                                'action' => 'PAYSTACK_INFLOW_CLEARED',
                                'event_type' => 'transaction',
                                'metadata' => "Automated liquidity injection of ₦" . number_format($tx->source_amount, 2) . " via Paystack. Ref: {$reference}",
                                'ip_address' => $request->ip()
                            ]);

                            DB::commit();
                            Log::info("Paystack Settlement Cleared: {$reference}");
                        }
                    } else {
                        // The user modified the URL and paid less than requested.
                        $tx->update(['status' => 'failed', 'metadata' => 'Amount mismatch. Fraud suspected.']);
                        DB::commit();
                        Log::warning("Paystack amount mismatch on {$reference}. Expected: {$tx->source_amount}, Paid: {$amountPaidInNaira}");
                    }
                } else {
                    // Transaction was already completed (Idempotency catch) or doesn't exist
                    DB::rollBack();
                }

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Paystack Webhook Failure: " . $e->getMessage());
                // Return 500 so Paystack tries sending the webhook again later
                return response()->json(['status' => 'error', 'message' => 'Internal Server Error'], 500); 
            }
        }

        // 5. ACKNOWLEDGE RECEIPT
        // Always return 200 OK so Paystack knows we received it, otherwise they will keep pinging us.
        return response()->json(['status' => 'success'], 200);
    }
}