<?php

namespace App\Livewire\admin;

use App\Models\RevenueLog;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class RevenueLedger extends Component
{
    public function render()
    {
        // Calculate Live Data directly from the Ledger
        $totalRevenueUSD = RevenueLog::sum('amount_usd');
        $feeIncome = RevenueLog::where('source', 'Flat Fee')->sum('amount_usd');
        $spreadIncome = RevenueLog::where('source', 'FX Spread')->sum('amount_usd');
        
        // Fetch the 10 most recent revenue pulses
        $recentLogs = RevenueLog::latest()->take(10)->get();

        return view('livewire.admin.revenue-ledger', [
            'totalRevenueUSD' => $totalRevenueUSD,
            'feeIncome' => $feeIncome,
            'spreadIncome' => $spreadIncome,
            'recentLogs' => $recentLogs
        ]);
    }
}