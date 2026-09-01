<?php

namespace App\Domain\Payments;

use App\Domain\Payments\Exceptions\InvalidWebhookSignatureException;
use App\Domain\Payments\Providers\InternalLedgerProvider;
use App\Domain\Payments\ValueObjects\PaymentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Webhook ingestion pipeline.
 *
 * Two trust boundaries (never trust an unverified webhook):
 *   1. ingestExternal() — provider webhooks. FAIL CLOSED: signature verified
 *      per provider; no configured secret ⇒ reject. External providers
 *      (Paystack, DusuPay, …) register here in later phases with real
 *      signatures — never fabricated.
 *   2. ingestInternal() — authenticated confirmations produced by our own
 *      infrastructure (queued settlement jobs, reconciliation). No external
 *      HMAC exists; the trust boundary is the calling code, and the event is
 *      still persisted + deduped exactly like an external one.
 *
 * Shared guarantees for both:
 *   - persisted BEFORE processing (auditable, replayable),
 *   - deduped on unique (provider, event_id): a replay returns the stored
 *     processing status — never processed twice,
 *   - processing is delegated to the orchestrator (state machine + ledger).
 */
class WebhookService
{
    public function __construct(
        private readonly PaymentOrchestrator $orchestrator,
        private readonly InternalLedgerProvider $internalProvider,
    ) {
    }

    /** External provider webhook (HMAC-verified, fail-closed). */
    public function ingestExternal(Request $request, string $providerCode): array
    {
        if ($providerCode === 'ledger') {
            // The internal rail never signs external webhooks; if this is
            // called, treat it as invalid (fail closed).
            throw new InvalidWebhookSignatureException(
                "Internal rail [ledger] does not accept external webhooks."
            );
        }

        $provider = $this->resolveExternalProvider($providerCode);

        if (! $provider->verifyWebhookSignature($request)) {
            Log::warning("Webhook signature rejected for provider [{$providerCode}].", ['ip' => $request->ip()]);
            throw new InvalidWebhookSignatureException("Invalid webhook signature for provider [{$providerCode}].");
        }

        return $this->persistAndProcess($providerCode, $request, $request->all());
    }

    /**
     * Authenticated internal confirmation (e.g. queued settlement job).
     * @param  array<string,mixed>  $payload
     */
    public function ingestInternal(string $source, array $payload, ?string $ip = null, ?string $userAgent = null): array
    {
        return $this->persistAndProcess($source, null, $payload, $ip, $userAgent);
    }

    /**
     * @return array{status:string, event_id:?string, already_processed:bool}
     */
    private function persistAndProcess(
        string $providerCode,
        ?Request $request,
        array $payload,
        ?string $ip = null,
        ?string $userAgent = null,
    ): array {
        $eventId = $payload['event_id'] ?? $payload['id'] ?? null;
        $rawContent = $request?->getContent() ?? json_encode($payload);
        $payloadHash = hash('sha256', (string) $rawContent);

        // ── Persist before processing (auditable, replayable) ───────────────
        try {
            $webhookId = DB::table('webhook_events')->insertGetId([
                'provider' => $providerCode,
                'event_id' => $eventId,
                'event_type' => $payload['event_type'] ?? $payload['event'] ?? 'unknown',
                'signature' => $request?->header('x-webhook-signature'),
                'payload_hash' => $payloadHash,
                'payload' => json_encode($payload),
                'ip_address' => $request?->ip() ?? $ip,
                'processing_status' => 'received',
                'retry_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Replayed event — never processed twice. Return stored status.
            $existing = DB::table('webhook_events')
                ->where('provider', $providerCode)
                ->where('event_id', $eventId)
                ->first();

            return [
                'status' => $existing->processing_status ?? 'processed',
                'event_id' => $eventId,
                'already_processed' => true,
            ];
        }

        // ── Process ─────────────────────────────────────────────────────────
        DB::table('webhook_events')->where('id', $webhookId)->update(['processing_status' => 'processing']);

        try {
            $this->processEvent($providerCode, $payload, $webhookId);
            DB::table('webhook_events')->where('id', $webhookId)->update(['processing_status' => 'processed']);
        } catch (\Throwable $e) {
            Log::error("Webhook processing failed [{$providerCode}] [{$eventId}]: {$e->getMessage()}");
            DB::table('webhook_events')->where('id', $webhookId)->update([
                'processing_status' => 'failed',
                'retry_count' => DB::raw('retry_count + 1'),
            ]);
            throw $e;
        }

        return [
            'status' => 'processed',
            'event_id' => $eventId,
            'already_processed' => false,
        ];
    }

    /**
     * Match the event payload to an operational transaction and settle it.
     * Payload contract:
     *   { event_id, reference, status: "success"|"failed", message?, provider_reference? }
     */
    private function processEvent(string $providerCode, array $payload, int $webhookId): void
    {
        $reference = $payload['reference'] ?? null;
        $status = strtolower((string) ($payload['status'] ?? ''));

        if ($reference === null) {
            throw new \RuntimeException('Webhook payload missing reference.');
        }

        $transaction = \App\Models\Transaction::where('reference', $reference)->first();
        if ($transaction === null) {
            throw new \RuntimeException("No transaction found for webhook reference [{$reference}].");
        }

        $result = $status === 'success'
            ? PaymentResult::success(
                reference: (string) ($payload['provider_reference'] ?? $reference),
                message: $payload['message'] ?? null,
            )
            : PaymentResult::failed($payload['message'] ?? 'Webhook reported failure.');

        $this->orchestrator->settle($transaction, $providerCode, $result);
    }

    private function resolveExternalProvider(string $code): PaymentProviderInterface
    {
        // Phase 5 registers only the internal rail; external providers are
        // wired here as their real adapters land. No fabricated providers.
        throw new InvalidWebhookSignatureException("No external provider registered [{$code}].");
    }
}
