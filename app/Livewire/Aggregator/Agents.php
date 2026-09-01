<?php

namespace App\Livewire\Aggregator;

use App\Domain\Aggregator\AggregatorAgentsService;
use App\Domain\Aggregator\AggregatorTenantService;
use App\Models\Aggregator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * AGGREGATOR CONSOLE — Stage B (agents directory, §14–22).
 *
 * Server-paginated network directory with honest live stats: filters,
 * search by code/name/phone/email, and an onboarding-pipeline strip whose
 * numbers come from real Agent rows. An aggregator without a provisioned
 * profile sees an honest empty state, never fabricated rows.
 */
#[Layout('layouts.aggregator')]
class Agents extends Component
{
    public string $search = '';
    public string $status = '';
    public string $kyc = '';
    public string $region = '';
    public string $city = '';
    public string $sort = 'newest';
    public int $perPage = 10;
    public int $page = 1;

    public function updatedSearch(): void
    {
        $this->page = 1;
    }

    public function updatedStatus(): void
    {
        $this->page = 1;
    }

    public function updatedKyc(): void
    {
        $this->page = 1;
    }

    public function updatedRegion(): void
    {
        $this->page = 1;
        $this->city = '';
    }

    public function updatedCity(): void
    {
        $this->page = 1;
    }

    public function updatedSort(): void
    {
        $this->page = 1;
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'status', 'kyc', 'region', 'city', 'sort']);
        $this->page = 1;
    }

    public function gotoPage(int $page): void
    {
        $this->page = max(1, $page);
    }

    public function render(AggregatorAgentsService $service): View
    {
        // Resolved fresh per request — protected state is not persisted
        // across Livewire wire-calls, and the tenant row must never be stale.
        $aggregator = app(AggregatorTenantService::class)->current();

        if ($aggregator === null) {
            return view('livewire.aggregator.agents', [
                'notProvisioned' => true,
                'payload' => null,
            ]);
        }

        $payload = $service->directory($aggregator, [
            'search' => $this->search,
            'status' => $this->status,
            'kyc_status' => $this->kyc,
            'region' => $this->region,
            'city' => $this->city,
            'sort' => $this->sort,
        ], $this->perPage, $this->page);

        return view('livewire.aggregator.agents', [
            'notProvisioned' => false,
            'payload' => $payload,
        ]);
    }
}
