<?php

namespace App\Livewire\Customer;

use App\Domain\Customer\CustomerSecurityService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * CUSTOMER BANKING — Stage 5 (security center).
 *
 * Devices + limits + daily spend, all honest:
 *   - device rows come from the real `devices` table; empty ⇒ an explicit
 *     "insufficient usage data" state, never fabricated rows;
 *   - limits come from the country+currency wallet config that governs the
 *     wallet; unconfigured limits render as "not set";
 *   - daily spend today is computed from the customer's real transactions;
 *   - limit edits are SESSION-ONLY (CustomerSecurityService), they never
 *     touch the config tables or the ledger.
 */
#[Layout('layouts.customer')]
class Security extends Component
{
    public string $editingCurrency = '';
    public string $editingKind = '';
    public string $editValue = '';

    #[Computed]
    public function devices(): array
    {
        return app(CustomerSecurityService::class)->devices(Auth::user());
    }

    #[Computed]
    public function limits(): array
    {
        return app(CustomerSecurityService::class)->walletLimits(Auth::user());
    }

    #[Computed]
    public function sessionEdits(): array
    {
        return app(CustomerSecurityService::class)->sessionLimitEdits(Auth::user());
    }

    public function beginEdit(string $currency, string $kind): void
    {
        $this->editingCurrency = $currency;
        $this->editingKind = $kind;
        $edits = $this->sessionEdits;
        $this->editValue = (string) ($edits[$currency][$kind] ?? '');
    }

    public function saveEdit(): void
    {
        $this->validate([
            'editValue' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
        ]);

        app(CustomerSecurityService::class)->saveLimitEdit(
            Auth::user(), $this->editingCurrency, $this->editingKind, $this->editValue
        );

        $this->editingCurrency = '';
        $this->editingKind = '';
        $this->editValue = '';
        $this->dispatch('toast', message: 'Limit saved for this session only — nothing changed on the server.', type: 'info');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $security = app(CustomerSecurityService::class);

        return view('livewire.customer.security', [
            'security' => $security,
            'hasPin' => $security->pinEnrolled(Auth::user()),
        ]);
    }
}
