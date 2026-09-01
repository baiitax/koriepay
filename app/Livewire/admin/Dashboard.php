<?php

namespace App\Livewire\admin;

use Livewire\Component;
use App\Models\{Transaction, BankNode, RevenueLog};
use Livewire\Attributes\On; 
use Livewire\Attributes\Layout;
use Carbon\Carbon;

#[Layout('layouts.admin')]
class Dashboard extends Component
{
    public $chartDays = [];
    public $chartVolumes = [];

    public function mount()
    {
        $this->loadChartData();
    }

    public function loadChartData()
    {
        $days = [];
        $volumes = [];

        // Generate the last 7 days of transaction volume
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $days[] = $date->format('M d');
            
            $dailyVolume = Transaction::whereDate('created_at', $date)
                                      ->where('status', 'completed')
                                      ->sum('source_amount');
            $volumes[] = round($dailyVolume, 2);
        }

        $this->chartDays = $days;
        $this->chartVolumes = $volumes;
    }

    // The Magic WebSocket Listener
    #[On('echo-private:admin-grid,pulse.update')]
    public function refreshDashboard($data)
    {
        // Re-calculate the chart data when a live pulse hits
        $this->loadChartData();
        
        // Dispatch an event to the browser to tell the JS Chart to redraw
        $this->dispatch('update-chart', series: $this->chartVolumes, categories: $this->chartDays);
    }

    public function render()
    {
        return view('livewire.admin.dashboard', [
            // Global Metrics
            'totalVolume' => Transaction::where('status', 'completed')->sum('source_amount'),
            'totalRevenue' => RevenueLog::sum('amount_usd'),
            'activeLiquidity' => BankNode::sum('balance'),
            'successRate' => Transaction::count() > 0 
                ? round((Transaction::where('status', 'completed')->count() / Transaction::count()) * 100, 1) 
                : 0,
            
            // The Live Feed
            'recentTransactions' => Transaction::latest()->take(6)->get(),
        ]);
    }
}