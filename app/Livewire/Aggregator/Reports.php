<?php

namespace App\Livewire\Aggregator;

use App\Domain\Aggregator\AggregatorReportsService;
use App\Domain\Aggregator\AggregatorTenantService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * AGGREGATOR CONSOLE — Stage H (report center, §62, §65).
 *
 * Catalog + async generation. Requesting a report requires `report.generate`;
 * the artifact is produced by a queued job (idempotent) and downloaded only
 * when ready. Every request/completion/failure/download is audited.
 */
#[Layout('layouts.aggregator')]
class Reports extends Component
{
    public bool $showRequest = false;
    public string $type = 'agent';
    public string $format = 'csv';
    public string $dateFrom = '';
    public string $dateTo = '';

    public int $perPage = 10;
    public int $page = 1;

    public function mount(): void
    {
        $this->dateFrom = now()->subDays(30)->toDateString();
        $this->dateTo = now()->toDateString();
    }

    public function gotoPage(int $page): void
    {
        $this->page = max(1, $page);
    }

    public function openRequest(): void
    {
        $this->showRequest = true;
    }

    public function cancelRequest(): void
    {
        $this->showRequest = false;
    }

    public function request(AggregatorReportsService $service): void
    {
        abort_unless(Gate::forUser(auth()->user())->allows('report.generate'), 403, 'Unauthorized: missing permission [report.generate].');
        $this->validate([
            'type' => ['required', 'in:agent,transaction,commission,liquidity,settlement,risk,kyc,network_growth'],
            'format' => ['required', 'in:csv,xlsx,pdf'],
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date', 'after_or_equal:dateFrom'],
        ]);

        $aggregator = app(AggregatorTenantService::class)->requireCurrent();
        $job = $service->request($aggregator, auth()->user(), $this->type, $this->format, $this->dateFrom, $this->dateTo);

        $this->showRequest = false;
        $this->dispatch('toast', message: "Report {$job->reference} — {$job->type} / {$job->format} — ".($job->status === 'ready' ? 'generated.' : 'queued.'), type: 'success');
    }

    public function refresh(): void
    {
        // Re-render pulls the live status of every job.
        $this->dispatch('toast', message: 'Report statuses refreshed.', type: 'info');
    }

    public function render(AggregatorReportsService $service): View
    {
        $aggregator = app(AggregatorTenantService::class)->current();

        if ($aggregator === null) {
            return view('livewire.aggregator.reports', ['notProvisioned' => true, 'payload' => null]);
        }

        return view('livewire.aggregator.reports', [
            'notProvisioned' => false,
            'catalog' => $service->catalog(),
            'jobs' => $service->jobs($aggregator, $this->perPage, $this->page),
            'canGenerate' => Gate::forUser(auth()->user())->allows('report.generate'),
        ]);
    }
}
