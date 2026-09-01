<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

/**
 * KoriePay login is phone + password -> OTP (step 2) -> role-based redirect.
 * These tests assert the REAL app contract (not the stock Breeze skeleton).
 */
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function makeCustomerUser(): User
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        // Legacy $fillable omits these columns; set them directly.
        $user->forceFill([
            'phone_number' => '08012345678',
            'role' => 'customer',
            'kyc_tier' => 1, // eligible for the country's primary wallet (§75)
        ])->save();

        return $user;
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.login');
    }

    public function test_users_can_authenticate_using_phone_password_and_otp(): void
    {
        $this->makeCustomerUser();

        $component = Volt::test('pages.auth.login')
            ->set('phone', '08012345678')
            ->set('password', 'password');

        $component->call('authenticate');

        $component
            ->assertHasNoErrors()
            ->assertSet('step', 2);

        // The dev flow stores a temporary OTP in the session; the UI never
        // reveals it outside the SMS gateway (Phase 4: real OTP delivery).
        $otp = session('auth_otp');
        $this->assertNotNull($otp);

        $component->set('otp', $otp)->call('verifyOtp');

        $component->assertRedirect(route('customer.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $this->makeCustomerUser();

        $component = Volt::test('pages.auth.login')
            ->set('phone', '08012345678')
            ->set('password', 'wrong-password');

        $component->call('authenticate');

        $component
            ->assertHasErrors(['phone'])
            ->assertSet('step', 1);

        $this->assertGuest();
    }

    public function test_customer_dashboard_renders_for_customer_role(): void
    {
        $user = $this->makeCustomerUser();

        $this->actingAs($user)
            ->get('/customer/dashboard')
            ->assertOk();
    }

    public function test_users_can_logout(): void
    {
        $user = $this->makeCustomerUser();

        $this->actingAs($user);

        // The real logout contract is the POST /logout route (with CSRF).
        $this->post(route('logout'))->assertRedirect('/');

        $this->assertGuest();
    }
}
