<?php

namespace App\Livewire\Customer;

use App\Domain\Customer\CustomerSecurityService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * CUSTOMER BANKING — Stage 5 (profile).
 *
 * Two security cards that respect the brief's hard rule — the customer app
 * NEVER persists credentials:
 *
 *   - Mobile PIN: a digit-entry pad whose confirmation is transient. The PIN
 *     never leaves the browser; the confirmation dialog is Alpine-local and
 *     is gone on navigation. Nothing is stored, hashed or transmitted.
 *   - Biometric login: support is detected via the WebAuthn platform
 *     authenticator API; the toggle calls the session-only endpoint and is
 *     re-read from the session on render. No credential material anywhere.
 *
 * The photo upload is the only persisted profile change (avatar only).
 */
#[Layout('layouts.customer')]
class Profile extends Component
{
    use WithFileUploads;

    public $photo;
    public bool $biometricSupported = false;
    public bool $biometricEnabled = false;
    public bool $pinVisible = false;
    public string $pinDigits = '';

    public function mount(CustomerSecurityService $security): void
    {
        $this->biometricEnabled = $security->biometricEnabled(Auth::user());
        // `supported` is a client-side fact (WebAuthn availability); the
        // server only reports what the device.js probe already told us.
        $this->biometricSupported = (bool) session('security.biometric_supported', false);
    }

    public function savePhoto(): void
    {
        $this->validate(['photo' => 'image|max:2048']);

        $user = Auth::user();
        $path = $this->photo->store('profile-photos', 'public');
        // profile_photo_path stays out of $fillable (strict mass-assignment);
        // this single, validated UI action sets it explicitly.
        $user->forceFill(['profile_photo_path' => $path])->save();

        $this->reset('photo');
        $this->dispatch('toast', message: 'Profile image updated.', type: 'success');
    }

    /**
     * PIN confirm — transient, client-side. This method is intentionally a
     * no-op shell: the digit entry + confirmation happen in the browser and
     * the result never reaches the server. Kept as a named hook so the
     * Alpine overlay has a stable place to call, with an explicit comment
     * that nothing is persisted.
     */
    public function confirmPin(): void
    {
        // Intentionally empty — see class docblock. The 6-digit code typed in
        // the overlay stays in the browser session only.
        $this->pinDigits = '';
        $this->pinVisible = false;
        $this->dispatch('toast', message: 'PIN entry is demonstration-only — nothing was saved.', type: 'info');
    }

    public function clearPin(): void
    {
        $this->pinDigits = '';
        $this->pinVisible = false;
    }

    public function render(CustomerSecurityService $security): \Illuminate\Contracts\View\View
    {
        $user = Auth::user();

        return view('livewire.customer.profile', [
            'user' => $user,
            'biometricEnabled' => $security->biometricEnabled($user),
            'tierInfo' => $this->tierInfo($user),
        ]);
    }

    /**
     * Authorization-tier display card. Derived from the user's actual KYC
     * status — display-only guidance, never an enforcement claim. Limit
     * figures are the documented tier ceilings for this build.
     */
    protected function tierInfo(\Illuminate\Foundation\Auth\User $user): array
    {
        $status = strtolower((string) $user->kyc_status);

        return match ($status) {
            'verified' => [
                'color' => '#29B475',
                'level' => 'Tier 3 · Verified',
                'daily_limit' => 5000000,
                'route' => 'customer.kyc-center',
                'action_text' => 'Identity verified — manage in KYC center',
            ],
            'pending' => [
                'color' => '#FCDB1A',
                'level' => 'Tier 1 · Review in progress',
                'daily_limit' => 500000,
                'route' => 'customer.kyc-center',
                'action_text' => 'Review in progress — track status',
            ],
            default => [
                'color' => '#FCDB1A',
                'level' => 'Tier 0 · Unverified',
                'daily_limit' => 100000,
                'route' => 'customer.kyc-center',
                'action_text' => 'Verify your identity',
            ],
        };
    }
}
