<?php

namespace App\Livewire\Manager;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Risk extends Component
{
    public $countryCode;
    public $currency;

    public function mount()
    {
        $this->countryCode = Auth::user()->country_code;
        $this->currency = $this->countryCode === 'NER' ? 'XOF' : 'NGN';
    }

    // Toggle Agent Account Lock
    public function toggleAccountLock($userId)
    {
        $agent = User::where('id', $userId)->where('country_code', $this->countryCode)->first();
        if ($agent && $agent->role === 'agent') {
            $agent->is_active = !$agent->is_active;
            $agent->save();
            
            $status = $agent->is_active ? 'Unfrozen' : 'Frozen';
            session()->flash('status', "Terminal for {$agent->name} has been {$status}.");
        }
    }

    public function render()
    {
        // 1. Find all frozen accounts in the region
        $frozenAgents = User::where('role', 'agent')
            ->where('country_code', $this->countryCode)
            ->where('is_active', 0)
            ->get();

        // 2. Find "High Value" transactions (e.g., above 1M NGN or XOF equivalent)
        $threshold = $this->currency === 'NGN' ? 1000000 : 500000;
        
        $flaggedTransactions = Transaction::with('user')
            ->whereHas('user', function($q) {
                $q->where('country_code', $this->countryCode);
            })
            ->where('amount', '>=', $threshold)
            ->latest()
            ->take(10)
            ->get();

        // 3. Find all agents to populate the "Manual Freeze" dropdown
        $allAgents = User::where('role', 'agent')
            ->where('country_code', $this->countryCode)
            ->where('is_active', 1)
            ->get();

        return view('livewire.manager.risk', compact('frozenAgents', 'flaggedTransactions', 'allAgents', 'threshold'));
    }
}