<?php

namespace App\Livewire\Aggregator;

use App\Domain\Aggregator\AggregatorInsightsService;
use App\Domain\Aggregator\AggregatorMetricsService;
use App\Domain\Aggregator\AggregatorObservabilityService;
use App\Domain\Aggregator\AggregatorTenantService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * AGGREGATOR CONSOLE — Stage A (home command center).
 *
 * All numbers come from AggregatorMetricsService (real records, tenant-
 * scoped, per-currency). Range + currency come from the global filter bar
 * (§9); they are query-string state so quick actions can deep-link.
 *
 * Honest states: no aggregator profile ⇒ "not provisioned"; no agents ⇒
 * zero KPIs + empty states, never fabricated figures.
 */
#[Layout('layouts.aggregator')]
class Dashboard extends Component
{
    #[Url(as: 'range', history: true)]
    public string $range = 'today';

    #[Url(as: 'currency', history: true)]
    public string $currency = '';

    public array $ranges = [
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        '7d' => '7 Days',
        '30d' => '30 Days',
        'month' => 'This Month',
        'prev_month' => 'Prev Month',
    ];

    public function updatedRange(): void
    {
        // Explicit reload — metrics are computed server-side per selection.
    }

    /** Format a money amount for display (thousands separator, no decimals). */
    public function money(string|float $value): string
    {
        return number_format((float) $value, 0);
    }

    /** Percent change vs previous period; '—' when there is no baseline. */
    public function deltaLabel(string|float $current, string|float $previous): string
    {
        $cur = (float) $current;
        $prev = (float) $previous;

        if ($prev <= 0) {
            return $cur == 0 ? '0%' : '—';
        }

        $pct = round(($cur - $prev) / $prev * 100, 1);

        return ($pct >= 0 ? '+' : '').$pct.'%';
    }

    public function render(
        AggregatorTenantService $tenant,
        AggregatorMetricsService $metrics,
        AggregatorInsightsService $insights,
        AggregatorObservabilityService $observability
    ): \Illuminate\Contracts\View\View {
        $aggregator = $tenant->current();

        if ($aggregator === null) {
            return view('livewire.aggregator.dashboard', [
                'notProvisioned' => true,
                'aggregator' => null,
                'center' => null,
                'eod' => null,
                'observability' => null,
            ]);
        }

        $filters = ['range' => $this->range];
        if ($this->currency !== '') {
            $filters['currency'] = $this->currency;
        }

        return view('livewire.aggregator.dashboard', [
            'notProvisioned' => false,
            'aggregator' => $aggregator,
            'center' => $metrics->commandCenter($aggregator, $filters),
            // Stage I §100–116: EOD summary + health signals surfaced on the
            // home screen — derived from real records, labelled authoritative.
            'eod' => $insights->eod($aggregator),
            'observability' => $observability->indicators($aggregator),
        ]);
    }
}
