<?php

namespace App\Livewire\Aggregator;

use App\Domain\Aggregator\AggregatorAgentsService;
use App\Domain\Aggregator\AggregatorTenantService;
use App\Models\Aggregator;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * AGGREGATOR CONSOLE — Stage B (agent recruitment, §20).
 *
 * CAPTURE ONLY — the hard rule from §20: recruitment registers the agent
 * as PENDING with unverified KYC; nothing here activates them. Activation
 * happens backend-side only after KYC is approved. The submission is
 * audited end-to-end via AgencyService (§82).
 */
#[Layout('layouts.aggregator')]
class RecruitAgent extends Component
{
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $country = 'NE';
    public string $region = '';
    public string $city = '';
    public string $tier = 'bronze';
    public string $notes = '';

    public bool $created = false;
    public string $createdCode = '';

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:32', 'unique:users,phone_number'],
            'country' => ['required', Rule::in(['NE', 'NG'])],
            'region' => ['nullable', 'string', 'max:90'],
            'city' => ['nullable', 'string', 'max:90'],
            'tier' => ['required', Rule::in(['bronze', 'silver', 'gold'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function submit(AggregatorAgentsService $service): void
    {
        $this->validate();

        $aggregator = app(AggregatorTenantService::class)->current();
        if ($aggregator === null) {
            abort(403, 'No aggregator profile is provisioned for this account.');
        }

        $agent = $service->recruit($aggregator, [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'country_iso2' => $this->country,
            'region' => $this->region !== '' ? $this->region : null,
            'city' => $this->city !== '' ? $this->city : null,
            'tier' => $this->tier,
        ], auth()->id());

        $this->created = true;
        $this->createdCode = $agent->agent_code;

        $this->reset(['name', 'email', 'phone', 'region', 'city', 'notes']);
        $this->dispatch('toast', message: 'Agent captured — activation requires approved KYC.', type: 'success');
    }

    public function render(): View
    {
        return view('livewire.aggregator.recruit-agent', [
            'notProvisioned' => app(AggregatorTenantService::class)->current() === null,
        ]);
    }
}
