<?php

namespace App\Livewire\Aggregator;

use App\Domain\Aggregator\AggregatorLiquidityService;
use App\Domain\Aggregator\AggregatorTenantService;
use App\Models\LiquidityRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * AGGREGATOR CONSOLE — Stage C (liquidity command center + request
 * workflow, §23–28).
 *
 * Reads ONLY ledger-sourced facts; forecasts/demand are labelled estimates.
 * Request actions (approve/reject/fund/cancel) are permission-gated
 * server-side (`liquidity.review`) and move money through the ledger — the
 * frontend never writes balances. Raising a request on an agent's behalf
 * requires `liquidity.request`.
 */
#[Layout('layouts.aggregator')]
class Liquidity extends Component
{
    public string $currency = '';
    public string $status = 'open';
    public int $page = 1;

    // Raise-on-behalf form.
    public string $agentId = '';
    public string $amount = '';
    public string $reason = 'cash_out_demand';

    // Per-request review notes keyed by request id.
    public array $notes = [];

    protected array $allowedStatuses = ['open', 'all', 'pending', 'approved', 'funded', 'rejected', 'cancelled'];

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
        $this->page = 1;
    }

    public function setStatus(string $status): void
    {
        $this->status = in_array($status, $this->allowedStatuses, true) ? $status : 'open';
        $this->page = 1;
    }

    public function gotoPage(int $page): void
    {
        $this->page = max(1, $page);
    }

    public function approve(int $id, AggregatorLiquidityService $service): void
    {
        abort_unless(Gate::forUser(auth()->user())->allows('liquidity.review'), 403, 'Unauthorized: missing permission [liquidity.review].');
        $request = $this->owned($id);
        $service->review($request, true, $this->notes[$id] ?? null, auth()->id());
        unset($this->notes[$id]);
        $this->dispatch('toast', message: 'Liquidity request approved — operational cash earmarked.', type: 'success');
    }

    public function reject(int $id, AggregatorLiquidityService $service): void
    {
        abort_unless(Gate::forUser(auth()->user())->allows('liquidity.review'), 403, 'Unauthorized: missing permission [liquidity.review].');
        $request = $this->owned($id);
        $service->review($request, false, $this->notes[$id] ?? null, auth()->id());
        unset($this->notes[$id]);
        $this->dispatch('toast', message: 'Liquidity request rejected.', type: 'warning');
    }

    public function fund(int $id, AggregatorLiquidityService $service): void
    {
        abort_unless(Gate::forUser(auth()->user())->allows('liquidity.review'), 403, 'Unauthorized: missing permission [liquidity.review].');
        $request = $this->owned($id);
        $service->fund($request, auth()->id());
        $this->dispatch('toast', message: 'Liquidity request funded — earmark released to agent float.', type: 'success');
    }

    public function cancel(int $id, AggregatorLiquidityService $service): void
    {
        abort_unless(Gate::forUser(auth()->user())->allows('liquidity.review'), 403, 'Unauthorized: missing permission [liquidity.review].');
        $request = $this->owned($id);
        $service->cancel($request, auth()->id(), $this->notes[$id] ?? null);
        unset($this->notes[$id]);
        $this->dispatch('toast', message: 'Liquidity request cancelled.', type: 'info');
    }

    public function createRequest(AggregatorLiquidityService $service): void
    {
        abort_unless(Gate::forUser(auth()->user())->allows('liquidity.request'), 403, 'Unauthorized: missing permission [liquidity.request].');
        $this->validate([
            'agentId' => ['required', 'integer'],
            'amount' => ['required', 'regex:/^\d+(\.\d{1,2})?$/', 'gt:0'],
            'reason' => ['required', 'in:cash_out_demand,restock,other'],
        ]);

        $aggregator = app(AggregatorTenantService::class)->current();
        $agent = $aggregator?->agents()->find((int) $this->agentId);

        if ($agent === null) {
            $this->addError('agentId', 'Select an agent from your network.');
            return;
        }

        $request = $service->submit($aggregator, $agent, [
            'amount' => $this->amount,
            'currency_code' => $agent->country_iso2 === 'NE' ? 'XOF' : 'NGN',
            'reason' => $this->reason,
            'requested_by_type' => 'aggregator',
        ], auth()->id());

        $this->reset(['agentId', 'amount', 'reason']);
        $this->status = 'open';
        $this->dispatch('toast', message: 'Liquidity request '.$request->reference.' raised for review.', type: 'success');
    }

    protected function owned(int $id): LiquidityRequest
    {
        $aggregator = app(AggregatorTenantService::class)->current();
        $request = LiquidityRequest::query()
            ->where('id', $id)
            ->where('aggregator_id', $aggregator?->id)
            ->first();

        abort_unless($request !== null, 404, 'Liquidity request not found in this network.');

        return $request;
    }

    public function render(AggregatorLiquidityService $service): View
    {
        $aggregator = app(AggregatorTenantService::class)->current();

        if ($aggregator === null) {
            return view('livewire.aggregator.liquidity', [
                'notProvisioned' => true,
                'payload' => null,
            ]);
        }

        $payload = $service->commandCenter($aggregator, ['currency' => $this->currency]);
        $payload['requests'] = $service->requests($aggregator, ['status' => $this->status], 10, $this->page);

        return view('livewire.aggregator.liquidity', [
            'notProvisioned' => false,
            'payload' => $payload,
        ]);
    }
}
