<?php

namespace App\Livewire\Agent;

use App\Models\{Transaction, Wallet, AuditLog};
use Illuminate\Support\Facades\{Auth, DB, Http};
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.agent')]
class CommissionDashboard extends Component
{
    // Form States
    public $withdrawSource = 'commission';
    public $amount = '';
    public $bankCode = ''; // Paystack uses Bank Codes (e.g., 058 for GTB)
    public $accountNumber = '';
    public $verifiedAccountName = '';
    
    public $isVerified = false;
    public $isLoading = false;

    // PHASE 1: Paystack Account Resolution
    public function updatedAccountNumber()
    {
        if (strlen($this->accountNumber) === 10 && !empty($this->bankCode)) {
            $this->verifyAccount();
        }
    }

    public function verifyAccount()
    {
        $this->isLoading = true;
        $this->isVerified = false;

        try {
            // Replace with your Paystack Secret Key from .env
            $response = Http::withToken(env('PAYSTACK_SECRET_KEY'))
                ->get("https://api.paystack.co/bank/resolve", [
                    'account_number' => $this->accountNumber,
                    'bank_code' => $this->bankCode,
                ]);

            if ($response->successful()) {
                $this->verifiedAccountName = $response->json()['data']['account_name'];
                $this->isVerified = true;
            } else {
                $this->addError('accountNumber', 'Could not resolve account. Check details.');
            }
        } catch (\Exception $e) {
            $this->addError('accountNumber', 'Identity Server Unreachable.');
        }

        $this->isLoading = false;
    }

    // PHASE 2: Secure Settlement
    public function processBankWithdrawal()
    {
        if (!$this->isVerified) {
            $this->addError('accountNumber', 'You must verify account identity first.');
            return;
        }

        $this->validate([
            'amount' => 'required|numeric|min:1000',
        ]);

        $agent = Auth::user();
        $wallet = Wallet::where('user_id', $agent->id)->where('currency_code', 'NGN')->lockForUpdate()->first();

        DB::beginTransaction();
        try {
            if ($this->withdrawSource === 'commission') {
                if ($wallet->commission_balance < $this->amount) throw new \Exception("Insufficient Commission Vault balance.");
                $wallet->decrement('commission_balance', $this->amount);
            } else {
                if ($wallet->balance < $this->amount) throw new \Exception("Insufficient Float Capital.");
                $wallet->decrement('balance', $this->amount);
            }

            Transaction::create([
                'sender_id' => $agent->id,
                'receiver_name' => $this->verifiedAccountName,
                'source_currency' => 'NGN',
                'destination_currency' => 'BANK',
                'source_amount' => $this->amount,
                'status' => 'pending',
                'reference' => 'STTL-' . strtoupper(Str::random(10)),
                'type' => 'bank_settlement',
                'bank_name' => $this->bankCode, // Store code for API payout later
                'account_number' => $this->accountNumber,
                'account_name' => $this->verifiedAccountName,
            ]);

            DB::commit();
            $this->reset(['amount', 'accountNumber', 'verifiedAccountName', 'isVerified']);
            session()->flash('success', "Settlement Queued for {$this->verifiedAccountName}");

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $agentId = Auth::id();
        
        // Fetch the Agent's NGN Wallet
        $wallet = Wallet::where('user_id', $agentId)->where('currency_code', 'NGN')->first();

        // RE-ADD THIS: Fetch recent settlements and commissions
        $history = Transaction::where('sender_id', $agentId)
                    ->whereIn('type', ['bank_settlement', 'cross_border', 'cash_out'])
                    ->latest()
                    ->take(10)
                    ->get();

        return view('livewire.agent.commission-dashboard', [
            'wallet' => $wallet,
            'history' => $history // Now the Blade file will see the variable
        ]);
    }
}