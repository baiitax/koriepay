<?php

namespace App\Livewire\Customer;

use App\Models\{Wallet, Transaction};
use App\Traits\HandlesPinSecurity;
use Illuminate\Support\Facades\{Auth, DB, Http, Log};
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.customer')]
class WithdrawVault extends Component
{
    use HandlesPinSecurity;

    public $step = 1;
    public $amount = '';
    public $currency = 'NGN';
    public $balance = 0;
    public $fee = 50.00;

    public $available_banks = [];
    public $bank_code = '';
    public $bank_name = '';
    public $account_number = '';
    public $resolved_account_name = '';
    public $transaction_pin = '';
    public $is_verifying = false;

    public function mount()
    {
        $this->updateBalance();
        $this->loadChannels();
    }

    public function updatedCurrency()
    {
        $this->reset(['amount', 'bank_code', 'account_number', 'resolved_account_name']);
        $this->updateBalance();
        $this->fee = $this->currency === 'NGN' ? 50.00 : 150.00;
        $this->loadChannels();
    }

    public function updateBalance()
    {
        $wallet = Auth::user()->wallets()->where('currency_code', $this->currency)->first();
        $this->balance = $wallet ? (float)$wallet->balance : 0;
    }

    public function loadChannels()
    {
        if ($this->currency === 'NGN') {
            $banks = [
                ['code' => '044', 'name' => 'Access Bank'],
                ['code' => '050', 'name' => 'Ecobank Nigeria'],
                ['code' => '070', 'name' => 'Fidelity Bank'],
                ['code' => '011', 'name' => 'First Bank of Nigeria'],
                ['code' => '214', 'name' => 'First City Monument Bank (FCMB)'],
                ['code' => '058', 'name' => 'Guaranty Trust Bank (GTCO)'],
                ['code' => '082', 'name' => 'Keystone Bank'],
                ['code' => '076', 'name' => 'Polaris Bank'],
                ['code' => '221', 'name' => 'Stanbic IBTC Bank'],
                ['code' => '232', 'name' => 'Sterling Bank'],
                ['code' => '032', 'name' => 'Union Bank of Nigeria'],
                ['code' => '033', 'name' => 'United Bank for Africa (UBA)'],
                ['code' => '215', 'name' => 'Unity Bank'],
                ['code' => '035', 'name' => 'Wema Bank'],
                ['code' => '057', 'name' => 'Zenith Bank'],
                ['code' => '090267', 'name' => 'Kuda Microfinance Bank'],
                ['code' => '090405', 'name' => 'Moniepoint Microfinance Bank'],
                ['code' => '999992', 'name' => 'OPay Digital Services'],
                ['code' => '090404', 'name' => 'PalmPay'],
            ];
        } else {
            $banks = [
                ['code' => 'AIRTEL_NE', 'name' => 'Airtel Money Niger'],
                ['code' => 'MOOV_NE', 'name' => 'Moov Money Niger'],
                ['code' => 'SONIBANK', 'name' => 'SONIBANK'],
                ['code' => 'ECO_NE', 'name' => 'Ecobank Niger'],
            ];
        }
        $this->available_banks = collect($banks)->sortBy('name')->values()->toArray();
    }

    public function updatedAccountNumber()
    {
        $this->resolved_account_name = '';
        $this->resetErrorBag('account_number');

        if (strlen($this->account_number) === 10 && $this->bank_code) {
            $this->resolveAccountViaGateway();
        }
    }

    public function updatedBankCode()
    {
        $this->resolved_account_name = '';

        // Find the bank name for the receipt
        $bank = collect($this->available_banks)->firstWhere('code', $this->bank_code);
        $this->bank_name = $bank ? $bank['name'] : '';

        if (strlen($this->account_number) === 10 && $this->bank_code) {
            $this->resolveAccountViaGateway();
        }
    }

    public function resolveAccountViaGateway()
    {
        $this->is_verifying = true;

        if ($this->currency === 'NGN') {
            $this->resolveViaPaystack();
        } else {
            $this->resolveViaDusuPay();
        }

        $this->is_verifying = false;
    }

    private function resolveViaPaystack()
    {
        try {
            // Replace SK_LIVE... with env('PAYSTACK_SECRET_KEY') in production
            $response = Http::withToken('' . env('PAYSTACK_SECRET_KEY'))
                ->timeout(10)
                ->get("https://api.paystack.co/bank/resolve", [
                    'account_number' => $this->account_number,
                    'bank_code' => $this->bank_code,
                ]);

            if ($response->successful()) {
                $this->resolved_account_name = $response->json('data.account_name');
            } else {
                $this->addError('account_number', 'Could not resolve account name. Verify details.');
            }
        } catch (\Exception $e) {
            Log::error("Paystack Resolve Error: " . $e->getMessage());
            $this->addError('account_number', 'Connection to verification gateway failed.');
        }
    }

    private function resolveViaDusuPay()
    {
        // Simulated for DusuPay
        sleep(1);
        $this->resolved_account_name = strtoupper(Auth::user()->name) . " (REGIONAL USER)";
    }

    public function validateStepOne()
    {
        $this->validate([
            'amount' => 'required|numeric|min:100',
            'bank_code' => 'required',
            'account_number' => 'required|digits:10',
        ]);

        if (($this->amount + $this->fee) > $this->balance) {
            $this->addError('amount', 'Insufficient Liquidity.');
            return;
        }

        if (!$this->resolved_account_name) {
            $this->addError('account_number', 'Please wait for account verification.');
            return;
        }

        $this->step = 2;
    }

    public function processWithdrawal()
    {
        $user = Auth::user();

        // Using your custom trait
        if (!$this->verifyPin($user, $this->transaction_pin)) {
            return;
        }

        $reference = 'KPW-' . strtoupper(Str::random(10));
        $totalDeduction = (float)$this->amount + $this->fee;

        try {
            $transaction = DB::transaction(function () use ($user, $totalDeduction, $reference) {
                $wallet = Wallet::where('user_id', $user->id)
                    ->where('currency_code', $this->currency)
                    ->lockForUpdate()
                    ->first();

                if ($wallet->balance < $totalDeduction) {
                    throw new \Exception("Insufficient balance.");
                }

                $wallet->decrement('balance', $totalDeduction);

                // Updated to match the actual Liquidity Grid schema
                return Transaction::create([
                    'sender_id' => $user->id,
                    'receiver_id' => null,
                    'receiver_name' => $this->resolved_account_name . " ({$this->bank_name})",
                    'type' => 'withdrawal',
                    'source_currency' => $this->currency,
                    'destination_currency' => $this->currency,
                    'source_amount' => $this->amount,
                    'destination_amount' => $this->amount,
                    'exchange_rate' => 1.00,
                    'fee_charged' => $this->fee,
                    'status' => 'completed',
                    'reference' => $reference,
                    'description' => "Withdrawal to " . $this->bank_code . " - " . $this->account_number,
                ]);
            });

            // Redirect straight to the Proof of Transaction!
            session()->flash('success', 'Withdrawal processed successfully.');
            return $this->redirect(route('transaction.receipt', $transaction->reference), navigate: true);

        } catch (\Exception $e) {
            $this->addError('transaction_pin', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.customer.withdraw-vault');
    }
}
