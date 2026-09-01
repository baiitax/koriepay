<?php

namespace App\Domain\Customer;

use App\Models\Transaction;
use Illuminate\Support\Facades\Config;

/**
 * CUSTOMER BANKING — receipts (Stage 3/4).
 *
 * A receipt is an authoritative, tamper-evident summary of a transaction:
 * the hash is HMAC-SHA256 (server secret) over the canonical receipt fields,
 * so the customer can verify a receipt was issued by KoriePay and never
 * altered. The app NEVER lets the client mint a receipt — it only renders
 * what the server hashes and returns.
 */
class TransactionReceiptService
{
    /** Canonical, ordered fields that make up the signed receipt. */
    public function canonicalString(Transaction $tx): string
    {
        return implode('|', [
            'KORIEPAY-RECEIPT-v1',
            (string) $tx->reference,
            (string) $tx->type,
            (string) $tx->source_currency,
            (string) $tx->source_amount,
            (string) ($tx->destination_currency ?? $tx->source_currency),
            (string) ($tx->destination_amount ?? $tx->source_amount),
            (string) ($tx->exchange_rate ?? '1.0000'),
            (string) ($tx->fee_charged ?? '0.00'),
            strtoupper((string) $tx->status),
            (string) $tx->created_at?->toIso8601String(),
            (string) ($tx->provider ?? ''),
            (string) ($tx->provider_reference ?? ''),
        ]);
    }

    /** HMAC-SHA256 receipt hash — the integrity token. */
    public function hash(Transaction $tx): string
    {
        return hash_hmac('sha256', $this->canonicalString($tx), $this->secret());
    }

    /** Recompute and compare — returns true only if the hash matches. */
    public function verify(Transaction $tx, string $hash): bool
    {
        return hash_equals($this->hash($tx), strtolower(trim($hash)));
    }

    /** Full receipt payload with its integrity token + verification status. */
    public function receipt(Transaction $tx): array
    {
        $hash = $this->hash($tx);

        return [
            'reference' => $tx->reference,
            'type' => $tx->type,
            'status' => strtoupper((string) $tx->status),
            'source' => [
                'currency' => $tx->source_currency,
                'amount' => (string) $tx->source_amount,
            ],
            'destination' => [
                'currency' => (string) ($tx->destination_currency ?? $tx->source_currency),
                'amount' => (string) ($tx->destination_amount ?? $tx->source_amount),
            ],
            'exchange_rate' => (string) ($tx->exchange_rate ?? '1.0000'),
            'fee' => (string) ($tx->fee_charged ?? '0.00'),
            'provider' => $tx->provider,
            'provider_reference' => $tx->provider_reference,
            'issued_at' => now()->toIso8601String(),
            'transaction_created_at' => $tx->created_at?->toIso8601String(),
            'hash' => $hash,
            'hash_algo' => 'HMAC-SHA256',
            'verified' => true,
        ];
    }

    private function secret(): string
    {
        // Derive a stable 32-byte key from the app key — no extra storage.
        return hash('sha256', 'koriepay-receipt|'.(string) Config::get('app.key'), true);
    }
}
