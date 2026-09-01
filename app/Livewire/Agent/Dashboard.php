<?php

namespace App\Livewire\Agent;

use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Carbon\Carbon;

#[Layout('layouts.agent')]
class Dashboard extends Component
{
    public function render()
    {
        $userId = Auth::id();
        $today = Carbon::today();

        return view('livewire.agent.dashboard', [
            // Recent Ledger Activity
            'recentActivity' => Transaction::where('sender_id', $userId)
                ->latest()
                ->take(8)
                ->get(),

            // Daily KPIs (Calculated strictly for today's physical operations)
            'cashInVolume' => Transaction::where('sender_id', $userId)
                ->where('type', 'cash_in')
                ->whereDate('created_at', $today)
                ->where('status', 'completed')
                ->sum('source_amount'),
                
            'cashOutVolume' => Transaction::where('sender_id', $userId)
                ->where('type', 'cash_out')
                ->whereDate('created_at', $today)
                ->where('status', 'completed')
                ->sum('source_amount'),
                
            'dailyOperations' => Transaction::where('sender_id', $userId)
                ->whereDate('created_at', $today)
                ->count(),
        ]);
    }
}