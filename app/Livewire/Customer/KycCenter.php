<?php

namespace App\Livewire\Customer;

use App\Domain\Customer\CustomerKycService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * CUSTOMER BANKING — Stage 5 (KYC center).
 *
 * Honest dashboard for identity verification:
 *   - status/tier come from CustomerKycService::revaluate() (real records);
 *   - ID-card actions are per-tier recommendations that route to the real
 *     verification journey (customer.kyc);
 *   - PIN verify is a digit-entry demo — transient, never stored;
 *   - digital identity: name/email are editable (persisted); date of birth is
 *     shown from the latest submitted ID document when on file — never
 *     invented when absent.
 */
#[Layout('layouts.customer')]
class KycCenter extends Component
{
    public string $pin = '';
    public bool $pinVerified = false;
    public string $displayName = '';
    public string $displayEmail = '';

    #[Computed]
    public function state(): array
    {
        return app(CustomerKycService::class)->revaluate(Auth::user());
    }

    public function mount(): void
    {
        $this->displayName = (string) Auth::user()->name;
        $this->displayEmail = (string) Auth::user()->email;
        $this->pinVerified = (bool) session('kyc.pin_verified.'.Auth::id(), false);
    }

    public function verifyPin(): void
    {
        // Demonstration only — the 6 digits are compared against a constant
        // demo token in-memory; no PIN is stored or hashed.
        if ($this->pin === '000000') {
            $this->pinVerified = true;
            session(['kyc.pin_verified.'.Auth::id() => true]);
            $this->dispatch('toast', message: 'Identity PIN verified (demo).', type: 'success');
        } else {
            $this->dispatch('toast', message: 'PIN did not match the demo token.', type: 'error');
        }
        $this->pin = '';
    }

    public function saveIdentity(): void
    {
        $this->validate([
            'displayName' => ['required', 'string', 'max:120'],
            'displayEmail' => ['required', 'email', 'max:190'],
        ]);

        $user = Auth::user();
        $emailTaken = \App\Models\User::where('email', $this->displayEmail)
            ->where('id', '!=', $user->id)->exists();

        if ($emailTaken) {
            $this->addError('displayEmail', 'That email is already in use by another account.');
            return;
        }

        $user->update(['name' => $this->displayName, 'email' => $this->displayEmail]);
        $this->dispatch('toast', message: 'Digital identity updated.', type: 'success');
    }

    public function render(CustomerKycService $kyc): \Illuminate\Contracts\View\View
    {
        $submission = \App\Models\KycSubmission::where('user_id', Auth::id())
            ->latest('submitted_at')->first();

        return view('livewire.customer.kyc-center', [
            'state' => $this->state,
            'submission' => $submission,
            'kyc' => $kyc,
        ]);
    }
}
