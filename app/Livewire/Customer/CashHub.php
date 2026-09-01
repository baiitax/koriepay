<?php

namespace App\Livewire\Customer;

use Illuminate\Support\Facades\Auth;
use App\Models\Wallet;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.customer')]
class CashHub extends Component
{
    // Master Tab State
    public $activeTab = 'deposit'; 
    
    // Sub-Category States
    public $depositMethod = 'bank'; // 'bank' or 'agent'
    public $withdrawMethod = 'bank'; // 'bank' or 'agent'
    
    // Withdrawal Properties
    public $withdrawCurrency = 'NGN';
    public $withdrawAmount = '';
    public $linkedBank = '';
    public $transactionPin = '';

    // Virtual Account Properties
    public $virtualAccount = null;
    public $isGeneratingAccount = false;
    public $apiError = null;

    // Secure Agent Deposit Properties
    public $agentDepositAmount = '';
    public $activeDepositToken = null;
    public $tokenExpiryTime = null;

    // Secure Agent Withdrawal Properties
    public $agentWithdrawToken = null;
    public $withdrawExpiryTime = null;

    public function mount()
    {
        $this->loadOrGenerateVirtualAccount();
    }

    public function loadOrGenerateVirtualAccount()
    {
        $user = Auth::user();
        $ngnWallet = Wallet::where('user_id', $user->id)->where('currency_code', 'NGN')->first();

        // 1. Load existing Virtual Account if it exists in DB
        if ($ngnWallet && $ngnWallet->virtual_account_number) {
            $this->virtualAccount = [
                'bank' => $ngnWallet->virtual_account_bank,
                'number' => $ngnWallet->virtual_account_number,
                'name' => $ngnWallet->virtual_account_name,
            ];
            return;
        }

        // 2. Mock generating a new Virtual Account (Replaced with API call in Production)
        $this->isGeneratingAccount = true;
        
        $this->virtualAccount = [
            'bank' => 'Providus Bank',
            'number' => '990' . rand(1000000, 9999999),
            'name' => 'KoriePay / ' . $user->name,
        ];

        if ($ngnWallet) {
            $ngnWallet->update([
                'virtual_account_bank' => $this->virtualAccount['bank'],
                'virtual_account_number' => $this->virtualAccount['number'],
                'virtual_account_name' => $this->virtualAccount['name'],
            ]);
        }
        
        $this->isGeneratingAccount = false;
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetValidation();
    }

    public function setDepositMethod($method)
    {
        $this->depositMethod = $method;
        $this->resetValidation();
    }

    public function setWithdrawMethod($method)
    {
        $this->withdrawMethod = $method;
        $this->resetValidation();
    }

    // ==========================================
    // AGENT DEPOSIT LOGIC
    // ==========================================
    public function generateDepositToken()
    {
        $this->validate([
            'agentDepositAmount' => 'required|numeric|min:500|max:5000000',
        ]);

        // Generate cryptographically secure 6-digit token
        $this->activeDepositToken = random_int(100000, 999999);
        $this->tokenExpiryTime = now()->addMinutes(15)->format('H:i'); 
        
        session()->flash('token_success', 'Token strictly locked to ₦' . number_format($this->agentDepositAmount));
    }

    public function revokeDepositToken()
    {
        $this->activeDepositToken = null;
        $this->agentDepositAmount = '';
        $this->tokenExpiryTime = null;
    }

    // ==========================================
    // WITHDRAWAL LOGIC (Bank & Agent)
    // ==========================================
    public function processWithdrawal()
    {
        $user = Auth::user();

        // 1. AML COMPLIANCE GUARD: Restrict Bank Withdrawals to Tier 3 (Verified) Nodes
        if ($this->withdrawMethod === 'bank' && $user->kyc_status !== 'verified') {
            $this->addError('withdrawMethod', 'Regulatory Block: Bank withdrawals are strictly limited to Tier 3 (BVN Verified) nodes.');
            return;
        }

        // 2. Form Validation
        $this->validate([
            'withdrawCurrency' => 'required|in:NGN,XOF',
            'withdrawAmount' => 'required|numeric|min:1000',
            'transactionPin' => 'required|digits:4',
            'linkedBank' => $this->withdrawMethod === 'bank' ? 'required|string' : 'nullable'
        ]);

        // 3. Strict PIN Validation
        if (!\Illuminate\Support\Facades\Hash::check($this->transactionPin, $user->transaction_pin)) {
            $this->addError('transactionPin', 'Invalid Authorization PIN.');
            return;
        }

        // 4. Strict Balance Validation (Including Fees)
        $wallet = Wallet::where('user_id', $user->id)->where('currency_code', $this->withdrawCurrency)->first();
        $totalDeduction = $this->withdrawAmount + ($this->withdrawMethod === 'bank' ? 50 : 0); 

        if (!$wallet || $wallet->balance < $totalDeduction) {
            $this->addError('withdrawAmount', 'Insufficient liquidity including network fees.');
            return;
        }

        // 5. Freeze / Deduct the funds
        $wallet->balance -= $totalDeduction;
        $wallet->save();

        // 6. Branch outcome based on method
        if ($this->withdrawMethod === 'agent') {
            $this->agentWithdrawToken = random_int(10000000, 99999999); // 8-Digit Code
            $this->withdrawExpiryTime = now()->addMinutes(30)->format('H:i'); 
            session()->flash('withdraw_token_success', 'Funds frozen. Present this token to the Agent.');
            $this->transactionPin = ''; // Clear PIN for safety
        } else {
            session()->flash('success', 'Withdrawal of ' . $this->withdrawCurrency . ' ' . number_format($this->withdrawAmount) . ' initiated to your bank successfully.');
            $this->reset(['withdrawAmount', 'transactionPin', 'linkedBank']);
            $this->redirect(route('customer.dashboard'), navigate: true);
        }
    }

    public function revokeWithdrawToken()
    {
        $user = Auth::user();
        $wallet = Wallet::where('user_id', $user->id)->where('currency_code', $this->withdrawCurrency)->first();
        
        if ($wallet) {
            // Refund the previously frozen amount
            $wallet->balance += $this->withdrawAmount;
            $wallet->save();
        }

        $this->agentWithdrawToken = null;
        $this->withdrawAmount = '';
        $this->withdrawExpiryTime = null;
    }

    public function render()
    {
        return view('livewire.customer.cash-hub', [
            'user' => Auth::user()
        ]);
    }
}