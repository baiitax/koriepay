<?php

namespace App\Livewire\Customer\Adashi;

use App\Models\{AdashiGroup, AdashiMember};
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.customer')]
class Dashboard extends Component
{
    public function render()
    {
        $user = Auth::user();

        // Eager load the group, and the members of that group to prevent N+1 Query issues
        $memberships = AdashiMember::where('user_id', $user->id)
            ->with(['group', 'group.members.user']) 
            ->latest()
            ->get();

        // Categorize the pools
        $activePools = $memberships->filter(fn($m) => $m->group->status === 'active');
        $pendingPools = $memberships->filter(fn($m) => $m->group->status === 'pending');
        $completedPools = $memberships->filter(fn($m) => $m->group->status === 'completed');

        // Financial Summaries (Safely calculating totals)
        $totalCommitted = $activePools->isEmpty() ? 0 : $activePools->sum(function ($m) {
            return $m->group->contribution_amount;
        });

        $expectedPot = $activePools->isEmpty() ? 0 : $activePools->sum(function ($m) {
            return $m->group->contribution_amount * $m->group->max_members;
        });

        return view('livewire.customer.adashi.dashboard', [
            'activePools' => $activePools,
            'pendingPools' => $pendingPools,
            'completedPools' => $completedPools,
            'totalCommitted' => $totalCommitted,
            'expectedPot' => $expectedPot,
        ]);
    }
}