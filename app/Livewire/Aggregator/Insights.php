<?php

namespace App\Livewire\Aggregator;

use App\Domain\Aggregator\AggregatorInsightsService;
use App\Domain\Aggregator\AggregatorObservabilityService;
use App\Domain\Aggregator\AggregatorTenantService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * AGGREGATOR CONSOLE — Stage I (insights, observability & EOD, §96–110).
 *
 * EOD summary, network growth/retention/productivity (from the derived read
 * model + live records), and honest observability indicators. Snapshot
 * actions require `network.analytics`; the page requires `network.view`.
 */
#[Layout('layouts.aggregator')]
class Insights extends Component
{
    public string $eodDate = '';

    public function mount(): void
    {
        $this->eodDate = now()->toDateString();
    }

    public function runSnapshot(AggregatorInsightsService $insights): void
    {
        abort_unless(Gate::forUser(auth()->user())->allows('network.analytics'), 403, 'Unauthorized: missing permission [network.analytics].');

        $aggregator = app(AggregatorTenantService::class)->requireCurrent();
        $date = \Illuminate\Support\Carbon::parse($this->eodDate ?: now()->toDateString());
        $row = $insights->snapshotDaily($aggregator, $date);

        $this->dispatch('toast', message: 'Read-model snapshot written for '.$row->metric_date->toDateString().'.', type: 'success');
    }

    public function render(
        AggregatorInsightsService $insights,
        AggregatorObservabilityService $observability
    ): View {
        $aggregator = app(AggregatorTenantService::class)->current();

        if ($aggregator === null) {
            return view('livewire.aggregator.insights', ['notProvisioned' => true, 'payload' => null]);
        }

        return view('livewire.aggregator.insights', [
            'notProvisioned' => false,
            'payload' => [
                'eod' => $insights->eod($aggregator, \Illuminate\Support\Carbon::parse($this->eodDate ?: now()->toDateString())),
                'eod_date' => $this->eodDate,
                'growth' => $insights->growth($aggregator),
                'retention' => $insights->retention($aggregator),
                'productivity' => $insights->productivity($aggregator),
                'series' => $insights->dailySeries($aggregator, now()->subDays(13)->toDateString(), now()->toDateString()),
                'observability' => $observability->indicators($aggregator),
                'canSnapshot' => Gate::forUser(auth()->user())->allows('network.analytics'),
            ],
        ]);
    }
}
