<?php

namespace App\Livewire\Aggregator;

use App\Domain\Aggregator\AggregatorRiskService;
use App\Domain\Aggregator\AggregatorTenantService;
use App\Models\RiskAlert;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * AGGREGATOR CONSOLE — Stage G (risk & alerts, §52–57, §142–143).
 *
 * Risk center (velocity, collusion signals — always labelled risk signals,
 * never fraud — and KYC inconsistencies) plus the alert center with the
 * audited workflow (assign → investigate → resolve / false positive),
 * permission-gated by `risk.alert.resolve`, and grouped notifications.
 */
#[Layout('layouts.aggregator')]
class Risk extends Component
{
    public string $alertStatus = 'all';
    public array $notes = [];

    public function setAlertStatus(string $status): void
    {
        $this->alertStatus = in_array($status, ['all', 'open', 'acknowledged', 'investigating', 'resolved', 'false_positive'], true)
            ? $status : 'all';
    }

    public function assign(int $id, AggregatorRiskService $service): void
    {
        abort_unless(Gate::forUser(auth()->user())->allows('risk.alert.resolve'), 403, 'Unauthorized: missing permission [risk.alert.resolve].');
        $service->assign($this->owned($id), auth()->id());
        $this->dispatch('toast', message: 'Alert assigned to you for follow-up.', type: 'info');
    }

    public function investigate(int $id, AggregatorRiskService $service): void
    {
        abort_unless(Gate::forUser(auth()->user())->allows('risk.alert.resolve'), 403, 'Unauthorized: missing permission [risk.alert.resolve].');
        $service->investigate($this->owned($id), auth()->id());
        $this->dispatch('toast', message: 'Alert moved to investigating.', type: 'info');
    }

    public function resolve(int $id, AggregatorRiskService $service): void
    {
        abort_unless(Gate::forUser(auth()->user())->allows('risk.alert.resolve'), 403, 'Unauthorized: missing permission [risk.alert.resolve].');
        $alert = $this->owned($id);
        $service->resolve($alert, auth()->id(), $this->notes[$id] ?? null);
        unset($this->notes[$id]);
        $this->dispatch('toast', message: 'Alert resolved.', type: 'success');
    }

    public function falsePositive(int $id, AggregatorRiskService $service): void
    {
        abort_unless(Gate::forUser(auth()->user())->allows('risk.alert.resolve'), 403, 'Unauthorized: missing permission [risk.alert.resolve].');
        $alert = $this->owned($id);
        $service->falsePositive($alert, auth()->id(), $this->notes[$id] ?? null);
        unset($this->notes[$id]);
        $this->dispatch('toast', message: 'Alert marked false positive.', type: 'info');
    }

    protected function owned(int $id): RiskAlert
    {
        $aggregator = app(AggregatorTenantService::class)->current();
        $agentIds = app(AggregatorTenantService::class)->agentIds($aggregator);

        $alert = RiskAlert::query()
            ->where('id', $id)
            ->where(function ($q) use ($agentIds, $aggregator) {
                $q->where(fn ($qq) => $qq->where('entity_type', 'agent')->whereIn('entity_id', $agentIds))
                    ->orWhere(fn ($qq) => $qq->where('entity_type', 'aggregator')->where('entity_id', $aggregator?->id));
            })
            ->first();

        abort_unless($alert !== null, 404, 'Risk alert not found in this network.');

        return $alert;
    }

    public function render(AggregatorRiskService $service): View
    {
        $aggregator = app(AggregatorTenantService::class)->current();

        if ($aggregator === null) {
            return view('livewire.aggregator.risk', [
                'notProvisioned' => true,
                'payload' => null,
            ]);
        }

        return view('livewire.aggregator.risk', [
            'notProvisioned' => false,
            'payload' => [
                'velocity' => $service->velocity($aggregator),
                'signals' => $service->collusionSignals($aggregator),
                'kyc' => $service->kycInconsistencies($aggregator),
                'alerts' => $service->alerts($aggregator, $this->alertStatus),
                'notifications' => $service->notifications($aggregator),
                'alert_status' => $this->alertStatus,
            ],
        ]);
    }
}
