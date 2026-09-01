<?php

namespace App\Livewire\Aggregator;

use App\Domain\Aggregator\AggregatorCommissionsService;
use App\Domain\Aggregator\AggregatorTenantService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * AGGREGATOR CONSOLE — Stage E (commission intelligence, §38–43).
 *
 * Read-only. Overview (today/week/month), product breakdown by rule with
 * rule versions, earnings with every component shown separately (gross /
 * adjustments / reversals / net / paid / pending — gross is NEVER labelled
 * as net), per-agent table and the audited entry trail.
 */
#[Layout('layouts.aggregator')]
class Commissions extends Component
{
    public string $currency = '';

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    public function render(AggregatorCommissionsService $service): View
    {
        $aggregator = app(AggregatorTenantService::class)->current();

        if ($aggregator === null) {
            return view('livewire.aggregator.commissions', [
                'notProvisioned' => true,
                'payload' => null,
            ]);
        }

        return view('livewire.aggregator.commissions', [
            'notProvisioned' => false,
            'payload' => [
                'overview' => $service->overview($aggregator, $this->currency),
                'breakdown' => $service->productBreakdown($aggregator, $this->currency),
                'earnings' => $service->earnings($aggregator, $this->currency),
                'agents' => $service->agentCommissions($aggregator, $this->currency),
                'audit' => $service->auditTrail($aggregator, $this->currency, 50),
                'currency' => $this->currency,
            ],
        ]);
    }
}
