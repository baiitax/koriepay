<?php

namespace App\Livewire\Regional;

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;

#[Layout('layouts.regional')]
class Dashboard extends Component
{
    public function render()
{
    $user = auth()->user();
    $regionId = $user->region_id ?? 0;

    // 1. Re-sync your Metrics (Ensure keys match the Blade view)
    $metrics = [
        'total_agents' => User::where('role', 'agent')->where('region_id', $regionId)->count(),
        'active_agents' => User::where('role', 'agent')->where('region_id', $regionId)->where('status', 'active')->count(),
        'pending_kyc' => User::where('role', 'agent')->where('region_id', $regionId)->where('kyc_status', 'pending')->count(),
        'volume_24h' => '42,500,000', // Placeholder
        'volume_trend' => '+14.2%',
        'regional_revenue' => '842,000', // Placeholder
        'revenue_trend' => '+5.2%',
        'agents_trend' => '+3 this week',
    ];

    // 2. Fetch data for the Main Table (All recent captures)
    $recentAgents = User::where('role', 'agent')
        ->where('region_id', $regionId)
        ->latest()
        ->take(5)
        ->get();

    // 3. THE FIX: Fetch data for the Action Queue (Only PENDING captures)
    $recent_kyc = User::where('role', 'agent')
        ->where('region_id', $regionId)
        ->where('kyc_status', 'pending')
        ->latest()
        ->take(5)
        ->get();

    return view('livewire.regional.dashboard', [
        'metrics' => $metrics,
        'recentAgents' => $recentAgents,
        'recent_kyc' => $recent_kyc, // Now line 167 in Blade will find this
    ]);
}
}