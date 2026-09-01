<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Domain\Customer\CustomerWalletService;
use App\Domain\Customer\Exceptions\WalletUnavailableException;
use App\Models\CustomerWallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Customer wallet endpoints — ownership enforced server-side (§87, §88).
 */
class WalletController extends Controller
{
    public function __construct(private readonly CustomerWalletService $wallets)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'wallets' => array_map(
                    fn (CustomerWallet $w) => $this->shape($w),
                    $this->wallets->walletsFor($request->user())
                ),
            ],
        ]);
    }

    public function show(Request $request, string $walletId): JsonResponse
    {
        $wallet = $this->resolve($request->user(), $walletId);

        return response()->json(['success' => true, 'data' => $this->shape($wallet)]);
    }

    public function balance(Request $request, string $walletId): JsonResponse
    {
        $wallet = $this->resolve($request->user(), $walletId);

        return response()->json([
            'success' => true,
            'data' => $this->wallets->balanceDetails($request->user(), $wallet),
        ]);
    }

    public function select(Request $request, string $walletId): JsonResponse
    {
        $wallet = $this->resolve($request->user(), $walletId);

        try {
            $this->wallets->selectWallet($request->user(), $wallet);
        } catch (WalletUnavailableException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'data' => $this->shape($wallet)]);
    }

    private function resolve($user, string $walletId): CustomerWallet
    {
        $wallet = CustomerWallet::query()->where('wallet_id', $walletId)->first();

        if ($wallet === null || (int) $wallet->user_id !== (int) $user->id) {
            abort(404, 'Wallet not found.');
        }

        return $wallet;
    }

    private function shape(CustomerWallet $wallet): array
    {
        $details = $this->wallets->balanceDetails(request()->user(), $wallet);

        return [
            'wallet_id' => $wallet->wallet_id,
            'currency' => $wallet->currency_code,
            'display_name' => $wallet->display_name,
            'is_primary' => (bool) $wallet->is_primary,
            'status' => strtoupper($wallet->status),
            'available_balance' => $details['available'],
            'pending_balance' => $details['pending'],
            'total_balance' => $details['total'],
            'minor_units' => $details['minor_units'],
            'last_updated_at' => $wallet->ledgerAccount?->updated_at?->toIso8601String(),
        ];
    }
}
