<?php

namespace App\Livewire\Customer;

use App\Models\{User, Wallet, Transaction};
use App\Traits\{HandlesPinSecurity, EnforcesVelocityLimits};
use App\Notifications\FundsReceived;
use Illuminate\Support\Facades\{Auth, DB, Cache, Log};
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.customer')]
class SendLiquidity extends Component
{
    use HandlesPinSecurity, EnforcesVelocityLimits;

    public $step = 1;
    
    public $amount = '';
    public $from_currency = 'NGN';
    public $to_currency = 'NGN';
    public $recipient_identifier = ''; 
    
    public $transaction_pin = '';
    
    public $balance = 0;
    public $exchange_rate = 1.0;
    public $fee = 0;
    
    public ?User $recipient = null;

    public function mount()
    {
        $this->updateBalance();
    }

    public function updatedFromCurrency() { $this->updateBalance(); $this->calculateFX(); }
    public function updatedToCurrency() { $this->calculateFX(); }
    public function updatedAmount() { $this->calculateFX(); }
    
    public function updatedRecipientIdentifier()
    {
        $this->resetErrorBag('recipient_identifier');
        $this->recipient = null;
        
        $rawInput = trim($this->recipient_identifier);
        if (empty($rawInput)) return;

        $usernameOrEmail = ltrim($rawInput, '@'); 
        $cleanPhone = preg_replace('/[^0-9]/', '', $rawInput); 

        $this->recipient = User::where('id', '!=', Auth::id())
            ->where(function ($query) use ($usernameOrEmail, $cleanPhone, $rawInput) {
                $query->where('email', $usernameOrEmail)->orWhere('username', $usernameOrEmail);
                if (!empty($cleanPhone)) {
                     $query->orWhere('phone_number', $cleanPhone)
                           ->orWhere('phone_number', '0' . $cleanPhone)
                           ->orWhere('phone_number', '+' . $cleanPhone)
                           ->orWhere('phone_number', $rawInput); 
                }
            })->first();
            
        if (!$this->recipient && strlen($usernameOrEmail) >= 3) {
            $this->addError('recipient_identifier', 'No KoriePay user found with these details.');
        }
    }

    public function updateBalance()
    {
        $wallet = Auth::user()->wallets()->where('currency_code', $this->from_currency)->first();
        $this->balance = $wallet ? (float)$wallet->balance : 0;
    }

    public function calculateFX()
    {
        if (empty($this->amount)) {
            $this->fee = 0;
            return;
        }

        if ($this->from_currency === $this->to_currency) {
            $this->exchange_rate = 1.0;
            $this->fee = 0; 
        } else {
            $cacheKey = "FX_{$this->from_currency}_{$this->to_currency}";
            $defaultRate = ($this->from_currency === 'NGN') ? 0.42 : 2.38;
            $this->exchange_rate = Cache::get($cacheKey, $defaultRate);
            $this->fee = (float)$this->amount * 0.015; 
        }
    }

    public function validateStepOne()
    {
        $this->validate([
            'amount' => 'required|numeric|min:50',
            'recipient_identifier' => 'required',
        ]);

        if (!$this->recipient) {
            $this->addError('recipient_identifier', 'Please select a valid recipient.');
            return;
        }

        $totalRequired = (float)$this->amount + (float)$this->fee;
        if ($totalRequired > $this->balance) {
            $this->addError('amount', 'Insufficient available balance.');
            return;
        }

        // Validate Sender & Receiver Limits gracefully
        if (!$this->validateLimits($totalRequired)) {
            return; 
        }

        $this->step = 2;
    }

    private function validateLimits($totalRequired)
    {
        // 1. Sender Daily Limit Check
        $senderLimit = Auth::user()->kyc_status === 'verified' ? 5000000 : 50000; 
        
        // FIX: Remove 'is_credit' query. If sender_id matches Auth::id(), it's inherently a debit.
        $spentToday = Transaction::where('sender_id', Auth::id())
            ->whereDate('created_at', today())
            ->sum('source_amount'); 
        
        if (($spentToday + $totalRequired) > $senderLimit) {
            $this->addError('amount', "This transfer exceeds your daily limit of ₦" . number_format($senderLimit) . ". Please verify your ID to increase limits.");
            return false;
        }

        // 2. Receiver Capacity Check
        $receiverWallet = $this->recipient->wallets()->firstOrCreate(['currency_code' => $this->to_currency], ['balance' => 0]);
        $creditAmount = (float)$this->amount * $this->exchange_rate;
        $receiverCapacity = $this->recipient->kyc_status === 'verified' ? 10000000 : 300000;

        if (($receiverWallet->balance + $creditAmount) > $receiverCapacity) {
            $this->addError('amount', "Transfer restricted: The recipient's account cannot hold this balance due to their current tier limits.");
            return false;
        }

        return true;
    }

    public function processTransfer()
    {
        $user = Auth::user();

        if (!$this->verifyPin($user, $this->transaction_pin)) { return; }

        $idempotencyKey = "transfer_lock_{$user->id}_{$this->recipient->id}_{$this->amount}_{$this->from_currency}";
        
        if (!Cache::add($idempotencyKey, true, 10)) { 
            $this->addError('transaction_pin', 'Transaction is already processing. Please wait.');
            return;
        }

        $reference = 'KP-SND-' . strtoupper(Str::random(8));

        try {
            DB::transaction(function () use ($user, $reference) {
                
                $senderWallet = $user->wallets()->where('currency_code', $this->from_currency)->lockForUpdate()->first();
                $receiverWallet = $this->recipient->wallets()->where('currency_code', $this->to_currency)->lockForUpdate()->first();

                $totalDeduction = (float)$this->amount + (float)$this->fee;
                $creditAmount = (float)$this->amount * $this->exchange_rate;

                if ($senderWallet->balance < $totalDeduction) {
                    throw new \Exception("Insufficient balance.");
                }

                // Adjust balances atomically
                $senderWallet->decrement('balance', $totalDeduction);
                $receiverWallet->increment('balance', $creditAmount);

                // FIX: Single-Entry Ledger insertion. 
                // We drop `is_credit` and `user_id` to respect your database schema.
                Transaction::create([
                    'sender_id' => $user->id,
                    'receiver_id' => $this->recipient->id,
                    'type' => 'p2p_transfer',
                    'currency' => $this->from_currency,
                    'source_amount' => $totalDeduction,
                    'destination_amount' => $creditAmount,
                    'fee' => $this->fee,
                    'status' => 'completed',
                    'reference' => $reference,
                    'description' => "Transfer to {$this->recipient->name}"
                ]);

                if(class_exists(FundsReceived::class)){
                    $this->recipient->notify(new FundsReceived($creditAmount, $this->to_currency, $user->name));
                }
            });

            session()->flash('success', 'Transfer successful!');
            return redirect()->route('transaction.receipt', $reference);

        } catch (\Exception $e) {
            Cache::forget($idempotencyKey);
            Log::error('P2P Transfer Failed: ' . $e->getMessage()); 
            $this->addError('transaction_pin', 'Transfer failed: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.customer.send-liquidity');
    }
}