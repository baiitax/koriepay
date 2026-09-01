<?php

namespace App\Livewire\Manager;

use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Forecaster extends Component
{
    public $currentRevenue = 0;
    public $projectedRevenue = 0;
    public $growthRate = 0;

    public function mount()
    {
        $countryCode = Auth::user()->country_code;

        // 1. Revenue this month
        $this->currentRevenue = Transaction::whereHas('user', fn($q) => $q->where('country_code', $countryCode))
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('fee');

        // 2. Revenue last month (for growth calculation)
        $lastMonthRevenue = Transaction::whereHas('user', fn($q) => $q->where('country_code', $countryCode))
            ->where('status', 'completed')
            ->whereBetween('created_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])
            ->sum('fee');

        // 3. Simple Linear Projection
        if ($lastMonthRevenue > 0) {
            $this->growthRate = (($this->currentRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100;
            $this->projectedRevenue = $this->currentRevenue * (1 + ($this->growthRate / 100));
        } else {
            $this->projectedRevenue = $this->currentRevenue; // No baseline data
        }
    }

    public function render()
    {
        return view('livewire.manager.forecaster');
    }
}