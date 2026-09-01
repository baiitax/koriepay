<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

/**
 * Phase 4 — Agent & Aggregator directory.
 *
 * Agents and aggregators are users with the corresponding role. The
 * directory is country-aware (data isolation, directive §63) and
 * permission-gated by the route middleware.
 */
#[Layout('layouts.admin')]
class AgentDirectory extends Component
{
    use WithPagination;

    public $search = '';
    public $role = '';          // '' | agent | aggregator
    public $country = '';       // '' | NGA | NER

    public function render()
    {
        $query = User::query()
            ->whereIn('role', ['agent', 'aggregator'])
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('phone_number', 'like', "%{$this->search}%");
            }))
            ->when($this->role, fn ($q) => $q->where('role', $this->role))
            ->when($this->country, fn ($q) => $q->where('country_code', $this->country));

        return view('livewire.admin.agent-directory', [
            'agents' => (clone $query)
                ->orderByDesc('id')
                ->paginate(12),
            'totalAgents' => User::where('role', 'agent')->count(),
            'totalAggregators' => User::where('role', 'aggregator')->count(),
            'totalActive' => User::whereIn('role', ['agent', 'aggregator'])->where('is_active', true)->count(),
            'countries' => \App\Models\Country::query()->where('is_active', true)->get(),
        ]);
    }
}
