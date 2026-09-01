<?php

namespace App\Livewire\Manager;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class AgentReport extends Component
{
    use WithPagination;

    public $countryCode;
    public $currency;
    public $timeframe = '30'; // Default to 30 days

    public function mount()
    {
        $this->countryCode = Auth::user()->country_code;
        $this->currency = $this->countryCode === 'NGA' ? 'NGN' : 'XOF';
    }

    public function render()
    {
        // Calculate performance metrics for agents in the manager's region
        $performanceData = User::where('role', 'agent')
            ->where('country_code', $this->countryCode)
            ->withSum(['transactions as total_volume' => function($q) {
                $q->where('status', 'completed')
                  ->where('created_at', '>=', now()->subDays((int)$this->timeframe));
            }], 'amount')
            ->withSum(['transactions as total_fees' => function($q) {
                $q->where('status', 'completed')
                  ->where('created_at', '>=', now()->subDays((int)$this->timeframe));
            }], 'fee')
            ->latest()
            ->paginate(15);

        return view('livewire.manager.agent-report', [
            'performanceData' => $performanceData
        ]);
    }
}