<?php

namespace App\Livewire\Aggregator;

use App\Domain\Agency\AgencyService;
use App\Domain\Aggregator\AggregatorAgentsService;
use App\Domain\Aggregator\AggregatorTenantService;
use App\Models\Agent;
use App\Models\Aggregator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * AGGREGATOR CONSOLE — Stage B (agent profile, §15–22).
 *
 * Ten tabs of real, tenant-scoped data: overview, KYC, transactions,
 * liquidity, commissions, performance (§17–19), risk, devices (honest
 * empty state), support and audit. Status changes go through the backend
 * AgencyService (never a frontend write, §15) and are permission-gated
 * server-side (`agent.suspend` / `agent.reactivate`).
 */
#[Layout('layouts.aggregator')]
class AgentProfile extends Component
{
    public Agent $agent;
    public string $tab = 'overview';
    public int $page = 1;

    public function mount(Agent $agent, AggregatorTenantService $tenant): void
    {
        // Tenant ownership guard — a foreign agent 404s (IDOR §133). Runs on
        // every request (mount + wire-calls re-mount in Livewire tests).
        if ($tenant->current() === null || ! $tenant->ownsAgent($tenant->current(), $agent)) {
            abort(404, 'Agent not found in this network.');
        }

        $this->agent = $agent;
    }

    public function switchTab(string $tab): void
    {
        $allowed = ['overview', 'kyc', 'transactions', 'liquidity', 'commissions', 'performance', 'risk', 'devices', 'support', 'audit'];
        $this->tab = in_array($tab, $allowed, true) ? $tab : 'overview';
        $this->page = 1;
    }

    public function gotoPage(int $page): void
    {
        $this->page = max(1, $page);
    }

    public function suspend(AgencyService $agency): void
    {
        Gate::authorize('agent.suspend');
        $this->assertOwned();
        $agency->suspendAgent($this->agent, auth()->id(), 'Suspended from the aggregator console.');
        $this->agent->refresh();
        $this->dispatch('toast', message: 'Agent suspended.', type: 'warning');
    }

    public function reactivate(AgencyService $agency): void
    {
        Gate::authorize('agent.reactivate');
        $this->assertOwned();
        $agency->reactivateAgent($this->agent, auth()->id());
        $this->agent->refresh();
        $this->dispatch('toast', message: 'Agent reactivated.', type: 'success');
    }

    /**
     * Re-check tenant ownership on wire-calls (IDOR §133): the protected
     * aggregator is never trusted — resolve the current tenant fresh.
     */
    protected function assertOwned(): void
    {
        $aggregator = app(AggregatorTenantService::class)->current();
        if ($aggregator === null || ! app(AggregatorTenantService::class)->ownsAgent($aggregator, $this->agent)) {
            abort(404, 'Agent not found in this network.');
        }
    }

    public function render(AggregatorAgentsService $service): View
    {
        $aggregator = app(AggregatorTenantService::class)->current();
        $payload = $service->agentProfile($aggregator, $this->agent, $this->tab);

        return view('livewire.aggregator.agent-profile', [
            'payload' => $payload,
            'canSuspend' => Gate::allows('agent.suspend'),
            'canReactivate' => Gate::allows('agent.reactivate'),
        ]);
    }
}
