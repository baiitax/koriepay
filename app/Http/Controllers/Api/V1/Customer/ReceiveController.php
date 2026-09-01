<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Domain\Customer\CustomerTransferService;
use App\Domain\Customer\Exceptions\CustomerBankingException;
use App\Domain\Customer\QrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * CUSTOMER BANKING — receive identity (§128 receive journey).
 *
 * GET /api/v1/customer/receive — the customer's public receive identity:
 * KoriePay ID, masked phone, canonical koriepay:// QR payload + a real
 * server-rendered QR (inline SVG data URI, works offline).
 */
class ReceiveController extends Controller
{
    public function __construct(
        private readonly CustomerTransferService $transfers,
        private readonly QrService $qr,
    ) {
    }

    public function identity(Request $request): JsonResponse
    {
        try {
            $identity = $this->transfers->receiveIdentity($request->user());
        } catch (CustomerBankingException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $identity['qr_svg_data_uri'] = $this->qr->dataUri($identity['qr_payload']);

        return response()->json(['success' => true, 'data' => $identity]);
    }
}
