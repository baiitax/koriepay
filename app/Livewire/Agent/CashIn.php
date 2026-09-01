<?php

namespace App\Livewire\Agent;

use App\Models\{User, Wallet, Transaction, AuditLog};
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\{Auth, DB};
use Illuminate\Support\Str;

#[Layout('layouts.agent')]
class CashIn extends Component
{
    public $customerIdentifier = ''; // Accepts Email, Phone, or Username
    public ?User $verifiedCustomer = null; // Holds the locked profile
    public $amount = '';

    // PHASE 1: Query and Lock the Target
    public function verifyCustomer()
    {
        $this->validate([
            'customerIdentifier' => 'required|string|min:3',
        ]);

        // Search across 3 different index vectors
        $this->verifiedCustomer = User::where('role', 'customer')
            ->where(function ($query) {
                $query->where('email', $this->customerIdentifier)
                      ->orWhere('phone', $this->customerIdentifier)
                      ->orWhere('username', $this->customerIdentifier);
            })->first();

        if (!$this->verifiedCustomer) {
            $this->addError('customerIdentifier', 'No customer found matching this credential.');
        }
    }

    // RESET: Clear the locked profile if they searched the wrong person
    public function resetVerification()
    {
        $this->reset(['verifiedCustomer', 'customerIdentifier', 'amount']);
    }

    // PHASE 2: Execute the Settlement
    public function processCashIn()
    {
        if (!$this->verifiedCustomer) return;

        $this->validate([
            'amount' => 'required|numeric|min:100',
        ]);

        $agent = Auth::user();
        $customer = $this->verifiedCustomer;

        DB::beginTransaction();
        
        try {
            $agentWallet = Wallet::where('user_id', $agent->id)->where('currency_code', 'NGN')->lockForUpdate()->first();

            if (!$agentWallet || $agentWallet->balance < $this->amount) {
                throw new \Exception('Insufficient Agent Float to process this deposit.');
            }

            $customerWallet = Wallet::firstOrCreate(
                ['user_id' => $customer->id, 'currency_code' => 'NGN'],
                ['balance' => 0]
            );

            // Execute Settlement
            $agentWallet->decrement('balance', $this->amount);
            $customerWallet->increment('balance', $this->amount);

            // Record Master Transaction
            $tx = Transaction::create([
                'sender_id' => $agent->id,
                'receiver_name' => $customer->name,
                'source_currency' => 'NGN',
                'destination_currency' => 'NGN',
                'source_amount' => $this->amount,
                'exchange_rate' => 1.0,
                'destination_amount' => $this->amount,
                'status' => 'completed',
                'reference' => 'KP-CASHIN-' . strtoupper(Str::random(6)),
                'type' => 'cash_in'
            ]);

            AuditLog::forceCreate([
                'user_id' => $agent->id,
                'user_name' => $agent->name,
                'action' => 'AGENT_CASH_IN',
                'event_type' => 'transaction',
                'metadata' => "Processed physical cash deposit of ₦" . number_format($this->amount, 2) . " for Customer [{$customer->email}]. Ref: {$tx->reference}",
                'ip_address' => request()->ip()
            ]);

            DB::commit();

            $this->resetVerification();
            session()->flash('success', "Cash-In Authorized. ₦" . number_format($tx->source_amount, 2) . " credited to {$customer->name}. Ref: {$tx->reference}");

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.agent.cash-in');
    }
}