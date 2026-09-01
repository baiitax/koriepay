<?php

namespace App\Livewire\Manager;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Treasury extends Component
{
    public $countryCode;
    public $currency;
    public $bankName;

    // Core Financials
    public $digitalLiability = 0;
    public $fiatInBank = 0;
    public $deficit = 0;
    public $reserveRatio = 0;
    public $commissionPool = 0;

    // Live Velocity (Simulated for dashboard effect)
    public $inflow24h = 0;
    public $outflow24h = 0;
    public $lastSync;

    public $topEarners = [];

    public function mount()
    {
        $this->countryCode = Auth::user()->country_code;
        $this->currency = $this->countryCode === 'NER' ? 'XOF' : 'NGN';
        $this->bankName = $this->countryCode === 'NER' ? 'Ecobank Niger' : 'Access Bank PLC';

        $this->calculateTreasury();
    }

    public function calculateTreasury()
    {
        // 1. Total Digital Liability (What we owe agents)
        $this->digitalLiability = Wallet::where('currency_code', $this->currency)
            ->whereHas('user', function($q) {
                $q->where('country_code', $this->countryCode);
            })->sum('balance');

        // 2. Simulated Fiat Bank Balance (Adds slight randomness to simulate live agent activity)
        // In a real app, this hits a Banking API (like Mono, Plaid, or NIBSS)
        $fluctuation = rand(-15000, 25000); 
        $this->fiatInBank = max(0, ($this->digitalLiability * 0.88) + $fluctuation);
        
        $this->deficit = max(0, $this->digitalLiability - $this->fiatInBank);
        
        // Calculate Reserve Health Ratio
        $this->reserveRatio = $this->digitalLiability > 0 ? round(($this->fiatInBank / $this->digitalLiability) * 100, 1) : 100;

        // 3. Simulated Commission Pool & Velocity
        $this->commissionPool = $this->digitalLiability * 0.015;
        $this->inflow24h = ($this->digitalLiability * 0.04) + rand(1000, 5000);
        $this->outflow24h = ($this->digitalLiability * 0.02) + rand(500, 3000);

        // 4. Top Earners
        $this->topEarners = User::where('role', 'agent')
            ->where('country_code', $this->countryCode)
            ->take(5)
            ->get();

        $this->lastSync = now()->format('H:i:s');
    }

    public function requestLiquidityWire()
    {
        sleep(1); // Simulate secure API handshake
        session()->flash('success', "Wire request for " . number_format($this->deficit) . " {$this->currency} authorized. Global Treasury routing funds.");
        
        // Force 100% reconciliation
        $this->fiatInBank = $this->digitalLiability;
        $this->deficit = 0;
        $this->reserveRatio = 100;
    }

    public function disburseCommissions()
    {
        sleep(1); // Simulate batch processing
        session()->flash('commission_success', "Successfully disbursed " . number_format($this->commissionPool) . " {$this->currency} to regional network.");
        $this->commissionPool = 0;
    }

    public function render()
    {
        return view('livewire.manager.treasury');
    }
}