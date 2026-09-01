<?php

namespace App\Livewire\Manager;

use App\Models\{User, Wallet, Transaction, AuditLog};
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Carbon\Carbon;


#[Layout('layouts.app')]
class Dashboard extends Component
{
    public $countryCode;
    public $currency;
    public $countryName;

    // Core Metrics
    public $totalLiquidity = 0;
    public $frozenLiquidity = 0;
    public $activeAgents = 0;
    public $pendingKyc = 0;

    // Velocity Metrics
    public $todayVolume = 0;
    public $todayTxCount = 0;
    public $successRate = 100;

    // Lists
    public $recentTransactions = [];
    public $topAgents = [];

    public function mount()
    {
        $manager = Auth::user();
        $this->countryCode = $manager->country_code;
        $this->currency = $this->countryCode === 'NER' ? 'XOF' : 'NGN';
        $this->countryName = $this->countryCode === 'NER' ? 'Niger' : 'Nigeria';

        $this->loadRegionalData();
    }

    /**
     * Highly Optimized Regional Data Fetching
     */
    public function loadRegionalData()
    {
        $today = Carbon::today();

        // 1. Agent & KYC Metrics
        $this->activeAgents = User::where('role', 'agent')->where('country_code', $this->countryCode)->count();
        $this->pendingKyc = User::where('kyc_status', 'pending')->where('country_code', $this->countryCode)->where('role', 'agent')->count();

        // 2. Liquidity Metrics (Eager loaded for performance)
        $wallets = Wallet::where('currency_code', $this->currency)
            ->whereHas('user', fn($q) => $q->where('country_code', $this->countryCode));
        
        $this->totalLiquidity = $wallets->sum('balance');
        $this->frozenLiquidity = $wallets->sum('frozen_balance');

        // 3. Today's Velocity & Success Rate
        $regionalTransactions = Transaction::whereHas('user', fn($q) => $q->where('country_code', $this->countryCode))
            ->whereDate('created_at', $today);

        $this->todayVolume = (clone $regionalTransactions)->sum('amount');
        $this->todayTxCount = (clone $regionalTransactions)->count();
        
        $failedTx = (clone $regionalTransactions)->where('status', 'failed')->count();
        if ($this->todayTxCount > 0) {
            $this->successRate = round((($this->todayTxCount - $failedTx) / $this->todayTxCount) * 100, 1);
        }

        // 4. Data Grids: Using Database-level sorting for "Tier-1" scale
        $this->recentTransactions = Transaction::with('user')
            ->whereHas('user', fn($q) => $q->where('country_code', $this->countryCode))
            ->latest()
            ->take(6)
            ->get();

        // Database-driven sort for liquidity providers (Performance optimized)
        $this->topAgents = User::where('role', 'agent')
            ->where('country_code', $this->countryCode)
            ->join('wallets', 'users.id', '=', 'wallets.user_id')
            ->where('wallets.currency_code', $this->currency)
            ->select('users.*', 'wallets.balance as wallet_balance')
            ->orderByDesc('wallets.balance')
            ->take(4)
            ->get();
    }

    public function with(): array
    {
        return [
            'recentAudits' => AuditLog::where('user_id', auth()->id())
                ->with('targetAgent')
                ->latest()
                ->take(5)
                ->get(),
        ];
    }

    public function render()
    {
        // Fetch the 5 most recent security overrides for this manager
        $recentAudits = AuditLog::where('user_id', auth()->id())
            ->with('targetAgent')
            ->latest()
            ->take(5)
            ->get();

        // Pass the variable explicitly to the view
        return view('livewire.manager.dashboard', [
            'recentAudits' => $recentAudits
        ]);
    }
}