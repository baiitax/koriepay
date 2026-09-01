<?php

namespace App\Livewire\Customer;

use App\Domain\Customer\CustomerWalletService;
use App\Domain\Customer\Exceptions\WalletUnavailableException;
use App\Models\CustomerWallet;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * CUSTOMER BANKING — Dashboard (brief §10, §12, §82, §90).
 *
 * Rewritten to the ledger-first read model:
 *   - wallets are provisioned from country/KYC configs (§75) — never a
 *     blanket NGN+XOF for everyone;
 *   - every balance is derived from the LEDGER (available) + in-flight
 *     transactions (pending) — `wallets.balance` is never read;
 *   - portfolio total is an ESTIMATE converted at the authoritative rate.
 */
#[Layout('layouts.customer')]
class Dashboard extends Component
{
    #[Url]
    public ?string $wallet = null;

    public bool $showBalance = true;

    public function mount(CustomerWalletService $wallets): void
    {
        $user = Auth::user();
        $wallets->provision($user);

        $selected = $wallets->selectedWallet($user);
        $this->wallet = $selected->wallet_id;
    }

    public function selectWallet(string $walletId, CustomerWalletService $service): void
    {
        $user = Auth::user();
        $wallet = CustomerWallet::where('wallet_id', $walletId)->first();

        if ($wallet === null || (int) $wallet->user_id !== (int) $user->id) {
            $this->dispatch('toast', type: 'error', message: 'Wallet not found.');

            return;
        }

        try {
            $service->selectWallet($user, $wallet);
            $this->wallet = $wallet->wallet_id;
        } catch (WalletUnavailableException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function toggleBalance(): void
    {
        $this->showBalance = ! $this->showBalance;
    }

    public function render(CustomerWalletService $service)
    {
        $user = Auth::user();
        $service->provision($user);

        $wallets = collect($service->walletsFor($user));
        $selected = $wallets->firstWhere('wallet_id', $this->wallet) ?? $wallets->first();

        return view('livewire.customer.dashboard', [
            'profile' => [
                'name' => $user->name,
                'phone' => $service->maskPhone((string) $user->phone_number),
                'kyc_status' => $user->kyc_status,
                'kyc_tier' => (int) $user->kyc_tier,
            ],
            'wallets' => $wallets->all(),
            'selected' => $selected,
            'selectedDetails' => $selected !== null
                ? $service->balanceDetails($user, $selected)
                : null,
            'portfolio' => $selected !== null ? $service->portfolioSummary($user) : null,
            'recentTransactions' => Transaction::query()
                ->where(fn ($q) => $q->where('sender_id', $user->id)->orWhere('receiver_id', $user->id))
                ->latest()
                ->take(5)
                ->get(),
            'exchangeAvailable' => $this->exchangeAvailable($service, $user, $wallets),
        ]);
    }

    private function exchangeAvailable(CustomerWalletService $service, $user, $wallets): bool
    {
        if ($wallets->count() < 2) {
            return false;
        }

        $codes = $wallets->pluck('currency_code')->all();

        try {
            $service->convert('1', $codes[0], $codes[1]);

            return true;
        } catch (\DomainException) {
            return false;
        }
    }
}
