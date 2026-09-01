<?php

namespace App\Livewire\Agent;

use App\Models\{Transaction, Wallet, AuditLog};
use Illuminate\Support\Facades\{Auth, Http, DB};
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;

#[Layout('layouts.agent')]
class FundWallet extends Component
{
    use WithFileUploads;

    public $corridor = 'NGN'; // NGN or XOF
    public $amount = '';
    
    // XOF Manual Funding Specifics
    public $selectedBank = '';
    public $receipt; 

    // Institutional Banks in Niger
    public $nigerBanks = [
        'ECOBANK' => ['name' => 'Ecobank Niger', 'account' => '123456789012'],
        'BOA' => ['name' => 'Bank of Africa (BOA)', 'account' => '098765432109'],
        'ORABANK' => ['name' => 'Orabank Niger', 'account' => '112233445566'],
        'CORIS' => ['name' => 'Coris Bank Int.', 'account' => '998877665544'],
    ];

    public function initiateFunding()
    {
        $this->validate([
            'amount' => 'required|numeric|min:1000',
            'corridor' => 'required|in:NGN,XOF'
        ]);

        if ($this->corridor === 'NGN') {
            return $this->processPaystackFunding();
        } else {
            return $this->processNigerWireTransfer();
        }
    }

    // =========================================================
    // NIGERIA: AUTOMATED PAYSTACK INFLOW
    // =========================================================
    private function processPaystackFunding()
    {
        $agent = Auth::user();
        $reference = 'KP-FUND-' . strtoupper(Str::random(12));
        
        // 1. Create Pending Transaction
        Transaction::create([
            'sender_id' => $agent->id,
            'receiver_name' => 'KoriePay Liquidity Pool',
            'source_currency' => 'NGN',
            'destination_currency' => 'NGN',
            'source_amount' => $this->amount,
            'exchange_rate' => 1.0,               // <-- Added
            'destination_amount' => $this->amount, // <-- Added
            'fee_charged' => 0,                   // <-- Added
            'status' => 'pending',
            'reference' => $reference,
            'type' => 'wallet_funding',
        ]);

        // 2. Call Paystack API
        try {
            $response = Http::withToken(env('PAYSTACK_SECRET_KEY'))
                ->post("https://api.paystack.co/transaction/initialize", [
                    'email' => $agent->email,
                    'amount' => $this->amount * 100, // Paystack uses Kobo (cents)
                    'reference' => $reference,
                    'callback_url' => route('agent.dashboard'), // Where they return after paying
                ]);

            if ($response->successful()) {
                $authUrl = $response->json()['data']['authorization_url'];
                return redirect()->away($authUrl); // Send Agent to secure checkout
            } else {
                session()->flash('error', 'Payment Gateway unreachable. Try again.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Secure handshake with Paystack failed.');
        }
    }

    // =========================================================
    // NIGER: INSTITUTIONAL WIRE TRANSFER
    // =========================================================
    private function processNigerWireTransfer()
    {
        $this->validate([
            'selectedBank' => 'required|string',
            'receipt' => 'required|image|max:5120', // Max 5MB
        ]);

        $agent = Auth::user();
        $reference = 'KP-XOF-' . strtoupper(Str::random(10));
        
        // Securely store the receipt in a private directory
        $receiptPath = $this->receipt->store("receipts/xof/{$agent->id}", 'local');

        DB::beginTransaction();
        try {
            Transaction::create([
                'sender_id' => $agent->id,
                'receiver_name' => $this->nigerBanks[$this->selectedBank]['name'],
                'source_currency' => 'XOF',
                'destination_currency' => 'XOF',
                'source_amount' => $this->amount,
                'exchange_rate' => 1.0,               // <-- Added
                'destination_amount' => $this->amount, // <-- Added
                'fee_charged' => 0,                   // <-- Added
                'status' => 'pending',
                'reference' => $reference,
                'type' => 'wallet_funding',
                'metadata' => json_encode(['receipt_path' => $receiptPath])
            ]);

            // Log for Compliance

            AuditLog::forceCreate([
                'user_id' => $agent->id, 'user_name' => $agent->name,
                'action' => 'AGENT_XOF_WIRE_LOGGED', 'event_type' => 'compliance',
                'metadata' => "Logged physical wire of {$this->amount} XOF to {$this->selectedBank}.",
                'ip_address' => request()->ip()
            ]);

            DB::commit();
            $this->reset(['amount', 'selectedBank', 'receipt']);
            session()->flash('success', "Wire transfer logged. Liquidity will be credited upon clearing.");

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', "Database fault: " . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.agent.fund-wallet');
    }
}