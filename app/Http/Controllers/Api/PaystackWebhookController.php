<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Wallet;
use App\Models\Transaction; // Assuming you have a Transaction model to log history
use App\Models\User;

class PaystackWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        // 1. SECURITY PRE-CHECK: Verify the request came from Paystack
        $secretKey = env('' . 'PAYSTACK_SECRET_KEY'); // Replace with your actual Paystack secret key
        $signature = $request->header('x-paystack-signature');
        $payload = $request->getContent();

        // Cryptographically hash the payload with your secret key
        $computedSignature = hash_hmac('sha512', $payload, $secretKey);

        if ($computedSignature !== $signature) {
            Log::alert('CRITICAL: Fake Paystack Webhook Attempt Detected!', ['ip' => $request->ip()]);
            // Return 401 Unauthorized
            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 401);
        }

        // 2. PARSE THE PAYLOAD
        $event = json_decode($payload, true);

        // We only care about successful charges
        if ($event['event'] === 'charge.success') {
            $data = $event['data'];

            // Paystack sends amounts in KOBO (lowest denomination). Divide by 100 to get NGN.
            $amountInNaira = $data['amount'] / 100;
            $reference = $data['reference'];
            $channel = $data['channel']; // Usually 'dedicated_nuba' for virtual accounts

            // 3. IDEMPOTENCY CHECK: Did we already process this exact transaction?
            // (If you don't have a transactions table yet, I highly recommend creating one)
            $existingTx = Transaction::where('reference', $reference)->first();

            if ($existingTx) {
                // We already processed this! Return 200 so Paystack stops retrying.
                Log::info("Webhook IDEMPOTENCY triggered. Reference {$reference} already processed.");
                return response()->json(['status' => 'success', 'message' => 'Already processed'], 200);
            }

            // 4. LOCATE THE WALLET
            // Paystack usually sends the virtual account number in the authorization object
            $receiverAccountNumber = $data['authorization']['receiver_bank_account_number'] ?? null;
            $customerEmail = $data['customer']['email'];

            $wallet = null;

            // Attempt A: Find by the exact Virtual Account Number
            if ($receiverAccountNumber) {
                $wallet = Wallet::where('virtual_account_number', $receiverAccountNumber)
                                ->where('currency_code', 'NGN')
                                ->first();
            }

            // Attempt B: Fallback (Find the user by email, then get their NGN wallet)
            if (!$wallet) {
                $user = User::where('email', $customerEmail)->first();
                if ($user) {
                    $wallet = Wallet::where('user_id', $user->id)
                                    ->where('currency_code', 'NGN')
                                    ->first();
                }
            }

            // 5. CREDIT THE USER
            if ($wallet) {
                // A. Add the money to their balance
                $wallet->balance += $amountInNaira;
                $wallet->save();

                // B. Log the transaction so it shows up in their KoriePay "Recent Activity" and prevents double-credits
                Transaction::create([
                    'user_id' => $wallet->user_id,
                    'type' => 'deposit',
                    'amount' => $amountInNaira,
                    'currency' => 'NGN',
                    'reference' => $reference,
                    'status' => 'completed',
                    'description' => 'Virtual Account Deposit via ' . ($data['authorization']['bank'] ?? 'Bank Transfer'),
                ]);

                Log::info("SUCCESS: Credited Wallet [{$wallet->id}] with {$amountInNaira} NGN. Ref: {$reference}");
            } else {
                Log::error("Webhook Error: Could not locate wallet for email {$customerEmail} or Account {$receiverAccountNumber}");
            }
        }

        // Always return 200 OK to Paystack so they know you received it.
        return response()->json(['status' => 'success'], 200);
    }
}
