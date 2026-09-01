<?php

namespace App\Http\Controllers\Api\V1\Customer\Profile;

use App\Domain\Customer\CustomerSecurityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

/**
 * CUSTOMER BANKING — Stage 5 (profile security endpoints).
 *
 * POST /api/v1/customer/profile/security/biometric — toggle biometric login.
 *   State is SESSION-ONLY: it is never written to the database and never
 *   stored as a credential. The server echoes "applied for this session".
 *
 * POST /api/v1/customer/profile/pin/enroll — the brief forbids storing PIN
 *   hashes in the customer app; this endpoint therefore REJECTS enrollment
 *   with a generic security-token warning and stores nothing.
 *
 * Both endpoints exist so the client can never accidentally persist
 * credentials through a missing guard.
 */
class DeviceController extends Controller
{
    public function __construct(private readonly CustomerSecurityService $security)
    {
    }

    public function biometric(Request $request): JsonResponse
    {
        $validated = Validator::make($request->all(), [
            'is_enabled' => ['required', 'boolean'],
        ])->validate();

        $this->security->setBiometric($request->user(), (bool) $validated['is_enabled']);

        return response()->json([
            'success' => true,
            'data' => [
                'biometric_enabled' => $this->security->biometricEnabled($request->user()),
                'persisted' => false,
                'note' => 'Biometric preference applied for this session only. It is not stored on KoriePay servers.',
            ],
        ]);
    }

    public function enrollPin(Request $request): JsonResponse
    {
        $validated = Validator::make($request->all(), [
            'pin' => ['required', 'digits:6'],
        ])->validate();

        // Deliberate refusal: the customer app never stores PIN hashes.
        // A 6-digit PIN is also rejected outright — transaction security uses
        // device-bound tokens, not reusable PINs.
        return response()->json([
            'success' => false,
            'error' => 'pin_storage_not_supported',
            'message' => 'This app does not store mobile PINs. Use your device biometrics or a security token instead.',
            'code' => 422,
        ], 422);
    }
}
