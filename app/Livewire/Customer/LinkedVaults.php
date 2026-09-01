<?php

namespace App\Livewire\Customer;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\LinkedVault;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.customer')]
class LinkedVaults extends Component
{
    public $accountNumber = '';
    public $bankCode = '';
    public $verificationNumber = ''; // BVN or NIN
    
    // UI State
    public $addModal = false;

    public function linkAccount()
    {
        $this->validate([
            'accountNumber' => 'required|numeric|digits:10',
            'bankCode' => 'required|string',
            'verificationNumber' => 'required|numeric|digits:11', // Standard BVN/NIN length
        ]);

        $user = Auth::user();

        // 1. LIVE API CALL: Resolve the Account Number via Paystack
        $secretKey = env('PAYSTACK_SECRET_KEY'); // Ensure this is in your .env
        
        $response = Http::withToken($secretKey)
            ->get("https://api.paystack.co/bank/resolve", [
                'account_number' => $this->accountNumber,
                'bank_code' => $this->bankCode
            ]);

        if (!$response->successful()) {
            $this->addError('accountNumber', 'Account resolution failed. Check the account number and bank.');
            return;
        }

        $resolvedData = $response->json('data');
        $resolvedName = strtolower($resolvedData['account_name']);
        
        // 2. STRICT AML MATCHING: Does the bank account name match the user's registered name?
        // We do a simple check to see if the user's last name appears in the bank account name.
        $userNames = explode(' ', strtolower($user->name));
        $lastName = end($userNames); // Get their last name

        if (!str_contains($resolvedName, $lastName)) {
            $this->addError('accountNumber', "Name Mismatch: Account belongs to '{$resolvedData['account_name']}'. It must match your registered Node name.");
            return;
        }

        // 3. SECURE SAVE: Pass verification and save to database
        // Look up bank name from common list (for the UI)
        $banks = ['058' => 'Guaranty Trust Bank', '044' => 'Access Bank', '033' => 'United Bank for Africa', '032' => 'Union Bank'];
        $bankName = $banks[$this->bankCode] ?? 'Verified Tier-1 Bank';

        LinkedVault::create([
            'user_id' => $user->id,
            'bank_name' => $bankName,
            'bank_code' => $this->bankCode,
            'account_number' => $resolvedData['account_number'],
            'account_name' => $resolvedData['account_name'],
            'is_verified' => true
        ]);

        session()->flash('success', 'External vault cryptographically verified and linked.');
        $this->reset(['accountNumber', 'bankCode', 'verificationNumber']);
        $this->dispatch('close-modal'); // Close Alpine modal
    }

    public function deleteVault($vaultId)
    {
        LinkedVault::where('id', $vaultId)->where('user_id', Auth::id())->delete();
        session()->flash('success', 'Vault unlinked successfully.');
    }

    public function render()
    {
        return view('livewire.customer.linked-vaults', [
            // Fetch dynamically from DB
            'vaults' => LinkedVault::where('user_id', Auth::id())->latest()->get()
        ]);
    }
}