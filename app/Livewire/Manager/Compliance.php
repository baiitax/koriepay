<?php

namespace App\Livewire\Manager;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Compliance extends Component
{
    public $countryCode;
    public $dailyLimit = 5000000; // Example: 5M NGN/XOF daily limit

    public function mount()
    {
        $this->countryCode = Auth::user()->country_code;
    }

    public function render()
    {
        // Fetch agents and their daily volume to check against limits
        $limitData = User::where('role', 'agent')
            ->where('country_code', $this->countryCode)
            ->withSum(['transactions as daily_volume' => function($q) {
                $q->where('status', 'completed')
                  ->where('created_at', '>=', now()->startOfDay());
            }], 'amount')
            ->get()
            ->map(function($agent) {
                $agent->usage_percent = ($agent->daily_volume / $this->dailyLimit) * 100;
                return $agent;
            })
            ->sortByDesc('usage_percent');

        return view('livewire.manager.compliance', compact('limitData'));
    }
}