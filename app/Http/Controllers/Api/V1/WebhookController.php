<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Payments\Exceptions\InvalidWebhookSignatureException;
use App\Domain\Payments\WebhookService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Unified webhook receiver (Phase 5).
 *
 * The provider's HMAC signature IS the authentication — there is no session.
 * Signature failures are rejected 401 (fail closed). The internal rail does
 * not accept external webhooks; external provider adapters register in later
 * phases with their real signature schemes.
 */
class WebhookController extends Controller
{
    public function __construct(private readonly WebhookService $webhooks)
    {
    }

    public function handle(Request $request, string $provider): JsonResponse
    {
        try {
            $result = $this->webhooks->ingestExternal($request, $provider);
        } catch (InvalidWebhookSignatureException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        return response()->json([
            'status' => $result['status'],
            'event_id' => $result['event_id'],
            'already_processed' => $result['already_processed'],
        ], 200);
    }
}
