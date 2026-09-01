<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Transaction, Wallet, User};
use App\Notifications\FundsReceived;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Log};

/**
 * Gateway webhook receiver (Phase 0 hardened).
 *
 * SECURITY CHANGES vs the legacy implementation:
 *  1. FAIL-CLOSED: if GATEWAY_WEBHOOK_SECRET is unset the request is rejected.
 *     The old code defaulted to the publicly-known 'simulation_secret_123'.
 *  2. Constant-time signature comparison via hash_equals().
 *  3. Replay protection: status must be 'pending' before any state change and
 *     the update is guarded by a row lock (idempotent by construction).
 */
class WebhookController extends Controller
{
    public function handleGatewayWebhook(Request $request)
    {
        $signature = (string) $request->header('x-gateway-signature');
        $secret = (string) env('GATEWAY_WEBHOOK_SECRET');

        // Fail closed — never trust a default secret.
        if ($secret === '' || $signature === '' || ! hash_equals($secret, $signature)) {
            Log::warning('Unauthorized Webhook Attempt: Invalid Signature', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        $event = $payload['event'] ?? null; // e.g., 'transfer.success' | 'transfer.failed'
        $data = $payload['data'] ?? [];
        $reference = $data['reference'] ?? null;

        if (! $reference) {
            return response()->json(['status' => 'error', 'message' => 'Missing reference'], 400);
        }

        $transaction = Transaction::where('reference', $reference)->first();

        // Idempotency: already processed → acknowledge without side effects.
        if (! $transaction || $transaction->status !== 'pending') {
            return response()->json(['status' => 'success', 'message' => 'Already processed or not found'], 200);
        }

        try {
            DB::transaction(function () use ($transaction, $event) {
                $lockedTx = Transaction::where('id', $transaction->id)
                    ->lockForUpdate()
                    ->where('status', 'pending')
                    ->first();

                if (! $lockedTx) {
                    return; // another worker settled it concurrently
                }

                if ($event === 'transfer.success') {
                    $lockedTx->update(['status' => 'completed']);
                    $this->notifyFundsReceived($lockedTx);
                } elseif (in_array($event, ['transfer.failed', 'transfer.reversed'], true)) {
                    $lockedTx->update(['status' => 'failed']);

                    $wallet = Wallet::where('user_id', $lockedTx->sender_id)
                        ->where('currency_code', $lockedTx->source_currency)
                        ->lockForUpdate()
                        ->first();

                    if ($wallet) {
                        $refundAmount = (float) $lockedTx->source_amount + (float) ($lockedTx->fee_charged ?? 0);
                        $wallet->increment('balance', $refundAmount);

                        // NOTE (Phase 3): balance increments here are a temporary
                        // projection fix — the immutable ledger replaces this path.
                        Log::info("Gateway refund posted for failed cash-out", [
                            'reference' => $lockedTx->reference,
                            'amount' => $refundAmount,
                        ]);
                    }
                }
            });

            return response()->json(['status' => 'success'], 200);
        } catch (\Exception $e) {
            Log::error('Webhook Settlement Failed', [
                'error' => $e->getMessage(),
                'reference' => $reference,
            ]);
            return response()->json(['status' => 'error', 'message' => 'Internal processing error'], 500);
        }
    }

    protected function notifyFundsReceived(Transaction $transaction): void
    {
        $recipient = User::find($transaction->receiver_id);
        if (! $recipient) {
            return;
        }

        try {
            $recipient->notify(new FundsReceived(
                (float) $transaction->destination_amount,
                $transaction->destination_currency,
                "Gateway settlement cleared"
            ));
        } catch (\Throwable $e) {
            // Notifications must never break settlement.
            Log::warning('Notification delivery failed', ['error' => $e->getMessage()]);
        }
    }
}
