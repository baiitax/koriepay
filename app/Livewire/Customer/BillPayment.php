<?php

namespace App\Livewire\Customer;

use App\Models\{Wallet, Transaction};
use App\Traits\{HandlesPinSecurity, EnforcesVelocityLimits};
use Illuminate\Support\Facades\{Auth, DB, Cache, Log};
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.customer')]
class BillPayment extends Component
{
    use HandlesPinSecurity, EnforcesVelocityLimits;

    public $step = 1;
    public $type = 'airtime'; // airtime, data, power, tv
    
    // Form State
    public $identifier = ''; // Phone, Meter, or Smartcard number
    public $provider = '';   // mtn, airtel, dstv, ikedc, etc.
    public $amount = '';
    public $package_id = ''; // For data/tv packages
    
    public $balance = 0;
    public $fee = 0; // Airtime/Data is usually free, Power/TV might have a ₦100 fee
    public $transaction_pin = '';

    // Data Packages (Mocked for UI, usually fetched from API)
    public function getDataPackagesProperty()
    {
        if ($this->type !== 'data' || !$this->provider) return [];
        
        return [
            ['id' => '1', 'name' => '1.5GB - 30 Days', 'price' => 1000],
            ['id' => '2', 'name' => '3GB - 30 Days', 'price' => 1500],
            ['id' => '3', 'name' => '10GB - 30 Days', 'price' => 3000],
            ['id' => '4', 'name' => '20GB - 30 Days', 'price' => 5000],
        ];
    }

    public function mount($type = 'airtime')
    {
        $validTypes = ['airtime', 'data', 'power', 'tv'];
        $this->type = in_array($type, $validTypes) ? $type : 'airtime';
        $this->fee = in_array($this->type, ['power', 'tv']) ? 100 : 0;
        $this->updateBalance();
    }

    public function updateBalance()
    {
        $wallet = Auth::user()->wallets()->where('currency_code', 'NGN')->first();
        $this->balance = $wallet ? (float)$wallet->balance : 0;
    }

    public function setType($newType)
    {
        $this->type = $newType;
        $this->reset(['identifier', 'provider', 'amount', 'package_id']);
        $this->fee = in_array($this->type, ['power', 'tv']) ? 100 : 0;
        $this->resetErrorBag();
    }

    // TIER-1 UX: Auto-detect network provider as user types
    public function updatedIdentifier($value)
    {
        $this->resetErrorBag('identifier');
        
        if (in_array($this->type, ['airtime', 'data']) && strlen($value) >= 4) {
            $prefix = substr($value, 0, 4);
            
            $mtn = ['0803','0806','0703','0706','0813','0816','0810','0814','0903','0906','0913','0916'];
            $airtel = ['0802','0808','0708','0812','0701','0902','0907','0901','0912'];
            $glo = ['0805','0807','0705','0815','0811','0905','0915'];
            $mobile9 = ['0809','0818','0817','0909','0908'];

            if (in_array($prefix, $mtn)) $this->provider = 'MTN';
            elseif (in_array($prefix, $airtel)) $this->provider = 'Airtel';
            elseif (in_array($prefix, $glo)) $this->provider = 'Glo';
            elseif (in_array($prefix, $mobile9)) $this->provider = '9Mobile';
            else $this->provider = '';
        }
    }

    // Auto-set amount when a package is selected
    public function updatedPackageId($value)
    {
        if ($this->type === 'data' && $value) {
            $package = collect($this->dataPackages)->firstWhere('id', $value);
            $this->amount = $package ? $package['price'] : '';
        }
    }

    public function validateStepOne()
    {
        $rules = [
            'identifier' => 'required',
            'amount' => 'required|numeric|min:50',
            'provider' => 'required',
        ];

        if ($this->type === 'airtime' || $this->type === 'data') {
            $rules['identifier'] = 'required|digits:11';
        }

        $this->validate($rules);

        $totalDeduction = (float)$this->amount + $this->fee;

        if ($totalDeduction > $this->balance) {
            $this->addError('amount', 'Insufficient available balance.');
            return;
        }

        $this->step = 2;
    }

    public function processPayment()
    {
        $user = Auth::user();

        if (!$this->verifyPin($user, $this->transaction_pin)) { return; }

        $idempotencyKey = "bill_lock_{$user->id}_{$this->identifier}_{$this->amount}_{$this->type}";
        if (!Cache::add($idempotencyKey, true, 10)) { 
            $this->addError('transaction_pin', 'Transaction is processing.');
            return;
        }

        $reference = 'KP-VAS-' . strtoupper(Str::random(10));
        $totalDeduction = (float)$this->amount + $this->fee;

        try {
            DB::transaction(function () use ($user, $totalDeduction, $reference) {
                
                $wallet = Wallet::where('user_id', $user->id)
                    ->where('currency_code', 'NGN')
                    ->lockForUpdate()
                    ->first();

                if ($wallet->balance < $totalDeduction) {
                    throw new \Exception("Insufficient balance.");
                }
                
                $wallet->decrement('balance', $totalDeduction);

                // FIX: Added 'exchange_rate' and corrected 'fee' to 'fee_charged'
                Transaction::create([
                    'sender_id' => $user->id,
                    'receiver_id' => null, 
                    'receiver_name' => strtoupper($this->provider) . ' ' . strtoupper($this->type),
                    'type' => 'bill_payment', 
                    'source_currency' => 'NGN',
                    'destination_currency' => 'NGN',
                    'source_amount' => $totalDeduction, 
                    'destination_amount' => $this->amount, 
                    'exchange_rate' => 1.00,             // <--- Fixed here
                    'fee_charged' => $this->fee,         // <--- Fixed here
                    'status' => 'completed', 
                    'reference' => $reference,
                    'description' => ucfirst($this->type) . " Purchase - {$this->identifier}",
                ]);

                // In production, call your biller API (e.g., Reloadly, Flutterwave, VTpass) here
            });

            session()->flash('success', ucfirst($this->type) . ' purchase successful.');
            return redirect()->route('transaction.receipt', $reference);

        } catch (\Exception $e) {
            Cache::forget($idempotencyKey);
            Log::error('Bill Payment Failed: ' . $e->getMessage()); 
            $this->addError('transaction_pin', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.customer.bill-payment');
    }
}