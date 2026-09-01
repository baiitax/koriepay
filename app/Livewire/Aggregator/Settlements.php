<?php

namespace App\Livewire\Aggregator;

use App\Domain\Aggregator\AggregatorSettlementsService;
use App\Domain\Aggregator\AggregatorTenantService;
use App\Models\AggregatorSettlement;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * AGGREGATOR CONSOLE — Stage E (settlement center, §38–43, §66–67).
 *
 * Settlement batches with the full breakdown (gross/fees/commission/
 * adjustments/net) and expected-vs-actual reconciliation. Transitions
 * (processing/settle/fail/under_review) move money through the ledger on
 * settle and are audited; batch rows are tenant-scoped server-side.
 */
#[Layout('layouts.aggregator')]
class Settlements extends Component
{
    public string $status = 'all';

    public function setStatus(string $status): void
    {
        $this->status = in_array($status, ['all', 'pending', 'processing', 'settled', 'failed', 'under_review'], true)
            ? $status : 'all';
    }

    public function createBatch(AggregatorSettlementsService $service): void
    {
        abort_unless(Gate::forUser(auth()->user())->allows('settlement.view'), 403, 'Unauthorized: missing permission [settlement.view].');
        $aggregator = app(AggregatorTenantService::class)->current();
        if ($aggregator === null) {
            abort(404);
        }

        $service->create($aggregator, $aggregator->country_iso2 === 'NE' ? 'XOF' : 'NGN', null, null, auth()->id());
        $this->dispatch('toast', message: 'Settlement batch created from accrued commissions.', type: 'success');
    }

    public function process(int $id, AggregatorSettlementsService $service): void
    {
        abort_unless(Gate::forUser(auth()->user())->allows('settlement.view'), 403, 'Unauthorized: missing permission [settlement.view].');
        $service->markProcessing($this->owned($id), auth()->id());
        $this->dispatch('toast', message: 'Batch moved to processing.', type: 'info');
    }

    public function settle(int $id, AggregatorSettlementsService $service): void
    {
        abort_unless(Gate::forUser(auth()->user())->allows('settlement.view'), 403, 'Unauthorized: missing permission [settlement.view].');
        $batch = $this->owned($id);
        $service->settle($batch, null, auth()->id());
        $this->dispatch('toast', message: 'Batch settled — payout posted to the aggregator float.', type: 'success');
    }

    public function fail(int $id, AggregatorSettlementsService $service): void
    {
        abort_unless(Gate::forUser(auth()->user())->allows('settlement.view'), 403, 'Unauthorized: missing permission [settlement.view].');
        $service->fail($this->owned($id), auth()->id(), 'Failed at the payout rail.');
        $this->dispatch('toast', message: 'Batch marked failed.', type: 'warning');
    }

    public function review(int $id, AggregatorSettlementsService $service): void
    {
        abort_unless(Gate::forUser(auth()->user())->allows('settlement.view'), 403, 'Unauthorized: missing permission [settlement.view].');
        $service->underReview($this->owned($id), auth()->id(), 'Placed under review by the aggregator.');
        $this->dispatch('toast', message: 'Batch placed under review.', type: 'info');
    }

    protected function owned(int $id): AggregatorSettlement
    {
        $aggregator = app(AggregatorTenantService::class)->current();
        $batch = AggregatorSettlement::query()
            ->where('id', $id)
            ->where('aggregator_id', $aggregator?->id)
            ->first();

        abort_unless($batch !== null, 404, 'Settlement batch not found in this network.');

        return $batch;
    }

    public function render(AggregatorSettlementsService $service): View
    {
        $aggregator = app(AggregatorTenantService::class)->current();

        if ($aggregator === null) {
            return view('livewire.aggregator.settlements', [
                'notProvisioned' => true,
                'payload' => null,
            ]);
        }

        return view('livewire.aggregator.settlements', [
            'notProvisioned' => false,
            'payload' => $service->center($aggregator, ['status' => $this->status]),
        ]);
    }
}
