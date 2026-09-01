<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Livewire\Volt\Volt;
use Tests\TestCase;

/**
 * KoriePay password recovery is phone-based (OTP), not email-notification
 * based. Tests assert the REAL app contract.
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function userWithPhone(): User
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $user->forceFill(['phone_number' => '08012345678'])->save();

        return $user;
    }

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertSeeVolt('pages.auth.forgot-password');
    }

    public function test_reset_password_otp_can_be_requested(): void
    {
        $this->userWithPhone();

        $component = Volt::test('pages.auth.forgot-password')
            ->set('phone', '08012345678');

        $component->call('sendOtp');

        $component
            ->assertHasNoErrors()
            ->assertSet('step', 2);

        $this->assertNotNull(session('reset_otp'));
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        $this->get('/reset-password/some-token')
            ->assertOk()
            ->assertSeeVolt('pages.auth.reset-password');
    }

    public function test_password_can_be_reset_with_valid_otp(): void
    {
        Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 200)]);

        $user = $this->userWithPhone();

        $component = Volt::test('pages.auth.reset-password')
            ->set('phone', '08012345678');

        $component->call('sendOtp');
        $component->assertSet('step', 2);

        $otp = session('reset_otp');
        $this->assertNotNull($otp);

        $component->set('otp', $otp)->call('verifyOtp');
        $component->assertSet('step', 3);

        $component
            ->set('password', 'Str0ng!Passw0rd')
            ->set('password_confirmation', 'Str0ng!Passw0rd')
            ->call('resetPassword');

        $component->assertHasNoErrors()->assertSet('step', 4);

        $this->assertTrue(Hash::check('Str0ng!Passw0rd', $user->fresh()->password));
    }

    public function test_reset_requires_registered_phone(): void
    {
        $component = Volt::test('pages.auth.forgot-password')
            ->set('phone', '09999999999');

        $component->call('sendOtp');

        $component
            ->assertHasErrors(['phone'])
            ->assertSet('step', 1);
    }
}
