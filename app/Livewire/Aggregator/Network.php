<?php

namespace App\Livewire\Aggregator;

use App\Domain\Aggregator\AggregatorNetworkService;
use App\Domain\Aggregator\AggregatorTenantService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * AGGREGATOR CONSOLE — Stage F (network intelligence, §44–51).
 *
 * Read-only. Analytics trends (hourly → monthly), failure intelligence by
 * recorded cause, geographic coverage + measurable recruitment
 * recommendations, and the explained network health score.
 */
#[Layout('layouts.aggregator')]
class Network extends Component
{
    public string $range = 'daily';

    public function setRange(string $range): void
    {
        $this->range = in_array($range, ['hourly', 'daily', 'weekly', 'monthly'], true) ? $range : 'daily';
    }

    public function render(AggregatorNetworkService $service): View
    {
        $aggregator = app(AggregatorTenantService::class)->current();

        if ($aggregator === null) {
            return view('livewire.aggregator.network', [
                'notProvisioned' => true,
                'payload' => null,
            ]);
        }

        return view('livewire.aggregator.network', [
            'notProvisioned' => false,
            'payload' => [
                'analytics' => $service->analytics($aggregator, $this->range),
                'failures' => $service->failureIntelligence($aggregator),
                'coverage' => $service->coverage($aggregator),
                'health' => $service->networkHealth($aggregator),
                'range' => $this->range,
            ],
        ]);
    }
}
