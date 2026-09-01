<?php

namespace App\Livewire\Manager;

use App\Models\{User, Wallet, Transaction};
use Illuminate\Support\Facades\{Auth, Hash, DB, Log};
use Illuminate\Support\Str;
use Livewire\{Component, WithPagination, Attributes\Layout};

#[Layout('layouts.app')]
class Agents extends Component
{
    use WithPagination;

    // Search & Thresholds
    public string $search = '';
    public int $lowBalanceThreshold = 50000; 
    public $countryCode;

    // Infrastructure States
    public bool $showDeployModal = false;
    public bool $showSuccessModal = false;
    public bool $showFundModal = false;
    
    // Result Data (Isolated from Form Resets)
    public array $deploymentResult = [];
    public ?User $targetAgent = null;
    public $injectionAmount = 0;

    // Form Inputs
    public string $name = '';
    public string $email = '';
    public string $phone = '';

    public function mount() {
        $this->countryCode = Auth::user()->country_code;
    }

    public function updatingSearch() {
        $this->resetPage();
    }

    /**
     * ATOMIC DEPLOYMENT: Provisions identity and liquidity in one transaction.
     */
    public function deployNode() {
        $this->validate([
            'name' => 'required|string|min:3',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|unique:users,phone_number',
        ]);

        try {
            DB::transaction(function () {
                $rawKey = 'SP-' . strtoupper(Str::random(12));
                
                $node = User::create([
                    'name' => $this->name,
                    'email' => strtolower($this->email),
                    'phone_number' => $this->phone,
                    'password' => Hash::make($rawKey),
                    'role' => 'agent',
                    'country_code' => $this->countryCode,
                    'is_active' => true,
                    'kyc_status' => 'verified'
                ]);

                Wallet::create([
                    'user_id' => $node->id,
                    'currency_code' => $this->countryCode === 'NGA' ? 'NGN' : 'XOF',
                    'balance' => 0.00,
                    'is_primary' => true,
                ]);

                // Store result before form reset
                $this->deploymentResult = [
                    'account' => $node->email,
                    'key' => $rawKey
                ];
            });

            $this->reset(['name', 'email', 'phone', 'showDeployModal']);
            $this->showSuccessModal = true;

        } catch (\Exception $e) {
            Log::error("Node Deployment Failure: " . $e->getMessage());
            session()->flash('error', 'Infrastructural provisioning failed.');
        }
    }

    /**
     * LIQUIDITY INJECTION: Targeted funding for low-balance nodes.
     */
    public function openFunding(int $userId) {
        $this->targetAgent = User::find($userId);
        $this->showFundModal = true;
    }

    public function executeFunding() {
        $this->validate(['injectionAmount' => 'required|numeric|min:500']);

        DB::transaction(function () {
            $wallet = $this->targetAgent->wallets()->where('is_primary', true)->first();
            $wallet->increment('balance', $this->injectionAmount);

            Transaction::create([
                'user_id' => $this->targetAgent->id,
                'wallet_id' => $wallet->id,
                'amount' => $this->injectionAmount,
                'type' => 'funding',
                'status' => 'completed',
                'direction' => 'in',
                'reference' => 'LIQ-' . strtoupper(Str::random(12)),
            ]);
        });

        $this->dispatch('notify', ['message' => 'Liquidity Synchronized']);
        $this->reset(['showFundModal', 'injectionAmount', 'targetAgent']);
    }

    /**
             * Log the event into the immutable audit table
             */
            private function audit(int $targetId, string $action, array $payload = [])
            {
                \App\Models\AuditLog::create([
                    'user_id' => Auth::id(),
                    'target_id' => $targetId,
                    'action' => $action,
                    'payload' => $payload,
                    'ip_address' => request()->ip(),
                ]);
            }

            public function processFunding()
            {
                $this->validate(['fundAmount' => 'required|numeric|min:1000']);

                DB::transaction(function () {
                    $wallet = $this->selectedAgent->wallets()->where('is_primary', true)->first();
                    $wallet->increment('balance', $this->fundAmount);

                    // ... existing Transaction creation ...

                    // NEW: Audit the funding
                    $this->audit($this->selectedAgent->id, 'FUNDING', [
                        'amount' => $this->fundAmount,
                        'currency' => $wallet->currency_code
                    ]);
                });

                $this->dispatch('notify', ['type' => 'success', 'message' => 'Liquidity synchronized & Audited.']);
                $this->reset(['showFundModal', 'fundAmount', 'selectedAgent']);
            }

            public function toggleStatus(int $userId)
            {
                $agent = User::findOrFail($userId);
                $agent->is_active = !$agent->is_active;
                $agent->save();

                // NEW: Audit the toggle
                $action = $agent->is_active ? 'UNFREEZE' : 'FREEZE';
                $this->audit($userId, $action);
                
                $this->dispatch('notify', ['type' => 'info', 'message' => "Terminal status changed to {$action}"]);
            }

    public function render() {
        $query = User::where('role', 'agent')
            ->where('country_code', $this->countryCode)
            ->with(['wallets' => fn($q) => $q->where('is_primary', true)]);

        if ($this->search) {
            $query->where(fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('phone_number', 'like', "%{$this->search}%"));
        }

        return view('livewire.manager.agents', [
            'agents' => $query->latest()->paginate(12)
        ]);
    }
}