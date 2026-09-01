<?php

namespace App\Livewire\Agent;

use App\Models\{User, Wallet, Transaction, AuditLog};
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\{Auth, DB};
use Illuminate\Support\Str;

#[Layout('layouts.agent')]
class CashOut extends Component
{
    public $customerIdentifier = '';
    public ?User $verifiedCustomer = null;
    public $customerBalance = 0;
    public $amount = '';
    
    public $step = 1; // 1: Search, 2: Amount/Request, 3: Verify Code
    public $authCodeInput = '';
    public $activeTransactionId = null;

    public function verifyCustomer()
    {
        $this->validate(['customerIdentifier' => 'required|string|min:3']);
        $this->verifiedCustomer = User::where('role', 'customer')
            ->where(function ($q) {
                $q->where('email', $this->customerIdentifier)->orWhere('phone', $this->customerIdentifier)->orWhere('username', $this->customerIdentifier);
            })->first();

        if (!$this->verifiedCustomer) {
            $this->addError('customerIdentifier', 'Customer not found.');
            return;
        }

        $wallet = Wallet::where('user_id', $this->verifiedCustomer->id)->where('currency_code', 'NGN')->first();
        $this->customerBalance = $wallet ? $wallet->balance : 0;
        $this->step = 2;
    }

    // PHASE 2: Generate Code (The Handshake)
    public function requestAuthorization()
    {
        $this->validate([
            'amount' => 'required|numeric|min:100|max:' . $this->customerBalance,
        ]);

        $generatedCode = rand(100000, 999999);

        // Create a PENDING transaction to hold the code
        $tx = Transaction::create([
            'sender_id' => Auth::id(),
            'receiver_name' => $this->verifiedCustomer->name,
            'source_currency' => 'NGN',
            'destination_currency' => 'NGN',
            'source_amount' => $this->amount,
            'exchange_rate' => 1.0,
            'destination_amount' => $this->amount,
            'status' => 'pending',
            'reference' => 'KP-OUT-' . strtoupper(Str::random(6)),
            'type' => 'cash_out',
            'auth_code' => $generatedCode // Store the secret code
        ]);

        $this->activeTransactionId = $tx->id;
        $this->step = 3;

        // DEVELOPER NOTE: In production, send $generatedCode via SMS/Email here.
        session()->flash('info', "AUTH CODE GENERATED: {$generatedCode} (Simulating SMS to customer)");
    }

    // PHASE 3: Final Authorization
    public function authorizeDispense()
    {
        $tx = Transaction::findOrFail($this->activeTransactionId);

        if ($this->authCodeInput != $tx->auth_code) {
            $this->addError('authCodeInput', 'Invalid Authorization Code. Transaction Denied.');
            return;
        }

    DB::beginTransaction();
    try {
        $fee = $tx->source_amount * 0.001; // 1% Fee
        $totalDeduction = $tx->source_amount + $fee;

        $customerWallet = Wallet::where('user_id', $this->verifiedCustomer->id)->where('currency_code', 'NGN')->lockForUpdate()->first();
        $agentWallet = Wallet::where('user_id', Auth::id())->where('currency_code', 'NGN')->lockForUpdate()->first();

        if ($customerWallet->balance < $totalDeduction) {
            throw new \Exception("Customer lacks funds for Principal + 1% Fee.");
        }

        // SETTLEMENT
        $customerWallet->decrement('balance', $totalDeduction);
        $agentWallet->increment('balance', $tx->source_amount); // Agent gets principal back in float
        $agentWallet->increment('commission_balance', $fee);   // Agent gets fee in commission vault

        $tx->update([
            'status' => 'completed',
            'fee_charged' => $fee,
            'auth_code' => null
        ]);

        AuditLog::forceCreate([
            'user_id' => Auth::id(), 'user_name' => Auth::user()->name,
            'action' => 'AGENT_CASH_OUT_AUTHORIZED', 'event_type' => 'transaction',
            'metadata' => "Authorized cash out of ₦" . number_format($tx->source_amount, 2) . " with ₦" . number_format($fee, 2) . " fee. Customer: {$this->verifiedCustomer->email}",
            'ip_address' => request()->ip()
        ]);
        
        try {
            $customer = User::where('email', $this->verifiedCustomer->email)->first();
            $agent = Auth::user();

            $customerWallet = Wallet::where('user_id', $customer->id)->where('currency_code', 'NGN')->lockForUpdate()->first();
            $agentWallet = Wallet::where('user_id', $agent->id)->where('currency_code', 'NGN')->lockForUpdate()->first();

            $customerWallet->decrement('balance', $tx->source_amount);
            $agentWallet->increment('balance', $tx->source_amount);

            $tx->update(['status' => 'completed', 'auth_code' => null]);

            AuditLog::forceCreate([
                'user_id' => $agent->id, 'user_name' => $agent->name,
                'action' => 'AGENT_CASH_OUT_AUTHORIZED', 'event_type' => 'transaction',
                'metadata' => "Dispensed ₦" . number_format($tx->source_amount, 2) . " using verified handshake code.",
                'ip_address' => request()->ip()
            ]);

            DB::commit();
            $this->reset();
            session()->flash('success', "Cash Dispense Authorized. Hand over physical cash.");
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', $e->getMessage());
        }
    }
    catch (\Exception $e) {
        DB::rollBack();
        session()->flash('error', $e->getMessage());
    }
}

    public function render() { return view('livewire.agent.cash-out'); }
}