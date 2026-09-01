<?php

namespace App\Livewire\Aggregator;

use App\Domain\Aggregator\AggregatorProfileService;
use App\Domain\Aggregator\AggregatorTenantService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * AGGREGATOR CONSOLE — Stage I (profile & backend-sourced limits, §64–65).
 *
 * Identity from the aggregator record; limits derived from the ledger and
 * real records — never invented caps. Display-name updates are audited.
 */
#[Layout('layouts.aggregator')]
class Profile extends Component
{
    public string $displayName = '';
    public bool $editing = false;

    public function mount(): void
    {
        $aggregator = app(AggregatorTenantService::class)->current();
        $this->displayName = $aggregator?->name ?? '';
    }

    public function toggleEdit(): void
    {
        $this->editing = ! $this->editing;
    }

    public function saveName(AggregatorProfileService $service): void
    {
        $this->validate(['displayName' => ['required', 'string', 'max:160']]);

        $aggregator = app(AggregatorTenantService::class)->requireCurrent();
        $service->updateName($aggregator, auth()->user(), $this->displayName);

        $this->editing = false;
        $this->dispatch('toast', message: 'Display name updated.', type: 'success');
    }

    public function render(AggregatorProfileService $service): View
    {
        $aggregator = app(AggregatorTenantService::class)->current();

        if ($aggregator === null) {
            return view('livewire.aggregator.profile', ['notProvisioned' => true, 'payload' => null]);
        }

        return view('livewire.aggregator.profile', [
            'notProvisioned' => false,
            'payload' => $service->profile($aggregator),
        ]);
    }
}
