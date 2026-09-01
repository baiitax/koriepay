<?php

namespace App\Livewire\Agent;

use App\Models\{User, Wallet, Transaction, FxRate};
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\{Auth, DB};
use Illuminate\Support\Str;

#[Layout('layouts.agent')]
class CrossBorder extends Component
{
    public $step = 1;
    
    // Upgraded: Accepts Email, Phone, or Username
    public $receiverIdentifier = ''; 
    public ?User $receiver = null;
    
    public $sourceAmount = 0; 
    public $destinationCurrency = 'XOF';
    public $exchangeRate = 0;
    public $destinationAmount = 0;
    public $fee = 0;

    public function mount()
    {
        $rateRecord = FxRate::where('pair', "NGN/XOF")->first();
        $this->exchangeRate = $rateRecord ? (float)$rateRecord->rate : 0.55;
    }

    public function updatedSourceAmount()
    {
        if (is_numeric($this->sourceAmount) && $this->sourceAmount > 0) {
            $this->destinationAmount = (float)$this->sourceAmount * $this->exchangeRate;
            $this->fee = (float)$this->sourceAmount * 0.03; // 3% Institutional Spread
        } else {
            $this->destinationAmount = 0;
            $this->fee = 0;
        }
    }

    // PHASE 1: Omni-Channel Lookup
    public function verifyReceiver()
    {
        $this->validate([
            'receiverIdentifier' => 'required|string|min:3'
        ]);
        
        // Prevent Agent from sending to themselves and scan 3 columns
        $this->receiver = User::where('id', '!=', Auth::id())
            ->where(function ($query) {
                $query->where('email', $this->receiverIdentifier)
                      ->orWhere('phone', $this->receiverIdentifier)
                      ->orWhere('username', $this->receiverIdentifier);
            })->first();

        if (!$this->receiver) {
            $this->addError('receiverIdentifier', 'Counterparty not found in the Sahel Network.');
            return;
        }
        $this->step = 2;
    }

    // PHASE 2: Atomic Settlement
    public function executeSettlement()
    {
        $this->validate([
            'sourceAmount' => 'required|numeric|min:1000',
        ]);
        
        $agent = Auth::user();
        
        DB::beginTransaction();
        try {
            // Lock Agent Float
            $agentWallet = Wallet::where('user_id', $agent->id)
                ->where('currency_code', 'NGN')
                ->lockForUpdate()
                ->first();
            
            if (!$agentWallet || $agentWallet->balance < $this->sourceAmount) {
                throw new \Exception("Insufficient Liquidity. Please top up your NGN Float.");
            }

            // Lock/Create Counterparty Wallet
            $receiverWallet = Wallet::firstOrCreate(
                ['user_id' => $this->receiver->id, 'currency_code' => 'XOF'],
                ['balance' => 0]
            );

            // Execute Trade
            $agentWallet->decrement('balance', $this->sourceAmount);
            $receiverWallet->increment('balance', $this->destinationAmount);

            // Record FX Transaction
            $tx = Transaction::create([
                'sender_id' => $agent->id,
                'receiver_name' => $this->receiver->name,
                'source_currency' => 'NGN',
                'destination_currency' => 'XOF',
                'source_amount' => $this->sourceAmount,
                'exchange_rate' => $this->exchangeRate,
                'destination_amount' => $this->destinationAmount,
                'fee_charged' => $this->fee,
                'status' => 'completed',
                'reference' => 'FX-OTC-' . strtoupper(Str::random(8)),
                'type' => 'cross_border'
            ]);

            // Allocate Spread to Agent
            $agentWallet->increment('commission_balance', $this->fee);

            DB::commit();
            
            $this->reset(['step', 'receiverIdentifier', 'sourceAmount', 'destinationAmount', 'fee']);
            session()->flash('success', "FX Settlement Cleared. ₦" . number_format((float)$tx->fee_charged, 2) . " credited to your earnings.");

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', $e->getMessage());
        }
    }

    public function render() { return view('livewire.agent.cross-border'); }
}