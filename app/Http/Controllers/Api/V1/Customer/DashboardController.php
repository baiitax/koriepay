<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Domain\Customer\CustomerWalletService;
use App\Models\CustomerWallet;
use App\Models\CustomerWalletConfig;
use App\Models\FxRate;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * CUSTOMER BANKING — §90 optimized dashboard read model.
 * One call returns profile (masked), wallets (ledger-sourced), selected
 * wallet, portfolio summary (estimate), recent transactions, notifications,
 * security/system status and exchange availability. No raw transaction
 * history is loaded — only the latest 5 rows.
 */
class DashboardController extends Controller
{
    public function __invoke(CustomerWalletService $wallets): JsonResponse
    {
        $user = request()->user();
        $wallets->provision($user);

        $list = $wallets->walletsFor($user);
        $selected = $wallets->selectedWallet($user);

        return response()->json([
            'success' => true,
            'data' => [
                'profile' => [
                    'name' => $user->name,
                    'phone' => $wallets->maskPhone((string) $user->phone_number),
                    'country' => $user->country_code,
                    'kyc_status' => $user->kyc_status,
                    'kyc_tier' => (int) $user->kyc_tier,
                ],
                'selected_wallet' => $selected !== null ? $this->walletShape($selected, $wallets) : null,
                'wallets' => array_map(fn (CustomerWallet $w) => $this->walletShape($w, $wallets), $list),
                'portfolio_summary' => $wallets->portfolioSummary($user),
                'quick_services' => ['send', 'receive', 'add_money', 'exchange', 'bills', 'airtime', 'data'],
                'recent_transactions' => $this->recentTransactions($user),
                'notifications' => $this->notifications($user),
                'security_status' => [
                    'devices_count' => \Illuminate\Support\Facades\DB::table('devices')->where('user_id', $user->id)->count(),
                    'kyc_status' => $user->kyc_status,
                    'is_active' => (bool) $user->is_active,
                ],
                'system_status' => $this->systemStatus(),
                'exchange_availability' => $this->exchangeAvailability($user),
                'data_freshness' => now()->toIso8601String(),
            ],
        ]);
    }

    private function walletShape(CustomerWallet $wallet, CustomerWalletService $service): array
    {
        $details = $service->balanceDetails(request()->user(), $wallet);

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

    private function recentTransactions($user): array
    {
        return Transaction::query()
            ->where(fn ($q) => $q->where('sender_id', $user->id)->orWhere('receiver_id', $user->id))
            ->latest()
            ->take(5)
            ->get()
            ->map(function (Transaction $tx) use ($user) {
                $isSender = (int) $tx->sender_id === (int) $user->id;
                $counterparty = $isSender
                    ? ($tx->receiver_name ?? 'KoriePay')
                    : (\App\Models\User::find($tx->sender_id)?->name ?? 'KoriePay');

                return [
                    'reference' => $tx->reference,
                    'type' => $tx->type,
                    'direction' => $isSender ? 'out' : 'in',
                    'amount' => (string) $tx->source_amount,
                    'currency' => $tx->source_currency,
                    'status' => strtoupper((string) $tx->status),
                    'counterparty' => $counterparty,
                    'created_at' => $tx->created_at?->toIso8601String(),
                ];
            })
            ->all();
    }

    private function notifications($user): array
    {
        return collect($user->notifications()->latest()->take(5)->get())
            ->map(fn ($n) => [
                'id' => $n->id,
                'type' => class_basename($n->type),
                'read' => $n->read_at !== null,
                'data' => $n->data,
                'created_at' => $n->created_at?->toIso8601String(),
            ])
            ->all();
    }

    private function systemStatus(): array
    {
        $ledger = \Illuminate\Support\Facades\DB::table('payment_providers')
            ->where('code', 'ledger')
            ->first(['is_active', 'health_score', 'updated_at']);

        return [
            'ledger_provider' => $ledger !== null && (bool) $ledger->is_active
                ? 'operational'
                : 'unavailable',
            'health_score' => $ledger?->health_score,
            'reported_at' => $ledger?->updated_at ? \Illuminate\Support\Carbon::parse($ledger->updated_at)->toIso8601String() : now()->toIso8601String(),
        ];
    }

    private function exchangeAvailability($user): array
    {
        $iso2 = app(CustomerWalletService::class)->iso2For($user);
        $available = CustomerWalletConfig::query()
            ->where('country_iso2', $iso2)
            ->where('is_available', true)
            ->pluck('currency_code')
            ->all();

        $rateExists = fn (string $a, string $b) => FxRate::query()
            ->where('base_currency', $a)->where('target_currency', $b)->where('is_active', true)->exists();

        return [
            'xof_to_ngn' => in_array('XOF', $available, true) && in_array('NGN', $available, true) && $rateExists('XOF', 'NGN'),
            'ngn_to_xof' => in_array('XOF', $available, true) && in_array('NGN', $available, true) && $rateExists('NGN', 'XOF'),
            'note' => 'Exchange availability depends on your country, verification level and active rates.',
        ];
    }
}
