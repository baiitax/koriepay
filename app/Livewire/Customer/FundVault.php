<?php

namespace App\Livewire\Customer;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.customer')]
class FundVault extends Component
{
    public $activeTab = 'bank'; // Defaults to 'bank', can be 'card'
    public $amount = '';

    // Pre-set quick amounts for the card UI
    public $quickAmounts = [2000, 5000, 10000, 50000];

    public function setAmount($value)
    {
        $this->amount = $value;
    }

    public function initiateCardPayment()
    {
        $this->validate([
            'amount' => 'required|numeric|min:100|max:1000000',
        ]);

        // In a live environment, you would call Paystack/Flutterwave API here.
        // e.g., $response = Paystack::initiate(['amount' => $this->amount * 100, ...]);
        // return redirect($response->authorization_url);

        $reference = 'KORIE-TOPUP-' . Str::upper(Str::random(8));
        
        session()->flash('success', 'Gateway initialized for ₦' . number_format($this->amount) . '. (API Pending)');
        
        // Reset after mock initialization
        $this->reset('amount');
    }

    public function render()
    {
        $user = Auth::user();
        
        // We assume the user's NGN wallet is their primary funding target for now
        $ngnWallet = $user->wallets()->where('currency_code', 'NGN')->first();

        return view('livewire.customer.fund-vault', [
            'user' => $user,
            'ngnWallet' => $ngnWallet,
        ]);
    }
}