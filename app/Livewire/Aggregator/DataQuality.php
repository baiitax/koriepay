<?php

namespace App\Livewire\Aggregator;

use App\Domain\Aggregator\AggregatorDataQualityService;
use App\Domain\Aggregator\AggregatorInsightsService;
use App\Domain\Aggregator\AggregatorTenantService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * AGGREGATOR CONSOLE — Stage I (data quality center).
 *
 * Live, tenant-scoped quality checks over the aggregator's own data. The
 * read-model recompute is gated on `network.analytics`; the page itself
 * requires `network.view`.
 */
#[Layout('layouts.aggregator')]
class DataQuality extends Component
{
    public function recomputeReadModel(AggregatorInsightsService $insights): void
    {
        abort_unless(Gate::forUser(auth()->user())->allows('network.analytics'), 403, 'Unauthorized: missing permission [network.analytics].');

        $aggregator = app(AggregatorTenantService::class)->requireCurrent();
        $count = $insights->backfill($aggregator, now()->subDays(30)->startOfDay(), now());

        $this->dispatch('toast', message: 'Read-model refreshed for '.$count.' days.', type: 'success');
    }

    public function render(AggregatorDataQualityService $service): View
    {
        $aggregator = app(AggregatorTenantService::class)->current();

        if ($aggregator === null) {
            return view('livewire.aggregator.data-quality', ['notProvisioned' => true, 'payload' => null, 'canRecompute' => false]);
        }

        return view('livewire.aggregator.data-quality', [
            'notProvisioned' => false,
            'payload' => $service->scan($aggregator),
            'canRecompute' => Gate::forUser(auth()->user())->allows('network.analytics'),
        ]);
    }
}
